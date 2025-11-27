<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdbRentalHistory extends Model
{
  use HasFactory;

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

  // Relasi ke Unit SDB (optional, karena unit bisa saja null jika dihapus)
  public function sdbUnit()
  {
    return $this->belongsTo(SdbUnit::class);
  }
}
