<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Tests\TestCase;

class DashboardPerRoleTest extends TestCase
{
    public function test_pekerja_lapangan_melihat_dashboard_penghasilan_dan_spk_tim(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $this->assertNotNull($pekerja);

        $response = $this->actingAs($pekerja)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Ringkasan Operasional', false);
        $response->assertSee('Penghasilan Bersih (THP)', false);
        $response->assertSee('Premi Terbang Disetujui', false);
        $response->assertSee('Rincian Slip Penghasilan', false);
        $response->assertSee('SPK Aktif Tim Saya', false);
        $response->assertSee('Stok BBM per Tim', false);
        $response->assertSee('Status Armada Drone Tim', false);
        $response->assertSee('Siklus Servis Berkala', false);
        $response->assertDontSee('kalender-mini', false);
    }

    public function test_pekerja_lapangan_dengan_peran_helper_melihat_slip_uang_makan_harian(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $this->assertNotNull($pekerja);

        $keanggotaan = \App\Models\AnggotaTim::where('pengguna_id', $pekerja->id)->first();
        if ($keanggotaan) {
            $keanggotaan->update(['jabatan_tim' => \App\Models\AnggotaTim::HELPER]);
        }

        try {
            $response = $this->actingAs($pekerja)->get(route('dashboard'));
            $response->assertStatus(200);
            $response->assertSee('Helper Lapangan', false);
            $response->assertSee('Uang Makan Kerja', false);
        } finally {
            if ($keanggotaan) {
                $keanggotaan->update(['jabatan_tim' => \App\Models\AnggotaTim::PILOT]);
            }
        }
    }

    public function test_kepala_lapangan_melihat_dashboard_monitoring_dan_antrean_laporan(): void
    {
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->first();
        $this->assertNotNull($kepala);

        $response = $this->actingAs($kepala)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Monitoring Operasional Lapangan', false);
        $response->assertSee('Antrean Laporan Butuh Verifikasi', false);
        $response->assertSee('Capaian Target Tiap Tim', false);
        $response->assertSee('Buat SPK Baru', false);
        $response->assertSee('Pipeline Operasional: Pre-Flight Mapping vs Aplikasi Drone', false);
        $response->assertSee('Pemetaan Petak (Mapping)', false);
        $response->assertSee('Aplikasi Semprot & Sebar', false);
        $response->assertSee('&lt; 24 Jam', false);
    }

    public function test_admin_melihat_dashboard_keuangan_bbm_dan_master(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Ringkasan Finansial', false);
        $response->assertSee('Antrean BBM Menunggu Verifikasi', false);
        $response->assertSee('Kasbon Personil Terbaru', false);
        $response->assertSee('Drone Aktif Beroperasi', false);
        $response->assertSee('Pengeluaran Bon Unit', false);
        $response->assertSee('Kesehatan Finansial Kru & Rasio Keamanan Kasbon', false);
        $response->assertSee('Estimasi Hak Premi Terbang Kru', false);
    }

    public function test_atasan_melihat_dashboard_eksekutif_dan_profit_bap(): void
    {
        $atasan = Pengguna::where('nama_pengguna', 'atasan')->first();
        $this->assertNotNull($atasan);

        $response = $this->actingAs($atasan)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Ringkasan Eksekutif', false);
        $response->assertSee('Hasil Bersih 80% (BAP)', false);
        $response->assertSee('Estimasi Profit Bersih', false);
        $response->assertSee('Efisiensi BBM', false);
        $response->assertSee('Produktivitas Tiap Tim Lapangan', false);
        $response->assertSee('Struktur Pendapatan & Distribusi Hasil BAP Resmi', false);
        $response->assertSee('Unit Operasional (80%)', false);
        $response->assertSee('Investor / Pemilik Modal (15%)', false);
        $response->assertSee('Administrasi PT (5%)', false);
        $response->assertSee('Komposisi Sumber Pendapatan', false);
        $response->assertSee('Aplikasi Pokok', false);
        $response->assertSee('Mapping Add-on', false);
    }

    public function test_semua_peran_dapat_mengakses_dashboard_pada_periode_kosong_tanpa_error(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->first();
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $atasan = Pengguna::where('nama_pengguna', 'atasan')->first();

        foreach ([$pekerja, $kepala, $admin, $atasan] as $user) {
            $response = $this->actingAs($user)->get(route('dashboard', ['bulan' => 1, 'tahun' => 2030]));
            $response->assertStatus(200);
        }
    }

    public function test_pekerja_lapangan_dilarang_akses_riwayat_aktivitas(): void
    {
        foreach (['pekerja', 'kepala', 'admin', 'atasan'] as $uname) {
            $user = Pengguna::where('nama_pengguna', $uname)->first();
            if ($user) {
                $response = $this->actingAs($user)->get(route('riwayat.index'));
                $response->assertStatus(403);
            }
        }
    }
}
