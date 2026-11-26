<?php

namespace App\Services;

use App\Models\Tim;
use App\Models\TransaksiBbm;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Layanan kalkulasi saldo dan ringkasan BBM per tim (FR-20, FR-21).
 *
 * Aturan resmi PRD:
 *  Stok akhir = Stok awal + Penerimaan terverifikasi + Penyesuaian masuk - Penggunaan terverifikasi - Penyesuaian keluar
 *
 * Pengajuan tidak memengaruhi stok resmi.
 * Transaksi yang belum diverifikasi ditampilkan terpisah sebagai "menunggu pemeriksaan".
 */
class BbmService
{
    /**
     * Hitung saldo BBM resmi saat ini untuk tim dan jenis BBM tertentu.
     */
    public function hitungSaldoResmi(int $timId, ?int $jenisBbmId = null): float
    {
        $query = TransaksiBbm::query()
            ->where('tim_id', $timId)
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->when($jenisBbmId, fn ($q) => $q->where('jenis_bbm_id', $jenisBbmId));

        $transaksi = $query->get(['jenis_transaksi', 'jumlah_liter']);

        $saldo = 0.0;
        foreach ($transaksi as $tx) {
            $liter = (float) $tx->jumlah_liter;
            match ($tx->jenis_transaksi) {
                TransaksiBbm::STOK_AWAL, TransaksiBbm::PENERIMAAN => $saldo += $liter,
                TransaksiBbm::PENGGUNAAN => $saldo -= $liter,
                TransaksiBbm::PENYESUAIAN => $saldo += $liter, // penyesuaian positif menambah, negatif mengurangi
                default => null, // pengajuan tidak mengubah saldo
            };
        }

        return round($saldo, 2);
    }

    /**
     * Hitung sisa saldo BBM yang benar-benar tersedia (saldo resmi terverifikasi dikurangi penggunaan yang menunggu verifikasi).
     * Mencegah race condition pengeluaran beruntun melebihi stok yang ada di lapangan.
     */
    public function hitungSaldoTersedia(int $timId, int $jenisBbmId): float
    {
        $saldoResmi = $this->hitungSaldoResmi($timId, $jenisBbmId);

        $penggunaanMenunggu = TransaksiBbm::query()
            ->where('tim_id', $timId)
            ->where('jenis_bbm_id', $jenisBbmId)
            ->where('jenis_transaksi', TransaksiBbm::PENGGUNAAN)
            ->where('status_verifikasi', TransaksiBbm::MENUNGGU_PEMERIKSAAN)
            ->sum('jumlah_liter');

        return round($saldoResmi - (float) $penggunaanMenunggu, 2);
    }

    /**
     * Ringkasan stok seluruh tim yang boleh diakses pengguna dengan rincian per jenis BBM.
     *
     * @param array<int>|null $idTimBoleh null = semua tim
     * @return Collection
     */
    public function ringkasanTim(?array $idTimBoleh = null): Collection
    {
        $tims = Tim::query()
            ->with(['distrik', 'drone'])
            ->when($idTimBoleh !== null, fn ($q) => $q->whereIn('id', $idTimBoleh))
            ->orderBy('nama_tim')
            ->get();

        $daftarJenisBbm = \App\Models\JenisBbm::where('aktif', true)->orderBy('nama_bbm')->get();

        return $tims->map(function (Tim $tim) use ($daftarJenisBbm) {
            $saldoPerJenis = [];
            $totalSaldo = 0.0;

            foreach ($daftarJenisBbm as $jb) {
                $saldoJb = $this->hitungSaldoResmi($tim->id, $jb->id);
                $saldoPerJenis[] = [
                    'jenis_bbm_id' => $jb->id,
                    'nama_bbm' => $jb->nama_bbm,
                    'saldo' => $saldoJb,
                ];
                $totalSaldo += $saldoJb;
            }

            $menungguVerifikasi = TransaksiBbm::query()
                ->where('tim_id', $tim->id)
                ->where('status_verifikasi', TransaksiBbm::MENUNGGU_PEMERIKSAAN)
                ->count();

            $penerimaanBulanIni = TransaksiBbm::query()
                ->where('tim_id', $tim->id)
                ->where('jenis_transaksi', TransaksiBbm::PENERIMAAN)
                ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
                ->whereMonth('tanggal_transaksi', now()->month)
                ->whereYear('tanggal_transaksi', now()->year)
                ->sum('jumlah_liter');

            $penggunaanBulanIni = TransaksiBbm::query()
                ->where('tim_id', $tim->id)
                ->where('jenis_transaksi', TransaksiBbm::PENGGUNAAN)
                ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
                ->whereMonth('tanggal_transaksi', now()->month)
                ->whereYear('tanggal_transaksi', now()->year)
                ->sum('jumlah_liter');

            return [
                'tim' => $tim,
                'saldo' => $totalSaldo,
                'saldo_per_jenis' => $saldoPerJenis,
                'menunggu' => $menungguVerifikasi,
                'penerimaan_bulan_ini' => (float) $penerimaanBulanIni,
                'penggunaan_bulan_ini' => (float) $penggunaanBulanIni,
            ];
        });
    }
}
