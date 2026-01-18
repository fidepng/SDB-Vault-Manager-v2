<?php

namespace App\Http\Controllers;

use App\Models\SdbLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    /**
     * Menampilkan halaman Audit Trail dengan filter yang telah diperbaiki.
     */
    public function index(Request $request)
    {
        // 1. VALIDASI & SANITASI INPUT
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'kegiatan' => 'nullable|string|max:100',
            'user_id' => 'nullable|integer|exists:users,id',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'per_page' => 'nullable|integer|in:20,50,100,200'
        ]);

        // 2. QUERY BUILDER dengan Eager Loading
        $query = SdbLog::with(['user:id,name,email', 'sdbUnit:id,nomor_sdb'])
            ->latest('timestamp');

        // 3. APPLY FILTERS (Optimized Logic)

        // A. Search Filter (Secured dengan Parameter Binding)
        if (!empty($validated['search'])) {
            $searchTerm = '%' . trim($validated['search']) . '%';

            $query->where(function ($q) use ($searchTerm) {
                $q->where('kegiatan', 'like', $searchTerm)
                    ->orWhere('deskripsi', 'like', $searchTerm)
                    ->orWhere('ip_address', 'like', $searchTerm)
                    ->orWhereHas('user', function ($u) use ($searchTerm) {
                        $u->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    })
                    ->orWhereHas('sdbUnit', function ($sdb) use ($searchTerm) {
                        $sdb->where('nomor_sdb', 'like', $searchTerm);
                    });
            });
        }

        // B. Kegiatan Filter (Exact Match)
        if (!empty($validated['kegiatan'])) {
            $query->where('kegiatan', $validated['kegiatan']);
        }

        // C. User Filter
        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        // D. Date Range Filter (FIXED: Both Start & End)
        if (!empty($validated['date_start'])) {
            $query->whereDate('timestamp', '>=', $validated['date_start']);
        }

        if (!empty($validated['date_end'])) {
            $query->whereDate('timestamp', '<=', $validated['date_end']);
        }

        // 4. PAGINATION (Configurable)
        $perPage = $validated['per_page'] ?? 50; // Default 50 (lebih optimal untuk audit log)
        $logs = $query->paginate($perPage)->withQueryString();

        // 5. DATA UNTUK DROPDOWN FILTERS

        // List User yang Aktif (Performance Optimized)
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        // List Kegiatan Unik (Sorted Alphabetically)
        $kegiatanList = SdbLog::select('kegiatan')
            ->distinct()
            ->orderBy('kegiatan')
            ->pluck('kegiatan');

        // 6. STATISTICS (Informasi Tambahan)
        $statistics = [
            'total_logs' => SdbLog::count(),
            'logs_today' => SdbLog::whereDate('timestamp', today())->count(),
            'logs_this_week' => SdbLog::whereBetween('timestamp', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'unique_users' => SdbLog::distinct('user_id')->count('user_id'),
        ];

        return view('audit-logs.index', compact(
            'logs',
            'users',
            'kegiatanList',
            'statistics'
        ));
    }

    /**
     * BONUS: Export Audit Logs to CSV (For Compliance)
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
        ]);

        $logs = SdbLog::with(['user', 'sdbUnit'])
            ->whereBetween('timestamp', [
                $validated['date_start'],
                $validated['date_end'] . ' 23:59:59'
            ])
            ->orderBy('timestamp', 'desc')
            ->get();

        $filename = 'audit_log_' . $validated['date_start'] . '_to_' . $validated['date_end'] . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, [
                'Timestamp',
                'User',
                'Email',
                'Kegiatan',
                'Deskripsi',
                'SDB Unit',
                'IP Address'
            ]);

            // Data Rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->timestamp->format('Y-m-d H:i:s'),
                    $log->user->name ?? 'System',
                    $log->user->email ?? '-',
                    $log->kegiatan,
                    $log->deskripsi,
                    $log->sdbUnit->nomor_sdb ?? '-',
                    $log->ip_address ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
