<?php

namespace Tests\Feature;

use App\Models\AnggotaTim;
use App\Models\DetailSpk;
use App\Models\Distrik;
use App\Models\Drone;
use App\Models\JenisPekerjaan;
use App\Models\Pengguna;
use App\Models\Petak;
use App\Models\Spk;
use App\Models\Tim;
use Tests\TestCase;

class DroneNamaMasterTest extends TestCase
{
    private Pengguna $admin;
    private Pengguna $pekerja;

    protected function setUp(): void
    {
        parent::setUp();
        Drone::where('nomor_drone', 'like', 'DRN-TEST-%')->delete();
        $this->admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();
        $this->pekerja = Pengguna::where('nama_pengguna', 'pekerja')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Drone::where('nomor_drone', 'like', 'DRN-TEST-%')->delete();
        parent::tearDown();
    }

    public function test_admin_melihat_kolom_nama_drone_pada_daftar_master(): void
    {
        $dronePertama = Drone::orderBy('nomor_drone')->firstOrFail();

        $response = $this->actingAs($this->admin)->get(route('master.drone.index'));
        $response->assertOk();
        $response->assertSee('Nomor drone');
        $response->assertSee('Nama drone');
        $response->assertSee('Tipe drone');
        $response->assertSee($dronePertama->nama_drone);
    }

    public function test_admin_bisa_tambah_drone_dengan_nama_drone(): void
    {
        $nomor = 'DRN-TEST-' . uniqid();
        $response = $this->actingAs($this->admin)->post(route('master.drone.store'), [
            'nomor_drone' => $nomor,
            'nama_drone' => 'Drone Bertuah Gamma 03',
            'tipe_drone' => 'DJI Agras T50',
            'status_operasional' => 'aktif',
            'ambang_batas_servis_ha' => 250,
            'keterangan' => 'Unit uji coba baru',
        ]);

        $response->assertRedirect(route('master.drone.index'));
        $this->assertDatabaseHas('drone', [
            'nomor_drone' => $nomor,
            'nama_drone' => 'Drone Bertuah Gamma 03',
            'tipe_drone' => 'DJI Agras T50',
        ]);
    }

    public function test_validasi_nama_drone_wajib_diisi(): void
    {
        $nomor = 'DRN-TEST-' . uniqid();
        $response = $this->actingAs($this->admin)->post(route('master.drone.store'), [
            'nomor_drone' => $nomor,
            'nama_drone' => '',
            'tipe_drone' => 'DJI Agras T50',
            'status_operasional' => 'aktif',
            'ambang_batas_servis_ha' => 200,
        ]);

        $response->assertSessionHasErrors('nama_drone');
    }

    public function test_admin_bisa_mengubah_nama_drone(): void
    {
        $drone = Drone::orderBy('nomor_drone')->firstOrFail();
        $namaAwal = $drone->nama_drone;
        $namaBaru = $namaAwal . ' Updated';

        $response = $this->actingAs($this->admin)->put(route('master.drone.update', $drone), [
            'nomor_drone' => $drone->nomor_drone,
            'nama_drone' => $namaBaru,
            'tipe_drone' => $drone->tipe_drone,
            'status_operasional' => $drone->status_operasional,
            'ambang_batas_servis_ha' => $drone->ambang_batas_servis_ha,
            'keterangan' => 'Catatan update',
        ]);

        $response->assertRedirect(route('master.drone.index'));
        $drone->refresh();
        $this->assertEquals($namaBaru, $drone->nama_drone);

        // Kembalikan nama semula agar data tetap murni
        $drone->update(['nama_drone' => $namaAwal]);
    }

    public function test_database_memiliki_33_unit_drone_resmi_dengan_nomor_tipe_dan_vendor_terpisah(): void
    {
        $this->assertEquals(33, Drone::count());

        // Verifikasi unit 601 (Vendor PAB DL, Nomor 601, Tipe T70P)
        $drone601 = Drone::where('nomor_drone', '601')->first();
        $this->assertNotNull($drone601);
        $this->assertEquals('T70P', $drone601->tipe_drone);
        $this->assertEquals('PAB DL', $drone601->nama_drone);
        $this->assertStringContainsString('DST', $drone601->lokasi_penugasan);
        $this->assertNull($drone601->keterangan);

        // Verifikasi unit 401 (Vendor ESJB, Nomor 401, Tipe T25P)
        $drone401 = Drone::where('nomor_drone', '401')->first();
        $this->assertNotNull($drone401);
        $this->assertEquals('T25P', $drone401->tipe_drone);
        $this->assertEquals('ESJB', $drone401->nama_drone);

        // Verifikasi unit 809 (Vendor PAB-AYA, Nomor 809, Tipe T50)
        $drone809 = Drone::where('nomor_drone', '809')->first();
        $this->assertNotNull($drone809);
        $this->assertEquals('T50', $drone809->tipe_drone);
        $this->assertEquals('PAB-AYA', $drone809->nama_drone);
        $this->assertStringContainsString('DLH', $drone809->lokasi_penugasan);
        $this->assertNull($drone809->keterangan);

        // Verifikasi tidak ada nomor gabungan lama
        $this->assertNull(Drone::where('nomor_drone', 'PAB-DL-601')->first());
        $this->assertNull(Drone::where('nomor_drone', 'DRN-T40-001')->first());
    }

    public function test_admin_dapat_mengubah_status_drone_ke_perawatan_dengan_catatan(): void
    {
        $drone = Drone::where('nomor_drone', '704')->firstOrFail();

        $response = $this->actingAs($this->admin)->patchJson(route('master.drone.update-status', $drone), [
            'status_operasional' => Drone::STATUS_PERAWATAN,
            'catatan_perawatan' => 'Ganti 2 pasang propeller & servis pompa nozzle di workshop',
        ]);

        $response->assertOk()
            ->assertJson([
                'sukses' => true,
                'status_operasional' => Drone::STATUS_PERAWATAN,
                'label' => 'Perawatan',
                'catatan_perawatan' => 'Ganti 2 pasang propeller & servis pompa nozzle di workshop',
            ]);

        $drone->refresh();
        $this->assertEquals(Drone::STATUS_PERAWATAN, $drone->status_operasional);
        $this->assertEquals('Ganti 2 pasang propeller & servis pompa nozzle di workshop', $drone->catatan_perawatan);

        // Pastikan tampilan index menampilkan status dan ringkasan
        $resIndex = $this->actingAs($this->admin)->get(route('master.drone.index', ['cari' => '704']));
        $resIndex->assertOk();
        $resIndex->assertSee('Perawatan');
        $resIndex->assertSee('Ganti 2 pasang propeller & servis pompa nozzle di workshop');
        $resIndex->assertSee('Dalam Perawatan');
        $resIndex->assertSee('Siap Kerja (Aktif)');
    }

    public function test_17_tim_operasional_terhubung_dengan_drone_dan_pilot_papan_monitoring(): void
    {
        // Verifikasi Tim DST Drone 601
        $drone601 = Drone::where('nomor_drone', '601')->firstOrFail();
        $tim601 = Tim::where('drone_id', $drone601->id)->with(['drone', 'distrik', 'anggota'])->first();
        $this->assertNotNull($tim601);
        $this->assertStringContainsString('DST', (string) $tim601->distrik?->nama_distrik);
        $pilot601 = $tim601->anggota->firstWhere('jabatan_tim', AnggotaTim::PILOT);
        $this->assertNotNull($pilot601);
        $this->assertStringContainsString('Aditya', $pilot601->nama_anggota);

        // Verifikasi Tim DTJ Drone 711
        $drone711 = Drone::where('nomor_drone', '711')->firstOrFail();
        $tim711 = Tim::where('drone_id', $drone711->id)->with(['drone', 'distrik', 'anggota'])->first();
        $this->assertNotNull($tim711);
        $this->assertStringContainsString('DTJ', (string) $tim711->distrik?->nama_distrik);
        $pilot711 = $tim711->anggota->firstWhere('jabatan_tim', AnggotaTim::PILOT);
        $this->assertNotNull($pilot711);
        $this->assertStringContainsString('Jeksen', $pilot711->nama_anggota);

        // Verifikasi Tim DLH Drone 605
        $drone605 = Drone::where('nomor_drone', '605')->firstOrFail();
        $tim605 = Tim::where('drone_id', $drone605->id)->with(['drone', 'distrik', 'anggota'])->first();
        $this->assertNotNull($tim605);
        $this->assertStringContainsString('DLH', (string) $tim605->distrik?->nama_distrik);
        $pilot605 = $tim605->anggota->firstWhere('jabatan_tim', AnggotaTim::PILOT);
        $this->assertNotNull($pilot605);
        $this->assertStringContainsString('Rico', $pilot605->nama_anggota);

        // Verifikasi bahwa helper tidak didata di Master Tim (karena merupakan tenaga warga lokal dinamis di lapangan)
        $this->assertEquals(0, AnggotaTim::where('jabatan_tim', AnggotaTim::HELPER)->count());
    }

    public function test_pekerja_dilarang_menginput_realisasi_jika_drone_tim_sedang_perawatan_atau_tidak_beroperasi(): void
    {
        // Cari SPK aktif disetujui
        $spk = Spk::where('status_spk', Spk::DISETUJUI)->with('detail.petak')->firstOrFail();
        $petak = $spk->detail->first()->petak;
        $jenisPekerjaan = JenisPekerjaan::where('aktif', true)->firstOrFail();

        // Cari tim yang diakses pekerja
        $idTimList = $this->pekerja->idTimYangDiakses();
        $tim = Tim::whereIn('id', $idTimList)->with('drone')->first();
        if (! $tim) {
            $tim = Tim::first();
            AnggotaTim::firstOrCreate([
                'tim_id' => $tim->id,
                'pengguna_id' => $this->pekerja->id,
                'jabatan_tim' => AnggotaTim::PILOT,
                'nama_anggota' => $this->pekerja->nama_lengkap,
            ]);
        }

        $drone = $tim->drone;
        $this->assertNotNull($drone);

        // 1. Ubah status drone menjadi perawatan
        $drone->update([
            'status_operasional' => Drone::STATUS_PERAWATAN,
            'catatan_perawatan' => 'Kerusakan pompa nozzle & ganti propeller',
        ]);

        // 2. Akses halaman create realisasi -> option tim harus berlabel ⛔ dan disabled
        $resCreate = $this->actingAs($this->pekerja)->get(route('realisasi.create'));
        $resCreate->assertOk();
        $resCreate->assertSee('⛔');
        $resCreate->assertSee('TIDAK DAPAT DIGUNAKAN');

        // 3. Coba kirim data realisasi -> harus ditolak oleh validasi server tim_id
        $resStore = $this->actingAs($this->pekerja)->post(route('realisasi.store'), [
            'nomor_laporan' => 'LAP/TEST/' . uniqid(),
            'tim_id' => $tim->id,
            'spk_id' => $spk->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $spk->distrik_id,
            'nama_pilot' => 'Budi Santoso',
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak->id,
                    'jenis_pekerjaan_id' => $jenisPekerjaan->id,
                    'luas_realisasi_ha' => 10.0,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
        ]);

        $resStore->assertSessionHasErrors(['tim_id']);
        $errors = session('errors')->get('tim_id');
        $this->assertStringContainsString('Perawatan', $errors[0]);
        $this->assertStringContainsString('tidak dapat digunakan', $errors[0]);

        // 4. Kembalikan status drone ke aktif
        $drone->update([
            'status_operasional' => Drone::STATUS_AKTIF,
            'catatan_perawatan' => null,
        ]);

        // Coba kirim lagi -> validasi tim_id tidak boleh error
        $resStoreSukses = $this->actingAs($this->pekerja)->post(route('realisasi.store'), [
            'nomor_laporan' => 'LAP/TEST/' . uniqid(),
            'tim_id' => $tim->id,
            'spk_id' => $spk->id,
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'distrik_id' => $spk->distrik_id,
            'nama_pilot' => 'Budi Santoso',
            'petak' => [
                [
                    'tanggal_pekerjaan' => now()->toDateString(),
                    'petak_id' => $petak->id,
                    'jenis_pekerjaan_id' => $jenisPekerjaan->id,
                    'luas_realisasi_ha' => 10.0,
                    'status_pekerjaan' => 'selesai',
                ],
            ],
        ]);

        $resStoreSukses->assertSessionDoesntHaveErrors(['tim_id']);
    }

    public function test_penanggung_jawab_tim_hanya_menarik_pilot_dan_mencegah_pilot_ganda_pada_drone_aktif(): void
    {
        // 1. Akses halaman create tim
        $responseCreate = $this->actingAs($this->admin)->get(route('master.tim.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('Pilot Penanggung Jawab Drone');

        // Pastikan opsi penanggung jawab tidak memuat akun admin atau atasan
        $responseCreate->assertDontSee($this->admin->nama_lengkap . ' (Admin)');

        // 2. Coba simpan tim dengan penanggung jawab non-pilot (misal admin) -> harus gagal validasi peran
        $droneBaru = Drone::create([
            'nomor_drone' => 'DRN-TEST-P1',
            'nama_drone' => 'Vendor Test',
            'tipe_drone' => 'T50',
            'status_operasional' => 'aktif',
        ]);

        $resNonPilot = $this->actingAs($this->admin)->post(route('master.tim.store'), [
            'nama_tim' => 'Tim Uji Coba Non Pilot',
            'drone_id' => $droneBaru->id,
            'kepala_tim_id' => $this->admin->id,
            'status_tim' => 'aktif',
        ]);
        $resNonPilot->assertSessionHasErrors(['kepala_tim_id']);

        // 3. Coba assign pilot yang sudah aktif memegang drone lain (misal Aditya) ke drone baru
        $timAditya = Tim::where('status_tim', 'aktif')->whereNotNull('kepala_tim_id')->firstOrFail();
        $pilotAktif = $timAditya->kepalaTim;

        $resPilotBentrok = $this->actingAs($this->admin)->post(route('master.tim.store'), [
            'nama_tim' => 'Tim Uji Coba Pilot Bentrok',
            'drone_id' => $droneBaru->id,
            'kepala_tim_id' => $pilotAktif->id,
            'status_tim' => 'aktif',
        ]);
        $resPilotBentrok->assertSessionHasErrors(['kepala_tim_id']);
        $errorMsg = session('errors')->get('kepala_tim_id')[0];
        $this->assertStringContainsString('Satu pilot hanya dapat bertanggung jawab atas satu unit drone aktif', $errorMsg);

        // 4. Buat pilot baru yang siap tugas dan assign ke tim baru -> harus berhasil dan otomatis sinkron ke anggota_tim
        $pilotBaru = Pengguna::create([
            'peran_id' => $this->pekerja->peran_id,
            'nama_lengkap' => 'Rahmat Pilot Baru',
            'nama_pengguna' => 'rahmatpilot',
            'email' => 'rahmat@pantrack.local',
            'kata_sandi' => 'password',
            'aktif' => true,
        ]);

        $resSukses = $this->actingAs($this->admin)->post(route('master.tim.store'), [
            'nama_tim' => 'Tim Uji Coba Sukses Pilot',
            'drone_id' => $droneBaru->id,
            'kepala_tim_id' => $pilotBaru->id,
            'status_tim' => 'aktif',
        ]);
        $resSukses->assertRedirect(route('master.tim.index'));

        $timBaru = Tim::where('nama_tim', 'Tim Uji Coba Sukses Pilot')->first();
        $this->assertNotNull($timBaru);
        $this->assertEquals($pilotBaru->id, $timBaru->kepala_tim_id);

        // Verifikasi otomatis sinkron ke anggota_tim dengan jabatan pilot
        $anggotaPilot = AnggotaTim::where('tim_id', $timBaru->id)->where('jabatan_tim', AnggotaTim::PILOT)->first();
        $this->assertNotNull($anggotaPilot);
        $this->assertEquals($pilotBaru->id, $anggotaPilot->pengguna_id);
        $this->assertEquals('Rahmat Pilot Baru', $anggotaPilot->nama_anggota);

        // Bersihkan data uji coba
        $timBaru->anggota()->delete();
        $timBaru->delete();
        $pilotBaru->delete();
        $droneBaru->delete();
    }

    public function test_status_aktif_otomatis_membersihkan_catatan_kendala_lama(): void
    {
        $drone = Drone::where('nomor_drone', '704')->firstOrFail();

        // 1. Set ke perawatan dulu dengan catatan servis
        $drone->update([
            'status_operasional' => Drone::STATUS_PERAWATAN,
            'catatan_perawatan' => 'Ganti oli dan kalibrasi pompa nozzle',
        ]);

        // 2. Admin mengembalikan status ke Aktif (unit selesai diservis dan sehat)
        $response = $this->actingAs($this->admin)->patchJson(route('master.drone.update-status', $drone), [
            'status_operasional' => Drone::STATUS_AKTIF,
            'catatan_perawatan' => '',
        ]);

        $response->assertOk()
            ->assertJson([
                'sukses' => true,
                'status_operasional' => Drone::STATUS_AKTIF,
                'label' => 'Aktif',
                'catatan_perawatan' => '',
            ]);

        $drone->refresh();
        $this->assertEquals(Drone::STATUS_AKTIF, $drone->status_operasional);
        $this->assertNull($drone->catatan_perawatan);
    }

    public function test_input_catatan_kendala_pada_status_aktif_otomatis_dialihkan_ke_perawatan(): void
    {
        $drone = Drone::where('nomor_drone', '704')->firstOrFail();
        $drone->update([
            'status_operasional' => Drone::STATUS_AKTIF,
            'catatan_perawatan' => null,
        ]);

        // Admin mengisi catatan kendala tapi status radio masih 'aktif'
        $response = $this->actingAs($this->admin)->patchJson(route('master.drone.update-status', $drone), [
            'status_operasional' => Drone::STATUS_AKTIF,
            'catatan_perawatan' => 'Baling-baling retak saat mendarat darurat',
        ]);

        $response->assertOk()
            ->assertJson([
                'sukses' => true,
                'status_operasional' => Drone::STATUS_PERAWATAN,
                'label' => 'Perawatan',
                'catatan_perawatan' => 'Baling-baling retak saat mendarat darurat',
            ]);

        $drone->refresh();
        $this->assertEquals(Drone::STATUS_PERAWATAN, $drone->status_operasional);
        $this->assertEquals('Baling-baling retak saat mendarat darurat', $drone->catatan_perawatan);
    }

    public function test_status_tidak_beroperasi_menyimpan_alasan_kendala_berat(): void
    {
        $drone = Drone::where('nomor_drone', '704')->firstOrFail();

        $response = $this->actingAs($this->admin)->patchJson(route('master.drone.update-status', $drone), [
            'status_operasional' => Drone::STATUS_TIDAK_BEROPERASI,
            'catatan_perawatan' => 'Crash parah di petak SRA5150, arm kanan patah & motor terbakar',
        ]);

        $response->assertOk()
            ->assertJson([
                'sukses' => true,
                'status_operasional' => Drone::STATUS_TIDAK_BEROPERASI,
                'label' => 'Tidak beroperasi',
                'catatan_perawatan' => 'Crash parah di petak SRA5150, arm kanan patah & motor terbakar',
            ]);

        $drone->refresh();
        $this->assertEquals(Drone::STATUS_TIDAK_BEROPERASI, $drone->status_operasional);
        $this->assertEquals('Crash parah di petak SRA5150, arm kanan patah & motor terbakar', $drone->catatan_perawatan);
        $this->assertEquals('Gudang Logistik (Nonaktif)', $drone->lokasi_penugasan);

        // Kembalikan ke aktif dan bersihkan agar tidak merusak state tes lain
        $drone->update([
            'status_operasional' => Drone::STATUS_AKTIF,
            'catatan_perawatan' => null,
        ]);
    }

    public function test_spk_mencegah_penugasan_tim_jika_drone_tidak_beroperasi(): void
    {
        $tim = Tim::where('status_tim', 'aktif')->whereNotNull('drone_id')->firstOrFail();
        $drone = $tim->drone;
        $drone->update([
            'status_operasional' => Drone::STATUS_TIDAK_BEROPERASI,
            'catatan_perawatan' => 'Motor dinamo rusak dan menunggu suku cadang',
        ]);

        $distrik = Distrik::firstOrFail();
        $petak = Petak::where('distrik_id', $distrik->id)->firstOrFail();
        $jenisPekerjaan = JenisPekerjaan::where('aktif', true)->firstOrFail();

        $kepalaLapangan = Pengguna::whereHas('peran', fn ($q) => $q->where('nama', \App\Models\Peran::KEPALA_LAPANGAN))->firstOrFail();

        $response = $this->actingAs($kepalaLapangan)->post(route('spk.store'), [
            'nomor_spk' => 'SPK/TEST/TB/' . uniqid(),
            'tanggal_spk' => now()->toDateString(),
            'periode_bulan' => (int) now()->month,
            'periode_tahun' => (int) now()->year,
            'pt_id' => $distrik->pt_id,
            'distrik_id' => $distrik->id,
            'jenis_pekerjaan_id' => $jenisPekerjaan->id,
            'tim_id' => $tim->id,
            'petak' => [
                [
                    'petak_id' => $petak->id,
                    'luas_target_ha' => 15.0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['tim_id']);
        $errorMsg = session('errors')->get('tim_id')[0];
        $this->assertStringContainsString('TIDAK BEROPERASI', $errorMsg);
        $this->assertStringContainsString($drone->nomor_drone, $errorMsg);

        // Kembalikan drone ke aktif
        $drone->update([
            'status_operasional' => Drone::STATUS_AKTIF,
            'catatan_perawatan' => null,
        ]);
    }
}
