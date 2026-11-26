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
        // 1. Tambah kolom tipe ke tabel jenis_pekerjaan jika belum ada
        if (Schema::hasTable('jenis_pekerjaan') && !Schema::hasColumn('jenis_pekerjaan', 'tipe')) {
            Schema::table('jenis_pekerjaan', function (Blueprint $table) {
                $table->string('tipe', 20)->default('utama')->after('nama_pekerjaan')->index();
            });
        }

        // 2. Pastikan data jenis_pekerjaan yang sudah ada memiliki tipe = 'utama'
        DB::table('jenis_pekerjaan')->whereNull('tipe')->orWhere('tipe', '')->update(['tipe' => 'utama']);

        // 3. Dapatkan admin ID untuk pencatatan dibuat_oleh pada tarif
        $adminId = DB::table('pengguna')->where('nama_pengguna', 'admin')->value('id')
            ?? DB::table('pengguna')->min('id');

        if (!$adminId) {
            $adminId = DB::table('pengguna')->insertGetId([
                'nama_pengguna' => 'admin',
                'nama_lengkap' => 'Administrator',
                'email' => 'admin@pantrack.id',
                'kata_sandi' => \Illuminate\Support\Facades\Hash::make('password'),
                'peran' => 'admin',
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Daftarkan 6 Varian Lapangan Resmi (5 Utama + 1 Addon Mapping)
        $varianData = [
            [
                'kode' => 'SPY-HPT',
                'nama' => 'Spraying - HPT',
                'tipe' => 'utama',
                'tarif' => 239500.00,
                'ket' => 'Penyemprotan hama & penyakit tanaman (HPT)',
            ],
            [
                'kode' => 'SPY-HPT-CW1',
                'nama' => 'Spraying - HPT + CW 1',
                'tipe' => 'utama',
                'tarif' => 239500.00,
                'ket' => 'Penyemprotan HPT + Chemical Weeding tahap 1',
            ],
            [
                'kode' => 'SPY-HPT2-CW2',
                'nama' => 'Spraying - HPT 2 + CW 2',
                'tipe' => 'utama',
                'tarif' => 239500.00,
                'ket' => 'Penyemprotan HPT 2 + Chemical Weeding tahap 2',
            ],
            [
                'kode' => 'SPD-PS1',
                'nama' => 'Spreading - PS1 (Standar)',
                'tipe' => 'utama',
                'tarif' => 300000.00,
                'ket' => 'Penebaran pupuk solid 1 lahan mineral/standar',
            ],
            [
                'kode' => 'SPD-PS1-MC',
                'nama' => 'Spreading - PS1 Marine Clay',
                'tipe' => 'utama',
                'tarif' => 340000.00,
                'ket' => 'Penebaran pupuk solid 1 lahan tanah liat laut / basah',
            ],
            [
                'kode' => 'SRV-MAPPING',
                'nama' => 'Jasa Mapping Petak Drone',
                'tipe' => 'addon',
                'tarif' => 30000.00,
                'ket' => 'Layanan pemetaan koordinat petak & pembuatan misi jalur terbang drone',
            ],
        ];

        foreach ($varianData as $v) {
            $existing = DB::table('jenis_pekerjaan')->where('kode_pekerjaan', $v['kode'])->first();
            $now = now();

            if ($existing) {
                DB::table('jenis_pekerjaan')->where('id', $existing->id)->update([
                    'nama_pekerjaan' => $v['nama'],
                    'tipe' => $v['tipe'],
                    'keterangan' => $v['ket'],
                    'aktif' => true,
                    'updated_at' => $now,
                ]);
                $jenisId = $existing->id;
            } else {
                $jenisId = DB::table('jenis_pekerjaan')->insertGetId([
                    'kode_pekerjaan' => $v['kode'],
                    'nama_pekerjaan' => $v['nama'],
                    'tipe' => $v['tipe'],
                    'keterangan' => $v['ket'],
                    'aktif' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Pastikan tarif aktif awal tersedia di tarif_pekerjaan
            $existingTarif = DB::table('tarif_pekerjaan')
                ->where('jenis_pekerjaan_id', $jenisId)
                ->where('berlaku_mulai', '2026-01-01')
                ->first();

            if ($existingTarif) {
                DB::table('tarif_pekerjaan')->where('id', $existingTarif->id)->update([
                    'nilai_tarif' => $v['tarif'],
                    'keterangan' => 'Tarif resmi ' . $v['nama'] . ' (Rp ' . number_format($v['tarif'], 0, ',', '.') . ' / Ha)',
                    'dibuat_oleh' => $adminId,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('tarif_pekerjaan')->insert([
                    'jenis_pekerjaan_id' => $jenisId,
                    'nilai_tarif' => $v['tarif'],
                    'berlaku_mulai' => '2026-01-01',
                    'berlaku_sampai' => null,
                    'keterangan' => 'Tarif resmi ' . $v['nama'] . ' (Rp ' . number_format($v['tarif'], 0, ',', '.') . ' / Ha)',
                    'dibuat_oleh' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jenis_pekerjaan') && Schema::hasColumn('jenis_pekerjaan', 'tipe')) {
            Schema::table('jenis_pekerjaan', function (Blueprint $table) {
                $table->dropColumn('tipe');
            });
        }
    }
};

