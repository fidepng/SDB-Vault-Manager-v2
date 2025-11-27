<?php

namespace App\Http\Controllers;

use App\Models\SdbLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Menampilkan halaman Audit Trail dengan filter.
     */
    public function index(Request $request)
    {
        // 1. Ambil Log dengan Relasi User & SdbUnit
        $query = SdbLog::with(['user', 'sdbUnit'])->latest('timestamp');

        // 2. Terapkan Filter Pencarian (Jika ada)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kegiatan', 'like', "%$search%")
                    ->orWhere('deskripsi', 'like', "%$search%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($request->filled('kegiatan')) {
            $query->where('kegiatan', $request->kegiatan);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('timestamp', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('timestamp', '<=', $request->date_end);
        }

        // 3. Pagination 20 baris per halaman
        $logs = $query->paginate(20)->withQueryString();

        // 4. Ambil list user untuk dropdown filter
        $users = User::all();

        // 5. Ambil list unik kegiatan untuk dropdown filter
        $kegiatanList = SdbLog::select('kegiatan')->distinct()->pluck('kegiatan');

        return view('audit-logs.index', compact('logs', 'users', 'kegiatanList'));
    }
}
