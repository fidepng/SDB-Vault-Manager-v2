<?php

namespace App\Services;

use App\Models\SdbLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class SdbLogService
{
  /**
   * Mencatat aktivitas ke dalam tabel sdb_logs.
   * * @param string $kegiatan (Contoh: 'LOGIN', 'LOGOUT', 'USER_UPDATE')
   * @param string $deskripsi Detail aktivitas
   * @param int|null $sdbUnitId (Opsional) Jika terkait unit SDB tertentu
   * @param int|null $userId (Opsional) Jika ingin mencatat user spesifik (misal saat login gagal/berhasil)
   */
  public static function record(string $kegiatan, string $deskripsi, ?int $sdbUnitId = null, ?int $userId = null)
  {
    // Jika userId tidak diisi, ambil dari user yang sedang login
    $targetUserId = $userId ?? Auth::id();

    SdbLog::create([
      'sdb_unit_id' => $sdbUnitId,
      'user_id'     => $targetUserId,
      'kegiatan'    => $kegiatan,
      'deskripsi'   => $deskripsi,
      'ip_address'  => Request::ip(), // Otomatis catat IP pelakunya
      'timestamp'   => now(),
    ]);
  }
}