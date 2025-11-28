<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdbRentalHistory extends Model
{
  use HasFactory;

  // Pastikan nama tabel benar
  protected $table = 'sdb_rental_histories';

  // PENTING: Semua kolom ini harus ada agar tidak error 500 saat create
  protected $fillable = [
    'sdb_unit_id',
    'nomor_sdb',
    'nama_nasabah',
    'tanggal_mulai',
    'tanggal_berakhir',
    'durasi_tahun',
    'status_akhir',
    'catatan',
  ];

  protected $casts = [
    'tanggal_mulai' => 'date',
    'tanggal_berakhir' => 'date',
  ];
}
