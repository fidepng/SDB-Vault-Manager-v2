<?php

namespace App\Http\Controllers;

use App\Models\SdbUnit;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;

class SdbLetterController extends Controller
{
  public function print(SdbUnit $sdbUnit)
  {
    if (!in_array($sdbUnit->status, [
      SdbUnit::STATUS_AKAN_JATUH_TEMPO,
      SdbUnit::STATUS_LEWAT_JATUH_TEMPO
    ])) {
      return back()->with('error', 'Surat hanya tersedia untuk unit yang akan/sudah jatuh tempo.');
    }

    // ✅ LOGGING DITAMBAHKAN
    AuditService::log(
      'PRINT_SURAT',
      "Mencetak surat peringatan untuk SDB {$sdbUnit->nomor_sdb} - Status: {$sdbUnit->status_text}",
      $sdbUnit->id
    );

    $data = [
      'unit' => $sdbUnit,
      'tanggal_cetak' => now()->translatedFormat('d F Y'),
      'nomor_surat' => 'SDB/NOTIF/' . date('Y') . '/' . $sdbUnit->nomor_sdb
    ];

    $pdf = Pdf::loadView('pdf.notification_letter', $data);
    $pdf->setPaper('A4', 'portrait');

    return $pdf->stream('Surat-Peringatan-SDB-' . $sdbUnit->nomor_sdb . '.pdf');
  }
}
