<?php

namespace Tests\Feature;

use App\Models\Distrik;
use App\Models\Drone;
use App\Models\JenisPekerjaan;
use App\Models\Pengguna;
use Tests\TestCase;

class MasterToggleStatusTest extends TestCase
{
    private Pengguna $admin;
    private Pengguna $pekerja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Pengguna::where('nama_pengguna', 'admin')->firstOrFail();
        $this->pekerja = Pengguna::where('nama_pengguna', 'pekerja')->firstOrFail();
    }

    public function test_admin_dapat_toggle_status_drone_secara_ajax(): void
    {
        $drone = Drone::create([
            'nomor_drone' => 'TEST-TOGGLE-01',
            'nama_drone' => 'Test Drone 01',
            'tipe_drone' => 'DJI Agras T30',
            'status_operasional' => Drone::STATUS_AKTIF,
            'ambang_batas_servis_ha' => 200,
        ]);

        // Toggle dari aktif ke tidak_beroperasi
        $response = $this->actingAs($this->admin)
            ->patchJson(route('master.drone.toggle-status', $drone));

        $response->assertOk()
            ->assertJson([
                'sukses' => true,
                'aktif' => false,
                'label' => 'Tidak beroperasi',
            ]);

        $drone->refresh();
        $this->assertEquals(Drone::STATUS_TIDAK_BEROPERASI, $drone->status_operasional);

        // Toggle kembali dari tidak_beroperasi ke aktif
        $response2 = $this->actingAs($this->admin)
            ->patchJson(route('master.drone.toggle-status', $drone));

        $response2->assertOk()
            ->assertJson([
                'sukses' => true,
                'aktif' => true,
                'label' => 'Aktif',
            ]);

        $drone->refresh();
        $this->assertEquals(Drone::STATUS_AKTIF, $drone->status_operasional);

        $drone->delete();
    }

    public function test_admin_dapat_toggle_status_jenis_pekerjaan_secara_form_biasa(): void
    {
        $jp = JenisPekerjaan::create([
            'kode_pekerjaan' => 'TEST-KGT',
            'nama_pekerjaan' => 'Kegiatan Uji Coba Toggle',
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('master.jenis-pekerjaan.index'))
            ->patch(route('master.jenis-pekerjaan.toggle-status', $jp));

        $response->assertRedirect(route('master.jenis-pekerjaan.index'));
        $response->assertSessionHas('sukses');

        $jp->refresh();
        $this->assertFalse((bool) $jp->aktif);

        $jp->delete();
    }

    public function test_pekerja_lapangan_dilarang_toggle_status_master(): void
    {
        $distrik = Distrik::firstOrFail();

        $response = $this->actingAs($this->pekerja)
            ->patchJson(route('master.distrik.toggle-status', $distrik));

        $response->assertForbidden();
    }

    public function test_tamu_belum_login_diredirek_ke_halaman_login(): void
    {
        $drone = Drone::firstOrFail();

        $response = $this->patch(route('master.drone.toggle-status', $drone));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_tidak_dapat_menonaktifkan_akun_sendiri(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson(route('master.pengguna.toggle-status', $this->admin));

        $response->assertStatus(422)
            ->assertJson([
                'sukses' => false,
                'pesan' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
            ]);

        $this->admin->refresh();
        $this->assertTrue((bool) $this->admin->aktif);
    }
}

