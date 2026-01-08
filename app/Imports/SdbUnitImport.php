<?php

namespace App\Imports;

use App\Models\SdbUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SdbUnitImport implements ToCollection, WithHeadingRow, WithValidation
{
  // Action type constants
  const ACTION_NEW = 'new_rental';
  const ACTION_UPDATE = 'correction';
  const ACTION_SKIP = 'skip';

  // CRITICAL: Business rule constants
  const RENTAL_DURATION_YEARS = 1;
  const RENTAL_DURATION_DAYS = 365; // For validation tolerance

  protected $previewMode = true;
  protected $results = [
    'total' => 0,
    'new' => [],
    'update' => [],
    'errors' => [],
    'skipped' => []
  ];

  public function __construct(bool $previewMode = true)
  {
    $this->previewMode = $previewMode;
  }

  public function collection(Collection $rows)
  {
    $this->results['total'] = $rows->count();

    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2; // +2 for Excel header

      try {
        // Skip completely empty rows
        if ($this->isEmptyRow($row)) {
          continue;
        }

        // Normalize and validate data
        $normalizedData = $this->normalizeRowData($row, $rowNumber);
        $this->validateBasicFormat($normalizedData, $rowNumber);

        // Get existing unit from database
        $existingUnit = SdbUnit::where('nomor_sdb', $normalizedData['nomor_sdb'])->first();

        // Apply strict business rules
        $validationResult = $this->applyStrictRules($normalizedData, $existingUnit, $rowNumber);

        // Categorize result
        $this->categorizeResult($validationResult);
      } catch (\Exception $e) {
        $this->results['errors'][] = [
          'row' => $rowNumber,
          'nomor_sdb' => $row['nomor_sdb'] ?? 'N/A',
          'error' => $e->getMessage()
        ];
      }
    }
  }

  /**
   * Check if row is completely empty
   */
  protected function isEmptyRow($row): bool
  {
    return empty($row['nomor_sdb']) || trim($row['nomor_sdb']) === '';
  }

  /**
   * CRITICAL FIX: Business Rules with 1-Year Rental Enforcement
   */
  protected function applyStrictRules($data, $existingUnit, $rowNumber)
  {
    $excelHasName = !empty($data['nama_nasabah']);
    $dbExists = $existingUnit !== null;
    $dbIsRented = $dbExists && !empty($existingUnit->nama_nasabah);

    // RULE 1: Data Integrity - If name exists, validate rental period
    if ($excelHasName) {
      if (empty($data['tanggal_sewa'])) {
        throw new \Exception("Data tidak lengkap: Nama Nasabah terisi, tetapi Tanggal Sewa kosong.");
      }

      // CRITICAL CHANGE: Validate 1-year rental period
      $this->validateRentalPeriod($data, $rowNumber);
    }

    // RULE 2: Data Protection - Cannot empty rented unit via import
    if (!$excelHasName && $dbIsRented) {
      throw new \Exception(
        "PROTEKSI DATA: Unit terisi oleh '{$existingUnit->nama_nasabah}'. " .
          "Tidak dapat dikosongkan via import. Gunakan menu 'Akhiri Sewa' manual."
      );
    }

    // RULE 3: Data Correction (Unit Already Rented -> Update)
    if ($dbIsRented && $excelHasName) {
      $changes = $this->detectChanges($existingUnit, $data);

      if ($changes) {
        return [
          'action' => self::ACTION_UPDATE,
          'row' => $rowNumber,
          'data' => $data,
          'unit' => $existingUnit,
          'changes' => $changes
        ];
      } else {
        return [
          'action' => self::ACTION_SKIP,
          'row' => $rowNumber,
          'reason' => 'Data identik dengan database'
        ];
      }
    }

    // RULE 4: New Rental (Empty Unit -> Filled)
    if (!$dbIsRented && $excelHasName) {
      return [
        'action' => self::ACTION_NEW,
        'row' => $rowNumber,
        'data' => $data,
        'unit' => $existingUnit
      ];
    }

    // RULE 5: Skip Empty Units
    if (!$excelHasName) {
      return [
        'action' => self::ACTION_SKIP,
        'row' => $rowNumber,
        'reason' => 'Unit kosong, tidak ada data untuk diproses'
      ];
    }

    // Fallback
    return [
      'action' => self::ACTION_SKIP,
      'row' => $rowNumber,
      'reason' => 'Tidak memenuhi kondisi pemrosesan'
    ];
  }

  /**
   * CRITICAL NEW METHOD: Validate 1-Year Rental Period
   * 
   * Business Rule: Bank SDB contracts are ALWAYS exactly 1 year.
   * Excel must provide EITHER:
   * 1. Only tanggal_sewa (system auto-calculates +1 year)
   * 2. Both dates with exactly 1 year duration (±7 days tolerance)
   */
  protected function validateRentalPeriod($data, $rowNumber): void
  {
    $start = Carbon::parse($data['tanggal_sewa'])->startOfDay();

    // Case 1: Only start date provided (RECOMMENDED)
    if (empty($data['tanggal_jatuh_tempo'])) {
      // Auto-calculate 1 year - consistent with manual input
      $data['tanggal_jatuh_tempo'] = $start->copy()->addYear()->format('Y-m-d');
      return; // Valid case
    }

    // Case 2: Both dates provided - validate duration
    $end = Carbon::parse($data['tanggal_jatuh_tempo'])->startOfDay();

    // Basic chronological validation
    if ($end->lessThanOrEqualTo($start)) {
      throw new \Exception(
        "Logika tanggal salah: Jatuh Tempo ({$end->format('d/m/Y')}) " .
          "harus setelah Tanggal Sewa ({$start->format('d/m/Y')})."
      );
    }

    // CRITICAL: Validate 1-year duration
    $expectedEnd = $start->copy()->addYear();
    $daysDifference = $end->diffInDays($expectedEnd, false); // signed difference

    // Allow ±7 days tolerance for leap years and month variations
    $tolerance = 7;

    if (abs($daysDifference) > $tolerance) {
      $actualDuration = $start->diffInDays($end);

      throw new \Exception(
        "DURASI SEWA TIDAK VALID: Sistem hanya menerima sewa 1 tahun. " .
          "Tanggal Sewa: {$start->format('d/m/Y')}, " .
          "Jatuh Tempo: {$end->format('d/m/Y')} " .
          "(Durasi: {$actualDuration} hari, Seharusnya: ~365 hari). " .
          "SOLUSI: Kosongkan kolom Jatuh Tempo, biarkan sistem kalkulasi otomatis."
      );
    }
  }

  /**
   * IMPROVED: Normalize row data with auto-calculation support
   */
  protected function normalizeRowData($row, $rowNumber)
  {
    $normalized = [
      'nomor_sdb' => $this->normalizeNomorSdb($row['nomor_sdb']),
      'tipe' => strtoupper(trim($row['tipe'] ?? '')),
      'nama_nasabah' => $this->normalizeName($row['nama_nasabah'] ?? ''),
      'tanggal_sewa' => $this->parseDate($row['tanggal_sewa'] ?? null, 'Tanggal Sewa', $rowNumber),
      'tanggal_jatuh_tempo' => $this->parseDate($row['tanggal_jatuh_tempo'] ?? null, 'Tanggal Jatuh Tempo', $rowNumber),
    ];

    // CRITICAL: Auto-calculate jatuh tempo if only start date provided
    if (!empty($normalized['nama_nasabah']) && !empty($normalized['tanggal_sewa']) && empty($normalized['tanggal_jatuh_tempo'])) {
      $start = Carbon::parse($normalized['tanggal_sewa']);
      $normalized['tanggal_jatuh_tempo'] = $start->copy()->addYear()->format('Y-m-d');
    }

    return $normalized;
  }

  /**
   * Normalize nomor SDB to 3-digit format
   */
  protected function normalizeNomorSdb($value): string
  {
    $cleaned = preg_replace('/[^0-9]/', '', trim($value));

    if (empty($cleaned)) {
      throw new \Exception("Nomor SDB tidak valid (kosong atau tidak mengandung angka)");
    }

    if (strlen($cleaned) > 3) {
      throw new \Exception("Nomor SDB terlalu panjang: '{$value}'. Maksimal 3 digit.");
    }

    return str_pad($cleaned, 3, '0', STR_PAD_LEFT);
  }

  /**
   * Normalize name (trim and title case)
   */
  protected function normalizeName($value): string
  {
    $trimmed = trim($value);

    if ($trimmed === '' || $trimmed === '-' || strtolower($trimmed) === 'null') {
      return '';
    }

    return mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
  }

  /**
   * IMPROVED: Robust date parsing with better error messages
   */
  protected function parseDate($value, string $fieldName, int $rowNumber): ?string
  {
    if (empty($value) || $value === '-' || strtolower($value) === 'null') {
      return null;
    }

    try {
      // Case 1: Excel serial number
      if (is_numeric($value) && $value > 0) {
        if ($value < 1 || $value > 2958465) {
          throw new \Exception("Serial number Excel tidak valid: {$value}");
        }
        return Date::excelToDateTimeObject($value)->format('Y-m-d');
      }

      // Case 2: String date formats
      $formats = [
        'Y-m-d',   // 2024-12-31
        'd/m/Y',   // 31/12/2024
        'd-m-Y',   // 31-12-2024
        'm/d/Y',   // 12/31/2024
        'Y/m/d',   // 2024/12/31
        'd.m.Y',   // 31.12.2024
      ];

      foreach ($formats as $format) {
        try {
          $parsed = Carbon::createFromFormat($format, trim($value));
          if ($parsed !== false && !$parsed->hasError()) {
            if ($parsed->year < 1990 || $parsed->year > 2100) {
              throw new \Exception("Tahun tidak valid: {$parsed->year}");
            }
            return $parsed->format('Y-m-d');
          }
        } catch (\Exception $e) {
          continue;
        }
      }

      // Fallback: Carbon intelligent parsing
      $carbonParsed = Carbon::parse($value);

      if ($carbonParsed->year < 1990 || $carbonParsed->year > 2100) {
        throw new \Exception("Tahun tidak valid: {$carbonParsed->year}");
      }

      return $carbonParsed->format('Y-m-d');
    } catch (\Exception $e) {
      throw new \Exception(
        "Format {$fieldName} tidak valid: '{$value}'. " .
          "Gunakan format: YYYY-MM-DD, DD/MM/YYYY, atau biarkan kosong untuk auto-kalkulasi. " .
          "Contoh: 2024-12-31 atau 31/12/2024"
      );
    }
  }

  /**
   * IMPROVED: Validate basic format with better error messages
   */
  protected function validateBasicFormat($data, $rowNumber): void
  {
    // Validate nomor SDB
    if (!preg_match('/^\d{3}$/', $data['nomor_sdb'])) {
      throw new \Exception(
        "Format Nomor SDB tidak valid: '{$data['nomor_sdb']}'. " .
          "Harus 3 digit angka (contoh: 001, 045, 120)."
      );
    }

    // Validate tipe
    if (!in_array($data['tipe'], ['B', 'C'])) {
      throw new \Exception(
        "Tipe tidak valid: '{$data['tipe']}'. " .
          "Hanya boleh 'B' atau 'C' (case insensitive)."
      );
    }

    // Validate name length if provided
    if (!empty($data['nama_nasabah'])) {
      $nameLength = mb_strlen($data['nama_nasabah']);

      if ($nameLength < 3) {
        throw new \Exception(
          "Nama Nasabah terlalu pendek (minimal 3 karakter). " .
            "Nama saat ini: '{$data['nama_nasabah']}' ({$nameLength} karakter)."
        );
      }

      if ($nameLength > 255) {
        throw new \Exception(
          "Nama Nasabah terlalu panjang (maksimal 255 karakter). " .
            "Nama saat ini: {$nameLength} karakter."
        );
      }

      if (!preg_match('/[a-zA-Z]/', $data['nama_nasabah'])) {
        throw new \Exception(
          "Nama Nasabah harus mengandung huruf. " .
            "Nama saat ini: '{$data['nama_nasabah']}'."
        );
      }
    }
  }

  /**
   * IMPROVED: Better change detection with date normalization
   */
  protected function detectChanges($unit, $data)
  {
    $changes = [];

    // Compare name (case insensitive)
    $dbName = trim($unit->nama_nasabah);
    $excelName = trim($data['nama_nasabah']);

    if (strcasecmp($dbName, $excelName) != 0) {
      $changes['nama_nasabah'] = [
        'old' => $dbName,
        'new' => $excelName
      ];
    }

    // Compare dates (normalized to Y-m-d)
    $dbTglSewa = $unit->tanggal_sewa
      ? Carbon::parse($unit->tanggal_sewa)->format('Y-m-d')
      : null;

    if ($dbTglSewa !== $data['tanggal_sewa']) {
      $changes['tanggal_sewa'] = [
        'old' => $dbTglSewa ? Carbon::parse($dbTglSewa)->format('d/m/Y') : '-',
        'new' => $data['tanggal_sewa'] ? Carbon::parse($data['tanggal_sewa'])->format('d/m/Y') : '-'
      ];
    }

    $dbJatuhTempo = $unit->tanggal_jatuh_tempo
      ? Carbon::parse($unit->tanggal_jatuh_tempo)->format('Y-m-d')
      : null;

    if ($dbJatuhTempo !== $data['tanggal_jatuh_tempo']) {
      $changes['tanggal_jatuh_tempo'] = [
        'old' => $dbJatuhTempo ? Carbon::parse($dbJatuhTempo)->format('d/m/Y') : '-',
        'new' => $data['tanggal_jatuh_tempo'] ? Carbon::parse($data['tanggal_jatuh_tempo'])->format('d/m/Y') : '-'
      ];
    }

    // Compare tipe
    if ($unit->tipe !== $data['tipe']) {
      $changes['tipe'] = [
        'old' => $unit->tipe,
        'new' => $data['tipe']
      ];
    }

    return !empty($changes) ? $changes : false;
  }

  /**
   * Categorize validation result
   */
  protected function categorizeResult($result): void
  {
    switch ($result['action']) {
      case self::ACTION_NEW:
        $this->results['new'][] = $result;
        break;
      case self::ACTION_UPDATE:
        $this->results['update'][] = $result;
        break;
      case self::ACTION_SKIP:
        $this->results['skipped'][] = $result;
        break;
    }
  }

  /**
   * Laravel Excel validation rules
   */
  public function rules(): array
  {
    return [
      'nomor_sdb' => 'required',
      'tipe' => 'required',
    ];
  }

  /**
   * Custom validation messages
   */
  public function customValidationMessages()
  {
    return [
      'nomor_sdb.required' => 'Kolom NOMOR_SDB wajib diisi.',
      'tipe.required' => 'Kolom TIPE wajib diisi.',
    ];
  }

  /**
   * Get import results
   */
  public function getResults(): array
  {
    return $this->results;
  }
}
