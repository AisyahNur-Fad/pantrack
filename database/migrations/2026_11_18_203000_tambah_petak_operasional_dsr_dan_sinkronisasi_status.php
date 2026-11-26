<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Daftarkan master petak riil baru untuk Distrik Sungai Riding (DSR) sesuai laporan operasional lapangan.
     */
    public function up(): void
    {
        $distrikDsr = DB::table('distrik')
            ->where('kode_distrik', 'DSR')
            ->orWhere('nama_distrik', 'like', '%DSR%')
            ->first();

        if (! $distrikDsr) {
            return;
        }

        $waktu = now();

        $petakBaru = [
            ['kode_petak' => 'SRB8030', 'nama_petak' => 'Petak SRB8030 DSR', 'luas_petak_ha' => 11.30, 'jenis_lahan' => 'Akasia'],
            ['kode_petak' => 'SRA5130', 'nama_petak' => 'Petak SRA5130 DSR', 'luas_petak_ha' => 12.30, 'jenis_lahan' => 'Akasia'],
            ['kode_petak' => 'SRB8010', 'nama_petak' => 'Petak SRB8010 DSR', 'luas_petak_ha' => 22.00, 'jenis_lahan' => 'Eukaliptus'],
            ['kode_petak' => 'SRB5190', 'nama_petak' => 'Petak SRB5190 DSR', 'luas_petak_ha' => 23.70, 'jenis_lahan' => 'Akasia'],
            ['kode_petak' => 'SRB5230', 'nama_petak' => 'Petak SRB5230 DSR', 'luas_petak_ha' => 24.00, 'jenis_lahan' => 'Eukaliptus'],
            ['kode_petak' => 'SRA6120', 'nama_petak' => 'Petak SRA6120 DSR', 'luas_petak_ha' => 23.90, 'jenis_lahan' => 'Akasia'],
            ['kode_petak' => 'SRB7140', 'nama_petak' => 'Petak SRB7140 DSR', 'luas_petak_ha' => 29.20, 'jenis_lahan' => 'Akasia'],
            ['kode_petak' => 'SRB6221', 'nama_petak' => 'Petak SRB6221 DSR', 'luas_petak_ha' => 11.30, 'jenis_lahan' => 'Akasia'],
            ['kode_petak' => 'SRA5070', 'nama_petak' => 'Petak SRA5070 DSR', 'luas_petak_ha' => 22.30, 'jenis_lahan' => 'Eukaliptus'],
        ];

        foreach ($petakBaru as $p) {
            $exist = DB::table('petak')
                ->where('distrik_id', $distrikDsr->id)
                ->where('kode_petak', $p['kode_petak'])
                ->first();

            if (! $exist) {
                DB::table('petak')->insert([
                    'distrik_id' => $distrikDsr->id,
                    'kode_petak' => $p['kode_petak'],
                    'nama_petak' => $p['nama_petak'],
                    'luas_petak_ha' => $p['luas_petak_ha'],
                    'jenis_lahan' => $p['jenis_lahan'],
                    'status_petak' => 'aktif',
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);
            } else {
                DB::table('petak')->where('id', $exist->id)->update([
                    'luas_petak_ha' => $p['luas_petak_ha'],
                    'status_petak' => 'aktif',
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
        $kodeHapus = [
            'SRB8030', 'SRA5130', 'SRB8010', 'SRB5190', 'SRB5230',
            'SRA6120', 'SRB7140', 'SRB6221', 'SRA5070',
        ];

        DB::table('petak')->whereIn('kode_petak', $kodeHapus)->delete();
    }
};
