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
        // 1. Tambah kolom harga_per_liter pada jenis_bbm (master harga tetap per liter)
        if (Schema::hasTable('jenis_bbm') && ! Schema::hasColumn('jenis_bbm', 'harga_per_liter')) {
            Schema::table('jenis_bbm', function (Blueprint $table) {
                $table->decimal('harga_per_liter', 15, 2)->default(0)->after('satuan');
            });
        }

        // Isi harga awal jika kosong pada data master jenis BBM yang sudah ada
        DB::table('jenis_bbm')->where('nama_bbm', 'like', '%Pertamax%')->where('harga_per_liter', 0)->update(['harga_per_liter' => 13500]);
        DB::table('jenis_bbm')->where('nama_bbm', 'like', '%Pertalite%')->where('harga_per_liter', 0)->update(['harga_per_liter' => 10000]);

        // 2. Ubah kolom keperluan pada transaksi_bbm dari enum menjadi varchar agar mendukung 'stok_masuk'
        if (Schema::hasTable('transaksi_bbm')) {
            // Pada MySQL/MariaDB, ubah definisi enum menjadi varchar(100) nullable
            DB::statement("ALTER TABLE transaksi_bbm MODIFY COLUMN keperluan VARCHAR(100) NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jenis_bbm') && Schema::hasColumn('jenis_bbm', 'harga_per_liter')) {
            Schema::table('jenis_bbm', function (Blueprint $table) {
                $table->dropColumn('harga_per_liter');
            });
        }

        if (Schema::hasTable('transaksi_bbm')) {
            DB::statement("ALTER TABLE transaksi_bbm MODIFY COLUMN keperluan ENUM('operasional_drone','mobil','genset','lainnya') NULL");
        }
    }
};

