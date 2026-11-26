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
        // 1. Modifikasi status_spk enum untuk mendukung 'selesai' dan tambahkan tim_id pada SPK
        if (Schema::hasTable('spk')) {
            try {
                DB::statement("ALTER TABLE `spk` MODIFY COLUMN `status_spk` ENUM('menunggu_persetujuan', 'perlu_perbaikan', 'disetujui', 'selesai', 'ditolak') DEFAULT 'menunggu_persetujuan'");
            } catch (\Throwable $e) {
                // Ignore if database does not use enum or already modified
            }

            Schema::table('spk', function (Blueprint $table) {
                if (!Schema::hasColumn('spk', 'tim_id')) {
                    $table->foreignId('tim_id')->nullable()->after('distrik_id')->constrained('tim')->nullOnDelete();
                }
            });
        }

        // 2. Tambah ambang_batas_servis_ha pada tabel drone
        if (Schema::hasTable('drone')) {
            Schema::table('drone', function (Blueprint $table) {
                if (!Schema::hasColumn('drone', 'ambang_batas_servis_ha')) {
                    $table->decimal('ambang_batas_servis_ha', 10, 2)->default(200.00)->after('status_operasional');
                }
            });
        }

        // 3. Tambah opsi filter tanggal dan opsi harga pada ekspor_laporan
        if (Schema::hasTable('ekspor_laporan')) {
            Schema::table('ekspor_laporan', function (Blueprint $table) {
                if (!Schema::hasColumn('ekspor_laporan', 'tanggal_mulai')) {
                    $table->date('tanggal_mulai')->nullable()->after('periode_tahun');
                }
                if (!Schema::hasColumn('ekspor_laporan', 'tanggal_selesai')) {
                    $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
                }
                if (!Schema::hasColumn('ekspor_laporan', 'opsi_harga')) {
                    $table->boolean('opsi_harga')->default(true)->after('lokasi_berkas');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('spk')) {
            Schema::table('spk', function (Blueprint $table) {
                if (Schema::hasColumn('spk', 'tim_id')) {
                    $table->dropForeign(['tim_id']);
                    $table->dropColumn('tim_id');
                }
            });
        }

        if (Schema::hasTable('drone')) {
            Schema::table('drone', function (Blueprint $table) {
                if (Schema::hasColumn('drone', 'ambang_batas_servis_ha')) {
                    $table->dropColumn('ambang_batas_servis_ha');
                }
            });
        }

        if (Schema::hasTable('ekspor_laporan')) {
            Schema::table('ekspor_laporan', function (Blueprint $table) {
                if (Schema::hasColumn('ekspor_laporan', 'tanggal_mulai')) {
                    $table->dropColumn(['tanggal_mulai', 'tanggal_selesai', 'opsi_harga']);
                }
            });
        }
    }
};
