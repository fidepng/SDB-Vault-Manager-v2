<?php

namespace App\Exports;

use App\Models\SdbUnit;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SdbUnitExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles
{
  protected $filters;

  public function __construct(array $filters = [])
  {
    $this->filters = $filters;
  }

  /**
   * Apply filters sama seperti yang digunakan di Dashboard
   */
  public function query()
  {
    $query = SdbUnit::query()->orderBy('nomor_sdb');

    // Apply Search Filter
    if (!empty($this->filters['search'])) {
      $query->search($this->filters['search']);
    }

    // Apply Status Filter
    if (!empty($this->filters['status'])) {
      $query->byStatus($this->filters['status']);
    }

    // Apply Tipe Filter
    if (!empty($this->filters['tipe'])) {
      $query->byTipe($this->filters['tipe']);
    }

    return $query;
  }

  /**
   * Header Excel
   */
  public function headings(): array
  {
    return [
      'NOMOR_SDB',
      'TIPE',
      'NAMA_NASABAH',
      'TANGGAL_SEWA',
      'TANGGAL_JATUH_TEMPO',
      'STATUS',
      'HARI_TERSISA'
    ];
  }

  /**
   * Mapping data untuk setiap row
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
      $sdbUnit->days_until_expiry ?? ''
    ];
  }

  /**
   * Format kolom tanggal agar tetap text (hindari Excel auto-format)
   */
  public function columnFormats(): array
  {
    return [
      'D' => NumberFormat::FORMAT_TEXT, // Tanggal Sewa
      'E' => NumberFormat::FORMAT_TEXT, // Tanggal Jatuh Tempo
    ];
  }

  /**
   * Styling untuk header
   */
  public function styles(Worksheet $sheet)
  {
    return [
      1 => [
        'font' => ['bold' => true, 'size' => 12],
        'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
          'startColor' => ['rgb' => '2563EB']
        ],
        'font' => ['color' => ['rgb' => 'FFFFFF']]
      ],
    ];
  }
}
