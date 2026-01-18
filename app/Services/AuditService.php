<?php

namespace App\Services;

use App\Models\SdbLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Centralized Audit Service
 * 
 * Best Practice: Semua logging harus melalui service ini
 * untuk konsistensi format dan kemudahan maintenance.
 */
class AuditService
{
  /**
   * Log aktivitas standar
   */
  public static function log(
    string $activity,
    string $description,
    ?int $sdbUnitId = null,
    array $metadata = []
  ): void {
    self::record([
      'kegiatan' => $activity,
      'deskripsi' => $description,
      'sdb_unit_id' => $sdbUnitId,
      'metadata' => $metadata
    ]);
  }

  /**
   * Log aktivitas dengan context tambahan
   */
  public static function logWithContext(
    string $activity,
    string $description,
    array $context = []
  ): void {
    $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);

    self::record([
      'kegiatan' => $activity,
      'deskripsi' => $description . $contextStr,
      'sdb_unit_id' => $context['sdb_unit_id'] ?? null,
    ]);
  }

  /**
   * Log perubahan data (untuk edit/update)
   */
  public static function logDataChange(
    string $entity,
    int $entityId,
    array $changes,
    ?int $sdbUnitId = null
  ): void {
    $description = "Perubahan data {$entity} ID:{$entityId} - "
      . json_encode($changes, JSON_UNESCAPED_UNICODE);

    self::record([
      'kegiatan' => 'DATA_CHANGE',
      'deskripsi' => $description,
      'sdb_unit_id' => $sdbUnitId,
    ]);
  }

  /**
   * Log akses/view (untuk tracking siapa yang melihat data sensitif)
   */
  public static function logAccess(
    string $resource,
    int $resourceId,
    string $action = 'VIEW'
  ): void {
    self::record([
      'kegiatan' => "ACCESS_{$action}",
      'deskripsi' => "User mengakses {$resource} ID:{$resourceId}",
      'sdb_unit_id' => null,
    ]);
  }

  /**
   * Log export data
   */
  public static function logExport(
    string $format,
    array $filters = [],
    int $recordCount = 0
  ): void {
    $filterStr = empty($filters)
      ? 'SEMUA DATA'
      : 'Filter: ' . json_encode($filters, JSON_UNESCAPED_UNICODE);

    self::record([
      'kegiatan' => 'EXPORT_DATA',
      'deskripsi' => "Export {$recordCount} records ke {$format}. {$filterStr}",
      'sdb_unit_id' => null,
    ]);
  }

  /**
   * Log import data
   */
  public static function logImport(
    string $phase,
    string $filename,
    array $stats = []
  ): void {
    $statsStr = empty($stats)
      ? ''
      : ' | Stats: ' . json_encode($stats, JSON_UNESCAPED_UNICODE);

    $activities = [
      'upload' => 'IMPORT_UPLOAD',
      'preview' => 'IMPORT_PREVIEW',
      'execute' => 'IMPORT_EXECUTE',
      'cancel' => 'IMPORT_CANCEL',
      'error' => 'IMPORT_ERROR',
    ];

    self::record([
      'kegiatan' => $activities[$phase] ?? 'IMPORT_UNKNOWN',
      'deskripsi' => "Import {$phase}: {$filename}{$statsStr}",
      'sdb_unit_id' => null,
    ]);
  }

  /**
   * Log failed authentication
   */
  public static function logFailedLogin(string $email, string $reason = 'Invalid credentials'): void
  {
    SdbLog::create([
      'user_id' => null, // Tidak ada user karena login gagal
      'kegiatan' => 'LOGIN_FAILED',
      'deskripsi' => "Failed login attempt for: {$email}. Reason: {$reason}",
      'ip_address' => Request::ip(),
      'timestamp' => now(),
    ]);
  }

  /**
   * Core logging method
   */
  private static function record(array $data): void
  {
    try {
      SdbLog::create([
        'sdb_unit_id' => $data['sdb_unit_id'] ?? null,
        'user_id' => Auth::id(),
        'kegiatan' => $data['kegiatan'],
        'deskripsi' => $data['deskripsi'],
        'ip_address' => Request::ip(),
        'timestamp' => now(),
      ]);
    } catch (\Exception $e) {
      // Logging TIDAK BOLEH membuat sistem crash
      // Catat di Laravel Log sebagai fallback
      \Log::error('Audit logging failed: ' . $e->getMessage(), [
        'data' => $data,
        'user' => Auth::id(),
        'ip' => Request::ip(),
      ]);
    }
  }

  /**
   * Bulk logging untuk operasi batch
   */
  public static function logBatch(array $logs): void
  {
    try {
      $records = array_map(function ($log) {
        return [
          'sdb_unit_id' => $log['sdb_unit_id'] ?? null,
          'user_id' => Auth::id(),
          'kegiatan' => $log['kegiatan'],
          'deskripsi' => $log['deskripsi'],
          'ip_address' => Request::ip(),
          'timestamp' => now(),
          'created_at' => now(),
          'updated_at' => now(),
        ];
      }, $logs);

      SdbLog::insert($records);
    } catch (\Exception $e) {
      \Log::error('Batch audit logging failed: ' . $e->getMessage());
    }
  }
}
