<?php

namespace App\Http\Controllers;

use App\Imports\SdbUnitImport;
use App\Exports\SdbUnitExport;
use App\Models\SdbUnit;
use App\Services\SdbUnitService;
use App\Services\SdbLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SdbImportController extends Controller
{
  protected $sdbService;

  // Import action constants
  const ACTION_NEW_RENTAL = 'new_rental';
  const ACTION_CORRECTION = 'correction';
  const ACTION_SKIP = 'skip';

  // Session keys
  const SESSION_PREVIEW = 'import_preview';
  const SESSION_FILENAME = 'import_filename';
  const SESSION_TIMESTAMP = 'import_timestamp';

  // Session timeout (minutes)
  const SESSION_TIMEOUT = 30;

  // Security limits
  const MAX_FILE_SIZE = 5242880; // 5MB in bytes
  const MAX_ROWS_LIMIT = 1000;   // Prevent memory exhaustion

  public function __construct(SdbUnitService $sdbService)
  {
    $this->sdbService = $sdbService;
  }

  /**
   * IMPROVED: Export with sanitized filters and logging
   */
  public function export(Request $request)
  {
    // Validate filter inputs
    $validator = Validator::make($request->all(), [
      'search' => 'nullable|string|max:255',
      'status' => 'nullable|string|in:kosong,terisi,akan_jatuh_tempo,lewat_jatuh_tempo',
      'tipe' => 'nullable|string|in:B,C'
    ]);

    if ($validator->fails()) {
      SdbLogService::record(
        'EXPORT_VALIDATION_FAILED',
        'Export validation failed: ' . $validator->errors()->first()
      );

      return redirect()->route('dashboard')
        ->with('error', 'Parameter export tidak valid: ' . $validator->errors()->first());
    }

    // Extract validated filters
    $filters = array_filter($validator->validated(), function ($value) {
      return $value !== null && $value !== '';
    });

    // Generate descriptive filename
    $filterSuffix = '';
    if (!empty($filters)) {
      $parts = [];
      if (isset($filters['status'])) {
        $parts[] = ucfirst($filters['status']);
      }
      if (isset($filters['tipe'])) {
        $parts[] = 'Tipe' . $filters['tipe'];
      }
      if (isset($filters['search'])) {
        $parts[] = 'Search';
      }
      $filterSuffix = '_' . implode('_', $parts);
    }

    $filename = 'SDB_Export' . $filterSuffix . '_' . now()->format('Ymd_His') . '.xlsx';

    // Log export with details
    SdbLogService::record(
      'EXPORT_DATA',
      sprintf(
        'Export executed. Filters: %s. User: %s',
        !empty($filters) ? json_encode($filters, JSON_UNESCAPED_UNICODE) : 'NONE',
        auth()->user()->name
      )
    );

    try {
      return Excel::download(new SdbUnitExport($filters), $filename);
    } catch (\Exception $e) {
      SdbLogService::record(
        'EXPORT_ERROR',
        'Export failed: ' . $e->getMessage()
      );

      return redirect()->route('dashboard')
        ->with('error', 'Export gagal: ' . $e->getMessage());
    }
  }

  /**
   * IMPROVED: Upload with enhanced validation and security
   */
  public function upload(Request $request)
  {
    // Validate file upload with strict rules
    try {
      $validated = $request->validate([
        'file' => [
          'required',
          'file',
          'mimes:xlsx,xls,csv',
          'max:' . (self::MAX_FILE_SIZE / 1024) // Convert to KB for validator
        ]
      ], [
        'file.required' => 'File wajib dipilih.',
        'file.mimes' => 'Format file harus Excel (.xlsx, .xls) atau CSV (.csv).',
        'file.max' => 'Ukuran file maksimal 5MB.'
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      SdbLogService::record(
        'IMPORT_UPLOAD_VALIDATION_FAILED',
        'File validation failed: ' . $e->validator->errors()->first('file')
      );

      return redirect()->route('dashboard')
        ->with('import_error', $e->validator->errors()->first('file'))
        ->with('import_error_type', 'validation');
    }

    $file = $request->file('file');
    $originalName = $file->getClientOriginalName();

    // Additional MIME type security check
    $mimeType = $file->getMimeType();
    $allowedMimes = [
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
      'application/vnd.ms-excel', // .xls
      'text/csv',
      'text/plain'
    ];

    if (!in_array($mimeType, $allowedMimes)) {
      SdbLogService::record(
        'IMPORT_INVALID_MIME',
        "Invalid MIME type detected: {$mimeType} for file '{$originalName}'"
      );

      return redirect()->route('dashboard')
        ->with('import_error', 'Tipe file tidak valid. Pastikan file adalah Excel atau CSV asli.')
        ->with('import_error_type', 'security');
    }

    try {
      // Store file temporarily with secure naming
      $secureFilename = now()->timestamp . '_' . auth()->id() . '_' . $file->hashName();
      $tempPath = $file->storeAs('temp-imports', $secureFilename);

      // Parse file in preview mode
      $import = new SdbUnitImport(previewMode: true);
      Excel::import($import, $file);
      $results = $import->getResults();

      // SECURITY: Check row count limit
      if ($results['total'] > self::MAX_ROWS_LIMIT) {
        Storage::delete($tempPath);

        SdbLogService::record(
          'IMPORT_ROW_LIMIT_EXCEEDED',
          "Import rejected: {$results['total']} rows exceeds limit of " . self::MAX_ROWS_LIMIT
        );

        return redirect()->route('dashboard')
          ->with('import_error', "File terlalu besar: {$results['total']} baris. Maksimal: " . self::MAX_ROWS_LIMIT . " baris.")
          ->with('import_error_type', 'security');
      }

      // Add metadata
      $results['metadata'] = [
        'filename' => $originalName,
        'uploaded_at' => now()->format('d/m/Y H:i:s'),
        'uploaded_by' => auth()->user()->name,
        'filesize' => $this->formatBytes($file->getSize()),
        'total_rows' => $results['total']
      ];

      if (empty($results['errors'])) {
        // Success: Store in session with timestamp
        session([
          self::SESSION_PREVIEW => $results,
          self::SESSION_FILENAME => $tempPath,
          self::SESSION_TIMESTAMP => now()
        ]);

        SdbLogService::record(
          'IMPORT_PREVIEW',
          "Preview generated for '{$originalName}': {$results['total']} rows, " .
            count($results['new']) . " new, " .
            count($results['update']) . " updates"
        );
      } else {
        // Has errors: Clear session, delete temp file
        session()->forget([self::SESSION_PREVIEW, self::SESSION_FILENAME, self::SESSION_TIMESTAMP]);
        Storage::delete($tempPath);

        SdbLogService::record(
          'IMPORT_VALIDATION_FAILED',
          "Validation failed for '{$originalName}': " . count($results['errors']) . " errors"
        );
      }

      return view('sdb.import.preview', compact('results'));
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
      $failures = $e->failures();
      $errorMessage = "Struktur file tidak valid. ";

      if (count($failures) > 0) {
        $firstError = $failures[0];
        $errorMessage .= "Baris {$firstError->row()}: " . implode(', ', $firstError->errors());
      }

      SdbLogService::record(
        'IMPORT_STRUCTURE_ERROR',
        "Structure validation failed: {$errorMessage}"
      );

      return redirect()->route('dashboard')
        ->with('import_error', $errorMessage)
        ->with('import_error_type', 'structure');
    } catch (\Exception $e) {
      SdbLogService::record(
        'IMPORT_SYSTEM_ERROR',
        "System error during upload: {$e->getMessage()}"
      );

      return redirect()->route('dashboard')
        ->with('import_error', 'Terjadi kesalahan sistem: ' . $e->getMessage())
        ->with('import_error_type', 'system');
    }
  }

  /**
   * IMPROVED: Execute with enhanced transaction safety
   */
  public function execute(Request $request)
  {
    // Validate confirmation
    $request->validate([
      'confirmation' => 'required|in:SAYA YAKIN'
    ], [
      'confirmation.required' => 'Konfirmasi wajib diisi.',
      'confirmation.in' => 'Ketik "SAYA YAKIN" dengan benar (huruf kapital semua).'
    ]);

    // Check session validity
    $results = session(self::SESSION_PREVIEW);
    $timestamp = session(self::SESSION_TIMESTAMP);

    if (!$results || !$timestamp) {
      return redirect()->route('dashboard')
        ->with('error', 'Session import telah berakhir. Silakan upload file kembali.')
        ->with('import_error_type', 'expired');
    }

    // Check timeout
    if (now()->diffInMinutes($timestamp) > self::SESSION_TIMEOUT) {
      $this->cleanupImportSession();

      SdbLogService::record(
        'IMPORT_SESSION_TIMEOUT',
        'Import session expired after ' . self::SESSION_TIMEOUT . ' minutes'
      );

      return redirect()->route('dashboard')
        ->with('error', "Session kadaluarsa (melebihi " . self::SESSION_TIMEOUT . " menit).")
        ->with('import_error_type', 'expired');
    }

    // Additional safety: verify no errors
    if (!empty($results['errors'])) {
      $this->cleanupImportSession();

      return redirect()->route('dashboard')
        ->with('error', 'Data mengandung error. Import dibatalkan.')
        ->with('import_error_type', 'validation');
    }

    // Execute import in transaction
    DB::beginTransaction();

    try {
      $successCount = 0;
      $errorLog = [];

      // Process new rentals
      foreach ($results['new'] as $item) {
        try {
          $this->processNewRental($item);
          $successCount++;
        } catch (\Exception $e) {
          $errorLog[] = [
            'row' => $item['row'],
            'nomor_sdb' => $item['data']['nomor_sdb'],
            'error' => $e->getMessage()
          ];
        }
      }

      // Process corrections
      foreach ($results['update'] as $item) {
        try {
          $this->processCorrection($item);
          $successCount++;
        } catch (\Exception $e) {
          $errorLog[] = [
            'row' => $item['row'],
            'nomor_sdb' => $item['data']['nomor_sdb'],
            'error' => $e->getMessage()
          ];
        }
      }

      // Check for execution errors
      if (!empty($errorLog)) {
        DB::rollBack();

        SdbLogService::record(
          'IMPORT_EXECUTION_FAILED',
          'Execution failed with ' . count($errorLog) . ' errors'
        );

        return redirect()->route('dashboard')
          ->with('error', 'Import gagal: ' . count($errorLog) . ' baris error.')
          ->with('import_execution_errors', $errorLog)
          ->with('import_error_type', 'execution');
      }

      // Success: commit and cleanup
      DB::commit();

      SdbLogService::record(
        'IMPORT_EXECUTED',
        "Import success: {$successCount} records (" .
          count($results['new']) . " new, " .
          count($results['update']) . " updated) by " .
          auth()->user()->name
      );

      $this->cleanupImportSession();

      return redirect()->route('dashboard')
        ->with('success', "✅ Import berhasil! {$successCount} data diproses.")
        ->with('import_success_details', [
          'total' => $successCount,
          'new' => count($results['new']),
          'updated' => count($results['update'])
        ]);
    } catch (\Exception $e) {
      DB::rollBack();

      SdbLogService::record(
        'IMPORT_EXECUTION_ERROR',
        "Critical error during execution: {$e->getMessage()}"
      );

      return redirect()->route('dashboard')
        ->with('error', 'Kesalahan sistem: ' . $e->getMessage())
        ->with('import_error_type', 'system');
    }
  }

  /**
   * Cancel import
   */
  public function cancel()
  {
    SdbLogService::record(
      'IMPORT_CANCELLED',
      'Import cancelled by user: ' . auth()->user()->name
    );

    $this->cleanupImportSession();

    return redirect()->route('dashboard')
      ->with('info', 'Import dibatalkan.');
  }

  /**
   * Process new rental (using service layer)
   */
  protected function processNewRental(array $item): void
  {
    $unit = $item['unit'] ?? SdbUnit::where('nomor_sdb', $item['data']['nomor_sdb'])->first();

    if (!$unit) {
      $unit = SdbUnit::create([
        'nomor_sdb' => $item['data']['nomor_sdb'],
        'tipe' => strtoupper($item['data']['tipe'])
      ]);
    }

    $this->sdbService->startNewRental($unit, [
      'nama_nasabah' => $item['data']['nama_nasabah'],
      'tanggal_sewa' => $item['data']['tanggal_sewa'],
      'tanggal_jatuh_tempo' => $item['data']['tanggal_jatuh_tempo']
    ]);
  }

  /**
   * Process correction (using service layer)
   */
  protected function processCorrection(array $item): void
  {
    $unit = $item['unit'];

    $this->sdbService->correctTenantData($unit, [
      'nama_nasabah' => $item['data']['nama_nasabah'],
      'tanggal_sewa' => $item['data']['tanggal_sewa'],
      'tanggal_jatuh_tempo' => $item['data']['tanggal_jatuh_tempo']
    ]);
  }

  /**
   * Cleanup session and temp files
   */
  protected function cleanupImportSession(): void
  {
    $tempFile = session(self::SESSION_FILENAME);

    if ($tempFile && Storage::exists($tempFile)) {
      Storage::delete($tempFile);
    }

    session()->forget([
      self::SESSION_PREVIEW,
      self::SESSION_FILENAME,
      self::SESSION_TIMESTAMP
    ]);
  }

  /**
   * Format bytes to human readable
   */
  protected function formatBytes(int $bytes, int $precision = 2): string
  {
    $units = ['B', 'KB', 'MB', 'GB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
      $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
  }
}
