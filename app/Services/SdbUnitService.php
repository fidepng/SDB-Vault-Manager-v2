<?php

namespace App\Services;

use App\Models\SdbUnit;
use App\Models\SdbLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SdbUnitService
{
  /**
   * Memproses pembaruan data penyewa atau penambahan penyewa baru.
   */
  public function updateTenant(SdbUnit $sdbUnit, array $validatedData): SdbUnit
  {
    if (!empty($validatedData['nama_nasabah']) && empty($validatedData['tanggal_sewa'])) {
      throw ValidationException::withMessages(['tanggal_sewa' => 'Tanggal sewa wajib diisi jika ada nama nasabah']);
    }

    if (!empty($validatedData['tanggal_sewa'])) {
      $validatedData['tanggal_jatuh_tempo'] = Carbon::parse($validatedData['tanggal_sewa'])->addYear()->toDateString();
    }

    $oldData = $sdbUnit->toArray();

    DB::transaction(function () use ($sdbUnit, $validatedData, $oldData) {
      $sdbUnit->update($validatedData);
      $this->trackAndLogChanges($sdbUnit, $oldData, $validatedData);
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
      $nama_nasabah = $sdbUnit->nama_nasabah;
      $this->createLog(
        $sdbUnit,
        'SEWA_BERAKHIR',
        "Sewa SDB {$sdbUnit->nomor_sdb} berakhir untuk {$nama_nasabah}"
      );

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
      throw new \Exception('SDB ini dalam keadaan kosong dan tidak bisa diperpanjang.', 400);
    }

    DB::transaction(function () use ($sdbUnit, $validatedData) {
      $oldJatuhTempo = Carbon::parse($sdbUnit->tanggal_jatuh_tempo)->isoFormat('D MMM YYYY');
      $oldName = $sdbUnit->nama_nasabah;

      $newSewaDate = Carbon::parse($validatedData['tanggal_mulai_baru']);
      $newJatuhTempoDate = $newSewaDate->copy()->addYear();
      $newName = $validatedData['nama_nasabah'];
      $nameHasChanged = $oldName !== $newName;

      $sdbUnit->update([
        'nama_nasabah' => $newName,
        'tanggal_sewa' => $newSewaDate->toDateString(),
        'tanggal_jatuh_tempo' => $newJatuhTempoDate->toDateString()
      ]);

      $deskripsiLog = "Periode sewa diperbarui menjadi: {$newSewaDate->isoFormat('D MMM YYYY')} - {$newJatuhTempoDate->isoFormat('D MMM YYYY')}";
      if ($nameHasChanged) {
        $deskripsiLog .= " (Nama nasabah diubah dari '{$oldName}' menjadi '{$newName}')";
      }
      $this->createLog($sdbUnit, 'PERPANJANGAN', $deskripsiLog);
    });

    return $sdbUnit->fresh();
  }

  // --- METODE HELPER YANG DIPINDAHKAN ---

  public function trackAndLogChanges(SdbUnit $sdbUnit, array $oldData, array $newData)
  {
    $changes = [];

    if (($oldData['nama_nasabah'] ?? null) !== ($newData['nama_nasabah'] ?? null)) {
      $changes[] = "Nama nasabah: " . ($oldData['nama_nasabah'] ?? 'Kosong') . " → " . ($newData['nama_nasabah'] ?? 'Kosong');
    }

    if (($oldData['tanggal_sewa'] ?? null) !== ($newData['tanggal_sewa'] ?? null)) {
      $oldDate = $oldData['tanggal_sewa'] ? Carbon::parse($oldData['tanggal_sewa'])->format('d/m/Y') : 'Tidak ada';
      $newDate = $newData['tanggal_sewa'] ? Carbon::parse($newData['tanggal_sewa'])->format('d/m/Y') : 'Tidak ada';
      $changes[] = "Tanggal sewa: {$oldDate} → {$newDate}";
    }

    if (($oldData['tanggal_jatuh_tempo'] ?? null) !== ($newData['tanggal_jatuh_tempo'] ?? null)) {
      $oldDate = $oldData['tanggal_jatuh_tempo'] ? Carbon::parse($oldData['tanggal_jatuh_tempo'])->format('d/m/Y') : 'Tidak ada';
      $newDate = $newData['tanggal_jatuh_tempo'] ? Carbon::parse($newData['tanggal_jatuh_tempo'])->format('d/m/Y') : 'Tidak ada';
      $changes[] = "Jatuh tempo: {$oldDate} → {$newDate}";
    }

    if (!empty($changes)) {
      $kegiatan = $this->determineActivityType($oldData, $newData);
      $this->createLog($sdbUnit, $kegiatan, implode(', ', $changes));
    }
  }

  public function determineActivityType(array $oldData, array $newData): string
  {
    $wasEmpty = empty($oldData['nama_nasabah']);
    $nowEmpty = empty($newData['nama_nasabah']);

    if ($wasEmpty && !$nowEmpty) return 'PENYEWAAN_BARU';
    if (!$wasEmpty && $nowEmpty) return 'SEWA_BERAKHIR';

    if (!$wasEmpty && !$nowEmpty) {
      if (($oldData['tanggal_jatuh_tempo'] ?? null) !== ($newData['tanggal_jatuh_tempo'] ?? null)) {
        return 'PERPANJANGAN';
      }
    }

    return 'EDIT_DATA';
  }

  public function createLog(SdbUnit $sdbUnit, string $kegiatan, string $deskripsi)
  {
    SdbLog::create([
      'sdb_unit_id' => $sdbUnit->id,
      'kegiatan' => $kegiatan,
      'deskripsi' => $deskripsi,
      'timestamp' => now()
    ]);
  }
}
