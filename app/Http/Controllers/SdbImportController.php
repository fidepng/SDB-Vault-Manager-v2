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

  public function __construct(SdbUnitService $sdbService)
  {
    $this->sdbService = $sdbService;
  }

  /**
   * Export data with current filters
   * 
   * FIX: Properly capture and validate query parameters
   */
  public function export(Request $request)
  {
    // Validate and sanitize filter inputs
    $validated = $request->validate([
      'search' => 'nullable|string|max:255',
      'status' => 'nullable|string|in:kosong,terisi,akan_jatuh_tempo,lewat_jatuh_tempo',
      'tipe' => 'nullable|string|in:B,C'
    ]);

    // Extract only provided filters (remove null values)
    $filters = array_filter($validated, function ($value) {
      return $value !== null && $value !== '';
    });

    // Generate descriptive filename with filter info
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

    // Log with actual filter values for debugging
    SdbLogService::record(
      'EXPORT_DATA',
      sprintf(
        'Export executed. Filters applied: %s. Total params: %d',
        !empty($filters) ? json_encode($filters, JSON_UNESCAPED_UNICODE) : 'NONE',
        count($filters)
      )
    );

    // Pass validated filters to export class
    return Excel::download(new SdbUnitExport($filters), $filename);
  }

  /**
   * Upload and preview import file
   */
  public function upload(Request $request)
  {
    // Validate file upload
    try {
      $validated = $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv|max:5120'
      ], [
        'file.required' => 'File wajib dipilih.',
        'file.mimes' => 'Format file harus Excel (.xlsx, .xls) atau CSV (.csv).',
        'file.max' => 'Ukuran file maksimal 5MB (5120KB).'
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      return redirect()->route('dashboard')
        ->with('import_error', $e->validator->errors()->first('file'))
        ->with('import_error_type', 'validation');
    }

    $file = $request->file('file');
    $originalName = $file->getClientOriginalName();

    try {
      // Store file temporarily for potential re-processing
      $tempPath = $file->store('temp-imports');

      // Parse file in preview mode
      $import = new SdbUnitImport(previewMode: true);
      Excel::import($import, $file);
      $results = $import->getResults();

      // Add metadata
      $results['metadata'] = [
        'filename' => $originalName,
        'uploaded_at' => now()->format('d/m/Y H:i:s'),
        'uploaded_by' => auth()->user()->name,
        'filesize' => $this->formatBytes($file->getSize())
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
          "Import preview generated: {$results['total']} rows, " .
            count($results['new']) . " new, " .
            count($results['update']) . " updates, " .
            count($results['skipped']) . " skipped"
        );
      } else {
        // Has errors: Clear any previous session, delete temp file
        session()->forget([self::SESSION_PREVIEW, self::SESSION_FILENAME, self::SESSION_TIMESTAMP]);
        Storage::delete($tempPath);

        SdbLogService::record(
          'IMPORT_VALIDATION_FAILED',
          "Import validation failed: " . count($results['errors']) . " errors found in file '{$originalName}'"
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

      return redirect()->route('dashboard')
        ->with('import_error', $errorMessage)
        ->with('import_error_type', 'structure');
    } catch (\Exception $e) {
      SdbLogService::record(
        'IMPORT_ERROR',
        "Import system error: {$e->getMessage()}"
      );

      return redirect()->route('dashboard')
        ->with('import_error', 'Terjadi kesalahan sistem: ' . $e->getMessage())
        ->with('import_error_type', 'system');
    }
  }

  /**
   * Execute validated import
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

    // Check session data exists and not expired
    $results = session(self::SESSION_PREVIEW);
    $timestamp = session(self::SESSION_TIMESTAMP);

    if (!$results || !$timestamp) {
      return redirect()->route('dashboard')
        ->with('error', 'Session import telah berakhir. Silakan upload file kembali.')
        ->with('import_error_type', 'expired');
    }

    // Check session timeout
    if (now()->diffInMinutes($timestamp) > self::SESSION_TIMEOUT) {
      $this->cleanupImportSession();

      return redirect()->route('dashboard')
        ->with('error', "Session import kadaluarsa (melebihi " . self::SESSION_TIMEOUT . " menit). Silakan upload file kembali.")
        ->with('import_error_type', 'expired');
    }

    // Additional safety check: verify no errors in preview
    if (!empty($results['errors'])) {
      $this->cleanupImportSession();

      return redirect()->route('dashboard')
        ->with('error', 'Data mengandung error. Import dibatalkan untuk keamanan.')
        ->with('import_error_type', 'validation');
    }

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

        return redirect()->route('dashboard')
          ->with('error', 'Import gagal dieksekusi. Ada error pada ' . count($errorLog) . ' baris data.')
          ->with('import_execution_errors', $errorLog)
          ->with('import_error_type', 'execution');
      }

      // Success: commit and cleanup
      DB::commit();

      SdbLogService::record(
        'IMPORT_EXECUTED',
        "Import successfully executed: {$successCount} records processed (" .
          count($results['new']) . " new, " .
          count($results['update']) . " updated)"
      );

      $this->cleanupImportSession();

      return redirect()->route('dashboard')
        ->with('success', "✅ Import berhasil! {$successCount} data telah diproses.")
        ->with('import_success_details', [
          'total' => $successCount,
          'new' => count($results['new']),
          'updated' => count($results['update'])
        ]);
    } catch (\Exception $e) {
      DB::rollBack();

      SdbLogService::record(
        'IMPORT_EXECUTION_FAILED',
        "Import execution failed: {$e->getMessage()}"
      );

      return redirect()->route('dashboard')
        ->with('error', 'Terjadi kesalahan sistem saat eksekusi: ' . $e->getMessage())
        ->with('import_error_type', 'system');
    }
  }

  /**
   * Cancel import and cleanup
   */
  public function cancel()
  {
    $this->cleanupImportSession();

    return redirect()->route('dashboard')
      ->with('info', 'Import dibatalkan. Data tidak ada yang berubah.');
  }

  /**
   * Process new rental from import
   */
  protected function processNewRental(array $item): void
  {
    $unit = $item['unit'] ?? SdbUnit::where('nomor_sdb', $item['data']['nomor_sdb'])->first();

    // Create physical unit if doesn't exist
    if (!$unit) {
      $unit = SdbUnit::create([
        'nomor_sdb' => $item['data']['nomor_sdb'],
        'tipe' => strtoupper($item['data']['tipe'])
      ]);
    }

    // Use service layer for audit trail
    $this->sdbService->startNewRental($unit, [
      'nama_nasabah' => $item['data']['nama_nasabah'],
      'tanggal_sewa' => $item['data']['tanggal_sewa'],
      'tanggal_jatuh_tempo' => $item['data']['tanggal_jatuh_tempo']
    ]);
  }

  /**
   * Process data correction from import
   */
  protected function processCorrection(array $item): void
  {
    $unit = $item['unit'];

    // Use service layer for audit trail
    $this->sdbService->correctTenantData($unit, [
      'nama_nasabah' => $item['data']['nama_nasabah'],
      'tanggal_sewa' => $item['data']['tanggal_sewa'],
      'tanggal_jatuh_tempo' => $item['data']['tanggal_jatuh_tempo']
    ]);
  }

  /**
   * Cleanup import session and temp files
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
