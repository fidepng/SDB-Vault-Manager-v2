<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SdbUnit;
use Illuminate\Http\Request;
use App\Models\SdbRentalHistory;
use App\Services\SdbUnitService;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SdbUnitResource;
use Illuminate\Validation\ValidationException;

class SdbController extends Controller
{
    public function __construct(protected SdbUnitService $sdbService) {}

    public function index(Request $request)
    {
        // Optimasi: Eager loading jika diperlukan di masa depan
        $sdbUnits = SdbUnit::orderBy('nomor_sdb', 'asc')->get();

        $sdbDataMap = SdbUnitResource::collection($sdbUnits)->keyBy('nomor_sdb');
        $statistics = SdbUnit::getStatistics();

        return view('dashboard', [
            'sdbLayouts' => config('sdb.layouts'),
            'sdbDataMap' => $sdbDataMap,
            'allUnits' => SdbUnitResource::collection($sdbUnits),
            'statistics' => $statistics
        ]);
    }

    public function show(SdbUnit $sdbUnit)
    {
        return new SdbUnitResource($sdbUnit);
    }

    /**
     * STORE: Membuat Unit Fisik Baru (Admin Feature).
     * Jika data nasabah disertakan, langsung proses sewa baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            SdbUnit::getValidationRules(),
            SdbUnit::getValidationMessages()
        );

        try {
            DB::beginTransaction();

            // 1. Buat Fisik Unit (Tanpa data nasabah dulu)
            $unitData = [
                'nomor_sdb' => $validated['nomor_sdb'],
                'tipe' => $validated['tipe']
            ];
            $sdbUnit = SdbUnit::create($unitData);

            // 2. Jika ada data nasabah, panggil Service Sewa Baru
            if (!empty($validated['nama_nasabah'])) {
                // Siapkan payload untuk sewa
                $rentalData = [
                    'nama_nasabah' => $validated['nama_nasabah'],
                    'tanggal_sewa' => $validated['tanggal_sewa'] ?? now(),
                    'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'] ?? null,
                ];

                // Service akan menangani logika & logging
                $sdbUnit = $this->sdbService->startNewRental($sdbUnit, $rentalData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Unit SDB berhasil dibuat.',
                'data' => new SdbUnitResource($sdbUnit)
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * UPDATE: Khusus KOREKSI DATA (Edit Typo, dll).
     * Jangan gunakan ini untuk Perpanjangan atau Sewa Baru.
     */
    public function update(Request $request, SdbUnit $sdbUnit)
    {
        // Validasi khusus update (ignore unique check for nomor_sdb if not changing)
        $validated = $request->validate(SdbUnit::getValidationRules(true), SdbUnit::getValidationMessages());

        try {
            // Panggil Service khusus Koreksi
            $updatedUnit = $this->sdbService->correctTenantData($sdbUnit, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Data penyewa berhasil dikoreksi.',
                'data' => new SdbUnitResource($updatedUnit)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400); // Bad Request jika logic validasi gagal
        }
    }

    /**
     * [BARU] STORE RENTAL: Khusus Sewa Baru pada Unit Existing.
     * Endpoint ini harus dipanggil saat user klik "Isi Unit" pada SDB Kosong.
     */
    public function storeRental(Request $request, SdbUnit $sdbUnit)
    {
        $validated = $request->validate([
            'nama_nasabah' => 'required|string|max:255',
            'tanggal_sewa' => 'required|date',
            'tanggal_jatuh_tempo' => 'nullable|date|after:tanggal_sewa',
        ], [
            'nama_nasabah.required' => 'Nama Nasabah wajib diisi untuk sewa baru.',
            'tanggal_sewa.required' => 'Tanggal Sewa wajib diisi.'
        ]);

        try {
            $rentedUnit = $this->sdbService->startNewRental($sdbUnit, $validated);

            return response()->json([
                'success' => true,
                'message' => 'SDB berhasil disewakan.',
                'data' => new SdbUnitResource($rentedUnit)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * EXTEND: Perpanjangan Sewa.
     */
    /**
     * PERPANJANGAN SEWA (Revisi Logic: 1 Transaksi = Tepat 1 Tahun)
     */
    /**
     * EXTEND: Perpanjangan Sewa.
     */
    public function extendRental(Request $request, string $id) // Ubah SdbUnit jadi string $id
    {
        // 1. Cari Unit Manual (Explicit)
        // Ini memastikan kita dapat object yang benar atau error 404 yang jelas
        $sdbUnit = SdbUnit::findOrFail($id);

        // 2. Validasi Input (Hapus before_or_equal:today untuk perpanjangan masa depan)
        $validated = $request->validate([
            'tanggal_mulai_baru' => 'required|date',
            'nama_nasabah' => 'required|string|max:255'
        ], [
            'tanggal_mulai_baru.required' => 'Tanggal mulai perpanjangan wajib diisi.',
            'nama_nasabah.required' => 'Nama nasabah wajib diisi.'
        ]);

        try {
            // 3. Panggil Service
            $extendedUnit = $this->sdbService->extendRental($sdbUnit, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Masa sewa berhasil diperpanjang.',
                'data'    => new SdbUnitResource($extendedUnit)
            ]);
        } catch (\Exception $e) {
            // Tangkap semua error backend dan kirim sbg JSON (bukan HTML error page)
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DESTROY: Mengakhiri Sewa (Vacate / Kosongkan Unit).
     * Kita menggunakan method destroy standard resource untuk aksi "Kosongkan".
     */
    public function destroy(SdbUnit $sdbUnit)
    {
        try {
            $emptiedUnit = $this->sdbService->endRental($sdbUnit);

            return response()->json([
                'success' => true,
                'message' => 'SDB berhasil dikosongkan dan masa sewa berakhir.',
                'data' => new SdbUnitResource($emptiedUnit)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function getFilteredData(Request $request)
    {
        $query = SdbUnit::query();
        if ($search = $request->get('search')) $query->search($search);
        if ($status = $request->get('status')) $query->byStatus($status);
        if ($tipe = $request->get('tipe')) $query->byTipe($tipe);

        $sdbUnits = $query->orderBy('nomor_sdb', 'asc')->get();

        return response()->json([
            'success' => true,
            'units' => SdbUnitResource::collection($sdbUnits),
            'statistics' => SdbUnit::getStatistics($sdbUnits) // Menggunakan method statis yang efisien
        ]);
    }

    public function getAttentionRequired()
    {
        $sdbUnits = SdbUnit::needsAttention()->orderBy('tanggal_jatuh_tempo')->get();
        return response()->json(['success' => true, 'data' => SdbUnitResource::collection($sdbUnits)]);
    }

    /**
     * Mengambil history & visit untuk Modal.
     * SUDAH DIPERBAIKI: Menggunakan logika status terpusat dari Model.
     */
    public function getHistory(SdbUnit $sdbUnit)
    {
        $rentalHistories = $sdbUnit->rentalHistories()->latest()->get();
        $visits = $sdbUnit->visits()->with('petugas')->latest()->get();

        if ($sdbUnit->nama_nasabah) {
            // Gunakan Accessor dari Model, JANGAN hitung manual lagi disini
            // Ini menjamin konsistensi antara warna grid dashboard dan status di modal
            $statusStr = strtoupper($sdbUnit->status_text);

            // Generate catatan berdasarkan status model
            $catatanStr = match ($sdbUnit->status) {
                SdbUnit::STATUS_LEWAT_JATUH_TEMPO => 'Nasabah menunggak / lewat jatuh tempo.',
                SdbUnit::STATUS_AKAN_JATUH_TEMPO => 'Masa sewa akan segera berakhir.',
                default => 'Masa sewa berjalan normal.'
            };

            $activeSession = [
                'id' => 'active',
                'nama_nasabah' => $sdbUnit->nama_nasabah . ' (Saat Ini)',
                'nomor_sdb' => $sdbUnit->nomor_sdb,
                'tanggal_mulai' => $sdbUnit->tanggal_sewa,
                'tanggal_berakhir' => $sdbUnit->tanggal_jatuh_tempo,
                'status_akhir' => $statusStr,
                'catatan' => $catatanStr,
            ];

            $rentalHistories->prepend($activeSession);
        }

        return response()->json([
            'rental_histories' => $rentalHistories,
            'visits' => $visits,
        ]);
    }
}
