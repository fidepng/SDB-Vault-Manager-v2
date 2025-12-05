<?php

namespace App\Http\Controllers;

use App\Models\SdbUnit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SdbLetterController extends Controller
{
  /**
   * Mencetak Surat Pemberitahuan Jatuh Tempo (PDF).
   */
  public function print(SdbUnit $sdbUnit)
  {
    // 1. Validasi Status (Opsional - Best Practice)
    // Kita cegah cetak surat untuk unit yang masih "Aman" atau "Kosong"
    // agar tidak terjadi kesalahan administrasi.
    if (!in_array($sdbUnit->status, [
      SdbUnit::STATUS_AKAN_JATUH_TEMPO,
      SdbUnit::STATUS_LEWAT_JATUH_TEMPO
    ])) {
      return back()->with('error', 'Surat hanya tersedia untuk unit yang akan/sudah jatuh tempo.');
    }

    // 2. Persiapan Data (Future Proofing)
    // Kita kirim object $sdbUnit utuh. Di masa depan (Fase 5), 
    // kita bisa kirim collection ($sdbUnits) untuk cetak massal.
    $data = [
      'unit' => $sdbUnit,
      'tanggal_cetak' => now()->translatedFormat('d F Y'),
      'nomor_surat' => 'SDB/NOTIF/' . date('Y') . '/' . $sdbUnit->nomor_sdb
    ];

    // 3. Generate PDF
    // loadView mengambil file dari resources/views/pdf/notification_letter.blade.php
    $pdf = Pdf::loadView('pdf.notification_letter', $data);

    // Setup Kertas A4 Portrait
    $pdf->setPaper('A4', 'portrait');

    // 4. Stream (Tampilkan di browser)
    // Gunakan 'stream' agar user bisa preview dulu sebelum download/print.
    // Nama file: Surat-Peringatan-SDB-001.pdf
    return $pdf->stream('Surat-Peringatan-SDB-' . $sdbUnit->nomor_sdb . '.pdf');
  }
}
