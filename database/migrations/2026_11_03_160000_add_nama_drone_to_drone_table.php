<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('drone')) {
            Schema::table('drone', function (Blueprint $table) {
                if (! Schema::hasColumn('drone', 'nama_drone')) {
                    $table->string('nama_drone', 120)->nullable()->after('nomor_drone');
                }
            });

            // Beri nama default untuk data drone yang sudah ada
            DB::table('drone')->where('nomor_drone', 'DRN-T40-001')->whereNull('nama_drone')
                ->update(['nama_drone' => 'Drone Bertuah Alpha 01']);
            DB::table('drone')->where('nomor_drone', 'DRN-T30-002')->whereNull('nama_drone')
                ->update(['nama_drone' => 'Drone Bertuah Beta 02']);
            DB::table('drone')->whereNull('nama_drone')
                ->update(['nama_drone' => DB::raw("CONCAT('Drone ', tipe_drone)")]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('drone')) {
            Schema::table('drone', function (Blueprint $table) {
                if (Schema::hasColumn('drone', 'nama_drone')) {
                    $table->dropColumn('nama_drone');
                }
            });
        }
    }
};

