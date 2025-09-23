<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str; // <-- BARIS INI YANG DITAMBAHKAN

/**
 * Class SdbLog
 *
 * @property int $id
 * @property int $sdb_unit_id
 * @property string $kegiatan
 * @property string $deskripsi
 * @property Carbon $timestamp
 * @property-read SdbUnit $sdbUnit
 * @property-read string $formatted_timestamp
 * @property-read string $timestamp_for_humans
 * @property-read string $kegiatan_icon
 * @property-read string $kegiatan_color
 * @property-read string $kegiatan_label
 */
class SdbLog extends Model
{
    use HasFactory;

    // Constants untuk jenis kegiatan agar konsisten di seluruh aplikasi
    public const KEGIATAN_PENYEWAAN_BARU = 'PENYEWAAN_BARU';
    public const KEGIATAN_PERPANJANGAN = 'PERPANJANGAN';
    public const KEGIATAN_SEWA_BERAKHIR = 'SEWA_BERAKHIR';
    public const KEGIATAN_EDIT_DATA = 'EDIT_DATA';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sdb_unit_id',
        'kegiatan',
        'deskripsi',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     * Ini penting untuk memastikan Laravel memperlakukan kolom sebagai tipe data yang benar.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_timestamp',
        'timestamp_for_humans',
        'kegiatan_icon',
        'kegiatan_color',
        'kegiatan_label',
    ];

    //======================================================================
    // ACCESSORS & MUTATORS
    //======================================================================

    /**
     * Accessor untuk format tanggal yang user-friendly dan sudah dikonversi ke timezone lokal.
     *
     * @return string
     */
    public function getFormattedTimestampAttribute(): string
    {
        // PENTING: Mengonversi waktu dari UTC (database) ke timezone tampilan
        return $this->timestamp
            ->setTimezone(config('app.display_timezone', 'Asia/Makassar'))
            ->isoFormat('D MMM YYYY, HH:mm'); // Format: 02 Sep 2025, 16:30
    }

    /**
     * Accessor untuk format "time ago" yang lebih dinamis.
     *
     * @return string
     */
    public function getTimestampForHumansAttribute(): string
    {
        return $this->timestamp
            ->setTimezone(config('app.display_timezone', 'Asia/Makassar'))
            ->diffForHumans(); // Format: "2 jam yang lalu"
    }

    /**
     * Accessor untuk mendapatkan emoji ikon berdasarkan kegiatan.
     * Menggunakan emoji standar agar kompatibel di semua browser.
     *
     * @return string
     */
    public function getKegiatanIconAttribute(): string
    {
        return match ($this->kegiatan) {
            self::KEGIATAN_PENYEWAAN_BARU => '🔑', // Key icon
            self::KEGIATAN_PERPANJANGAN => '🔄', // Refresh icon
            self::KEGIATAN_SEWA_BERAKHIR => '❌', // Cross/Out icon
            self::KEGIATAN_EDIT_DATA => '✏️', // Edit icon
            default => '📋'                      // Default clipboard icon
        };
    }

    /**
     * Accessor untuk mendapatkan warna badge Tailwind CSS berdasarkan kegiatan.
     *
     * @return string
     */
    public function getKegiatanColorAttribute(): string
    {
        return match ($this->kegiatan) {
            self::KEGIATAN_PENYEWAAN_BARU => 'bg-green-100 text-green-800',
            self::KEGIATAN_PERPANJANGAN => 'bg-blue-100 text-blue-800',
            self::KEGIATAN_SEWA_BERAKHIR => 'bg-red-100 text-red-800',
            self::KEGIATAN_EDIT_DATA => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Accessor untuk mendapatkan label kegiatan yang user-friendly.
     *
     * @return string
     */
    public function getKegiatanLabelAttribute(): string
    {
        return str_replace('_', ' ', Str::title($this->kegiatan));
    }

    //======================================================================
    // RELATIONSHIPS
    //======================================================================

    /**
     * Mendefinisikan relasi "belongsTo" dengan SdbUnit.
     */
    public function sdbUnit()
    {
        return $this->belongsTo(SdbUnit::class);
    }

    //======================================================================
    // SCOPES
    //======================================================================

    /**
     * Scope untuk filter log berdasarkan jenis kegiatan.
     */
    public function scopeByKegiatan($query, $kegiatan)
    {
        return $query->where('kegiatan', $kegiatan);
    }

    /**
     * Scope untuk filter log berdasarkan rentang tanggal.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        // Menggunakan Carbon untuk memastikan parsing tanggal yang aman
        return $query->whereBetween('timestamp', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    //======================================================================
    // STATIC METHODS & BOOT
    //======================================================================

    /**
     * Mendapatkan semua jenis kegiatan sebagai array untuk dropdown filter.
     *
     * @return array
     */
    public static function getKegiatanOptions(): array
    {
        return [
            self::KEGIATAN_PENYEWAAN_BARU,
            self::KEGIATAN_PERPANJANGAN,
            self::KEGIATAN_SEWA_BERAKHIR,
            self::KEGIATAN_EDIT_DATA,
        ];
    }

    /**
     * Mengatur nilai default untuk atribut saat model dibuat.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (is_null($log->timestamp)) {
                $log->timestamp = now();
            }
        });
    }
}
