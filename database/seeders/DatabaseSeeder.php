<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SdbUnit;
use App\Models\SdbRentalHistory;
use App\Models\SdbVisit;
use App\Models\SdbLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. BUAT USER (SUPER ADMIN & ADMIN BIASA)
        // Kita gunakan firstOrCreate agar tidak error jika dijalankan berulang
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@bank.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
            ]
        );

        $admin1 = User::firstOrCreate(
            ['email' => 'cs1@bank.com'],
            [
                'name' => 'Petugas CS 1',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. GENERATE UNIT SESUAI LAYOUT ASLI
        // PENTING: Kita panggil seeder asli agar layout denah tidak rusak/undefined
        $this->call(SdbUnitSeeder::class);

        // 3. AMBIL UNIT YANG SUDAH ADA UNTUK DISIMULASIKAN
        // Kita ambil beberapa unit secara acak untuk dijadikan bahan tes
        $units = SdbUnit::take(10)->get();

        if ($units->count() < 5) {
            $this->command->warn('Jumlah unit dari SdbUnitSeeder terlalu sedikit untuk skenario lengkap. Pastikan SdbUnitSeeder mengisi data.');
            return;
        }

        // --- SKENARIO DATA (SEEDING DI ATAS DATA ASLI) ---

        // Kasus A: SDB Terisi Normal (Baru Sewa) -> Unit ke-1
        $this->sewaUnit($units[0], 'Budi Santoso', Carbon::now()->subMonths(2));

        // Kasus B: SDB Akan Jatuh Tempo (H-5) -> Unit ke-2
        $this->sewaUnit($units[1], 'Siti Aminah', Carbon::now()->subYear()->addDays(5));

        // Kasus C: SDB Lewat Jatuh Tempo (H+10) -> Unit ke-3
        $this->sewaUnit($units[2], 'Doni Pratama', Carbon::now()->subYear()->subDays(10));

        // Kasus D: SDB Kosong tapi punya History Sewa -> Unit ke-4
        $unitEx = $units[3];
        // Pastikan unit ini kosong dulu
        $unitEx->update(['nama_nasabah' => null, 'tanggal_sewa' => null, 'tanggal_jatuh_tempo' => null]);

        SdbRentalHistory::create([
            'sdb_unit_id' => $unitEx->id,
            'nomor_sdb' => $unitEx->nomor_sdb,
            'nama_nasabah' => 'Mantan Nasabah',
            'tanggal_mulai' => Carbon::now()->subYears(2),
            'tanggal_berakhir' => Carbon::now()->subYear(),
            'durasi_tahun' => 1,
            'status_akhir' => 'selesai',
            'catatan' => 'Berhenti langganan, pindah kota.'
        ]);

        SdbLog::create([
            'sdb_unit_id' => $unitEx->id,
            'user_id' => $admin1->id,
            'kegiatan' => 'SEWA_BERAKHIR',
            'deskripsi' => 'Sewa berakhir untuk Mantan Nasabah',
            'timestamp' => Carbon::now()->subYear()
        ]);

        // Kasus E: SDB Terisi & Punya Banyak Kunjungan -> Unit ke-5
        $unitVisit = $units[4];
        $this->sewaUnit($unitVisit, 'Rina Wati', Carbon::now()->subMonths(6));

        // Seed Kunjungan
        SdbVisit::create([
            'sdb_unit_id' => $unitVisit->id,
            'nama_pengunjung' => 'Rina Wati',
            'waktu_kunjung' => Carbon::now()->subMonths(5),
            'petugas_id' => $admin1->id
        ]);
        SdbVisit::create([
            'sdb_unit_id' => $unitVisit->id,
            'nama_pengunjung' => 'Suami Rina',
            'waktu_kunjung' => Carbon::now()->subMonths(2),
            'petugas_id' => $superAdmin->id,
            'keterangan' => 'Mewakilkan istri'
        ]);
        SdbVisit::create([
            'sdb_unit_id' => $unitVisit->id,
            'nama_pengunjung' => 'Rina Wati',
            'waktu_kunjung' => Carbon::now()->subDays(3),
            'petugas_id' => $admin1->id
        ]);
    }

    // Helper function untuk menyewa unit
    private function sewaUnit($unit, $nama, $tanggalMulai)
    {
        $jatuhTempo = (clone $tanggalMulai)->addYear();

        $unit->update([
            'nama_nasabah' => $nama,
            'tanggal_sewa' => $tanggalMulai,
            'tanggal_jatuh_tempo' => $jatuhTempo
        ]);

        // Buat Log Pendaftaran
        SdbLog::create([
            'sdb_unit_id' => $unit->id,
            'user_id' => 1, // Asumsi ID 1 adalah superadmin
            'kegiatan' => 'PENYEWAAN_BARU',
            'deskripsi' => "Penyewaan baru a.n $nama",
            'timestamp' => $tanggalMulai
        ]);
    }
}
