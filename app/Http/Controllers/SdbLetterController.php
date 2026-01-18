<?php

namespace App\Http\Controllers;

use App\Models\SdbUnit;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SdbLetterController extends Controller
{
  /**
   * Generate dan print surat pemberitahuan jatuh tempo
   * * @param SdbUnit $sdbUnit
   * @return \Illuminate\Http\Response
   */
  public function print(SdbUnit $sdbUnit)
  {
    // [FIX] NAIKKAN MEMORY LIMIT KHUSUS UNTUK PROSES INI
    // DomPDF butuh memory besar saat memproses image base64
    ini_set('memory_limit', '512M');
    set_time_limit(300); // Tambah waktu eksekusi juga untuk jaga-jaga

    // ============================================
    // 1. VALIDASI BISNIS LOGIC
    // ============================================

    // Pastikan unit memiliki penyewa aktif
    if (!$sdbUnit->nama_nasabah) {
      return back()->with('error', 'Unit SDB ini sedang kosong. Tidak ada surat yang dapat dicetak.');
    }

    // Hanya unit yang akan/sudah jatuh tempo yang bisa dicetak suratnya
    if (!in_array($sdbUnit->status, [
      SdbUnit::STATUS_AKAN_JATUH_TEMPO,
      SdbUnit::STATUS_LEWAT_JATUH_TEMPO
    ])) {
      return back()->with('error', 'Surat pemberitahuan hanya dapat dicetak untuk unit yang akan/sudah jatuh tempo.');
    }

    try {
      // ============================================
      // 2. PREPARE DATA UNTUK PDF
      // ============================================

      $data = [
        'unit' => $sdbUnit,
        'tanggal_cetak' => now()->translatedFormat('d F Y'),
        'nomor_surat' => $this->generateNomorSurat($sdbUnit),

        // Data Bank BTN
        'bank' => [
          'name' => 'PT. Bank Tabungan Negara (Persero) Tbk',
          'address' => 'Jl. Gajah Mada No.1, RT.2/RW.8, Petojo Utara, Kecamatan Gambir, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10130',
          'phone' => '(021) 000 0000',
          'email' => 'btn.callcenter@btn.co.id',
          'website' => 'www.btn.co.id'
        ],

        // Logo Path
        'logo_base64' => $this->getLogoBase64(),

        // Status Information
        'is_overdue' => $sdbUnit->status === SdbUnit::STATUS_LEWAT_JATUH_TEMPO,
        'days_info' => abs($sdbUnit->days_until_expiry),

        // Contact Information
        'contact_person' => auth()->user()?->name ?? 'Petugas Bank',
        'contact_position' => $this->getUserPosition(),
      ];

      // ============================================
      // 3. GENERATE PDF
      // ============================================

      $pdf = Pdf::loadView('pdf.notification_letter', $data);

      // PDF Configuration
      $pdf->setPaper('A4', 'portrait');
      $pdf->setOption('enable-local-file-access', false); // False karena kita pakai base64
      $pdf->setOption('isPhpEnabled', false);

      // [OPSIONAL] Kompresi agar output file lebih kecil
      $pdf->setOption('compress', true);

      // ============================================
      // 4. AUDIT LOGGING
      // ============================================

      AuditService::log(
        'PRINT_SURAT',
        "Mencetak surat pemberitahuan untuk SDB {$sdbUnit->nomor_sdb} - {$sdbUnit->nama_nasabah}",
        $sdbUnit->id,
        [
          'status' => $sdbUnit->status_text,
          'days_until_expiry' => $sdbUnit->days_until_expiry,
          'nomor_surat' => $data['nomor_surat']
        ]
      );

      // ============================================
      // 5. RETURN PDF RESPONSE
      // ============================================

      $filename = sprintf(
        'Surat_Pemberitahuan_SDB_%s_%s.pdf',
        $sdbUnit->nomor_sdb,
        now()->format('Ymd')
      );

      return $pdf->stream($filename);
    } catch (\Exception $e) {
      // Error Handling & Logging
      \Log::error('PDF Error: ' . $e->getMessage()); // Log error asli untuk debugging

      AuditService::log(
        'PRINT_SURAT_ERROR',
        "Gagal mencetak surat untuk SDB {$sdbUnit->nomor_sdb}: {$e->getMessage()}",
        $sdbUnit->id
      );

      return back()->with('error', 'Terjadi kesalahan saat membuat surat (Memory Limit). Silakan coba lagi atau hubungi administrator.');
    }
  }

  /**
   * Generate nomor surat dengan format standar
   * Format: XXX/SDB-NOTIF/{Bulan Romawi}/{Tahun}
   * 
   * @param SdbUnit $sdbUnit
   * @return string
   */
  private function generateNomorSurat(SdbUnit $sdbUnit): string
  {
    $sequence = str_pad(
      SdbUnit::whereYear('created_at', now()->year)->count(),
      3,
      '0',
      STR_PAD_LEFT
    );

    $romanMonth = $this->getRomanMonth(now()->month);
    $year = now()->year;

    return "{$sequence}/SDB-NOTIF/{$romanMonth}/{$year}";
  }

  /**
   * Get logo as base64 encoded string
   */
  private function getLogoBase64(): ?string
  {
    // [UPDATE] Prioritas utama sekarang cek 'btn-crop.png'
    $logoPath = public_path('images/btn-crop.png');

    // Jika tidak ada, cek alternatif (btn.png biasa, dll)
    if (!file_exists($logoPath)) {
      $alternatives = [
        public_path('images/btn.png'),
        public_path('images/logo-btn.png'),
        public_path('images/btn-removebg.png'),
      ];

      foreach ($alternatives as $path) {
        if (file_exists($path)) {
          $logoPath = $path;
          break;
        }
      }
    }

    if (isset($logoPath) && file_exists($logoPath)) {
      $imageData = file_get_contents($logoPath);
      $base64 = base64_encode($imageData);
      // Deteksi mime type sederhana berdasarkan ekstensi
      $mimeType = pathinfo($logoPath, PATHINFO_EXTENSION) === 'png' ? 'image/png' : 'image/jpeg';

      return "data:{$mimeType};base64,{$base64}";
    }

    return null;
  }

  /**
   * Get user position based on role
   * 
   * @return string
   */
  private function getUserPosition(): string
  {
    $user = auth()->user();

    if (!$user) {
      return 'Petugas Bank';
    }

    return match ($user->role ?? 'user') {
      'super_admin' => 'Kepala Operasional',
      'admin' => 'Customer Service Officer',
      default => 'Petugas Bank'
    };
  }

  /**
   * Convert month number to Roman numeral
   * 
   * @param int $month
   * @return string
   */
  private function getRomanMonth(int $month): string
  {
    $romans = [
      1 => 'I',
      2 => 'II',
      3 => 'III',
      4 => 'IV',
      5 => 'V',
      6 => 'VI',
      7 => 'VII',
      8 => 'VIII',
      9 => 'IX',
      10 => 'X',
      11 => 'XI',
      12 => 'XII'
    ];

    return $romans[$month] ?? 'I';
  }
}
