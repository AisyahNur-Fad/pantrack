<?php

namespace Tests\Feature;

use App\Models\JenisBbm;
use App\Models\KasbonPersonil;
use App\Models\PengeluaranOperasionalUnit;
use App\Models\Pengguna;
use App\Models\PenutupanPeriode;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use App\Services\EksporExcelService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class KeuanganDanEkspor7SheetTest extends TestCase
{
    public function test_hanya_admin_dan_atasan_bisa_akses_keuangan_unit(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $this->assertNotNull($pekerja);
        $this->assertNotNull($admin);

        // Pekerja ditolak
        $responsePekerja = $this->actingAs($pekerja)->get('/keuangan');
        $responsePekerja->assertStatus(403);

        // Admin diizinkan
        $responseAdmin = $this->actingAs($admin)->get('/keuangan');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Keuangan & Bon Operasional Unit Drone');
    }

    public function test_admin_dapat_mencatat_dan_mengubah_pengeluaran_operasional_unit(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();
        $this->assertNotNull($tim);

        // 1. Catat pengeluaran baru
        $responseStore = $this->actingAs($admin)->post('/keuangan/pengeluaran', [
            'tim_id' => $tim->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'tanggal_transaksi' => now()->toDateString(),
            'kategori_biaya' => PengeluaranOperasionalUnit::SPAREPART,
            'keterangan' => 'Pembelian Propeller & Arm Nozzle T50',
            'nominal' => 1125000,
            'nomor_bukti_pi' => 'PIHAN180826001',
        ]);
        $responseStore->assertRedirect();
        $responseStore->assertSessionHas('sukses');

        $pengeluaran = PengeluaranOperasionalUnit::where('nomor_bukti_pi', 'PIHAN180826001')->first();
        $this->assertNotNull($pengeluaran);
        $this->assertEquals(1125000, (float) $pengeluaran->nominal);

        // 2. Ubah pengeluaran
        $responseUpdate = $this->actingAs($admin)->put("/keuangan/pengeluaran/{$pengeluaran->id}", [
            'tim_id' => $tim->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'tanggal_transaksi' => now()->toDateString(),
            'kategori_biaya' => PengeluaranOperasionalUnit::SPAREPART,
            'keterangan' => 'Pembelian Propeller & Arm Nozzle T50 Update',
            'nominal' => 1250000,
            'nomor_bukti_pi' => 'PIHAN180826001',
        ]);
        $responseUpdate->assertRedirect();
        $pengeluaran->refresh();
        $this->assertEquals(1250000, (float) $pengeluaran->nominal);

        // 3. Bersihkan data
        $pengeluaran->delete();
    }

    public function test_admin_dapat_mencatat_kasbon_kru(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();

        $response = $this->actingAs($admin)->post('/keuangan/kasbon', [
            'tim_id' => $tim->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'tanggal_kasbon' => now()->toDateString(),
            'nama_personil' => 'Pranoto',
            'peran_kru' => 'pilot',
            'jumlah_kasbon' => 1500000,
            'keterangan' => 'Uang muka keperluan keluarga',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $kasbon = KasbonPersonil::where('nama_personil', 'Pranoto')->where('jumlah_kasbon', 1500000)->first();
        $this->assertNotNull($kasbon);

        // Bersihkan
        $kasbon->delete();
    }

    public function test_transaksi_bbm_dapat_mencatat_harga_total_dan_nomor_struk(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();
        $jenisBbm = JenisBbm::first();

        $nomorUnik = 'BBM/TEST/' . uniqid();
        $response = $this->actingAs($pekerja)->post('/bbm', [
            'nomor_transaksi' => $nomorUnik,
            'tim_id' => $tim->id,
            'jenis_bbm_id' => $jenisBbm->id,
            'jenis_transaksi' => 'penerimaan',
            'tanggal_transaksi' => now()->toDateString(),
            'jumlah_liter' => 100,
            'harga_per_liter' => 14500,
            'total_biaya' => 1450000,
            'nomor_struk_nota' => 'STRUK-SPBU-081',
            'keterangan' => 'Pengisian solar posko tim',
        ]);

        $response->assertRedirect();
        $bbm = TransaksiBbm::where('nomor_transaksi', $nomorUnik)->first();
        $this->assertNotNull($bbm);
        $this->assertEquals(14500, (float) $bbm->harga_per_liter);
        $this->assertEquals(1450000, (float) $bbm->total_biaya);
        $this->assertEquals('STRUK-SPBU-081', $bbm->nomor_struk_nota);

        $bbm->delete();
    }

    public function test_ekspor_excel_menghasilkan_7_sheet_lengkap(): void
    {
        $service = app(EksporExcelService::class);
        $tim = Tim::where('status_tim', 'aktif')->first();

        $hasil = $service->buatRekapitulasi(
            (int) now()->month,
            (int) now()->year,
            null,
            $tim?->id,
            null,
            null,
            true
        );

        $this->assertFileExists($hasil['path']);

        // Buka kembali file Excel dengan PhpSpreadsheet untuk verifikasi sheet
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($hasil['path']);

        $sheetNames = $spreadsheet->getSheetNames();
        $this->assertCount(7, $sheetNames, 'Laporan resmi harus memiliki tepat 7 Sheet.');

        $expectedSheets = [
            'Cover',
            'Rekap Profit & Bon',
            'Lampiran Realisasi',
            'Slip Gaji',
            'Rincian Operasional',
            'Ransum & Kantin',
            'BAP Pembayaran',
        ];

        foreach ($expectedSheets as $expected) {
            $this->assertContains($expected, $sheetNames, "Sheet '{$expected}' wajib ada dalam berkas Excel.");
        }

        // Pastikan sheet Cover memiliki judul yang benar
        $sheetCover = $spreadsheet->getSheetByName('Cover');
        $this->assertEquals('PT. PANLIBATA ANDALAS BERTUAH / DRONE BERTUAH', $sheetCover->getCell('B2')->getValue());

        // Bersihkan
        if (file_exists($hasil['path'])) {
            @unlink($hasil['path']);
        }
        $hasil['ekspor']->delete();
    }

    public function test_ekspor_pdf_berhasil_dibuat_dan_dapat_diunduh(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();
        $this->assertNotNull($admin);

        // 1. Uji pembuatan file PDF via service
        $service = app(EksporExcelService::class);
        $hasilPdf = $service->buatPdfRekapitulasi(
            (int) now()->month,
            (int) now()->year,
            null,
            $tim?->id,
            null,
            null,
            true
        );

        $this->assertFileExists($hasilPdf['path']);
        $this->assertStringEndsWith('.pdf', $hasilPdf['nama']);
        $this->assertGreaterThan(1000, filesize($hasilPdf['path']));

        // 2. Uji endpoint POST /ekspor/proses dengan format=pdf
        $responsePost = $this->actingAs($admin)->post('/ekspor/proses', [
            'bulan' => (int) now()->month,
            'tahun' => (int) now()->year,
            'tim_id' => $tim?->id,
            'format' => 'pdf',
        ]);
        $responsePost->assertOk();
        $this->assertEquals('application/pdf', $responsePost->headers->get('Content-Type'));

        // 3. Uji endpoint GET /ekspor/{ekspor}/unduh-pdf
        $responseUnduhPdf = $this->actingAs($admin)->get("/ekspor/{$hasilPdf['ekspor']->id}/unduh-pdf");
        $responseUnduhPdf->assertOk();
        $this->assertEquals('application/pdf', $responseUnduhPdf->headers->get('Content-Type'));

        // Bersihkan file PDF uji
        if (file_exists($hasilPdf['path'])) {
            @unlink($hasilPdf['path']);
        }
        $hasilPdf['ekspor']->delete();
    }

    public function test_admin_dapat_toggle_status_lunas_kasbon(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();

        $kasbon = KasbonPersonil::create([
            'tim_id' => $tim->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'tanggal_kasbon' => now()->toDateString(),
            'nama_personil' => 'Heru (Helper)',
            'peran_kru' => 'helper',
            'jumlah_kasbon' => 500000,
            'status_lunas' => false,
            'diinput_oleh' => $admin->id,
        ]);

        $this->assertFalse((bool) $kasbon->status_lunas);

        // Toggle lunas via PATCH
        $response = $this->actingAs($admin)->patch("/keuangan/kasbon/{$kasbon->id}/toggle-lunas");
        $response->assertRedirect();
        $kasbon->refresh();
        $this->assertTrue((bool) $kasbon->status_lunas);

        // Toggle kembali
        $this->actingAs($admin)->patch("/keuangan/kasbon/{$kasbon->id}/toggle-lunas");
        $kasbon->refresh();
        $this->assertFalse((bool) $kasbon->status_lunas);

        $kasbon->delete();
    }

    public function test_admin_dapat_mengatur_tarif_dan_gaji_tim_di_master(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();

        $response = $this->actingAs($admin)->put("/master/tim/{$tim->id}", [
            'nama_tim' => $tim->nama_tim,
            'drone_id' => $tim->drone_id,
            'distrik_id' => $tim->distrik_id,
            'kepala_tim_id' => $tim->kepala_tim_id,
            'status_tim' => 'aktif',
            'premi_pilot_per_ha' => 25000,
            'premi_helper_per_ha' => 12000,
            'gaji_pokok_pilot' => 1200000,
            'gaji_pokok_helper' => 500000,
            'uang_makan_pilot' => 950000,
            'uang_makan_helper_per_hari' => 55000,
            'biaya_mapping_per_ha' => 35000,
            'biaya_enterteint_per_ha' => 15000,
            'honor_adm_tim' => 600000,
        ]);

        $response->assertRedirect();
        $tim->refresh();

        $this->assertEquals(25000, (float) $tim->premi_pilot_per_ha);
        $this->assertEquals(12000, (float) $tim->premi_helper_per_ha);
        $this->assertEquals(1200000, (float) $tim->gaji_pokok_pilot);
        $this->assertEquals(500000, (float) $tim->gaji_pokok_helper);
        $this->assertEquals(55000, (float) $tim->uang_makan_helper_per_hari);
        $this->assertEquals(600000, (float) $tim->honor_adm_tim);
    }

    public function test_admin_dapat_menutup_dan_membuka_kembali_periode_pembukuan(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();
        $bulan = 11;
        $tahun = 2026;

        // 1. Admin menutup periode
        $responseTutup = $this->actingAs($admin)->post('/keuangan/periode/tutup', [
            'tim_id' => $tim->id,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'catatan' => 'Closing buku bulanan unit untuk laporan resmi',
        ]);
        $responseTutup->assertRedirect();
        $responseTutup->assertSessionHas('sukses');

        $penutupan = PenutupanPeriode::where('tim_id', $tim->id)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->first();

        $this->assertNotNull($penutupan);
        $this->assertEquals(PenutupanPeriode::STATUS_TERKUNCI, $penutupan->status);
        $this->assertEquals($admin->id, $penutupan->ditutup_oleh);
        $this->assertTrue(PenutupanPeriode::apakahTerkunci($bulan, $tahun, $tim->id));

        // 2. Admin membuka kembali periode dengan menyertakan alasan
        $responseBuka = $this->actingAs($admin)->post('/keuangan/periode/buka', [
            'tim_id' => $tim->id,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'alasan_buka' => 'Terdapat susulan nota perbaikan arm drone yang perlu dimasukkan',
        ]);
        $responseBuka->assertRedirect();
        $responseBuka->assertSessionHas('sukses');

        $penutupan->refresh();
        $this->assertEquals(PenutupanPeriode::STATUS_TERBUKA, $penutupan->status);
        $this->assertEquals($admin->id, $penutupan->dibuka_oleh);
        $this->assertFalse(PenutupanPeriode::apakahTerkunci($bulan, $tahun, $tim->id));

        // Bersihkan
        $penutupan->delete();
    }

    public function test_kasbon_tetap_terhitung_saat_periode_tutup_namun_penambahan_baru_dicegah(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();
        $bulan = 12;
        $tahun = 2026;

        // 1. Buat data kasbon yang sudah ada sebelum periode ditutup
        $kasbonLama = KasbonPersonil::create([
            'tim_id' => $tim->id,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'tanggal_kasbon' => '2026-12-05',
            'nama_personil' => 'Joko Susanto',
            'peran_kru' => 'helper',
            'jumlah_kasbon' => 450000,
            'keterangan' => 'Kasbon belanja bekal',
            'status_lunas' => false,
            'diinput_oleh' => $admin->id,
        ]);

        // 2. Tutup periode tersebut
        $this->actingAs($admin)->post('/keuangan/periode/tutup', [
            'tim_id' => $tim->id,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'catatan' => 'Closing akhir tahun',
        ]);

        $this->assertTrue(PenutupanPeriode::apakahTerkunci($bulan, $tahun, $tim->id));

        // 3. Pastikan kasbon LAMA TETAP TERHITUNG dan tampil di halaman index keuangan
        $responseIndex = $this->actingAs($admin)->get("/keuangan?tim_id={$tim->id}&bulan={$bulan}&tahun={$tahun}");
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Periode Pembukuan Ditutup (Terkunci)');
        $responseIndex->assertSee('Joko Susanto');
        $responseIndex->assertSee('450.000'); // Nominal kasbon tetap masuk perhitungan dan tampil

        // 4. Coba menambah kasbon baru pada periode yang sudah ditutup -> HARUS DITOLAK
        $responseTambahKasbon = $this->actingAs($admin)->post('/keuangan/kasbon', [
            'tim_id' => $tim->id,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'tanggal_kasbon' => '2026-12-20',
            'nama_personil' => 'Budi Baru',
            'peran_kru' => 'pilot',
            'jumlah_kasbon' => 700000,
            'keterangan' => 'Kasbon baru seharusnya gagal',
        ]);
        $responseTambahKasbon->assertSessionHas('galat');

        // Pastikan kasbon baru TIDAK tersimpan
        $this->assertDatabaseMissing('kasbon_personil', [
            'nama_personil' => 'Budi Baru',
            'jumlah_kasbon' => 700000,
        ]);

        // 5. Coba menambah pengeluaran baru pada periode yang sudah ditutup -> HARUS DITOLAK
        $responseTambahPengeluaran = $this->actingAs($admin)->post('/keuangan/pengeluaran', [
            'tim_id' => $tim->id,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'tanggal_transaksi' => '2026-12-20',
            'kategori_biaya' => 'operasional_lapangan',
            'keterangan' => 'Pengeluaran baru seharusnya ditolak',
            'nominal' => 200000,
        ]);
        $responseTambahPengeluaran->assertSessionHas('galat');
        $this->assertDatabaseMissing('pengeluaran_operasional_unit', [
            'keterangan' => 'Pengeluaran baru seharusnya ditolak',
        ]);

        // 6. Sekarang Admin membuka kembali (reopen) periode dengan alasan
        $this->actingAs($admin)->post('/keuangan/periode/buka', [
            'tim_id' => $tim->id,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'alasan_buka' => 'Perlu menambahkan kasbon mendesak dan disetujui manajemen',
        ]);
        $this->assertFalse(PenutupanPeriode::apakahTerkunci($bulan, $tahun, $tim->id));

        // 7. Setelah dibuka kembali, penambahan kasbon baru HARUS BERHASIL
        $responseTambahKasbonUlang = $this->actingAs($admin)->post('/keuangan/kasbon', [
            'tim_id' => $tim->id,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'tanggal_kasbon' => '2026-12-20',
            'nama_personil' => 'Budi Baru',
            'peran_kru' => 'pilot',
            'jumlah_kasbon' => 700000,
            'keterangan' => 'Kasbon baru setelah dibuka kembali',
        ]);
        $responseTambahKasbonUlang->assertSessionHas('sukses');
        $this->assertDatabaseHas('kasbon_personil', [
            'nama_personil' => 'Budi Baru',
            'jumlah_kasbon' => 700000,
        ]);

        // Bersihkan data uji
        KasbonPersonil::where('nama_personil', 'Budi Baru')->delete();
        $kasbonLama->delete();
        PenutupanPeriode::where('tim_id', $tim->id)->where('periode_bulan', $bulan)->where('periode_tahun', $tahun)->delete();
    }
}
