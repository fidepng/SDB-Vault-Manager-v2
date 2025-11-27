<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdb_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdb_unit_id')->constrained()->cascadeOnDelete();

            // Nama pengunjung (default nama nasabah, tapi bisa diwakilkan)
            $table->string('nama_pengunjung');
            $table->dateTime('waktu_kunjung');

            // Mencatat siapa petugas yang melayani (Audit Trail)
            $table->foreignId('petugas_id')->constrained('users');

            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('waktu_kunjung');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdb_visits');
    }
};
