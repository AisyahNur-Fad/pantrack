<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bersihkan seluruh data percobaan dan sisakan HANYA data resmi dari papan monitoring (4 PT & 20 Distrik).
     */
    public function up(): void
    {
        $waktu = now();

        // 1. Ambil ID Distrik DPS dan DST yang sah
        $distrikDps = DB::table('distrik')->where('nama_distrik', 'Distrik DPS')->first();
        $distrikDst = DB::table('distrik')->where('nama_distrik', 'Distrik DST')->first();

        $targetDpsId = $distrikDps?->id;
        $targetDstId = $distrikDst?->id ?? $targetDpsId;

        // 2. Daftar 20 Distrik resmi dari papan monitoring
        $distrikResmi = [
            'Distrik DPS', 'Distrik DST', 'Distrik DSB', 'Distrik DSP', 'Distrik DSK', 'Distrik DSG', 'Distrik DSM',
            'Distrik DAS', 'Distrik DSJ', 'Distrik DTJ', 'Distrik DTK', 'Distrik DBR', 'Distrik DBT',
            'Distrik DSH', 'Distrik DLH', 'Distrik DTP', 'Distrik DKL', 'Distrik DSR', 'Distrik DTD',
            'Distrik DMS',
        ];

        // 3. ID Distrik percobaan yang akan dihapus
        $trialDistrikIds = DB::table('distrik')
            ->whereNotIn('nama_distrik', $distrikResmi)
            ->pluck('id')
            ->toArray();

        if (! empty($trialDistrikIds) && $targetDpsId) {
            // Migrasikan relasi petak eksisting ke Distrik DPS / DST
            DB::table('petak')->whereIn('distrik_id', $trialDistrikIds)->where('kode_petak', 'like', 'MNS-%')->update([
                'distrik_id' => $targetDpsId,
            ]);
            DB::table('petak')->whereIn('distrik_id', $trialDistrikIds)->where('kode_petak', 'like', 'LBO-%')->update([
                'distrik_id' => $targetDstId,
            ]);
            // Hapus petak uji coba sementara jika ada
            DB::table('petak')->whereIn('distrik_id', $trialDistrikIds)->where('kode_petak', 'like', '%TEST%')->delete();
            // Pindahkan sisa petak apapun yang tersisa ke DPS
            DB::table('petak')->whereIn('distrik_id', $trialDistrikIds)->update(['distrik_id' => $targetDpsId]);

            // Sesuaikan kode & nama petak agar senada dengan distrik barunya
            DB::table('petak')->where('kode_petak', 'MNS-A01')->update(['kode_petak' => 'DPS-A01', 'nama_petak' => 'Blok A01 DPS']);
            DB::table('petak')->where('kode_petak', 'MNS-A02')->update(['kode_petak' => 'DPS-A02', 'nama_petak' => 'Blok A02 DPS']);
            DB::table('petak')->where('kode_petak', 'LBO-B01')->update(['kode_petak' => 'DST-B01', 'nama_petak' => 'Blok B01 DST']);

            // Migrasikan relasi tim, spk, dan laporan_realisasi ke Distrik DPS
            DB::table('tim')->whereIn('distrik_id', $trialDistrikIds)->update(['distrik_id' => $targetDpsId]);
            DB::table('spk')->whereIn('distrik_id', $trialDistrikIds)->update(['distrik_id' => $targetDpsId]);
            DB::table('laporan_realisasi')->whereIn('distrik_id', $trialDistrikIds)->update(['distrik_id' => $targetDpsId]);

            // Hapus seluruh distrik percobaan (Minas, Libo, Test, dll.)
            DB::table('distrik')->whereIn('id', $trialDistrikIds)->delete();
        }

        // 4. Hapus seluruh PT percobaan yang bukan BMH, BAP, SBA, CBS
        DB::table('pt')->whereNotIn('kode_pt', ['BMH', 'BAP', 'SBA', 'CBS'])->delete();
    }

    public function down(): void
    {
        // Operasi pembersihan satu arah
    }
};

