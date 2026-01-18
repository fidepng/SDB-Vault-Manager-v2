<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sdb_logs', function (Blueprint $table) {
            // Opsional: Tambah kolom untuk advanced tracking
            $table->string('user_agent', 500)->nullable()->after('ip_address');
            $table->json('metadata')->nullable()->after('user_agent');

            // Index untuk performa query
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('sdb_logs', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'metadata']);
            $table->dropIndex(['created_at']);
        });
    }
};
