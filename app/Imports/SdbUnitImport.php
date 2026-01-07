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
   * FIXED: Apply strict business rules with proper detection
   * 
   * KEY FIX: Check if unit exists in DB FIRST before categorizing
   */
  protected function applyStrictRules($data, $existingUnit, $rowNumber)
  {
    $excelHasName = !empty($data['nama_nasabah']);

    // CRITICAL FIX: Check if unit exists AND has rental data
    $dbExists = $existingUnit !== null;
    $dbIsRented = $dbExists && !empty($existingUnit->nama_nasabah);

    // RULE 1: Data Integrity - If name exists, dates must be complete and valid
    if ($excelHasName) {
      if (empty($data['tanggal_sewa'])) {
        throw new \Exception("Data tidak lengkap: Nama Nasabah terisi, tetapi Tanggal Sewa kosong.");
      }

      if (empty($data['tanggal_jatuh_tempo'])) {
        throw new \Exception("Data tidak lengkap: Nama Nasabah terisi, tetapi Tanggal Jatuh Tempo kosong.");
      }

      // Validate date logic
      $start = Carbon::parse($data['tanggal_sewa']);
      $end = Carbon::parse($data['tanggal_jatuh_tempo']);

      if ($end->lessThanOrEqualTo($start)) {
        throw new \Exception(
          "Logika tanggal salah: Jatuh Tempo ({$end->format('d/m/Y')}) " .
            "harus setelah Tanggal Sewa ({$start->format('d/m/Y')})."
        );
      }

      // Business rule: minimum rental 1 month
      if ($start->diffInDays($end) < 30) {
        throw new \Exception(
          "Durasi sewa minimal 1 bulan (30 hari). " .
            "Durasi saat ini: {$start->diffInDays($end)} hari."
        );
      }
    }

    // RULE 2: Data Protection - Cannot empty rented unit via import
    if (!$excelHasName && $dbIsRented) {
      throw new \Exception(
        "PROTEKSI DATA: Unit terisi oleh '{$existingUnit->nama_nasabah}'. " .
          "Tidak dapat dikosongkan via import. Gunakan menu 'Akhiri Sewa' manual."
      );
    }

    // ============================================================
    // CRITICAL FIX FOR ISSUE #2: 
    // Prioritize checking if DB unit is rented FIRST
    // ============================================================

    // RULE 3: Data Correction (Unit Already Rented -> Update)
    // This MUST be checked BEFORE "new rental" logic
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
    // Only reaches here if unit is NOT rented
    if (!$dbIsRented && $excelHasName) {
      return [
        'action' => self::ACTION_NEW,
        'row' => $rowNumber,
        'data' => $data,
        'unit' => $existingUnit // Could be null if physical unit doesn't exist
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

    // Fallback (should never reach here with proper logic)
    return [
      'action' => self::ACTION_SKIP,
      'row' => $rowNumber,
      'reason' => 'Tidak memenuhi kondisi pemrosesan'
    ];
  }

  /**
   * Normalize row data with proper trimming and parsing
   */
  protected function normalizeRowData($row, $rowNumber)
  {
    return [
      'nomor_sdb' => $this->normalizeNomorSdb($row['nomor_sdb']),
      'tipe' => strtoupper(trim($row['tipe'] ?? '')),
      'nama_nasabah' => $this->normalizeName($row['nama_nasabah'] ?? ''),
      'tanggal_sewa' => $this->parseDate($row['tanggal_sewa'] ?? null, 'Tanggal Sewa', $rowNumber),
      'tanggal_jatuh_tempo' => $this->parseDate($row['tanggal_jatuh_tempo'] ?? null, 'Tanggal Jatuh Tempo', $rowNumber),
    ];
  }

  /**
   * Normalize nomor SDB to 3-digit format
   */
  protected function normalizeNomorSdb($value): string
  {
    $cleaned = preg_replace('/[^0-9]/', '', trim($value));

    // Validate not empty after cleaning
    if (empty($cleaned)) {
      throw new \Exception("Nomor SDB tidak valid (kosong atau tidak mengandung angka)");
    }

    // Validate not too long
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

    // Return empty if truly empty
    if ($trimmed === '' || $trimmed === '-' || strtolower($trimmed) === 'null') {
      return '';
    }

    // Apply title case for proper names
    return mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
  }

  /**
   * Robust date parsing supporting multiple formats
   */
  protected function parseDate($value, string $fieldName, int $rowNumber): ?string
  {
    if (empty($value) || $value === '-' || strtolower($value) === 'null') {
      return null;
    }

    try {
      // Case 1: Excel serial number (numeric)
      if (is_numeric($value) && $value > 0) {
        // Validate serial number range (Excel dates start from 1900)
        if ($value < 1 || $value > 2958465) { // 31 Dec 9999
          throw new \Exception("Serial number Excel tidak valid: {$value}");
        }
        return Date::excelToDateTimeObject($value)->format('Y-m-d');
      }

      // Case 2: String date - try multiple formats
      $formats = [
        'Y-m-d',        // 2024-12-31 (ISO standard)
        'd/m/Y',        // 31/12/2024 (Indonesian format)
        'd-m-Y',        // 31-12-2024
        'm/d/Y',        // 12/31/2024 (US format)
        'Y/m/d',        // 2024/12/31
        'd.m.Y',        // 31.12.2024 (European)
      ];

      foreach ($formats as $format) {
        try {
          $parsed = Carbon::createFromFormat($format, trim($value));
          if ($parsed !== false && !$parsed->hasError()) {
            // Validate date is reasonable (not too far in past/future)
            $now = Carbon::now();
            if ($parsed->year < 1990 || $parsed->year > 2100) {
              throw new \Exception("Tahun tidak valid: {$parsed->year}");
            }
            return $parsed->format('Y-m-d');
          }
        } catch (\Exception $e) {
          continue; // Try next format
        }
      }

      // Fallback: Let Carbon try to parse it intelligently
      $carbonParsed = Carbon::parse($value);

      // Validate reasonable date range
      if ($carbonParsed->year < 1990 || $carbonParsed->year > 2100) {
        throw new \Exception("Tahun tidak valid: {$carbonParsed->year}");
      }

      return $carbonParsed->format('Y-m-d');
    } catch (\Exception $e) {
      throw new \Exception(
        "Format {$fieldName} tidak valid: '{$value}'. " .
          "Gunakan format: YYYY-MM-DD, DD/MM/YYYY, atau serial number Excel. " .
          "Contoh: 2024-12-31 atau 31/12/2024"
      );
    }
  }

  /**
   * Validate basic format requirements
   */
  protected function validateBasicFormat($data, $rowNumber): void
  {
    // Validate nomor SDB format (already validated in normalize, but double-check)
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

      // Validate name contains letters (not just numbers/symbols)
      if (!preg_match('/[a-zA-Z]/', $data['nama_nasabah'])) {
        throw new \Exception(
          "Nama Nasabah harus mengandung huruf. " .
            "Nama saat ini: '{$data['nama_nasabah']}'."
        );
      }
    }
  }

  /**
   * ENHANCED: Detect changes with better comparison
   */
  protected function detectChanges($unit, $data)
  {
    $changes = [];

    // Compare name (case insensitive trim)
    $dbName = trim($unit->nama_nasabah);
    $excelName = trim($data['nama_nasabah']);

    if (strcasecmp($dbName, $excelName) != 0) {
      $changes['nama_nasabah'] = [
        'old' => $dbName,
        'new' => $excelName
      ];
    }

    // Compare dates (normalized to Y-m-d format)
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

    // Also compare tipe (in case it was corrected)
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
   * Laravel Excel validation rules for headers
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
