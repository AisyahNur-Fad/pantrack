<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom catatan_perawatan pada tabel drone.
     */
    public function up(): void
    {
        Schema::table('drone', function (Blueprint $table) {
            $table->text('catatan_perawatan')->nullable()->after('status_operasional');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('drone', function (Blueprint $table) {
            $table->dropColumn('catatan_perawatan');
        });
    }
};

