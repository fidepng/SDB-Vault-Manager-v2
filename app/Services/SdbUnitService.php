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
   * MEMULAI SEWA BARU (Explicit Action)
   * Digunakan ketika Unit dalam keadaan KOSONG diisi oleh Nasabah baru.
   */
  public function startNewRental(SdbUnit $sdbUnit, array $validatedData): SdbUnit
  {
    // 1. Guard: Pastikan unit benar-benar kosong atau baru dibuat
    if (!empty($sdbUnit->nama_nasabah)) {
      throw ValidationException::withMessages([
        'nomor_sdb' => "Unit SDB {$sdbUnit->nomor_sdb} sudah terisi. Gunakan fitur 'Koreksi Data' atau 'Perpanjangan' jika ingin mengubah data."
      ]);
    }

    // 2. Auto-Calculate Jatuh Tempo (Default 1 Tahun jika tidak diisi)
    $tanggalSewa = Carbon::parse($validatedData['tanggal_sewa']);
    $jatuhTempo = isset($validatedData['tanggal_jatuh_tempo'])
      ? Carbon::parse($validatedData['tanggal_jatuh_tempo'])
      : $tanggalSewa->copy()->addYear();

    DB::transaction(function () use ($sdbUnit, $validatedData, $tanggalSewa, $jatuhTempo) {
      // Update Unit
      $sdbUnit->update([
        'nama_nasabah' => $validatedData['nama_nasabah'],
        'tanggal_sewa' => $tanggalSewa->toDateString(),
        'tanggal_jatuh_tempo' => $jatuhTempo->toDateString(),
      ]);

      // Log Aktivitas
      $this->createLog(
        $sdbUnit,
        'PENYEWAAN_BARU',
        "SDB {$sdbUnit->nomor_sdb} mulai disewa oleh {$validatedData['nama_nasabah']}. Periode: {$tanggalSewa->format('d/m/Y')} - {$jatuhTempo->format('d/m/Y')}"
      );
    });

    return $sdbUnit->fresh();
  }

  /**
   * KOREKSI DATA PENYEWA (Explicit Action)
   * Digunakan hanya untuk memperbaiki Typo nama, kesalahan input tanggal, dll.
   * TIDAK BOLEH digunakan untuk perpanjangan sewa (renewal).
   */
  public function correctTenantData(SdbUnit $sdbUnit, array $validatedData): SdbUnit
  {
    // 1. Guard: Unit harus terisi
    if (empty($sdbUnit->nama_nasabah)) {
      throw ValidationException::withMessages([
        'nama_nasabah' => 'Tidak dapat mengoreksi data pada unit kosong.'
      ]);
    }

    $oldData = $sdbUnit->toArray();

    DB::transaction(function () use ($sdbUnit, $validatedData, $oldData) {
      // Update Data
      $sdbUnit->update($validatedData);

      // Cek Perubahan untuk Logging
      $changes = $this->calculateChanges($oldData, $sdbUnit->fresh()->toArray());

      if (!empty($changes)) {
        // Format JSON untuk audit trail yang rapi
        $deskripsi = 'JSON_DATA:' . json_encode($changes);
        $this->createLog($sdbUnit, 'EDIT_DATA', $deskripsi);
      }
    });

    return $sdbUnit->fresh();
  }

  /**
   * PERPANJANGAN SEWA (Explicit Action)
   * Digunakan khusus untuk memperpanjang durasi sewa.
   */
  // Update method extendRental di SdbUnitService.php
  public function extendRental(SdbUnit $sdbUnit, array $validatedData): SdbUnit
  {
    // 0. Pre-Check: Unit harus berstatus sewa
    if (!$sdbUnit->is_rented) {
      throw new \Exception('Unit SDB ini sedang kosong. Gunakan menu "Sewa Baru".', 400);
    }

    $oldJatuhTempo = Carbon::parse($sdbUnit->tanggal_jatuh_tempo)->startOfDay();
    $newStart      = Carbon::parse($validatedData['tanggal_mulai_baru'])->startOfDay();

    // ----------------------------------------------------------------------
    // VALIDASI LANJUTAN (Advanced Validation)
    // ----------------------------------------------------------------------

    // 1. Cek Overlap (Tumpang Tindih)
    // Tanggal mulai baru TIDAK BOLEH sebelum atau sama dengan jatuh tempo lama.
    // Logika: Kontrak lama berakhir 31 Des. Baru harus mulai minimal 1 Jan.
    if ($newStart->lte($oldJatuhTempo)) {
      $nextDay = $oldJatuhTempo->copy()->addDay()->format('d-m-Y');
      throw ValidationException::withMessages([
        'tanggal_mulai_baru' => "Tanggal overlap! Masa sewa saat ini baru berakhir pada {$oldJatuhTempo->format('d-m-Y')}. Perpanjangan harus dimulai minimal tanggal {$nextDay}."
      ]);
    }

    // 2. Cek Gap (Celah/Keterlambatan)
    // Jika tanggal mulai baru > (jatuh tempo + 1 hari), berarti ada jeda tidak terbayar.
    // Opsional: Anda bisa throw error jika kebijakan kantor "Tidak Boleh Ada Gap".
    // Di sini kita izinkan tapi catat sebagai warning di log.
    $seharusnyaMulai = $oldJatuhTempo->copy()->addDay();
    $daysGap = 0;

    if ($newStart->gt($seharusnyaMulai)) {
      $daysGap = $seharusnyaMulai->diffInDays($newStart);
      // Jika Gap terlalu lama (misal > 30 hari), mungkin perlu validasi tambahan?
      // if ($daysGap > 30) { ... } 
    }

    // ----------------------------------------------------------------------
    // PROSES TRANSAKSI
    // ----------------------------------------------------------------------

    DB::transaction(function () use ($sdbUnit, $newStart, $daysGap) { // Pass variables

      // 1. Snapshot Data Lama (Immutable)
      $oldMulai = Carbon::parse($sdbUnit->tanggal_sewa);
      $oldAkhir = Carbon::parse($sdbUnit->tanggal_jatuh_tempo);
      $oldName  = $sdbUnit->nama_nasabah;

      // 2. Kalkulasi Tanggal Baru (Durasi 1 Tahun)
      $newEnd = $newStart->copy()->addYear();

      // 3. Arsipkan ke History
      // Kita hitung durasi lama secara real
      $durasiReal = max(1, $oldMulai->diffInYears($oldAkhir));

      SdbRentalHistory::create([
        'sdb_unit_id'      => $sdbUnit->id,
        'nomor_sdb'        => $sdbUnit->nomor_sdb,
        'nama_nasabah'     => $oldName,
        'tanggal_mulai'    => $oldMulai->toDateString(),
        'tanggal_berakhir' => $oldAkhir->toDateString(),
        'durasi_tahun'     => $durasiReal,
        'status_akhir'     => 'selesai', // Status siklus ini selesai
        'catatan'          => 'Perpanjangan sewa (Archived by System)',
      ]);

      // 4. Update Data Master SDB
      // HANYA update tanggal, nama nasabah HARUS tetap (Integritas Data)
      $sdbUnit->update([
        'tanggal_sewa'        => $newStart->toDateString(),
        'tanggal_jatuh_tempo' => $newEnd->toDateString()
      ]);

      // 5. Audit Log (SdbLog)
      // Log yang informatif mencakup Gap jika ada
      $logMessage = "Perpanjangan Sewa: {$newStart->format('d/m/Y')} s/d {$newEnd->format('d/m/Y')}.";

      if ($daysGap > 0) {
        $logMessage .= " [PERHATIAN: Terdapat jeda kontrak (Gap) selama {$daysGap} hari].";
      }

      $this->createLog($sdbUnit, 'PERPANJANGAN', $logMessage);
    });

    return $sdbUnit->fresh();
  }

  /**
   * MENGHENTIKAN SEWA (Explicit Action)
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

      // Hitung durasi real (bukan durasi kontrak)
      $durasi = $mulai->diffInYears($akhir);
      if ($durasi < 1) $durasi = 1;

      SdbRentalHistory::create([
        'sdb_unit_id'    => $sdbUnit->id,
        'nomor_sdb'      => $sdbUnit->nomor_sdb,
        'nama_nasabah'   => $sdbUnit->nama_nasabah,
        'tanggal_mulai'  => $sdbUnit->tanggal_sewa,
        'tanggal_berakhir' => now()->toDateString(), // Berakhir hari ini
        'durasi_tahun'   => $durasi,
        'status_akhir'   => 'selesai',
        'catatan'        => 'Sewa diakhiri manual melalui sistem',
      ]);

      // Log Audit
      $this->createLog(
        $sdbUnit,
        'SEWA_BERAKHIR',
        "Sewa SDB {$sdbUnit->nomor_sdb} diakhiri. Unit dikosongkan."
      );

      // Bersihkan Unit
      $sdbUnit->update([
        'nama_nasabah' => null,
        'tanggal_sewa' => null,
        'tanggal_jatuh_tempo' => null
      ]);
    });

    return $sdbUnit->fresh();
  }

    // --- HELPER METHODS ---

  /**
   * Helper murni untuk menghitung perbedaan data (Diff).
   * Tidak melakukan logging, hanya return array.
   */
  private function calculateChanges(array $oldData, array $newData): array
  {
    $changes = [];

    // Field yang ingin dipantau perubahannya
    $monitoredFields = [
      'nama_nasabah' => 'Nama Nasabah',
      'tanggal_sewa' => 'Tanggal Sewa',
      'tanggal_jatuh_tempo' => 'Jatuh Tempo',
      'tipe' => 'Tipe Unit'
    ];

    foreach ($monitoredFields as $field => $label) {
      $old = $oldData[$field] ?? null;
      $new = $newData[$field] ?? null;

      // Khusus tanggal, kita normalisasi formatnya agar perbandingannya fair
      if (str_contains($field, 'tanggal')) {
        $old = $old ? Carbon::parse($old)->format('Y-m-d') : null;
        $new = $new ? Carbon::parse($new)->format('Y-m-d') : null;
      }

      if ($old !== $new) {
        // Format tampilan untuk log (Human Readable)
        $oldDisplay = $oldData[$field] ?? '-';
        $newDisplay = $newData[$field] ?? '-';

        // Format tanggal untuk tampilan user (bukan untuk logic if di atas)
        if (str_contains($field, 'tanggal')) {
          $oldDisplay = $oldData[$field] ? Carbon::parse($oldData[$field])->format('d M Y') : '-';
          $newDisplay = $newData[$field] ? Carbon::parse($newData[$field])->format('d M Y') : '-';
        }

        $changes[] = [
          'field' => $label,
          'old'   => $oldDisplay,
          'new'   => $newDisplay,
        ];
      }
    }

    return $changes;
  }

  private function createLog(SdbUnit $sdbUnit, string $kegiatan, string $deskripsi)
  {
    SdbLogService::record(
      $kegiatan,
      $deskripsi,
      $sdbUnit->id
    );
  }
}
