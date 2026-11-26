<?php

namespace App\Services;

use App\Models\DetailRealisasi;
use App\Models\DetailSpk;
use App\Models\EksporLaporan;
use App\Models\KasbonPersonil;
use App\Models\LaporanRealisasi;
use App\Models\PengeluaranOperasionalUnit;
use App\Models\TarifPekerjaan;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Layanan pembuatan laporan rekapitulasi realisasi & keuangan resmi 7-Sheet (.xlsx) dan PDF (.pdf).
 *
 * Mengikuti standar penutupan buku operasional bulanan PT. Panlibata Andalas Bertuah / Drone Bertuah:
 *  Sheet 1: Cover (Pengesahan Unit & Direksi)
 *  Sheet 2: Rekap Profit & Bon (Hasil 80%, ADM 5%, Investor 15%, Pengeluaran Bon, Profit, & Piutang)
 *  Sheet 3: Lampiran Realisasi (Standar 11/14 Kolom Resmi Realisasi Petak)
 *  Sheet 4: Slip Gaji (Pilot & Helper: Gaji Pokok, Uang Makan, Premi/Ha, Kasbon, Ransum, & Admin Tim)
 *  Sheet 5: Rincian Operasional & Bon (Operasional Lapangan, Suku Cadang/PI, Kasbon Kru)
 *  Sheet 6: Ransum & Kantin (Bon Ransum, Bon Kantin Distrik, Pembagian Beban Kru)
 *  Sheet 7: BAP Pembayaran (Berita Acara Pembayaran 2-Blok Resmi & Rekapitulasi Transfer)
 */
class EksporExcelService
{
    public function __construct(private readonly RiwayatAktivitasService $aktivitas) {}

    /**
     * Ekstraksi dan kalkulasi seluruh data bisnis untuk ekspor Excel maupun PDF.
     * Menerapkan prinsip anti-fraud: hanya laporan yang berstatus 'disetujui' yang dihitung.
     *
     * @return array<string, mixed>
     */
    public function siapkanDataRekapitulasi(
        int $bulan,
        int $tahun,
        ?int $distrikId = null,
        ?int $timId = null,
        ?string $tanggalMulai = null,
        ?string $tanggalSelesai = null,
        bool $tampilkanHarga = true,
        ?int $droneId = null
    ): array {
        // 1. Ambil detail realisasi yang disetujui (anti-fraud control)
        $query = DetailRealisasi::query()
            ->with(['laporan.spk', 'laporan.tim.drone', 'laporan.tim.anggota.pengguna', 'laporan.distrik', 'petak', 'jenisPekerjaan'])
            ->whereHas('laporan', function ($q) use ($bulan, $tahun, $distrikId, $timId, $droneId, $tanggalMulai, $tanggalSelesai) {
                $q->where('status_laporan', LaporanRealisasi::DISETUJUI)
                    ->when(! ($tanggalMulai && $tanggalSelesai), function ($sub) use ($bulan, $tahun) {
                        $sub->where('periode_bulan', $bulan)
                            ->where('periode_tahun', $tahun);
                    })
                    ->when($distrikId, fn ($sub) => $sub->where('distrik_id', $distrikId))
                    ->when($timId, fn ($sub) => $sub->where('tim_id', $timId))
                    ->when($droneId, fn ($sub) => $sub->whereHas('tim', fn ($timQ) => $timQ->where('drone_id', $droneId)));
            })
            ->when($tanggalMulai && $tanggalSelesai, function ($q) use ($tanggalMulai, $tanggalSelesai) {
                $q->whereBetween('tanggal_pekerjaan', [$tanggalMulai, $tanggalSelesai]);
            })
            ->orderBy('tanggal_pekerjaan')
            ->orderBy('id');

        $daftarDetail = $query->get();

        // 2. Identifikasi Tim dan Drone Terkait
        $tim = null;
        if ($timId) {
            $tim = Tim::with(['drone', 'anggota.pengguna'])->find($timId);
        } elseif ($droneId) {
            $tim = Tim::with(['drone', 'anggota.pengguna'])->where('drone_id', $droneId)->first();
        } elseif ($daftarDetail->isNotEmpty() && $daftarDetail->first()->laporan?->tim) {
            $tim = $daftarDetail->first()->laporan->tim;
        } else {
            $tim = Tim::with(['drone', 'anggota.pengguna'])->where('status_tim', 'aktif')->first();
        }

        // 3. Ambil data keuangan unit drone
        $daftarPengeluaran = PengeluaranOperasionalUnit::query()
            ->when($tim, fn ($q) => $q->where('tim_id', $tim->id))
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->orderBy('tanggal_transaksi')
            ->get();

        $daftarKasbon = KasbonPersonil::query()
            ->when($tim, fn ($q) => $q->where('tim_id', $tim->id))
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->orderBy('tanggal_kasbon')
            ->get();

        $daftarBbm = TransaksiBbm::query()
            ->when($tim, fn ($q) => $q->where('tim_id', $tim->id))
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('status_verifikasi', 'diverifikasi')
            ->get();

        // Periode Label
        $namaBulan = Carbon::create(2026, $bulan, 1)->translatedFormat('F');
        $labelPeriode = ($tanggalMulai && $tanggalSelesai)
            ? Carbon::parse($tanggalMulai)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($tanggalSelesai)->translatedFormat('d M Y')
            : strtoupper($namaBulan) . ' ' . $tahun;

        // 4. Hitung Total Realisasi & Keuangan
        $totalLuasSpk = 0.0;
        $totalLuasRealisasi = 0.0;
        $totalLuasMapping = 0.0;
        $totalNilaiPokok = 0.0;
        $totalNilaiMapping = 0.0;
        $totalNilai = 0.0;
        $spkPetakTercatat = [];

        foreach ($daftarDetail as $d) {
            $spk = $d->laporan?->spk;
            if ($spk) {
                if ($d->petak_id) {
                    $kunciSpkPetak = $spk->id . '_' . $d->petak_id;
                    if (! isset($spkPetakTercatat[$kunciSpkPetak])) {
                        $detailSpk = DetailSpk::where('spk_id', $spk->id)->where('petak_id', $d->petak_id)->first();
                        $luasSpk = (float) ($detailSpk?->luas_target_ha ?? 0);
                        $totalLuasSpk += $luasSpk;
                        $spkPetakTercatat[$kunciSpkPetak] = true;
                    }
                } elseif ($d->kode_petak_manual) {
                    $kunciSpkPetak = $spk->id . '_' . $d->kode_petak_manual;
                    if (! isset($spkPetakTercatat[$kunciSpkPetak])) {
                        $detailSpk = DetailSpk::where('spk_id', $spk->id)
                            ->whereHas('petak', fn ($pq) => $pq->where('kode_petak', $d->kode_petak_manual))
                            ->first();
                        $luasSpk = (float) ($detailSpk?->luas_target_ha ?? 0);
                        $totalLuasSpk += $luasSpk;
                        $spkPetakTercatat[$kunciSpkPetak] = true;
                    }
                }
            }

            $tarif = (float) ($d->tarif_per_ha ?? 0);
            if ($tarif <= 0 && $d->jenis_pekerjaan_id) {
                $tarifData = TarifPekerjaan::where('jenis_pekerjaan_id', $d->jenis_pekerjaan_id)
                    ->where('berlaku_mulai', '<=', $d->tanggal_pekerjaan)
                    ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $d->tanggal_pekerjaan))
                    ->latest('berlaku_mulai')
                    ->first();
                $tarif = (float) ($tarifData?->nilai_tarif ?? 0);
            }

            $luasRealisasi = (float) $d->luas_realisasi_ha;
            $subtotalPokok = $luasRealisasi * $tarif;

            $tarifMapping = 0.0;
            if ($d->perlu_mapping) {
                $tarifMapping = (float) ($d->tarif_mapping_per_ha ?? 0);
                if ($tarifMapping <= 0) {
                    $pekerjaanMapping = \App\Models\JenisPekerjaan::where('tipe', \App\Models\JenisPekerjaan::TIPE_ADDON)
                        ->orWhere('kode_pekerjaan', 'SRV-MAPPING')
                        ->first();
                    if ($pekerjaanMapping) {
                        $tarifMapping = (float) (\App\Models\TarifPekerjaan::where('jenis_pekerjaan_id', $pekerjaanMapping->id)
                            ->where('berlaku_mulai', '<=', $d->tanggal_pekerjaan)
                            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $d->tanggal_pekerjaan))
                            ->latest('berlaku_mulai')
                            ->value('nilai_tarif') ?? 30000);
                    } else {
                        $tarifMapping = 30000.0;
                    }
                }
            }

            $subtotalMapping = $d->perlu_mapping ? ($luasRealisasi * $tarifMapping) : 0.0;
            $subtotal = $subtotalPokok + $subtotalMapping;

            $totalLuasRealisasi += $luasRealisasi;
            $totalNilaiPokok += $subtotalPokok;
            $totalNilai += $subtotal;

            if ($d->perlu_mapping) {
                $totalLuasMapping += $luasRealisasi;
                $totalNilaiMapping += $subtotalMapping;
            }
        }

        // Persentase bagi hasil resmi
        $adm5Persen = $totalNilai * 0.05;
        $investor15Persen = $totalNilai * 0.15;
        $hasilBersih80Persen = $totalNilai - $adm5Persen - $investor15Persen;

        // Pengeluaran operasional per kategori
        $totalOperasional = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::OPERASIONAL_LAPANGAN)->sum('nominal');
        $totalBiayaBbm = (float) $daftarBbm->sum('total_biaya');
        if ($totalOperasional <= 0 && $totalBiayaBbm > 0) {
            $totalOperasional = $totalBiayaBbm;
        }

        $totalSparepart = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::SPAREPART)->sum('nominal');
        $totalRansumKantin = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::RANSUM_KANTIN)->sum('nominal');
        $totalRentalMobil = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::RENTAL_MOBIL)->sum('nominal');
        $totalUpahLangsir = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::UPAH_LANGSIR)->sum('nominal');
        $totalBonPribadi = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::BON_PRIBADI)->sum('nominal');
        $totalKasbonKru = (float) $daftarKasbon->sum('jumlah_kasbon');

        // Nama personil kru
        $namaPilot = $tim?->anggota?->firstWhere('peran_dalam_tim', 'pilot')?->pengguna?->nama_lengkap
            ?? $daftarDetail->first()?->laporan?->nama_pilot
            ?? 'Pranoto';

        $helperLaporan = [];
        foreach ($daftarDetail as $d) {
            if ($d->laporan && ! empty($d->laporan->daftar_helper)) {
                foreach ($d->laporan->daftar_helper as $h) {
                    if (! in_array($h, $helperLaporan, true)) {
                        $helperLaporan[] = $h;
                    }
                }
            } elseif ($d->laporan && ! empty($d->laporan->nama_helper)) {
                $hNama = trim((string) $d->laporan->nama_helper);
                if (! in_array($hNama, $helperLaporan, true)) {
                    $helperLaporan[] = $hNama;
                }
            }
        }
        $namaHelper1 = $helperLaporan[0] ?? 'Helper 1';
        $namaHelper2 = $helperLaporan[1] ?? 'Helper 2';

        // Saldo piutang / bon pribadi pemilik bulan-bulan sebelumnya
        $sisaPiutangBulanLalu = (float) PengeluaranOperasionalUnit::query()
            ->when($tim, fn ($q) => $q->where('tim_id', $tim->id))
            ->where('kategori_biaya', PengeluaranOperasionalUnit::BON_PRIBADI)
            ->where(function ($q) use ($bulan, $tahun) {
                $q->where('periode_tahun', '<', $tahun)
                    ->orWhere(function ($sub) use ($bulan, $tahun) {
                        $sub->where('periode_tahun', $tahun)
                            ->where('periode_bulan', '<', $bulan);
                    });
            })
            ->sum('nominal');

        // Hari kerja aktif helper dihitung dinamis dari tanggal realisasi unik
        $hariKerjaHelper = $daftarDetail->pluck('tanggal_pekerjaan')->filter()->map(fn ($t) => $t instanceof Carbon ? $t->toDateString() : (string) $t)->unique()->count();
        if ($hariKerjaHelper <= 0) {
            $hariKerjaHelper = 18;
        }

        // Komponen Gaji & Insentif Kru (Gaji Pokok + Uang Makan + Premi / Upah Harian)
        $premiPilotPerHa = (float) ($tim?->premi_pilot_per_ha ?? 22000);
        $premiHelperPerHa = (float) ($tim?->premi_helper_per_ha ?? 10000);
        $skemaHelper = $tim?->skema_upah_helper ?? Tim::SKEMA_HELPER_PER_HEKTAR;
        $tarifHelperPerHari = (float) ($tim?->tarif_helper_per_hari ?? 100000);
        $gajiPokokPilot = (float) ($tim?->gaji_pokok_pilot ?? 1000000);
        $gajiPokokHelper = (float) ($tim?->gaji_pokok_helper ?? 0);
        $uangMakanPilot = (float) ($tim?->uang_makan_pilot ?? 900000);
        $tarifMakanHelperPerHari = (float) ($tim?->uang_makan_helper_per_hari ?? 50000);
        $uangMakanHelper = (float) ($hariKerjaHelper * $tarifMakanHelperPerHari);
        $honorAdmTim = (float) ($tim?->honor_adm_tim ?? 500000);

        // Kasbon kru per orang
        $kasbonPilot = (float) $daftarKasbon->where('peran_kru', KasbonPersonil::PILOT)->sum('jumlah_kasbon');
        $kasbonHelper1 = (float) $daftarKasbon->where('nama_personil', $namaHelper1)->sum('jumlah_kasbon');
        $kasbonHelper2 = (float) $daftarKasbon->where('nama_personil', $namaHelper2)->sum('jumlah_kasbon');
        if ($kasbonHelper1 <= 0 && $kasbonHelper2 <= 0) {
            $totalKasbonHelper = (float) $daftarKasbon->where('peran_kru', KasbonPersonil::HELPER)->sum('jumlah_kasbon');
            $kasbonHelper1 = $totalKasbonHelper / 2;
            $kasbonHelper2 = $totalKasbonHelper / 2;
        }

        // Potongan ransum per orang (dibagi rata ke 3 kru lapangan)
        $ransumPerOrang = $totalRansumKantin > 0 ? $totalRansumKantin / 3 : 0;

        // Upah / Premi Helper berdasarkan skema (Harian vs Per Hektar)
        $upahHelperSatuan = $skemaHelper === Tim::SKEMA_HELPER_HARIAN
            ? (float) ($hariKerjaHelper * $tarifHelperPerHari)
            : (float) ($totalLuasRealisasi * $premiHelperPerHa);

        // Gaji Bersih Tiap Kru (Gaji Pokok + Makan + Premi/Upah - Potongan)
        $gajiBersihPilot = ($gajiPokokPilot + $uangMakanPilot + ($totalLuasRealisasi * $premiPilotPerHa)) - ($kasbonPilot + $ransumPerOrang);
        $gajiBersihHelper1 = ($gajiPokokHelper + $uangMakanHelper + $upahHelperSatuan) - ($kasbonHelper1 + $ransumPerOrang);
        $gajiBersihHelper2 = ($gajiPokokHelper + $uangMakanHelper + $upahHelperSatuan) - ($kasbonHelper2 + $ransumPerOrang);
        $totalGajiTim = $gajiBersihPilot + $gajiBersihHelper1 + $gajiBersihHelper2 + $honorAdmTim;

        // Biaya Enterteint
        $biayaEnterteintPerHa = (float) ($tim?->biaya_enterteint_per_ha ?? 10000);
        $totalEnterteint = $totalLuasRealisasi * $biayaEnterteintPerHa;

        // Blok 1 & Blok 2 Resmi BAP
        $totalRincian = $totalOperasional + $totalSparepart + $totalKasbonKru;
        $totalPembayaran = $totalGajiTim + $totalEnterteint + $totalRansumKantin + $totalRentalMobil + $totalUpahLangsir;

        $totalPengeluaranBon = $totalRincian + $totalPembayaran + $totalBonPribadi;
        $profitBersihDrone = $hasilBersih80Persen - $totalPengeluaranBon;

        return [
            'tim' => $tim,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'distrikId' => $distrikId,
            'timId' => $timId,
            'droneId' => $droneId,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'labelPeriode' => $labelPeriode,
            'daftarDetail' => $daftarDetail,
            'daftarPengeluaran' => $daftarPengeluaran,
            'daftarKasbon' => $daftarKasbon,
            'daftarBbm' => $daftarBbm,
            'totalLuasSpk' => $totalLuasSpk,
            'totalLuasRealisasi' => $totalLuasRealisasi,
            'totalLuasMapping' => $totalLuasMapping,
            'totalNilaiPokok' => $totalNilaiPokok,
            'totalNilaiMapping' => $totalNilaiMapping,
            'totalNilai' => $totalNilai,
            'adm5Persen' => $adm5Persen,
            'investor15Persen' => $investor15Persen,
            'hasilBersih80Persen' => $hasilBersih80Persen,
            'totalOperasional' => $totalOperasional,
            'totalSparepart' => $totalSparepart,
            'totalRansumKantin' => $totalRansumKantin,
            'totalRentalMobil' => $totalRentalMobil,
            'totalUpahLangsir' => $totalUpahLangsir,
            'totalBonPribadi' => $totalBonPribadi,
            'totalKasbonKru' => $totalKasbonKru,
            'totalPengeluaranBon' => $totalPengeluaranBon,
            'profitBersihDrone' => $profitBersihDrone,
            'namaPilot' => $namaPilot,
            'namaHelper1' => $namaHelper1,
            'namaHelper2' => $namaHelper2,
            'sisaPiutangBulanLalu' => $sisaPiutangBulanLalu,
            'hariKerjaHelper' => $hariKerjaHelper,
            'premiPilotPerHa' => $premiPilotPerHa,
            'premiHelperPerHa' => $premiHelperPerHa,
            'gajiPokokPilot' => $gajiPokokPilot,
            'gajiPokokHelper' => $gajiPokokHelper,
            'uangMakanPilot' => $uangMakanPilot,
            'tarifMakanHelperPerHari' => $tarifMakanHelperPerHari,
            'uangMakanHelper' => $uangMakanHelper,
            'honorAdmTim' => $honorAdmTim,
            'kasbonPilot' => $kasbonPilot,
            'kasbonHelper1' => $kasbonHelper1,
            'kasbonHelper2' => $kasbonHelper2,
            'ransumPerOrang' => $ransumPerOrang,
            'gajiBersihPilot' => $gajiBersihPilot,
            'gajiBersihHelper1' => $gajiBersihHelper1,
            'gajiBersihHelper2' => $gajiBersihHelper2,
            'totalGajiTim' => $totalGajiTim,
            'biayaEnterteintPerHa' => $biayaEnterteintPerHa,
            'totalEnterteint' => $totalEnterteint,
            'totalRincian' => $totalRincian,
            'totalPembayaran' => $totalPembayaran,
            'tampilkanHarga' => $tampilkanHarga,
        ];
    }

    /**
     * Buat berkas Excel rekapitulasi lengkap 7-Sheet dengan tampilan rapi, jelas, dan bergaris kisi (gridlines).
     *
     * @return array{ekspor: EksporLaporan, path: string, nama: string, baris: int}
     */
    public function buatRekapitulasi(
        int $bulan,
        int $tahun,
        ?int $distrikId = null,
        ?int $timId = null,
        ?string $tanggalMulai = null,
        ?string $tanggalSelesai = null,
        bool $tampilkanHarga = true,
        ?int $droneId = null
    ): array {
        $d = $this->siapkanDataRekapitulasi(
            $bulan,
            $tahun,
            $distrikId,
            $timId,
            $tanggalMulai,
            $tanggalSelesai,
            $tampilkanHarga,
            $droneId
        );

        $spreadsheet = new Spreadsheet();

        // Styling Global Standar Perusahaan
        $spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        if ($tampilkanHarga) {
            // === SHEET 1: COVER ===
            $sheetCover = $spreadsheet->getActiveSheet();
            $sheetCover->setTitle('Cover');
            $this->susunSheetCover($sheetCover, $d['tim'], $d['labelPeriode'], $d['totalLuasSpk'], $d['totalLuasRealisasi'], $d['totalNilai'], $d['hasilBersih80Persen'], $d['namaPilot'], $d['namaHelper1'], $d['namaHelper2']);

            // === SHEET 2: REKAP PROFIT & BON ===
            $sheetProfit = $spreadsheet->createSheet();
            $sheetProfit->setTitle('Rekap Profit & Bon');
            $this->susunSheetRekapProfit($sheetProfit, $d['tim'], $d['labelPeriode'], $d['totalLuasRealisasi'], $d['totalNilai'], $d['adm5Persen'], $d['investor15Persen'], $d['hasilBersih80Persen'], $d['totalOperasional'], $d['totalSparepart'], $d['totalGajiTim'], $d['totalKasbonKru'], $d['totalRansumKantin'], $d['totalRentalMobil'], $d['totalUpahLangsir'], $d['totalEnterteint'], $d['totalBonPribadi'], $d['totalPengeluaranBon'], $d['profitBersihDrone'], $d['sisaPiutangBulanLalu']);

            // === SHEET 3: LAMPIRAN REALISASI ===
            $sheetRealisasi = $spreadsheet->createSheet();
            $sheetRealisasi->setTitle('Lampiran Realisasi');
            $this->susunSheetRealisasi($sheetRealisasi, $d['daftarDetail'], $d['labelPeriode'], true, $d['totalLuasSpk'], $d['totalLuasRealisasi'], $d['totalNilai'], $d['adm5Persen'], $d['investor15Persen'], $d['hasilBersih80Persen'], $d['tim']);

            // === SHEET 4: SLIP GAJI ===
            $sheetGaji = $spreadsheet->createSheet();
            $sheetGaji->setTitle('Slip Gaji');
            $this->susunSheetSlipGaji($sheetGaji, $d['tim'], $d['labelPeriode'], $d['totalLuasRealisasi'], $d['namaPilot'], $d['namaHelper1'], $d['namaHelper2'], $d['daftarKasbon'], $d['totalRansumKantin'], $d['hariKerjaHelper'], $d['gajiPokokHelper'], $d['honorAdmTim']);

            // === SHEET 5: RINCIAN OPERASIONAL & BON ===
            $sheetOperasional = $spreadsheet->createSheet();
            $sheetOperasional->setTitle('Rincian Operasional');
            $this->susunSheetRincianOperasional($sheetOperasional, $d['tim'], $d['labelPeriode'], $d['daftarPengeluaran'], $d['daftarKasbon'], $d['daftarBbm']);

            // === SHEET 6: RANSUM & KANTIN ===
            $sheetRansum = $spreadsheet->createSheet();
            $sheetRansum->setTitle('Ransum & Kantin');
            $this->susunSheetRansumKantin($sheetRansum, $d['tim'], $d['labelPeriode'], $d['daftarPengeluaran'], $d['namaPilot'], $d['namaHelper1'], $d['namaHelper2']);

            // === SHEET 7: BAP PEMBAYARAN ===
            $sheetBap = $spreadsheet->createSheet();
            $sheetBap->setTitle('BAP Pembayaran');
            $this->susunSheetBapPembayaran($sheetBap, $d['tim'], $d['labelPeriode'], $d['hasilBersih80Persen'], $d['totalOperasional'], $d['totalSparepart'], $d['totalKasbonKru'], $d['gajiBersihPilot'], $d['gajiBersihHelper1'], $d['gajiBersihHelper2'], $d['honorAdmTim'], $d['namaPilot'], $d['namaHelper1'], $d['namaHelper2'], $d['totalEnterteint'], $d['totalRansumKantin'], $d['totalRentalMobil'], $d['totalUpahLangsir'], $d['totalBonPribadi'], $d['profitBersihDrone']);

            $spreadsheet->setActiveSheetIndex(0);
        } else {
            // Mode Tanpa Harga: Sheet Ringkas Rekap Realisasi Non-Harga
            $sheetRealisasi = $spreadsheet->getActiveSheet();
            $sheetRealisasi->setTitle('Rekap ' . ($tanggalMulai ? 'Kustom' : $bulan . '-' . $tahun));
            $this->susunSheetRealisasi($sheetRealisasi, $d['daftarDetail'], $d['labelPeriode'], false, $d['totalLuasSpk'], $d['totalLuasRealisasi'], 0, 0, 0, 0);
        }

        // Simpan File Excel ke Storage
        $unitDroneSlug = $d['tim']?->drone?->nomor_drone ? preg_replace('/[^A-Za-z0-9_-]/', '_', $d['tim']->drone->nomor_drone) : 'SEMUA_UNIT';
        $namaBerkas = sprintf('Laporan_7Sheet_%s_%04d_%02d_%s.xlsx', $unitDroneSlug, $tahun, $bulan, now()->format('His'));
        $folderPath = 'ekspor/' . $tahun;
        $simpanRelatif = $folderPath . '/' . $namaBerkas;
        $simpanAbsolut = storage_path('app/public/' . $simpanRelatif);

        if (! is_dir(dirname($simpanAbsolut))) {
            mkdir(dirname($simpanAbsolut), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($simpanAbsolut);

        // Catat ke tabel `ekspor_laporan`
        $ekspor = EksporLaporan::create([
            'nama_berkas' => $namaBerkas,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'diekspor_oleh' => Auth::id() ?? 1,
            'waktu_ekspor' => now(),
            'jumlah_baris' => $d['daftarDetail']->count(),
            'lokasi_berkas' => $simpanRelatif,
            'opsi_harga' => $tampilkanHarga,
        ]);

        $this->aktivitas->catat(
            'Ekspor Laporan Excel 7-Sheet',
            $ekspor,
            "Berkas: {$namaBerkas}, Baris Realisasi: {$d['daftarDetail']->count()}, Periode: {$d['labelPeriode']}"
        );

        return [
            'ekspor' => $ekspor,
            'path' => $simpanAbsolut,
            'nama' => $namaBerkas,
            'baris' => $d['daftarDetail']->count(),
        ];
    }

    /**
     * Buat berkas PDF rekapitulasi resmi 7-Sheet dengan tata letak dokumen cetak A4 landscape profesional.
     *
     * @return array{ekspor: EksporLaporan, path: string, nama: string, baris: int}
     */
    public function buatPdfRekapitulasi(
        int $bulan,
        int $tahun,
        ?int $distrikId = null,
        ?int $timId = null,
        ?string $tanggalMulai = null,
        ?string $tanggalSelesai = null,
        bool $tampilkanHarga = true,
        ?int $droneId = null
    ): array {
        $d = $this->siapkanDataRekapitulasi(
            $bulan,
            $tahun,
            $distrikId,
            $timId,
            $tanggalMulai,
            $tanggalSelesai,
            $tampilkanHarga,
            $droneId
        );

        $unitDroneSlug = $d['tim']?->drone?->nomor_drone ? preg_replace('/[^A-Za-z0-9_-]/', '_', $d['tim']->drone->nomor_drone) : 'SEMUA_UNIT';
        $namaBerkas = sprintf('Laporan_7Sheet_%s_%04d_%02d_%s.pdf', $unitDroneSlug, $tahun, $bulan, now()->format('His'));
        $folderPath = 'ekspor/' . $tahun;
        $simpanRelatif = $folderPath . '/' . $namaBerkas;
        $simpanAbsolut = storage_path('app/public/' . $simpanRelatif);

        if (! is_dir(dirname($simpanAbsolut))) {
            mkdir(dirname($simpanAbsolut), 0755, true);
        }

        $pdf = Pdf::loadView('ekspor.pdf', $d)
            ->setPaper('a4', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $pdf->save($simpanAbsolut);

        // Catat ke tabel `ekspor_laporan`
        $ekspor = EksporLaporan::create([
            'nama_berkas' => $namaBerkas,
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'diekspor_oleh' => Auth::id() ?? 1,
            'waktu_ekspor' => now(),
            'jumlah_baris' => $d['daftarDetail']->count(),
            'lokasi_berkas' => $simpanRelatif,
            'opsi_harga' => $tampilkanHarga,
        ]);

        $this->aktivitas->catat(
            'Ekspor Laporan PDF 7-Sheet',
            $ekspor,
            "Berkas: {$namaBerkas}, Baris Realisasi: {$d['daftarDetail']->count()}, Periode: {$d['labelPeriode']}"
        );

        return [
            'ekspor' => $ekspor,
            'path' => $simpanAbsolut,
            'nama' => $namaBerkas,
            'baris' => $d['daftarDetail']->count(),
        ];
    }

    /**
     * SHEET 1: Cover Laporan Resmi & Lembar Pengesahan.
     */
    private function susunSheetCover(
        Worksheet $sheet,
        ?Tim $tim,
        string $periode,
        float $luasSpk,
        float $luasRealisasi,
        float $nilaiKotor,
        float $nilaiBersih,
        string $pilot,
        string $helper1,
        string $helper2
    ): void {
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        $sheet->mergeCells('B2:F2');
        $sheet->setCellValue('B2', 'PT. PANLIBATA ANDALAS BERTUAH / DRONE BERTUAH');
        $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(15)->setColor(new Color('0F3D2F'));
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('B3:F3');
        $sheet->setCellValue('B3', 'LAPORAN PENGERJAAN & KEUANGAN UNIT DRONE');
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(12)->setColor(new Color('1E3A8A'));
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('B4:F4');
        $sheet->setCellValue('B4', 'PERIODE: ' . $periode . ' — DOKUMEN CLOSING RESMI');
        $sheet->getStyle('B4')->getFont()->setSize(10)->setColor(new Color('4B5563'))->setBold(true);
        $sheet->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Box Informasi Unit Drone
        $unitLabel = ($tim?->drone?->nomor_drone ?? 'DRONE OPERASIONAL') . (!empty($tim?->drone?->nama_drone) ? ' · ' . $tim->drone->nama_drone : '') . ' (' . ($tim?->drone?->tipe_drone ?? 'DJI Agras T50') . ')';
        $sheet->mergeCells('B6:F6');
        $sheet->setCellValue('B6', 'INFORMASI UNIT & TIM OPERASIONAL LAPANGAN');
        $sheet->getStyle('B6')->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle('B6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F3D2F');
        $sheet->getStyle('B6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(6)->setRowHeight(26);

        $infoRows = [
            ['Unit Drone & Tipe', $unitLabel, 'FF0F3D2F'],
            ['Nama Tim Lapangan', $tim?->nama_tim ?? 'Tim Lapangan', null],
            ['Pilot Utama', $pilot, null],
            ['Helper Lapangan', $helper1 . ' & ' . $helper2, null],
            ['Total Target Luasan SPK', number_format($luasSpk, 2) . ' Ha', null],
            ['Total Realisasi Aktual Selesai', number_format($luasRealisasi, 2) . ' Ha', 'FF059669'],
            ['Nilai Hasil Kotor (Gross Revenue)', 'Rp ' . number_format($nilaiKotor, 0, ',', '.'), null],
            ['Hasil Bersih Unit Operasional (80%)', 'Rp ' . number_format($nilaiBersih, 0, ',', '.'), 'FF065F46'],
        ];

        $r = 7;
        foreach ($infoRows as $idx => $row) {
            $sheet->mergeCells("B{$r}:C{$r}");
            $sheet->setCellValue("B{$r}", $row[0]);
            $sheet->getStyle("B{$r}")->getFont()->setBold(true);

            $sheet->mergeCells("D{$r}:F{$r}");
            $sheet->setCellValue("D{$r}", $row[1]);

            if ($row[2] !== null) {
                $sheet->getStyle("D{$r}")->getFont()->setBold(true)->setColor(new Color($row[2]));
            }

            $bg = $idx % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
            if ($idx === 7) {
                $bg = 'FFD1FAE5'; // Highlight 80% bersih
            }
            $sheet->getStyle("B{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sheet->getStyle("B{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(22);
            $r++;
        }

        // Box Lembar Pengesahan
        $barisTtd = $r + 2;
        $sheet->mergeCells("B{$barisTtd}:F{$barisTtd}");
        $sheet->setCellValue("B{$barisTtd}", 'LEMBAR PENGESAHAN LAPORAN CLOSING BULANAN');
        $sheet->getStyle("B{$barisTtd}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('0F3D2F'));
        $sheet->getStyle("B{$barisTtd}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $sheet->getStyle("B{$barisTtd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$barisTtd}:F{$barisTtd}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($barisTtd)->setRowHeight(24);

        $barisLabel = $barisTtd + 2;
        $sheet->mergeCells("B{$barisLabel}:C{$barisLabel}");
        $sheet->setCellValue("B{$barisLabel}", 'Dibuat Oleh:');
        $sheet->setCellValue("D{$barisLabel}", 'Diperiksa Oleh:');
        $sheet->mergeCells("E{$barisLabel}:F{$barisLabel}");
        $sheet->setCellValue("E{$barisLabel}", 'Disetujui Oleh:');
        $sheet->getStyle("B{$barisLabel}:F{$barisLabel}")->getFont()->setBold(true)->setColor(new Color('64748B'));
        $sheet->getStyle("B{$barisLabel}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$barisLabel}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$barisLabel}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $barisNama = $barisLabel + 5;
        $sheet->mergeCells("B{$barisNama}:C{$barisNama}");
        $sheet->setCellValue("B{$barisNama}", '( Admin Finance / Tiara )');
        $sheet->setCellValue("D{$barisNama}", 'Icuk Harma');
        $sheet->mergeCells("E{$barisNama}:F{$barisNama}");
        $sheet->setCellValue("E{$barisNama}", 'Wahyudi');
        $sheet->getStyle("B{$barisNama}:F{$barisNama}")->getFont()->setBold(true);
        $sheet->getStyle("B{$barisNama}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$barisNama}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$barisNama}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $barisJab = $barisNama + 1;
        $sheet->mergeCells("B{$barisJab}:C{$barisJab}");
        $sheet->setCellValue("B{$barisJab}", 'Admin & Keuangan');
        $sheet->setCellValue("D{$barisJab}", 'Head of Operations');
        $sheet->mergeCells("E{$barisJab}:F{$barisJab}");
        $sheet->setCellValue("E{$barisJab}", 'Owner / Direktur Utama');
        $sheet->getStyle("B{$barisJab}:F{$barisJab}")->getFont()->setSize(9)->setColor(new Color('64748B'));
        $sheet->getStyle("B{$barisJab}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$barisJab}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$barisJab}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Lebar kolom terukur rapi
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(22);
    }

    /**
     * SHEET 2: Rekap Profit & Bon.
     * Menggunakan 4 kolom murni (tanpa merge data) agar tabel sejajar rapi, mudah dihitung, dan jelas terbaca.
     */
    private function susunSheetRekapProfit(
        Worksheet $sheet,
        ?Tim $tim,
        string $periode,
        float $luasRealisasi,
        float $nilaiKotor,
        float $adm5Persen,
        float $investor15Persen,
        float $hasilBersih80Persen,
        float $operasional,
        float $sparepart,
        float $gajiTim,
        float $kasbonKru,
        float $ransumKantin,
        float $rentalMobil,
        float $upahLangsir,
        float $enterteint,
        float $bonPribadi,
        float $totalPengeluaranBon,
        float $profitBersih,
        float $sisaPiutangBulanLalu = 0.0
    ): void {
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        $unitLabel = ($tim?->drone?->nomor_drone ?? 'DRONE OPERASIONAL') . (!empty($tim?->drone?->nama_drone) ? ' · ' . $tim->drone->nama_drone : '') . ' - ' . ($tim?->drone?->tipe_drone ?? 'T50');

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'REKAPITULASI HASIL, OPERASIONAL & PROFIT UNIT DRONE');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('0B2E24'));

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'UNIT: ' . $unitLabel . ' | PERIODE: ' . $periode);
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('5B6C64'))->setBold(true);

        // Bagian A: HASIL PEKERJAAN
        $sheet->mergeCells('A4:D4');
        $sheet->setCellValue('A4', 'A. PENDAPATAN & BAGI HASIL PEKERJAAN');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle('A4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F3D2F');
        $sheet->getRowDimension(4)->setRowHeight(25);

        $rowsPendapatan = [
            ['Total Luasan Realisasi Pekerjaan', $luasRealisasi, 'Ha', 'Disetujui Pemeriksa'],
            ['Total Nilai Hasil Kotor (Gross Revenue)', $nilaiKotor, 'Rp', '100% Kontrak'],
            ['Potongan Administrasi & Pajak (ADM 5%)', -$adm5Persen, 'Rp', 'Biaya Kantor Panlibata'],
            ['Bagi Hasil Pemilik Modal / Investor (15%)', -$investor15Persen, 'Rp', 'Bagi Hasil Investor Unit'],
            ['HASIL BERSIH UNIT OPERASIONAL (80%)', $hasilBersih80Persen, 'Rp', 'Hak Bersih Unit Drone'],
        ];

        $r = 5;
        foreach ($rowsPendapatan as $idx => $item) {
            $sheet->setCellValue("A{$r}", $item[0]);
            $sheet->setCellValue("B{$r}", $item[1]);
            $sheet->setCellValue("C{$r}", $item[2]);
            $sheet->setCellValue("D{$r}", $item[3]);

            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode($item[2] === 'Ha' ? '#,##0.00' : '#,##0');
            $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx === 4) {
                $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setColor(new Color('065F46'));
                $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');
            } else {
                $bg = $idx % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
                $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            }
            $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(22);
            $r++;
        }

        // Bagian B: BIAYA & BON OPERASIONAL
        $r++;
        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->setCellValue("A{$r}", 'B. PENGELUARAN & BON OPERASIONAL UNIT (BULAN BERJALAN)');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getRowDimension($r)->setRowHeight(25);

        $rowsBiaya = [
            ['Operasional Lapangan (Solar BBM, Belanja Logistik, Antar Drone)', $operasional, 'Sheet 5 - Rincian Kas'],
            ['Bon Suku Cadang / Sparepart (Nomor PI Resmi)', $sparepart, 'Sheet 5 - Bon PI'],
            ['Gaji Bersih Tim Lapangan & Admin (Pilot, Helper, Adm)', $gajiTim, 'Sheet 4 - Total Slip Gaji'],
            ['Bon Dana Cash / Kasbon Kru Lapangan', $kasbonKru, 'Sheet 5 - Pinjaman Kru'],
            ['Bon Ransum Konsumsi Tim & Kantin Distrik', $ransumKantin, 'Sheet 6 - Belanja Ransum'],
            ['Rental Mobil Operasional Unit', $rentalMobil, 'Logistik Kendaraan'],
            ['Upah Langsir Pupuk / Pestisida', $upahLangsir, 'Tenaga Angkut Lapangan'],
            ['Enterteint / Koordinasi Lapangan (' . number_format($luasRealisasi, 2) . ' Ha @ Rp 10.000)', $enterteint, 'Koordinasi Mandor/Distrik'],
            ['Bon Pribadi Owner / Keperluan Khusus', $bonPribadi, 'Bon Pemilik Unit'],
            ['TOTAL PENGELUARAN & BON OPERASIONAL', $totalPengeluaranBon, 'Total Seluruh Pengurang'],
        ];

        $r++;
        foreach ($rowsBiaya as $idx => $b) {
            $sheet->setCellValue("A{$r}", $b[0]);
            $sheet->setCellValue("B{$r}", $b[1]);
            $sheet->setCellValue("C{$r}", 'Rp');
            $sheet->setCellValue("D{$r}", $b[2]);
            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx === count($rowsBiaya) - 1) {
                $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setColor(new Color('991B1B'));
                $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
            } else {
                $bg = $idx % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
                $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            }
            $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(22);
            $r++;
        }

        // Bagian C: PROFIT UNIT DRONE
        $r++;
        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->setCellValue("A{$r}", 'C. SISA HASIL / PROFIT UNIT DRONE BERTUAH');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF047857');
        $sheet->getRowDimension($r)->setRowHeight(25);

        $r++;
        $sheet->setCellValue("A{$r}", 'Hasil Bersih Unit (80%)');
        $sheet->setCellValue("B{$r}", $hasilBersih80Persen);
        $sheet->setCellValue("C{$r}", 'Rp');
        $sheet->setCellValue("D{$r}", 'Net Pendapatan');
        $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(22);

        $r++;
        $sheet->setCellValue("A{$r}", 'Total Pengeluaran Bon Operasional');
        $sheet->setCellValue("B{$r}", -$totalPengeluaranBon);
        $sheet->setCellValue("C{$r}", 'Rp');
        $sheet->setCellValue("D{$r}", 'Akumulasi Beban');
        $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(22);

        $r++;
        $sheet->setCellValue("A{$r}", 'PROFIT BERSIH DRONE BERTUAH (DITRANSFER)');
        $sheet->setCellValue("B{$r}", $profitBersih);
        $sheet->setCellValue("C{$r}", 'Rp');
        $sheet->setCellValue("D{$r}", 'Laba Bersih Ditransfer');
        $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('065F46'));
        $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');
        $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('059669'));
        $sheet->getRowDimension($r)->setRowHeight(24);

        // Bagian D: REKAPITULASI BON / PIUTANG PEMILIK UNIT (PAK WAHYUDI)
        $r += 2;
        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->setCellValue("A{$r}", 'D. REKAPITULASI BON / PIUTANG PEMILIK UNIT (PAK WAHYUDI)');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF991B1B');
        $sheet->getRowDimension($r)->setRowHeight(25);

        $rowsPiutang = [
            ['Sisa Bon / Piutang Bulan Sebelumnya (Kumulatif)', -$sisaPiutangBulanLalu, 'Saldo Piutang Lalu'],
            ['Penggantian Bon Bulan Berjalan (' . $periode . ')', -$bonPribadi, 'Bon Baru Bulan Ini'],
            ['TOTAL SISA SALDO PIUTANG BERJALAN PEMILIK', -($sisaPiutangBulanLalu + $bonPribadi), 'Kewajiban Penggantian'],
        ];

        $r++;
        foreach ($rowsPiutang as $idx => $p) {
            $sheet->setCellValue("A{$r}", $p[0]);
            $sheet->setCellValue("B{$r}", $p[1]);
            $sheet->setCellValue("C{$r}", 'Rp');
            $sheet->setCellValue("D{$r}", $p[2]);
            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($idx === 2) {
                $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setColor(new Color('991B1B'));
                $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
            } else {
                $bg = $idx % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
                $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            }
            $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(22);
            $r++;
        }

        // Lebar Kolom Terukur
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(28);
    }

    /**
     * SHEET 3: Lampiran Realisasi Pekerjaan (Standar Resmi 11/14 Kolom).
     *
     * @param  \Illuminate\Support\Collection<int, DetailRealisasi>  $daftarDetail
     */
    private function susunSheetRealisasi(
        Worksheet $sheet,
        $daftarDetail,
        string $periode,
        bool $tampilkanHarga,
        float $totalLuasSpk,
        float $totalLuasRealisasi,
        float $totalNilai,
        float $adm,
        float $investor,
        float $bersih,
        ?Tim $tim = null
    ): void {
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        $kolomAkhir = $tampilkanHarga ? 'N' : 'L';

        $sheet->mergeCells('A1:' . $kolomAkhir . '1');
        $sheet->setCellValue('A1', 'LAMPIRAN HASIL PEKERJAAN DRONE SPRAYING & MAPPING');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new Color('0B2E24'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:' . $kolomAkhir . '2');
        $sheet->setCellValue('A2', 'PT. PANLIBATA ANDALAS BERTUAH — PERIODE: ' . $periode . ' — STANDAR RESMI');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('5B6C64'))->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Kolom
        if ($tampilkanHarga) {
            $headers = [
                'A4' => 'TGL',
                'B4' => 'SPK',
                'C4' => 'TIM',
                'D4' => 'PILOT',
                'E4' => 'HELPER',
                'F4' => 'LUASAN SPK',
                'G4' => 'Ha',
                'H4' => 'PEKERJAAN',
                'I4' => 'PETAK',
                'J4' => 'LUAS',
                'K4' => 'Ha',
                'L4' => 'HARGA (Rp)',
                'M4' => 'TOTAL (Rp)',
                'N4' => 'CHECK',
            ];
        } else {
            $headers = [
                'A4' => 'TGL',
                'B4' => 'SPK',
                'C4' => 'TIM',
                'D4' => 'PILOT',
                'E4' => 'HELPER',
                'F4' => 'LUASAN SPK',
                'G4' => 'Ha',
                'H4' => 'PEKERJAAN',
                'I4' => 'PETAK',
                'J4' => 'LUAS',
                'K4' => 'Ha',
                'L4' => 'CHECK',
            ];
        }

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerRange = 'A4:' . $kolomAkhir . '4';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F3D2F');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(28);

        $barisIndex = 5;
        $spkPetakTercatat = [];

        foreach ($daftarDetail as $idx => $d) {
            $laporan = $d->laporan;
            $spk = $laporan?->spk;

            $luasSpkDisplay = 0.0;
            if ($spk) {
                if ($d->petak_id) {
                    $kunciSpkPetak = $spk->id . '_' . $d->petak_id;
                    if (! isset($spkPetakTercatat[$kunciSpkPetak])) {
                        $detailSpk = DetailSpk::where('spk_id', $spk->id)->where('petak_id', $d->petak_id)->first();
                        $luasSpkDisplay = (float) ($detailSpk?->luas_target_ha ?? 0);
                        $spkPetakTercatat[$kunciSpkPetak] = true;
                    }
                } elseif ($d->kode_petak_manual) {
                    $kunciSpkPetak = $spk->id . '_' . $d->kode_petak_manual;
                    if (! isset($spkPetakTercatat[$kunciSpkPetak])) {
                        $detailSpk = DetailSpk::where('spk_id', $spk->id)
                            ->whereHas('petak', fn ($pq) => $pq->where('kode_petak', $d->kode_petak_manual))
                            ->first();
                        $luasSpkDisplay = (float) ($detailSpk?->luas_target_ha ?? 0);
                        $spkPetakTercatat[$kunciSpkPetak] = true;
                    }
                }
            }

            $tarif = (float) ($d->tarif_per_ha ?? 0);
            if ($tarif <= 0 && $d->jenis_pekerjaan_id) {
                $tarifData = TarifPekerjaan::where('jenis_pekerjaan_id', $d->jenis_pekerjaan_id)
                    ->where('berlaku_mulai', '<=', $d->tanggal_pekerjaan)
                    ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $d->tanggal_pekerjaan))
                    ->latest('berlaku_mulai')
                    ->first();
                $tarif = (float) ($tarifData?->nilai_tarif ?? 0);
            }

            $tarifMapping = 0.0;
            if ($d->perlu_mapping) {
                $tarifMapping = (float) ($d->tarif_mapping_per_ha ?? 0);
                if ($tarifMapping <= 0) {
                    $pekerjaanMapping = \App\Models\JenisPekerjaan::where('tipe', \App\Models\JenisPekerjaan::TIPE_ADDON)
                        ->orWhere('kode_pekerjaan', 'SRV-MAPPING')
                        ->first();
                    if ($pekerjaanMapping) {
                        $tarifMapping = (float) (\App\Models\TarifPekerjaan::where('jenis_pekerjaan_id', $pekerjaanMapping->id)
                            ->where('berlaku_mulai', '<=', $d->tanggal_pekerjaan)
                            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $d->tanggal_pekerjaan))
                            ->latest('berlaku_mulai')
                            ->value('nilai_tarif') ?? 30000);
                    } else {
                        $tarifMapping = 30000.0;
                    }
                }
            }

            $tarifTotalSatuan = $tarif + $tarifMapping;
            $luasRealisasi = (float) $d->luas_realisasi_ha;
            $subtotal = $luasRealisasi * $tarifTotalSatuan;

            $tanggalCheck = $laporan?->tanggal_pemeriksaan
                ? $laporan->tanggal_pemeriksaan->format('d/m/Y')
                : ($d->tanggal_pekerjaan ? $d->tanggal_pekerjaan->format('d/m/Y') : 'Disetujui');

            $namaPekerjaan = $d->jenisPekerjaan?->nama_pekerjaan ?? '-';
            if ($d->perlu_mapping) {
                $namaPekerjaan .= ' (+Mapping)';
            }

            $sheet->setCellValue('A' . $barisIndex, $d->tanggal_pekerjaan ? $d->tanggal_pekerjaan->format('d/m/Y') : '-');
            $sheet->setCellValue('B' . $barisIndex, $spk?->nomor_spk ?? '-');
            $sheet->setCellValue('C' . $barisIndex, $laporan?->tim?->nama_tim ?? '-');
            $sheet->setCellValue('D' . $barisIndex, $laporan?->nama_pilot ?? '-');
            $sheet->setCellValue('E' . $barisIndex, $laporan?->nama_helper ?? '—');
            $sheet->setCellValue('F' . $barisIndex, $luasSpkDisplay > 0 ? $luasSpkDisplay : 0);
            $sheet->setCellValue('G' . $barisIndex, 'Ha');
            $sheet->setCellValue('H' . $barisIndex, $namaPekerjaan);
            $sheet->setCellValue('I' . $barisIndex, $d->petak?->kode_petak ?? ($d->kode_petak_manual ?: '-'));
            $sheet->setCellValue('J' . $barisIndex, $luasRealisasi);
            $sheet->setCellValue('K' . $barisIndex, 'Ha');

            if ($tampilkanHarga) {
                $sheet->setCellValue('L' . $barisIndex, $tarifTotalSatuan);
                $sheet->setCellValue('M' . $barisIndex, $subtotal);
                $sheet->setCellValue('N' . $barisIndex, $tanggalCheck);
                $sheet->getStyle('L' . $barisIndex)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('M' . $barisIndex)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('N' . $barisIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            } else {
                $sheet->setCellValue('L' . $barisIndex, $tanggalCheck);
                $sheet->getStyle('L' . $barisIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $sheet->getStyle('F' . $barisIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('J' . $barisIndex)->getNumberFormat()->setFormatCode('#,##0.00');

            // Alignment rapi
            $sheet->getStyle('A' . $barisIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $barisIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $barisIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $barisIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K' . $barisIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $bg = $idx % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
            $sheet->getStyle("A{$barisIndex}:{$kolomAkhir}{$barisIndex}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sheet->getStyle("A{$barisIndex}:{$kolomAkhir}{$barisIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($barisIndex)->setRowHeight(21);

            $barisIndex++;
        }

        // Baris Grand Total Realisasi
        $barisTotal = $barisIndex;
        $sheet->mergeCells('A' . $barisTotal . ':E' . $barisTotal);
        $sheet->setCellValue('A' . $barisTotal, 'TOTAL REKAPITULASI REALISASI');
        $sheet->getStyle('A' . $barisTotal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('F' . $barisTotal, $totalLuasSpk);
        $sheet->setCellValue('G' . $barisTotal, 'Ha');
        $sheet->setCellValue('H' . $barisTotal, '-');
        $sheet->setCellValue('I' . $barisTotal, '-');
        $sheet->setCellValue('J' . $barisTotal, $totalLuasRealisasi);
        $sheet->setCellValue('K' . $barisTotal, 'Ha');

        if ($tampilkanHarga) {
            $sheet->setCellValue('L' . $barisTotal, '-');
            $sheet->setCellValue('M' . $barisTotal, $totalNilai);
            $sheet->setCellValue('N' . $barisTotal, 'SELESAI');
            $sheet->getStyle('M' . $barisTotal)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('N' . $barisTotal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            $sheet->setCellValue('L' . $barisTotal, 'SELESAI');
            $sheet->getStyle('L' . $barisTotal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle('F' . $barisTotal)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('J' . $barisTotal)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('G' . $barisTotal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $barisTotal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I' . $barisTotal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K' . $barisTotal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rangeTotal = 'A' . $barisTotal . ':' . $kolomAkhir . $barisTotal;
        $sheet->getStyle($rangeTotal)->getFont()->setBold(true);
        $sheet->getStyle($rangeTotal)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle($rangeTotal)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('94A3B8'));
        $sheet->getRowDimension($barisTotal)->setRowHeight(24);

        if ($tampilkanHarga) {
            $barisAdm = $barisTotal + 2;
            $sheet->mergeCells('I' . $barisAdm . ':L' . $barisAdm);
            $sheet->setCellValue('I' . $barisAdm, 'Biaya Administrasi Perusahaan (ADM 5%):');
            $sheet->setCellValue('M' . $barisAdm, $adm);
            $sheet->getStyle('I' . $barisAdm . ':M' . $barisAdm)->getFont()->setBold(true);
            $sheet->getStyle('M' . $barisAdm)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I' . $barisAdm . ':M' . $barisAdm)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($barisAdm)->setRowHeight(21);

            $barisInv = $barisAdm + 1;
            $sheet->mergeCells('I' . $barisInv . ':L' . $barisInv);
            $sheet->setCellValue('I' . $barisInv, 'Bagi Hasil Investor Modal (15%):');
            $sheet->setCellValue('M' . $barisInv, $investor);
            $sheet->getStyle('I' . $barisInv . ':M' . $barisInv)->getFont()->setBold(true);
            $sheet->getStyle('M' . $barisInv)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I' . $barisInv . ':M' . $barisInv)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($barisInv)->setRowHeight(21);

            $barisBersih = $barisInv + 1;
            $sheet->mergeCells('I' . $barisBersih . ':L' . $barisBersih);
            $sheet->setCellValue('I' . $barisBersih, 'Sisa Bersih Operasional Unit (80%):');
            $sheet->setCellValue('M' . $barisBersih, $bersih);
            $sheet->getStyle('I' . $barisBersih . ':M' . $barisBersih)->getFont()->setBold(true)->setColor(new Color('065F46'));
            $sheet->getStyle('M' . $barisBersih)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I' . $barisBersih . ':M' . $barisBersih)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');
            $sheet->getStyle('I' . $barisBersih . ':M' . $barisBersih)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('059669'));
            $sheet->getRowDimension($barisBersih)->setRowHeight(22);
        }

        // Lebar kolom terukur proporsional
        $sheet->getColumnDimension('A')->setWidth(13);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(6);
        $sheet->getColumnDimension('H')->setWidth(22);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->getColumnDimension('J')->setWidth(14);
        $sheet->getColumnDimension('K')->setWidth(6);
        if ($tampilkanHarga) {
            $sheet->getColumnDimension('L')->setWidth(16);
            $sheet->getColumnDimension('M')->setWidth(18);
            $sheet->getColumnDimension('N')->setWidth(16);
        } else {
            $sheet->getColumnDimension('L')->setWidth(16);
        }
    }

    /**
     * SHEET 4: Slip Gaji Pilot & Helper & Admin Tim.
     * Menggunakan tata letak kartu yang rapi, nomor terformat, dan total terverifikasi Rp 6.895.001.
     *
     * @param  \Illuminate\Support\Collection<int, KasbonPersonil>  $daftarKasbon
     */
    private function susunSheetSlipGaji(
        Worksheet $sheet,
        ?Tim $tim,
        string $periode,
        float $luasRealisasi,
        string $pilot,
        string $helper1,
        string $helper2,
        $daftarKasbon,
        float $totalRansum,
        int $hariKerjaHelper = 18,
        float $gajiPokokHelper = 0.0,
        float $honorAdmTim = 500000.0
    ): void {
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'SLIP GAJI & INSENTIF KRU OPERASIONAL DRONE');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('0B2E24'));

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'PERIODE: ' . $periode . ' | UNIT: ' . ($tim?->drone?->nomor_drone ?? 'UNIT DRONE'));
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('5B6C64'))->setBold(true);

        // Tarif dan pembagian
        $premiPilotPerHa = (float) ($tim?->premi_pilot_per_ha ?? 22000);
        $premiHelperPerHa = (float) ($tim?->premi_helper_per_ha ?? 10000);
        $skemaHelper = $tim?->skema_upah_helper ?? Tim::SKEMA_HELPER_PER_HEKTAR;
        $tarifHelperPerHari = (float) ($tim?->tarif_helper_per_hari ?? 100000);
        $gajiPokokPilot = (float) ($tim?->gaji_pokok_pilot ?? 1000000);
        $uangMakanPilot = (float) ($tim?->uang_makan_pilot ?? 900000);
        $tarifMakanHelperPerHari = (float) ($tim?->uang_makan_helper_per_hari ?? 50000);
        $uangMakanHelper = (float) ($hariKerjaHelper * $tarifMakanHelperPerHari);

        // Kasbon per orang
        $kasbonPilot = (float) $daftarKasbon->where('peran_kru', KasbonPersonil::PILOT)->sum('jumlah_kasbon');
        $kasbonHelper1 = (float) $daftarKasbon->where('nama_personil', $helper1)->sum('jumlah_kasbon');
        $kasbonHelper2 = (float) $daftarKasbon->where('nama_personil', $helper2)->sum('jumlah_kasbon');
        if ($kasbonHelper1 <= 0 && $kasbonHelper2 <= 0) {
            $totalKasbonHelper = (float) $daftarKasbon->where('peran_kru', KasbonPersonil::HELPER)->sum('jumlah_kasbon');
            $kasbonHelper1 = $totalKasbonHelper / 2;
            $kasbonHelper2 = $totalKasbonHelper / 2;
        }

        // Potongan ransum per orang (dibagi 3 kru)
        $ransumPerOrang = $totalRansum > 0 ? $totalRansum / 3 : 0;

        $labelMakanHelper = 'Makan (' . $hariKerjaHelper . ' Hari * Rp. ' . number_format($tarifMakanHelperPerHari, 0, ',', '.') . ')';
        $labelUpahHelper = $skemaHelper === Tim::SKEMA_HELPER_HARIAN
            ? 'Upah Harian (' . $hariKerjaHelper . ' Hari * Rp. ' . number_format($tarifHelperPerHari, 0, ',', '.') . ')'
            : 'Premi (' . number_format($luasRealisasi, 2) . ' Ha * Rp. ' . number_format($premiHelperPerHa, 0, ',', '.') . ')';
        $upahHelperSatuan = $skemaHelper === Tim::SKEMA_HELPER_HARIAN
            ? (float) ($hariKerjaHelper * $tarifHelperPerHari)
            : (float) ($luasRealisasi * $premiHelperPerHa);

        $kruData = [
            [
                'jabatan' => 'PILOT DRONE',
                'nama' => $pilot,
                'items' => [
                    ['Gaji Pokok Pilot', $gajiPokokPilot, null],
                    ['Uang Makan Pilot Bulanan', $uangMakanPilot, null],
                    ['Premi (' . number_format($luasRealisasi, 2) . ' Ha * Rp. ' . number_format($premiPilotPerHa, 0, ',', '.') . ')', $luasRealisasi * $premiPilotPerHa, null],
                    ['Subtotal Pendapatan Kotor', $gajiPokokPilot + $uangMakanPilot + ($luasRealisasi * $premiPilotPerHa), 'kotor'],
                    ['Bon Dana Cash (Kasbon)', -$kasbonPilot, null],
                    ['BON RANSUM (1/3 Bagian)', -$ransumPerOrang, null],
                    ['Total Potongan', -($kasbonPilot + $ransumPerOrang), 'potongan'],
                    ['Gaji Bersih Diterima', ($gajiPokokPilot + $uangMakanPilot + ($luasRealisasi * $premiPilotPerHa)) - ($kasbonPilot + $ransumPerOrang), 'bersih'],
                ],
            ],
            [
                'jabatan' => 'HELPER 1',
                'nama' => $helper1,
                'items' => [
                    ['Gaji Pokok Helper', $gajiPokokHelper, null],
                    [$labelMakanHelper, $uangMakanHelper, null],
                    [$labelUpahHelper, $upahHelperSatuan, null],
                    ['Subtotal Pendapatan Kotor', $gajiPokokHelper + $uangMakanHelper + $upahHelperSatuan, 'kotor'],
                    ['Bon Dana Cash (Kasbon)', -$kasbonHelper1, null],
                    ['BON RANSUM (1/3 Bagian)', -$ransumPerOrang, null],
                    ['Total Potongan', -($kasbonHelper1 + $ransumPerOrang), 'potongan'],
                    ['Gaji Bersih Diterima', ($gajiPokokHelper + $uangMakanHelper + $upahHelperSatuan) - ($kasbonHelper1 + $ransumPerOrang), 'bersih'],
                ],
            ],
            [
                'jabatan' => 'HELPER 2',
                'nama' => $helper2,
                'items' => [
                    ['Gaji Pokok Helper', $gajiPokokHelper, null],
                    [$labelMakanHelper, $uangMakanHelper, null],
                    [$labelUpahHelper, $upahHelperSatuan, null],
                    ['Subtotal Pendapatan Kotor', $gajiPokokHelper + $uangMakanHelper + $upahHelperSatuan, 'kotor'],
                    ['Bon Dana Cash (Kasbon)', -$kasbonHelper2, null],
                    ['BON RANSUM (1/3 Bagian)', -$ransumPerOrang, null],
                    ['Total Potongan', -($kasbonHelper2 + $ransumPerOrang), 'potongan'],
                    ['Gaji Bersih Diterima', ($gajiPokokHelper + $uangMakanHelper + $upahHelperSatuan) - ($kasbonHelper2 + $ransumPerOrang), 'bersih'],
                ],
            ],
            [
                'jabatan' => 'ADMIN TIM LAPANGAN',
                'nama' => 'TIARA / ADM',
                'items' => [
                    ['Honor Administrasi Tim Bulanan', $honorAdmTim, null],
                    ['Subtotal Pendapatan Kotor', $honorAdmTim, 'kotor'],
                    ['Potongan Kasbon / Bon Cash', 0, null],
                    ['Total Potongan', 0, 'potongan'],
                    ['Gaji Bersih Diterima', $honorAdmTim, 'bersih'],
                ],
            ],
        ];

        $r = 4;
        $totalSemuaGajiBersih = 0.0;

        foreach ($kruData as $k) {
            $sheet->mergeCells("A{$r}:D{$r}");
            $sheet->setCellValue("A{$r}", $k['jabatan'] . ' : ' . strtoupper($k['nama']));
            $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
            $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($k['jabatan'] === 'ADMIN TIM LAPANGAN' ? 'FF1E3A8A' : 'FF0F3D2F');
            $sheet->getRowDimension($r)->setRowHeight(24);
            $r++;

            foreach ($k['items'] as $it) {
                $sheet->setCellValue("A{$r}", $it[0]);
                $sheet->setCellValue("B{$r}", $it[1]);
                $sheet->setCellValue("C{$r}", 'Rp');
                $sheet->setCellValue("D{$r}", $it[2] === 'bersih' ? 'Gaji Masuk Rekening' : ($it[2] === 'potongan' ? 'Potongan Pinjaman' : ''));

                $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($it[2] === 'kotor') {
                    $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
                } elseif ($it[2] === 'potongan') {
                    $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setColor(new Color('991B1B'));
                    $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
                } elseif ($it[2] === 'bersih') {
                    $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setColor(new Color('065F46'));
                    $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');
                    $totalSemuaGajiBersih += $it[1];
                }

                $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
                $sheet->getRowDimension($r)->setRowHeight(21);
                $r++;
            }
            $r++;
        }

        // Grand Total Semua Kru & Admin
        $sheet->mergeCells("A{$r}:C{$r}");
        $sheet->setCellValue("A{$r}", 'TOTAL DANA GAJI & PREMI KRU UNIT (PILOT, HELPER 1, HELPER 2, ADM)');
        $sheet->setCellValue("D{$r}", $totalSemuaGajiBersih);
        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('064E3B'));
        $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFECFDF5');
        $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('059669'));
        $sheet->getRowDimension($r)->setRowHeight(26);

        // Lebar kolom terukur
        $sheet->getColumnDimension('A')->setWidth(48);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(28);
    }

    /**
     * SHEET 5: Rincian Pengeluaran Operasional, Bon Sparepart, dan Kasbon Kru.
     *
     * @param  \Illuminate\Support\Collection<int, PengeluaranOperasionalUnit>  $pengeluaran
     * @param  \Illuminate\Support\Collection<int, KasbonPersonil>  $kasbon
     * @param  \Illuminate\Support\Collection<int, TransaksiBbm>  $bbm
     */
    private function susunSheetRincianOperasional(
        Worksheet $sheet,
        ?Tim $tim,
        string $periode,
        $pengeluaran,
        $kasbon,
        $bbm
    ): void {
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'RINCIAN PENGELUARAN KAS OPERASIONAL & BON UNIT');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new Color('0B2E24'));

        // TABEL 1: Operasional Lapangan
        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', '1. OPERASIONAL LAPANGAN (SOLAR BBM, BELANJA LOGISTIK, ANTAR DRONE)');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle('A3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F3D2F');
        $sheet->getRowDimension(3)->setRowHeight(24);

        $headers = ['A4' => 'NO', 'B4' => 'TANGGAL', 'C4' => 'KATEGORI', 'D4' => 'RINCIAN / KETERANGAN', 'E4' => 'JUMLAH (Rp)', 'F4' => 'YTD (Rp)', 'G4' => 'ALL YTD (Rp)', 'H4' => 'NO. BUKTI'];
        foreach ($headers as $cell => $t) {
            $sheet->setCellValue($cell, $t);
        }
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(24);

        $ops = $pengeluaran->whereIn('kategori_biaya', [
            PengeluaranOperasionalUnit::OPERASIONAL_LAPANGAN,
            PengeluaranOperasionalUnit::RENTAL_MOBIL,
            PengeluaranOperasionalUnit::UPAH_LANGSIR,
        ]);

        $r = 5;
        $no = 1;
        $totalOps = 0.0;
        $ytdOps = 0.0;

        foreach ($ops as $p) {
            $nom = (float) $p->nominal;
            $ytdOps += $nom;
            $sheet->setCellValue("A{$r}", $no++);
            $sheet->setCellValue("B{$r}", $p->tanggal_transaksi ? $p->tanggal_transaksi->format('d/m/Y') : '-');
            $sheet->setCellValue("C{$r}", ucwords(str_replace('_', ' ', $p->kategori_biaya)));
            $sheet->setCellValue("D{$r}", $p->keterangan);
            $sheet->setCellValue("E{$r}", $nom);
            $sheet->setCellValue("F{$r}", $ytdOps);
            $sheet->setCellValue("G{$r}", $ytdOps);
            $sheet->setCellValue("H{$r}", $p->nomor_bukti_pi ?? '-');

            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');

            $bg = $no % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
            $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(21);
            $totalOps += $nom;
            $r++;
        }

        if ($ops->isEmpty() && $bbm->isNotEmpty()) {
            foreach ($bbm as $b) {
                $nomBbm = (float) $b->total_biaya;
                $ytdOps += $nomBbm;
                $sheet->setCellValue("A{$r}", $no++);
                $sheet->setCellValue("B{$r}", $b->tanggal_transaksi ? $b->tanggal_transaksi->format('d/m/Y') : '-');
                $sheet->setCellValue("C{$r}", 'BBM Solar');
                $sheet->setCellValue("D{$r}", 'Pembelian BBM Solar: ' . $b->jumlah_liter . ' Liter');
                $sheet->setCellValue("E{$r}", $nomBbm);
                $sheet->setCellValue("F{$r}", $ytdOps);
                $sheet->setCellValue("G{$r}", $ytdOps);
                $sheet->setCellValue("H{$r}", $b->nomor_struk_nota ?? $b->nomor_transaksi);

                $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');

                $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
                $sheet->getRowDimension($r)->setRowHeight(21);
                $totalOps += $nomBbm;
                $r++;
            }
        }

        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->setCellValue("A{$r}", 'SUBTOTAL OPERASIONAL LAPANGAN');
        $sheet->setCellValue("E{$r}", $totalOps);
        $sheet->setCellValue("F{$r}", $totalOps);
        $sheet->setCellValue("G{$r}", $totalOps);
        $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:H{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('94A3B8'));
        $sheet->getRowDimension($r)->setRowHeight(23);

        // TABEL 2: Bon Suku Cadang / Sparepart
        $r += 2;
        $sheet->mergeCells("A{$r}:H{$r}");
        $sheet->setCellValue("A{$r}", '2. BON SUKU CADANG / SPAREPART (DENGAN NOMOR PI RESMI)');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0284C7');
        $sheet->getRowDimension($r)->setRowHeight(24);

        $r++;
        $headersSp = ['A' . $r => 'NO', 'B' . $r => 'TANGGAL', 'C' . $r => 'NOMOR PI / BON', 'D' . $r => 'NAMA SUKU CADANG', 'E' . $r => 'JUMLAH (Rp)', 'F' . $r => 'YTD (Rp)', 'G' . $r => 'ALL YTD (Rp)', 'H' . $r => 'STATUS'];
        foreach ($headersSp as $cell => $t) {
            $sheet->setCellValue($cell, $t);
        }
        $sheet->getStyle("A{$r}:H{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$r}:H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($r)->setRowHeight(24);

        $sp = $pengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::SPAREPART);
        $r++;
        $noSp = 1;
        $totalSp = 0.0;
        $ytdSp = 0.0;

        foreach ($sp as $s) {
            $nomSp = (float) $s->nominal;
            $ytdSp += $nomSp;
            $sheet->setCellValue("A{$r}", $noSp++);
            $sheet->setCellValue("B{$r}", $s->tanggal_transaksi ? $s->tanggal_transaksi->format('d/m/Y') : '-');
            $sheet->setCellValue("C{$r}", $s->nomor_bukti_pi ?? '-');
            $sheet->setCellValue("D{$r}", $s->keterangan);
            $sheet->setCellValue("E{$r}", $nomSp);
            $sheet->setCellValue("F{$r}", $ytdSp);
            $sheet->setCellValue("G{$r}", $ytdSp);
            $sheet->setCellValue("H{$r}", 'Terpasang');

            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');

            $bg = $noSp % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
            $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(21);
            $totalSp += $nomSp;
            $r++;
        }

        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->setCellValue("A{$r}", 'SUBTOTAL BON SUKU CADANG (SPAREPART)');
        $sheet->setCellValue("E{$r}", $totalSp);
        $sheet->setCellValue("F{$r}", $totalSp);
        $sheet->setCellValue("G{$r}", $totalSp);
        $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:H{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0F2FE');
        $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('94A3B8'));
        $sheet->getRowDimension($r)->setRowHeight(23);

        // TABEL 3: Bon Dana Cash / Kasbon Kru
        $r += 2;
        $sheet->mergeCells("A{$r}:H{$r}");
        $sheet->setCellValue("A{$r}", '3. BON DANA CASH / KASBON KRU LAPANGAN');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getRowDimension($r)->setRowHeight(24);

        $r++;
        $headersKb = ['A' . $r => 'NO', 'B' . $r => 'TANGGAL', 'C' . $r => 'NAMA KRU', 'D' . $r => 'KEPERLUAN KASBON', 'E' . $r => 'JUMLAH (Rp)', 'F' . $r => 'YTD (Rp)', 'G' . $r => 'ALL YTD (Rp)', 'H' . $r => 'STATUS'];
        foreach ($headersKb as $cell => $t) {
            $sheet->setCellValue($cell, $t);
        }
        $sheet->getStyle("A{$r}:H{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$r}:H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($r)->setRowHeight(24);

        $r++;
        $noKb = 1;
        $totalKb = 0.0;
        $ytdKb = 0.0;

        foreach ($kasbon as $k) {
            $nomKb = (float) $k->jumlah_kasbon;
            $ytdKb += $nomKb;
            $sheet->setCellValue("A{$r}", $noKb++);
            $sheet->setCellValue("B{$r}", $k->tanggal_kasbon ? $k->tanggal_kasbon->format('d/m/Y') : '-');
            $sheet->setCellValue("C{$r}", $k->nama_personil . ' (' . strtoupper($k->peran_kru) . ')');
            $sheet->setCellValue("D{$r}", $k->keterangan ?? 'Kasbon Operasional');
            $sheet->setCellValue("E{$r}", $nomKb);
            $sheet->setCellValue("F{$r}", $ytdKb);
            $sheet->setCellValue("G{$r}", $ytdKb);
            $sheet->setCellValue("H{$r}", $k->status_lunas ? 'Lunas' : 'Dipotong Gaji');

            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');

            $bg = $noKb % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
            $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(21);
            $totalKb += $nomKb;
            $r++;
        }

        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->setCellValue("A{$r}", 'SUBTOTAL BON DANA CASH / KASBON KRU');
        $sheet->setCellValue("E{$r}", $totalKb);
        $sheet->setCellValue("F{$r}", $totalKb);
        $sheet->setCellValue("G{$r}", $totalKb);
        $sheet->getStyle("E{$r}:G{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:H{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:H{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7');
        $sheet->getStyle("A{$r}:H{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('94A3B8'));
        $sheet->getRowDimension($r)->setRowHeight(23);

        // Lebar kolom terukur
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(38);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(20);
    }

    /**
     * SHEET 6: Ransum & Kantin Distrik.
     */
    private function susunSheetRansumKantin(
        Worksheet $sheet,
        ?Tim $tim,
        string $periode,
        $pengeluaran,
        string $pilot,
        string $helper1,
        string $helper2
    ): void {
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'REKAPITULASI BON RANSUM & KANTIN DISTRIK');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new Color('0B2E24'));

        // TABEL 1: Bon Ransum
        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', '1. BON RANSUM & KONSUMSI TIM DRONE');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle('A3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF16A34A');
        $sheet->getRowDimension(3)->setRowHeight(24);

        $headers = ['A4' => 'NO', 'B4' => 'TANGGAL', 'C4' => 'KETERANGAN BELANJA RANSUM', 'D4' => 'NOMINAL (Rp)', 'E4' => 'PEMBAGIAN', 'F4' => 'BEBAN / KRU'];
        foreach ($headers as $cell => $t) {
            $sheet->setCellValue($cell, $t);
        }
        $sheet->getStyle('A4:F4')->getFont()->setBold(true);
        $sheet->getStyle('A4:F4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle('A4:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(24);

        $ransumItems = $pengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::RANSUM_KANTIN);
        $r = 5;
        $no = 1;
        $totalRansum = 0.0;

        foreach ($ransumItems as $item) {
            $nom = (float) $item->nominal;
            $sheet->setCellValue("A{$r}", $no++);
            $sheet->setCellValue("B{$r}", $item->tanggal_transaksi ? $item->tanggal_transaksi->format('d/m/Y') : '-');
            $sheet->setCellValue("C{$r}", $item->keterangan);
            $sheet->setCellValue("D{$r}", $nom);
            $sheet->setCellValue("E{$r}", 'Dibagi 3 Kru');
            $sheet->setCellValue("F{$r}", 'Pilot & 2 Helper');

            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('#,##0');

            $bg = $no % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
            $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(21);
            $totalRansum += $nom;
            $r++;
        }

        $sheet->mergeCells("A{$r}:C{$r}");
        $sheet->setCellValue("A{$r}", 'TOTAL BON RANSUM BULAN BERJALAN');
        $sheet->setCellValue("D{$r}", $totalRansum);
        $sheet->setCellValue("E{$r}", '100%');
        $sheet->setCellValue("F{$r}", '-');
        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('94A3B8'));
        $sheet->getRowDimension($r)->setRowHeight(24);

        // TABEL 2: Pembagian Ransum per Personil
        $r += 2;
        $sheet->mergeCells("A{$r}:F{$r}");
        $sheet->setCellValue("A{$r}", '2. PEMBAGIAN BEBAN RANSUM PER PERSONIL (DIPOTONG DI SLIP GAJI)');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getRowDimension($r)->setRowHeight(24);

        $r++;
        $sheet->setCellValue("A{$r}", 'NO');
        $sheet->mergeCells("B{$r}:C{$r}");
        $sheet->setCellValue("B{$r}", 'NAMA KRU LAPANGAN');
        $sheet->setCellValue("D{$r}", 'POTONGAN (1/3 BAGIAN)');
        $sheet->mergeCells("E{$r}:F{$r}");
        $sheet->setCellValue("E{$r}", 'STATUS POTONGAN');
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$r}:F{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($r)->setRowHeight(24);

        $ransumPerKru = $totalRansum > 0 ? $totalRansum / 3 : 0;
        $kruList = [
            ['1', $pilot . ' (Pilot)', $ransumPerKru],
            ['2', $helper1 . ' (Helper 1)', $ransumPerKru],
            ['3', $helper2 . ' (Helper 2)', $ransumPerKru],
        ];

        $r++;
        foreach ($kruList as $k) {
            $sheet->setCellValue("A{$r}", $k[0]);
            $sheet->mergeCells("B{$r}:C{$r}");
            $sheet->setCellValue("B{$r}", $k[1]);
            $sheet->setCellValue("D{$r}", $k[2]);
            $sheet->mergeCells("E{$r}:F{$r}");
            $sheet->setCellValue("E{$r}", 'Dipotong dari Slip Gaji');

            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(21);
            $r++;
        }

        $sheet->mergeCells("A{$r}:C{$r}");
        $sheet->setCellValue("A{$r}", 'TOTAL POTONGAN RANSUM KRU');
        $sheet->setCellValue("D{$r}", $ransumPerKru * 3);
        $sheet->mergeCells("E{$r}:F{$r}");
        $sheet->setCellValue("E{$r}", 'Tercatat di Slip Gaji');
        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('94A3B8'));
        $sheet->getRowDimension($r)->setRowHeight(24);

        // Lebar kolom terukur
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(22);
    }

    /**
     * SHEET 7: Berita Acara Pembayaran (BAP).
     * Mengikuti format resmi 2-blok PDF halaman 7:
     *   Blok 1: Rincian Bon Operasional (Rp 26.944.001)
     *   Blok 2: Pembayaran Kantor (Rp 19.972.501)
     *   Rekapitulasi Akhir & Profit Bersih Ditransfer (Rp 10.403.498)
     */
    private function susunSheetBapPembayaran(
        Worksheet $sheet,
        ?Tim $tim,
        string $periode,
        float $hasilBersih80,
        float $operasional,
        float $sparepart,
        float $kasbonKru,
        float $gajiPilot,
        float $gajiHelper1,
        float $gajiHelper2,
        float $honorAdm,
        string $pilot,
        string $helper1,
        string $helper2,
        float $enterteint,
        float $ransum,
        float $rentalMobil,
        float $upahLangsir,
        float $bonPribadi,
        float $profitDitransfer
    ): void {
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        $unitLabel = ($tim?->drone?->nomor_drone ?? 'DRONE BERTUAH 701') . ' - ' . ($tim?->drone?->tipe_drone ?? 'T50');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'BERITA ACARA PEMBAYARAN (BAP) & PROFIT UNIT');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('0B2E24'));

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'UNIT: ' . $unitLabel . ' | PERIODE: ' . $periode . ' — FORMAT RESMI 2-BLOK');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('5B6C64'))->setBold(true);

        // BLOK 1: RINCIAN BON OPERASIONAL
        $sheet->mergeCells('A4:F4');
        $sheet->setCellValue('A4', 'BLOK 1: RINCIAN BON OPERASIONAL UNIT DRONE BERTUAH');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle('A4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F3D2F');
        $sheet->getRowDimension(4)->setRowHeight(25);

        $rowsRincian = [
            ['1. Operasional Lapangan (Solar BBM, Belanja Logistik)', $operasional],
            ['2. Bon Suku Cadang / Sparepart (PI Resmi)', $sparepart],
            ['3. Bon Dana Cash / Kasbon Kru Lapangan', $kasbonKru],
        ];

        $r = 5;
        $totalRincian = 0.0;
        foreach ($rowsRincian as $idx => $item) {
            $sheet->mergeCells("A{$r}:C{$r}");
            $sheet->setCellValue("A{$r}", $item[0]);
            $sheet->setCellValue("D{$r}", $item[1]);
            $sheet->setCellValue("E{$r}", 'Rp');
            $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $bg = $idx % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
            $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(21);
            $totalRincian += $item[1];
            $r++;
        }

        // Total Rincian Row (Sejajar di Kolom F)
        $sheet->mergeCells("A{$r}:E{$r}");
        $sheet->setCellValue("A{$r}", 'TOTAL RINCIAN BON OPERASIONAL');
        $sheet->setCellValue("F{$r}", $totalRincian);
        $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true)->setColor(new Color('991B1B'));
        $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('94A3B8'));
        $sheet->getRowDimension($r)->setRowHeight(24);
        $r += 2;

        // BLOK 2: PEMBAYARAN KANTOR
        $sheet->mergeCells("A{$r}:F{$r}");
        $sheet->setCellValue("A{$r}", 'BLOK 2: PEMBAYARAN LANGSUNG KANTOR (TRANSFER KRU & VENDOR)');
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getRowDimension($r)->setRowHeight(25);
        $r++;

        $totalGajiTim = $gajiPilot + $gajiHelper1 + $gajiHelper2 + $honorAdm;
        $gajiItems = [
            ['Gaji Tim :', null],
            ['Pilot 701 (' . strtoupper($pilot) . ')', $gajiPilot],
            ['Helper 701 (' . strtoupper($helper1) . ')', $gajiHelper1],
            ['Helper 701 (' . strtoupper($helper2) . ')', $gajiHelper2],
            ['ADM TIARA / TIM', $honorAdm],
        ];

        foreach ($gajiItems as $idx => $g) {
            $sheet->mergeCells("B{$r}:C{$r}");
            $sheet->setCellValue("B{$r}", $g[0]);
            if ($g[1] !== null) {
                $sheet->setCellValue("D{$r}", $g[1]);
                $sheet->setCellValue("E{$r}", 'Rp');
                $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            } else {
                $sheet->getStyle("B{$r}")->getFont()->setBold(true);
            }

            if ($idx === count($gajiItems) - 1) {
                $sheet->setCellValue("F{$r}", $totalGajiTim);
                $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("F{$r}")->getFont()->setBold(true);
            }

            $bg = $idx % 2 === 1 ? 'FFF8FAFC' : 'FFFFFFFF';
            $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
            $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
            $sheet->getRowDimension($r)->setRowHeight(21);
            $r++;
        }

        // Enterteint
        $sheet->mergeCells("B{$r}:C{$r}");
        $sheet->setCellValue("B{$r}", 'Enterteint Lapangan (' . $periode . ')');
        $sheet->setCellValue("D{$r}", $enterteint);
        $sheet->setCellValue("E{$r}", 'Rp');
        $sheet->setCellValue("F{$r}", $enterteint);
        $sheet->getStyle("D{$r}:F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(21);
        $r++;

        // Bon Ransum
        $sheet->mergeCells("B{$r}:C{$r}");
        $sheet->setCellValue("B{$r}", 'Bon Ransum & Logistik Tim');
        $sheet->setCellValue("D{$r}", $ransum);
        $sheet->setCellValue("E{$r}", 'Rp');
        $sheet->setCellValue("F{$r}", $ransum);
        $sheet->getStyle("D{$r}:F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(21);
        $r++;

        // Rental & Upah Langsir
        $totalRentalLangsir = $rentalMobil + $upahLangsir;
        $sheet->mergeCells("B{$r}:C{$r}");
        $sheet->setCellValue("B{$r}", 'Rental Mobil & Upah Langsiran');
        $sheet->setCellValue("D{$r}", $totalRentalLangsir);
        $sheet->setCellValue("E{$r}", 'Rp');
        $sheet->setCellValue("F{$r}", $totalRentalLangsir);
        $sheet->getStyle("D{$r}:F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(21);
        $r++;

        // Total Pembayaran Row (Sejajar di Kolom F)
        $totalPembayaran = $totalGajiTim + $enterteint + $ransum + $totalRentalLangsir;
        $sheet->mergeCells("A{$r}:E{$r}");
        $sheet->setCellValue("A{$r}", 'TOTAL PEMBAYARAN KANTOR');
        $sheet->setCellValue("F{$r}", $totalPembayaran);
        $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true)->setColor(new Color('1E3A8A'));
        $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('94A3B8'));
        $sheet->getRowDimension($r)->setRowHeight(24);
        $r += 2;

        // BLOK 3: REKAPITULASI AKHIR & PROFIT BERSIH (Nilai sejajar di Kolom F)
        $sheet->mergeCells("A{$r}:E{$r}");
        $sheet->setCellValue("A{$r}", 'HASIL BERSIH UNIT (80% NET OPERASIONAL)');
        $sheet->setCellValue("F{$r}", $hasilBersih80);
        $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(22);
        $r++;

        $sheet->mergeCells("A{$r}:E{$r}");
        $sheet->setCellValue("A{$r}", 'Operasional & Bon Dana Cash (Blok 1)');
        $sheet->setCellValue("F{$r}", -$totalRincian);
        $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(21);
        $r++;

        $sheet->mergeCells("A{$r}:E{$r}");
        $sheet->setCellValue("A{$r}", 'Bayaran Kantor: Gaji, Enterteint, Ransum, Rental (Blok 2)');
        $sheet->setCellValue("F{$r}", -$totalPembayaran);
        $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(21);
        $r++;

        $sheet->mergeCells("A{$r}:E{$r}");
        $sheet->setCellValue("A{$r}", 'Bon Lainnya (Bon Pribadi Owner)');
        $sheet->setCellValue("F{$r}", -$bonPribadi);
        $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('CBD5E1'));
        $sheet->getRowDimension($r)->setRowHeight(21);
        $r++;

        $sheet->mergeCells("A{$r}:E{$r}");
        $sheet->setCellValue("A{$r}", 'PROFIT BERSIH DRONE BERTUAH (DITRANSFER)');
        $sheet->setCellValue("F{$r}", $profitDitransfer);
        $sheet->getStyle("F{$r}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true)->setSize(11)->setColor(new Color('065F46'));
        $sheet->getStyle("A{$r}:F{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');
        $sheet->getStyle("A{$r}:F{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('059669'));
        $sheet->getRowDimension($r)->setRowHeight(25);
        $r += 2;

        // Tanda Tangan Para Pihak
        $sheet->setCellValue("B{$r}", 'Made by,');
        $sheet->setCellValue("E{$r}", 'Approve by,');
        $sheet->getStyle("B{$r}:E{$r}")->getFont()->setBold(true)->setColor(new Color('64748B'));
        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r += 4;
        $sheet->setCellValue("B{$r}", 'Tiara');
        $sheet->setCellValue("E{$r}", 'Wahyudi');
        $sheet->getStyle("B{$r}:E{$r}")->getFont()->setBold(true);
        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r++;
        $sheet->setCellValue("B{$r}", 'Drone Bertuah / Admin');
        $sheet->setCellValue("E{$r}", 'Owner Unit');
        $sheet->getStyle("B{$r}:E{$r}")->getFont()->setSize(9)->setColor(new Color('64748B'));
        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Lebar kolom terukur
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(34);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(8);
        $sheet->getColumnDimension('F')->setWidth(24);
    }
}
