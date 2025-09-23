<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SdbUnit; // <-- BARU: Tambahkan use statement
use Carbon\Carbon;     // <-- BARU: Tambahkan use statement

class TenantDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding dynamic and varied tenant data...');

        // Ambil semua unit SDB yang tersedia dan acak urutannya
        $availableUnits = SdbUnit::whereNull('nama_nasabah')->get()->shuffle();
        if ($availableUnits->isEmpty()) {
            $this->command->warn('No empty SDB units available to seed tenant data. Skipping.');
            return;
        }
        $totalUnits = $availableUnits->count();

        // Tentukan persentase untuk setiap status
        $overdueCount = (int)($totalUnits * 0.10); // 10% Lewat Jatuh Tempo
        $dueSoonCount = (int)($totalUnits * 0.15); // 15% Akan Jatuh Tempo
        $rentedCount = (int)($totalUnits * 0.60);  // 60% Terisi Normal

        $overdueUnits = $availableUnits->splice(0, $overdueCount);
        $dueSoonUnits = $availableUnits->splice(0, $dueSoonCount);
        $rentedUnits = $availableUnits->splice(0, $rentedCount);

        $firstNames = ['Adi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar', 'Gita', 'Hadi', 'Indah', 'Joko'];
        $lastNames = ['Wijaya', 'Santoso', 'Kusuma', 'Lestari', 'Gunawan', 'Hartono', 'Setiawan'];

        // 1. Lewat Jatuh Tempo
        foreach ($overdueUnits as $unit) {
            $jatuhTempo = now()->subDays(rand(5, 60));
            $sewa = $jatuhTempo->copy()->subYear()->addDays(rand(0, 10));
            $unit->update($this->createTenantPayload($sewa, $jatuhTempo, $firstNames, $lastNames));
        }
        if ($overdueUnits->count() > 0) $this->command->info("{$overdueUnits->count()} SDB units seeded with 'Overdue' status.");

        // 2. Akan Jatuh Tempo
        foreach ($dueSoonUnits as $unit) {
            $jatuhTempo = now()->addDays(rand(0, SdbUnit::WARNING_DAYS));
            $sewa = $jatuhTempo->copy()->subYear()->addDays(rand(0, 10));
            $unit->update($this->createTenantPayload($sewa, $jatuhTempo, $firstNames, $lastNames));
        }
        if ($dueSoonUnits->count() > 0) $this->command->info("{$dueSoonUnits->count()} SDB units seeded with 'Due Soon' status.");

        // 3. Terisi Normal
        foreach ($rentedUnits as $unit) {
            $jatuhTempo = now()->addDays(rand(SdbUnit::WARNING_DAYS + 1, 365));
            $sewa = $jatuhTempo->copy()->subYear()->addDays(rand(0, 10));
            $unit->update($this->createTenantPayload($sewa, $jatuhTempo, $firstNames, $lastNames));
        }
        if ($rentedUnits->count() > 0) $this->command->info("{$rentedUnits->count()} SDB units seeded with 'Rented' status.");

        $this->command->info("Dummy tenant data seeding complete.");
    }

    private function createTenantPayload(Carbon $sewa, Carbon $jatuhTempo, array $firstNames, array $lastNames): array
    {
        return [
            'nama_nasabah' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
            'tanggal_sewa' => $sewa->toDateString(),
            'tanggal_jatuh_tempo' => $jatuhTempo->toDateString(),
        ];
    }
}
