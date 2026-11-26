<?php

namespace Database\Seeders;

use App\Models\AnggotaTim;
use App\Models\DetailRealisasi;
use App\Models\DetailSpk;
use App\Models\Distrik;
use App\Models\Drone;
use App\Models\JenisBbm;
use App\Models\JenisPekerjaan;
use App\Models\KasbonPersonil;
use App\Models\LaporanRealisasi;
use App\Models\PengeluaranOperasionalUnit;
use App\Models\Pengguna;
use App\Models\Petak;
use App\Models\Pt;
use App\Models\Spk;
use App\Models\TarifPekerjaan;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OperasionalRealistisSeeder extends Seeder
{
    /**
     * Seeder data operasional realistis PANTRACK berdasarkan data riil lapangan:
     * - Drone 951 (T70P, Periode Juli 2026, Distrik Sungai Riding / DSR - PT SBA, Pilot Heru Prasetiyo, Co-Pilot Santo, Helper Shobirin/Raka/Nasir)
     * - Drone 701 (T50, Periode Agustus 2026, Distrik Sebangun Kranji / DSK - PT BMH, SPREADING BAP Excel)
     * - Master Petak, 10+ SPK resmi, 10 Laporan Realisasi, 24+ Transaksi BBM, 10 Kasbon, dan 10 Biaya Operasional Unit.
     */
    public function run(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->first();
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $peranPekerjaId = $pekerja?->peran_id ?? 2;

        if (! $admin || ! $kepala) {
            return;
        }

        // 1. Dapatkan / Buat Jenis Pekerjaan Pokok & Add-on
        $spraying = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPRAYING'],
            ['nama_pekerjaan' => 'Penyemprotan', 'tipe' => JenisPekerjaan::TIPE_UTAMA, 'keterangan' => 'Penyemprotan herbisida/pestisida drone', 'aktif' => true]
        );
        $spreading = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPREADING'],
            ['nama_pekerjaan' => 'Penebaran', 'tipe' => JenisPekerjaan::TIPE_UTAMA, 'keterangan' => 'Penebaran pupuk/benih butir drone', 'aktif' => true]
        );

        $spyHpt = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPY-HPT'],
            ['nama_pekerjaan' => 'Spraying - HPT', 'tipe' => JenisPekerjaan::TIPE_UTAMA, 'keterangan' => 'Penyemprotan hama & penyakit tanaman (HPT)', 'aktif' => true]
        );
        $spyCw1 = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPY-HPT-CW1'],
            ['nama_pekerjaan' => 'Spraying - HPT + CW 1', 'tipe' => JenisPekerjaan::TIPE_UTAMA, 'keterangan' => 'Penyemprotan HPT + Chemical Weeding tahap 1', 'aktif' => true]
        );
        $spyCw2 = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPY-HPT2-CW2'],
            ['nama_pekerjaan' => 'Spraying - HPT 2 + CW 2', 'tipe' => JenisPekerjaan::TIPE_UTAMA, 'keterangan' => 'Penyemprotan HPT 2 + Chemical Weeding tahap 2', 'aktif' => true]
        );
        $spdPs1 = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPD-PS1'],
            ['nama_pekerjaan' => 'Spreading - PS1 (Standar)', 'tipe' => JenisPekerjaan::TIPE_UTAMA, 'keterangan' => 'Penebaran pupuk solid 1 lahan mineral/standar', 'aktif' => true]
        );
        $spdPs1Mc = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPD-PS1-MC'],
            ['nama_pekerjaan' => 'Spreading - PS1 Marine Clay', 'tipe' => JenisPekerjaan::TIPE_UTAMA, 'keterangan' => 'Penebaran pupuk solid 1 lahan tanah liat laut / basah', 'aktif' => true]
        );
        $srvMapping = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SRV-MAPPING'],
            ['nama_pekerjaan' => 'Jasa Mapping Petak Drone', 'tipe' => JenisPekerjaan::TIPE_ADDON, 'keterangan' => 'Layanan pemetaan koordinat petak & pembuatan misi jalur terbang drone', 'aktif' => true]
        );

        // 2. Dapatkan / Buat Jenis BBM
        $pertamax = JenisBbm::firstOrCreate(
            ['nama_bbm' => 'Pertamax'],
            ['satuan' => 'liter', 'aktif' => true]
        );

        // 3. Pastikan Tarif Pekerjaan Standar
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spreading->id, 'berlaku_mulai' => '2026-01-01'],
            ['nilai_tarif' => 340000.00, 'keterangan' => 'Tarif resmi Spreading per hektar (Rp 340.000 / Ha)', 'dibuat_oleh' => $admin->id]
        );
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spraying->id, 'berlaku_mulai' => '2026-01-01'],
            ['nilai_tarif' => 175000.00, 'keterangan' => 'Tarif resmi Spraying per hektar (Rp 175.000 / Ha)', 'dibuat_oleh' => $admin->id]
        );
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spyHpt->id, 'berlaku_mulai' => '2026-01-01'],
            ['nilai_tarif' => 239500.00, 'keterangan' => 'Tarif resmi Spraying - HPT (Rp 239.500 / Ha)', 'dibuat_oleh' => $admin->id]
        );
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spyCw1->id, 'berlaku_mulai' => '2026-01-01'],
            ['nilai_tarif' => 239500.00, 'keterangan' => 'Tarif resmi Spraying - HPT + CW 1 (Rp 239.500 / Ha)', 'dibuat_oleh' => $admin->id]
        );
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spyCw2->id, 'berlaku_mulai' => '2026-01-01'],
            ['nilai_tarif' => 239500.00, 'keterangan' => 'Tarif resmi Spraying - HPT 2 + CW 2 (Rp 239.500 / Ha)', 'dibuat_oleh' => $admin->id]
        );
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spdPs1->id, 'berlaku_mulai' => '2026-01-01'],
            ['nilai_tarif' => 300000.00, 'keterangan' => 'Tarif resmi Spreading - PS1 Standar (Rp 300.000 / Ha)', 'dibuat_oleh' => $admin->id]
        );
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spdPs1Mc->id, 'berlaku_mulai' => '2026-01-01'],
            ['nilai_tarif' => 340000.00, 'keterangan' => 'Tarif resmi Spreading - PS1 Marine Clay (Rp 340.000 / Ha)', 'dibuat_oleh' => $admin->id]
        );
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $srvMapping->id, 'berlaku_mulai' => '2026-01-01'],
            ['nilai_tarif' => 30000.00, 'keterangan' => 'Tarif resmi Jasa Mapping Petak Drone (Rp 30.000 / Ha)', 'dibuat_oleh' => $admin->id]
        );

        // Arsip Riwayat Tarif Sebelumnya (Data historis tahun 2025 sebelum penyesuaian tarif 2026)
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spdPs1->id, 'berlaku_mulai' => '2025-01-01'],
            [
                'berlaku_sampai' => '2025-12-31',
                'nilai_tarif' => 280000.00,
                'keterangan' => 'Arsip: Tarif Spreading PS1 tahun 2025 (Sebelum penyesuaian tarif 2026)',
                'dibuat_oleh' => $admin->id,
            ]
        );
        TarifPekerjaan::updateOrCreate(
            ['jenis_pekerjaan_id' => $spyHpt->id, 'berlaku_mulai' => '2025-01-01'],
            [
                'berlaku_sampai' => '2025-12-31',
                'nilai_tarif' => 220000.00,
                'keterangan' => 'Arsip: Tarif Spraying HPT tahun 2025 (Sebelum penyesuaian tarif 2026)',
                'dibuat_oleh' => $admin->id,
            ]
        );

        // 4. Distrik Penting
        $ptSba = Pt::where('kode_pt', 'SBA')->first();
        $ptBmh = Pt::where('kode_pt', 'BMH')->first();

        $distrikDsr = Distrik::where('kode_distrik', 'DSR')->orWhere('nama_distrik', 'like', '%DSR%')->first()
            ?? Distrik::create(['nama_distrik' => 'DSR - DISTRIK SUNGAI RIDING', 'kode_distrik' => 'DSR', 'pt_id' => $ptSba?->id, 'keterangan' => 'DISTRIK SUNGAI RIDING', 'aktif' => true]);

        $distrikDsk = Distrik::where('kode_distrik', 'DSK')->orWhere('nama_distrik', 'like', '%DSK%')->first()
            ?? Distrik::create(['nama_distrik' => 'DSK - DISTRIK SUNGAI KETUPAK', 'kode_distrik' => 'DSK', 'pt_id' => $ptBmh?->id, 'keterangan' => 'DISTRIK SUNGAI KETUPAK', 'aktif' => true]);

        $distrikDst = Distrik::where('kode_distrik', 'DST')->orWhere('nama_distrik', 'like', '%DST%')->first();
        $distrikDsb = Distrik::where('kode_distrik', 'DSB')->orWhere('nama_distrik', 'like', '%DSB%')->first();
        $distrikDsj = Distrik::where('kode_distrik', 'DSJ')->orWhere('nama_distrik', 'like', '%DSJ%')->first();
        $distrikDtj = Distrik::where('kode_distrik', 'DTJ')->orWhere('nama_distrik', 'like', '%DTJ%')->first();
        $distrikDlh = Distrik::where('kode_distrik', 'DLH')->orWhere('nama_distrik', 'like', '%DLH%')->first();
        $distrikDtp = Distrik::where('kode_distrik', 'DTP')->orWhere('nama_distrik', 'like', '%DTP%')->first();

        // 5. Master Petak Realistis Distrik DSR (Sungai Riding - 24 Petak Sesuai Laporan Harian)
        $petakDsrData = [
            ['kode_petak' => 'SRA5150', 'luas' => 22.10, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRA8090', 'luas' => 15.10, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRB5261', 'luas' => 12.30, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRA5100', 'luas' => 9.70, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRA5120', 'luas' => 15.40, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6240', 'luas' => 11.00, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6200', 'luas' => 23.90, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRA5030', 'luas' => 17.20, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6210', 'luas' => 12.60, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRC6050', 'luas' => 20.30, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRB6160', 'luas' => 12.90, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6151', 'luas' => 11.40, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6271', 'luas' => 11.30, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6281', 'luas' => 11.10, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6270', 'luas' => 12.80, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRI5093', 'luas' => 5.00, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRE8091', 'luas' => 8.30, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB7020', 'luas' => 11.90, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRB6230', 'luas' => 23.20, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRA6070', 'luas' => 10.00, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6170', 'luas' => 24.40, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRA5040', 'luas' => 17.50, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRA5010', 'luas' => 21.10, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB5120', 'luas' => 27.00, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRB8030', 'luas' => 11.30, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRA5130', 'luas' => 12.30, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB8010', 'luas' => 22.00, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRB5190', 'luas' => 23.70, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB5230', 'luas' => 24.00, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'SRA6120', 'luas' => 23.90, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB7140', 'luas' => 29.20, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRB6221', 'luas' => 11.30, 'jenis' => 'Akasia'],
            ['kode_petak' => 'SRA5070', 'luas' => 22.30, 'jenis' => 'Eukaliptus'],
        ];

        foreach ($petakDsrData as $p) {
            Petak::firstOrCreate(
                ['distrik_id' => $distrikDsr->id, 'kode_petak' => $p['kode_petak']],
                [
                    'nama_petak' => 'Petak ' . $p['kode_petak'] . ' DSR',
                    'jenis_lahan' => $p['jenis'],
                    'luas_petak_ha' => $p['luas'],
                    'status_petak' => 'aktif',
                ]
            );
        }

        // 6. Master Petak Realistis Distrik DSK (Sebangun Kranji - 12 Petak Sesuai BAP Excel Drone 701)
        $petakDskData = [
            ['kode_petak' => 'TKE1170', 'luas' => 21.30, 'jenis' => 'Akasia'],
            ['kode_petak' => 'TKE3110', 'luas' => 21.90, 'jenis' => 'Akasia'],
            ['kode_petak' => 'TKE1150', 'luas' => 16.80, 'jenis' => 'Akasia'],
            ['kode_petak' => 'TKB2090', 'luas' => 20.90, 'jenis' => 'Akasia'],
            ['kode_petak' => 'TKF1190', 'luas' => 24.50, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'TKF1180', 'luas' => 23.30, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'TKF2230', 'luas' => 23.50, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'TKE4110', 'luas' => 21.20, 'jenis' => 'Akasia'],
            ['kode_petak' => 'TKC1050', 'luas' => 20.60, 'jenis' => 'Akasia'],
            ['kode_petak' => 'TKF2110', 'luas' => 20.90, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'TKF5160', 'luas' => 25.10, 'jenis' => 'Eukaliptus'],
            ['kode_petak' => 'TKF5180', 'luas' => 8.00, 'jenis' => 'Eukaliptus'],
        ];

        foreach ($petakDskData as $p) {
            Petak::firstOrCreate(
                ['distrik_id' => $distrikDsk->id, 'kode_petak' => $p['kode_petak']],
                [
                    'nama_petak' => 'Petak ' . $p['kode_petak'] . ' DSK',
                    'jenis_lahan' => $p['jenis'],
                    'luas_petak_ha' => $p['luas'],
                    'status_petak' => 'aktif',
                ]
            );
        }

        // 7. Pengguna Pilot Resmi (Heru Prasetiyo, Budi Santoso, Aditya, dll.)
        $pilotHeru = Pengguna::firstOrCreate(
            ['nama_pengguna' => 'heru'],
            [
                'peran_id' => $peranPekerjaId,
                'nama_lengkap' => 'Heru Prasetiyo (Pilot)',
                'email' => 'heru@pantrack.local',
                'kata_sandi' => Hash::make('password'),
                'nomor_telepon' => '081234567810',
                'aktif' => true,
            ]
        );

        $pilotBudi = Pengguna::where('nama_pengguna', 'pekerja')->first();

        // 8. Tim Drone 951 & Tim Drone 701
        $drone951 = Drone::where('nomor_drone', '951')->first();
        $drone701 = Drone::where('nomor_drone', '701')->first();

        $tim951 = null;
        if ($drone951) {
            $tim951 = Tim::firstOrCreate(
                ['drone_id' => $drone951->id],
                [
                    'nama_tim' => 'Tim DSR - Drone 951 (Heru)',
                    'distrik_id' => $distrikDsr->id,
                    'kepala_tim_id' => $pilotHeru->id,
                    'tanggal_mulai' => '2026-01-01',
                    'status_tim' => Tim::STATUS_AKTIF,
                ]
            );

            AnggotaTim::firstOrCreate(
                ['tim_id' => $tim951->id, 'jabatan_tim' => AnggotaTim::PILOT],
                ['pengguna_id' => $pilotHeru->id, 'nama_anggota' => 'Heru Prasetiyo', 'tanggal_mulai' => '2026-01-01']
            );

            AnggotaTim::firstOrCreate(
                ['tim_id' => $tim951->id, 'jabatan_tim' => AnggotaTim::CO_PILOT],
                ['pengguna_id' => null, 'nama_anggota' => 'Santo', 'tanggal_mulai' => '2026-01-01']
            );
        }

        $tim701 = null;
        if ($drone701) {
            $tim701 = Tim::firstOrCreate(
                ['drone_id' => $drone701->id],
                [
                    'nama_tim' => 'Tim DSK - Drone 701 (Budi)',
                    'distrik_id' => $distrikDsk->id,
                    'kepala_tim_id' => $pilotBudi?->id,
                    'tanggal_mulai' => '2026-01-01',
                    'status_tim' => Tim::STATUS_AKTIF,
                ]
            );

            if ($pilotBudi) {
                AnggotaTim::firstOrCreate(
                    ['tim_id' => $tim701->id, 'jabatan_tim' => AnggotaTim::PILOT],
                    ['pengguna_id' => $pilotBudi->id, 'nama_anggota' => 'Budi Santoso', 'tanggal_mulai' => '2026-01-01']
                );
            }

            AnggotaTim::firstOrCreate(
                ['tim_id' => $tim701->id, 'jabatan_tim' => AnggotaTim::CO_PILOT],
                ['pengguna_id' => null, 'nama_anggota' => 'Rahmat', 'tanggal_mulai' => '2026-01-01']
            );
        }

        // 9. 12 Data SPK Riil (10 SPK Spreading DSK Sesuai BAP + 2 SPK Spraying DSR)
        $daftarSpkData = [
            ['nomor' => '5123025566', 'tgl' => '2026-08-07', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKE1170', 'luas_spk' => 21.80],
            ['nomor' => '5122938568', 'tgl' => '2026-08-08', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKE3110', 'luas_spk' => 20.90],
            ['nomor' => '5123033805', 'tgl' => '2026-08-08', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKE1150', 'luas_spk' => 16.80],
            ['nomor' => '5123029039', 'tgl' => '2026-08-09', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKB2090', 'luas_spk' => 20.90],
            ['nomor' => '5123033806', 'tgl' => '2026-08-14', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKF1190', 'luas_spk' => 24.50],
            ['nomor' => '5123029086', 'tgl' => '2026-08-15', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKF1180', 'luas_spk' => 23.30],
            ['nomor' => '5123035255', 'tgl' => '2026-08-16', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKF2230', 'luas_spk' => 23.50],
            ['nomor' => '5123035277', 'tgl' => '2026-08-17', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKE4110', 'luas_spk' => 21.20],
            ['nomor' => '5123035245', 'tgl' => '2026-08-18', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKC1050', 'luas_spk' => 20.60],
            ['nomor' => '5123035981', 'tgl' => '2026-08-19', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKF2110', 'luas_spk' => 20.90],
            ['nomor' => '5123040906', 'tgl' => '2026-08-20', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKF5160', 'luas_spk' => 25.10],
            ['nomor' => '5123040995', 'tgl' => '2026-08-20', 'distrik' => $distrikDsk, 'jenis' => $spreading, 'petak' => 'TKF5180', 'luas_spk' => 8.00],
            // 2 SPK Distrik Sungai Riding (DSR - Spraying)
            ['nomor' => '5124070001', 'tgl' => '2026-07-01', 'distrik' => $distrikDsr, 'jenis' => $spraying, 'petak' => 'SRA5150', 'luas_spk' => 150.00],
            ['nomor' => '5124070002', 'tgl' => '2026-07-15', 'distrik' => $distrikDsr, 'jenis' => $spraying, 'petak' => 'SRC6050', 'luas_spk' => 220.00],
        ];

        $spkModelMap = [];
        foreach ($daftarSpkData as $spkItem) {
            $spkRow = Spk::firstOrCreate(
                ['nomor_spk' => $spkItem['nomor']],
                [
                    'tanggal_spk' => $spkItem['tgl'],
                    'periode_bulan' => (int) date('m', strtotime($spkItem['tgl'])),
                    'periode_tahun' => (int) date('Y', strtotime($spkItem['tgl'])),
                    'distrik_id' => $spkItem['distrik']->id,
                    'jenis_pekerjaan_id' => $spkItem['jenis']->id,
                    'diinput_oleh' => $kepala->id,
                    'disetujui_oleh' => $kepala->id,
                    'tanggal_persetujuan' => $spkItem['tgl'] . ' 08:00:00',
                    'status_spk' => Spk::SELESAI,
                    'keterangan' => 'SPK Resmi Lapangan ' . $spkItem['jenis']->kode_pekerjaan . ' Petak ' . $spkItem['petak'],
                ]
            );

            $spkModelMap[$spkItem['nomor']] = $spkRow;

            $petakTarget = Petak::where('kode_petak', $spkItem['petak'])->first();
            if ($petakTarget) {
                DetailSpk::firstOrCreate(
                    ['spk_id' => $spkRow->id, 'petak_id' => $petakTarget->id],
                    [
                        'luas_target_ha' => $spkItem['luas_spk'],
                        'catatan' => 'Target SPK luasan ' . $spkItem['luas_spk'] . ' Ha',
                    ]
                );
            }
        }

        // 10. LAPORAN REALISASI 1: Drone 951 (T70P, Periode Juli 2026, Distrik Sungai Riding)
        // Helper warga lokal: Shobirin, Raka, Nasir
        if ($tim951) {
            $lap951 = LaporanRealisasi::firstOrCreate(
                ['nomor_laporan' => 'REAL/DSR/2026/07/951'],
                [
                    'tim_id' => $tim951->id,
                    'nama_tim_manual' => 'Tim DSR - Drone 951',
                    'nama_pilot' => 'Heru Prasetiyo',
                    'nama_copilot' => 'Santo',
                    'nama_helper' => 'Shobirin, Raka, Nasir',
                    'spk_id' => $spkModelMap['5124070001']->id ?? null,
                    'periode_bulan' => 7,
                    'periode_tahun' => 2026,
                    'distrik_id' => $distrikDsr->id,
                    'diinput_oleh' => $pilotHeru->id,
                    'diperiksa_oleh' => $kepala->id,
                    'tanggal_pemeriksaan' => '2026-07-31 17:00:00',
                    'status_laporan' => LaporanRealisasi::DISETUJUI,
                    'catatan_pemeriksaan' => 'Pencapaian Juli MTD 367.5 Ha disetujui.',
                ]
            );

            // Detail Realisasi Drone 951 Sesuai Teks Riil Lapangan
            $logHarian951 = [
                ['tgl' => '2026-07-04', 'petak' => 'SRA5150', 'luas' => 22.10, 'status' => 'selesai', 'ket' => 'HPT+CW1 CLOSING'],
                ['tgl' => '2026-07-05', 'petak' => null, 'luas' => 0.00, 'status' => 'terkendala', 'ket' => 'Ketek Rusak'],
                ['tgl' => '2026-07-06', 'petak' => null, 'luas' => 0.00, 'status' => 'terkendala', 'ket' => 'Ketek Rusak'],
                ['tgl' => '2026-07-07', 'petak' => 'SRA8090', 'luas' => 15.10, 'status' => 'selesai', 'ket' => 'PPS PRE CLOSING'],
                ['tgl' => '2026-07-07', 'petak' => 'SRB5261', 'luas' => 12.30, 'status' => 'selesai', 'ket' => 'PPS PRE CLOSING'],
                ['tgl' => '2026-07-07', 'petak' => 'SRA5100', 'luas' => 9.70, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-08', 'petak' => 'SRA5120', 'luas' => 15.40, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-09', 'petak' => 'SRB6240', 'luas' => 11.00, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-10', 'petak' => 'SRB6200', 'luas' => 23.90, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-11', 'petak' => 'SRA5030', 'luas' => 17.20, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-12', 'petak' => 'SRB6210', 'luas' => 12.60, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-13', 'petak' => 'SRC6050', 'luas' => 20.30, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-14', 'petak' => 'SRB6160', 'luas' => 12.90, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-15', 'petak' => null, 'luas' => 0.00, 'status' => 'menunggu_mapping', 'ket' => 'Menunggu Mapping'],
                ['tgl' => '2026-07-16', 'petak' => null, 'luas' => 0.00, 'status' => 'menunggu_mapping', 'ket' => 'Menunggu Mapping'],
                ['tgl' => '2026-07-17', 'petak' => null, 'luas' => 0.00, 'status' => 'menunggu_mapping', 'ket' => 'Menunggu Mapping'],
                ['tgl' => '2026-07-18', 'petak' => 'SRB6151', 'luas' => 11.40, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-19', 'petak' => null, 'luas' => 0.00, 'status' => 'proses_mapping', 'ket' => 'Mapping Petak Lapangan'],
                ['tgl' => '2026-07-20', 'petak' => 'SRB6271', 'luas' => 11.30, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-21', 'petak' => 'SRB6281', 'luas' => 11.10, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-21', 'petak' => 'SRB6270', 'luas' => 12.80, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-22', 'petak' => null, 'luas' => 0.00, 'status' => 'menunggu_flight_mission', 'ket' => 'Menunggu Flight mission'],
                ['tgl' => '2026-07-23', 'petak' => 'SRI5093', 'luas' => 5.00, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-23', 'petak' => 'SRE8091', 'luas' => 8.30, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-24', 'petak' => 'SRB7020', 'luas' => 11.90, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-25', 'petak' => 'SRB6230', 'luas' => 23.20, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-25', 'petak' => 'SRA6070', 'luas' => 10.00, 'status' => 'dalam_proses', 'ket' => 'PS1 PROGRES'],
                ['tgl' => '2026-07-26', 'petak' => null, 'luas' => 0.00, 'status' => 'terkendala', 'ket' => 'MRS Belom Jadi'],
                ['tgl' => '2026-07-27', 'petak' => 'SRB6170', 'luas' => 24.40, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-28', 'petak' => 'SRA5040', 'luas' => 17.50, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-28', 'petak' => 'SRA5010', 'luas' => 21.10, 'status' => 'selesai', 'ket' => 'PS1 CLOSING'],
                ['tgl' => '2026-07-30', 'petak' => 'SRB5120', 'luas' => 27.00, 'status' => 'dalam_proses', 'ket' => 'PS1 PROGRES'],
            ];

            foreach ($logHarian951 as $d) {
                $petakObj = $d['petak'] ? Petak::where('kode_petak', $d['petak'])->first() : null;
                DetailRealisasi::firstOrCreate(
                    [
                        'laporan_realisasi_id' => $lap951->id,
                        'tanggal_pekerjaan' => $d['tgl'],
                        'kode_petak_manual' => $d['petak'],
                    ],
                    [
                        'petak_id' => $petakObj?->id,
                        'jenis_pekerjaan_id' => $spraying->id,
                        'luas_realisasi_ha' => $d['luas'],
                        'status_pekerjaan' => $d['status'],
                        'keterangan_kendala' => $d['ket'],
                        'tarif_per_ha' => 175000.00,
                    ]
                );
            }
        }

        // 11. LAPORAN REALISASI 2: Drone 701 (T50, Periode Agustus 2026, Distrik Sebangun Kranji / DSK)
        // Sesuai Rekap BAP Excel: 12 pengerjaan, total 248.00 Ha, nilai Rp 84.150.000
        if ($tim701) {
            $lap701 = LaporanRealisasi::firstOrCreate(
                ['nomor_laporan' => 'REAL/DSK/2026/08/701'],
                [
                    'tim_id' => $tim701->id,
                    'nama_tim_manual' => 'Tim DSK - Drone 701',
                    'nama_pilot' => 'Budi Santoso',
                    'nama_copilot' => 'Rahmat',
                    'nama_helper' => 'Shobirin, Samsul',
                    'spk_id' => $spkModelMap['5123025566']->id ?? null,
                    'periode_bulan' => 8,
                    'periode_tahun' => 2026,
                    'distrik_id' => $distrikDsk->id,
                    'diinput_oleh' => $pekerja?->id ?? $admin->id,
                    'diperiksa_oleh' => $kepala->id,
                    'tanggal_pemeriksaan' => '2026-08-24 16:30:00',
                    'status_laporan' => LaporanRealisasi::DISETUJUI,
                    'catatan_pemeriksaan' => 'Rekap BAP Spreading 248.00 Ha disetujui 24/08/2026.',
                ]
            );

            $logBap701 = [
                ['tgl' => '2026-08-07', 'petak' => 'TKE1170', 'luas' => 21.30, 'spk' => '5123025566'],
                ['tgl' => '2026-08-08', 'petak' => 'TKE3110', 'luas' => 21.90, 'spk' => '5122938568'],
                ['tgl' => '2026-08-08', 'petak' => 'TKE1150', 'luas' => 16.80, 'spk' => '5123033805'],
                ['tgl' => '2026-08-09', 'petak' => 'TKB2090', 'luas' => 20.90, 'spk' => '5123029039'],
                ['tgl' => '2026-08-14', 'petak' => 'TKF1190', 'luas' => 24.50, 'spk' => '5123033806'],
                ['tgl' => '2026-08-15', 'petak' => 'TKF1180', 'luas' => 23.30, 'spk' => '5123029086'],
                ['tgl' => '2026-08-16', 'petak' => 'TKF2230', 'luas' => 23.50, 'spk' => '5123035255'],
                ['tgl' => '2026-08-17', 'petak' => 'TKE4110', 'luas' => 21.20, 'spk' => '5123035277'],
                ['tgl' => '2026-08-18', 'petak' => 'TKC1050', 'luas' => 20.60, 'spk' => '5123035245'],
                ['tgl' => '2026-08-19', 'petak' => 'TKF2110', 'luas' => 20.90, 'spk' => '5123035981'],
                ['tgl' => '2026-08-20', 'petak' => 'TKF5160', 'luas' => 25.10, 'spk' => '5123040906'],
                ['tgl' => '2026-08-20', 'petak' => 'TKF5180', 'luas' => 8.00, 'spk' => '5123040995'],
            ];

            foreach ($logBap701 as $bap) {
                $petakObj = Petak::where('kode_petak', $bap['petak'])->first();
                DetailRealisasi::firstOrCreate(
                    [
                        'laporan_realisasi_id' => $lap701->id,
                        'tanggal_pekerjaan' => $bap['tgl'],
                        'kode_petak_manual' => $bap['petak'],
                    ],
                    [
                        'petak_id' => $petakObj?->id,
                        'jenis_pekerjaan_id' => $spreading->id,
                        'luas_realisasi_ha' => $bap['luas'],
                        'status_pekerjaan' => 'selesai',
                        'keterangan_kendala' => 'SPREADING SPK ' . $bap['spk'] . ' CLOSING',
                        'tarif_per_ha' => 340000.00,
                    ]
                );
            }
        }

        // 12. LAPORAN REALISASI 3 s.d. 10 (Tim Lainnya untuk melengkapi 10 Laporan)
        $laporanTimLain = [
            ['drone' => '601', 'distrik' => $distrikDst, 'pilot' => 'Aditya', 'co' => 'Diwang', 'helper' => 'Roni, Sugeng', 'bulan' => 8, 'status' => LaporanRealisasi::DISETUJUI],
            ['drone' => '806', 'distrik' => $distrikDst, 'pilot' => 'Egi', 'co' => 'Galang', 'helper' => 'Maman', 'bulan' => 8, 'status' => LaporanRealisasi::DISETUJUI],
            ['drone' => '706', 'distrik' => $distrikDsb, 'pilot' => 'Adit', 'co' => 'Rido', 'helper' => 'Yanto, Agus', 'bulan' => 8, 'status' => LaporanRealisasi::MENUNGGU_PEMERIKSAAN],
            ['drone' => '402', 'distrik' => $distrikDsj, 'pilot' => 'Zidane', 'co' => null, 'helper' => 'Heri', 'bulan' => 8, 'status' => LaporanRealisasi::MENUNGGU_PEMERIKSAAN],
            ['drone' => '711', 'distrik' => $distrikDtj, 'pilot' => 'Jeksen', 'co' => null, 'helper' => 'Budi, Dani', 'bulan' => 8, 'status' => LaporanRealisasi::DISETUJUI],
            ['drone' => '605', 'distrik' => $distrikDlh, 'pilot' => 'Rico', 'co' => 'Teguh', 'helper' => 'Wawan', 'bulan' => 8, 'status' => LaporanRealisasi::DISETUJUI],
            ['drone' => '707', 'distrik' => $distrikDtp, 'pilot' => 'Itsna', 'co' => null, 'helper' => 'Dedi, Slamet', 'bulan' => 8, 'status' => LaporanRealisasi::DRAF],
            ['drone' => '703', 'distrik' => $distrikDsr, 'pilot' => 'Arel', 'co' => null, 'helper' => 'Samsul, Bpk Samsul', 'bulan' => 7, 'status' => LaporanRealisasi::DISETUJUI],
        ];

        foreach ($laporanTimLain as $lt) {
            $droneObj = Drone::where('nomor_drone', $lt['drone'])->first();
            if (! $droneObj) {
                continue;
            }
            $timObj = Tim::where('drone_id', $droneObj->id)->first();
            if (! $timObj) {
                continue;
            }

            $lapTim = LaporanRealisasi::firstOrCreate(
                ['nomor_laporan' => 'REAL/' . ($lt['distrik']->nama_distrik ?? 'DIST') . '/2026/08/' . $lt['drone']],
                [
                    'tim_id' => $timObj->id,
                    'nama_tim_manual' => $timObj->nama_tim,
                    'nama_pilot' => $lt['pilot'],
                    'nama_copilot' => $lt['co'],
                    'nama_helper' => $lt['helper'],
                    'periode_bulan' => $lt['bulan'],
                    'periode_tahun' => 2026,
                    'distrik_id' => $lt['distrik']->id ?? $distrikDsr->id,
                    'diinput_oleh' => $timObj->kepala_tim_id ?? $admin->id,
                    'diperiksa_oleh' => $kepala->id,
                    'tanggal_pemeriksaan' => $lt['status'] === LaporanRealisasi::DISETUJUI ? '2026-08-25 15:00:00' : null,
                    'status_laporan' => $lt['status'],
                    'catatan_pemeriksaan' => $lt['status'] === LaporanRealisasi::DISETUJUI ? 'Disetujui lengkap.' : null,
                ]
            );

            DetailRealisasi::firstOrCreate(
                [
                    'laporan_realisasi_id' => $lapTim->id,
                    'tanggal_pekerjaan' => '2026-08-10',
                    'kode_petak_manual' => 'PETAK-' . $lt['drone'] . '-01',
                ],
                [
                    'jenis_pekerjaan_id' => $spraying->id,
                    'luas_realisasi_ha' => 25.00,
                    'status_pekerjaan' => 'selesai',
                    'keterangan_kendala' => 'Operasional rutin semprot herbisida',
                    'tarif_per_ha' => 175000.00,
                ]
            );
        }

        // 13. TRANSAKSI BBM REALISTIS UNIT 951 (Persis Teks Laporan WhatsApp Lapangan)
        // Stok Masuk = 640 L (20L Stok Awal + 320L + 300L)
        // Penggunaan Mobil = 90 L (20L + 20L + 15L + 35L)
        // Penggunaan Genset = 315 L (17 transaksi genset)
        // Total Terpakai = 405 L
        // Sisa Saldo Tersedia = 215 L
        if ($tim951) {
            $transaksiBbm951 = [
                // Stok Masuk
                ['no' => 'TX-BBM/2026/07/001', 'tgl' => '2026-07-01', 'jenis' => TransaksiBbm::STOK_AWAL, 'liter' => 20.00, 'kep' => 'stok_masuk', 'ket' => 'Stok Awal BBM Pertamax (Sisa Juni)'],
                ['no' => 'TX-BBM/2026/07/002', 'tgl' => '2026-07-02', 'jenis' => TransaksiBbm::PENERIMAAN, 'liter' => 320.00, 'kep' => 'stok_masuk', 'ket' => 'Drop Logistik BBM Pertamax Gelombang 1'],
                ['no' => 'TX-BBM/2026/07/003', 'tgl' => '2026-07-18', 'jenis' => TransaksiBbm::PENERIMAAN, 'liter' => 300.00, 'kep' => 'stok_masuk', 'ket' => 'Drop Logistik BBM Pertamax Gelombang 2'],

                // Penggunaan Mobil (Total 90 L)
                ['no' => 'TX-BBM/2026/07/004', 'tgl' => '2026-07-02', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 20.00, 'kep' => 'mobil', 'ket' => 'Operasional Mobil Pelangsir Sisa Juni'],
                ['no' => 'TX-BBM/2026/07/005', 'tgl' => '2026-07-16', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 20.00, 'kep' => 'mobil', 'ket' => 'Operasional Mobil Angkut Bahan & Personil'],
                ['no' => 'TX-BBM/2026/07/006', 'tgl' => '2026-07-19', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 15.00, 'kep' => 'mobil', 'ket' => 'Operasional Mobil Mapping Petak Lapangan'],
                ['no' => 'TX-BBM/2026/07/007', 'tgl' => '2026-07-29', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 35.00, 'kep' => 'mobil', 'ket' => 'Operasional Mobil Pelangsir Petak Jauh'],

                // Penggunaan Genset (Total 315 L)
                ['no' => 'TX-BBM/2026/07/008', 'tgl' => '2026-07-04', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 10.00, 'kep' => 'genset', 'ket' => 'Genset Charger Baterai Petak SRA5150'],
                ['no' => 'TX-BBM/2026/07/009', 'tgl' => '2026-07-07', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 30.00, 'kep' => 'genset', 'ket' => 'Genset Charger Baterai 3 Petak Sekaligus'],
                ['no' => 'TX-BBM/2026/07/010', 'tgl' => '2026-07-08', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 20.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRA5120'],
                ['no' => 'TX-BBM/2026/07/011', 'tgl' => '2026-07-09', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 10.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6240'],
                ['no' => 'TX-BBM/2026/07/012', 'tgl' => '2026-07-10', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 20.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6200'],
                ['no' => 'TX-BBM/2026/07/013', 'tgl' => '2026-07-11', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 20.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRA5030'],
                ['no' => 'TX-BBM/2026/07/014', 'tgl' => '2026-07-12', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 10.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6210'],
                ['no' => 'TX-BBM/2026/07/015', 'tgl' => '2026-07-13', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 20.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRC6050'],
                ['no' => 'TX-BBM/2026/07/016', 'tgl' => '2026-07-14', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 10.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6160'],
                ['no' => 'TX-BBM/2026/07/017', 'tgl' => '2026-07-18', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 10.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6151'],
                ['no' => 'TX-BBM/2026/07/018', 'tgl' => '2026-07-20', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 10.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6271'],
                ['no' => 'TX-BBM/2026/07/019', 'tgl' => '2026-07-21', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 20.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6281 & SRB6270'],
                ['no' => 'TX-BBM/2026/07/020', 'tgl' => '2026-07-23', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 20.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRI5093 & SRE8091'],
                ['no' => 'TX-BBM/2026/07/021', 'tgl' => '2026-07-25', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 35.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6230 & SRA6070'],
                ['no' => 'TX-BBM/2026/07/022', 'tgl' => '2026-07-27', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 30.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB6170'],
                ['no' => 'TX-BBM/2026/07/023', 'tgl' => '2026-07-28', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 35.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRA5040 & SRA5010'],
                ['no' => 'TX-BBM/2026/07/024', 'tgl' => '2026-07-30', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 25.00, 'kep' => 'genset', 'ket' => 'Genset Charger Petak SRB5120'],
            ];

            foreach ($transaksiBbm951 as $bbm) {
                TransaksiBbm::firstOrCreate(
                    ['nomor_transaksi' => $bbm['no']],
                    [
                        'tim_id' => $tim951->id,
                        'jenis_bbm_id' => $pertamax->id,
                        'jenis_transaksi' => $bbm['jenis'],
                        'tanggal_transaksi' => $bbm['tgl'],
                        'jumlah_liter' => $bbm['liter'],
                        'harga_per_liter' => 12500.00,
                        'total_biaya' => $bbm['liter'] * 12500.00,
                        'keperluan' => $bbm['kep'],
                        'keterangan' => $bbm['ket'],
                        'status_verifikasi' => TransaksiBbm::TERVERIFIKASI,
                        'diinput_oleh' => $pilotHeru->id,
                        'diperiksa_oleh' => $admin->id,
                        'tanggal_pemeriksaan' => $bbm['tgl'] . ' 18:00:00',
                        'catatan_pemeriksaan' => 'Terverifikasi sesuai nota & rekonsiliasi drum.',
                    ]
                );
            }
        }

        // 14. TRANSAKSI BBM UNIT 701 (Agustus 2026)
        if ($tim701) {
            $transaksiBbm701 = [
                ['no' => 'TX-BBM/2026/08/701-01', 'tgl' => '2026-08-01', 'jenis' => TransaksiBbm::STOK_AWAL, 'liter' => 50.00, 'kep' => 'stok_masuk', 'ket' => 'Stok awal BBM Agustus unit 701'],
                ['no' => 'TX-BBM/2026/08/701-02', 'tgl' => '2026-08-05', 'jenis' => TransaksiBbm::PENERIMAAN, 'liter' => 400.00, 'kep' => 'stok_masuk', 'ket' => 'Drop 2 drum Pertamax dari workshop BMH'],
                ['no' => 'TX-BBM/2026/08/701-03', 'tgl' => '2026-08-07', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 35.00, 'kep' => 'genset', 'ket' => 'Penggunaan genset spreading TKE1170'],
                ['no' => 'TX-BBM/2026/08/701-04', 'tgl' => '2026-08-08', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 40.00, 'kep' => 'genset', 'ket' => 'Penggunaan genset spreading TKE3110 & TKE1150'],
                ['no' => 'TX-BBM/2026/08/701-05', 'tgl' => '2026-08-14', 'jenis' => TransaksiBbm::PENGGUNAAN, 'liter' => 30.00, 'kep' => 'mobil', 'ket' => 'Pengisian mobil pelangsir pupuk DSK'],
            ];

            foreach ($transaksiBbm701 as $bbm) {
                TransaksiBbm::firstOrCreate(
                    ['nomor_transaksi' => $bbm['no']],
                    [
                        'tim_id' => $tim701->id,
                        'jenis_bbm_id' => $pertamax->id,
                        'jenis_transaksi' => $bbm['jenis'],
                        'tanggal_transaksi' => $bbm['tgl'],
                        'jumlah_liter' => $bbm['liter'],
                        'harga_per_liter' => 12500.00,
                        'total_biaya' => $bbm['liter'] * 12500.00,
                        'keperluan' => $bbm['kep'],
                        'keterangan' => $bbm['ket'],
                        'status_verifikasi' => TransaksiBbm::TERVERIFIKASI,
                        'diinput_oleh' => $pekerja?->id ?? $admin->id,
                        'diperiksa_oleh' => $admin->id,
                        'tanggal_pemeriksaan' => $bbm['tgl'] . ' 18:00:00',
                        'catatan_pemeriksaan' => 'Terverifikasi.',
                    ]
                );
            }
        }

        // 15. 10 DATA KASBON PERSONIL
        $kasbonData = [
            ['tim' => $tim951, 'nama' => 'Heru Prasetiyo', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-07-03', 'bulan' => 7, 'tahun' => 2026, 'jumlah' => 1000000.00, 'ket' => 'Kasbon uang saku pilot awal bulan Juli', 'lunas' => false],
            ['tim' => $tim951, 'nama' => 'Santo', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-07-05', 'bulan' => 7, 'tahun' => 2026, 'jumlah' => 500000.00, 'ket' => 'Kasbon co-pilot keperluan keluarga', 'lunas' => false],
            ['tim' => $tim701, 'nama' => 'Budi Santoso', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-08-02', 'bulan' => 8, 'tahun' => 2026, 'jumlah' => 1500000.00, 'ket' => 'Kasbon pegangan tugas luar distrik DSK', 'lunas' => false],
            ['tim' => $tim701, 'nama' => 'Rahmat', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-08-04', 'bulan' => 8, 'tahun' => 2026, 'jumlah' => 500000.00, 'ket' => 'Kasbon co-pilot Rahmat', 'lunas' => false],
            ['tim' => $tim951, 'nama' => 'Aditya', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-08-10', 'bulan' => 8, 'tahun' => 2026, 'jumlah' => 1200000.00, 'ket' => 'Kasbon pilot operasional DST', 'lunas' => false],
            ['tim' => $tim951, 'nama' => 'Diwang', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-08-12', 'bulan' => 8, 'tahun' => 2026, 'jumlah' => 400000.00, 'ket' => 'Kasbon co-pilot Diwang', 'lunas' => false],
            ['tim' => $tim701, 'nama' => 'Egi', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-08-15', 'bulan' => 8, 'tahun' => 2026, 'jumlah' => 1000000.00, 'ket' => 'Kasbon pilot Egi (Sudah dikembalikan)', 'lunas' => true],
            ['tim' => $tim951, 'nama' => 'Rico', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-08-16', 'bulan' => 8, 'tahun' => 2026, 'jumlah' => 800000.00, 'ket' => 'Kasbon pilot Rico Distrik DLH', 'lunas' => false],
            ['tim' => $tim701, 'nama' => 'Zidane', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-08-18', 'bulan' => 8, 'tahun' => 2026, 'jumlah' => 1000000.00, 'ket' => 'Kasbon pilot Zidane Distrik DSJ', 'lunas' => false],
            ['tim' => $tim951, 'nama' => 'Arel', 'peran' => KasbonPersonil::PILOT, 'tgl' => '2026-07-20', 'bulan' => 7, 'tahun' => 2026, 'jumlah' => 750000.00, 'ket' => 'Kasbon pilot Arel Drone 703 DSR', 'lunas' => true],
        ];

        foreach ($kasbonData as $kb) {
            if (! $kb['tim']) {
                continue;
            }
            KasbonPersonil::firstOrCreate(
                [
                    'tim_id' => $kb['tim']->id,
                    'nama_personil' => $kb['nama'],
                    'tanggal_kasbon' => $kb['tgl'],
                ],
                [
                    'peran_kru' => $kb['peran'],
                    'periode_bulan' => $kb['bulan'],
                    'periode_tahun' => $kb['tahun'],
                    'jumlah_kasbon' => $kb['jumlah'],
                    'keterangan' => $kb['ket'],
                    'status_lunas' => $kb['lunas'],
                    'diinput_oleh' => $admin->id,
                ]
            );
        }

        // 16. 10 DATA PENGELUARAN OPERASIONAL UNIT
        $operasionalData = [
            ['tim' => $tim951, 'tgl' => '2026-07-06', 'bulan' => 7, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::OPERASIONAL_LAPANGAN, 'nom' => 350000.00, 'ket' => 'Service perbaikan ketek (perahu kanal kebun mogok)'],
            ['tim' => $tim951, 'tgl' => '2026-07-10', 'bulan' => 7, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::UPAH_LANGSIR, 'nom' => 450000.00, 'ket' => 'Uang saku lelah 3 helper lokal (Shobirin, Raka, Nasir)'],
            ['tim' => $tim951, 'tgl' => '2026-07-15', 'bulan' => 7, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::OPERASIONAL_LAPANGAN, 'nom' => 120000.00, 'ket' => 'Oli mesin 4-tak genset charger drone'],
            ['tim' => $tim951, 'tgl' => '2026-07-18', 'bulan' => 7, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::RANSUM_KANTIN, 'nom' => 150000.00, 'ket' => 'Galon air mineral & konsumsi lapangan tim petak DSR'],
            ['tim' => $tim951, 'tgl' => '2026-07-22', 'bulan' => 7, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::OPERASIONAL_LAPANGAN, 'nom' => 85000.00, 'ket' => 'Tali tambang dan perbaikan terpal tenda baterai drone'],
            ['tim' => $tim701, 'tgl' => '2026-08-08', 'bulan' => 8, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::UPAH_LANGSIR, 'nom' => 300000.00, 'ket' => 'Uang makan & lelah helper lokal (Shobirin, Samsul)'],
            ['tim' => $tim701, 'tgl' => '2026-08-12', 'bulan' => 8, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::SPAREPART, 'nom' => 175000.00, 'ket' => 'Busi & filter udara cadangan genset spreading'],
            ['tim' => $tim701, 'tgl' => '2026-08-16', 'bulan' => 8, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::RANSUM_KANTIN, 'nom' => 350000.00, 'ket' => 'Ransum sembako beras & lauk tim kemah petak DSK'],
            ['tim' => $tim951, 'tgl' => '2026-08-19', 'bulan' => 8, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::RENTAL_MOBIL, 'nom' => 250000.00, 'ket' => 'Sewa perahu pelangsir rintis kanal penyeberangan'],
            ['tim' => $tim701, 'tgl' => '2026-08-21', 'bulan' => 8, 'tahun' => 2026, 'kat' => PengeluaranOperasionalUnit::OPERASIONAL_LAPANGAN, 'nom' => 95000.00, 'ket' => 'Selang pompa drum & corong saringan bahan bakar BBM'],
        ];

        foreach ($operasionalData as $op) {
            if (! $op['tim']) {
                continue;
            }
            PengeluaranOperasionalUnit::firstOrCreate(
                [
                    'tim_id' => $op['tim']->id,
                    'tanggal_transaksi' => $op['tgl'],
                    'keterangan' => $op['ket'],
                ],
                [
                    'periode_bulan' => $op['bulan'],
                    'periode_tahun' => $op['tahun'],
                    'kategori_biaya' => $op['kat'],
                    'nominal' => $op['nom'],
                    'nomor_bukti_pi' => 'NOTA/' . date('Ym', strtotime($op['tgl'])) . '/' . rand(100, 999),
                    'diinput_oleh' => $admin->id,
                ]
            );
        }
    }
}

