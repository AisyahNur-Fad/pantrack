<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman root dialihkan ke dashboard / login.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // 1. Halaman login akun
        $responseLogin = $this->get('/login');
        $responseLogin->assertStatus(200);

        // 2. Landing page utama publik untuk tamu (guest)
        $responseLanding = $this->get('/');
        $responseLanding->assertStatus(200);
        $responseLanding->assertSee('PANTRACK');
        $responseLanding->assertSee('PT. Panlibata Andalas Bertuah');

        // 3. Jika pengguna telah login, mengakses root '/' langsung diarahkan ke dashboard
        $user = \App\Models\Pengguna::where('nama_pengguna', 'admin')->first();
        $responseAuth = $this->actingAs($user)->get('/');
        $responseAuth->assertRedirect(route('dashboard'));

        // 4. Mengakses dashboard langsung berstatus 200 OK dan merender UI modern
        $responseDashboard = $this->actingAs($user)->get(route('dashboard'));
        $responseDashboard->assertStatus(200);
        $responseDashboard->assertSee('Ringkasan Finansial', false);
        $responseDashboard->assertSee('Tautan Cepat', false);
        $responseDashboard->assertSee('Stok BBM per Tim', false);
        $responseDashboard->assertSee('data-sidebar-ciut-toggle', false);
    }
}

