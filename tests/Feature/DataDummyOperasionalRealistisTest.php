<?php

namespace Tests\Feature;

use App\Models\AnggotaTim;
use App\Models\DetailRealisasi;
use App\Models\Distrik;
use App\Models\Drone;
use App\Models\KasbonPersonil;
use App\Models\LaporanRealisasi;
use App\Models\PengeluaranOperasionalUnit;
use App\Models\Pengguna;
use App\Models\Petak;
use App\Models\Spk;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use Database\Seeders\OperasionalRealistisSeeder;
use Tests\TestCase;

class DataDummyOperasionalRealistisTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Jalankan seeder operasional realistis agar data selalu terjamin tersedia
        $this->seed(OperasionalRealistisSeeder::class);
    }

    public function test_master_petak_memiliki_data_riil_dsr_dan_dsk(): void
    {
        $distrikDsr = Distrik::where('kode_distrik', 'DSR')->orWhere('nama_distrik', 'like', '%DSR%')->firstOrFail();
        $distrikDsk = Distrik::where('kode_distrik', 'DSK')->orWhere('nama_distrik', 'like', '%DSK%')->firstOrFail();

        // Cek petak DSR
        $this->assertNotNull(Petak::where('distrik_id', $distrikDsr->id)->where('kode_petak', 'SRA5150')->first());
        $this->assertNotNull(Petak::where('distrik_id', $distrikDsr->id)->where('kode_petak', 'SRB5261')->first());
        $this->assertNotNull(Petak::where('distrik_id', $distrikDsr->id)->where('kode_petak', 'SRB5120')->first());

        // Cek petak DSK
        $this->assertNotNull(Petak::where('distrik_id', $distrikDsk->id)->where('kode_petak', 'TKE1170')->first());
        $this->assertNotNull(Petak::where('distrik_id', $distrikDsk->id)->where('kode_petak', 'TKF5180')->first());
    }

    public function test_tim_drone_951_dan_701_terhubung_ke_pilot_dan_distrik_resmi(): void
    {
        // Tim Drone 951 (Pilot Heru Prasetiyo, Co-Pilot Santo, Distrik DSR)
        $drone951 = Drone::where('nomor_drone', '951')->firstOrFail();
        $tim951 = Tim::where('drone_id', $drone951->id)->with(['kepalaTim', 'anggota', 'distrik'])->firstOrFail();
        $this->assertStringContainsString('DSR', $tim951->distrik->nama_distrik);
        $this->assertStringContainsString('Heru', $tim951->kepalaTim->nama_lengkap);

        $pilot951 = $tim951->anggota->firstWhere('jabatan_tim', AnggotaTim::PILOT);
        $coPilot951 = $tim951->anggota->firstWhere('jabatan_tim', AnggotaTim::CO_PILOT);
        $this->assertNotNull($pilot951);
        $this->assertNotNull($coPilot951);
        $this->assertEquals('Heru Prasetiyo', $pilot951->nama_anggota);
        $this->assertEquals('Santo', $coPilot951->nama_anggota);

        // Tim Drone 701 (Pilot Budi Santoso, Co-Pilot Rahmat, Distrik DSK)
        $drone701 = Drone::where('nomor_drone', '701')->firstOrFail();
        $tim701 = Tim::where('drone_id', $drone701->id)->with(['kepalaTim', 'anggota', 'distrik'])->firstOrFail();
        $this->assertStringContainsString('DSK', $tim701->distrik->nama_distrik);

        $pilot701 = $tim701->anggota->firstWhere('jabatan_tim', AnggotaTim::PILOT);
        $coPilot701 = $tim701->anggota->firstWhere('jabatan_tim', AnggotaTim::CO_PILOT);
        $this->assertNotNull($pilot701);
        $this->assertNotNull($coPilot701);
        $this->assertEquals('Budi Santoso', $pilot701->nama_anggota);
        $this->assertEquals('Rahmat', $coPilot701->nama_anggota);
    }

    public function test_laporan_realisasi_drone_951_sesuai_teks_wa_lapangan(): void
    {
        $lap951 = LaporanRealisasi::where('nomor_laporan', 'REAL/DSR/2026/07/951')
            ->with(['detail', 'tim'])
            ->firstOrFail();

        // Personil
        $this->assertEquals('Heru Prasetiyo', $lap951->nama_pilot);
        $this->assertEquals('Santo', $lap951->nama_copilot);
        $this->assertEquals('Shobirin, Raka, Nasir', $lap951->nama_helper);
        $this->assertCount(3, $lap951->daftar_helper);
        $this->assertEquals(['Shobirin', 'Raka', 'Nasir'], $lap951->daftar_helper);

        // Akumulasi total luas MTD Juli adalah tepat 367.50 Ha
        $totalLuasMtd = $lap951->detail->sum('luas_realisasi_ha');
        $this->assertEquals(367.50, round((float) $totalLuasMtd, 2));

        // Verifikasi keberadaan kendala operasional lapangan riil
        $kendalaKetek = $lap951->detail->where('keterangan_kendala', 'Ketek Rusak')->first();
        $this->assertNotNull($kendalaKetek);
        $this->assertEquals('terkendala', $kendalaKetek->status_pekerjaan);
        $this->assertEquals(0.00, (float) $kendalaKetek->luas_realisasi_ha);

        $kendalaMapping = $lap951->detail->where('status_pekerjaan', 'menunggu_mapping')->first();
        $this->assertNotNull($kendalaMapping);

        $prosesMapping = $lap951->detail->where('status_pekerjaan', 'proses_mapping')->first();
        $this->assertNotNull($prosesMapping);

        $kendalaFlight = $lap951->detail->where('status_pekerjaan', 'menunggu_flight_mission')->first();
        $this->assertNotNull($kendalaFlight);

        $kendalaMrs = $lap951->detail->where('keterangan_kendala', 'MRS Belom Jadi')->first();
        $this->assertNotNull($kendalaMrs);

        // Verifikasi multi-petak tgl 7 Juli (15.1 + 12.3 + 9.7 = 37.1 Ha)
        $tgl7Petaks = $lap951->detail->filter(fn ($d) => $d->tanggal_pekerjaan?->toDateString() === '2026-07-07');
        $this->assertCount(3, $tgl7Petaks);
        $this->assertEquals(37.10, round((float) $tgl7Petaks->sum('luas_realisasi_ha'), 2));
    }

    public function test_laporan_realisasi_drone_701_sesuai_rekap_bap_excel(): void
    {
        $lap701 = LaporanRealisasi::where('nomor_laporan', 'REAL/DSK/2026/08/701')
            ->with(['detail', 'tim'])
            ->firstOrFail();

        $this->assertEquals('Budi Santoso', $lap701->nama_pilot);
        $this->assertEquals('Rahmat', $lap701->nama_copilot);
        $this->assertEquals('Shobirin, Samsul', $lap701->nama_helper);
        $this->assertCount(2, $lap701->daftar_helper);

        // Verifikasi 12 pengerjaan petak
        $this->assertCount(12, $lap701->detail);

        // Total Luasan 248.00 Ha
        $totalLuas = $lap701->detail->sum('luas_realisasi_ha');
        $this->assertEquals(248.00, (float) $totalLuas);

        // Total Nilai Pekerjaan (248.00 Ha * Rp 340.000 / Ha = Rp 84.320.000 tarif per Ha atau sesuai SPK)
        $totalNilai = $lap701->detail->sum(fn ($d) => (float) $d->luas_realisasi_ha * (float) $d->tarif_per_ha);
        $this->assertEquals(84320000.00, $totalNilai);
    }

    public function test_transaksi_bbm_unit_951_sesuai_catatan_lapangan(): void
    {
        $drone951 = Drone::where('nomor_drone', '951')->firstOrFail();
        $tim951 = Tim::where('drone_id', $drone951->id)->firstOrFail();

        // Stok Masuk: 20 Ltr (Awal) + 320 Ltr + 300 Ltr = 640 Ltr
        $stokMasuk = TransaksiBbm::where('tim_id', $tim951->id)
            ->whereIn('jenis_transaksi', [TransaksiBbm::STOK_AWAL, TransaksiBbm::PENERIMAAN])
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->sum('jumlah_liter');
        $this->assertEquals(640.00, (float) $stokMasuk);

        // Penggunaan Mobil: 20 + 20 + 15 + 35 = 90 Ltr
        $pakaiMobil = TransaksiBbm::where('tim_id', $tim951->id)
            ->where('jenis_transaksi', TransaksiBbm::PENGGUNAAN)
            ->where('keperluan', 'mobil')
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->sum('jumlah_liter');
        $this->assertEquals(90.00, (float) $pakaiMobil);

        // Penggunaan Genset: 335 Ltr (dari 17 kali pengisian genset harian di petak)
        $pakaiGenset = TransaksiBbm::where('tim_id', $tim951->id)
            ->where('jenis_transaksi', TransaksiBbm::PENGGUNAAN)
            ->where('keperluan', 'genset')
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->sum('jumlah_liter');
        $this->assertEquals(335.00, (float) $pakaiGenset);

        // Total Penggunaan: 90 (Mobil) + 335 (Genset) = 425 Ltr
        $totalPenggunaan = TransaksiBbm::where('tim_id', $tim951->id)
            ->where('jenis_transaksi', TransaksiBbm::PENGGUNAAN)
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->sum('jumlah_liter');
        $this->assertEquals(425.00, (float) $totalPenggunaan);

        // Sisa Saldo Tersedia: 640 - 425 = 215 Ltr (Tepat sesuai fisik drum sisa 215 Ltr di catatan lapangan!)
        $sisaSaldo = $stokMasuk - $totalPenggunaan;
        $this->assertEquals(215.00, (float) $sisaSaldo);
    }

    public function test_kasbon_dan_pengeluaran_operasional_unit_tersedia_minimal_10_data(): void
    {
        $this->assertGreaterThanOrEqual(10, KasbonPersonil::count());
        $this->assertGreaterThanOrEqual(10, PengeluaranOperasionalUnit::count());

        // Verifikasi pengeluaran operasional unit mencakup kategori upah langsir helper lokal & perbaikan ketek
        $upahLokal = PengeluaranOperasionalUnit::where('kategori_biaya', PengeluaranOperasionalUnit::UPAH_LANGSIR)->first();
        $this->assertNotNull($upahLokal);

        $perbaikanKetek = PengeluaranOperasionalUnit::where('keterangan', 'like', '%perahu kanal%')->first();
        $this->assertNotNull($perbaikanKetek);
    }

    public function test_sidebar_admin_menampilkan_sub_grup_dan_penanda_anggota_tim(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();

        // Sub-grup visual Data Master
        $response->assertSee('Wilayah & Lahan');
        $response->assertSee('Armada & Personil');
        $response->assertSee('Standar & Tarif');
        $response->assertSee('Sistem & Logistik');

        // Indikator cabang hierarki Anggota Tim di bawah Tim
        $response->assertSee('sidebar__tautan--sub', false);
        $response->assertSee('sidebar__cabang-ikon', false);
    }

    public function test_halaman_jenis_pekerjaan_menampilkan_tarif_aktif_dan_tab_kembar(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('master.jenis-pekerjaan.index'));
        $response->assertOk();

        // Header kolom tarif aktif saat ini
        $response->assertSee('Tarif Aktif Saat Ini');
        $response->assertSee('Rp 340.000'); // Tarif Spreading
        $response->assertSee('Rp 175.000'); // Tarif Spraying

        // Tab navigasi kembar
        $response->assertSee('1. Katalog Jenis Pekerjaan');
        $response->assertSee('2. Standar & Riwayat Tarif', false);

        // Tombol cepat pintasan ke riwayat tarif
        $response->assertSee('master/tarif-pekerjaan?jenis_pekerjaan_id=', false);
    }

    public function test_halaman_tim_menampilkan_tab_kembar_dan_tombol_anggota(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('master.tim.index'));
        $response->assertOk();

        // Tab navigasi kembar Tim & Anggota
        $response->assertSee('1. Tim Operasional');
        $response->assertSee('2. Anggota Tim (Pilot/Co-Pilot)');

        // Tombol pintasan langsung ke anggota tim terkait
        $response->assertSee('master/anggota-tim?tim_id=', false);
    }

    public function test_halaman_tim_menampilkan_kartu_ringkasan_dan_filter_klik(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('master.tim.index'));
        $response->assertOk();

        // Verifikasi keberadaan kartu ringkasan
        $response->assertSee('Total Tim');
        $response->assertSee('Tim Aktif');
        $response->assertSee('Tim Nonaktif');
        $response->assertSee('Drone Bertugas');
        $response->assertSee('Pilot Ditugaskan');

        // Verifikasi tautan filter cepat pada kartu
        $response->assertSee('status_tim=aktif', false);
        $response->assertSee('status_tim=tidak_aktif', false);
    }

    public function test_halaman_petak_menampilkan_kartu_ringkasan_dan_luasan_total(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->firstOrFail();

        // Admin dilarang akses master petak (khusus Kepala Lapangan)
        $this->actingAs($admin)->get(route('master.petak.index'))->assertForbidden();

        // Kepala Lapangan berhak mengelola master petak
        $response = $this->actingAs($kepala)->get(route('master.petak.index'));
        $response->assertOk();

        // Verifikasi kartu ringkasan petak
        $response->assertSee('Total Petak');
        $response->assertSee('Luas Lahan Total');
        $response->assertSee('Petak Aktif');
        $response->assertSee('Petak Nonaktif');
        $response->assertSee('Sebaran Distrik');

        // Verifikasi filter klik pada kartu petak
        $response->assertSee('status_petak=aktif', false);
        $response->assertSee('status_petak=tidak_aktif', false);
    }

    public function test_halaman_drone_kartu_ringkasan_memiliki_filter_klik(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('master.drone.index'));
        $response->assertOk();

        // Verifikasi tautan filter pada kartu drone
        $response->assertSee('status_operasional=aktif', false);
        $response->assertSee('status_operasional=perawatan', false);
        $response->assertSee('status_operasional=tidak_beroperasi', false);
    }
}
