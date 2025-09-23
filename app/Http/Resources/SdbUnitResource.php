<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SdbUnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // '$this->resource' adalah objek model SdbUnit itu sendiri
        $unit = $this->resource;

        return [
            // Data Utama
            'id' => $unit->id,
            'nomor_sdb' => $unit->nomor_sdb,
            'tipe' => $unit->tipe,
            'nama_nasabah' => $unit->nama_nasabah,

            // **Format Tanggal yang Konsisten (YYYY-MM-DD) untuk input form**
            'tanggal_sewa' => $unit->tanggal_sewa?->format('Y-m-d'),
            'tanggal_jatuh_tempo' => $unit->tanggal_jatuh_tempo?->format('Y-m-d'),

            // Data Turunan dari Accessor di Model
            'status' => $unit->status,
            'status_text' => $unit->status_text,
            'status_color' => $unit->status_color,
            'needs_action' => $unit->needs_action,
            'days_until_expiry' => $unit->days_until_expiry,
            'is_rented' => $unit->is_rented,
            'rental_period' => $unit->rental_period,

            // Data Tambahan yang Diformat untuk Tampilan Display
            'tanggal_sewa_formatted' => $unit->tanggal_sewa
                ?->setTimezone(config('app.display_timezone'))
                ->isoFormat('D MMMM YYYY'),
            'tanggal_jatuh_tempo_formatted' => $unit->tanggal_jatuh_tempo
                ?->setTimezone(config('app.display_timezone'))
                ->isoFormat('D MMMM YYYY'),
        ];
    }
}
