<?php

namespace App\Http\Controllers;

use App\Models\SdbUnit;
use Illuminate\Http\Request;
use App\Exports\SdbUnitExport;
use App\Imports\SdbUnitImport;
use App\Services\AuditService;
use App\Services\SdbLogService;
use App\Services\SdbUnitService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
   * Export dengan logging
   */
  public function export(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'search' => 'nullable|string|max:255',
      'status' => 'nullable|string|in:kosong,terisi,akan_jatuh_tempo,lewat_jatuh_tempo',
      'tipe' => 'nullable|string|in:B,C'
    ]);

    if ($validator->fails()) {
      return redirect()->route('dashboard')
        ->with('error', 'Parameter export tidak valid: ' . $validator->errors()->first());
    }

    $filters = array_filter($validator->validated(), function ($value) {
      return $value !== null && $value !== '';
    });

    $filename = 'SDB_Export_' . now()->format('Ymd_His') . '.xlsx';

    // ✅ LOGGING DITAMBAHKAN
    $recordCount = $this->getExportRecordCount($filters);
    AuditService::logExport('Excel', $filters, $recordCount);

    try {
      return Excel::download(new SdbUnitExport($filters), $filename);
    } catch (\Exception $e) {
      AuditService::log('EXPORT_ERROR', 'Export failed: ' . $e->getMessage());
      return redirect()->route('dashboard')
        ->with('error', 'Export gagal: ' . $e->getMessage());
    }
  }

  /**
   * Upload dengan logging
   */
  public function upload(Request $request)
  {
    try {
      $validated = $request->validate([
        'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:' . (self::MAX_FILE_SIZE / 1024)]
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      // ✅ LOGGING ERROR
      AuditService::logImport('error', 'validation_failed', [
        'error' => $e->validator->errors()->first('file')
      ]);

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

      // ✅ LOGGING UPLOAD
      AuditService::logImport('upload', $originalName, [
        'total_rows' => $results['total'],
        'new' => count($results['new']),
        'updates' => count($results['update']),
        'errors' => count($results['errors']),
      ]);

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
        session()->forget([self::SESSION_PREVIEW, self::SESSION_FILENAME, self::SESSION_TIMESTAMP]);
        Storage::delete($tempPath);

        // ✅ LOGGING VALIDATION FAILED
        AuditService::logImport('error', $originalName, [
          'phase' => 'validation',
          'error_count' => count($results['errors'])
        ]);

        return view('sdb.import.preview', compact('results'));
      }

      if (!$hasChanges) {
        Storage::delete($tempPath);

        // ✅ LOGGING NO CHANGES
        AuditService::logImport('cancel', $originalName, [
          'reason' => 'no_changes',
          'total_rows' => $results['total']
        ]);

        return redirect()->route('dashboard')
          ->with('import_info', '📋 Import dibatalkan: Tidak ada perubahan data yang terdeteksi.');
      }

      // HAS CHANGES: Store in session for confirmation
      session([
        self::SESSION_PREVIEW => $results,
        self::SESSION_FILENAME => $tempPath,
        self::SESSION_TIMESTAMP => now()
      ]);

      // ✅ LOGGING PREVIEW
      AuditService::logImport('preview', $originalName, [
        'total' => $results['total'],
        'new' => count($results['new']),
        'updates' => count($results['update'])
      ]);

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
      // ✅ LOGGING SYSTEM ERROR
      AuditService::logImport('error', $originalName, [
        'phase' => 'processing',
        'error' => $e->getMessage()
      ]);

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

        // ✅ LOGGING EXECUTION FAILED
        AuditService::logImport('error', 'execution', [
          'error_count' => count($errorLog),
          'errors' => $errorLog
        ]);

        return redirect()->route('dashboard')
          ->with('error', 'Import gagal: ' . count($errorLog) . ' baris error.')
          ->with('import_execution_errors', $errorLog)
          ->with('import_error_type', 'execution');
      }

      DB::commit();

      // ✅ LOGGING SUCCESS
      AuditService::logImport('execute', 'import_success', [
        'total_processed' => $successCount,
        'new_rentals' => count($results['new']),
        'corrections' => count($results['update'])
      ]);

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

      // ✅ LOGGING CRITICAL ERROR
      AuditService::logImport('error', 'critical', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);

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
    $results = session(self::SESSION_PREVIEW);

    // ✅ LOGGING CANCEL
    AuditService::logImport('cancel', 'user_cancelled', [
      'reason' => 'manual_cancellation',
      'pending_changes' => [
        'new' => count($results['new'] ?? []),
        'updates' => count($results['update'] ?? [])
      ]
    ]);

    $this->cleanupImportSession();

    return redirect()->route('dashboard')
      ->with('info', 'Import dibatalkan.');
  }

  /**
   * Helper: Get export record count
   */
  private function getExportRecordCount(array $filters): int
  {
    $query = SdbUnit::query();

    if (!empty($filters['search'])) {
      $query->search($filters['search']);
    }
    if (!empty($filters['status'])) {
      $query->byStatus($filters['status']);
    }
    if (!empty($filters['tipe'])) {
      $query->byTipe($filters['tipe']);
    }

    return $query->count();
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
