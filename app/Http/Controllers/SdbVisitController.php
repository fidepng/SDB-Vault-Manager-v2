<?php

namespace App\Http\Controllers;

use App\Models\SdbUnit;
use App\Models\SdbVisit;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SdbVisitController extends Controller
{
    public function store(Request $request, SdbUnit $sdbUnit)
    {
        $validated = $request->validate([
            'nama_pengunjung' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'waktu_kunjung' => 'required|date',
        ]);

        $visit = SdbVisit::create([
            'sdb_unit_id' => $sdbUnit->id,
            'nama_pengunjung' => $validated['nama_pengunjung'],
            'waktu_kunjung' => $validated['waktu_kunjung'],
            'keterangan' => $validated['keterangan'],
            'petugas_id' => Auth::id(),
        ]);

        // ✅ LOGGING DITAMBAHKAN
        AuditService::log(
            'CATAT_KUNJUNGAN',
            "Kunjungan dicatat untuk SDB {$sdbUnit->nomor_sdb} oleh {$validated['nama_pengunjung']}",
            $sdbUnit->id
        );

        return back()->with('success', 'Data kunjungan berhasil dicatat.');
    }
}
