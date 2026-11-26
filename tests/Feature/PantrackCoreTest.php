<?php

namespace Tests\Feature;

use App\Models\Distrik;
use App\Models\Drone;
use App\Models\JenisBbm;
use App\Models\JenisPekerjaan;
use App\Models\LaporanRealisasi;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Petak;
use App\Models\Spk;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use App\Services\BbmService;
use App\Services\EksporExcelService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PantrackCoreTest extends TestCase
{
    public function test_login_sukses_dan_gagal(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $this->assertNotNull($admin, 'User admin harus ada di database.');

        // Login dengan kata sandi salah harus gagal
        $responseGagal = $this->post('/login', [
            'nama_pengguna' => 'admin',
            'kata_sandi' => 'salah123',
        ]);
        $responseGagal->assertSessionHasErrors('nama_pengguna');

        // Login dengan kata sandi benar harus berhasil redirect ke dashboard
        $responseSukses = $this->post('/login', [
            'nama_pengguna' => 'admin',
            'kata_sandi' => 'password',
        ]);
        $responseSukses->assertRedirect(route('dashboard'));
    }

    public function test_pekerja_lapangan_tidak_bisa_akses_data_master(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $this->assertNotNull($pekerja);

        $response = $this->actingAs($pekerja)->get('/master/pengguna');
        $response->assertStatus(403);
    }

    public function test_kalkulasi_saldo_bbm_terisolasi_dan_hanya_yang_terverifikasi(): void
    {
        $tim = Tim::first();
        $bbm = JenisBbm::first();
        $this->assertNotNull($tim);
        $this->assertNotNull($bbm);

        $service = app(BbmService::class);
        $saldoAwal = $service->hitungSaldoResmi($tim->id, $bbm->id);

        // Buat transaksi penerimaan belum diverifikasi -> saldo tidak boleh berubah
        $txBelumVerif = TransaksiBbm::create([
            'nomor_transaksi' => 'TEST/BBM/' . uniqid(),
            'tim_id' => $tim->id,
            'jenis_bbm_id' => $bbm->id,
            'jenis_transaksi' => TransaksiBbm::PENERIMAAN,
            'tanggal_transaksi' => now()->toDateString(),
            'jumlah_liter' => 100.00,
            'status_verifikasi' => TransaksiBbm::MENUNGGU_PEMERIKSAAN,
            'diinput_oleh' => 1,
        ]);

        $saldoSetelah = $service->hitungSaldoResmi($tim->id, $bbm->id);
        $this->assertEquals($saldoAwal, $saldoSetelah, 'Transaksi belum diverifikasi tidak boleh mengubah saldo resmi.');

        // Verifikasi transaksi -> saldo harus bertambah 100
        $txBelumVerif->update(['status_verifikasi' => TransaksiBbm::TERVERIFIKASI]);
        $saldoTerverif = $service->hitungSaldoResmi($tim->id, $bbm->id);
        $this->assertEquals($saldoAwal + 100.00, $saldoTerverif, 'Transaksi terverifikasi harus menambah saldo resmi.');

        // Bersihkan data uji
        $txBelumVerif->delete();
    }

    public function test_ekspor_excel_berhasil_dibuat(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $this->actingAs($admin);

        $service = app(EksporExcelService::class);
        $hasil = $service->buatRekapitulasi(now()->month, now()->year);

        $this->assertFileExists($hasil['path']);
        $this->assertStringEndsWith('.xlsx', $hasil['nama']);

        // Bersihkan file uji
        if (file_exists($hasil['path'])) {
            @unlink($hasil['path']);
        }
        $hasil['ekspor']->delete();

        // Uji ekspor dengan filter rentang tanggal dan sembunyikan harga
        $hasilKustom = $service->buatRekapitulasi(
            (int) now()->month,
            (int) now()->year,
            null,
            null,
            now()->subDays(10)->toDateString(),
            now()->toDateString(),
            false
        );
        $this->assertFileExists($hasilKustom['path']);
        if (file_exists($hasilKustom['path'])) {
            @unlink($hasilKustom['path']);
        }
        $hasilKustom['ekspor']->delete();
    }

    public function test_pengguna_bisa_update_profil(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $this->assertNotNull($pekerja);

        $response = $this->actingAs($pekerja)->get('/profil');
        $response->assertStatus(200);

        $responseUpdate = $this->actingAs($pekerja)->put('/profil', [
            'nama_lengkap' => 'Budi Santoso Update',
            'nama_pengguna' => 'pekerja',
            'email' => 'pekerja.baru@pantrack.local',
            'nomor_telepon' => '089988776655',
        ]);
        $responseUpdate->assertRedirect();
        $responseUpdate->assertSessionHas('sukses');

        $pekerja->refresh();
        $this->assertEquals('Budi Santoso Update', $pekerja->nama_lengkap);
        $this->assertEquals('pekerja.baru@pantrack.local', $pekerja->email);

        // Kembalikan ke asal
        $pekerja->update([
            'nama_lengkap' => 'Budi Santoso (Pekerja)',
            'email' => 'pekerja@pantrack.local',
        ]);
    }

    public function test_pengguna_bisa_ganti_kata_sandi_mandiri(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $this->assertNotNull($pekerja);

        // Coba kata sandi lama salah
        $responseSalah = $this->actingAs($pekerja)->put('/profil/kata-sandi', [
            'kata_sandi_lama' => 'sandiSalah123',
            'kata_sandi' => 'PasswordBaru#2026',
            'kata_sandi_confirmation' => 'PasswordBaru#2026',
        ]);
        $responseSalah->assertSessionHasErrors('kata_sandi_lama');

        // Ganti dengan kata sandi lama yang benar
        $responseBenar = $this->actingAs($pekerja)->put('/profil/kata-sandi', [
            'kata_sandi_lama' => 'password',
            'kata_sandi' => 'PasswordBaru#2026',
            'kata_sandi_confirmation' => 'PasswordBaru#2026',
        ]);
        $responseBenar->assertRedirect();
        $responseBenar->assertSessionHas('sukses');

        $pekerja->refresh();
        $this->assertTrue(Hash::check('PasswordBaru#2026', $pekerja->kata_sandi));

        // Kembalikan password ke 'password'
        $pekerja->update(['kata_sandi' => Hash::make('password')]);
    }

    public function test_kepala_lapangan_wajib_buat_spk_dan_admin_atau_pekerja_tidak_bisa_buat_spk(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->first();
        $distrik = Distrik::whereNotNull('pt_id')->first() ?? Distrik::first();
        $petak = Petak::where('distrik_id', $distrik->id)->first() ?? Petak::first();
        $pekerjaan = JenisPekerjaan::where('tipe', 'utama')->first() ?? JenisPekerjaan::first();

        $this->assertNotNull($admin);
        $this->assertNotNull($pekerja);
        $this->assertNotNull($kepala);

        $payload = [
            'nomor_spk' => 'SPK-TEST/' . uniqid(),
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'pt_id' => $distrik->pt_id,
            'distrik_id' => $distrik->id,
            'jenis_pekerjaan_id' => $pekerjaan->id,
            'petak' => [
                [
                    'petak_id' => $petak->id,
                    'luas_target_ha' => 20.00,
                ],
            ],
        ];

        // 1. Admin tidak boleh membuatkan SPK (403 Forbidden)
        $respAdmin = $this->actingAs($admin)->post('/spk', $payload);
        $respAdmin->assertStatus(403);

        // 2. Pekerja lapangan tidak boleh membuat SPK (403 Forbidden)
        $respPekerja = $this->actingAs($pekerja)->post('/spk', $payload);
        $respPekerja->assertStatus(403);

        // 3. Kepala lapangan berhak dan berhasil membuatkan SPK
        $respKepala = $this->actingAs($kepala)->post('/spk', $payload);
        $respKepala->assertRedirect();

        $spk = Spk::where('nomor_spk', $payload['nomor_spk'])->first();
        $this->assertNotNull($spk, 'SPK harus berhasil dibuat oleh Kepala Lapangan.');
        $this->assertEquals(Spk::MENUNGGU_PERSETUJUAN, $spk->status_spk, 'SPK dari Kepala Lapangan berstatus menunggu persetujuan Admin.');
        $this->assertEquals($kepala->id, $spk->diinput_oleh);

        // 4. Admin mengonfirmasi dan menilai SPK menjadi disetujui
        $respVerif = $this->actingAs($admin)->post(route('spk.verifikasi', $spk), [
            'tindakan' => 'setujui',
            'catatan' => 'Diverifikasi dan disetujui oleh Admin.',
        ]);
        $respVerif->assertRedirect();
        $spk->refresh();
        $this->assertEquals(Spk::DISETUJUI, $spk->status_spk);
        $this->assertEquals($admin->id, $spk->disetujui_oleh);

        // Bersihkan data uji
        $spk->detail()->delete();
        $spk->delete();
    }

    public function test_pekerja_mengerjakan_realisasi_dari_spk_yang_dibuat_kepala_lapangan(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->first();
        $tim = Tim::first();
        $distrik = Distrik::first();
        $pekerjaan = JenisPekerjaan::first();
        $petak = Petak::first();

        // 1. Kepala lapangan membuatkan SPK terlebih dahulu
        $nomorSpk = 'SPK-KEPALA/' . uniqid();
        $spk = Spk::create([
            'nomor_spk' => $nomorSpk,
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'jenis_pekerjaan_id' => $pekerjaan->id,
            'diinput_oleh' => $kepala->id,
            'disetujui_oleh' => $kepala->id,
            'tanggal_persetujuan' => now(),
            'status_spk' => Spk::DISETUJUI,
        ]);

        $spk->detail()->create([
            'petak_id' => $petak->id,
            'luas_target_ha' => 25.50,
        ]);

        // 2. Pekerja mencoba input laporan realisasi TANPA SPK -> wajib gagal validasi
        $nomorLaporanBaru = 'LAP-TEST/' . uniqid();
        $respTanpaSpk = $this->actingAs($pekerja)->post('/realisasi', [
            'nomor_laporan' => $nomorLaporanBaru,
            'tim_id' => $tim->id,
            'nama_pilot' => 'Budi Santoso',
            'distrik_id' => $distrik->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak->id,
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 25.00,
                    'status_pekerjaan' => 'selesai',
                ]
            ],
        ]);
        $respTanpaSpk->assertSessionHasErrors('spk_id');

        // 3. Pekerja input laporan realisasi dengan memilih SPK yang sudah dibuat Kepala Lapangan
        $response = $this->actingAs($pekerja)->post('/realisasi', [
            'nomor_laporan' => $nomorLaporanBaru,
            'tim_id' => $tim->id,
            'nama_tim_manual' => 'Tim Alpha Operasional Lapangan',
            'nama_pilot' => 'Budi Santoso (Pilot Lapangan)',
            'nama_copilot' => 'Ahmad Co-Pilot',
            'nama_helper' => 'Doni Helper',
            'spk_id' => $spk->id,
            'distrik_id' => $distrik->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'ajukan' => 1,
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak->id,
                    'kode_petak_manual' => null,
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_spk_ha' => 25.50,
                    'luas_realisasi_ha' => 25.00,
                    'status_pekerjaan' => 'selesai',
                    'keterangan_kendala' => null,
                ]
            ],
        ]);

        $response->assertRedirect();

        // Verifikasi laporan realisasi berhasil tersimpan dan terhubung ke SPK
        $laporan = LaporanRealisasi::where('nomor_laporan', $nomorLaporanBaru)->first();
        $this->assertNotNull($laporan);
        $this->assertEquals($spk->id, $laporan->spk_id);
        $this->assertEquals('Budi Santoso (Pilot Lapangan)', $laporan->nama_pilot);
        $this->assertEquals('Ahmad Co-Pilot', $laporan->nama_copilot);
        $this->assertEquals('Doni Helper', $laporan->nama_helper);
        $this->assertEquals(LaporanRealisasi::MENUNGGU_PEMERIKSAAN, $laporan->status_laporan);

        // Bersihkan data uji
        $laporan->detail()->delete();
        $laporan->delete();
        $spk->detail()->delete();
        $spk->delete();
    }

    public function test_saldo_bbm_mencegah_pengeluaran_melebihi_saldo_tersedia(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $tim = Tim::first();
        $bbm = JenisBbm::first();
        $this->assertNotNull($pekerja);

        $service = app(BbmService::class);
        $saldoResmi = $service->hitungSaldoResmi($tim->id, $bbm->id);

        // Coba input penggunaan melebihi saldo resmi
        $response = $this->actingAs($pekerja)->post('/bbm', [
            'nomor_transaksi' => 'TX/TEST/OVER/' . uniqid(),
            'tim_id' => $tim->id,
            'jenis_bbm_id' => $bbm->id,
            'jenis_transaksi' => 'penggunaan',
            'tanggal_transaksi' => now()->toDateString(),
            'jumlah_liter' => $saldoResmi + 99999.00,
            'keperluan' => 'operasional_drone',
        ]);

        $response->assertSessionHasErrors('jumlah_liter');
    }

    public function test_validasi_spk_petak_wajib_sesuai_distrik(): void
    {
        $kepala = Pengguna::where('nama_pengguna', 'kepala')->first();
        $distrikA = Distrik::first();
        $distrikB = Distrik::where('id', '!=', $distrikA->id)->first();

        if ($distrikB) {
            $petakB = Petak::where('distrik_id', $distrikB->id)->first();
            if ($petakB) {
                $response = $this->actingAs($kepala)->post('/spk', [
                    'nomor_spk' => 'SPK/ERR/' . uniqid(),
                    'tanggal_spk' => now()->toDateString(),
                    'periode_bulan' => (int) now()->month,
                    'periode_tahun' => (int) now()->year,
                    'distrik_id' => $distrikA->id,
                    'petak' => [
                        [
                            'petak_id' => $petakB->id, // Petak milik Distrik B, bukan Distrik A
                            'luas_target_ha' => 10.0,
                        ]
                    ]
                ]);

                $response->assertSessionHasErrors('petak.0.petak_id');
            }
        }
    }

    public function test_laporan_realisasi_draf_bisa_dihapus(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $tim = Tim::first();
        $distrik = Distrik::first();

        $laporan = LaporanRealisasi::create([
            'nomor_laporan' => 'DRAFT/DEL/' . uniqid(),
            'tim_id' => $tim->id,
            'nama_pilot' => 'Pilot Test',
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'diinput_oleh' => $pekerja->id,
            'status_laporan' => LaporanRealisasi::DRAF,
        ]);

        $response = $this->actingAs($pekerja)->delete("/realisasi/{$laporan->id}");
        $response->assertRedirect(route('realisasi.index'));
        $this->assertDatabaseMissing('laporan_realisasi', ['id' => $laporan->id]);
    }

    public function test_profil_nomor_telepon_harus_angka(): void
    {
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();

        $response = $this->actingAs($pekerja)->put('/profil', [
            'nama_lengkap' => $pekerja->nama_lengkap,
            'nama_pengguna' => $pekerja->nama_pengguna,
            'email' => $pekerja->email,
            'nomor_telepon' => 'NOMOR-BUKAN-ANGKA',
        ]);

        $response->assertSessionHasErrors('nomor_telepon');
    }

    public function test_transaksi_bbm_terverifikasi_tidak_dapat_diedit_atau_dihapus(): void
    {
        $admin = Pengguna::where('nama_pengguna', 'admin')->first();
        $tim = Tim::first();
        $bbm = JenisBbm::first();

        $tx = TransaksiBbm::create([
            'nomor_transaksi' => 'TEST/LOCK/' . uniqid(),
            'tim_id' => $tim->id,
            'jenis_bbm_id' => $bbm->id,
            'jenis_transaksi' => TransaksiBbm::PENERIMAAN,
            'tanggal_transaksi' => now()->toDateString(),
            'jumlah_liter' => 50.00,
            'status_verifikasi' => TransaksiBbm::TERVERIFIKASI,
            'diinput_oleh' => $admin->id,
        ]);

        // Coba edit transaksi yang sudah terverifikasi -> harus ditolak dengan 403 Forbidden
        $responseEdit = $this->actingAs($admin)->get("/bbm/{$tx->id}/edit");
        $responseEdit->assertStatus(403);

        // Coba delete transaksi yang sudah terverifikasi -> harus ditolak dengan 403 Forbidden
        $responseDelete = $this->actingAs($admin)->delete("/bbm/{$tx->id}");
        $responseDelete->assertStatus(403);

        // Ubah status ke menunggu_pemeriksaan, lalu hapus untuk membersihkan
        $tx->update(['status_verifikasi' => TransaksiBbm::MENUNGGU_PEMERIKSAAN]);
        $tx->delete();
    }

    public function test_spk_bisa_ditandai_selesai_dan_tidak_muncul_di_pilihan_realisasi_baru(): void
    {
        $kepala = Pengguna::whereHas('peran', fn ($q) => $q->where('nama', Peran::KEPALA_LAPANGAN))->first();
        $admin = Pengguna::whereHas('peran', fn ($q) => $q->where('nama', Peran::ADMIN))->first();
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $distrik = Distrik::first();

        $spk = Spk::create([
            'nomor_spk' => 'SPK/TEST/CLOSE/' . uniqid(),
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'diinput_oleh' => $kepala->id,
            'status_spk' => Spk::DISETUJUI,
        ]);

        // Kepala lapangan menutup SPK menjadi selesai
        $response = $this->actingAs($kepala)->from(route('spk.show', $spk))->post("/spk/{$spk->id}/selesai");
        $response->assertRedirect(route('spk.show', $spk));

        $spk->refresh();
        $this->assertEquals(Spk::SELESAI, $spk->status_spk);

        // SPK berstatus selesai tidak boleh muncul dalam pilihan dropdown input realisasi baru
        session()->flush();
        $responseForm = $this->actingAs($pekerja)->get('/realisasi/create');
        $responseForm->assertDontSee($spk->nomor_spk);

        // Bersihkan
        $spk->delete();
    }

    public function test_alur_lengkap_realisasi_dari_spk_hingga_disetujui_dan_ekspor(): void
    {
        $kepala = Pengguna::whereHas('peran', fn ($q) => $q->where('nama', Peran::KEPALA_LAPANGAN))->first();
        $admin = Pengguna::whereHas('peran', fn ($q) => $q->where('nama', Peran::ADMIN))->first();
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $distrik = Distrik::first();
        $tim = Tim::where('distrik_id', $distrik->id)->first() ?? Tim::first();
        $petaks = Petak::where('distrik_id', $distrik->id)->take(2)->get();
        $pekerjaan = JenisPekerjaan::first();
        $bbm = JenisBbm::first();

        // 1. Kepala Lapangan membuat SPK
        $spk = Spk::create([
            'nomor_spk' => 'SPK/E2E/' . uniqid(),
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'tim_id' => $tim->id,
            'jenis_pekerjaan_id' => $pekerjaan->id,
            'diinput_oleh' => $kepala->id,
            'disetujui_oleh' => $kepala->id,
            'tanggal_persetujuan' => now(),
            'status_spk' => Spk::DISETUJUI,
        ]);

        foreach ($petaks as $p) {
            \App\Models\DetailSpk::create([
                'spk_id' => $spk->id,
                'petak_id' => $p->id,
                'luas_target_ha' => 10.00,
                'tarif_per_ha' => 150000.00,
            ]);
        }

        // 2. Pekerja menginput Laporan Realisasi dengan Helper & Catat BBM
        $nomorLaporan = 'LAP/E2E/' . uniqid();
        $responseInput = $this->actingAs($pekerja)->post('/realisasi', [
            'nomor_laporan' => $nomorLaporan,
            'tim_id' => $tim->id,
            'nama_pilot' => 'Pilot E2E Test',
            'nama_helper' => 'Warga Lokal E2E',
            'spk_id' => $spk->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'ajukan' => 1,
            'catat_bbm' => 1,
            'bbm_jenis_bbm_id' => $bbm->id,
            'bbm_jumlah_liter' => 20.00,
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petaks[0]->id,
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 8.50,
                    'status_pekerjaan' => 'selesai',
                ],
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petaks[1]->id,
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_realisasi_ha' => 9.00,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
        ]);

        $laporan = LaporanRealisasi::where('nomor_laporan', $nomorLaporan)->first();
        $this->assertNotNull($laporan, 'Laporan realisasi harus berhasil tersimpan.');
        $this->assertEquals(LaporanRealisasi::MENUNGGU_PEMERIKSAAN, $laporan->status_laporan);

        // Periksa BBM tertaut terbuat
        $bbmTertaut = TransaksiBbm::where('laporan_realisasi_id', $laporan->id)->first();
        $this->assertNotNull($bbmTertaut, 'Pencatatan BBM terhubung harus otomatis terbentuk.');
        $this->assertEquals(20.00, (float) $bbmTertaut->jumlah_liter);

        // 3. Hak akses keuangan: Hanya Admin yang melihat alokasi korporat (ADM 5% & Investor 15%)
        $responseShowAdmin = $this->actingAs($admin)->get("/realisasi/{$laporan->id}");
        $responseShowAdmin->assertStatus(200);
        $responseShowAdmin->assertSee('Potongan Administrasi (ADM 5%)');
        $responseShowAdmin->assertSee('Bagi Hasil Investor (15%)');
        $responseShowAdmin->assertSee('Sisa Bersih Operasional (80%)');
        $responseShowAdmin->assertSee('Estimasi Jatah Tunai Helper');

        // Pekerja lapangan hanya melihat estimasi hak kru tanpa alokasi korporat
        $responseShowPekerja = $this->actingAs($pekerja)->get("/realisasi/{$laporan->id}");
        $responseShowPekerja->assertStatus(200);
        $responseShowPekerja->assertSee('Hak dan Estimasi Pendapatan Kru Lapangan');
        $responseShowPekerja->assertDontSee('Potongan Administrasi (ADM 5%)');
        $responseShowPekerja->assertDontSee('Bagi Hasil Investor (15%)');

        // Kepala Lapangan melihat detail laporan untuk pemeriksaan
        $responseShow = $this->actingAs($kepala)->get("/realisasi/{$laporan->id}");
        $responseShow->assertStatus(200);
        $responseShow->assertSee($bbmTertaut->nomor_transaksi);

        // 4. Kepala Lapangan menyetujui laporan
        $responseApprove = $this->actingAs($kepala)->post("/realisasi/{$laporan->id}/periksa", [
            'tindakan' => 'setujui',
            'catatan' => 'Pekerjaan sesuai dengan target SPK.',
        ]);
        $laporan->refresh();
        $this->assertEquals(LaporanRealisasi::DISETUJUI, $laporan->status_laporan);

        // 5. Laporan disetujui tidak boleh bisa dihapus
        $responseDel = $this->actingAs($pekerja)->delete("/realisasi/{$laporan->id}");
        $this->assertDatabaseHas('laporan_realisasi', ['id' => $laporan->id]);

        // 6. Ekspor Excel mencakup data laporan yang disetujui ini
        $service = app(EksporExcelService::class);
        $hasilExcel = $service->buatRekapitulasi(
            (int) now()->month,
            (int) now()->year,
            $distrik->id,
            $tim->id,
            null,
            null,
            true,
            $tim->drone_id
        );
        $this->assertFileExists($hasilExcel['path']);
        if (file_exists($hasilExcel['path'])) {
            @unlink($hasilExcel['path']);
        }
        $hasilExcel['ekspor']->delete();

        // Bersihkan data pengujian
        $bbmTertaut->delete();
        $laporan->detail()->delete();
        $laporan->delete();
        $spk->detail()->delete();
        $spk->delete();
    }

    public function test_input_helper_lapangan_maksimal_3_orang_secara_dinamis(): void
    {
        $kepala = Pengguna::whereHas('peran', fn ($q) => $q->where('nama', Peran::KEPALA_LAPANGAN))->first();
        $admin = Pengguna::whereHas('peran', fn ($q) => $q->where('nama', Peran::ADMIN))->first();
        $pekerja = Pengguna::where('nama_pengguna', 'pekerja')->first();
        $distrik = Distrik::first();
        $tim = Tim::where('distrik_id', $distrik->id)->first() ?? Tim::first();
        $petak = Petak::where('distrik_id', $distrik->id)->first();
        $pekerjaan = JenisPekerjaan::first();

        // 1. Buat SPK
        $nomorSpk = 'SPK-HLP-' . uniqid();
        $spk = Spk::create([
            'nomor_spk' => $nomorSpk,
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'tim_id' => $tim->id,
            'jenis_pekerjaan_id' => $pekerjaan->id,
            'diinput_oleh' => $kepala->id,
            'disetujui_oleh' => $kepala->id,
            'tanggal_persetujuan' => now(),
            'status_spk' => Spk::DISETUJUI,
        ]);

        \App\Models\DetailSpk::create([
            'spk_id' => $spk->id,
            'petak_id' => $petak->id,
            'luas_target_ha' => 15.0,
            'tarif_per_ha' => 150000.0,
        ]);

        // 2. Input laporan realisasi dengan 3 orang helper sekaligus (array)
        $nomorLaporan = 'RLP-HLP-' . time();
        $responseCreate = $this->actingAs($pekerja)->post('/realisasi', [
            'nomor_laporan' => $nomorLaporan,
            'tim_id' => $tim->id,
            'nama_pilot' => 'Pilot Handal',
            'nama_copilot' => 'Co-Pilot Sigap',
            'nama_helper' => ['Helper 1 (Pak Joko)', 'Helper 2 (Pak Slamet)', 'Helper 3 (Pak Agus)'],
            'spk_id' => $spk->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak->id,
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_spk_ha' => 15.0,
                    'luas_realisasi_ha' => 15.0,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
        ]);

        $responseCreate->assertRedirect();
        $laporan = LaporanRealisasi::where('nomor_laporan', $nomorLaporan)->first();
        $this->assertNotNull($laporan);

        // String tersimpan rapi digabungkan dengan koma
        $this->assertEquals('Helper 1 (Pak Joko), Helper 2 (Pak Slamet), Helper 3 (Pak Agus)', $laporan->nama_helper);

        // Accessor daftar_helper mengembalikan array 3 orang
        $this->assertCount(3, $laporan->daftar_helper);
        $this->assertEquals(['Helper 1 (Pak Joko)', 'Helper 2 (Pak Slamet)', 'Helper 3 (Pak Agus)'], $laporan->daftar_helper);

        // Halaman detail menampilkan info 3 orang helper
        $responseShow = $this->actingAs($pekerja)->get("/realisasi/{$laporan->id}");
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Helper (3 Orang)');
        $responseShow->assertSee('Helper 1 (Pak Joko)');
        $responseShow->assertSee('Helper 2 (Pak Slamet)');
        $responseShow->assertSee('Helper 3 (Pak Agus)');

        // Verifikasi dua lapis penguncian: status draf dilarang diedit oleh pekerja
        $this->actingAs($pekerja)->get("/realisasi/{$laporan->id}/edit")->assertStatus(403);
        $this->actingAs($pekerja)->put("/realisasi/{$laporan->id}", [
            'spk_id' => $spk->id,
            'tim_id' => $tim->id,
            'distrik_id' => $distrik->id,
            'nama_pilot' => 'Pilot Handal',
        ])->assertStatus(403);

        // Kepala lapangan mengembalikan laporan untuk perbaikan (revisi)
        $laporan->update(['status_laporan' => LaporanRealisasi::PERLU_PERBAIKAN]);

        // 3. Update laporan diizinkan karena berstatus perlu_perbaikan (uji helper dinamis tanpa batas, misal 4 orang)
        $responseUpdate = $this->actingAs($pekerja)->put("/realisasi/{$laporan->id}", [
            'nomor_laporan' => $nomorLaporan,
            'tim_id' => $tim->id,
            'nama_pilot' => 'Pilot Handal',
            'nama_copilot' => 'Co-Pilot Sigap',
            'nama_helper' => ['Helper Baru 1', 'Helper Baru 2', 'Helper Baru 3', 'Helper Baru 4', ''], // item kosong otomatis dibersihkan
            'spk_id' => $spk->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $distrik->id,
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak->id,
                    'jenis_pekerjaan_id' => $pekerjaan->id,
                    'luas_spk_ha' => 15.0,
                    'luas_realisasi_ha' => 15.0,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
        ]);
        $responseUpdate->assertRedirect();
        $laporan->refresh();

        $this->assertEquals('Helper Baru 1, Helper Baru 2, Helper Baru 3, Helper Baru 4', $laporan->nama_helper);
        $this->assertCount(4, $laporan->daftar_helper);

        // Bersihkan data
        $laporan->detail()->delete();
        $laporan->delete();
        $spk->detail()->delete();
        $spk->delete();
    }
}

