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
        Schema::table('laporan_realisasi', function (Blueprint $table) {
            if (!Schema::hasColumn('laporan_realisasi', 'nama_tim_manual')) {
                $table->string('nama_tim_manual', 150)->nullable()->after('tim_id')->comment('Nama tim jika diketikkan langsung');
            }
            if (!Schema::hasColumn('laporan_realisasi', 'nama_pilot')) {
                $table->string('nama_pilot', 150)->nullable()->after('nama_tim_manual')->comment('Nama pilot penerbangan');
            }
            if (!Schema::hasColumn('laporan_realisasi', 'nama_copilot')) {
                $table->string('nama_copilot', 150)->nullable()->after('nama_pilot')->comment('Nama co-pilot penerbangan');
            }
            if (!Schema::hasColumn('laporan_realisasi', 'nama_helper')) {
                $table->string('nama_helper', 150)->nullable()->after('nama_copilot')->comment('Nama helper di lapangan (nama bebas)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_realisasi', function (Blueprint $table) {
            $table->dropColumn(['nama_tim_manual', 'nama_pilot', 'nama_copilot', 'nama_helper']);
        });
    }
};
