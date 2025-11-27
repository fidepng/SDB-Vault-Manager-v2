<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sdb_logs', function (Blueprint $table) {
            $table->id();

            // UBAH: Nullable, karena log sistem (seperti Login) tidak butuh ID unit
            $table->foreignId('sdb_unit_id')->nullable()->constrained()->nullOnDelete();

            // BARU: Kolom user_id untuk mencatat SIAPA yang melakukan aksi (Audit Trail)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // UBAH: Gunakan string agar fleksibel (bisa mencatat 'LOGIN', 'LOGOUT', dll)
            // Hapus enum lama dan ganti string
            $table->string('kegiatan');

            $table->text('deskripsi');
            $table->string('ip_address', 45)->nullable(); // Opsional: catat IP
            $table->timestamp('timestamp');
            $table->timestamps();

            // Indexes untuk performa pencarian log
            $table->index(['sdb_unit_id', 'timestamp']);
            $table->index('user_id');
            $table->index('kegiatan');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sdb_logs');
    }
};
