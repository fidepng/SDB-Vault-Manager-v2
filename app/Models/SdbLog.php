<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdbLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sdb_unit_id',
        'user_id',
        'kegiatan',
        'deskripsi',
        'ip_address',
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function sdbUnit()
    {
        return $this->belongsTo(SdbUnit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
