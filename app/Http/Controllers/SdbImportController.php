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

  const ACTION_NEW_RENTAL = 'new_rental';
  const ACTION_CORRECTION = 'correction';
  const ACTION_SKIP = 'skip';

  const SESSION_PREVIEW = 'import_preview';
  const SESSION_FILENAME = 'import_filename';
  const SESSION_TIMESTAMP = 'import_timestamp';
  const SESSION_TIMEOUT = 30;

  const MAX_FILE_SIZE = 5242880;
  const MAX_ROWS_LIMIT = 1000;

  public function __construct(SdbUnitService $sdbService)
  {
    $this->sdbService = $sdbService;
  }

  /**
   * Export with sanitized filters and logging
   */
  public function export(Request $request)
  {
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

    $filters = array_filter($validator->validated(), function ($value) {
      return $value !== null && $value !== '';
    });

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
   * IMPROVED: Upload with comprehensive validation
   */
  public function upload(Request $request)
  {
    try {
      $validated = $request->validate([
        'file' => [
          'required',
          'file',
          'mimes:xlsx,xls,csv',
          'max:' . (self::MAX_FILE_SIZE / 1024)
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

    $mimeType = $file->getMimeType();
    $allowedMimes = [
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/vnd.ms-excel',
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
      $secureFilename = now()->timestamp . '_' . auth()->id() . '_' . $file->hashName();
      $tempPath = $file->storeAs('temp-imports', $secureFilename);

      $import = new SdbUnitImport(previewMode: true);
      Excel::import($import, $file);
      $results = $import->getResults();

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

      $results['metadata'] = [
        'filename' => $originalName,
        'uploaded_at' => now()->format('d/m/Y H:i:s'),
        'uploaded_by' => auth()->user()->name,
        'filesize' => $this->formatBytes($file->getSize()),
        'total_rows' => $results['total']
      ];

      // ============================================================
      // CRITICAL FIX: Check if there are ANY changes to import
      // ============================================================
      $hasChanges = count($results['new']) > 0 || count($results['update']) > 0;
      $hasErrors = count($results['errors']) > 0;

      if ($hasErrors) {
        // Has errors: Delete temp file and show errors
        session()->forget([self::SESSION_PREVIEW, self::SESSION_FILENAME, self::SESSION_TIMESTAMP]);
        Storage::delete($tempPath);

        SdbLogService::record(
          'IMPORT_VALIDATION_FAILED',
          "Validation failed for '{$originalName}': " . count($results['errors']) . " errors"
        );

        return view('sdb.import.preview', compact('results'));
      }

      if (!$hasChanges) {
        // NO CHANGES DETECTED: Early exit with info message
        Storage::delete($tempPath);

        SdbLogService::record(
          'IMPORT_NO_CHANGES',
          "Import aborted for '{$originalName}': No changes detected (all data identical)"
        );

        return redirect()->route('dashboard')
          ->with('import_info', '📋 Import dibatalkan: Tidak ada perubahan data yang terdeteksi. Semua data di Excel identik dengan database.')
          ->with('import_info_details', [
            'total_rows' => $results['total'],
            'skipped' => count($results['skipped']),
            'message' => 'Semua baris dalam file Excel sudah sesuai dengan data di database.'
          ]);
      }

      // HAS CHANGES: Store in session for confirmation
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
   * Execute import with enhanced transaction safety
   */
  public function execute(Request $request)
  {
    $request->validate([
      'confirmation' => 'required|in:SAYA YAKIN'
    ], [
      'confirmation.required' => 'Konfirmasi wajib diisi.',
      'confirmation.in' => 'Ketik "SAYA YAKIN" dengan benar (huruf kapital semua).'
    ]);

    $results = session(self::SESSION_PREVIEW);
    $timestamp = session(self::SESSION_TIMESTAMP);

    if (!$results || !$timestamp) {
      return redirect()->route('dashboard')
        ->with('error', 'Session import telah berakhir. Silakan upload file kembali.')
        ->with('import_error_type', 'expired');
    }

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

    if (!empty($results['errors'])) {
      $this->cleanupImportSession();

      return redirect()->route('dashboard')
        ->with('error', 'Data mengandung error. Import dibatalkan.')
        ->with('import_error_type', 'validation');
    }

    // ADDITIONAL CHECK: Verify there are actual changes to import
    $hasChanges = count($results['new']) > 0 || count($results['update']) > 0;

    if (!$hasChanges) {
      $this->cleanupImportSession();

      return redirect()->route('dashboard')
        ->with('info', 'Import dibatalkan: Tidak ada perubahan data yang terdeteksi.');
    }

    DB::beginTransaction();

    try {
      $successCount = 0;
      $errorLog = [];

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
   * Process new rental
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
   * Process correction
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
