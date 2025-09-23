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
            $table->foreignId('sdb_unit_id')->constrained()->onDelete('cascade');
            $table->enum('kegiatan', [
                'PENYEWAAN_BARU',
                'PERPANJANGAN',
                'SEWA_BERAKHIR',
                'EDIT_DATA'
            ]);
            $table->text('deskripsi');
            $table->timestamp('timestamp');
            $table->timestamps();

            // Indexes untuk performance
            $table->index(['sdb_unit_id', 'timestamp']);
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
