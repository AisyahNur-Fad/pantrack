<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BbmController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanRealisasiController;
use App\Http\Controllers\Master\AnggotaTimController;
use App\Http\Controllers\Master\DistrikController;
use App\Http\Controllers\Master\DroneController;
use App\Http\Controllers\Master\JenisBbmController;
use App\Http\Controllers\Master\JenisPekerjaanController;
use App\Http\Controllers\Master\PenggunaController;
use App\Http\Controllers\Master\PetakController;
use App\Http\Controllers\Master\TarifPekerjaanController;
use App\Http\Controllers\Master\TimController;
use App\Http\Controllers\RiwayatAktivitasController;
use App\Http\Controllers\SpkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute PANTRACK
|--------------------------------------------------------------------------
| Urutan: publik (login) -> wajib login -> dibatasi peran.
| Hak akses diterapkan di backend lewat middleware `peran` (lihat BatasiPeran).
*/

Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('landing');

// --- Publik: hanya untuk tamu -------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'formLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
});

// --- Wajib login --------------------------------------------------------------
Route::middleware(['auth', 'aktif'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/riwayat-aktivitas', [RiwayatAktivitasController::class, 'index'])
        ->name('riwayat.index');

    // --- Profil & Ganti Kata Sandi (Semua Pengguna) --------------------------
    Route::get('/profil', [\App\Http\Controllers\ProfilController::class, 'index'])->name('profil.index');
    Route::put('/profil', [\App\Http\Controllers\ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/kata-sandi', [\App\Http\Controllers\ProfilController::class, 'updatePassword'])->name('profil.password');

    // --- API Cascading Dropdown (PT & Distrik) --------------------------------
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('pt', [\App\Http\Controllers\PtController::class, 'apiDaftarPt'])->name('pt.index');
        Route::get('pt/{pt}/distrik', [\App\Http\Controllers\PtController::class, 'apiDistrikPerPt'])->name('pt.distrik');
        Route::get('distrik', [\App\Http\Controllers\PtController::class, 'apiDaftarDistrik'])->name('distrik.index');
    });


    // --- Operasional: SPK (Kepala Lapangan membuat, Admin/Atasan menilai & konfirmasi) ---
    Route::middleware('peran:kepala_lapangan')->group(function () {
        Route::get('spk/create', [SpkController::class, 'create'])->name('spk.create');
        Route::post('spk', [SpkController::class, 'store'])->name('spk.store');
        Route::get('spk/{spk}/edit', [SpkController::class, 'edit'])->name('spk.edit');
        Route::put('spk/{spk}', [SpkController::class, 'update'])->name('spk.update');
        Route::delete('spk/{spk}', [SpkController::class, 'destroy'])->name('spk.destroy');
        Route::post('spk/{spk}/selesai', [SpkController::class, 'selesai'])->name('spk.selesai');
    });

    // Konfirmasi & Penilaian SPK: Khusus Admin & Atasan
    Route::middleware('peran:admin,atasan')->group(function () {
        Route::post('spk/{spk}/verifikasi', [SpkController::class, 'verifikasi'])->name('spk.verifikasi');
    });

    Route::middleware('peran:admin,pekerja_lapangan,kepala_lapangan,atasan')->group(function () {
        Route::get('spk', [SpkController::class, 'index'])->name('spk.index');
        Route::get('spk/{spk}', [SpkController::class, 'show'])->name('spk.show');
        Route::get('spk/{spk}/berkas', [SpkController::class, 'unduhBerkas'])->name('spk.berkas');
    });

    // --- Operasional: Laporan Realisasi Pekerjaan (FR-08 s/d FR-12) ------------
    Route::middleware('peran:admin,pekerja_lapangan,kepala_lapangan,atasan')->group(function () {
        Route::get('realisasi', [LaporanRealisasiController::class, 'index'])->name('realisasi.index');
        Route::get('realisasi/lampiran/{lampiran}', [LaporanRealisasiController::class, 'unduhLampiran'])
            ->name('realisasi.lampiran');

        // Pengisian, revisi, pengajuan, dan penghapusan laporan khusus Pekerja Lapangan
        Route::middleware('peran:pekerja_lapangan')->group(function () {
            Route::get('realisasi/create', [LaporanRealisasiController::class, 'create'])->name('realisasi.create');
            Route::post('realisasi', [LaporanRealisasiController::class, 'store'])->name('realisasi.store');
            Route::get('realisasi/{realisasi}/edit', [LaporanRealisasiController::class, 'edit'])->name('realisasi.edit');
            Route::put('realisasi/{realisasi}', [LaporanRealisasiController::class, 'update'])->name('realisasi.update');
            Route::patch('realisasi/{realisasi}', [LaporanRealisasiController::class, 'update']);
            Route::post('realisasi/{realisasi}/ajukan', [LaporanRealisasiController::class, 'ajukan'])->name('realisasi.ajukan');
            Route::delete('realisasi/{realisasi}', [LaporanRealisasiController::class, 'destroy'])->name('realisasi.destroy');
        });

        Route::get('realisasi/{realisasi}', [LaporanRealisasiController::class, 'show'])->name('realisasi.show');

        Route::post('realisasi/{realisasi}/periksa', [LaporanRealisasiController::class, 'periksa'])
            ->middleware('peran:kepala_lapangan')
            ->name('realisasi.periksa');
    });

    // --- Operasional: Monitoring & Transaksi BBM (FR-16 s/d FR-21) -------------
    Route::middleware('peran:admin,kepala_lapangan,pekerja_lapangan,atasan')->group(function () {
        Route::get('bbm', [BbmController::class, 'index'])->name('bbm.index');
        Route::get('bbm/tim/{tim}', [BbmController::class, 'detailTim'])->name('bbm.detail-tim');
        Route::get('bbm/lampiran/{lampiran}', [BbmController::class, 'unduhLampiran'])->name('bbm.lampiran');

        // Hanya Admin dan Pekerja Lapangan yang boleh mencatat/mengubah/menghapus transaksi BBM
        Route::middleware('peran:admin,pekerja_lapangan')->group(function () {
            Route::get('bbm/create', [BbmController::class, 'create'])->name('bbm.create');
            Route::post('bbm', [BbmController::class, 'store'])->name('bbm.store');
            Route::get('bbm/{bbm}/edit', [BbmController::class, 'edit'])->name('bbm.edit');
            Route::put('bbm/{bbm}', [BbmController::class, 'update'])->name('bbm.update');
            Route::patch('bbm/{bbm}', [BbmController::class, 'update']);
            Route::delete('bbm/{bbm}', [BbmController::class, 'destroy'])->name('bbm.destroy');
        });

        Route::get('bbm/{bbm}', [BbmController::class, 'show'])->name('bbm.show');

        Route::post('bbm/{bbm}/verifikasi', [BbmController::class, 'verifikasi'])
            ->middleware('peran:admin')
            ->name('bbm.verifikasi');
    });

    // --- Laporan: Ekspor Excel (FR-24, FR-25) ---------------------------------
    Route::middleware('peran:admin,atasan')->prefix('ekspor')->group(function () {
        Route::get('/', [\App\Http\Controllers\EksporLaporanController::class, 'index'])->name('ekspor.index');
        Route::post('/proses', [\App\Http\Controllers\EksporLaporanController::class, 'ekspor'])->name('ekspor.proses');
        Route::get('/{ekspor}/unduh', [\App\Http\Controllers\EksporLaporanController::class, 'unduh'])->name('ekspor.unduh');
        Route::get('/{ekspor}/unduh-pdf', [\App\Http\Controllers\EksporLaporanController::class, 'unduhPdf'])->name('ekspor.unduh-pdf');
    });

    // --- Keuangan & Bon Operasional Unit Drone: Khusus Admin & Atasan ---------
    Route::middleware('peran:admin,atasan')->prefix('keuangan')->group(function () {
        Route::get('/', [\App\Http\Controllers\KeuanganUnitController::class, 'index'])->name('keuangan.index');
        Route::get('/pengeluaran/create', [\App\Http\Controllers\KeuanganUnitController::class, 'createPengeluaran'])->name('keuangan.pengeluaran.create');
        Route::post('/pengeluaran', [\App\Http\Controllers\KeuanganUnitController::class, 'storePengeluaran'])->name('keuangan.pengeluaran.store');
        Route::get('/pengeluaran/{pengeluaran}/edit', [\App\Http\Controllers\KeuanganUnitController::class, 'editPengeluaran'])->name('keuangan.pengeluaran.edit');
        Route::put('/pengeluaran/{pengeluaran}', [\App\Http\Controllers\KeuanganUnitController::class, 'updatePengeluaran'])->name('keuangan.pengeluaran.update');
        Route::delete('/pengeluaran/{pengeluaran}', [\App\Http\Controllers\KeuanganUnitController::class, 'destroyPengeluaran'])->name('keuangan.pengeluaran.destroy');

        Route::get('/kasbon/create', [\App\Http\Controllers\KeuanganUnitController::class, 'createKasbon'])->name('keuangan.kasbon.create');
        Route::post('/kasbon', [\App\Http\Controllers\KeuanganUnitController::class, 'storeKasbon'])->name('keuangan.kasbon.store');
        Route::get('/kasbon/{kasbon}/edit', [\App\Http\Controllers\KeuanganUnitController::class, 'editKasbon'])->name('keuangan.kasbon.edit');
        Route::put('/kasbon/{kasbon}', [\App\Http\Controllers\KeuanganUnitController::class, 'updateKasbon'])->name('keuangan.kasbon.update');
        Route::patch('/kasbon/{kasbon}/toggle-lunas', [\App\Http\Controllers\KeuanganUnitController::class, 'toggleLunasKasbon'])->name('keuangan.kasbon.toggle-lunas');
        Route::delete('/kasbon/{kasbon}', [\App\Http\Controllers\KeuanganUnitController::class, 'destroyKasbon'])->name('keuangan.kasbon.destroy');

        // Penutupan & Buka Kembali Periode Pembukuan Keuangan
        Route::post('/periode/tutup', [\App\Http\Controllers\KeuanganUnitController::class, 'tutupPeriode'])->name('keuangan.periode.tutup');
        Route::post('/periode/buka', [\App\Http\Controllers\KeuanganUnitController::class, 'bukaPeriode'])->name('keuangan.periode.buka');
    });

    // --- Data master: khusus admin (PRD bab 4) --------------------------------

    Route::middleware('peran:admin')->prefix('master')->group(function () {
        Route::patch('pt/{id}/toggle-status', [\App\Http\Controllers\Master\PtController::class, 'toggleStatus'])->name('master.pt.toggle-status');
        Route::patch('drone/{id}/toggle-status', [DroneController::class, 'toggleStatus'])->name('master.drone.toggle-status');
        Route::patch('drone/{id}/update-status', [DroneController::class, 'updateStatus'])->name('master.drone.update-status');
        Route::patch('jenis-pekerjaan/{id}/toggle-status', [JenisPekerjaanController::class, 'toggleStatus'])->name('master.jenis-pekerjaan.toggle-status');
        Route::patch('distrik/{id}/toggle-status', [DistrikController::class, 'toggleStatus'])->name('master.distrik.toggle-status');
        Route::patch('jenis-bbm/{id}/toggle-status', [JenisBbmController::class, 'toggleStatus'])->name('master.jenis-bbm.toggle-status');
        Route::patch('pengguna/{id}/toggle-status', [PenggunaController::class, 'toggleStatus'])->name('master.pengguna.toggle-status');
        Route::patch('tim/{id}/toggle-status', [TimController::class, 'toggleStatus'])->name('master.tim.toggle-status');

        Route::resource('pengguna', PenggunaController::class)->except(['show', 'destroy'])->names('master.pengguna');
        Route::resource('pt', \App\Http\Controllers\Master\PtController::class)->except('show')->names('master.pt');
        Route::resource('distrik', DistrikController::class)->except('show')->names('master.distrik');
        Route::resource('drone', DroneController::class)->except('show')->names('master.drone');
        Route::resource('tim', TimController::class)->except('show')->names('master.tim');
        Route::resource('anggota-tim', AnggotaTimController::class)->except('show')->names('master.anggota-tim');
        Route::resource('jenis-pekerjaan', JenisPekerjaanController::class)->except('show')->names('master.jenis-pekerjaan');
        Route::resource('jenis-bbm', JenisBbmController::class)->except('show')->names('master.jenis-bbm');
        Route::resource('tarif-pekerjaan', TarifPekerjaanController::class)->except('show')->names('master.tarif-pekerjaan');
    });

    // --- Data master petak lahan: khusus Kepala Lapangan (yang berinteraksi langsung di lapangan) ---
    Route::middleware('peran:kepala_lapangan')->prefix('master')->group(function () {
        Route::patch('petak/{id}/toggle-status', [PetakController::class, 'toggleStatus'])->name('master.petak.toggle-status');
        Route::resource('petak', PetakController::class)->except('show')->names('master.petak');
    });
});

