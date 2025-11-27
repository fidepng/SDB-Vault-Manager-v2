<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SdbUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SdbUnitResource;
use Illuminate\Validation\ValidationException;
use App\Services\SdbUnitService; // <-- 1. Import Service

class SdbController extends Controller
{
    // 2. Gunakan Dependency Injection untuk memasukkan service
    public function __construct(protected SdbUnitService $sdbService) {}

    public function index(Request $request)
    {
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

    public function store(Request $request)
    {
        // 1. Validasi input request seperti biasa.
        $validated = $request->validate(
            SdbUnit::getValidationRules(),
            SdbUnit::getValidationMessages()
        );

        try {
            // 2. Gunakan transaction untuk memastikan integritas data.
            DB::beginTransaction();

            // 3. Logika utama untuk membuat record baru tetap di controller.
            $sdbUnit = SdbUnit::create($validated);

            // 4. Jika ada data nasabah, panggil metode createLog dari service.
            //    Perhatikan penggunaan `$this->sdbService->createLog`.
            if (!empty($validated['nama_nasabah'])) {
                $this->sdbService->createLog(
                    $sdbUnit,
                    'PENYEWAAN_BARU',
                    "SDB {$sdbUnit->nomor_sdb} disewa oleh {$validated['nama_nasabah']}"
                );
            }

            DB::commit();

            // 5. Kembalikan response sukses seperti sebelumnya.
            return response()->json([
                'success' => true,
                'message' => 'Data SDB berhasil disimpan',
                'data' => new SdbUnitResource($sdbUnit)
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    // 3. Metode `update` menjadi sangat ramping
    public function update(Request $request, SdbUnit $sdbUnit)
    {
        $validated = $request->validate(SdbUnit::getValidationRules(true), SdbUnit::getValidationMessages());

        try {
            $updatedUnit = $this->sdbService->updateTenant($sdbUnit, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Data SDB berhasil diupdate',
                'data' => new SdbUnitResource($updatedUnit)
            ]);
        } catch (\Exception $e) {
            // Tangani semua jenis exception dari service
            return response()->json(['success' => false, 'message' => 'Gagal mengupdate data: ' . $e->getMessage()], 500);
        }
    }

    // 4. Metode `destroy` juga menjadi ramping
    public function destroy(SdbUnit $sdbUnit)
    {
        try {
            $emptiedUnit = $this->sdbService->endRental($sdbUnit);

            return response()->json([
                'success' => true,
                'message' => 'SDB berhasil dikosongkan',
                'data' => new SdbUnitResource($emptiedUnit)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 500);
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
            'statistics' => SdbUnit::getStatistics($sdbUnits)
        ]);
    }

    // 5. Metode `extendRental` juga menjadi ramping
    public function extendRental(Request $request, SdbUnit $sdbUnit)
    {
        $validated = $request->validate([
            'tanggal_mulai_baru' => 'required|date|before_or_equal:today',
            'nama_nasabah' => 'required|string|max:255'
        ], [
            'tanggal_mulai_baru.required' => 'Tanggal mulai perpanjangan wajib diisi.',
            'nama_nasabah.required' => 'Nama nasabah wajib diisi.'
        ]);

        try {
            $extendedUnit = $this->sdbService->extendRental($sdbUnit, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Masa sewa berhasil diperpanjang dan diperbarui.',
                'data'    => new SdbUnitResource($extendedUnit)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperpanjang sewa: ' . $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function getAttentionRequired()
    {
        // Logika ini cukup simpel, jadi bisa tetap di controller
        $sdbUnits = SdbUnit::needsAttention()->orderBy('tanggal_jatuh_tempo')->get();
        return response()->json(['success' => true, 'data' => SdbUnitResource::collection($sdbUnits)]);
    }

    /**
     * Mengambil data history dan kunjungan untuk ditampilkan di Modal via AJAX/Fetch.
     */
    public function getHistory(SdbUnit $sdbUnit)
    {
        // 1. Ambil history masa lalu dari database
        $rentalHistories = $sdbUnit->rentalHistories()->latest()->get();
        $visits = $sdbUnit->visits()->with('petugas')->latest()->get();

        // 2. PERBAIKAN: Jika Unit sedang terisi, masukkan data aktif ke tumpukan history paling atas
        if ($sdbUnit->nama_nasabah) {
            $activeSession = [
                'id' => 'active', // ID dummy
                'nama_nasabah' => $sdbUnit->nama_nasabah,
                'nomor_sdb' => $sdbUnit->nomor_sdb,
                'tanggal_mulai' => $sdbUnit->tanggal_sewa,
                'tanggal_berakhir' => $sdbUnit->tanggal_jatuh_tempo, // Masih berjalan
                'status_akhir' => 'SEDANG AKTIF', // Status khusus untuk pembeda UI
                'catatan' => 'Masa sewa sedang berjalan saat ini.',
            ];

            // Gabungkan: Data Aktif + Data History Lama
            // Kita pakai prepend agar muncul paling atas
            $rentalHistories->prepend($activeSession);
        }

        return response()->json([
            'rental_histories' => $rentalHistories,
            'visits' => $visits,
        ]);
    }
}
