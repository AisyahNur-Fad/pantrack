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
            if (! Schema::hasColumn('tim', 'gaji_pokok_helper')) {
                $table->decimal('gaji_pokok_helper', 12, 2)->default(0.00)->after('gaji_pokok_pilot');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tim', function (Blueprint $table) {
            if (Schema::hasColumn('tim', 'gaji_pokok_helper')) {
                $table->dropColumn('gaji_pokok_helper');
            }
        });
    }
};
