<?php

// dalam file migrasi yang baru dibuat
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sdb_units', function (Blueprint $table) {
            // Mengubah kolom menjadi TIMESTAMP.
            // TIMESTAMP di MySQL/PostgreSQL akan menyimpan info timezone
            // dan bekerja sangat baik dengan setting UTC Laravel.
            $table->timestamp('tanggal_sewa')->nullable()->change();
            $table->timestamp('tanggal_jatuh_tempo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sdb_units', function (Blueprint $table) {
            // Logika untuk mengembalikan jika diperlukan (rollback)
            $table->date('tanggal_sewa')->nullable()->change();
            $table->date('tanggal_jatuh_tempo')->nullable()->change();
        });
    }
};
