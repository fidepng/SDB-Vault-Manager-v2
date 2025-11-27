<?php

namespace App\Http\Controllers;

use App\Models\SdbUnit;
use App\Models\SdbVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SdbVisitController extends Controller
{
    /**
     * Menyimpan data kunjungan baru.
     */
    public function store(Request $request, SdbUnit $sdbUnit)
    {
        // Validasi input
        $validated = $request->validate([
            'nama_pengunjung' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'waktu_kunjung' => 'required|date',
        ]);

        // Simpan ke database
        SdbVisit::create([
            'sdb_unit_id' => $sdbUnit->id,
            'nama_pengunjung' => $validated['nama_pengunjung'],
            'waktu_kunjung' => $validated['waktu_kunjung'],
            'keterangan' => $validated['keterangan'],
            'petugas_id' => Auth::id(), // Otomatis catat siapa admin yang input
        ]);

        return back()->with('success', 'Data kunjungan berhasil dicatat.');
    }
}
