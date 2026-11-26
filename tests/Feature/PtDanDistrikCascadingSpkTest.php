<?php

namespace Tests\Feature;

use App\Models\Distrik;
use App\Models\Petak;
use App\Models\Pt;
use App\Models\Pengguna;
use App\Models\Spk;
use Tests\TestCase;

class PtDanDistrikCascadingSpkTest extends TestCase
{
    private Pengguna $admin;
    private Pengguna $kepala;
    private Pt $pt1;
    private Pt $pt2;
    private Distrik $distrikPt1;
    private Distrik $distrikPt2;
    private Petak $petakPt1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();
        $this->kepala = Pengguna::where('nama_pengguna', 'kepala')->firstOrFail();

        // Menggunakan PT resmi sesuai papan monitoring
        $this->pt1 = Pt::where('kode_pt', 'BMH')->firstOrFail();
        $this->pt2 = Pt::where('kode_pt', 'BAP')->firstOrFail();

        // Menggunakan Distrik resmi terkait PT
        $this->distrikPt1 = Distrik::where('kode_distrik', 'DPS')->orWhere('nama_distrik', 'like', '%DPS%')->firstOrFail();
        $this->distrikPt2 = Distrik::where('kode_distrik', 'DAS')->orWhere('nama_distrik', 'like', '%DAS%')->firstOrFail();

        // Siapkan Petak di distrik PT1
        $this->petakPt1 = Petak::firstOrCreate(
            ['kode_petak' => 'DPS-A01', 'distrik_id' => $this->distrikPt1->id],
            ['nama_petak' => 'Blok A01 DPS', 'luas_petak_ha' => 20, 'status_petak' => 'aktif']
        );
    }

    protected function tearDown(): void
    {
        Spk::where('nomor_spk', 'like', 'SPK-TEST-CAS%')->delete();
        Pt::where('kode_pt', 'like', 'PT-TEST-%')->delete();
        parent::tearDown();
    }

    public function test_relasi_eloquent_pt_dan_distrik(): void
    {
        // One-to-Many: PT memiliki banyak Distrik
        $this->assertTrue($this->pt1->distrik->contains($this->distrikPt1));
        $this->assertFalse($this->pt1->distrik->contains($this->distrikPt2));

        // Belongs-To: Distrik dimiliki oleh satu PT
        $this->assertEquals($this->pt1->id, $this->distrikPt1->pt->id);
        $this->assertEquals($this->pt2->id, $this->distrikPt2->pt->id);
    }

    public function test_api_daftar_pt_aktif(): void
    {
        $response = $this->actingAs($this->kepala)->getJson('/api/pt');

        $response->assertOk()
            ->assertJson([
                'sukses' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->pt1->id,
                'kode_pt' => 'BMH',
            ]);
    }

    public function test_api_daftar_distrik_dengan_filter_pt_id(): void
    {
        // Filter distrik milik PT 1
        $response = $this->actingAs($this->kepala)->getJson("/api/distrik?pt_id={$this->pt1->id}");

        $response->assertOk()
            ->assertJson(['sukses' => true])
            ->assertJsonFragment(['nama_distrik' => $this->distrikPt1->nama_distrik])
            ->assertJsonMissing(['nama_distrik' => $this->distrikPt2->nama_distrik]);

        // Filter distrik milik PT 2
        $responsePt2 = $this->actingAs($this->kepala)->getJson("/api/distrik?pt_id={$this->pt2->id}");

        $responsePt2->assertOk()
            ->assertJsonFragment(['nama_distrik' => $this->distrikPt2->nama_distrik])
            ->assertJsonMissing(['nama_distrik' => $this->distrikPt1->nama_distrik]);
    }

    public function test_api_distrik_per_pt_route(): void
    {
        $response = $this->actingAs($this->kepala)->getJson("/api/pt/{$this->pt1->id}/distrik");

        $response->assertOk()
            ->assertJson([
                'sukses' => true,
                'pt' => [
                    'id' => $this->pt1->id,
                    'kode_pt' => 'BMH',
                ],
            ])
            ->assertJsonFragment(['id' => $this->distrikPt1->id]);
    }

    public function test_halaman_create_spk_memuat_pilihan_pt_dan_dropdown_cascading(): void
    {
        $response = $this->actingAs($this->kepala)->get(route('spk.create'));

        $response->assertOk();
        $response->assertSee('Pilih Perusahaan (PT)');
        $response->assertSee('Pilih Distrik');
        $response->assertSee('BMH');
        $response->assertSee('loadingDistrik');
    }

    public function test_validasi_store_spk_wajib_pt_dan_distrik_dan_harus_sesuai_pt(): void
    {
        // 1. PT dan Distrik kosong
        $res1 = $this->actingAs($this->kepala)->post(route('spk.store'), [
            'nomor_spk' => 'SPK-TEST-CAS-' . uniqid(),
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'petak' => [
                ['petak_id' => $this->petakPt1->id, 'luas_target_ha' => 10],
            ],
        ]);
        $res1->assertSessionHasErrors(['pt_id', 'distrik_id']);

        // 2. Distrik bukan milik PT yang dipilih (mismatch PT2 dengan distrik PT1)
        $res2 = $this->actingAs($this->kepala)->post(route('spk.store'), [
            'nomor_spk' => 'SPK-TEST-CAS-' . uniqid(),
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'pt_id' => $this->pt2->id,
            'distrik_id' => $this->distrikPt1->id, // Distrik milik PT1, bukan PT2!
            'petak' => [
                ['petak_id' => $this->petakPt1->id, 'luas_target_ha' => 10],
            ],
        ]);
        $res2->assertSessionHasErrors(['distrik_id']);
    }

    public function test_store_spk_berhasil_dengan_pt_dan_distrik_valid(): void
    {
        $nomorSpk = 'SPK-TEST-CAS-' . uniqid();
        $response = $this->actingAs($this->kepala)->post(route('spk.store'), [
            'nomor_spk' => $nomorSpk,
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'pt_id' => $this->pt1->id,
            'distrik_id' => $this->distrikPt1->id,
            'petak' => [
                ['petak_id' => $this->petakPt1->id, 'luas_target_ha' => 15.5],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('spk', [
            'nomor_spk' => $nomorSpk,
            'distrik_id' => $this->distrikPt1->id,
        ]);
    }

    public function test_kompatibilitas_mundur_store_spk_jika_pt_id_tidak_dikirim(): void
    {
        // Jika request lama mengirim distrik_id tanpa pt_id, controller otomatis mengisi pt_id dari distrik
        $nomorSpk = 'SPK-TEST-CAS-' . uniqid();
        $response = $this->actingAs($this->kepala)->post(route('spk.store'), [
            'nomor_spk' => $nomorSpk,
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'distrik_id' => $this->distrikPt1->id, // pt_id sengaja tidak dikirim
            'petak' => [
                ['petak_id' => $this->petakPt1->id, 'luas_target_ha' => 12],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('spk', [
            'nomor_spk' => $nomorSpk,
            'distrik_id' => $this->distrikPt1->id,
        ]);
    }

    public function test_halaman_edit_spk_memuat_pt_dan_distrik_terpilih(): void
    {
        $spk = Spk::create([
            'nomor_spk' => 'SPK-TEST-CAS-' . uniqid(),
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'distrik_id' => $this->distrikPt1->id,
            'diinput_oleh' => $this->kepala->id,
            'disetujui_oleh' => $this->kepala->id,
            'tanggal_persetujuan' => now(),
            'status_spk' => Spk::DISETUJUI,
        ]);

        $response = $this->actingAs($this->kepala)->get(route('spk.edit', $spk));

        $response->assertOk();
        $response->assertSee('Pilih Perusahaan (PT)');
        $response->assertSee('Pilih Distrik');
        $response->assertSee($this->pt1->nama_pt);
        $response->assertSee($this->distrikPt1->nama_distrik);
    }

    public function test_admin_dapat_mengakses_dan_menambah_master_pt(): void
    {
        $response = $this->actingAs($this->admin)->get(route('master.pt.index'));
        $response->assertOk();
        $response->assertSee('Perusahaan (PT)');

        $kodeBaru = 'PT-TEST-' . rand(100, 999);
        $resStore = $this->actingAs($this->admin)->post(route('master.pt.store'), [
            'kode_pt' => $kodeBaru,
            'nama_pt' => 'PT Perkebunan Uji Coba',
            'keterangan' => 'Unit uji coba otomatis',
            'aktif' => 1,
        ]);

        $resStore->assertRedirect(route('master.pt.index'));
        $this->assertDatabaseHas('pt', [
            'kode_pt' => $kodeBaru,
            'nama_pt' => 'PT Perkebunan Uji Coba',
        ]);
    }

    public function test_admin_melihat_4_pt_dan_20_distrik_papan_monitoring(): void
    {
        // 1. Admin melihat PT: BMH, BAP, SBA, CBS pada menu Master PT
        $responsePt = $this->actingAs($this->admin)->get(route('master.pt.index'));
        $responsePt->assertOk();
        $responsePt->assertSee('BMH');
        $responsePt->assertSee('BAP');
        $responsePt->assertSee('SBA');
        $responsePt->assertSee('CBS');

        // 2. Admin melihat Distrik pada menu Master Distrik
        $responseDistrik = $this->actingAs($this->admin)->get(route('master.distrik.index'));
        $responseDistrik->assertOk();
        $responseDistrik->assertSee('PADANG SUGIHAN');
        $responseDistrik->assertSee('AIR SUGIHAN');
        $responseDistrik->assertSee('SIMPANG HERAN');
    }

    public function test_cascading_api_distrik_sesuai_pemetaan_papan_monitoring(): void
    {
        $ptBmh = Pt::where('kode_pt', 'BMH')->firstOrFail();
        $ptBap = Pt::where('kode_pt', 'BAP')->firstOrFail();
        $ptSba = Pt::where('kode_pt', 'SBA')->firstOrFail();

        // BMH: 7 distrik (DPS, DST, DSB, DSP, DSK, DSG, DSS)
        $resBmh = $this->actingAs($this->kepala)->getJson("/api/distrik?pt_id={$ptBmh->id}");
        $resBmh->assertOk()->assertJsonCount(7, 'data');

        // BAP: 7 distrik (DAS, DSJ, DTJ, DTK, DBR, DBT, DSH)
        $resBap = $this->actingAs($this->kepala)->getJson("/api/distrik?pt_id={$ptBap->id}");
        $resBap->assertOk()->assertJsonCount(7, 'data');

        // SBA: 5 distrik (DLH, DTP, DKL, DSR, DTD)
        $resSba = $this->actingAs($this->kepala)->getJson("/api/distrik?pt_id={$ptSba->id}");
        $resSba->assertOk()->assertJsonCount(5, 'data');
    }
}
