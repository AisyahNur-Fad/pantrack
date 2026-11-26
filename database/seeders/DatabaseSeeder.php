<?php

namespace Database\Seeders;

use App\Models\AnggotaTim;
use App\Models\DetailSpk;
use App\Models\Distrik;
use App\Models\Drone;
use App\Models\JenisBbm;
use App\Models\JenisPekerjaan;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Petak;
use App\Models\Pt;
use App\Models\Spk;
use App\Models\TarifPekerjaan;
use App\Models\Tim;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seeder database PANTRACK.
     * Mengisi akun pengguna awal untuk masing-masing peran dan master data operasional pendukung.
     */
    public function run(): void
    {
        // 1. Pastikan Peran Tersedia (1: admin, 2: pekerja_lapangan, 3: kepala_lapangan, 4: atasan)
        $perans = [
            Peran::ADMIN => 'Mengelola data master, memeriksa transaksi BBM, dan mengekspor laporan',
            Peran::PEKERJA_LAPANGAN => 'Mengerjakan SPK operasional, realisasi pekerjaan, dan transaksi BBM',
            Peran::KEPALA_LAPANGAN => 'Membuatkan SPK operasional dan memeriksa laporan realisasi pekerjaan',
            Peran::ATASAN => 'Memantau dashboard dan laporan operasional',
        ];

        foreach ($perans as $nama => $keterangan) {
            Peran::firstOrCreate(
                ['nama' => $nama],
                ['keterangan' => $keterangan]
            );
        }

        $peranAdmin = Peran::where('nama', Peran::ADMIN)->first();
        $peranPekerja = Peran::where('nama', Peran::PEKERJA_LAPANGAN)->first();
        $peranKepala = Peran::where('nama', Peran::KEPALA_LAPANGAN)->first();
        $peranAtasan = Peran::where('nama', Peran::ATASAN)->first();

        // 2. Akun Pengguna Awal (Password default: 'password')
        $daftarPengguna = [
            [
                'peran_id' => $peranAdmin->id,
                'nama_lengkap' => 'Administrator Sistem',
                'nama_pengguna' => 'admin',
                'email' => 'admin@pantrack.local',
                'kata_sandi' => Hash::make('password'),
                'nomor_telepon' => '081234567890',
                'aktif' => true,
            ],
            [
                'peran_id' => $peranPekerja->id,
                'nama_lengkap' => 'Budi Santoso (Pekerja)',
                'nama_pengguna' => 'pekerja',
                'email' => 'pekerja@pantrack.local',
                'kata_sandi' => Hash::make('password'),
                'nomor_telepon' => '081234567891',
                'aktif' => true,
            ],
            [
                'peran_id' => $peranKepala->id,
                'nama_lengkap' => 'Ahmad Fauzi (Kepala Lapangan)',
                'nama_pengguna' => 'kepala',
                'email' => 'kepala@pantrack.local',
                'kata_sandi' => Hash::make('password'),
                'nomor_telepon' => '081234567892',
                'aktif' => true,
            ],
            [
                'peran_id' => $peranAtasan->id,
                'nama_lengkap' => 'Ir. H. Hendra Wijaya (Atasan)',
                'nama_pengguna' => 'atasan',
                'email' => 'atasan@pantrack.local',
                'kata_sandi' => Hash::make('password'),
                'nomor_telepon' => '081234567893',
                'aktif' => true,
            ],
        ];

        foreach ($daftarPengguna as $data) {
            Pengguna::firstOrCreate(
                ['nama_pengguna' => $data['nama_pengguna']],
                $data
            );
        }

        // 3. Jenis Pekerjaan Awal (Spraying & Spreading)
        $jenisSpraying = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPRAYING'],
            [
                'nama_pekerjaan' => 'Penyemprotan',
                'keterangan' => 'Pekerjaan penyemprotan pupuk/pestisida menggunakan drone agrikultur',
                'aktif' => true,
            ]
        );

        $jenisSpreading = JenisPekerjaan::firstOrCreate(
            ['kode_pekerjaan' => 'SPREADING'],
            [
                'nama_pekerjaan' => 'Penebaran',
                'keterangan' => 'Pekerjaan penebaran benih/pupuk butir menggunakan drone agrikultur',
                'aktif' => true,
            ]
        );

        // 4. Jenis BBM Awal
        JenisBbm::firstOrCreate(
            ['nama_bbm' => 'Pertamax'],
            ['satuan' => 'liter', 'aktif' => true]
        );
        JenisBbm::firstOrCreate(
            ['nama_bbm' => 'Pertalite'],
            ['satuan' => 'liter', 'aktif' => true]
        );

        // 5. Master 3 PT & 19 Distrik Resmi Sesuai Papan Konsesi Operasional
        $ptBmh = Pt::updateOrCreate(
            ['kode_pt' => 'BMH'],
            ['nama_pt' => 'PT. BUMI MEKAR HIJAU', 'keterangan' => 'PT. BUMI MEKAR HIJAU (BMH) - 7 Distrik Operasional', 'aktif' => true]
        );
        $ptBap = Pt::updateOrCreate(
            ['kode_pt' => 'BAP'],
            ['nama_pt' => 'PT. BUMI ANDALAS PERMAI (BAP)', 'keterangan' => 'PT. BUMI ANDALAS PERMAI (BAP) - 7 Distrik Operasional', 'aktif' => true]
        );
        $ptSba = Pt::updateOrCreate(
            ['kode_pt' => 'SBA'],
            ['nama_pt' => 'PT. SEBANGUN BUMI ANDALAS (SBA)', 'keterangan' => 'PT. SEBANGUN BUMI ANDALAS (SBA) - 5 Distrik Operasional', 'aktif' => true]
        );
        $ptCbs = Pt::updateOrCreate(
            ['kode_pt' => 'CBS'],
            ['nama_pt' => 'PT CBS', 'keterangan' => 'Unit Nonaktif / Di luar 3 PT Konsesi Utama', 'aktif' => false]
        );

        $daftarDistrikPapan = [
            // PT. BUMI MEKAR HIJAU (BMH - 7 distrik)
            ['pt_id' => $ptBmh->id, 'kode' => 'DPS', 'nama' => 'DPS - DISTRIK PADANG SUGIHAN', 'ket' => 'DISTRIK PADANG SUGIHAN'],
            ['pt_id' => $ptBmh->id, 'kode' => 'DST', 'nama' => 'DST - DISTRIK SIMPANG TIGA', 'ket' => 'DISTRIK SIMPANG TIGA'],
            ['pt_id' => $ptBmh->id, 'kode' => 'DSB', 'nama' => 'DSB - DISTRIK SUNGAI BYUKU', 'ket' => 'DISTRIK SUNGAI BYUKU'],
            ['pt_id' => $ptBmh->id, 'kode' => 'DSP', 'nama' => 'DSP - DISTRIK SUNGAI PENYABUNGAN', 'ket' => 'DISTRIK SUNGAI PENYABUNGAN'],
            ['pt_id' => $ptBmh->id, 'kode' => 'DSK', 'nama' => 'DSK - DISTRIK SUNGAI KETUPAK', 'ket' => 'DISTRIK SUNGAI KETUPAK'],
            ['pt_id' => $ptBmh->id, 'kode' => 'DSG', 'nama' => 'DSG - DISTRIK SUNGAI GEBANG', 'ket' => 'DISTRIK SUNGAI GEBANG'],
            ['pt_id' => $ptBmh->id, 'kode' => 'DSS', 'nama' => 'DSS - DISTRIK SUNGAI SERDANG', 'ket' => 'DISTRIK SUNGAI SERDANG'],

            // PT. BUMI ANDALAS PERMAI (BAP - 7 distrik)
            ['pt_id' => $ptBap->id, 'kode' => 'DAS', 'nama' => 'DAS - DISTRIK AIR SUGIHAN', 'ket' => 'DISTRIK AIR SUGIHAN'],
            ['pt_id' => $ptBap->id, 'kode' => 'DSJ', 'nama' => 'DSJ - DISTRIK SUNGAI JELETUNG', 'ket' => 'DISTRIK SUNGAI JELETUNG'],
            ['pt_id' => $ptBap->id, 'kode' => 'DTJ', 'nama' => 'DTJ - DISTRIK TANJUNG JATI', 'ket' => 'DISTRIK TANJUNG JATI'],
            ['pt_id' => $ptBap->id, 'kode' => 'DTK', 'nama' => 'DTK - DISTRIK TANJUNG KAIT', 'ket' => 'DISTRIK TANJUNG KAIT'],
            ['pt_id' => $ptBap->id, 'kode' => 'DBR', 'nama' => 'DBR - DISTRIK BAGAN RAME', 'ket' => 'DISTRIK BAGAN RAME'],
            ['pt_id' => $ptBap->id, 'kode' => 'DBT', 'nama' => 'DBT - DISTRIK BAGAN TENGAH', 'ket' => 'DISTRIK BAGAN TENGAH'],
            ['pt_id' => $ptBap->id, 'kode' => 'DSH', 'nama' => 'DSH - DISTRIK SIMPANG HERAN', 'ket' => 'DISTRIK SIMPANG HERAN'],

            // PT. SEBANGUN BUMI ANDALAS (SBA - 5 distrik)
            ['pt_id' => $ptSba->id, 'kode' => 'DLH', 'nama' => 'DLH - DISTRIK LEBONG HITAM', 'ket' => 'DISTRIK LEBONG HITAM'],
            ['pt_id' => $ptSba->id, 'kode' => 'DTP', 'nama' => 'DTP - DISTRIK TELUK PULAI', 'ket' => 'DISTRIK TELUK PULAI'],
            ['pt_id' => $ptSba->id, 'kode' => 'DKL', 'nama' => 'DKL - DISTRIK KUALA LUMPUR', 'ket' => 'DISTRIK KUALA LUMPUR'],
            ['pt_id' => $ptSba->id, 'kode' => 'DSR', 'nama' => 'DSR - DISTRIK SUNGAI RIDING', 'ket' => 'DISTRIK SUNGAI RIDING'],
            ['pt_id' => $ptSba->id, 'kode' => 'DTD', 'nama' => 'DTD - DISTRIK TELUK DAUN', 'ket' => 'DISTRIK TELUK DAUN'],
        ];

        foreach ($daftarDistrikPapan as $dp) {
            $distrikRow = Distrik::where('kode_distrik', $dp['kode'])
                ->orWhere('nama_distrik', 'like', '%' . $dp['kode'] . '%')
                ->first();

            if ($distrikRow) {
                $distrikRow->update([
                    'pt_id' => $dp['pt_id'],
                    'kode_distrik' => $dp['kode'],
                    'nama_distrik' => $dp['nama'],
                    'keterangan' => $dp['ket'],
                    'aktif' => true,
                ]);
            } else {
                Distrik::create([
                    'pt_id' => $dp['pt_id'],
                    'kode_distrik' => $dp['kode'],
                    'nama_distrik' => $dp['nama'],
                    'keterangan' => $dp['ket'],
                    'aktif' => true,
                ]);
            }
        }

        $distrikDps = Distrik::where('kode_distrik', 'DPS')->orWhere('nama_distrik', 'like', '%DPS%')->first();
        $distrikDst = Distrik::where('kode_distrik', 'DST')->orWhere('nama_distrik', 'like', '%DST%')->first();

        // 6. Master Armada 33 Drone Sesuai Papan Monitoring Lapangan
        $daftar33Drone = [
            ['nomor_drone' => '903', 'nama_drone' => 'PAB-DF', 'tipe_drone' => 'T20P', 'keterangan' => null],
            ['nomor_drone' => '904', 'nama_drone' => 'PAB-DF', 'tipe_drone' => 'T25', 'keterangan' => null],
            ['nomor_drone' => '909', 'nama_drone' => 'PAB-DF', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '900', 'nama_drone' => 'PAB-DF', 'tipe_drone' => 'T70P', 'keterangan' => null],
            ['nomor_drone' => '701', 'nama_drone' => 'PAB-YD', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '704', 'nama_drone' => 'PAB-YD', 'tipe_drone' => 'T25P', 'keterangan' => null],
            ['nomor_drone' => '707', 'nama_drone' => 'PAB-YD', 'tipe_drone' => 'T70P', 'keterangan' => null],
            ['nomor_drone' => '711', 'nama_drone' => 'PAB-KNS', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '706', 'nama_drone' => 'PAB-R3P', 'tipe_drone' => 'T25P', 'keterangan' => null],
            ['nomor_drone' => '905', 'nama_drone' => 'PAB-CMC', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '605', 'nama_drone' => 'APL-ABD', 'tipe_drone' => 'T25P', 'keterangan' => null],
            ['nomor_drone' => '808', 'nama_drone' => 'PAB-DS', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '609', 'nama_drone' => 'PAB EKA', 'tipe_drone' => 'T55', 'keterangan' => null],
            ['nomor_drone' => '801', 'nama_drone' => 'PAB-RH', 'tipe_drone' => 'T20P', 'keterangan' => null],
            ['nomor_drone' => '802', 'nama_drone' => 'PAB-RH', 'tipe_drone' => 'T20P', 'keterangan' => null],
            ['nomor_drone' => '805', 'nama_drone' => 'PAB-RS', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '806', 'nama_drone' => 'PAB-RS', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '601', 'nama_drone' => 'PAB DL', 'tipe_drone' => 'T70P', 'keterangan' => null],
            ['nomor_drone' => '602', 'nama_drone' => 'PAB-WK', 'tipe_drone' => 'T25', 'keterangan' => null],
            ['nomor_drone' => '606', 'nama_drone' => 'PAB-LR', 'tipe_drone' => 'T25', 'keterangan' => null],
            ['nomor_drone' => '608', 'nama_drone' => 'PAB-GK', 'tipe_drone' => 'T25P', 'keterangan' => null],
            ['nomor_drone' => '809', 'nama_drone' => 'PAB-AYA', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '906', 'nama_drone' => 'PAB-AKA', 'tipe_drone' => 'T25P', 'keterangan' => null],
            ['nomor_drone' => '303', 'nama_drone' => 'PAB-RCM', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '302', 'nama_drone' => 'PAB-RCM', 'tipe_drone' => 'T25P', 'keterangan' => null],
            ['nomor_drone' => '301', 'nama_drone' => 'PAB-ASE', 'tipe_drone' => 'T25', 'keterangan' => null],
            ['nomor_drone' => '305', 'nama_drone' => 'PAB-ASE', 'tipe_drone' => 'T70P', 'keterangan' => null],
            ['nomor_drone' => '401', 'nama_drone' => 'ESJB', 'tipe_drone' => 'T25P', 'keterangan' => null],
            ['nomor_drone' => '402', 'nama_drone' => 'ESJB', 'tipe_drone' => 'T50', 'keterangan' => null],
            ['nomor_drone' => '403', 'nama_drone' => 'ESJB', 'tipe_drone' => 'T70P', 'keterangan' => null],
            ['nomor_drone' => '702', 'nama_drone' => 'CSP', 'tipe_drone' => 'T25', 'keterangan' => null],
            ['nomor_drone' => '703', 'nama_drone' => 'CSP', 'tipe_drone' => 'T25', 'keterangan' => null],
            ['nomor_drone' => '951', 'nama_drone' => 'PAB HR', 'tipe_drone' => 'T70P', 'keterangan' => null],
        ];

        foreach ($daftar33Drone as $d) {
            $droneRow = Drone::where('nomor_drone', $d['nomor_drone'])->first();
            if ($droneRow) {
                $droneRow->update([
                    'nama_drone' => $d['nama_drone'],
                    'tipe_drone' => $d['tipe_drone'],
                    'status_operasional' => 'aktif',
                    'ambang_batas_servis_ha' => 200.00,
                    'keterangan' => $d['keterangan'],
                ]);
            } else {
                Drone::create([
                    'nomor_drone' => $d['nomor_drone'],
                    'nama_drone' => $d['nama_drone'],
                    'tipe_drone' => $d['tipe_drone'],
                    'status_operasional' => 'aktif',
                    'ambang_batas_servis_ha' => 200.00,
                    'keterangan' => $d['keterangan'],
                ]);
            }
        }

        $drone1 = Drone::whereNotIn('nomor_drone', ['601', '806', '706', '401', '402', '403', '711', '301', '605', '602', '809', '707', '608', '609', '909', '606', '703'])->first() ?? Drone::first();

        // 7. Master Petak Contoh
        Petak::firstOrCreate(
            ['distrik_id' => $distrikDps->id, 'kode_petak' => 'DPS-A01'],
            [
                'nama_petak' => 'Blok A01 DPS',
                'jenis_lahan' => 'Sawit TM',
                'luas_petak_ha' => 25.50,
                'status_petak' => 'aktif',
            ]
        );
        Petak::firstOrCreate(
            ['distrik_id' => $distrikDps->id, 'kode_petak' => 'DPS-A02'],
            [
                'nama_petak' => 'Blok A02 DPS',
                'jenis_lahan' => 'Sawit TBM',
                'luas_petak_ha' => 30.00,
                'status_petak' => 'aktif',
            ]
        );
        Petak::firstOrCreate(
            ['distrik_id' => $distrikDst->id, 'kode_petak' => 'DST-B01'],
            [
                'nama_petak' => 'Blok B01 DST',
                'jenis_lahan' => 'Sawit TM',
                'luas_petak_ha' => 45.20,
                'status_petak' => 'aktif',
            ]
        );

        // 8. Tim Lapangan Contoh
        $adminUser = Pengguna::where('nama_pengguna', 'admin')->first();
        $pekerjaUser = Pengguna::where('nama_pengguna', 'pekerja')->first();

        $timAlpha = Tim::firstOrCreate(
            ['nama_tim' => 'Tim Alpha Drone'],
            [
                'drone_id' => $drone1->id,
                'distrik_id' => $distrikDps->id,
                'kepala_tim_id' => $pekerjaUser?->id,
                'tanggal_mulai' => now()->startOfYear()->toDateString(),
                'status_tim' => 'aktif',
            ]
        );

        if ($pekerjaUser && $timAlpha) {
            AnggotaTim::firstOrCreate(
                ['tim_id' => $timAlpha->id, 'pengguna_id' => $pekerjaUser->id],
                [
                    'jabatan_tim' => AnggotaTim::PILOT,
                    'nama_anggota' => 'Budi Santoso',
                    'tanggal_mulai' => now()->startOfYear()->toDateString(),
                ]
            );
        }

        // Daftarkan 17 Tim Resmi Sesuai Papan Monitoring Lapangan
        $daftarTimPapan = [
            ['nomor_drone' => '601', 'nama_distrik' => 'Distrik DST', 'nama_tim' => 'Tim DST - Drone 601 (Aditya)', 'pilot' => 'Aditya', 'co_pilot' => 'Diwang'],
            ['nomor_drone' => '806', 'nama_distrik' => 'Distrik DST', 'nama_tim' => 'Tim DST - Drone 806 (Egi)', 'pilot' => 'Egi', 'co_pilot' => 'Galang'],
            ['nomor_drone' => '706', 'nama_distrik' => 'Distrik DSB', 'nama_tim' => 'Tim DSB - Drone 706 (Adit)', 'pilot' => 'Adit', 'co_pilot' => 'Rido'],
            ['nomor_drone' => '401', 'nama_distrik' => 'Distrik DSJ', 'nama_tim' => 'Tim DSJ - Drone 401 (ESJB)', 'pilot' => 'Kru ESJB 401', 'co_pilot' => null],
            ['nomor_drone' => '402', 'nama_distrik' => 'Distrik DSJ', 'nama_tim' => 'Tim DSJ - Drone 402 (Zidane)', 'pilot' => 'Zidane', 'co_pilot' => null],
            ['nomor_drone' => '403', 'nama_distrik' => 'Distrik DSJ', 'nama_tim' => 'Tim DSJ - Drone 403 (Aldo)', 'pilot' => 'Aldo', 'co_pilot' => null],
            ['nomor_drone' => '711', 'nama_distrik' => 'Distrik DTJ', 'nama_tim' => 'Tim DTJ - Drone 711 (Jeksen)', 'pilot' => 'Jeksen', 'co_pilot' => null],
            ['nomor_drone' => '301', 'nama_distrik' => 'Distrik DTK', 'nama_tim' => 'Tim DTK - Drone 301 (PAB-ASE)', 'pilot' => 'Kru ASE 301', 'co_pilot' => null],
            ['nomor_drone' => '605', 'nama_distrik' => 'Distrik DLH', 'nama_tim' => 'Tim DLH - Drone 605 (Rico)', 'pilot' => 'Rico', 'co_pilot' => 'Teguh'],
            ['nomor_drone' => '602', 'nama_distrik' => 'Distrik DLH', 'nama_tim' => 'Tim DLH - Drone 602 (Andre)', 'pilot' => 'Andre (DLH)', 'co_pilot' => 'Haryadi'],
            ['nomor_drone' => '809', 'nama_distrik' => 'Distrik DLH', 'nama_tim' => 'Tim DLH - Drone 809 (Putra)', 'pilot' => 'Putra', 'co_pilot' => 'Bayu'],
            ['nomor_drone' => '707', 'nama_distrik' => 'Distrik DTP', 'nama_tim' => 'Tim DTP - Drone 707 (Itsna)', 'pilot' => 'Itsna', 'co_pilot' => null],
            ['nomor_drone' => '608', 'nama_distrik' => 'Distrik DTP', 'nama_tim' => 'Tim DTP - Drone 608 (Melvin)', 'pilot' => 'Melvin', 'co_pilot' => 'Efran'],
            ['nomor_drone' => '609', 'nama_distrik' => 'Distrik DTP', 'nama_tim' => 'Tim DTP - Drone 609 (PAB EKA)', 'pilot' => 'Kru EKA 609', 'co_pilot' => null],
            ['nomor_drone' => '909', 'nama_distrik' => 'Distrik DKL', 'nama_tim' => 'Tim DKL - Drone 909 (Sofian)', 'pilot' => 'Sofian', 'co_pilot' => null],
            ['nomor_drone' => '606', 'nama_distrik' => 'Distrik DSR', 'nama_tim' => 'Tim DSR - Drone 606 (Febri)', 'pilot' => 'Febri', 'co_pilot' => null],
            ['nomor_drone' => '703', 'nama_distrik' => 'Distrik DSR', 'nama_tim' => 'Tim DSR - Drone 703 (Arel)', 'pilot' => 'Arel', 'co_pilot' => null],
        ];

        foreach ($daftarTimPapan as $index => $item) {
            $drone = Drone::where('nomor_drone', $item['nomor_drone'])->first();
            $kodeSingkat = trim(str_replace('Distrik ', '', $item['nama_distrik']));
            $distrik = Distrik::where('nama_distrik', $item['nama_distrik'])
                ->orWhere('kode_distrik', $kodeSingkat)
                ->orWhere('nama_distrik', 'like', "%{$kodeSingkat}%")
                ->first();
            if (! $drone || ! $distrik) {
                continue;
            }

            $pilotUser = null;
            if ($item['pilot']) {
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $item['pilot']));
                if (strlen($username) < 3) {
                    $username = 'pilot' . $item['nomor_drone'];
                }

                $pilotUser = Pengguna::firstOrCreate(
                    ['nama_pengguna' => $username],
                    [
                        'peran_id' => $peranPekerja->id,
                        'nama_lengkap' => $item['pilot'] . ' (Pilot)',
                        'email' => $username . '@pantrack.local',
                        'kata_sandi' => Hash::make('password'),
                        'nomor_telepon' => '081234' . str_pad((string) ($index + 10), 6, '0', STR_PAD_LEFT),
                        'aktif' => true,
                    ]
                );
            }

            $tim = Tim::firstOrCreate(
                ['drone_id' => $drone->id],
                [
                    'nama_tim' => $item['nama_tim'],
                    'distrik_id' => $distrik->id,
                    'kepala_tim_id' => $pilotUser?->id,
                    'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    'status_tim' => Tim::STATUS_AKTIF,
                ]
            );

            if ($pilotUser) {
                AnggotaTim::firstOrCreate(
                    ['tim_id' => $tim->id, 'jabatan_tim' => AnggotaTim::PILOT],
                    [
                        'pengguna_id' => $pilotUser->id,
                        'nama_anggota' => $item['pilot'],
                        'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    ]
                );
            }

            if ($item['co_pilot']) {
                AnggotaTim::firstOrCreate(
                    ['tim_id' => $tim->id, 'jabatan_tim' => AnggotaTim::CO_PILOT],
                    [
                        'pengguna_id' => null,
                        'nama_anggota' => $item['co_pilot'],
                        'tanggal_mulai' => now()->startOfYear()->toDateString(),
                    ]
                );
            }
        }

        // 9. Tarif Pekerjaan Default
        if ($adminUser) {
            TarifPekerjaan::firstOrCreate(
                [
                    'jenis_pekerjaan_id' => $jenisSpraying->id,
                    'berlaku_mulai' => now()->startOfYear()->toDateString(),
                ],
                [
                    'nilai_tarif' => 175000.00,
                    'keterangan' => 'Tarif standar spraying per hektar tahun berjalan',
                    'dibuat_oleh' => $adminUser->id,
                ]
            );

            TarifPekerjaan::firstOrCreate(
                [
                    'jenis_pekerjaan_id' => $jenisSpreading->id,
                    'berlaku_mulai' => now()->startOfYear()->toDateString(),
                ],
                [
                    'nilai_tarif' => 200000.00,
                    'keterangan' => 'Tarif standar spreading per hektar tahun berjalan',
                    'dibuat_oleh' => $adminUser->id,
                ]
            );
        }

        // 10. Contoh SPK Awal Dibuat dan Diterbitkan oleh Kepala Lapangan
        $kepalaUser = Pengguna::where('nama_pengguna', 'kepala')->first();
        $petakDpsRow = Petak::where('kode_petak', 'DPS-A01')->first();
        if ($kepalaUser && $petakDpsRow) {
            $spkAwal = Spk::firstOrCreate(
                ['nomor_spk' => 'SPK/2026/10/001'],
                [
                    'tanggal_spk' => now()->toDateString(),
                    'periode_bulan' => (int) now()->format('m'),
                    'periode_tahun' => (int) now()->format('Y'),
                    'distrik_id' => $distrikDps->id,
                    'jenis_pekerjaan_id' => $jenisSpraying->id,
                    'diinput_oleh' => $kepalaUser->id,
                    'disetujui_oleh' => $kepalaUser->id,
                    'tanggal_persetujuan' => now(),
                    'status_spk' => Spk::DISETUJUI,
                    'keterangan' => 'SPK operasional spraying perdana diterbitkan oleh Kepala Lapangan',
                ]
            );

            DetailSpk::firstOrCreate(
                ['spk_id' => $spkAwal->id, 'petak_id' => $petakDpsRow->id],
                [
                    'luas_target_ha' => 25.50,
                    'catatan' => 'Target luasan spraying petak A01 DPS',
                ]
            );
        }

        // 11. Seeder Operasional Riil Lapangan (Drone 951, Drone 701, Petak, BBM, Kasbon, dan Biaya Unit)
        $this->call(OperasionalRealistisSeeder::class);
    }
}

