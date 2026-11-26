<?php

namespace Tests\Feature;

use App\Models\DetailSpk;
use App\Models\Distrik;
use App\Models\JenisPekerjaan;
use App\Models\Pengguna;
use App\Models\Petak;
use App\Models\Pt;
use App\Models\Spk;
use App\Models\TarifPekerjaan;
use App\Models\Tim;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KatalogJenisPekerjaanDanVarianTest extends TestCase
{
    private Pengguna $admin;
    private Pengguna $kepala;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();
        $this->kepala = Pengguna::where('nama_pengguna', 'kepala')->firstOrFail();
    }

    public function test_database_memiliki_kolom_tipe_pada_tabel_jenis_pekerjaan(): void
    {
        $this->assertTrue(Schema::hasColumn('jenis_pekerjaan', 'tipe'));
    }

    public function test_katalog_memiliki_6_varian_resmi_dengan_tipe_utama_dan_addon_beserta_tarif(): void
    {
        // 1. Verifikasi Varian Spraying HPT (Rp 239.500)
        $spyHpt = JenisPekerjaan::where('kode_pekerjaan', 'SPY-HPT')->first();
        $this->assertNotNull($spyHpt);
        $this->assertEquals(JenisPekerjaan::TIPE_UTAMA, $spyHpt->tipe);
        $this->assertEquals(239500.00, (float) $spyHpt->tarifAktif?->nilai_tarif);

        // 2. Verifikasi Varian Spraying HPT + CW 1 (Rp 239.500)
        $spyCw1 = JenisPekerjaan::where('kode_pekerjaan', 'SPY-HPT-CW1')->first();
        $this->assertNotNull($spyCw1);
        $this->assertEquals(JenisPekerjaan::TIPE_UTAMA, $spyCw1->tipe);
        $this->assertEquals(239500.00, (float) $spyCw1->tarifAktif?->nilai_tarif);

        // 3. Verifikasi Varian Spraying HPT 2 + CW 2 (Rp 239.500)
        $spyCw2 = JenisPekerjaan::where('kode_pekerjaan', 'SPY-HPT2-CW2')->first();
        $this->assertNotNull($spyCw2);
        $this->assertEquals(JenisPekerjaan::TIPE_UTAMA, $spyCw2->tipe);
        $this->assertEquals(239500.00, (float) $spyCw2->tarifAktif?->nilai_tarif);

        // 4. Verifikasi Varian Spreading PS1 Standar (Rp 300.000)
        $spdPs1 = JenisPekerjaan::where('kode_pekerjaan', 'SPD-PS1')->first();
        $this->assertNotNull($spdPs1);
        $this->assertEquals(JenisPekerjaan::TIPE_UTAMA, $spdPs1->tipe);
        $this->assertEquals(300000.00, (float) $spdPs1->tarifAktif?->nilai_tarif);

        // 5. Verifikasi Varian Spreading PS1 Marine Clay (Rp 340.000)
        $spdPs1Mc = JenisPekerjaan::where('kode_pekerjaan', 'SPD-PS1-MC')->first();
        $this->assertNotNull($spdPs1Mc);
        $this->assertEquals(JenisPekerjaan::TIPE_UTAMA, $spdPs1Mc->tipe);
        $this->assertEquals(340000.00, (float) $spdPs1Mc->tarifAktif?->nilai_tarif);

        // 6. Verifikasi Jasa Mapping Petak Drone (Rp 30.000, Add-on)
        $srvMapping = JenisPekerjaan::where('kode_pekerjaan', 'SRV-MAPPING')->first();
        $this->assertNotNull($srvMapping);
        $this->assertEquals(JenisPekerjaan::TIPE_ADDON, $srvMapping->tipe);
        $this->assertTrue($srvMapping->is_addon);
        $this->assertEquals(30000.00, (float) $srvMapping->tarifAktif?->nilai_tarif);
    }

    public function test_scope_utama_dan_scope_addon_bekerja_dengan_benar(): void
    {
        $utamaList = JenisPekerjaan::utama()->pluck('kode_pekerjaan')->all();
        $addonList = JenisPekerjaan::addon()->pluck('kode_pekerjaan')->all();

        $this->assertContains('SPY-HPT', $utamaList);
        $this->assertContains('SPY-HPT-CW1', $utamaList);
        $this->assertContains('SPY-HPT2-CW2', $utamaList);
        $this->assertContains('SPD-PS1', $utamaList);
        $this->assertContains('SPD-PS1-MC', $utamaList);
        $this->assertNotContains('SRV-MAPPING', $utamaList);

        $this->assertContains('SRV-MAPPING', $addonList);
        $this->assertNotContains('SPY-HPT', $addonList);
    }

    public function test_admin_bisa_tambah_dan_ubah_jenis_pekerjaan_dengan_pilihan_tipe(): void
    {
        $kodeBaru = 'TEST-ADDON-' . uniqid();

        // 1. Tambah data baru bertipe addon
        $resStore = $this->actingAs($this->admin)->post(route('master.jenis-pekerjaan.store'), [
            'kode_pekerjaan' => $kodeBaru,
            'nama_pekerjaan' => 'Layanan Langsir Air',
            'tipe' => JenisPekerjaan::TIPE_ADDON,
            'keterangan' => 'Layanan pendukung langsir air petak jauh',
            'aktif' => 1,
        ]);
        $resStore->assertRedirect(route('master.jenis-pekerjaan.index'));

        $record = JenisPekerjaan::where('kode_pekerjaan', $kodeBaru)->first();
        $this->assertNotNull($record);
        $this->assertEquals(JenisPekerjaan::TIPE_ADDON, $record->tipe);

        // 2. Ubah data kembali
        $resUpdate = $this->actingAs($this->admin)->put(route('master.jenis-pekerjaan.update', $record), [
            'kode_pekerjaan' => $kodeBaru,
            'nama_pekerjaan' => 'Layanan Langsir Air Updated',
            'tipe' => JenisPekerjaan::TIPE_UTAMA,
            'keterangan' => 'Diubah jadi utama',
            'aktif' => 1,
        ]);
        $resUpdate->assertRedirect(route('master.jenis-pekerjaan.index'));

        $record->refresh();
        $this->assertEquals(JenisPekerjaan::TIPE_UTAMA, $record->tipe);
        $this->assertEquals('Layanan Langsir Air Updated', $record->nama_pekerjaan);

        // Hapus data uji coba
        $record->delete();
    }

    public function test_halaman_katalog_menampilkan_dua_tabel_terpisah_pokok_dan_addon(): void
    {
        $response = $this->actingAs($this->admin)->get(route('master.jenis-pekerjaan.index'));
        $response->assertOk();

        // 1. Verifikasi Kartu Ringkasan
        $response->assertSee('Total Katalog');
        $response->assertSee('🌾 Pekerjaan Pokok');
        $response->assertSee('🗺️ Layanan Add-on');
        $response->assertSee('🟢 Layanan Aktif');

        // 2. Verifikasi Header Dua Tabel Terpisah
        $response->assertSee('1. Pekerjaan Pokok / Utama (Aplikasi Drone Lapangan)');
        $response->assertSee('2. Layanan & Biaya Tambahan (Add-on / Opsi Lapangan)', false);

        // 3. Verifikasi Isi Tabel Pekerjaan Pokok
        $response->assertSee('Spraying - HPT');
        $response->assertSee('Spraying - HPT + CW 1');
        $response->assertSee('Spraying - HPT 2 + CW 2');
        $response->assertSee('Spreading - PS1 (Standar)');
        $response->assertSee('Spreading - PS1 Marine Clay');
        $response->assertSee('Rp 239.500');
        $response->assertSee('Rp 300.000');
        $response->assertSee('Rp 340.000');

        // 4. Verifikasi Isi Tabel Layanan Tambahan Add-on
        $response->assertSee('Jasa Mapping Petak Drone');
        $response->assertSee('SRV-MAPPING');
        $response->assertSee('Rp 30.000');
        $response->assertSee('Jasa Add-on');
    }

    public function test_halaman_tarif_menampilkan_dua_tabel_terpisah_aktif_dan_riwayat(): void
    {
        $response = $this->actingAs($this->admin)->get(route('master.tarif-pekerjaan.index'));
        $response->assertOk();

        // 1. Verifikasi Kartu Ringkasan Tarif
        $response->assertSee('Total Record Tarif');
        $response->assertSee('🟢 Tarif Aktif Saat Ini');
        $response->assertSee('📜 Riwayat & Arsip Tarif', false);
        $response->assertSee('📋 Layanan Terdaftar');

        // 2. Verifikasi Header Dua Tabel Terpisah
        $response->assertSee('1. Tarif Berlaku Saat Ini (Standar Aktif)');
        $response->assertSee('2. Riwayat & Arsip Perubahan Tarif (Sebelum Diubah)', false);

        // 3. Verifikasi Tabel 1: Tarif Aktif
        $response->assertSee('Aktif Berlaku');
        $response->assertSee('Rp 239.500');
        $response->assertSee('Rp 300.000');
        $response->assertSee('Rp 340.000');
        $response->assertSee('Rp 30.000');

        // 4. Verifikasi Tabel 2: Riwayat Tarif (Arsip 2025)
        $response->assertSee('Arsip / Nonaktif');
        $response->assertSee('Rp 280.000');
        $response->assertSee('Rp 220.000');
        $response->assertSee('Arsip: Tarif Spreading PS1 tahun 2025');
        $response->assertSee('Arsip: Tarif Spraying HPT tahun 2025');
    }

    public function test_filter_dan_cari_bekerja_pada_halaman_tarif_dua_tabel(): void
    {
        // 1. Filter berdasarkan jenis pekerjaan (contoh: SPD-PS1)
        $ps1 = JenisPekerjaan::where('kode_pekerjaan', 'SPD-PS1')->firstOrFail();
        $resFilter = $this->actingAs($this->admin)->get(route('master.tarif-pekerjaan.index', [
            'jenis_pekerjaan_id' => $ps1->id,
        ]));
        $resFilter->assertOk();
        $resFilter->assertSee('Rp 300.000');
        $resFilter->assertSee('Rp 280.000');
        // Tidak menampilkan pekerjaan lain
        $resFilter->assertDontSee('SRV-MAPPING');

        // 2. Cari dengan kata kunci "Mapping"
        $resCari = $this->actingAs($this->admin)->get(route('master.tarif-pekerjaan.index', [
            'cari' => 'Mapping',
        ]));
        $resCari->assertOk();
        $resCari->assertSee('SRV-MAPPING');
        $resCari->assertSee('Rp 30.000');
        $resCari->assertDontSee('SPD-PS1-MC');
    }

    public function test_alur_ganti_tarif_otomatis_mengarsipkan_tarif_sebelumnya_ke_tabel_riwayat(): void
    {
        $srvMapping = JenisPekerjaan::where('kode_pekerjaan', 'SRV-MAPPING')->firstOrFail();
        $tarifLama = TarifPekerjaan::where('jenis_pekerjaan_id', $srvMapping->id)
            ->whereNull('berlaku_sampai')
            ->firstOrFail();

        $tglMulaiBaru = now()->addDays(5)->toDateString();
        $tglTutupSeharusnya = now()->addDays(4)->toDateString();

        // 1. Eksekusi alur penetapan tarif baru
        $response = $this->actingAs($this->admin)->post(route('master.tarif-pekerjaan.store'), [
            'jenis_pekerjaan_id' => $srvMapping->id,
            'nilai_tarif' => 35000.00,
            'berlaku_mulai' => $tglMulaiBaru,
            'keterangan' => 'Penyesuaian tarif pemetaan drone baru',
        ]);

        $response->assertRedirect(route('master.tarif-pekerjaan.index'));
        $response->assertSessionHas('sukses');

        // 2. Verifikasi tarif lama otomatis ditutup (berlaku_sampai = H-1) dan diarsipkan
        $tarifLama->refresh();
        $this->assertEquals($tglTutupSeharusnya, $tarifLama->berlaku_sampai?->toDateString());
        $this->assertStringContainsString('Otomatis diarsipkan saat penetapan tarif baru', (string) $tarifLama->keterangan);

        // 3. Verifikasi tarif baru tersimpan dengan baik
        $tarifBaru = TarifPekerjaan::where('jenis_pekerjaan_id', $srvMapping->id)
            ->where('berlaku_mulai', $tglMulaiBaru)
            ->first();
        $this->assertNotNull($tarifBaru);
        $this->assertEquals(35000.00, (float) $tarifBaru->nilai_tarif);
        $this->assertNull($tarifBaru->berlaku_sampai);

        // 4. Cleanup
        $tarifBaru->delete();
        $tarifLama->update([
            'berlaku_sampai' => null,
            'keterangan' => 'Tarif resmi Jasa Mapping Petak Drone (Rp 30.000 / Ha)',
        ]);
    }

    public function test_tarif_yang_sudah_digunakan_spk_terkunci_secara_immutable_dari_perubahan_nominal(): void
    {
        // Cari jenis pekerjaan yang sudah dipakai SPK (misal SPK pertama)
        $spk = Spk::whereNotNull('jenis_pekerjaan_id')->firstOrFail();
        $tarif = TarifPekerjaan::where('jenis_pekerjaan_id', $spk->jenis_pekerjaan_id)
            ->where('berlaku_mulai', '<=', $spk->tanggal_spk)
            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $spk->tanggal_spk))
            ->firstOrFail();

        $this->assertTrue($tarif->digunakanDiSpk());
        $nilaiAwal = (float) $tarif->nilai_tarif;

        // Coba ubah nominal tarif yang sudah terkunci oleh SPK
        $response = $this->actingAs($this->admin)->put(route('master.tarif-pekerjaan.update', $tarif), [
            'jenis_pekerjaan_id' => $tarif->jenis_pekerjaan_id,
            'nilai_tarif' => $nilaiAwal + 50000,
            'berlaku_mulai' => $tarif->berlaku_mulai->toDateString(),
            'berlaku_sampai' => $tarif->berlaku_sampai?->toDateString(),
            'keterangan' => 'Mencoba paksa ganti nominal',
        ]);

        $response->assertSessionHasErrors(['nilai_tarif']);
        $tarif->refresh();
        $this->assertEquals($nilaiAwal, (float) $tarif->nilai_tarif);
    }

    public function test_tarif_yang_sudah_digunakan_spk_tidak_bisa_dihapus_tetapi_tarif_tanpa_spk_bisa_dihapus(): void
    {
        // 1. Tarif yang dipakai SPK ditolak saat akan dihapus
        $spk = Spk::whereNotNull('jenis_pekerjaan_id')->firstOrFail();
        $tarifTerkunci = TarifPekerjaan::where('jenis_pekerjaan_id', $spk->jenis_pekerjaan_id)
            ->where('berlaku_mulai', '<=', $spk->tanggal_spk)
            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $spk->tanggal_spk))
            ->firstOrFail();

        $resHapusKunci = $this->actingAs($this->admin)->delete(route('master.tarif-pekerjaan.destroy', $tarifTerkunci));
        $resHapusKunci->assertSessionHas('galat');
        $this->assertDatabaseHas('tarif_pekerjaan', ['id' => $tarifTerkunci->id]);

        // 2. Buat tarif percobaan tanpa SPK -> bisa dihapus
        $tarifBebas = TarifPekerjaan::create([
            'jenis_pekerjaan_id' => $spk->jenis_pekerjaan_id,
            'nilai_tarif' => 99999.00,
            'berlaku_mulai' => '2099-01-01',
            'berlaku_sampai' => '2099-12-31',
            'keterangan' => 'Tarif uji coba masa depan',
            'dibuat_oleh' => $this->admin->id,
        ]);

        $resHapusBebas = $this->actingAs($this->admin)->delete(route('master.tarif-pekerjaan.destroy', $tarifBebas));
        $resHapusBebas->assertSessionHas('sukses');
        $this->assertDatabaseMissing('tarif_pekerjaan', ['id' => $tarifBebas->id]);

        // 3. Tampilan form memuat panduan Auto-Archive & Kunci Integritas
        $resCreate = $this->actingAs($this->admin)->get(route('master.tarif-pekerjaan.create'));
        $resCreate->assertOk();
        $resCreate->assertSee('Alur Pembaruan Tarif Otomatis (Auto-Archive)');

        $resEditKunci = $this->actingAs($this->admin)->get(route('master.tarif-pekerjaan.edit', $tarifTerkunci));
        $resEditKunci->assertOk();
        $resEditKunci->assertSee('Integritas Keuangan: Tarif Terkunci SPK');
    }

    public function test_dropdown_spk_create_hanya_menampilkan_pekerjaan_pokok_tanpa_addon_mapping(): void
    {
        $response = $this->actingAs($this->kepala)->get(route('spk.create'));
        $response->assertOk();

        // 1. Memuat pekerjaan pokok di dropdown
        $response->assertSee('Spraying - HPT');
        $response->assertSee('Spreading - PS1 (Standar)');
        $response->assertSee('Spreading - PS1 Marine Clay');

        // 2. Tidak memuat varian addon/mapping di dropdown utama
        $response->assertDontSee('<option value="" >Jasa Mapping Petak Drone</option>', false);
        $response->assertDontSee('SRV-MAPPING');

        // 3. Kolom mapping dan live kalkulator ada pada tabel petak
        $response->assertSee('Layanan Mapping (+Rp 30.000/Ha)');
        $response->assertSee('Estimasi Anggaran Target SPK:');
        $response->assertSee('🗺️ Butuh Mapping');
    }

    public function test_kepala_lapangan_bisa_buat_spk_dengan_opsi_mapping_petak_kondisional_dan_anggaran_terkalkulasi(): void
    {
        $distrik = Distrik::where('kode_distrik', 'DSR')->orWhere('nama_distrik', 'like', '%DSR%')->firstOrFail();
        $ps1 = JenisPekerjaan::where('kode_pekerjaan', 'SPD-PS1')->firstOrFail();

        $petak1 = Petak::where('distrik_id', $distrik->id)->firstOrFail();
        $petak2 = Petak::where('distrik_id', $distrik->id)->where('id', '!=', $petak1->id)->firstOrFail();

        $nomorSpk = 'SPK-MAP-TEST-' . uniqid();

        // Buat SPK: Petak 1 butuh mapping (petak baru), Petak 2 tanpa mapping (petak lama)
        $payload = [
            'nomor_spk' => $nomorSpk,
            'tanggal_spk' => '2026-11-20',
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'pt_id' => $distrik->pt_id,
            'distrik_id' => $distrik->id,
            'jenis_pekerjaan_id' => $ps1->id,
            'keterangan' => 'Uji coba SPK mapping petak',
            'petak' => [
                [
                    'petak_id' => $petak1->id,
                    'luas_target_ha' => 10.00,
                    'perlu_mapping' => 1,
                    'catatan' => 'Petak baru butuh mapping',
                ],
                [
                    'petak_id' => $petak2->id,
                    'luas_target_ha' => 15.00,
                    'perlu_mapping' => 0,
                    'catatan' => 'Petak lama tanpa mapping',
                ],
            ],
        ];

        $resStore = $this->actingAs($this->kepala)->post(route('spk.store'), $payload);
        $resStore->assertRedirect();

        $spk = Spk::where('nomor_spk', $nomorSpk)->with('detail')->firstOrFail();

        // 1. Verifikasi Detail Petak 1 (Dengan Mapping)
        $detail1 = $spk->detail->where('petak_id', $petak1->id)->firstOrFail();
        $this->assertTrue($detail1->perlu_mapping);
        $this->assertEquals(300000.00, (float) $detail1->tarif_per_ha);
        $this->assertEquals(30000.00, (float) $detail1->tarif_mapping_per_ha);
        $this->assertEquals(3000000.00, $detail1->subtotal_pokok);
        $this->assertEquals(300000.00, $detail1->subtotal_mapping);
        $this->assertEquals(3300000.00, $detail1->subtotal_biaya);

        // 2. Verifikasi Detail Petak 2 (Tanpa Mapping)
        $detail2 = $spk->detail->where('petak_id', $petak2->id)->firstOrFail();
        $this->assertFalse($detail2->perlu_mapping);
        $this->assertEquals(300000.00, (float) $detail2->tarif_per_ha);
        $this->assertNull($detail2->tarif_mapping_per_ha);
        $this->assertEquals(4500000.00, $detail2->subtotal_pokok);
        $this->assertEquals(0.00, $detail2->subtotal_mapping);
        $this->assertEquals(4500000.00, $detail2->subtotal_biaya);

        // 3. Verifikasi Total SPK
        $this->assertEquals(25.00, $spk->total_luas_target);
        $this->assertEquals(10.00, $spk->total_luas_mapping);
        $this->assertTrue($spk->ada_layanan_mapping);
        $this->assertEquals(7500000.00, $spk->total_biaya_pokok);
        $this->assertEquals(300000.00, $spk->total_biaya_mapping);
        $this->assertEquals(7800000.00, $spk->total_estimasi_biaya);

        // 4. Verifikasi Halaman Tampil SPK (show)
        $resShow = $this->actingAs($this->kepala)->get(route('spk.show', $spk));
        $resShow->assertOk();
        $resShow->assertSee('Termasuk Mapping (10,00 Ha)');
        $resShow->assertSee('Rp 7.800.000');
        $resShow->assertSee('Rp 3.300.000');
        $resShow->assertSee('Rp 4.500.000');

        // Cleanup
        $spk->detail()->delete();
        $spk->delete();
    }

    public function test_realisasi_mencatat_mapping_dan_sinkron_ke_ekspor_7sheet_dan_pdf(): void
    {
        $ps1 = JenisPekerjaan::where('kode_pekerjaan', 'SPD-PS1')->firstOrFail();
        $distrik = Distrik::firstOrFail();
        $petaks = Petak::where('distrik_id', $distrik->id)->take(2)->get();
        if ($petaks->count() < 2) {
            $petaks = Petak::take(2)->get();
        }
        $petak1 = $petaks[0];
        $petak2 = $petaks[1];
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->firstOrFail();
        $idTimPekerja = $pekerja->idTimYangDiakses() ?? [];
        $tim = Tim::where('status_tim', 'aktif')
            ->when(! empty($idTimPekerja), fn ($q) => $q->whereIn('id', $idTimPekerja))
            ->firstOrFail();

        // 1. Buat SPK Disetujui dengan 2 petak (Petak 1 ada mapping, Petak 2 tidak)
        $nomorSpk = 'SPK-REALISASI-MAP-' . uniqid();
        $spk = Spk::create([
            'nomor_spk' => $nomorSpk,
            'tanggal_spk' => now()->toDateString(),
            'tim_id' => $tim->id,
            'periode_bulan' => (int) now()->format('m'),
            'periode_tahun' => (int) now()->format('Y'),
            'pt_id' => $distrik->pt_id,
            'distrik_id' => $distrik->id,
            'jenis_pekerjaan_id' => $ps1->id,
            'status_spk' => Spk::DISETUJUI,
            'diinput_oleh' => $this->kepala->id,
            'disetujui_oleh' => $this->kepala->id,
        ]);

        DetailSpk::create([
            'spk_id' => $spk->id,
            'petak_id' => $petak1->id,
            'luas_target_ha' => 10.00,
            'perlu_mapping' => true,
            'tarif_per_ha' => 300000.00,
            'tarif_mapping_per_ha' => 30000.00,
        ]);

        DetailSpk::create([
            'spk_id' => $spk->id,
            'petak_id' => $petak2->id,
            'luas_target_ha' => 15.00,
            'perlu_mapping' => false,
            'tarif_per_ha' => 300000.00,
            'tarif_mapping_per_ha' => null,
        ]);

        // 2. Simpan Realisasi via Controller
        $nomorLaporan = 'LAP-MAP-' . uniqid();
        $payloadRealisasi = [
            'nomor_laporan' => $nomorLaporan,
            'tim_id' => $tim->id,
            'nama_pilot' => 'Pilot Tester',
            'spk_id' => $spk->id,
            'distrik_id' => $distrik->id,
            'periode_bulan' => (int) now()->format('m'),
            'periode_tahun' => (int) now()->format('Y'),
            'ajukan' => 1,
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak1->id,
                    'jenis_pekerjaan_id' => $ps1->id,
                    'luas_spk_ha' => 10.00,
                    'luas_realisasi_ha' => 10.00,
                    'perlu_mapping' => 1,
                    'status_pekerjaan' => 'selesai',
                ],
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak2->id,
                    'jenis_pekerjaan_id' => $ps1->id,
                    'luas_spk_ha' => 15.00,
                    'luas_realisasi_ha' => 15.00,
                    'perlu_mapping' => 0,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
        ];

        $resPost = $this->actingAs($pekerja)->post(route('realisasi.store'), $payloadRealisasi);
        $resPost->assertRedirect();

        $realisasi = \App\Models\LaporanRealisasi::where('nomor_laporan', $nomorLaporan)->with('detail')->firstOrFail();

        // 3. Verifikasi Detail Realisasi
        $d1 = $realisasi->detail->where('petak_id', $petak1->id)->firstOrFail();
        $this->assertTrue($d1->perlu_mapping);
        $this->assertEquals(30000.00, (float) $d1->tarif_mapping_per_ha);
        $this->assertEquals(3000000.00, $d1->subtotal_pokok);
        $this->assertEquals(300000.00, $d1->subtotal_mapping);
        $this->assertEquals(3300000.00, $d1->subtotal_biaya);
        $this->assertEquals(330000.00, $d1->total_tarif_per_ha);
        $this->assertStringContainsString('(+Mapping)', $d1->nama_pekerjaan_lengkap);

        $d2 = $realisasi->detail->where('petak_id', $petak2->id)->firstOrFail();
        $this->assertFalse($d2->perlu_mapping);
        $this->assertEquals(4500000.00, $d2->subtotal_pokok);
        $this->assertEquals(0.00, $d2->subtotal_mapping);
        $this->assertEquals(4500000.00, $d2->subtotal_biaya);

        // 4. Verifikasi Akumulasi Laporan Realisasi
        $this->assertEquals(25.00, $realisasi->total_luas_realisasi);
        $this->assertEquals(10.00, $realisasi->total_luas_mapping);
        $this->assertTrue($realisasi->ada_layanan_mapping);
        $this->assertEquals(7500000.00, $realisasi->total_nilai_pokok);
        $this->assertEquals(300000.00, $realisasi->total_nilai_mapping);
        $this->assertEquals(7800000.00, $realisasi->total_nilai_pekerjaan);

        // 5. Verifikasi Halaman Show Realisasi
        $resShow = $this->actingAs($this->kepala)->get(route('realisasi.show', $realisasi));
        $resShow->assertOk();
        $resShow->assertSee('Total Nilai: Rp 7.800.000');
        $resShow->assertSee('Mapping: 10,00 Ha (+Rp 300.000)');
        $resShow->assertSee('+Mapping (+Rp 30.000/Ha)');

        // 6. Setujui Laporan untuk Pengujian Ekspor
        $realisasi->update(['status_laporan' => \App\Models\LaporanRealisasi::DISETUJUI]);

        // 7. Verifikasi EksporExcelService siapkanDataRekapitulasi
        $excelService = app(\App\Services\EksporExcelService::class);
        $dataRekap = $excelService->siapkanDataRekapitulasi(
            bulan: (int) now()->format('m'),
            tahun: (int) now()->format('Y'),
            distrikId: $distrik->id,
            timId: $tim->id
        );

        $this->assertGreaterThanOrEqual(7800000.00, $dataRekap['totalNilai']);
        $this->assertGreaterThanOrEqual(10.00, $dataRekap['totalLuasMapping']);
        $this->assertGreaterThanOrEqual(300000.00, $dataRekap['totalNilaiMapping']);
        $this->assertEquals(round($dataRekap['totalNilai'] * 0.05, 2), round($dataRekap['adm5Persen'], 2));
        $this->assertEquals(round($dataRekap['totalNilai'] * 0.15, 2), round($dataRekap['investor15Persen'], 2));
        $this->assertEquals(round($dataRekap['totalNilai'] * 0.80, 2), round($dataRekap['hasilBersih80Persen'], 2));

        // Cleanup
        $realisasi->detail()->delete();
        $realisasi->delete();
        $spk->detail()->delete();
        $spk->delete();
    }

    /**
     * Fase 4: Memastikan hanya Pekerja Lapangan yang bisa mengisi/mengubah/mengajukan/menghapus
     * laporan realisasi, sedangkan Kepala Lapangan & Atasan hanya mode pantau (Kepala tetap bisa periksa).
     */
    public function test_kepemilikan_hak_akses_laporan_realisasi_per_peran(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->firstOrFail();
        $atasan = Pengguna::where('nama_pengguna', 'atasan')->firstOrFail();
        $idTimPekerja = $pekerja->idTimYangDiakses() ?? [];
        $tim = Tim::where('status_tim', 'aktif')
            ->when(! empty($idTimPekerja), fn ($q) => $q->whereIn('id', $idTimPekerja))
            ->firstOrFail();
        $distrik = Distrik::firstOrFail();

        $laporan = \App\Models\LaporanRealisasi::create([
            'nomor_laporan' => 'LAP-HAK-' . uniqid(),
            'tim_id' => $tim->id,
            'nama_pilot' => 'Pilot Hak Akses',
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'diinput_oleh' => $pekerja->id,
            'status_laporan' => \App\Models\LaporanRealisasi::MENUNGGU_PEMERIKSAAN,
        ]);

        // Kepala Lapangan, Atasan, dan Admin: boleh lihat index & show, dilarang akses rute tulis
        foreach ([$this->kepala, $atasan, $this->admin] as $pemantau) {
            $resIndex = $this->actingAs($pemantau)->get(route('realisasi.index'));
            $resIndex->assertOk();
            $resIndex->assertDontSee('Input Laporan Realisasi');

            $resShow = $this->actingAs($pemantau)->get(route('realisasi.show', $laporan));
            $resShow->assertOk();
            $resShow->assertDontSee('Ubah Laporan');
            $resShow->assertDontSee('Ajukan ke Kepala Lapangan');

            $this->actingAs($pemantau)->get(route('realisasi.create'))->assertForbidden();
            $this->actingAs($pemantau)->post(route('realisasi.store'), [])->assertForbidden();
            $this->actingAs($pemantau)->get(route('realisasi.edit', $laporan))->assertForbidden();
            $this->actingAs($pemantau)->put(route('realisasi.update', $laporan), [])->assertForbidden();
            $this->actingAs($pemantau)->post(route('realisasi.ajukan', $laporan))->assertForbidden();
            $this->actingAs($pemantau)->delete(route('realisasi.destroy', $laporan))->assertForbidden();
        }

        // Kepala Lapangan tetap berhak memeriksa & menyetujui laporan realisasi
        $resPeriksa = $this->actingAs($this->kepala)->post(route('realisasi.periksa', $laporan), [
            'tindakan' => 'setujui',
            'catatan' => 'Disetujui sesuai pengecekan lapangan.',
        ]);
        $resPeriksa->assertRedirect();
        $laporan->refresh();
        $this->assertEquals(\App\Models\LaporanRealisasi::DISETUJUI, $laporan->status_laporan);

        $laporan->delete();
    }
}

