<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom keuangan & struk pada tabel transaksi_bbm
        if (Schema::hasTable('transaksi_bbm')) {
            Schema::table('transaksi_bbm', function (Blueprint $table) {
                if (! Schema::hasColumn('transaksi_bbm', 'harga_per_liter')) {
                    $table->decimal('harga_per_liter', 15, 2)->nullable()->after('jumlah_liter');
                }
                if (! Schema::hasColumn('transaksi_bbm', 'total_biaya')) {
                    $table->decimal('total_biaya', 15, 2)->nullable()->after('harga_per_liter');
                }
                if (! Schema::hasColumn('transaksi_bbm', 'nomor_struk_nota')) {
                    $table->string('nomor_struk_nota', 100)->nullable()->after('total_biaya');
                }
            });
        }

        // 2. Tambah parameter tarif gaji kru & standar biaya pada tabel tim
        if (Schema::hasTable('tim')) {
            Schema::table('tim', function (Blueprint $table) {
                if (! Schema::hasColumn('tim', 'premi_pilot_per_ha')) {
                    $table->decimal('premi_pilot_per_ha', 12, 2)->default(22000.00)->after('status_tim');
                }
                if (! Schema::hasColumn('tim', 'premi_helper_per_ha')) {
                    $table->decimal('premi_helper_per_ha', 12, 2)->default(10000.00)->after('premi_pilot_per_ha');
                }
                if (! Schema::hasColumn('tim', 'gaji_pokok_pilot')) {
                    $table->decimal('gaji_pokok_pilot', 12, 2)->default(1000000.00)->after('premi_helper_per_ha');
                }
                if (! Schema::hasColumn('tim', 'uang_makan_pilot')) {
                    $table->decimal('uang_makan_pilot', 12, 2)->default(900000.00)->after('gaji_pokok_pilot');
                }
                if (! Schema::hasColumn('tim', 'uang_makan_helper_per_hari')) {
                    $table->decimal('uang_makan_helper_per_hari', 12, 2)->default(50000.00)->after('uang_makan_pilot');
                }
                if (! Schema::hasColumn('tim', 'biaya_enterteint_per_ha')) {
                    $table->decimal('biaya_enterteint_per_ha', 12, 2)->default(10000.00)->after('uang_makan_helper_per_hari');
                }
                if (! Schema::hasColumn('tim', 'honor_adm_tim')) {
                    $table->decimal('honor_adm_tim', 12, 2)->default(500000.00)->after('biaya_enterteint_per_ha');
                }
                if (! Schema::hasColumn('tim', 'biaya_mapping_per_ha')) {
                    $table->decimal('biaya_mapping_per_ha', 12, 2)->default(30000.00)->after('honor_adm_tim');
                }
            });
        }

        // 3. Buat tabel pengeluaran_operasional_unit
        if (! Schema::hasTable('pengeluaran_operasional_unit')) {
            Schema::create('pengeluaran_operasional_unit', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tim_id')->constrained('tim')->cascadeOnDelete()->cascadeOnUpdate();
                $table->unsignedTinyInteger('periode_bulan');
                $table->unsignedSmallInteger('periode_tahun');
                $table->date('tanggal_transaksi');
                $table->enum('kategori_biaya', [
                    'operasional_lapangan',
                    'sparepart',
                    'ransum_kantin',
                    'rental_mobil',
                    'upah_langsir',
                    'bon_pribadi',
                    'lain_lain',
                ]);
                $table->string('keterangan', 255);
                $table->decimal('nominal', 15, 2);
                $table->string('nomor_bukti_pi', 100)->nullable();
                $table->string('lampiran_nota', 255)->nullable();
                $table->foreignId('diinput_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->timestamps();

                $table->index(['tim_id', 'periode_tahun', 'periode_bulan'], 'idx_ops_tim_periode');
            });
        }

        // 4. Buat tabel kasbon_personil
        if (! Schema::hasTable('kasbon_personil')) {
            Schema::create('kasbon_personil', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tim_id')->constrained('tim')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('nama_personil', 150);
                $table->enum('peran_kru', ['pilot', 'helper', 'pelangsir', 'lainnya'])->default('pilot');
                $table->date('tanggal_kasbon');
                $table->unsignedTinyInteger('periode_bulan');
                $table->unsignedSmallInteger('periode_tahun');
                $table->decimal('jumlah_kasbon', 15, 2);
                $table->string('keterangan', 255)->nullable();
                $table->boolean('status_lunas')->default(false);
                $table->foreignId('diinput_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->timestamps();

                $table->index(['tim_id', 'periode_tahun', 'periode_bulan'], 'idx_kasbon_tim_periode');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_personil');
        Schema::dropIfExists('pengeluaran_operasional_unit');

        if (Schema::hasTable('tim')) {
            Schema::table('tim', function (Blueprint $table) {
                $table->dropColumn([
                    'premi_pilot_per_ha',
                    'premi_helper_per_ha',
                    'gaji_pokok_pilot',
                    'uang_makan_pilot',
                    'uang_makan_helper_per_hari',
                    'biaya_enterteint_per_ha',
                    'honor_adm_tim',
                    'biaya_mapping_per_ha',
                ]);
            });
        }

        if (Schema::hasTable('transaksi_bbm')) {
            Schema::table('transaksi_bbm', function (Blueprint $table) {
                $table->dropColumn(['harga_per_liter', 'total_biaya', 'nomor_struk_nota']);
            });
        }
    }
};
