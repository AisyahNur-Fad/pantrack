<?php

namespace App\Http\Controllers;

use App\Models\DetailRealisasi;
use App\Models\Distrik;
use App\Models\Drone;
use App\Models\LaporanRealisasi;
use App\Models\Tim;
use Illuminate\View\View;

/**
 * Controller untuk Landing Page publik PANTRACK sebelum masuk ke form akun.
 */
class LandingController extends Controller
{
    public function index()
    {
        // Jika pengguna sudah login, langsung arahkan ke dashboard
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $totalDrone = Drone::count();
        $totalDistrik = Distrik::where('aktif', true)->count();
        $totalTim = Tim::where('status_tim', Tim::STATUS_AKTIF)->count();

        // Total luasan lahan yang berhasil diselesaikan dan berstatus disetujui
        $totalLuasRealisasi = (float) DetailRealisasi::whereHas('laporan', function ($q) {
            $q->where('status_laporan', LaporanRealisasi::DISETUJUI);
        })->sum('luas_realisasi_ha');

        $daftarDrone = Drone::orderBy('nomor_drone')->take(4)->get();
        $daftarDistrik = Distrik::where('aktif', true)->orderBy('nama_distrik')->take(6)->get();

        return view('landing', compact(
            'totalDrone',
            'totalDistrik',
            'totalTim',
            'totalLuasRealisasi',
            'daftarDrone',
            'daftarDistrik'
        ));
    }
}

