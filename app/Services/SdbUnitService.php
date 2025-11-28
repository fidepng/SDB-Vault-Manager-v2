<?php

namespace App\Services;

use App\Models\SdbUnit;
use App\Models\SdbRentalHistory;
use App\Services\SdbLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SdbUnitService
{
  /**
   * Memproses pembaruan data penyewa (KOREKSI DATA / EDIT).
   */
  /**
   * Memproses pembaruan data penyewa (Bisa SEWA BARU atau KOREKSI DATA).
   */
  public function updateTenant(SdbUnit $sdbUnit, array $validatedData): SdbUnit
  {
    // Validasi
    if (!empty($validatedData['nama_nasabah']) && empty($validatedData['tanggal_sewa'])) {
      throw ValidationException::withMessages(['tanggal_sewa' => 'Tanggal sewa wajib diisi.']);
    }

    // Auto-Calculate Jatuh Tempo (Aturan 1 Tahun)
    if (!empty($validatedData['tanggal_sewa'])) {
      if (empty($validatedData['tanggal_jatuh_tempo'])) {
        $validatedData['tanggal_jatuh_tempo'] = Carbon::parse($validatedData['tanggal_sewa'])
          ->addYear()
          ->toDateString();
      }
    }

    $oldData = $sdbUnit->toArray();

    // [LOGIC BARU] Tentukan Jenis Kegiatan
    $activityType = 'EDIT_DATA'; // Default: Koreksi Data

    // Cek: Apakah sebelumnya kosong? Jika ya, berarti ini Sewa Baru.
    if (empty($oldData['nama_nasabah']) && !empty($validatedData['nama_nasabah'])) {
      $activityType = 'PENYEWAAN_BARU';
    }

    DB::transaction(function () use ($sdbUnit, $validatedData, $oldData, $activityType) {
      $sdbUnit->update($validatedData);

      // Gunakan activityType hasil deteksi di atas
      $this->trackAndLogChanges($sdbUnit, $oldData, $validatedData, $activityType);
    });

    return $sdbUnit->fresh();
  }

  /**
   * Mengakhiri masa sewa sebuah unit SDB.
   */
  public function endRental(SdbUnit $sdbUnit): SdbUnit
  {
    if (!$sdbUnit->is_rented) {
      throw new \Exception('SDB sudah dalam keadaan kosong.', 400);
    }

    DB::transaction(function () use ($sdbUnit) {
      // Snapshot ke History
      $mulai = Carbon::parse($sdbUnit->tanggal_sewa);
      $akhir = Carbon::parse($sdbUnit->tanggal_jatuh_tempo);

      $durasi = $mulai->diffInYears($akhir);
      if ($durasi < 1) $durasi = 1;

      SdbRentalHistory::create([
        'sdb_unit_id'    => $sdbUnit->id,
        'nomor_sdb'      => $sdbUnit->nomor_sdb,
        'nama_nasabah'   => $sdbUnit->nama_nasabah,
        'tanggal_mulai'  => $sdbUnit->tanggal_sewa,
        'tanggal_berakhir' => now()->toDateString(),
        'durasi_tahun'   => $durasi,
        'status_akhir'   => 'selesai',
        'catatan'        => 'Sewa diakhiri melalui sistem',
      ]);

      // Log Audit
      $this->createLog(
        $sdbUnit,
        'SEWA_BERAKHIR',
        "Sewa SDB {$sdbUnit->nomor_sdb} berakhir untuk {$sdbUnit->nama_nasabah}"
      );

      // Bersihkan Unit
      $sdbUnit->update(['nama_nasabah' => null, 'tanggal_sewa' => null, 'tanggal_jatuh_tempo' => null]);
    });

    return $sdbUnit->fresh();
  }

  /**
   * Memperpanjang masa sewa unit SDB.
   */
  public function extendRental(SdbUnit $sdbUnit, array $validatedData): SdbUnit
  {
    if (!$sdbUnit->is_rented) {
      throw new \Exception('SDB ini dalam keadaan kosong.', 400);
    }

    DB::transaction(function () use ($sdbUnit, $validatedData) {
      // Data Lama
      $oldMulai = Carbon::parse($sdbUnit->tanggal_sewa);
      $oldJatuhTempo = Carbon::parse($sdbUnit->tanggal_jatuh_tempo);
      $oldName = $sdbUnit->nama_nasabah;
      $today = Carbon::now();

      // Logic Continuity vs Reset
      if ($today->lte($oldJatuhTempo)) {
        $newStart = $oldJatuhTempo->copy()->addDay(); // H+1
        $catatanHistory = 'Perpanjangan tepat waktu (Periode bersambung)';
      } else {
        $newStart = $today; // Reset hari ini
        $gap = $oldJatuhTempo->diffInDays($today);
        $catatanHistory = 'Perpanjangan terlambat (Gap waktu: ' . $gap . ' hari)';
      }

      $newEnd = $newStart->copy()->addYear()->subDay();

      // Simpan History Lama
      $durasiLama = $oldMulai->diffInYears($oldJatuhTempo);
      if ($durasiLama < 1) $durasiLama = 1;

      SdbRentalHistory::create([
        'sdb_unit_id'    => $sdbUnit->id,
        'nomor_sdb'      => $sdbUnit->nomor_sdb,
        'nama_nasabah'   => $oldName,
        'tanggal_mulai'  => $oldMulai->toDateString(),
        'tanggal_berakhir' => $oldJatuhTempo->toDateString(),
        'durasi_tahun'   => $durasiLama,
        'status_akhir'   => 'selesai',
        'catatan'        => $catatanHistory,
      ]);

      // Update Unit
      $newName = $validatedData['nama_nasabah'];
      $nameHasChanged = $oldName !== $newName;

      $sdbUnit->update([
        'nama_nasabah' => $newName,
        'tanggal_sewa' => $newStart->toDateString(),
        'tanggal_jatuh_tempo' => $newEnd->toDateString()
      ]);

      // Log
      $deskripsiLog = "Perpanjangan Sewa. Periode Baru: {$newStart->isoFormat('D MMM YYYY')} - {$newEnd->isoFormat('D MMM YYYY')}";
      if ($nameHasChanged) {
        $deskripsiLog .= " (Nama nasabah dikoreksi: '{$oldName}' -> '{$newName}')";
      }

      $this->createLog($sdbUnit, 'PERPANJANGAN', $deskripsiLog);
    });

    return $sdbUnit->fresh();
  }

    // --- METODE HELPER ---

  /**
   * Helper untuk melacak perubahan field dan mencatat log.
   * [PERBAIKAN]: Menambahkan parameter $forcedActivity untuk override otomatisasi.
   */
  /**
   * Helper untuk melacak perubahan field dan mencatat log (JSON FORMAT).
   */
  public function trackAndLogChanges(SdbUnit $sdbUnit, array $oldData, array $newData, ?string $forcedActivity = null)
  {
    $changes = [];

    // Cek Nama
    if (($oldData['nama_nasabah'] ?? null) !== ($newData['nama_nasabah'] ?? null)) {
      $changes[] = [
        'field' => 'Nama Nasabah',
        'old'   => $oldData['nama_nasabah'] ?? '-',
        'new'   => $newData['nama_nasabah'] ?? '-',
      ];
    }

    // Cek Tanggal Sewa
    if (($oldData['tanggal_sewa'] ?? null) !== ($newData['tanggal_sewa'] ?? null)) {
      $oldDate = $oldData['tanggal_sewa'] ? Carbon::parse($oldData['tanggal_sewa'])->format('d M Y') : '-';
      $newDate = $newData['tanggal_sewa'] ? Carbon::parse($newData['tanggal_sewa'])->format('d M Y') : '-';
      $changes[] = [
        'field' => 'Tanggal Sewa',
        'old'   => $oldDate,
        'new'   => $newDate,
      ];
    }

    // Cek Jatuh Tempo
    if (($oldData['tanggal_jatuh_tempo'] ?? null) !== ($newData['tanggal_jatuh_tempo'] ?? null)) {
      $oldDate = $oldData['tanggal_jatuh_tempo'] ? Carbon::parse($oldData['tanggal_jatuh_tempo'])->format('d M Y') : '-';
      $newDate = $newData['tanggal_jatuh_tempo'] ? Carbon::parse($newData['tanggal_jatuh_tempo'])->format('d M Y') : '-';
      $changes[] = [
        'field' => 'Jatuh Tempo',
        'old'   => $oldDate,
        'new'   => $newDate,
      ];
    }

    if (!empty($changes)) {
      $kegiatan = $forcedActivity ?? $this->determineActivityType($oldData, $newData);

      // SIMPAN SEBAGAI JSON STRING
      // Tambahkan flag khusus 'JSON_DATA:' agar view tahu ini format baru
      $deskripsi = 'JSON_DATA:' . json_encode($changes);

      $this->createLog($sdbUnit, $kegiatan, $deskripsi);
    }
  }

  public function determineActivityType(array $oldData, array $newData): string
  {
    $wasEmpty = empty($oldData['nama_nasabah']);
    $nowEmpty = empty($newData['nama_nasabah']);

    if ($wasEmpty && !$nowEmpty) return 'PENYEWAAN_BARU';
    if (!$wasEmpty && $nowEmpty) return 'SEWA_BERAKHIR';

    // Logika penebak ini hanya jalan jika tidak ada $forcedActivity
    if (!$wasEmpty && !$nowEmpty) {
      if (($oldData['tanggal_jatuh_tempo'] ?? null) !== ($newData['tanggal_jatuh_tempo'] ?? null)) {
        return 'PERPANJANGAN';
      }
    }

    return 'EDIT_DATA';
  }

  public function createLog(SdbUnit $sdbUnit, string $kegiatan, string $deskripsi)
  {
    SdbLogService::record(
      $kegiatan,
      $deskripsi,
      $sdbUnit->id
    );
  }
}
