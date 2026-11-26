<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('laporan_realisasi', function (Blueprint $table) {
            if (!Schema::hasColumn('laporan_realisasi', 'tanggal_input')) {
                $table->date('tanggal_input')->nullable()->after('spk_id')->comment('Tanggal pencatatan laporan realisasi oleh pekerja');
            }
            $table->string('nomor_laporan', 100)->nullable()->change();
            $table->text('nama_helper')->nullable()->change();
        });

        // Backfill tanggal_input untuk data historis
        DB::table('laporan_realisasi')
            ->whereNull('tanggal_input')
            ->update(['tanggal_input' => DB::raw('DATE(created_at)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_realisasi', function (Blueprint $table) {
            if (Schema::hasColumn('laporan_realisasi', 'tanggal_input')) {
                $table->dropColumn('tanggal_input');
            }
        });
    }
};

