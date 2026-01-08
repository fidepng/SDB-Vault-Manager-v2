<?php

namespace App\Exports;

use App\Models\SdbUnit;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SdbUnitExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, WithTitle
{
  protected $filters;

  public function __construct(array $filters = [])
  {
    $this->filters = $filters;
  }

  /**
   * SECURITY FIX: Sanitize and validate filters
   */
  protected function sanitizeFilters(): array
  {
    $sanitized = [];

    // Validate search input
    if (!empty($this->filters['search'])) {
      $sanitized['search'] = trim($this->filters['search']);
      // Prevent SQL injection through escaping
      $sanitized['search'] = str_replace(['%', '_'], ['\\%', '\\_'], $sanitized['search']);
    }

    // Validate status (whitelist only)
    if (!empty($this->filters['status'])) {
      $validStatuses = ['kosong', 'terisi', 'akan_jatuh_tempo', 'lewat_jatuh_tempo'];
      if (in_array($this->filters['status'], $validStatuses)) {
        $sanitized['status'] = $this->filters['status'];
      }
    }

    // Validate tipe (whitelist only)
    if (!empty($this->filters['tipe'])) {
      if (in_array(strtoupper($this->filters['tipe']), ['B', 'C'])) {
        $sanitized['tipe'] = strtoupper($this->filters['tipe']);
      }
    }

    return $sanitized;
  }

  /**
   * Apply filters with proper sanitization
   */
  public function query()
  {
    $sanitized = $this->sanitizeFilters();
    $query = SdbUnit::query()->orderBy('nomor_sdb');

    // Apply Search Filter (using scope for safety)
    if (!empty($sanitized['search'])) {
      $query->search($sanitized['search']);
    }

    // Apply Status Filter (using scope)
    if (!empty($sanitized['status'])) {
      $query->byStatus($sanitized['status']);
    }

    // Apply Tipe Filter (using scope)
    if (!empty($sanitized['tipe'])) {
      $query->byTipe($sanitized['tipe']);
    }

    return $query;
  }

  /**
   * IMPROVED: Header with clearer instructions
   */
  public function headings(): array
  {
    return [
      'NOMOR_SDB',
      'TIPE',
      'NAMA_NASABAH',
      'TANGGAL_SEWA',
      'TANGGAL_JATUH_TEMPO', // Optional: leave empty for auto-calculation
      'STATUS',
      'HARI_TERSISA'
    ];
  }

  /**
   * IMPROVED: Mapping with better null handling
   */
  public function map($sdbUnit): array
  {
    return [
      $sdbUnit->nomor_sdb,
      $sdbUnit->tipe,
      $sdbUnit->nama_nasabah ?? '',
      $sdbUnit->tanggal_sewa ? $sdbUnit->tanggal_sewa->format('Y-m-d') : '',
      $sdbUnit->tanggal_jatuh_tempo ? $sdbUnit->tanggal_jatuh_tempo->format('Y-m-d') : '',
      $sdbUnit->status_text,
      $sdbUnit->days_until_expiry !== null ? (string)$sdbUnit->days_until_expiry : ''
    ];
  }

  /**
   * Format kolom tanggal agar tetap text
   */
  public function columnFormats(): array
  {
    return [
      'D' => NumberFormat::FORMAT_TEXT, // Tanggal Sewa
      'E' => NumberFormat::FORMAT_TEXT, // Tanggal Jatuh Tempo
    ];
  }

  /**
   * IMPROVED: Better styling with instructions row
   */
  public function styles(Worksheet $sheet)
  {
    // Header row styling
    $sheet->getStyle('1:1')->applyFromArray([
      'font' => [
        'bold' => true,
        'size' => 12,
        'color' => ['rgb' => 'FFFFFF']
      ],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2563EB']
      ],
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
      ]
    ]);

    // Auto-size columns
    foreach (range('A', 'G') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Add instruction note below header (row 2)
    $sheet->insertNewRowBefore(2, 1);
    $sheet->setCellValue('A2', 'PETUNJUK: Kolom TANGGAL_JATUH_TEMPO boleh dikosongkan, sistem akan auto-kalkulasi +1 tahun dari Tanggal Sewa.');
    $sheet->mergeCells('A2:G2');
    $sheet->getStyle('A2')->applyFromArray([
      'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '666666']],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFF9E6']
      ]
    ]);

    return [];
  }

  /**
   * Set sheet title based on filters
   */
  public function title(): string
  {
    $sanitized = $this->sanitizeFilters();

    if (empty($sanitized)) {
      return 'All_SDB_Units';
    }

    $parts = [];
    if (isset($sanitized['status'])) {
      $parts[] = ucfirst($sanitized['status']);
    }
    if (isset($sanitized['tipe'])) {
      $parts[] = 'Tipe_' . $sanitized['tipe'];
    }

    return implode('_', $parts) ?: 'Filtered_Data';
  }
}
