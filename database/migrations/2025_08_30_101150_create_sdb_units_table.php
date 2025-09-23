<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- BARU: Tambahkan DB Facade

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdb_units', function (Blueprint $table) {
            $table->id();
            // UBAH: Sesuaikan panjang nomor SDB menjadi 3 karakter
            $table->string('nomor_sdb', 3)->unique();
            $table->enum('tipe', ['B', 'C']);
            $table->string('nama_nasabah')->nullable();
            $table->date('tanggal_sewa')->nullable();
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->timestamps();

            // UBAH: Index cukup pada nomor_sdb karena sudah unik
            $table->index('nama_nasabah');
            $table->index('tanggal_jatuh_tempo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdb_units');
    }
};
