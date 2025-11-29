<?php

namespace App\Models;

use App\Models\SdbVisit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdbUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_sdb',
        'tipe',
        'nama_nasabah',
        'tanggal_sewa',
        'tanggal_jatuh_tempo'
    ];

    protected $casts = [
        'tanggal_sewa' => 'datetime', // Ubah dari 'date' ke 'datetime'
        'tanggal_jatuh_tempo' => 'datetime' // Ubah dari 'date' ke 'datetime'
    ];

    // Computed attributes untuk JSON serialization
    protected $appends = [
        'status',
        'status_text',
        'status_color',
        'needs_action',
        'days_until_expiry'
    ];

    // Constants untuk status - FIXED CONSISTENCY
    public const STATUS_KOSONG = 'kosong';
    public const STATUS_TERISI = 'terisi';
    public const STATUS_AKAN_JATUH_TEMPO = 'akan_jatuh_tempo';
    public const STATUS_LEWAT_JATUH_TEMPO = 'lewat_jatuh_tempo'; // FIXED: consistent casing

    // Warning threshold dalam hari
    public const WARNING_DAYS = 7;

    public function logs()
    {
        return $this->hasMany(SdbLog::class)->orderBy('timestamp', 'desc');
    }

    /**
     * Improved status calculation with better logic
     */
    public function getStatusAttribute()
    {
        // Jika tidak ada nama nasabah, status kosong
        if (empty($this->nama_nasabah)) {
            return self::STATUS_KOSONG;
        }

        // Jika tidak ada tanggal jatuh tempo, dianggap terisi normal
        if (!$this->tanggal_jatuh_tempo) {
            return self::STATUS_TERISI;
        }

        $now = now()->startOfDay();
        $jatuhTempo = Carbon::parse($this->tanggal_jatuh_tempo)->startOfDay();

        // Jika sudah lewat jatuh tempo
        if ($jatuhTempo->lt($now)) {
            return self::STATUS_LEWAT_JATUH_TEMPO; // FIXED: consistent casing
        }

        // Jika akan jatuh tempo dalam WARNING_DAYS ke depan
        $daysUntilExpiry = $now->diffInDays($jatuhTempo, false);
        if ($daysUntilExpiry <= self::WARNING_DAYS) {
            return self::STATUS_AKAN_JATUH_TEMPO;
        }

        // Default: terisi normal
        return self::STATUS_TERISI;
    }

    /**
     * Get status color with consistent mapping
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_KOSONG => 'bg-gray-500',
            self::STATUS_TERISI => 'bg-blue-500',
            self::STATUS_AKAN_JATUH_TEMPO => 'bg-yellow-500',
            self::STATUS_LEWAT_JATUH_TEMPO => 'bg-red-500', // FIXED: consistent casing
            default => 'bg-gray-400'
        };
    }

    /**
     * Get human readable status text
     */
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_KOSONG => 'Kosong',
            self::STATUS_TERISI => 'Terisi',
            self::STATUS_AKAN_JATUH_TEMPO => 'Akan Jatuh Tempo',
            self::STATUS_LEWAT_JATUH_TEMPO => 'Lewat Jatuh Tempo', // FIXED: consistent casing
            default => 'Tidak Diketahui'
        };
    }

    // app/Models/SdbUnit.php

    /**
     * IMPROVED: More efficient scope untuk filter berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        if (empty($status)) {
            return $query;
        }

        // --- LOGIKA BARU ---

        // 1. Tangani kasus "KOSONG" secara terpisah dan langsung
        if ($status === self::STATUS_KOSONG) {
            return $query->where(function ($q) {
                $q->whereNull('nama_nasabah')->orWhere('nama_nasabah', '');
            });
        }

        // 2. Untuk semua status lainnya (Terisi, Akan Jatuh Tempo, Lewat Jatuh Tempo),
        //    pasti harus ada nama nasabah. Terapkan kondisi ini sekali saja.
        $query->whereNotNull('nama_nasabah')->where('nama_nasabah', '!=', '');

        $now = now()->startOfDay();
        $warningDate = $now->copy()->addDays(self::WARNING_DAYS);

        // 3. Terapkan filter tanggal spesifik berdasarkan status
        if ($status === self::STATUS_TERISI) {
            $query->where('tanggal_jatuh_tempo', '>', $warningDate);
        } elseif ($status === self::STATUS_AKAN_JATUH_TEMPO) {
            $query->whereBetween('tanggal_jatuh_tempo', [$now, $warningDate]);
        } elseif ($status === self::STATUS_LEWAT_JATUH_TEMPO) {
            $query->where('tanggal_jatuh_tempo', '<', $now);
        }

        return $query;
    }

    /**
     * Improved search scope
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('nomor_sdb', 'like', "%{$search}%")
                ->orWhere('nama_nasabah', 'like', "%{$search}%");
        });
    }

    /**
     * Scope untuk mendapatkan SDB berdasarkan tipe
     */
    public function scopeByTipe($query, $tipe)
    {
        return $query->where('tipe', strtoupper($tipe));
    }

    /**
     * Helper method untuk cek apakah SDB memerlukan aksi
     */
    public function getNeedsActionAttribute()
    {
        return $this->status === self::STATUS_LEWAT_JATUH_TEMPO; // FIXED: consistent casing
    }

    /**
     * IMPROVED: More accurate days calculation
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->tanggal_jatuh_tempo || empty($this->nama_nasabah)) {
            return null;
        }

        $now = now()->startOfDay();
        $expiry = Carbon::parse($this->tanggal_jatuh_tempo)->startOfDay();

        // Return signed difference: positive = future, negative = past
        return $now->diffInDays($expiry, false);
    }

    /**
     * Check if SDB is currently rented
     */
    public function getIsRentedAttribute()
    {
        return !empty($this->nama_nasabah);
    }

    /**
     * Get formatted rental period
     */
    public function getRentalPeriodAttribute()
    {
        if (!$this->is_rented || !$this->tanggal_sewa || !$this->tanggal_jatuh_tempo) {
            return null;
        }

        return $this->tanggal_sewa->format('d/m/Y') . ' - ' . $this->tanggal_jatuh_tempo->format('d/m/Y');
    }

    /**
     * Scope untuk SDB yang perlu perhatian (akan/sudah jatuh tempo)
     */
    public function scopeNeedsAttention($query)
    {
        $now = now()->startOfDay();
        $warningDate = $now->copy()->addDays(self::WARNING_DAYS);

        return $query->whereNotNull('nama_nasabah')
            ->where('nama_nasabah', '!=', '')
            ->whereNotNull('tanggal_jatuh_tempo')
            ->where('tanggal_jatuh_tempo', '<=', $warningDate);
    }

    /**
     * OPTIMIZED: Get statistics efficiently
     */
    public static function getStatistics($units = null)
    {
        if ($units) {
            // Logika untuk collection yang sudah ada tetap sama
            return [
                'total' => $units->count(),
                'kosong' => $units->filter(fn($unit) => $unit->status === self::STATUS_KOSONG)->count(),
                'terisi' => $units->filter(fn($unit) => $unit->status === self::STATUS_TERISI)->count(),
                'akan_jatuh_tempo' => $units->filter(fn($unit) => $unit->status === self::STATUS_AKAN_JATUH_TEMPO)->count(),
                'lewat_jatuh_tempo' => $units->filter(fn($unit) => $unit->status === self::STATUS_LEWAT_JATUH_TEMPO)->count(),
            ];
        }

        // --- SOLUSI: SATU QUERY UNTUK SEMUA STATISTIK ---
        $now = now()->startOfDay();
        $warningDate = $now->copy()->addDays(self::WARNING_DAYS);

        $stats = self::query()
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("COUNT(CASE WHEN nama_nasabah IS NULL OR nama_nasabah = '' THEN 1 END) as kosong")
            ->selectRaw("COUNT(CASE WHEN (nama_nasabah IS NOT NULL AND nama_nasabah != '') AND (tanggal_jatuh_tempo > ? OR tanggal_jatuh_tempo IS NULL) THEN 1 END) as terisi", [$warningDate])
            ->selectRaw("COUNT(CASE WHEN (nama_nasabah IS NOT NULL AND nama_nasabah != '') AND (tanggal_jatuh_tempo BETWEEN ? AND ?) THEN 1 END) as akan_jatuh_tempo", [$now, $warningDate])
            ->selectRaw("COUNT(CASE WHEN (nama_nasabah IS NOT NULL AND nama_nasabah != '') AND tanggal_jatuh_tempo < ? THEN 1 END) as lewat_jatuh_tempo", [$now])
            ->first()
            ->toArray();

        return array_map('intval', $stats); // Konversi hasil string ke integer
    }

    /**
     * Boot method untuk auto-generate events
     */
    protected static function boot()
    {
        parent::boot();

        // Event saat SDB dibuat
        static::created(function ($sdbUnit) {
            if ($sdbUnit->nama_nasabah) {
                $sdbUnit->logs()->create([
                    'kegiatan' => 'PENYEWAAN_BARU',
                    'deskripsi' => "SDB {$sdbUnit->nomor_sdb} disewa oleh {$sdbUnit->nama_nasabah}",
                    'timestamp' => now()
                ]);
            }
        });
    }

    /**
     * Validation rules
     */
    public static function getValidationRules($isUpdate = false)
    {
        $rules = [
            'tipe' => 'required|in:B,C',
            'nama_nasabah' => 'nullable|string|max:255',
            'tanggal_sewa' => 'nullable|date|before_or_equal:today',
            'tanggal_jatuh_tempo' => 'nullable|date|after:tanggal_sewa'
        ];

        if (!$isUpdate) {
            // UBAH: Sesuaikan regex untuk format nomor baru (3 digit angka)
            $rules['nomor_sdb'] = 'required|string|unique:sdb_units,nomor_sdb|regex:/^\d{3}$/';
        }

        return $rules;
    }

    /**
     * Custom validation messages
     */
    public static function getValidationMessages()
    {
        return [
            'nomor_sdb.required' => 'Nomor SDB wajib diisi',
            'nomor_sdb.unique' => 'Nomor SDB sudah digunakan',
            'nomor_sdb.regex' => 'Format nomor SDB harus 3 digit angka (contoh: 001, 120).',
            'tipe.required' => 'Tipe SDB wajib dipilih',
            'tipe.in' => 'Tipe SDB harus B atau C',
            'tanggal_sewa.before_or_equal' => 'Tanggal sewa tidak boleh lebih dari hari ini',
            'tanggal_jatuh_tempo.after' => 'Tanggal jatuh tempo harus setelah tanggal sewa'
        ];
    }
    public function rentalHistories()
    {
        return $this->hasMany(SdbRentalHistory::class);
    }

    public function visits()
    {
        return $this->hasMany(SdbVisit::class);
    }
}
