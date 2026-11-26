<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan pembaruan data resmi 3 Perusahaan (PT) dan 19 Distrik operasional PANTRACK.
     */
    public function up(): void
    {
        // 1. Pastikan kolom kode_distrik tersedia di tabel distrik
        if (Schema::hasTable('distrik')) {
            Schema::table('distrik', function (Blueprint $table) {
                if (! Schema::hasColumn('distrik', 'kode_distrik')) {
                    $table->string('kode_distrik', 20)->nullable()->after('pt_id');
                }
            });
        }

        $waktu = now();

        // 2. Data Resmi 3 Perusahaan (PT)
        $daftarPtResmi = [
            'BAP' => [
                'nama_pt' => 'PT. BUMI ANDALAS PERMAI (BAP)',
                'kode_pt' => 'BAP',
                'keterangan' => 'PT. BUMI ANDALAS PERMAI (BAP) - 7 Distrik Operasional',
                'aktif' => true,
            ],
            'SBA' => [
                'nama_pt' => 'PT. SEBANGUN BUMI ANDALAS (SBA)',
                'kode_pt' => 'SBA',
                'keterangan' => 'PT. SEBANGUN BUMI ANDALAS (SBA) - 5 Distrik Operasional',
                'aktif' => true,
            ],
            'BMH' => [
                'nama_pt' => 'PT. BUMI MEKAR HIJAU',
                'kode_pt' => 'BMH',
                'keterangan' => 'PT. BUMI MEKAR HIJAU (BMH) - 7 Distrik Operasional',
                'aktif' => true,
            ],
        ];

        $ptIds = [];
        foreach ($daftarPtResmi as $kode => $dataPt) {
            $existingPt = DB::table('pt')->where('kode_pt', $kode)->first();
            if ($existingPt) {
                DB::table('pt')->where('id', $existingPt->id)->update([
                    'nama_pt' => $dataPt['nama_pt'],
                    'keterangan' => $dataPt['keterangan'],
                    'aktif' => $dataPt['aktif'],
                    'updated_at' => $waktu,
                ]);
                $ptIds[$kode] = $existingPt->id;
            } else {
                $ptIds[$kode] = DB::table('pt')->insertGetId([
                    'nama_pt' => $dataPt['nama_pt'],
                    'kode_pt' => $dataPt['kode_pt'],
                    'keterangan' => $dataPt['keterangan'],
                    'aktif' => $dataPt['aktif'],
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);
            }
        }

        // Nonaktifkan PT CBS dan distrik DMS yang merupakan entitas percobaan
        DB::table('pt')->where('kode_pt', 'CBS')->update([
            'aktif' => false,
            'keterangan' => 'Unit Nonaktif / Di luar 3 PT Konsesi Utama',
            'updated_at' => $waktu,
        ]);
        DB::table('distrik')->where('nama_distrik', 'like', '%DMS%')->update([
            'aktif' => false,
            'updated_at' => $waktu,
        ]);

        // 3. Data Resmi 19 Distrik
        $daftarDistrikResmi = [
            // PT. BUMI ANDALAS PERMAI (BAP) - 7 Distrik
            ['pt' => 'BAP', 'kode' => 'DAS', 'nama' => 'DAS - DISTRIK AIR SUGIHAN', 'ket' => 'DISTRIK AIR SUGIHAN'],
            ['pt' => 'BAP', 'kode' => 'DSJ', 'nama' => 'DSJ - DISTRIK SUNGAI JELETUNG', 'ket' => 'DISTRIK SUNGAI JELETUNG'],
            ['pt' => 'BAP', 'kode' => 'DTJ', 'nama' => 'DTJ - DISTRIK TANJUNG JATI', 'ket' => 'DISTRIK TANJUNG JATI'],
            ['pt' => 'BAP', 'kode' => 'DTK', 'nama' => 'DTK - DISTRIK TANJUNG KAIT', 'ket' => 'DISTRIK TANJUNG KAIT'],
            ['pt' => 'BAP', 'kode' => 'DBR', 'nama' => 'DBR - DISTRIK BAGAN RAME', 'ket' => 'DISTRIK BAGAN RAME'],
            ['pt' => 'BAP', 'kode' => 'DBT', 'nama' => 'DBT - DISTRIK BAGAN TENGAH', 'ket' => 'DISTRIK BAGAN TENGAH'],
            ['pt' => 'BAP', 'kode' => 'DSH', 'nama' => 'DSH - DISTRIK SIMPANG HERAN', 'ket' => 'DISTRIK SIMPANG HERAN'],

            // PT. SEBANGUN BUMI ANDALAS (SBA) - 5 Distrik
            ['pt' => 'SBA', 'kode' => 'DLH', 'nama' => 'DLH - DISTRIK LEBONG HITAM', 'ket' => 'DISTRIK LEBONG HITAM'],
            ['pt' => 'SBA', 'kode' => 'DTP', 'nama' => 'DTP - DISTRIK TELUK PULAI', 'ket' => 'DISTRIK TELUK PULAI'],
            ['pt' => 'SBA', 'kode' => 'DKL', 'nama' => 'DKL - DISTRIK KUALA LUMPUR', 'ket' => 'DISTRIK KUALA LUMPUR'],
            ['pt' => 'SBA', 'kode' => 'DSR', 'nama' => 'DSR - DISTRIK SUNGAI RIDING', 'ket' => 'DISTRIK SUNGAI RIDING'],
            ['pt' => 'SBA', 'kode' => 'DTD', 'nama' => 'DTD - DISTRIK TELUK DAUN', 'ket' => 'DISTRIK TELUK DAUN'],

            // PT. BUMI MEKAR HIJAU (BMH) - 7 Distrik
            ['pt' => 'BMH', 'kode' => 'DPS', 'nama' => 'DPS - DISTRIK PADANG SUGIHAN', 'ket' => 'DISTRIK PADANG SUGIHAN'],
            ['pt' => 'BMH', 'kode' => 'DST', 'nama' => 'DST - DISTRIK SIMPANG TIGA', 'ket' => 'DISTRIK SIMPANG TIGA'],
            ['pt' => 'BMH', 'kode' => 'DSB', 'nama' => 'DSB - DISTRIK SUNGAI BYUKU', 'ket' => 'DISTRIK SUNGAI BYUKU'],
            ['pt' => 'BMH', 'kode' => 'DSP', 'nama' => 'DSP - DISTRIK SUNGAI PENYABUNGAN', 'ket' => 'DISTRIK SUNGAI PENYABUNGAN'],
            ['pt' => 'BMH', 'kode' => 'DSK', 'nama' => 'DSK - DISTRIK SUNGAI KETUPAK', 'ket' => 'DISTRIK SUNGAI KETUPAK'],
            ['pt' => 'BMH', 'kode' => 'DSG', 'nama' => 'DSG - DISTRIK SUNGAI GEBANG', 'ket' => 'DISTRIK SUNGAI GEBANG'],
            ['pt' => 'BMH', 'kode' => 'DSS', 'nama' => 'DSS - DISTRIK SUNGAI SERDANG', 'ket' => 'DISTRIK SUNGAI SERDANG'],
        ];

        foreach ($daftarDistrikResmi as $d) {
            $ptId = $ptIds[$d['pt']] ?? null;

            // Pencarian data distrik lama berdasarkan kode atau nama lama (termasuk kasus typo DSM -> DSS)
            $queryLama = DB::table('distrik');
            if ($d['kode'] === 'DSS') {
                $queryLama->where(function ($q) {
                    $q->where('nama_distrik', 'Distrik DSM')
                        ->orWhere('nama_distrik', 'like', '%DSS%')
                        ->orWhere('nama_distrik', 'like', '%DSM%')
                        ->orWhere('kode_distrik', 'DSS')
                        ->orWhere('kode_distrik', 'DSM');
                });
            } else {
                $queryLama->where(function ($q) use ($d) {
                    $q->where('nama_distrik', 'Distrik ' . $d['kode'])
                        ->orWhere('nama_distrik', 'like', '%' . $d['kode'] . '%')
                        ->orWhere('kode_distrik', $d['kode']);
                });
            }

            $existingDistrik = $queryLama->first();

            if ($existingDistrik) {
                DB::table('distrik')->where('id', $existingDistrik->id)->update([
                    'pt_id' => $ptId,
                    'kode_distrik' => $d['kode'],
                    'nama_distrik' => $d['nama'],
                    'keterangan' => $d['ket'],
                    'aktif' => true,
                    'updated_at' => $waktu,
                ]);
            } else {
                DB::table('distrik')->insert([
                    'pt_id' => $ptId,
                    'kode_distrik' => $d['kode'],
                    'nama_distrik' => $d['nama'],
                    'keterangan' => $d['ket'],
                    'aktif' => true,
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);
            }
        }
    }

    /**
     * Reverse migrasi jika diperlukan.
     */
    public function down(): void
    {
        // Revert ke nama dasar lama jika di-rollback
        $daftarRollback = [
            'DAS' => 'Distrik DAS', 'DSJ' => 'Distrik DSJ', 'DTJ' => 'Distrik DTJ', 'DTK' => 'Distrik DTK',
            'DBR' => 'Distrik DBR', 'DBT' => 'Distrik DBT', 'DSH' => 'Distrik DSH', 'DLH' => 'Distrik DLH',
            'DTP' => 'Distrik DTP', 'DKL' => 'Distrik DKL', 'DSR' => 'Distrik DSR', 'DTD' => 'Distrik DTD',
            'DPS' => 'Distrik DPS', 'DST' => 'Distrik DST', 'DSB' => 'Distrik DSB', 'DSP' => 'Distrik DSP',
            'DSK' => 'Distrik DSK', 'DSG' => 'Distrik DSG', 'DSS' => 'Distrik DSM',
        ];

        foreach ($daftarRollback as $kode => $namaLama) {
            DB::table('distrik')->where('kode_distrik', $kode)->update([
                'nama_distrik' => $namaLama,
            ]);
        }
    }
};

