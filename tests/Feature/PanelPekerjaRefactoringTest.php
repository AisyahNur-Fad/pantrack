<?php

namespace Tests\Feature;

use App\Models\DetailSpk;
use App\Models\Distrik;
use App\Models\JenisPekerjaan;
use App\Models\LaporanRealisasi;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Petak;
use App\Models\Spk;
use App\Models\Tim;
use Tests\TestCase;

class PanelPekerjaRefactoringTest extends TestCase
{
    protected ?Pengguna $pekerja = null;
    protected ?Pengguna $kepalaLapangan = null;
    protected ?Pengguna $admin = null;
    protected ?Spk $spk = null;
    protected array $cleanupLaporanIds = [];
    protected array $cleanupSpkIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $this->kepalaLapangan = Pengguna::where('nama_pengguna', 'kepala')->first();
        $this->admin = Pengguna::where('nama_pengguna', 'admin')->first();

        $this->assertNotNull($this->pekerja, 'Pengguna pekerja harus ada di database.');
        $this->assertNotNull($this->kepalaLapangan, 'Pengguna kepala lapangan harus ada di database.');
        $this->assertNotNull($this->admin, 'Pengguna admin harus ada di database.');

        // Buat SPK percobaan untuk pengujian
        $distrik = Distrik::first() ?? Distrik::create(['nama_distrik' => 'Distrik Test', 'kode_distrik' => 'TST']);
        $tim = Tim::first() ?? Tim::create(['nama_tim' => 'Tim Test', 'kode_tim' => 'TST1', 'distrik_id' => $distrik->id]);
        $pekerjaan = JenisPekerjaan::where('tipe', 'utama')->first() ?? JenisPekerjaan::first();
        $petak = Petak::first() ?? Petak::create(['distrik_id' => $distrik->id, 'kode_petak' => 'PTK-TEST', 'luas_hektar' => 10.0]);

        // Pastikan pekerja terhubung ke tim
        if ($this->pekerja->tim_id !== $tim->id) {
            $this->pekerja->update(['tim_id' => $tim->id, 'distrik_id' => $distrik->id]);
        }

        $this->spk = Spk::create([
            'nomor_spk' => 'SPK-TEST-' . uniqid(),
            'tanggal_spk' => now()->toDateString(),
            'distrik_id' => $distrik->id,
            'tim_id' => $tim->id,
            'jenis_pekerjaan_id' => $pekerjaan->id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'status_spk' => Spk::DISETUJUI,
            'diinput_oleh' => $this->kepalaLapangan->id,
        ]);
        $this->cleanupSpkIds[] = $this->spk->id;

        DetailSpk::create([
            'spk_id' => $this->spk->id,
            'petak_id' => $petak->id,
            'luas_target_ha' => 15.0,
        ]);
    }

    protected function tearDown(): void
    {
        if (!empty($this->cleanupLaporanIds)) {
            LaporanRealisasi::whereIn('id', $this->cleanupLaporanIds)->delete();
        }

        if (!empty($this->cleanupSpkIds)) {
            DetailSpk::whereIn('spk_id', $this->cleanupSpkIds)->delete();
            Spk::whereIn('id', $this->cleanupSpkIds)->delete();
        }

        parent::tearDown();
    }

    public function test_fase_1_dashboard_pekerja_tidak_menampilkan_kalender(): void
    {
        $response = $this->actingAs($this->pekerja)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertDontSee('kalender-mini', false);
        $response->assertDontSee('Kalender Kerja Lapangan', false);
    }

    public function test_fase_1_pekerja_lapangan_dilarang_akses_riwayat_aktivitas_dan_menu_hilang(): void
    {
        // 1. Menu navigasi tidak menampilkan tautan Riwayat Aktivitas untuk semua peran
        foreach ([$this->pekerja, $this->kepalaLapangan, $this->admin] as $user) {
            $responseDash = $this->actingAs($user)->get(route('dashboard'));
            $responseDash->assertDontSee(route('riwayat.index'));
            $responseDash->assertDontSee('Riwayat Aktivitas');

            // 2. Akses langsung URL ditolak dengan 403 Forbidden untuk seluruh peran
            $responseUrl = $this->actingAs($user)->get(route('riwayat.index'));
            $responseUrl->assertStatus(403);
        }
    }

    public function test_fase_2_submit_realisasi_tanpa_nomor_laporan_dan_tanggal_input_terkunci_di_server(): void
    {
        $petak = Petak::first();
        $pekerjaan = JenisPekerjaan::where('tipe', 'utama')->first();

        $payload = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'nama_copilot' => 'Co-Pilot Bambang',
            'nama_helper' => ['Helper A', 'Helper B', 'Helper C', 'Helper D', 'Helper E'], // 5 helpers!
            'petak' => [
                [
                    'petak_id' => $petak->id,
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 12.5,
                    'status_pekerjaan' => 'selesai',
                    'keterangan_kendala' => 'Lancar',
                ],
            ],
            'ajukan' => 0, // simpan draf
        ];

        $response = $this->actingAs($this->pekerja)->post(route('realisasi.store'), $payload);

        // Ambil laporan yang baru dibuat
        $laporan = LaporanRealisasi::where('spk_id', $this->spk->id)->latest()->first();
        $this->assertNotNull($laporan);
        $this->cleanupLaporanIds[] = $laporan->id;

        $response->assertRedirect(route('realisasi.show', $laporan));

        // Tanggal input selalu tanggal hari ini dari server
        $this->assertEquals(now()->toDateString(), $laporan->tanggal_input?->toDateString());

        // Helper 5 orang tersimpan penuh tanpa terpotong
        $this->assertCount(5, $laporan->daftar_helper);
        $this->assertContains('Helper E', $laporan->daftar_helper);

        // Fallback nomor laporan otomatis SPK + Tanggal
        $this->assertStringContainsString($this->spk->nomor_spk, $laporan->nomor_laporan);
    }

    public function test_fase_2_pengecekan_harian_mencegah_duplikasi_spk_di_hari_yang_sama(): void
    {
        // Buat laporan pertama untuk hari ini
        $laporan1 = LaporanRealisasi::create([
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'tanggal_input' => now()->toDateString(),
            'status_laporan' => LaporanRealisasi::MENUNGGU_PEMERIKSAAN,
            'diinput_oleh' => $this->pekerja->id,
        ]);
        $this->cleanupLaporanIds[] = $laporan1->id;

        // Coba submit laporan kedua untuk SPK yang sama di hari yang sama
        $petak = Petak::first();
        $pekerjaan = JenisPekerjaan::where('tipe', 'utama')->first();

        $payload = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'petak' => [
                [
                    'petak_id' => $petak->id,
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 5.0,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
            'ajukan' => 1,
        ];

        $response = $this->actingAs($this->pekerja)->from(route('realisasi.create'))->post(route('realisasi.store'), $payload);
        $response->assertRedirect(route('realisasi.create'));
        $response->assertSessionHasErrors(['spk_id']);
    }

    public function test_fase_2_kunci_edit_laporan_dua_lapis_pekerja_hanya_bisa_edit_status_perlu_perbaikan(): void
    {
        $laporan = LaporanRealisasi::create([
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'tanggal_input' => now()->toDateString(),
            'status_laporan' => LaporanRealisasi::DRAF,
            'diinput_oleh' => $this->pekerja->id,
        ]);
        $this->cleanupLaporanIds[] = $laporan->id;

        // 1. Backend layer: Edit laporan draf oleh pekerja ditolak 403
        $this->actingAs($this->pekerja)->get(route('realisasi.edit', $laporan))->assertStatus(403);
        $this->actingAs($this->pekerja)->put(route('realisasi.update', $laporan), [])->assertStatus(403);

        // 2. Frontend layer: Tombol edit tidak muncul di index & show untuk draf
        $this->actingAs($this->pekerja)->get(route('realisasi.index'))->assertDontSee(route('realisasi.edit', $laporan));
        $this->actingAs($this->pekerja)->get(route('realisasi.show', $laporan))->assertDontSee(route('realisasi.edit', $laporan));

        // 3. Ketika Kepala Lapangan meminta revisi (perlu_perbaikan)
        $laporan->update([
            'status_laporan' => LaporanRealisasi::PERLU_PERBAIKAN,
            'catatan_pemeriksaan' => 'Mohon perbaiki luas realisasi petak.',
        ]);

        // 4. Backend layer: Sekarang pekerja diizinkan akses edit
        $this->actingAs($this->pekerja)->get(route('realisasi.edit', $laporan))->assertStatus(200);

        // 5. Frontend layer: Tombol edit muncul di index & show
        $this->actingAs($this->pekerja)->get(route('realisasi.index'))->assertSee(route('realisasi.edit', $laporan));
        $this->actingAs($this->pekerja)->get(route('realisasi.show', $laporan))->assertSee(route('realisasi.edit', $laporan));
    }

    public function test_fase_3_form_create_realisasi_memiliki_elemen_ui_yang_benar(): void
    {
        $response = $this->actingAs($this->pekerja)->get(route('realisasi.create'));
        $response->assertStatus(200);

        // 1. Tidak ada input nomor_laporan
        $response->assertDontSee('name="nomor_laporan"', false);

        // 2. Tanggal hari ini tampil rapi di atas tabel rincian
        $response->assertSee('Tanggal Hari Ini:');
        $response->assertSee(now()->translatedFormat('d F Y'));

        // 3. Tombol Muat Petak & Tambah Baris Petak sudah dihilangkan
        $response->assertDontSee('Muat Petak dari SPK');
        $response->assertDontSee('Tambah Baris Petak');

        // 4. Petak otomatis dari SPK (readonly abu-abu dan tidak ada dropdown petak bebas)
        $response->assertSee('placeholder="Otomatis dari SPK"', false);
        $response->assertSee('text-petak-tampil', false);

        // 5. Input luas_spk_ha berstatus readonly dan bergaya abu-abu
        $response->assertSee('cursor-not-allowed', false);
        $response->assertSee('readonly', false);

        // 6. Dropdown jenis pekerjaan hanya memuat pekerjaan pokok (tidak memuat Jasa Mapping yang merupakan add-on di kolom kanan)
        $response->assertDontSee('>Jasa Mapping Petak Drone</option>', false);
        $response->assertSee('🗺️ Mapping');
    }

    public function test_satu_spk_satu_petak_otomatis_terisi_ke_database(): void
    {
        $pekerjaan = JenisPekerjaan::where('tipe', 'utama')->first();

        // Submit form TANPA mengirim petak_id eksplisit (hanya mengirim data realisasi)
        $payload = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'petak' => [
                [
                    'petak_id' => '', // kosong, menguji pengisian otomatis dari SPK
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 8.5,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
            'ajukan' => 0,
        ];

        $response = $this->actingAs($this->pekerja)->post(route('realisasi.store'), $payload);
        $laporan = LaporanRealisasi::where('spk_id', $this->spk->id)->latest()->first();
        $this->assertNotNull($laporan);
        $this->cleanupLaporanIds[] = $laporan->id;

        // Pastikan detail realisasi memiliki petak_id yang sama persis dengan petak target SPK
        $targetSpkPetakId = $this->spk->detail()->first()?->petak_id;
        $this->assertNotNull($targetSpkPetakId);

        $detailRealisasi = $laporan->detail()->first();
        $this->assertNotNull($detailRealisasi);
        $this->assertEquals($targetSpkPetakId, $detailRealisasi->petak_id);
    }

    public function test_pengisian_bbm_dan_kendala_wajib(): void
    {
        $pekerjaan = JenisPekerjaan::where('tipe', 'utama')->first();

        // 1. Submit tanpa memilih BBM / jika memilih tanpa BBM tapi keterangan kosong -> ditolak validasi
        $payloadTanpaAlasanBbm = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'ada_bbm' => 0,
            'keterangan_tanpa_bbm' => '', // kosong, harus error
            'ada_kendala' => 0,
            'petak' => [
                [
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 10.0,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
            'ajukan' => 0,
        ];
        $respErrorBbm = $this->actingAs($this->pekerja)->from(route('realisasi.create'))->post(route('realisasi.store'), $payloadTanpaAlasanBbm);
        $respErrorBbm->assertSessionHasErrors('keterangan_tanpa_bbm');

        // 2. Submit jika ada kendala tapi rincian kendala kosong -> ditolak validasi
        $payloadTanpaRincianKendala = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'ada_bbm' => 0,
            'keterangan_tanpa_bbm' => 'Baterai di-charge semalam di mess',
            'ada_kendala' => 1,
            'catatan_kendala' => '', // kosong, harus error
            'petak' => [
                [
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 2.0,
                    'status_pekerjaan' => 'terkendala',
                ],
            ],
            'ajukan' => 0,
        ];
        $respErrorKendala = $this->actingAs($this->pekerja)->from(route('realisasi.create'))->post(route('realisasi.store'), $payloadTanpaRincianKendala);
        $respErrorKendala->assertSessionHasErrors('catatan_kendala');

        // 3. Submit valid dengan tanpa BBM (ada alasan) dan ada kendala (ada catatan) -> sukses tersimpan
        $payloadSukses = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'ada_bbm' => 0,
            'keterangan_tanpa_bbm' => 'Baterai di-charge di mess, genset tidak digunakan',
            'ada_kendala' => 1,
            'catatan_kendala' => 'Hujan badai mulai pukul 11:00 sehingga penerbangan dihentikan',
            'petak' => [
                [
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 4.5,
                    'status_pekerjaan' => 'terkendala',
                ],
            ],
            'ajukan' => 0,
        ];
        $respSukses = $this->actingAs($this->pekerja)->post(route('realisasi.store'), $payloadSukses);
        $laporan = LaporanRealisasi::where('spk_id', $this->spk->id)->latest()->first();
        $this->assertNotNull($laporan);
        $this->cleanupLaporanIds[] = $laporan->id;

        $this->assertFalse((bool) $laporan->ada_bbm);
        $this->assertEquals('Baterai di-charge di mess, genset tidak digunakan', $laporan->keterangan_tanpa_bbm);
        $this->assertTrue((bool) $laporan->ada_kendala);
        $this->assertEquals('Hujan badai mulai pukul 11:00 sehingga penerbangan dihentikan', $laporan->catatan_kendala);
        $this->assertCount(0, $laporan->transaksiBbm);

        // Halaman detail menampilkan informasi kendala dan alasan tanpa bbm
        $respShow = $this->actingAs($this->pekerja)->get(route('realisasi.show', $laporan));
        $respShow->assertSee('Terdapat Kendala');
        $respShow->assertSee('Hujan badai mulai pukul 11:00');
        $respShow->assertSee('Tidak Ada Penggunaan BBM');
        $respShow->assertSee('Baterai di-charge di mess');
    }

    public function test_kendala_total_bisa_input_luas_nol_sedangkan_lancar_wajib_lebih_dari_nol(): void
    {
        $pekerjaan = JenisPekerjaan::where('tipe', 'utama')->first();

        // 1. Jika operasional lancar tapi luas diisi 0.00 -> ditolak validasi
        $payloadLancarNol = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'ada_bbm' => 0,
            'keterangan_tanpa_bbm' => 'Baterai di-charge via listrik PLN',
            'ada_kendala' => 0,
            'petak' => [
                [
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 0.00,
                ],
            ],
            'ajukan' => 0,
        ];
        $respLancarNol = $this->actingAs($this->pekerja)->from(route('realisasi.create'))->post(route('realisasi.store'), $payloadLancarNol);
        $respLancarNol->assertSessionHasErrors('petak.0.luas_realisasi_ha');

        // 2. Jika ada kendala (batal terbang / terhenti total), luas 0.00 Ha berhasil disimpan dengan status terkendala
        $payloadKendalaTotal = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Pilot Anton',
            'ada_bbm' => 0,
            'keterangan_tanpa_bbm' => 'Operasional batal terbang / libur operasional',
            'ada_kendala' => 1,
            'catatan_kendala' => 'Hujan badai seharian penuh sejak pagi, drone tidak bisa lepas landas',
            'petak' => [
                [
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 0.00,
                ],
            ],
            'ajukan' => 0,
        ];
        $respKendalaTotal = $this->actingAs($this->pekerja)->post(route('realisasi.store'), $payloadKendalaTotal);
        $laporan = LaporanRealisasi::where('spk_id', $this->spk->id)->latest()->first();
        $this->assertNotNull($laporan);
        $this->cleanupLaporanIds[] = $laporan->id;

        $respKendalaTotal->assertRedirect(route('realisasi.show', $laporan));
        $this->assertEquals(0.00, (float)$laporan->detail->first()->luas_realisasi_ha);
        $this->assertEquals('terkendala', $laporan->detail->first()->status_pekerjaan);
    }

    public function test_fase_4_transparansi_pendapatan_kru_lapangan_dan_alokasi_admin(): void
    {
        $pekerjaan = JenisPekerjaan::where('tipe', 'utama')->first();

        // 1. Buat laporan realisasi dengan helper
        $payload = [
            'spk_id' => $this->spk->id,
            'distrik_id' => $this->spk->distrik_id,
            'periode_bulan' => now()->month,
            'periode_tahun' => now()->year,
            'tim_id' => $this->spk->tim_id,
            'nama_pilot' => 'Budi Santoso (Pekerja)',
            'nama_helper' => 'Rian Kurniawan',
            'ada_bbm' => 0,
            'keterangan_tanpa_bbm' => 'Baterai diisi daya genset posko',
            'ada_kendala' => 0,
            'petak' => [
                [
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 10.00,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
            'ajukan' => 0,
        ];

        $postResp = $this->actingAs($this->pekerja)->post(route('realisasi.store'), $payload);
        $laporan = LaporanRealisasi::where('spk_id', $this->spk->id)->latest()->first();
        $this->assertNotNull($laporan);
        $this->cleanupLaporanIds[] = $laporan->id;

        // 2. Tampilan Pekerja Lapangan: Transparan hak kru, tanpa potongan korporat
        $respPekerja = $this->actingAs($this->pekerja)->get(route('realisasi.show', $laporan));
        $respPekerja->assertOk();
        $respPekerja->assertSee('Hak dan Estimasi Pendapatan Kru Lapangan');
        $respPekerja->assertSee('Luas Dikerjakan: 10,00 Ha');
        $respPekerja->assertSee('Gaji Pokok (Tetap):');
        $respPekerja->assertSee('Premi Terbang');
        $respPekerja->assertSee('Estimasi Subtotal Hak Pilot:');
        $respPekerja->assertSee('Estimasi Jatah Tunai Helper:');
        $respPekerja->assertSee('Helper: Rian Kurniawan');
        $respPekerja->assertDontSee('Potongan Administrasi (ADM 5%)');
        $respPekerja->assertDontSee('Bagi Hasil Investor (15%)');
        $respPekerja->assertDontSee('Total Bruto:');

        // 3. Tampilan Admin Sistem: Lengkap dengan potongan korporat dan skema helper
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $respAdmin = $this->actingAs($admin)->get(route('realisasi.show', $laporan));
        $respAdmin->assertOk();
        $respAdmin->assertSee('Rincian Alokasi Pendapatan Pekerjaan');
        $respAdmin->assertSee('Total Bruto:');
        $respAdmin->assertSee('Potongan Administrasi (ADM 5%)');
        $respAdmin->assertSee('Bagi Hasil Investor (15%)');
        $respAdmin->assertSee('Sisa Bersih Operasional (80%)');
        $respAdmin->assertSee('Estimasi Jatah Tunai Helper Lapangan:');
    }

    public function test_status_donut_dashboard_dapat_diklik_dan_langsung_ke_halaman_filter_realisasi(): void
    {
        // 1. Dashboard memuat link status langsung ke realisasi
        $responseDasbor = $this->actingAs($this->pekerja)->get(route('dashboard'));
        $responseDasbor->assertOk();
        $responseDasbor->assertSee('Status Laporan Realisasi Tim Saya');
        $responseDasbor->assertSee(route('realisasi.index', ['status' => 'draf', 'bulan' => now()->month, 'tahun' => now()->year]));
        $responseDasbor->assertSee(route('realisasi.index', ['status' => 'menunggu_pemeriksaan', 'bulan' => now()->month, 'tahun' => now()->year]));
        $responseDasbor->assertSee(route('realisasi.index', ['status' => 'perlu_perbaikan', 'bulan' => now()->month, 'tahun' => now()->year]));
        $responseDasbor->assertSee(route('realisasi.index', ['status' => 'disetujui', 'bulan' => now()->month, 'tahun' => now()->year]));
        $responseDasbor->assertSee(route('realisasi.index', ['status' => 'ditolak', 'bulan' => now()->month, 'tahun' => now()->year]));

        // 2. Klik link draf membuka halaman realisasi dengan filter draf aktif
        $respDraf = $this->actingAs($this->pekerja)->get(route('realisasi.index', [
            'status' => 'draf',
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]));
        $respDraf->assertOk();
        $respDraf->assertSee('Menampilkan filter status:');
        $respDraf->assertSee('Draf');

        // 3. Klik link disetujui membuka halaman realisasi dengan filter disetujui aktif
        $respDisetujui = $this->actingAs($this->pekerja)->get(route('realisasi.index', [
            'status' => 'disetujui',
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]));
        $respDisetujui->assertOk();
        $respDisetujui->assertSee('Menampilkan filter status:');
        $respDisetujui->assertSee('Disetujui');
    }

    public function test_dashboard_kepala_lapangan_memiliki_status_donut_stacked_bar_dan_komparasi_tim_interaktif(): void
    {
        $response = $this->actingAs($this->kepalaLapangan)->get(route('dashboard'));
        $response->assertOk();

        // 1. Memastikan judul bagian status laporan memuat periode
        $response->assertSee('Status Laporan Realisasi');

        // 2. Memastikan proportional stacked bar (chart-bar-tumpuk) ada
        $response->assertSee('chart-bar-tumpuk');

        // 3. Memastikan link status donut & bar tumpuk mengarah ke filter realisasi dengan periode
        $response->assertSee(route('realisasi.index', ['status' => 'draf', 'bulan' => now()->month, 'tahun' => now()->year]));
        $response->assertSee(route('realisasi.index', ['status' => 'menunggu_pemeriksaan', 'bulan' => now()->month, 'tahun' => now()->year]));
        $response->assertSee(route('realisasi.index', ['status' => 'disetujui', 'bulan' => now()->month, 'tahun' => now()->year]));

        // 4. Memastikan kartu KPI Laporan Masuk dan bar perbandingan tim memiliki tautan interaktif
        $response->assertSee(route('realisasi.index', ['bulan' => now()->month, 'tahun' => now()->year]));

        // 5. Memastikan widget kalender mini sudah dihapus
        $response->assertDontSee('kalender-mini');
    }
}
