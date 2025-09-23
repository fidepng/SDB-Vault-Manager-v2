<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SdbLog;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Persiapan Awal
        Schema::disableForeignKeyConstraints();
        User::truncate();
        SdbLog::truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Panggil Seeder untuk membuat struktur SDB KOSONG
        $this->call(SdbUnitSeeder::class);
        $this->command->info('Fixed SDB units structure has been created.');

        // 3. Buat User Admin Default
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@sdb.com',
            'password' => Hash::make('admin123'),
        ]);
        $this->command->info('Default admin user created (admin@sdb.com / admin123).');
    }
}
