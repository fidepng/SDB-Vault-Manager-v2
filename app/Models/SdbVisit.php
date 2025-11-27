<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdbVisit extends Model
{
  use HasFactory;

  protected $fillable = [
    'sdb_unit_id',
    'nama_pengunjung',
    'waktu_kunjung',
    'petugas_id',
    'keterangan',
  ];

  protected $casts = [
    'waktu_kunjung' => 'datetime',
  ];

  public function sdbUnit()
  {
    return $this->belongsTo(SdbUnit::class);
  }

  public function petugas()
  {
    return $this->belongsTo(User::class, 'petugas_id');
  }
}
