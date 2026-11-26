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
        if (!Schema::hasTable('peran')) {
            Schema::create('peran', function (Blueprint $table) {
                $table->id();
                $table->string('nama', 50)->unique();
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('distrik')) {
            Schema::create('distrik', function (Blueprint $table) {
                $table->id();
                $table->string('nama_distrik', 120)->unique();
                $table->text('keterangan')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('drone')) {
            Schema::create('drone', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_drone', 50)->unique();
                $table->string('tipe_drone', 100);
                $table->enum('status_operasional', ['aktif', 'perawatan', 'tidak_beroperasi'])->default('aktif');
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pengguna')) {
            Schema::create('pengguna', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peran_id')->constrained('peran')->cascadeOnUpdate();
                $table->string('nama_lengkap', 150);
                $table->string('nama_pengguna', 80)->unique();
                $table->string('email', 150)->nullable()->unique();
                $table->string('kata_sandi', 255)->comment('Simpan hash kata sandi Laravel, bukan teks asli');
                $table->string('nomor_telepon', 30)->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('jenis_pekerjaan')) {
            Schema::create('jenis_pekerjaan', function (Blueprint $table) {
                $table->id();
                $table->string('kode_pekerjaan', 50)->unique();
                $table->string('nama_pekerjaan', 120);
                $table->text('keterangan')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('jenis_bbm')) {
            Schema::create('jenis_bbm', function (Blueprint $table) {
                $table->id();
                $table->string('nama_bbm', 80)->unique();
                $table->string('satuan', 20)->default('liter');
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('petak')) {
            Schema::create('petak', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distrik_id')->constrained('distrik')->cascadeOnUpdate();
                $table->string('kode_petak', 80);
                $table->string('nama_petak', 150)->nullable();
                $table->string('jenis_lahan', 100)->nullable();
                $table->decimal('luas_petak_ha', 12, 2)->nullable();
                $table->enum('status_petak', ['aktif', 'tidak_aktif'])->default('aktif');
                $table->text('keterangan')->nullable();
                $table->timestamps();
                $table->unique(['distrik_id', 'kode_petak'], 'uq_petak_distrik_kode');
            });
        }

        if (!Schema::hasTable('tim')) {
            Schema::create('tim', function (Blueprint $table) {
                $table->id();
                $table->string('nama_tim', 120);
                $table->foreignId('drone_id')->constrained('drone')->cascadeOnUpdate();
                $table->foreignId('distrik_id')->nullable()->constrained('distrik')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('kepala_tim_id')->nullable()->comment('Pengguna yang menjadi penanggung jawab tim, jika ditetapkan')->constrained('pengguna')->nullOnDelete()->cascadeOnUpdate();
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->enum('status_tim', ['aktif', 'tidak_aktif'])->default('aktif');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('anggota_tim')) {
            Schema::create('anggota_tim', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tim_id')->constrained('tim')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('pengguna_id')->nullable()->comment('Diisi jika anggota memiliki akun PANTRACK')->constrained('pengguna')->nullOnDelete()->cascadeOnUpdate();
                $table->string('nama_anggota', 150);
                $table->enum('jabatan_tim', ['pilot', 'co_pilot', 'helper', 'anggota_lain']);
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tarif_pekerjaan')) {
            Schema::create('tarif_pekerjaan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jenis_pekerjaan_id')->constrained('jenis_pekerjaan')->cascadeOnUpdate();
                $table->decimal('nilai_tarif', 15, 2);
                $table->date('berlaku_mulai');
                $table->date('berlaku_sampai')->nullable();
                $table->text('keterangan')->nullable();
                $table->foreignId('dibuat_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->timestamps();
                $table->index(['jenis_pekerjaan_id', 'berlaku_mulai', 'berlaku_sampai'], 'idx_tarif_berlaku');
            });
        }

        if (!Schema::hasTable('spk')) {
            Schema::create('spk', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_spk', 100)->unique();
                $table->date('tanggal_spk')->nullable();
                $table->unsignedTinyInteger('periode_bulan');
                $table->year('periode_tahun');
                $table->foreignId('distrik_id')->constrained('distrik')->cascadeOnUpdate();
                $table->foreignId('jenis_pekerjaan_id')->nullable()->constrained('jenis_pekerjaan')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('diinput_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->foreignId('disetujui_oleh')->nullable()->constrained('pengguna')->nullOnDelete()->cascadeOnUpdate();
                $table->dateTime('tanggal_persetujuan')->nullable();
                $table->enum('status_spk', ['menunggu_persetujuan', 'perlu_perbaikan', 'disetujui', 'ditolak'])->default('menunggu_persetujuan');
                $table->text('catatan_perbaikan')->nullable();
                $table->string('berkas_spk', 255)->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('detail_spk')) {
            Schema::create('detail_spk', function (Blueprint $table) {
                $table->id();
                $table->foreignId('spk_id')->constrained('spk')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('petak_id')->constrained('petak')->cascadeOnUpdate();
                $table->decimal('luas_target_ha', 12, 2)->default(0.00);
                $table->decimal('tarif_per_ha', 15, 2)->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
                $table->unique(['spk_id', 'petak_id'], 'uq_detail_spk_petak');
            });
        }

        if (!Schema::hasTable('laporan_realisasi')) {
            Schema::create('laporan_realisasi', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_laporan', 60)->unique();
                $table->foreignId('tim_id')->constrained('tim')->cascadeOnUpdate();
                $table->foreignId('spk_id')->nullable()->constrained('spk')->nullOnDelete()->cascadeOnUpdate();
                $table->unsignedTinyInteger('periode_bulan');
                $table->year('periode_tahun');
                $table->foreignId('distrik_id')->constrained('distrik')->cascadeOnUpdate();
                $table->foreignId('diinput_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->foreignId('diperiksa_oleh')->nullable()->constrained('pengguna')->nullOnDelete()->cascadeOnUpdate();
                $table->dateTime('tanggal_pemeriksaan')->nullable();
                $table->enum('status_laporan', ['draf', 'menunggu_pemeriksaan', 'perlu_perbaikan', 'disetujui', 'ditolak'])->default('draf');
                $table->text('catatan_pemeriksaan')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('detail_realisasi')) {
            Schema::create('detail_realisasi', function (Blueprint $table) {
                $table->id();
                $table->foreignId('laporan_realisasi_id')->constrained('laporan_realisasi')->cascadeOnDelete()->cascadeOnUpdate();
                $table->date('tanggal_pekerjaan');
                $table->foreignId('petak_id')->nullable()->constrained('petak')->nullOnDelete()->cascadeOnUpdate();
                $table->string('kode_petak_manual', 80)->nullable()->comment('Dipakai jika kode petak belum tersedia di master');
                $table->foreignId('jenis_pekerjaan_id')->constrained('jenis_pekerjaan')->cascadeOnUpdate();
                $table->decimal('luas_realisasi_ha', 12, 2)->default(0.00);
                $table->enum('status_pekerjaan', [
                    'belum_dimulai',
                    'dalam_proses',
                    'selesai',
                    'terkendala',
                    'menunggu_mapping',
                    'menunggu_flight_mission',
                    'proses_mapping'
                ]);
                $table->text('keterangan_kendala')->nullable();
                $table->decimal('tarif_per_ha', 15, 2)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transaksi_bbm')) {
            Schema::create('transaksi_bbm', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_transaksi', 60)->unique();
                $table->foreignId('tim_id')->constrained('tim')->cascadeOnUpdate();
                $table->foreignId('jenis_bbm_id')->constrained('jenis_bbm')->cascadeOnUpdate();
                $table->enum('jenis_transaksi', ['stok_awal', 'pengajuan', 'penerimaan', 'penggunaan', 'penyesuaian']);
                $table->date('tanggal_transaksi');
                $table->decimal('jumlah_liter', 12, 2);
                $table->enum('keperluan', ['operasional_drone', 'mobil', 'genset', 'lainnya'])->nullable();
                $table->text('keterangan')->nullable();
                $table->enum('status_verifikasi', ['menunggu_pemeriksaan', 'terverifikasi', 'perlu_perbaikan', 'ditolak'])->default('menunggu_pemeriksaan');
                $table->foreignId('diinput_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->foreignId('diperiksa_oleh')->nullable()->constrained('pengguna')->nullOnDelete()->cascadeOnUpdate();
                $table->dateTime('tanggal_pemeriksaan')->nullable();
                $table->text('catatan_pemeriksaan')->nullable();
                $table->timestamps();
                $table->index(['tim_id', 'tanggal_transaksi'], 'idx_bbm_tim_tanggal');
            });
        }

        if (!Schema::hasTable('lampiran_bbm')) {
            Schema::create('lampiran_bbm', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transaksi_bbm_id')->constrained('transaksi_bbm')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('nama_berkas_asli', 255);
                $table->string('lokasi_berkas', 255);
                $table->foreignId('diunggah_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lampiran_realisasi')) {
            Schema::create('lampiran_realisasi', function (Blueprint $table) {
                $table->id();
                $table->foreignId('laporan_realisasi_id')->constrained('laporan_realisasi')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('nama_berkas_asli', 255);
                $table->string('lokasi_berkas', 255);
                $table->string('jenis_lampiran', 80)->nullable();
                $table->foreignId('diunggah_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('riwayat_aktivitas')) {
            Schema::create('riwayat_aktivitas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pengguna_id')->nullable()->constrained('pengguna')->nullOnDelete()->cascadeOnUpdate();
                $table->string('nama_aktivitas', 120);
                $table->string('jenis_data', 80);
                $table->unsignedBigInteger('id_data')->nullable();
                $table->text('keterangan')->nullable();
                $table->string('alamat_ip', 45)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['jenis_data', 'id_data'], 'idx_aktivitas_data');
            });
        }

        if (!Schema::hasTable('ekspor_laporan')) {
            Schema::create('ekspor_laporan', function (Blueprint $table) {
                $table->id();
                $table->string('nama_berkas', 255);
                $table->unsignedTinyInteger('periode_bulan');
                $table->year('periode_tahun');
                $table->foreignId('diekspor_oleh')->constrained('pengguna')->cascadeOnUpdate();
                $table->dateTime('waktu_ekspor');
                $table->unsignedInteger('jumlah_baris')->default(0);
                $table->string('lokasi_berkas', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ekspor_laporan');
        Schema::dropIfExists('riwayat_aktivitas');
        Schema::dropIfExists('lampiran_realisasi');
        Schema::dropIfExists('lampiran_bbm');
        Schema::dropIfExists('transaksi_bbm');
        Schema::dropIfExists('detail_realisasi');
        Schema::dropIfExists('laporan_realisasi');
        Schema::dropIfExists('detail_spk');
        Schema::dropIfExists('spk');
        Schema::dropIfExists('tarif_pekerjaan');
        Schema::dropIfExists('anggota_tim');
        Schema::dropIfExists('tim');
        Schema::dropIfExists('petak');
        Schema::dropIfExists('jenis_bbm');
        Schema::dropIfExists('jenis_pekerjaan');
        Schema::dropIfExists('pengguna');
        Schema::dropIfExists('drone');
        Schema::dropIfExists('distrik');
        Schema::dropIfExists('peran');
    }
};
