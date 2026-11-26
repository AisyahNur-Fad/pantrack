<?php

namespace Tests\Feature;

use App\Models\AnggotaTim;
use App\Models\Pengguna;
use App\Services\DashboardService;
use Tests\TestCase;

class DashboardServiceKalkulasiTest extends TestCase
{
    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardService::class);
    }

    public function test_kalkulasi_data_pekerja_membedakan_pilot_dan_helper(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $this->assertNotNull($pekerja);

        $keanggotaan = AnggotaTim::where('pengguna_id', $pekerja->id)->first();
        if ($keanggotaan) {
            $keanggotaan->update(['jabatan_tim' => AnggotaTim::PILOT]);
        }

        try {
            // 1. Uji kondisi sebagai Pilot Drone
            $dataPilot = $this->service->ringkasan($pekerja, 11, 2026);
            $this->assertArrayHasKey('pekerja', $dataPilot);
            $pKru = $dataPilot['pekerja'];

            $this->assertEquals('pilot', $pKru['peranKru']);
            $this->assertEquals('Pilot Drone', $pKru['labelPeranKru']);
            $this->assertFalse($pKru['isHelper']);
            $this->assertGreaterThan(0, $pKru['gajiPokok']);
            $this->assertGreaterThan(0, $pKru['uangMakan']);
            $this->assertGreaterThan(0, $pKru['premiPerHa']);
            $this->assertNotNull($pKru['droneInfo']);
            $this->assertArrayHasKey('ambang_batas_servis_ha', $pKru['droneInfo']);

            // 2. Uji kondisi jika jabatan diubah menjadi Helper
            if ($keanggotaan) {
                $keanggotaan->update(['jabatan_tim' => AnggotaTim::HELPER]);
            }

            $dataHelper = $this->service->ringkasan($pekerja, 11, 2026);
            $hKru = $dataHelper['pekerja'];

            $this->assertEquals('helper', $hKru['peranKru']);
            $this->assertEquals('Helper Lapangan', $hKru['labelPeranKru']);
            $this->assertTrue($hKru['isHelper']);
            $this->assertGreaterThan(0, $hKru['premiPerHa']);
            $this->assertGreaterThan($hKru['premiPerHa'], $pKru['premiPerHa']);
            $this->assertGreaterThan(0, $hKru['tarifMakanHarian']);
        } finally {
            // Selalu kembalikan ke pilot
            if ($keanggotaan) {
                $keanggotaan->update(['jabatan_tim' => AnggotaTim::PILOT]);
            }
        }
    }

    public function test_kalkulasi_data_kepala_memiliki_sla_radar_dan_pipeline_mapping(): void
    {
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->first();
        $this->assertNotNull($kepala);

        $data = $this->service->ringkasan($kepala, 11, 2026);
        $this->assertArrayHasKey('kepala', $data);
        $k = $data['kepala'];

        // Uji SLA Radar
        $this->assertArrayHasKey('slaSummary', $k);
        $this->assertArrayHasKey('total', $k['slaSummary']);
        $this->assertArrayHasKey('hijau', $k['slaSummary']);
        $this->assertArrayHasKey('kuning', $k['slaSummary']);
        $this->assertArrayHasKey('merah', $k['slaSummary']);
        $this->assertGreaterThanOrEqual(0, $k['slaSummary']['total']);

        // Uji Pipeline Mapping vs Aplikasi
        $this->assertArrayHasKey('pipeline', $k);
        $p = $k['pipeline'];
        $this->assertArrayHasKey('targetAplikasiHa', $p);
        $this->assertArrayHasKey('realisasiAplikasiHa', $p);
        $this->assertArrayHasKey('targetMappingHa', $p);
        $this->assertArrayHasKey('realisasiMappingHa', $p);
        $this->assertGreaterThanOrEqual(0, $p['persenAplikasi']);
        $this->assertGreaterThanOrEqual(0, $p['persenMapping']);
    }

    public function test_kalkulasi_data_admin_memiliki_anomali_bbm_dan_safety_ratio_kasbon(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $this->assertNotNull($admin);

        $data = $this->service->ringkasan($admin, 11, 2026);
        $this->assertArrayHasKey('admin', $data);
        $adm = $data['admin'];

        // Kasbon Safety Ratio
        $this->assertArrayHasKey('kasbonSafetyRatio', $adm);
        $this->assertArrayHasKey('kasbonSafetyStatus', $adm);
        $this->assertContains($adm['kasbonSafetyStatus'], ['aman', 'waspada', 'kritis']);
        $this->assertGreaterThanOrEqual(0, $adm['kasbonSafetyRatio']);

        // Radar Anomali BBM
        $this->assertArrayHasKey('anomaliBbm', $adm);
        $this->assertIsArray($adm['anomaliBbm']);
        $this->assertArrayHasKey('rasioArmadaBbm', $adm);
        $this->assertGreaterThanOrEqual(0, $adm['rasioArmadaBbm']);
    }

    public function test_kalkulasi_data_atasan_profit_bap_dan_kontribusi_mapping(): void
    {
        $atasan = Pengguna::where('nama_pengguna', 'atasan')->first();
        $this->assertNotNull($atasan);

        $data = $this->service->ringkasan($atasan, 11, 2026);
        $this->assertArrayHasKey('atasan', $data);
        $at = $data['atasan'];

        // Formula Bagi Hasil Resmi 80 / 15 / 5
        $totalNilai = $at['totalNilai'];
        $this->assertEquals(round($totalNilai * 0.80, 2), round($at['hasilBersih80'], 2));
        $this->assertEquals(round($totalNilai * 0.15, 2), round($at['investor15'], 2));
        $this->assertEquals(round($totalNilai * 0.05, 2), round($at['admPt5'], 2));

        // Kontribusi Revenue Pokok vs Mapping Add-on
        $this->assertArrayHasKey('nilaiPokok', $at);
        $this->assertArrayHasKey('nilaiMapping', $at);
        if ($totalNilai > 0) {
            $this->assertEquals(100.0, round($at['persenPokok'] + $at['persenMapping'], 1));
        }

        // Estimasi Profit Bersih
        $this->assertGreaterThanOrEqual(0, $at['estimasiProfitBersih']);
        $this->assertArrayHasKey('marginProfitPersen', $at);
        $this->assertGreaterThanOrEqual(0, $at['marginProfitPersen']);
    }
}
