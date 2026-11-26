<?php

namespace App\View\Composers;

use App\Models\LaporanRealisasi;
use App\Models\Spk;
use App\Models\TransaksiBbm;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Menyiapkan menu sidebar dari config/pantrack.php.
 * Hanya item yang route-nya sudah ada dan boleh diakses peran pengguna yang ditampilkan,
 * sehingga menu modul baru muncul otomatis saat route-nya dibuat.
 */
class MenuComposer
{
    public function compose(View $view): void
    {
        $pengguna = auth()->user();
        $idTim = $pengguna?->idTimYangDiakses();

        $badges = [];
        if ($pengguna) {
            if ($pengguna->adalahKepalaLapangan()) {
                $badges['realisasi.index'] = LaporanRealisasi::where('status_laporan', LaporanRealisasi::MENUNGGU_PEMERIKSAAN)->count();
                $badges['spk.index'] = Spk::where('status_spk', Spk::MENUNGGU_PERSETUJUAN)->count();
            } elseif ($pengguna->adalahPekerjaLapangan()) {
                $badges['realisasi.index'] = LaporanRealisasi::where('status_laporan', LaporanRealisasi::PERLU_PERBAIKAN)
                    ->when($idTim !== null, fn ($q) => $q->whereIn('tim_id', $idTim))
                    ->count();
                $badges['spk.index'] = Spk::where('status_spk', Spk::DISETUJUI)
                    ->whereDoesntHave('laporanRealisasi')
                    ->count();
                $badges['bbm.index'] = TransaksiBbm::where('status_verifikasi', TransaksiBbm::PERLU_PERBAIKAN)
                    ->when($idTim !== null, fn ($q) => $q->whereIn('tim_id', $idTim))
                    ->count();
            }

            if ($pengguna->adalahAdmin()) {
                $badges['bbm.index'] = TransaksiBbm::where('status_verifikasi', TransaksiBbm::MENUNGGU_PEMERIKSAAN)->count();
            }
        }

        $menu = collect(config('pantrack.menu'))
            ->map(fn (array $grup) => [
                'judul' => $grup['judul'],
                'item' => collect($grup['item'])
                    ->filter(
                        fn (array $item) => Route::has($item['route'])
                            && ($item['peran'] === null || $pengguna?->punyaPeran(...$item['peran']))
                    )
                    ->map(fn (array $item) => array_merge($item, [
                        'badge' => $badges[$item['route']] ?? 0,
                    ])),
            ])
            ->filter(fn (array $grup) => $grup['item']->isNotEmpty());

        $view->with('menu', $menu);
    }
}
