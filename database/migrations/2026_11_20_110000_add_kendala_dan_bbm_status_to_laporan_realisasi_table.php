<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_realisasi', function (Blueprint $table) {
            if (!Schema::hasColumn('laporan_realisasi', 'ada_kendala')) {
                $table->boolean('ada_kendala')->default(false)->after('status_laporan');
            }
            if (!Schema::hasColumn('laporan_realisasi', 'catatan_kendala')) {
                $table->text('catatan_kendala')->nullable()->after('ada_kendala');
            }
            if (!Schema::hasColumn('laporan_realisasi', 'ada_bbm')) {
                $table->boolean('ada_bbm')->default(false)->after('catatan_kendala');
            }
            if (!Schema::hasColumn('laporan_realisasi', 'keterangan_tanpa_bbm')) {
                $table->text('keterangan_tanpa_bbm')->nullable()->after('ada_bbm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('laporan_realisasi', function (Blueprint $table) {
            $table->dropColumn(['ada_kendala', 'catatan_kendala', 'ada_bbm', 'keterangan_tanpa_bbm']);
        });
    }
};

