<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaksi_bbm') && ! Schema::hasColumn('transaksi_bbm', 'laporan_realisasi_id')) {
            Schema::table('transaksi_bbm', function (Blueprint $table) {
                $table->foreignId('laporan_realisasi_id')
                    ->nullable()
                    ->after('keperluan')
                    ->constrained('laporan_realisasi')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaksi_bbm') && Schema::hasColumn('transaksi_bbm', 'laporan_realisasi_id')) {
            Schema::table('transaksi_bbm', function (Blueprint $table) {
                $table->dropForeign(['laporan_realisasi_id']);
                $table->dropColumn('laporan_realisasi_id');
            });
        }
    }
};
