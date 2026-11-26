<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Jalankan migrasi seeding data 4 PT & 20 Distrik sesuai papan monitoring.
     */
    public function up(): void
    {
        $waktu = now();

        // 1. Data 4 Perusahaan (PT) Induk
        $daftarPt = [
            'BMH' => [
                'nama_pt' => 'PT BMH',
                'kode_pt' => 'BMH',
                'keterangan' => 'Wilayah Operasional PT BMH',
                'aktif' => true,
            ],
            'BAP' => [
                'nama_pt' => 'PT BAP',
                'kode_pt' => 'BAP',
                'keterangan' => 'Wilayah Operasional PT BAP',
                'aktif' => true,
            ],
            'SBA' => [
                'nama_pt' => 'PT SBA',
                'kode_pt' => 'SBA',
                'keterangan' => 'Wilayah Operasional PT SBA',
                'aktif' => true,
            ],
            'CBS' => [
                'nama_pt' => 'PT CBS',
                'kode_pt' => 'CBS',
                'keterangan' => 'Wilayah Operasional PT CBS',
                'aktif' => true,
            ],
        ];

        $ptIds = [];
        foreach ($daftarPt as $kode => $ptData) {
            $existing = DB::table('pt')->where('kode_pt', $kode)->first();
            if ($existing) {
                $ptIds[$kode] = $existing->id;
                DB::table('pt')->where('id', $existing->id)->update([
                    'nama_pt' => $ptData['nama_pt'],
                    'keterangan' => $ptData['keterangan'],
                    'aktif' => true,
                    'updated_at' => $waktu,
                ]);
            } else {
                $ptIds[$kode] = DB::table('pt')->insertGetId([
                    'nama_pt' => $ptData['nama_pt'],
                    'kode_pt' => $ptData['kode_pt'],
                    'keterangan' => $ptData['keterangan'],
                    'aktif' => $ptData['aktif'],
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);
            }
        }

        // 2. Data 20 Distrik terpetakan ke masing-masing PT
        $pemetaanDistrik = [
            // PT BMH (7 distrik)
            ['pt' => 'BMH', 'nama' => 'Distrik DPS', 'keterangan' => 'Distrik DPS - Wilayah PT BMH'],
            ['pt' => 'BMH', 'nama' => 'Distrik DST', 'keterangan' => 'Distrik DST - Wilayah PT BMH'],
            ['pt' => 'BMH', 'nama' => 'Distrik DSB', 'keterangan' => 'Distrik DSB - Wilayah PT BMH'],
            ['pt' => 'BMH', 'nama' => 'Distrik DSP', 'keterangan' => 'Distrik DSP - Wilayah PT BMH'],
            ['pt' => 'BMH', 'nama' => 'Distrik DSK', 'keterangan' => 'Distrik DSK - Wilayah PT BMH'],
            ['pt' => 'BMH', 'nama' => 'Distrik DSG', 'keterangan' => 'Distrik DSG - Wilayah PT BMH'],
            ['pt' => 'BMH', 'nama' => 'Distrik DSM', 'keterangan' => 'Distrik DSM - Wilayah PT BMH'],

            // PT BAP (6 distrik)
            ['pt' => 'BAP', 'nama' => 'Distrik DAS', 'keterangan' => 'Distrik DAS - Wilayah PT BAP'],
            ['pt' => 'BAP', 'nama' => 'Distrik DSJ', 'keterangan' => 'Distrik DSJ - Wilayah PT BAP'],
            ['pt' => 'BAP', 'nama' => 'Distrik DTJ', 'keterangan' => 'Distrik DTJ - Wilayah PT BAP'],
            ['pt' => 'BAP', 'nama' => 'Distrik DTK', 'keterangan' => 'Distrik DTK - Wilayah PT BAP'],
            ['pt' => 'BAP', 'nama' => 'Distrik DBR', 'keterangan' => 'Distrik DBR - Wilayah PT BAP'],
            ['pt' => 'BAP', 'nama' => 'Distrik DBT', 'keterangan' => 'Distrik DBT - Wilayah PT BAP'],

            // PT SBA (6 distrik)
            ['pt' => 'SBA', 'nama' => 'Distrik DSH', 'keterangan' => 'Distrik DSH - Wilayah PT SBA'],
            ['pt' => 'SBA', 'nama' => 'Distrik DLH', 'keterangan' => 'Distrik DLH - Wilayah PT SBA'],
            ['pt' => 'SBA', 'nama' => 'Distrik DTP', 'keterangan' => 'Distrik DTP - Wilayah PT SBA'],
            ['pt' => 'SBA', 'nama' => 'Distrik DKL', 'keterangan' => 'Distrik DKL - Wilayah PT SBA'],
            ['pt' => 'SBA', 'nama' => 'Distrik DSR', 'keterangan' => 'Distrik DSR - Wilayah PT SBA'],
            ['pt' => 'SBA', 'nama' => 'Distrik DTD', 'keterangan' => 'Distrik DTD - Wilayah PT SBA'],

            // PT CBS (1 distrik)
            ['pt' => 'CBS', 'nama' => 'Distrik DMS', 'keterangan' => 'Distrik DMS - Wilayah PT CBS'],
        ];

        foreach ($pemetaanDistrik as $d) {
            $ptId = $ptIds[$d['pt']] ?? null;
            $exist = DB::table('distrik')->where('nama_distrik', $d['nama'])->first();
            if ($exist) {
                DB::table('distrik')->where('id', $exist->id)->update([
                    'pt_id' => $ptId,
                    'keterangan' => $d['keterangan'],
                    'aktif' => true,
                    'updated_at' => $waktu,
                ]);
            } else {
                DB::table('distrik')->insert([
                    'pt_id' => $ptId,
                    'nama_distrik' => $d['nama'],
                    'keterangan' => $d['keterangan'],
                    'aktif' => true,
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);
            }
        }
    }

    /**
     * Reverse migrasi data jika di-rollback.
     */
    public function down(): void
    {
        $daftarDistrikHapus = [
            'Distrik DPS', 'Distrik DST', 'Distrik DSB', 'Distrik DSP', 'Distrik DSK', 'Distrik DSG', 'Distrik DSM',
            'Distrik DAS', 'Distrik DSJ', 'Distrik DTJ', 'Distrik DTK', 'Distrik DBR', 'Distrik DBT',
            'Distrik DSH', 'Distrik DLH', 'Distrik DTP', 'Distrik DKL', 'Distrik DSR', 'Distrik DTD',
            'Distrik DMS',
        ];

        DB::table('distrik')->whereIn('nama_distrik', $daftarDistrikHapus)->delete();
        DB::table('pt')->whereIn('kode_pt', ['BMH', 'BAP', 'SBA', 'CBS'])->delete();
    }
};

