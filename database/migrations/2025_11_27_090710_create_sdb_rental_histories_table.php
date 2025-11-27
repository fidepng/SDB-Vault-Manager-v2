<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdb_rental_histories', function (Blueprint $table) {
            $table->id();
            // Menyimpan ID unit (nullable jika unit fisik dihapus di masa depan)
            $table->foreignId('sdb_unit_id')->nullable()->constrained()->nullOnDelete();

            // Redundansi data penting agar tetap ada walau unit dihapus
            $table->string('nomor_sdb');
            $table->string('nama_nasabah');

            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');
            $table->integer('durasi_tahun');

            $table->enum('status_akhir', ['selesai', 'diputus', 'pindah']);
            $table->text('catatan')->nullable();

            $table->timestamps();

            // Index untuk pencarian cepat (best practice operasional)
            $table->index('nomor_sdb');
            $table->index('nama_nasabah');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdb_rental_histories');
    }
};
