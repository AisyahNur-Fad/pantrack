<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_realisasi', function (Blueprint $table) {
            if (!Schema::hasColumn('detail_realisasi', 'perlu_mapping')) {
                $table->boolean('perlu_mapping')->default(false)->after('luas_realisasi_ha')->index();
            }
            if (!Schema::hasColumn('detail_realisasi', 'tarif_mapping_per_ha')) {
                $table->decimal('tarif_mapping_per_ha', 15, 2)->nullable()->after('tarif_per_ha');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detail_realisasi', function (Blueprint $table) {
            if (Schema::hasColumn('detail_realisasi', 'tarif_mapping_per_ha')) {
                $table->dropColumn('tarif_mapping_per_ha');
            }
            if (Schema::hasColumn('detail_realisasi', 'perlu_mapping')) {
                $table->dropColumn('perlu_mapping');
            }
        });
    }
};

