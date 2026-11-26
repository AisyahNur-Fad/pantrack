<?php

namespace App\Services;

use App\Models\AnggotaTim;
use App\Models\DetailRealisasi;
use App\Models\Drone;
use App\Models\KasbonPersonil;
use App\Models\LaporanRealisasi;
use App\Models\PengeluaranOperasionalUnit;
use App\Models\Pengguna;
use App\Models\PenutupanPeriode;
use App\Models\Spk;
use App\Models\TarifPekerjaan;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung angka dashboard langsung dari database, selalu dibatasi sesuai hak akses pengguna.
 *
 * Aturan yang dijaga:
 *  - Luas target SPK (detail_spk) dan luas realisasi (detail_realisasi) dihitung dari tabel berbeda dan tidak pernah dijumlahkan (BR-05).
 *  - Realisasi hanya dihitung dari laporan berstatus "disetujui" (README bab 11 no. 5).
 *  - Pekerja lapangan hanya melihat tim miliknya dan SPK yang ia input.
 */
class DashboardService
{
    /** @return array<string, mixed> */
    public function ringkasan(Pengguna $pengguna, int $bulan, int $tahun): array
    {
        $idTim = $pengguna->idTimYangDiakses(); // null = tanpa pembatasan tim

        $data = [
            'menunggu' => [
                'laporan' => $this->laporan($idTim)->where('status_laporan', LaporanRealisasi::MENUNGGU_PEMERIKSAAN)->count(),
                'spk' => ($pengguna->adalahKepalaLapangan() || $pengguna->adalahAdmin() || $pengguna->adalahAtasan()) ? $this->spk(null)->where('status_spk', Spk::MENUNGGU_PERSETUJUAN)->count() : 0,
                'bbm' => $this->bbm($idTim)->where('status_verifikasi', TransaksiBbm::MENUNGGU_PEMERIKSAAN)->count(),
            ],
            'laporanPerStatus' => $this->laporanPerStatus($idTim, $bulan, $tahun),
            'luasTargetSpk' => $this->luasTargetSpk(null, $bulan, $tahun),
            'luasRealisasi' => (float) $this->realisasiDisetujui($idTim, $bulan, $tahun)->sum('d.luas_realisasi_ha'),
            'totalNilaiRealisasi' => $this->totalNilaiRealisasi($idTim, $bulan, $tahun),
            'luasHariIni' => $this->luasRealisasiTanggal($idTim, now()->toDateString(), now()->toDateString()),
            'luasBulanBerjalan' => $this->luasRealisasiTanggal($idTim, now()->startOfMonth()->toDateString(), now()->toDateString()),
            'rekapStatusPekerjaan' => $this->rekapStatusPekerjaan($idTim, $bulan, $tahun),
            'rekapDistrik' => $this->rekapDistrik($idTim, null, $bulan, $tahun),
            'rekapTim' => $this->rekapRealisasi('tim', 'l.tim_id', 'nama_tim', $idTim, $bulan, $tahun),
            'rekapJenisPekerjaan' => $this->rekapRealisasi('jenis_pekerjaan', 'd.jenis_pekerjaan_id', 'nama_pekerjaan', $idTim, $bulan, $tahun),
            'ringkasanBbm' => app(\App\Services\BbmService::class)->ringkasanTim($idTim),
        ];

        // Data khusus per peran pengguna
        if ($pengguna->adalahPekerjaLapangan()) {
            $data['pekerja'] = $this->dataPekerja($pengguna, $bulan, $tahun, $idTim);
        } elseif ($pengguna->adalahKepalaLapangan()) {
            $data['kepala'] = $this->dataKepala($pengguna, $bulan, $tahun);
        } elseif ($pengguna->adalahAdmin()) {
            $data['admin'] = $this->dataAdmin($pengguna, $bulan, $tahun);
        } elseif ($pengguna->adalahAtasan()) {
            $data['atasan'] = $this->dataAtasan($pengguna, $bulan, $tahun);
        }

        return $data;
    }

    /** Data dashboard spesifik pekerja lapangan (fokus estimasi penghasilan, SPK aktif, laporan sendiri, dan status armada drone) */
    private function dataPekerja(Pengguna $pengguna, int $bulan, int $tahun, ?array $idTim): array
    {
        $tim = null;
        if (! empty($idTim)) {
            $tim = Tim::with(['drone', 'distrik'])->find($idTim[0]);
        }
        if (! $tim) {
            $tim = $pengguna->keanggotaanTim()->latest()->first()?->tim ?? $pengguna->timYangDipimpin()->first();
        }
        if ($tim) {
            $tim->loadMissing(['drone', 'distrik']);
        }

        // Luas terbang tim bulan ini yang sudah disetujui
        $luasDisetujuiTim = $tim ? (float) $this->realisasiDisetujui([$tim->id], $bulan, $tahun)->sum('d.luas_realisasi_ha') : 0;

        // Identifikasi Peran Anggota Tim (Pilot, Co-Pilot, atau Helper)
        $keanggotaan = $tim ? $pengguna->keanggotaanTim()->where('tim_id', $tim->id)->latest()->first() : null;
        $jabatan = $keanggotaan?->jabatan_tim ?? AnggotaTim::PILOT;
        $isHelper = ($jabatan === AnggotaTim::HELPER);
        $isCoPilot = ($jabatan === AnggotaTim::CO_PILOT);
        $peranKru = $isHelper ? 'helper' : ($isCoPilot ? 'co_pilot' : 'pilot');
        $labelPeranKru = match ($peranKru) {
            'helper' => 'Helper Lapangan',
            'co_pilot' => 'Co-Pilot Drone',
            default => 'Pilot Drone',
        };

        // Hari kerja aktif tim/kru bulan ini (dari jumlah tanggal unik pekerjaan)
        $hariKerjaAktif = $tim ? (int) $this->realisasiDisetujui([$tim->id], $bulan, $tahun)
            ->distinct('d.tanggal_pekerjaan')
            ->count('d.tanggal_pekerjaan') : 0;
        if ($hariKerjaAktif <= 0 && $tim) {
            $hariKerjaAktif = (int) DetailRealisasi::whereHas('laporan', function ($q) use ($tim, $bulan, $tahun) {
                $q->where('tim_id', $tim->id)
                    ->where('periode_bulan', $bulan)
                    ->where('periode_tahun', $tahun);
            })->distinct('tanggal_pekerjaan')->count('tanggal_pekerjaan');
        }

        // Komponen Gaji & Insentif sesuai spesifikasi slip gaji BAP / EksporExcelService
        if ($isHelper) {
            $gajiPokok = (float) ($tim?->gaji_pokok_helper ?? 0);
            $tarifMakanHarian = (float) ($tim?->uang_makan_helper_per_hari ?? 50000);
            $uangMakan = (float) ($hariKerjaAktif * $tarifMakanHarian);
            $premiPerHa = (float) ($tim?->premi_helper_per_ha ?? 10000);
        } else {
            $gajiPokok = (float) ($tim?->gaji_pokok_pilot ?? 1000000);
            $uangMakan = (float) ($tim?->uang_makan_pilot ?? 900000);
            $premiPerHa = (float) ($tim?->premi_pilot_per_ha ?? 22000);
            $tarifMakanHarian = 0;
        }

        $totalPremi = $luasDisetujuiTim * $premiPerHa;

        // Kasbon personil kru (prioritas filter nama atau peran kru di tim bulan ini)
        $namaPersonil = $keanggotaan?->nama_anggota ?? $pengguna->nama_lengkap;
        $kasbonPeran = $isHelper ? KasbonPersonil::HELPER : KasbonPersonil::PILOT;

        $kasbon = (float) KasbonPersonil::query()
            ->when($tim, fn ($q) => $q->where('tim_id', $tim->id))
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('status_lunas', false)
            ->where(function ($q) use ($kasbonPeran, $namaPersonil) {
                $q->where('peran_kru', $kasbonPeran);
                if (! empty($namaPersonil)) {
                    $q->orWhere('nama_personil', 'like', '%' . trim($namaPersonil) . '%');
                }
            })
            ->sum('jumlah_kasbon');

        if ($kasbon <= 0 && $tim) {
            $kasbon = (float) KasbonPersonil::query()
                ->where('tim_id', $tim->id)
                ->where('periode_bulan', $bulan)
                ->where('periode_tahun', $tahun)
                ->where('status_lunas', false)
                ->sum('jumlah_kasbon');
        }

        $estimasiKotor = $gajiPokok + $uangMakan + $totalPremi;
        $estimasiBersih = max(0, $estimasiKotor - $kasbon);

        // Informasi Armada & Pemeliharaan Drone Tim
        $drone = $tim?->drone;
        $droneInfo = null;
        if ($drone) {
            $totalLuasDrone = (float) $drone->total_luas_terbang_ha;
            $ambangBatas = (float) $drone->ambang_batas_servis_ha;
            $persenServis = (float) $drone->persen_batas_servis;
            $perluServis = (bool) $drone->perlu_servis;
            $sisaMenujuServis = $ambangBatas > 0 ? max(0, $ambangBatas - $totalLuasDrone) : 0;

            $droneInfo = [
                'id' => $drone->id,
                'nomor_drone' => $drone->nomor_drone,
                'nama_drone' => $drone->nama_drone,
                'tipe_drone' => $drone->tipe_drone,
                'status_operasional' => $drone->status_operasional,
                'total_luas_terbang_ha' => $totalLuasDrone,
                'ambang_batas_servis_ha' => $ambangBatas,
                'perlu_servis' => $perluServis,
                'persen_batas_servis' => $persenServis,
                'sisa_menuju_servis' => $sisaMenujuServis,
                'catatan_perawatan' => $drone->catatan_perawatan,
            ];
        }

        // Laporan milik pengguna / tim yang butuh perbaikan segera
        $laporanPerluPerbaikan = LaporanRealisasi::with(['spk', 'distrik', 'pemeriksa'])
            ->when($tim, fn ($q) => $q->where('tim_id', $tim->id), fn ($q) => $q->where('diinput_oleh', $pengguna->id))
            ->where('status_laporan', LaporanRealisasi::PERLU_PERBAIKAN)
            ->latest()
            ->take(5)
            ->get();

        // SPK aktif untuk tim saya
        $spkAktif = Spk::with(['distrik', 'jenisPekerjaan'])
            ->when($tim, fn ($q) => $q->where('tim_id', $tim->id))
            ->whereIn('status_spk', [Spk::DISETUJUI, Spk::MENUNGGU_PERSETUJUAN])
            ->latest('tanggal_spk')
            ->take(5)
            ->get();

        // Laporan terakhir saya
        $laporanTerbaru = LaporanRealisasi::with(['spk', 'distrik'])
            ->when($tim, fn ($q) => $q->where('tim_id', $tim->id), fn ($q) => $q->where('diinput_oleh', $pengguna->id))
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->latest()
            ->take(5)
            ->get();

        return [
            'tim' => $tim,
            'peranKru' => $peranKru,
            'labelPeranKru' => $labelPeranKru,
            'isHelper' => $isHelper,
            'isCoPilot' => $isCoPilot,
            'hariKerjaAktif' => $hariKerjaAktif,
            'tarifMakanHarian' => $tarifMakanHarian,
            'luasDisetujuiTim' => $luasDisetujuiTim,
            'gajiPokok' => $gajiPokok,
            'uangMakan' => $uangMakan,
            'premiPerHa' => $premiPerHa,
            'totalPremi' => $totalPremi,
            'kasbon' => $kasbon,
            'estimasiKotor' => $estimasiKotor,
            'estimasiBersih' => $estimasiBersih,
            'droneInfo' => $droneInfo,
            'laporanPerluPerbaikan' => $laporanPerluPerbaikan,
            'spkAktif' => $spkAktif,
            'laporanTerbaru' => $laporanTerbaru,
        ];
    }

    /** Data dashboard spesifik kepala lapangan (fokus verifikasi laporan, monitoring SLA antrean, pipeline mapping & target SPK tim) */
    private function dataKepala(Pengguna $pengguna, int $bulan, int $tahun): array
    {
        $antreanLaporan = LaporanRealisasi::with(['tim', 'distrik', 'spk', 'detail', 'penginput'])
            ->where('status_laporan', LaporanRealisasi::MENUNGGU_PEMERIKSAAN)
            ->oldest()
            ->take(8)
            ->get();

        // SLA Antrean Verifikasi Laporan Realisasi
        $totalMenungguLaporan = LaporanRealisasi::where('status_laporan', LaporanRealisasi::MENUNGGU_PEMERIKSAAN)->count();
        $slaKurang24 = LaporanRealisasi::where('status_laporan', LaporanRealisasi::MENUNGGU_PEMERIKSAAN)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        $sla24sd48 = LaporanRealisasi::where('status_laporan', LaporanRealisasi::MENUNGGU_PEMERIKSAAN)
            ->where('created_at', '<', now()->subHours(24))
            ->where('created_at', '>=', now()->subHours(48))
            ->count();
        $slaLebih48 = LaporanRealisasi::where('status_laporan', LaporanRealisasi::MENUNGGU_PEMERIKSAAN)
            ->where('created_at', '<', now()->subHours(48))
            ->count();

        $slaSummary = [
            'total' => $totalMenungguLaporan,
            'hijau' => $slaKurang24,
            'kuning' => $sla24sd48,
            'merah' => $slaLebih48,
        ];

        $antreanSpk = Spk::with(['tim', 'distrik', 'jenisPekerjaan'])
            ->where('status_spk', Spk::MENUNGGU_PERSETUJUAN)
            ->latest()
            ->take(6)
            ->get();

        $laporanPerbaikan = LaporanRealisasi::with(['tim', 'distrik', 'penginput'])
            ->where('status_laporan', LaporanRealisasi::PERLU_PERBAIKAN)
            ->latest('tanggal_pemeriksaan')
            ->take(5)
            ->get();

        // Pipeline Mapping vs Spraying / Aplikasi
        $targetAplikasiHa = (float) $this->targetSpkDisetujui(null, $bulan, $tahun)->sum('d.luas_target_ha');
        $realisasiAplikasiHa = (float) $this->realisasiDisetujui(null, $bulan, $tahun)->sum('d.luas_realisasi_ha');

        $targetMappingHa = (float) $this->targetSpkDisetujui(null, $bulan, $tahun)
            ->where('d.perlu_mapping', true)
            ->sum('d.luas_target_ha');
        $realisasiMappingHa = (float) $this->realisasiDisetujui(null, $bulan, $tahun)
            ->where('d.perlu_mapping', true)
            ->sum('d.luas_realisasi_ha');

        $pipeline = [
            'targetAplikasiHa' => $targetAplikasiHa,
            'realisasiAplikasiHa' => $realisasiAplikasiHa,
            'persenAplikasi' => $targetAplikasiHa > 0 ? min(100, round(($realisasiAplikasiHa / $targetAplikasiHa) * 100)) : 0,
            'targetMappingHa' => $targetMappingHa,
            'realisasiMappingHa' => $realisasiMappingHa,
            'persenMapping' => $targetMappingHa > 0 ? min(100, round(($realisasiMappingHa / $targetMappingHa) * 100)) : 0,
        ];

        // Petak Terkendala di Lapangan
        $petakTerkendala = DetailRealisasi::with(['laporan.tim', 'laporan.distrik'])
            ->where('status_pekerjaan', 'terkendala')
            ->whereHas('laporan', function ($q) use ($bulan, $tahun) {
                $q->where('periode_bulan', $bulan)
                    ->where('periode_tahun', $tahun);
            })
            ->latest('tanggal_pekerjaan')
            ->take(5)
            ->get();

        $rekapTimSpk = $this->targetSpkDisetujui(null, $bulan, $tahun)
            ->join('tim as t', 't.id', '=', 's.tim_id')
            ->selectRaw('t.id, t.nama_tim, SUM(d.luas_target_ha) as target_ha')
            ->groupBy('t.id', 't.nama_tim')
            ->get()->keyBy('id');

        $rekapTimRealisasi = $this->realisasiDisetujui(null, $bulan, $tahun)
            ->join('tim as t', 't.id', '=', 'l.tim_id')
            ->selectRaw('t.id, t.nama_tim, SUM(d.luas_realisasi_ha) as realisasi_ha')
            ->groupBy('t.id', 't.nama_tim')
            ->get()->keyBy('id');

        $performaTim = $rekapTimSpk->keys()->merge($rekapTimRealisasi->keys())->unique()
            ->map(function ($id) use ($rekapTimSpk, $rekapTimRealisasi) {
                $target = (float) ($rekapTimSpk[$id]->target_ha ?? 0);
                $realisasi = (float) ($rekapTimRealisasi[$id]->realisasi_ha ?? 0);
                $persen = $target > 0 ? round(($realisasi / $target) * 100) : ($realisasi > 0 ? 100 : 0);
                return (object) [
                    'id' => $id,
                    'nama_tim' => $rekapTimSpk[$id]->nama_tim ?? $rekapTimRealisasi[$id]->nama_tim ?? 'Tim #'.$id,
                    'target_ha' => $target,
                    'realisasi_ha' => $realisasi,
                    'persen' => $persen,
                ];
            })->values();

        return [
            'antreanLaporan' => $antreanLaporan,
            'slaSummary' => $slaSummary,
            'antreanSpk' => $antreanSpk,
            'laporanPerbaikan' => $laporanPerbaikan,
            'pipeline' => $pipeline,
            'petakTerkendala' => $petakTerkendala,
            'performaTim' => $performaTim,
        ];
    }

    /** Data dashboard spesifik admin (fokus verifikasi BBM, radar anomali solar, kasbon safety ratio, keuangan operasional unit, dan armada drone) */
    private function dataAdmin(Pengguna $pengguna, int $bulan, int $tahun): array
    {
        $transaksiBbmMenunggu = TransaksiBbm::with(['tim.drone', 'jenisBbm', 'penginput'])
            ->where('status_verifikasi', TransaksiBbm::MENUNGGU_PEMERIKSAAN)
            ->oldest('tanggal_transaksi')
            ->take(8)
            ->get();

        $totalKasbonPeriode = (float) KasbonPersonil::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->sum('jumlah_kasbon');

        $kasbonBelumLunas = (float) KasbonPersonil::where('status_lunas', false)->sum('jumlah_kasbon');

        $kasbonPilot = (float) KasbonPersonil::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('peran_kru', KasbonPersonil::PILOT)
            ->sum('jumlah_kasbon');

        $kasbonHelper = (float) KasbonPersonil::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('peran_kru', KasbonPersonil::HELPER)
            ->sum('jumlah_kasbon');

        // Estimasi Hak Insentif Premi Terbang Kru Bulan Ini
        $totalHakPremiKru = (float) $this->realisasiDisetujui(null, $bulan, $tahun)
            ->sum(DB::raw('d.luas_realisasi_ha * 22000'));

        $kasbonSafetyRatio = $totalHakPremiKru > 0 ? round(($kasbonBelumLunas / $totalHakPremiKru) * 100, 1) : 0;
        $kasbonSafetyStatus = match (true) {
            $kasbonSafetyRatio <= 40 => 'aman',
            $kasbonSafetyRatio <= 70 => 'waspada',
            default => 'kritis',
        };

        $kasbonTerbaru = KasbonPersonil::with('tim')
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->latest('tanggal_kasbon')
            ->take(5)
            ->get();

        $isTerkunci = PenutupanPeriode::apakahTerkunci($bulan, $tahun);

        $droneList = Drone::orderBy('nomor_drone')->get();
        $droneMendekatiServis = $droneList->filter(fn ($d) => $d->perlu_servis || $d->persen_batas_servis >= 80);

        $totalPengeluaranBon = (float) PengeluaranOperasionalUnit::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->sum('nominal');

        // Radar Anomali Konsumsi BBM (> 1.50 L/Ha per Tim)
        $bbmPerTim = TransaksiBbm::whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('jenis_transaksi', 'keluar')
            ->groupBy('tim_id')
            ->selectRaw('tim_id, SUM(jumlah_liter) as total_liter, SUM(total_biaya) as total_biaya')
            ->get()->keyBy('tim_id');

        $luasPerTim = $this->realisasiDisetujui(null, $bulan, $tahun)
            ->join('tim as t', 't.id', '=', 'l.tim_id')
            ->groupBy('t.id', 't.nama_tim')
            ->selectRaw('t.id, t.nama_tim, SUM(d.luas_realisasi_ha) as total_ha')
            ->get()->keyBy('id');

        $anomaliBbm = [];
        foreach ($luasPerTim as $timId => $rowTim) {
            $ha = (float) $rowTim->total_ha;
            $liter = (float) ($bbmPerTim[$timId]->total_liter ?? 0);
            $rasio = $ha > 0 ? round($liter / $ha, 2) : 0;
            if ($rasio > 1.50 && $ha > 0) {
                $anomaliBbm[] = [
                    'tim_id' => $timId,
                    'nama_tim' => $rowTim->nama_tim,
                    'luas_ha' => $ha,
                    'total_liter' => $liter,
                    'rasio_l_per_ha' => $rasio,
                    'kelebihan_persen' => round((($rasio - 1.20) / 1.20) * 100),
                ];
            }
        }

        $totalBbmLiterBulan = (float) TransaksiBbm::whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('jenis_transaksi', 'keluar')
            ->sum('jumlah_liter');
        $totalLuasRealisasiBulan = (float) $this->realisasiDisetujui(null, $bulan, $tahun)->sum('d.luas_realisasi_ha');
        $rasioArmadaBbm = $totalLuasRealisasiBulan > 0 ? round($totalBbmLiterBulan / $totalLuasRealisasiBulan, 2) : 0;

        return [
            'transaksiBbmMenunggu' => $transaksiBbmMenunggu,
            'totalKasbonPeriode' => $totalKasbonPeriode,
            'kasbonBelumLunas' => $kasbonBelumLunas,
            'kasbonPilot' => $kasbonPilot,
            'kasbonHelper' => $kasbonHelper,
            'totalHakPremiKru' => $totalHakPremiKru,
            'kasbonSafetyRatio' => $kasbonSafetyRatio,
            'kasbonSafetyStatus' => $kasbonSafetyStatus,
            'kasbonTerbaru' => $kasbonTerbaru,
            'isTerkunci' => $isTerkunci,
            'totalDrone' => $droneList->count(),
            'droneAktif' => $droneList->where('status_operasional', Drone::STATUS_AKTIF)->count(),
            'droneMendekatiServis' => $droneMendekatiServis,
            'totalPengeluaranBon' => $totalPengeluaranBon,
            'anomaliBbm' => $anomaliBbm,
            'totalBbmLiterBulan' => $totalBbmLiterBulan,
            'rasioArmadaBbm' => $rasioArmadaBbm,
        ];
    }

    /** Data dashboard spesifik atasan / direksi (fokus executive summary, tren MoM, revenue mapping vs pokok, profit 80% BAP, rasio BBM/Ha, ekspor) */
    private function dataAtasan(Pengguna $pengguna, int $bulan, int $tahun): array
    {
        $luasRealisasi = (float) $this->realisasiDisetujui(null, $bulan, $tahun)->sum('d.luas_realisasi_ha');
        $totalNilai = $this->totalNilaiRealisasi(null, $bulan, $tahun);

        // Hasil bersih 80% BAP resmi (investor 15%, adm 5%)
        $hasilBersih80 = $totalNilai * 0.80;
        $investor15 = $totalNilai * 0.15;
        $admPt5 = $totalNilai * 0.05;

        // Breakdown Nilai Pokok (Aplikasi) vs Mapping Add-on
        $nilaiPokok = (float) $this->realisasiDisetujui(null, $bulan, $tahun)
            ->sum(DB::raw('d.luas_realisasi_ha * d.tarif_per_ha'));
        $nilaiMapping = (float) $this->realisasiDisetujui(null, $bulan, $tahun)
            ->where('d.perlu_mapping', true)
            ->sum(DB::raw('d.luas_realisasi_ha * d.tarif_mapping_per_ha'));
        $persenMapping = $totalNilai > 0 ? round(($nilaiMapping / $totalNilai) * 100, 1) : 0;
        $persenPokok = max(0, 100 - $persenMapping);

        // Biaya Operasional Riil Unit (Operasional lapangan, sparepart, rental mobil, logistik)
        $totalOperasionalRiil = (float) PengeluaranOperasionalUnit::where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('kategori_biaya', '!=', PengeluaranOperasionalUnit::BON_PRIBADI)
            ->sum('nominal');

        $totalBiayaBbm = (float) TransaksiBbm::whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('jenis_transaksi', 'keluar')
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->sum('total_biaya');

        // Estimasi Profit Bersih Unit (Hasil 80% BAP - Biaya operasional riil unit)
        // Kasbon kru tidak dikurangkan dua kali karena merupakan uang muka yang dipotong langsung dari payroll/slip gaji kru
        $biayaOperasionalKalkulasi = $totalOperasionalRiil > 0 ? $totalOperasionalRiil : $totalBiayaBbm;
        $estimasiProfitBersih = max(0, $hasilBersih80 - $biayaOperasionalKalkulasi);
        $marginProfitPersen = $hasilBersih80 > 0 ? round(($estimasiProfitBersih / $hasilBersih80) * 100, 1) : 0;

        $totalBbmLiter = (float) TransaksiBbm::whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('jenis_transaksi', 'keluar')
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->sum('jumlah_liter');

        $rasioBbmPerHa = $luasRealisasi > 0 ? round($totalBbmLiter / $luasRealisasi, 2) : 0;

        $luasTargetSpk = $this->luasTargetSpk(null, $bulan, $tahun);
        $persenKetercapaian = $luasTargetSpk > 0 ? min(100, round(($luasRealisasi / $luasTargetSpk) * 100)) : ($luasRealisasi > 0 ? 100 : 0);

        // Pertumbuhan Month-over-Month (MoM) dibandingkan bulan lalu
        $prevBulan = $bulan === 1 ? 12 : $bulan - 1;
        $prevTahun = $bulan === 1 ? $tahun - 1 : $tahun;

        $prevLuasRealisasi = (float) $this->realisasiDisetujui(null, $prevBulan, $prevTahun)->sum('d.luas_realisasi_ha');
        $pertumbuhanLuasMoM = $prevLuasRealisasi > 0 ? round((($luasRealisasi - $prevLuasRealisasi) / $prevLuasRealisasi) * 100, 1) : null;

        $prevTotalNilai = (float) $this->totalNilaiRealisasi(null, $prevBulan, $prevTahun);
        $prevHasilBersih80 = $prevTotalNilai * 0.80;
        $pertumbuhanHasilMoM = $prevHasilBersih80 > 0 ? round((($hasilBersih80 - $prevHasilBersih80) / $prevHasilBersih80) * 100, 1) : null;

        $prevTotalBbm = (float) TransaksiBbm::whereMonth('tanggal_transaksi', $prevBulan)
            ->whereYear('tanggal_transaksi', $prevTahun)
            ->where('jenis_transaksi', 'keluar')
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->sum('jumlah_liter');
        $prevRasioBbm = $prevLuasRealisasi > 0 ? round($prevTotalBbm / $prevLuasRealisasi, 2) : 0;
        $deltaRasioBbm = round($rasioBbmPerHa - $prevRasioBbm, 2);

        return [
            'luasRealisasi' => $luasRealisasi,
            'totalNilai' => $totalNilai,
            'hasilBersih80' => $hasilBersih80,
            'investor15' => $investor15,
            'admPt5' => $admPt5,
            'nilaiPokok' => $nilaiPokok,
            'nilaiMapping' => $nilaiMapping,
            'persenPokok' => $persenPokok,
            'persenMapping' => $persenMapping,
            'estimasiProfitBersih' => $estimasiProfitBersih,
            'marginProfitPersen' => $marginProfitPersen,
            'totalBbmLiter' => $totalBbmLiter,
            'rasioBbmPerHa' => $rasioBbmPerHa,
            'luasTargetSpk' => $luasTargetSpk,
            'persenKetercapaian' => $persenKetercapaian,
            'pertumbuhanLuasMoM' => $pertumbuhanLuasMoM,
            'pertumbuhanHasilMoM' => $pertumbuhanHasilMoM,
            'deltaRasioBbm' => $deltaRasioBbm,
        ];
    }


    /* ------------------------------------------------------------------
     | Query dasar yang sudah dibatasi hak akses
     | ------------------------------------------------------------------ */

    private function laporan(?array $idTim)
    {
        return LaporanRealisasi::query()->when($idTim !== null, fn ($q) => $q->whereIn('tim_id', $idTim));
    }

    private function bbm(?array $idTim)
    {
        return TransaksiBbm::query()->when($idTim !== null, fn ($q) => $q->whereIn('tim_id', $idTim));
    }

    private function spk(?int $idPenginput)
    {
        return Spk::query()->when($idPenginput, fn ($q) => $q->where('diinput_oleh', $idPenginput));
    }

    /** Detail realisasi dari laporan disetujui pada periode laporan tertentu. */
    private function realisasiDisetujui(?array $idTim, int $bulan, int $tahun): Builder
    {
        return DB::table('detail_realisasi as d')
            ->join('laporan_realisasi as l', 'l.id', '=', 'd.laporan_realisasi_id')
            ->where('l.status_laporan', LaporanRealisasi::DISETUJUI)
            ->where('l.periode_bulan', $bulan)
            ->where('l.periode_tahun', $tahun)
            ->when($idTim !== null, fn ($q) => $q->whereIn('l.tim_id', $idTim));
    }

    /** Detail target SPK dari SPK disetujui pada periode SPK tertentu. */
    private function targetSpkDisetujui(?int $idPenginput, int $bulan, int $tahun): Builder
    {
        return DB::table('detail_spk as d')
            ->join('spk as s', 's.id', '=', 'd.spk_id')
            ->where('s.status_spk', Spk::DISETUJUI)
            ->where('s.periode_bulan', $bulan)
            ->where('s.periode_tahun', $tahun)
            ->when($idPenginput, fn ($q) => $q->where('s.diinput_oleh', $idPenginput));
    }

    /* ------------------------------------------------------------------
     | Angka dan rekapitulasi
     | ------------------------------------------------------------------ */

    /** @return array<string, int> jumlah laporan periode ini per status (semua status ditampilkan, nol bila kosong) */
    private function laporanPerStatus(?array $idTim, int $bulan, int $tahun): array
    {
        $hitung = $this->laporan($idTim)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->selectRaw('status_laporan, COUNT(*) as jumlah')
            ->groupBy('status_laporan')
            ->pluck('jumlah', 'status_laporan');

        return collect(array_keys(LaporanRealisasi::statusLaporan()))
            ->mapWithKeys(fn ($status) => [$status => (int) ($hitung[$status] ?? 0)])
            ->all();
    }

    private function luasTargetSpk(?int $idPenginput, int $bulan, int $tahun): float
    {
        return (float) $this->targetSpkDisetujui($idPenginput, $bulan, $tahun)->sum('d.luas_target_ha');
    }

    /** Luas realisasi disetujui berdasarkan tanggal pekerjaan (To Day dan MTD, FR-13). */
    private function luasRealisasiTanggal(?array $idTim, string $dari, string $sampai): float
    {
        return (float) DB::table('detail_realisasi as d')
            ->join('laporan_realisasi as l', 'l.id', '=', 'd.laporan_realisasi_id')
            ->where('l.status_laporan', LaporanRealisasi::DISETUJUI)
            ->whereBetween('d.tanggal_pekerjaan', [$dari, $sampai])
            ->when($idTim !== null, fn ($q) => $q->whereIn('l.tim_id', $idTim))
            ->sum('d.luas_realisasi_ha');
    }

    /** Total estimasi nilai pekerjaan disetujui periode ini (luas x tarif per Ha). */
    private function totalNilaiRealisasi(?array $idTim, int $bulan, int $tahun): float
    {
        $rows = $this->realisasiDisetujui($idTim, $bulan, $tahun)
            ->select('d.luas_realisasi_ha', 'd.tarif_per_ha', 'd.jenis_pekerjaan_id', 'd.tanggal_pekerjaan')
            ->get();

        $cacheTarif = [];

        return (float) $rows->sum(function ($r) use (&$cacheTarif) {
            $tarif = (float) ($r->tarif_per_ha ?? 0);
            if ($tarif <= 0 && $r->jenis_pekerjaan_id) {
                if (! isset($cacheTarif[$r->jenis_pekerjaan_id])) {
                    $cacheTarif[$r->jenis_pekerjaan_id] = (float) (TarifPekerjaan::where('jenis_pekerjaan_id', $r->jenis_pekerjaan_id)
                        ->latest('berlaku_mulai')
                        ->value('nilai_tarif') ?? 0);
                }
                $tarif = $cacheTarif[$r->jenis_pekerjaan_id];
            }
            return (float) $r->luas_realisasi_ha * $tarif;
        });
    }

    private function rekapStatusPekerjaan(?array $idTim, int $bulan, int $tahun): Collection
    {
        $label = DetailRealisasi::statusPekerjaan();

        return $this->realisasiDisetujui($idTim, $bulan, $tahun)
            ->selectRaw('d.status_pekerjaan as kode, COUNT(*) as jumlah_baris, SUM(d.luas_realisasi_ha) as luas')
            ->groupBy('d.status_pekerjaan')
            ->get()
            ->map(fn ($r) => (object) [
                'nama' => $label[$r->kode] ?? $r->kode,
                'jumlah_baris' => (int) $r->jumlah_baris,
                'luas' => (float) $r->luas,
            ]);
    }

    /** Rekap per distrik: luas target SPK dan luas realisasi ditampilkan di kolom terpisah. */
    private function rekapDistrik(?array $idTim, ?int $idPenginput, int $bulan, int $tahun): Collection
    {
        $realisasi = $this->realisasiDisetujui($idTim, $bulan, $tahun)
            ->join('distrik as x', 'x.id', '=', 'l.distrik_id')
            ->selectRaw('x.id, x.nama_distrik as nama, SUM(d.luas_realisasi_ha) as luas')
            ->groupBy('x.id', 'x.nama_distrik')
            ->get()->keyBy('id');

        $target = $this->targetSpkDisetujui($idPenginput, $bulan, $tahun)
            ->join('distrik as x', 'x.id', '=', 's.distrik_id')
            ->selectRaw('x.id, x.nama_distrik as nama, SUM(d.luas_target_ha) as luas')
            ->groupBy('x.id', 'x.nama_distrik')
            ->get()->keyBy('id');

        return $realisasi->keys()->merge($target->keys())->unique()
            ->map(fn ($id) => (object) [
                'nama' => ($realisasi[$id] ?? $target[$id])->nama,
                'luas_target' => (float) ($target[$id]->luas ?? 0),
                'luas_realisasi' => (float) ($realisasi[$id]->luas ?? 0),
            ])
            ->sortBy('nama')
            ->values();
    }

    /** Rekap realisasi per dimensi tertentu (tim, jenis pekerjaan). Nama tabel/kolom hanya dari konstanta internal. */
    private function rekapRealisasi(string $tabel, string $kolomPenghubung, string $kolomNama, ?array $idTim, int $bulan, int $tahun): Collection
    {
        return $this->realisasiDisetujui($idTim, $bulan, $tahun)
            ->join("{$tabel} as r", 'r.id', '=', $kolomPenghubung)
            ->selectRaw("r.{$kolomNama} as nama, COUNT(*) as jumlah_baris, SUM(d.luas_realisasi_ha) as luas")
            ->groupBy('r.id', "r.{$kolomNama}")
            ->orderBy('nama')
            ->get()
            ->map(fn ($r) => (object) [
                'nama' => $r->nama,
                'jumlah_baris' => (int) $r->jumlah_baris,
                'luas' => (float) $r->luas,
            ]);
    }
}
