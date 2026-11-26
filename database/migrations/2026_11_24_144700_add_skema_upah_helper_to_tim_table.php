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
        Schema::table('tim', function (Blueprint $table) {
            $table->string('skema_upah_helper', 30)
                ->default('per_hektar')
                ->after('premi_helper_per_ha')
                ->comment('Skema pembayaran helper: per_hektar atau harian');

            $table->decimal('tarif_helper_per_hari', 12, 2)
                ->default(100000.00)
                ->after('skema_upah_helper')
                ->comment('Tarif upah harian helper jika skema_upah_helper = harian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tim', function (Blueprint $table) {
            $table->dropColumn(['skema_upah_helper', 'tarif_helper_per_hari']);
        });
    }
};

