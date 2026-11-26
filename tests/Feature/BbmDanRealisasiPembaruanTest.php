<?php

namespace Tests\Feature;

use App\Models\JenisBbm;
use App\Models\JenisPekerjaan;
use App\Models\LaporanRealisasi;
use App\Models\Pengguna;
use App\Models\Petak;
use App\Models\Spk;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use App\Services\BbmService;
use Tests\TestCase;

class BbmDanRealisasiPembaruanTest extends TestCase
{
    /**
     * Requirement 1 & 3: Otomatisasi harga satuan master fixed, kalkulasi total biaya, dan otomatisasi keperluan.
     */
    public function test_otomatisasi_harga_master_total_biaya_dan_keperluan_stok_masuk(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();

        // Siapkan master BBM dengan harga tetap
        $jenisBbm = JenisBbm::create([
            'nama_bbm' => 'BioSolar-' . uniqid(),
            'harga_per_liter' => 6800.00,
            'satuan' => 'liter',
            'aktif' => true,
        ]);

        $nomorUnik = 'BBM/AUTO/' . uniqid();

        // 1. Catat penerimaan (+) tanpa kirim harga_per_liter (otomatis ambil dari master dan total biaya otomatis)
        $response = $this->actingAs($pekerja)->post(route('bbm.store'), [
            'nomor_transaksi' => $nomorUnik,
            'tim_id' => $tim->id,
            'jenis_bbm_id' => $jenisBbm->id,
            'jenis_transaksi' => 'penerimaan',
            'tanggal_transaksi' => now()->toDateString(),
            'jumlah_liter' => 50.00,
            // harga_per_liter dan keperluan sengaja tidak dikirim atau null untuk uji otomatisasi
        ]);

        $response->assertRedirect();
        $tx = TransaksiBbm::where('nomor_transaksi', $nomorUnik)->first();
        $this->assertNotNull($tx);
        $this->assertEquals(6800.00, (float) $tx->harga_per_liter, 'Harga satuan otomatis ditarik dari master Jenis BBM');
        $this->assertEquals(340000.00, (float) $tx->total_biaya, 'Total biaya otomatis dihitung: 50 * 6800');
        $this->assertEquals('stok_masuk', $tx->keperluan, 'Keperluan transaksi penerimaan otomatis dikunci ke stok_masuk');
        $this->assertEquals('Stok BBM Masuk', $tx->labelKeperluan());

        // Bersihkan
        $tx->delete();
        $jenisBbm->delete();
    }

    /**
     * Requirement 2 & 4: Indikator mutasi, verifikasi admin langsung mempengaruhi saldo stok tim.
     */
    public function test_admin_verifikasi_transaksi_bbm_dan_sinkronisasi_saldo(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();
        $jenisBbm = JenisBbm::first();

        $bbmService = app(BbmService::class);
        $saldoAwal = $bbmService->hitungSaldoResmi($tim->id, $jenisBbm->id);

        // 1. Pekerja input transaksi penerimaan (+)
        $nomorPenerimaan = 'BBM/MASUK/' . uniqid();
        $this->actingAs($pekerja)->post(route('bbm.store'), [
            'nomor_transaksi' => $nomorPenerimaan,
            'tim_id' => $tim->id,
            'jenis_bbm_id' => $jenisBbm->id,
            'jenis_transaksi' => 'penerimaan',
            'tanggal_transaksi' => now()->toDateString(),
            'jumlah_liter' => 100.00,
        ])->assertRedirect();

        $txMasuk = TransaksiBbm::where('nomor_transaksi', $nomorPenerimaan)->first();
        $this->assertEquals(TransaksiBbm::MENUNGGU_PEMERIKSAAN, $txMasuk->status_verifikasi);

        // Saldo belum bertambah sebelum diverifikasi
        $this->assertEquals($saldoAwal, $bbmService->hitungSaldoResmi($tim->id, $jenisBbm->id));

        // 2. Admin verifikasi (Setujui) transaksi
        $responseVerifikasi = $this->actingAs($admin)->post(route('bbm.verifikasi', $txMasuk), [
            'tindakan' => 'verifikasi',
            'catatan' => 'Bukti struk valid dan sesuai.',
        ]);
        $responseVerifikasi->assertRedirect();

        $txMasuk->refresh();
        $this->assertEquals(TransaksiBbm::TERVERIFIKASI, $txMasuk->status_verifikasi);

        // Saldo resmi bertambah 100 liter secara balance
        $saldoSetelahVerif = $bbmService->hitungSaldoResmi($tim->id, $jenisBbm->id);
        $this->assertEquals(round($saldoAwal + 100.00, 2), $saldoSetelahVerif);

        // 3. Pekerja input penggunaan (-) 40 liter
        $nomorPenggunaan = 'BBM/PAKAI/' . uniqid();
        $this->actingAs($pekerja)->post(route('bbm.store'), [
            'nomor_transaksi' => $nomorPenggunaan,
            'tim_id' => $tim->id,
            'jenis_bbm_id' => $jenisBbm->id,
            'jenis_transaksi' => 'penggunaan',
            'tanggal_transaksi' => now()->toDateString(),
            'jumlah_liter' => 40.00,
            'keperluan' => 'operasional_drone',
        ])->assertRedirect();

        $txPakai = TransaksiBbm::where('nomor_transaksi', $nomorPenggunaan)->first();

        // Admin setujui penggunaan
        $this->actingAs($admin)->post(route('bbm.verifikasi', $txPakai), [
            'tindakan' => 'verifikasi',
            'catatan' => 'Sesuai laporan penerbangan.',
        ])->assertRedirect();

        // Saldo resmi berkurang 40 liter secara akurat
        $saldoAkhir = $bbmService->hitungSaldoResmi($tim->id, $jenisBbm->id);
        $this->assertEquals(round($saldoSetelahVerif - 40.00, 2), $saldoAkhir);

        // Uji tampilan tabel BBM index memuat label teks status dan tombol verifikasi
        $resIndex = $this->actingAs($admin)->get(route('bbm.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('Terverifikasi');
        $resIndex->assertSee('Stok BBM Masuk');

        // Bersihkan
        $txPakai->delete();
        $txMasuk->delete();
    }

    /**
     * Requirement 5: Multi-Input (Repeater) konsumsi BBM pada form realisasi penerbangan (FR-16).
     */
    public function test_multi_input_repeater_bbm_pada_realisasi_penerbangan(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();
        $spk = Spk::where('status_spk', Spk::DISETUJUI)
            ->whereDoesntHave('laporanRealisasi', function ($q) {
                $q->whereDate('tanggal_input', now()->toDateString());
            })
            ->with('detail')
            ->first();

        if (! $spk) {
            $spk = Spk::where('status_spk', Spk::DISETUJUI)->with('detail')->first();
            LaporanRealisasi::where('spk_id', $spk->id)->whereDate('tanggal_input', now()->toDateString())->delete();
        }

        $petak = Petak::find($spk->detail->first()->petak_id);
        $jenisPekerjaan = JenisPekerjaan::where('aktif', true)->first();

        $bbmA = JenisBbm::where('nama_bbm', 'like', '%Pertamax%')->first();
        $bbmB = JenisBbm::where('nama_bbm', 'like', '%Pertalite%')->first();

        $nomorLaporan = 'REALISASI/REPEATER/' . uniqid();

        // Kirim data formulir realisasi dengan repeater BBM (2 baris: Drone dan Genset)
        $response = $this->actingAs($pekerja)->post(route('realisasi.store'), [
            'nomor_laporan' => $nomorLaporan,
            'tim_id' => $tim->id,
            'spk_id' => $spk->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $spk->distrik_id,
            'nama_pilot' => 'Budi Pilot',
            'nama_copilot' => 'Ahmad Co',
            'nama_helper' => ['Helper Pak Joko'],
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak->id,
                    'jenis_pekerjaan_id' => $jenisPekerjaan->id,
                    'luas_realisasi_ha' => 15.5,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
            // Checkbox catat bbm aktif dengan repeater bbm_items
            'catat_bbm' => 1,
            'bbm_items' => [
                [
                    'jenis_bbm_id' => $bbmA->id,
                    'keperluan' => 'operasional_drone',
                    'jumlah_liter' => 12.50,
                    'keterangan' => 'Penerbangan sesi pagi drone 1',
                ],
                [
                    'jenis_bbm_id' => $bbmB->id,
                    'keperluan' => 'genset',
                    'jumlah_liter' => 8.00,
                    'keterangan' => 'Genset charger baterai drone',
                ],
            ],
        ]);

        $response->assertRedirect();
        $laporan = LaporanRealisasi::where('nomor_laporan', $nomorLaporan)->first();
        $this->assertNotNull($laporan);

        // Pastikan kedua baris tersimpan sebagai TransaksiBbm yang terhubung dengan laporan
        $transaksiBbm = TransaksiBbm::where('laporan_realisasi_id', $laporan->id)->get();
        $this->assertCount(2, $transaksiBbm);

        $txDrone = $transaksiBbm->where('keperluan', 'operasional_drone')->first();
        $this->assertNotNull($txDrone);
        $this->assertEquals($bbmA->id, $txDrone->jenis_bbm_id);
        $this->assertEquals(12.50, (float) $txDrone->jumlah_liter);
        $this->assertEquals(TransaksiBbm::PENGGUNAAN, $txDrone->jenis_transaksi);
        $this->assertEquals(round(12.50 * (float) $bbmA->harga_per_liter, 2), (float) $txDrone->total_biaya);

        $txGenset = $transaksiBbm->where('keperluan', 'genset')->first();
        $this->assertNotNull($txGenset);
        $this->assertEquals($bbmB->id, $txGenset->jenis_bbm_id);
        $this->assertEquals(8.00, (float) $txGenset->jumlah_liter);
        $this->assertEquals(round(8.00 * (float) $bbmB->harga_per_liter, 2), (float) $txGenset->total_biaya);

        // Uji halaman show realisasi menampilkan tabel konsumsi BBM
        $resShow = $this->actingAs($pekerja)->get(route('realisasi.show', $laporan));
        $resShow->assertOk();
        $resShow->assertSee('Konsumsi BBM Terhubung');
        $resShow->assertSee('12,50 L');
        $resShow->assertSee('8,00 L');

        // Bersihkan
        $txDrone->delete();
        $txGenset->delete();
        $laporan->detail()->delete();
        $laporan->delete();
    }

    /**
     * Memastikan endpoint detail tim untuk pop-up BBM bekerja sesuai hak akses tim.
     */
    public function test_endpoint_detail_tim_bbm_dan_halaman_monitoring_popup(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();

        $resIndex = $this->actingAs($admin)->get(route('bbm.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('dialog-detail-tim-bbm');
        $resIndex->assertSee('dialog-semua-tim-bbm');

        $resJson = $this->actingAs($admin)->getJson(route('bbm.detail-tim', $tim));
        $resJson->assertOk()
            ->assertJsonStructure([
                'tim' => ['id', 'nama_tim', 'total_masuk_format', 'total_keluar_format'],
                'transaksi',
            ]);

        $timLain = Tim::whereNotIn('id', $pekerja->idTimYangDiakses() ?? [])->first();
        if ($timLain) {
            $this->actingAs($pekerja)->getJson(route('bbm.detail-tim', $timLain))->assertForbidden();
        }
    }

    /**
     * Memastikan pembatasan kepemilikan peran pada modul BBM (Atasan & Kepala hanya pantau;
     * Pekerja tidak bisa stok awal/penyesuaian; nomor transaksi otomatis jika kosong).
     */
    public function test_kepemilikan_hak_akses_peran_dan_otomatisasi_nomor_bbm(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->first();
        $atasan = Pengguna::where('nama_pengguna', 'atasan')->first();
        $tim = Tim::where('status_tim', 'aktif')->first();
        $jenisBbm = JenisBbm::first();

        // Kepala Lapangan & Atasan: boleh lihat monitoring, dilarang buka form create/store
        foreach ([$kepala, $atasan] as $pemantau) {
            $resIndex = $this->actingAs($pemantau)->get(route('bbm.index'));
            $resIndex->assertOk();
            $resIndex->assertSee('Mode Pantau');
            $resIndex->assertDontSee('Catat Transaksi BBM');

            $this->actingAs($pemantau)->get(route('bbm.create'))->assertForbidden();
            $this->actingAs($pemantau)->post(route('bbm.store'), [
                'tim_id' => $tim->id,
                'jenis_bbm_id' => $jenisBbm->id,
                'jenis_transaksi' => 'penerimaan',
                'tanggal_transaksi' => now()->toDateString(),
                'jumlah_liter' => 10,
            ])->assertForbidden();
        }

        // Pekerja Lapangan dilarang input stok_awal maupun penyesuaian
        foreach (['stok_awal', 'penyesuaian'] as $jenisTerlarang) {
            $this->actingAs($pekerja)->post(route('bbm.store'), [
                'tim_id' => $tim->id,
                'jenis_bbm_id' => $jenisBbm->id,
                'jenis_transaksi' => $jenisTerlarang,
                'tanggal_transaksi' => now()->toDateString(),
                'jumlah_liter' => 10,
            ])->assertSessionHasErrors('jenis_transaksi');
        }

        // Pekerja Lapangan input penerimaan tanpa nomor_transaksi -> otomatis dibuat server
        $resAuto = $this->actingAs($pekerja)->post(route('bbm.store'), [
            'tim_id' => $tim->id,
            'jenis_bbm_id' => $jenisBbm->id,
            'jenis_transaksi' => 'penerimaan',
            'tanggal_transaksi' => now()->toDateString(),
            'jumlah_liter' => 15,
            'keterangan' => 'Uji nomor otomatis',
        ]);
        $resAuto->assertRedirect();

        $txAuto = TransaksiBbm::where('keterangan', 'Uji nomor otomatis')->latest('id')->first();
        $this->assertNotNull($txAuto);
        $this->assertStringStartsWith('BBM/', $txAuto->nomor_transaksi);

        // Admin tetap bisa mengakses create dan melihat seluruh tab
        $this->actingAs($admin)->get(route('bbm.create'))->assertOk();

        $txAuto->delete();
    }
}
