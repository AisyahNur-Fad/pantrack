<?php

namespace App\Http\Controllers;

use App\Models\DetailRealisasi;
use App\Models\DetailSpk;
use App\Models\Distrik;
use App\Models\Drone;
use App\Models\JenisBbm;
use App\Models\JenisPekerjaan;
use App\Models\LampiranRealisasi;
use App\Models\LaporanRealisasi;
use App\Models\Pengguna;
use App\Models\Petak;
use App\Models\Spk;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use App\Services\RiwayatAktivitasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Pengelolaan Laporan Realisasi Pekerjaan Petak Drone (FR-08 s/d FR-12).
 *
 * Aturan penting:
 *  - Pekerja langsung mengetikkan nomor SPK pada formulir laporan realisasi.
 *  - Input personil tim lapangan meliputi Pilot, Co-Pilot, dan Helper (nama bebas).
 *  - Formulir mendukung banyak baris petak dalam satu laporan (FR-09).
 *  - Luas realisasi tersimpan pada `detail_realisasi` dan tidak disamakan dengan target SPK (BR-05).
 *  - Pemeriksaan dan persetujuan dilakukan oleh Kepala Lapangan (FR-11).
 */
class LaporanRealisasiController extends Controller
{
    public function __construct(private readonly RiwayatAktivitasService $aktivitas) {}

    public function index(Request $request): View
    {
        $pengguna = Auth::user();
        $idTimBoleh = $pengguna->idTimYangDiakses();

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $timId = $request->input('tim_id');
        $distrikId = $request->input('distrik_id');
        $status = $request->input('status');
        $cari = trim((string) $request->input('cari'));

        $query = LaporanRealisasi::query()
            ->with(['tim', 'distrik', 'spk.jenisPekerjaan', 'penginput', 'pemeriksa', 'detail.petak'])
            ->withCount('detail')
            ->when($idTimBoleh !== null, fn ($q) => $q->whereIn('tim_id', $idTimBoleh))
            ->when($bulan, fn ($q) => $q->where('periode_bulan', $bulan))
            ->when($tahun, fn ($q) => $q->where('periode_tahun', $tahun))
            ->when($timId, fn ($q) => $q->where('tim_id', $timId))
            ->when($distrikId, fn ($q) => $q->where('distrik_id', $distrikId))
            ->when($status, fn ($q) => $q->where('status_laporan', $status))
            ->when($cari, fn ($q) => $q->where(fn ($sub) => $sub->where('nomor_laporan', 'like', "%{$cari}%")
                ->orWhere('nama_pilot', 'like', "%{$cari}%")
                ->orWhere('nama_tim_manual', 'like', "%{$cari}%")
                ->orWhereHas('spk', fn ($s) => $s->where('nomor_spk', 'like', "%{$cari}%"))
                ->orWhereHas('distrik', fn ($d) => $d->where('nama_distrik', 'like', "%{$cari}%"))));

        $daftarLaporan = $query->latest('id')->paginate(15)->withQueryString();

        $daftarTim = Tim::when($idTimBoleh !== null, fn ($q) => $q->whereIn('id', $idTimBoleh))
            ->where('status_tim', 'aktif')
            ->orderBy('nama_tim')
            ->get();

        $daftarDistrik = Distrik::where('aktif', true)->orderBy('nama_distrik')->get();

        return view('realisasi.index', compact(
            'daftarLaporan', 'daftarTim', 'daftarDistrik',
            'bulan', 'tahun', 'timId', 'distrikId', 'status', 'cari'
        ));
    }

    public function create(): View
    {
        $pengguna = $this->pastikanPekerjaLapangan();
        $idTimBoleh = $pengguna->idTimYangDiakses();

        $daftarTim = Tim::when($idTimBoleh !== null, fn ($q) => $q->whereIn('id', $idTimBoleh))
            ->where('status_tim', 'aktif')
            ->with(['distrik', 'anggota', 'drone'])
            ->orderBy('nama_tim')
            ->get();

        if ($daftarTim->isEmpty()) {
            abort(403, 'Anda belum terdaftar dalam tim lapangan aktif manapun.');
        }

        $daftarDistrik = Distrik::where('aktif', true)->orderBy('nama_distrik')->get();
        // Hanya pekerjaan pokok / aplikasi drone (tipe = utama) yang muncul di dropdown, karena mapping adalah add-on di kolom kanan
        $daftarPekerjaan = JenisPekerjaan::utama()->where('aktif', true)->orderBy('nama_pekerjaan')->get();
        $daftarPetak = Petak::where('status_petak', 'aktif')->with('distrik')->orderBy('kode_petak')->get();
        $daftarJenisBbm = JenisBbm::where('aktif', true)->orderBy('nama_bbm')->get();

        // SPK yang sudah disetujui/diterbitkan oleh Kepala Lapangan dan belum selesai
        $daftarSpk = Spk::where('status_spk', Spk::DISETUJUI)
            ->when($idTimBoleh !== null, function ($q) use ($idTimBoleh) {
                $q->where(fn ($sub) => $sub->whereNull('tim_id')->orWhereIn('tim_id', $idTimBoleh));
            })
            ->with(['distrik', 'tim', 'jenisPekerjaan', 'detail.petak'])
            ->latest('id')
            ->get();

        $statusPekerjaan = DetailRealisasi::statusPekerjaan();

        return view('realisasi.create', compact(
            'daftarTim', 'daftarDistrik', 'daftarPekerjaan', 'daftarPetak', 'daftarSpk', 'daftarJenisBbm', 'statusPekerjaan'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $pengguna = $this->pastikanPekerjaLapangan();
        $idTimBoleh = $pengguna->idTimYangDiakses();

        $data = $request->validate([
            'nomor_laporan' => ['nullable', 'string', 'max:60'],
            'tim_id' => [
                'required',
                'exists:tim,id',
                function ($attribute, $value, $fail) use ($idTimBoleh) {
                    if ($idTimBoleh !== null && ! in_array((int) $value, $idTimBoleh, true)) {
                        $fail('Anda tidak memiliki hak akses untuk menginput atas nama tim ini.');
                        return;
                    }

                    $timDipilih = Tim::with('drone')->find($value);
                    if ($timDipilih && $timDipilih->drone && in_array($timDipilih->drone->status_operasional, [Drone::STATUS_PERAWATAN, Drone::STATUS_TIDAK_BEROPERASI], true)) {
                        $statusStr = $timDipilih->drone->status_operasional === Drone::STATUS_PERAWATAN ? 'Perawatan' : 'Tidak Beroperasi';
                        $catatan = $timDipilih->drone->catatan_perawatan ? " ({$timDipilih->drone->catatan_perawatan})" : '';
                        $fail("Unit Drone {$timDipilih->drone->nomor_drone} milik tim ini sedang dalam status {$statusStr}{$catatan} dan tidak dapat digunakan untuk penerbangan lapangan.");
                    }
                },
            ],
            'nama_tim_manual' => ['nullable', 'string', 'max:150'],
            'nama_pilot' => ['required', 'string', 'max:150'],
            'nama_copilot' => ['nullable', 'string', 'max:150'],
            'nama_helper' => ['nullable'],
            'nama_helper.*' => ['nullable', 'string', 'max:150'],
            'spk_id' => ['required', 'exists:spk,id'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'distrik_id' => ['required', 'exists:distrik,id'],
            'ajukan' => ['nullable', 'boolean'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'petak' => ['required', 'array', 'min:1'],
            'petak.*.tanggal_pekerjaan' => ['nullable', 'date'],
            'petak.*.petak_id' => ['nullable', 'exists:petak,id'],
            'petak.*.kode_petak_manual' => ['nullable', 'string', 'max:80'],
            'petak.*.jenis_pekerjaan_id' => ['required', 'exists:jenis_pekerjaan,id'],
            'petak.*.luas_spk_ha' => ['nullable', 'numeric', 'min:0'],
            'petak.*.luas_realisasi_ha' => ['required', 'numeric', 'min:0'],
            'petak.*.perlu_mapping' => ['nullable', 'boolean'],
            'petak.*.status_pekerjaan' => ['nullable', 'string'],
            'petak.*.keterangan_kendala' => ['nullable', 'string', 'max:500'],
            'ada_kendala' => ['nullable', 'in:0,1'],
            'catatan_kendala' => ['nullable', 'string', 'max:1000', 'required_if:ada_kendala,1'],
            'ada_bbm' => ['nullable', 'in:0,1'],
            'keterangan_tanpa_bbm' => ['nullable', 'string', 'max:500', 'required_if:ada_bbm,0'],
            'catat_bbm' => ['nullable', 'boolean'],
            'bbm_items' => ['nullable', 'array'],
            'bbm_items.*.jenis_bbm_id' => ['nullable', 'exists:jenis_bbm,id'],
            'bbm_items.*.keperluan' => ['nullable', 'string', 'max:100'],
            'bbm_items.*.jumlah_liter' => ['nullable', 'numeric', 'min:0.01'],
            'bbm_items.*.keterangan' => ['nullable', 'string', 'max:255'],
            'bbm_jenis_bbm_id' => ['nullable', 'exists:jenis_bbm,id'],
            'bbm_jumlah_liter' => ['nullable', 'numeric', 'min:0.01'],
            'bbm_keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'spk_id.required' => 'Wajib memilih SPK yang diterbitkan oleh Kepala Lapangan.',
            'spk_id.exists' => 'SPK yang dipilih tidak ditemukan dalam sistem.',
            'nama_pilot.required' => 'Nama Pilot wajib diisi untuk pelaporan tim di lapangan.',
            'petak.required' => 'Minimal satu baris pekerjaan petak harus diisi.',
            'petak.*.jenis_pekerjaan_id.required' => 'Pilih jenis pekerjaan.',
            'petak.*.luas_realisasi_ha.required' => 'Isi luas realisasi (Ha).',
            'bbm_jenis_bbm_id.required_if' => 'Pilih jenis BBM jika mencatat konsumsi bahan bakar.',
            'bbm_jumlah_liter.required_if' => 'Isi jumlah liter BBM yang digunakan.',
        ]);

        $spk = Spk::with(['distrik', 'detail'])->findOrFail($data['spk_id']);
        if ($spk->status_spk !== Spk::DISETUJUI) {
            return back()->withErrors(['spk_id' => 'SPK yang dipilih belum disetujui atau sudah selesai/tidak aktif.'])->withInput();
        }

        // Tanggal wajib harian yang dikunci dari sisi server (tidak dapat dimanipulasi klien)
        $tanggalHariIni = now()->toDateString();

        // Pengecekan input harian unik per SPK dan tanggal hari ini
        $sudahAdaLaporanHariIni = LaporanRealisasi::where('spk_id', $spk->id)
            ->where(function ($q) use ($tanggalHariIni) {
                $q->whereDate('tanggal_input', $tanggalHariIni)
                  ->orWhere(function ($sub) use ($tanggalHariIni) {
                      $sub->whereNull('tanggal_input')
                          ->whereHas('detail', fn ($dq) => $dq->whereDate('tanggal_pekerjaan', $tanggalHariIni));
                  });
            })
            ->where('status_laporan', '!=', LaporanRealisasi::DITOLAK)
            ->exists();

        if ($sudahAdaLaporanHariIni) {
            return back()->withErrors([
                'spk_id' => "Laporan untuk SPK {$spk->nomor_spk} pada hari ini ({$tanggalHariIni}) sudah pernah dibuat. Silakan periksa daftar laporan.",
            ])->withInput();
        }

        // Pastikan distrik laporan sinkron dengan distrik SPK
        if ((int) $data['distrik_id'] !== (int) $spk->distrik_id) {
            return back()->withErrors(['distrik_id' => 'Distrik laporan harus sesuai dengan distrik SPK terpilih (' . ($spk->distrik?->nama_distrik ?? '') . ').'])->withInput();
        }

        // Pastikan setiap petak yang dipilih terdaftar pada target detail_spk dari SPK terpilih (1 SPK = 1 Petak)
        $spk->loadMissing('detail');
        $targetDetailSpk = $spk->detail->first();
        $targetPetakId = $targetDetailSpk?->petak_id;
        $targetPetakIds = $spk->detail->pluck('petak_id')->filter()->all();

        foreach ($data['petak'] as $idx => &$row) {
            if (empty($row['petak_id']) && $targetPetakId) {
                $row['petak_id'] = $targetPetakId;
            } elseif (! empty($row['petak_id']) && ! empty($targetPetakIds)) {
                if (! in_array((int) $row['petak_id'], $targetPetakIds, true)) {
                    return back()->withErrors(["petak.{$idx}.petak_id" => "Petak pada baris ke-" . ($idx + 1) . " bukan bagian dari target SPK terpilih ({$spk->nomor_spk})."])->withInput();
                }
            }

            if (empty($row['jenis_pekerjaan_id']) && $spk->jenis_pekerjaan_id) {
                $row['jenis_pekerjaan_id'] = $spk->jenis_pekerjaan_id;
            }
        }
        unset($row);

        // Evaluasi kondisi kendala & konsumsi BBM
        $adaKendala = $request->has('ada_kendala')
            ? $request->boolean('ada_kendala')
            : (! empty($data['catatan_kendala']) || collect($data['petak'])->contains(fn ($p) => ($p['status_pekerjaan'] ?? '') === 'terkendala'));

        $adaBbm = $request->has('ada_bbm')
            ? $request->boolean('ada_bbm')
            : ($request->boolean('catat_bbm') || ! empty($data['bbm_items']) || ! empty($data['bbm_jumlah_liter']));

        if ($adaBbm) {
            $rawBbm = $request->input('bbm_items', []);
            $adaItemValid = collect($rawBbm)->contains(fn ($it) => (float)($it['jumlah_liter'] ?? 0) > 0 && ! empty($it['jenis_bbm_id']));
            if (! $adaItemValid && empty($data['bbm_jumlah_liter'])) {
                return back()->withErrors(['bbm_items' => 'Karena memilih "Ada Penggunaan BBM", wajib mengisi minimal satu rincian BBM dengan jumlah liter lebih dari 0.'])->withInput();
            }
        }

        // Jika operasional lancar (tanpa kendala), luas realisasi tidak boleh 0.00 Ha
        $totalLuasInput = collect($data['petak'])->sum(fn ($p) => (float) ($p['luas_realisasi_ha'] ?? 0));
        if (! $adaKendala && $totalLuasInput <= 0) {
            return back()->withErrors(['petak.0.luas_realisasi_ha' => 'Luas realisasi tidak boleh 0.00 Ha jika operasional berjalan lancar tanpa kendala. Jika pekerjaan batal terbang atau terhenti, silakan pilih "Terdapat Kendala Lapangan".'])->withInput();
        }

        $statusLaporan = $request->boolean('ajukan')
            ? LaporanRealisasi::MENUNGGU_PEMERIKSAAN
            : LaporanRealisasi::DRAF;

        DB::transaction(function () use ($data, $spk, $statusLaporan, $tanggalHariIni, $adaKendala, $adaBbm, $request, &$laporan) {
            $namaHelper = $this->normalisasiNamaHelper($request->input('nama_helper'));

            $laporan = LaporanRealisasi::create([
                'nomor_laporan' => $data['nomor_laporan'] ?? null,
                'tim_id' => $data['tim_id'],
                'nama_tim_manual' => $data['nama_tim_manual'] ?? null,
                'nama_pilot' => $data['nama_pilot'],
                'nama_copilot' => $data['nama_copilot'] ?? null,
                'nama_helper' => $namaHelper,
                'spk_id' => $spk->id,
                'tanggal_input' => $tanggalHariIni,
                'periode_bulan' => $data['periode_bulan'],
                'periode_tahun' => $data['periode_tahun'],
                'distrik_id' => $data['distrik_id'],
                'diinput_oleh' => Auth::id(),
                'status_laporan' => $statusLaporan,
                'ada_kendala' => $adaKendala,
                'catatan_kendala' => $adaKendala ? ($data['catatan_kendala'] ?? null) : null,
                'ada_bbm' => $adaBbm,
                'keterangan_tanpa_bbm' => ! $adaBbm ? ($data['keterangan_tanpa_bbm'] ?? null) : null,
            ]);

            $spk->loadMissing('detail');
            $detailSpkByPetakId = $spk->detail->keyBy('petak_id');

            foreach ($data['petak'] as $row) {
                $tarifAktif = $this->cariTarifAktif((int) $row['jenis_pekerjaan_id'], $tanggalHariIni);

                $petakId = ! empty($row['petak_id']) ? (int) $row['petak_id'] : null;
                $spkItem = $petakId ? ($detailSpkByPetakId[$petakId] ?? null) : null;
                $perluMapping = isset($row['perlu_mapping'])
                    ? filter_var($row['perlu_mapping'], FILTER_VALIDATE_BOOLEAN)
                    : ($spkItem?->perlu_mapping ?? false);

                $tarifMapping = null;
                if ($perluMapping) {
                    $tarifMapping = $spkItem?->tarif_mapping_per_ha ?? $this->cariTarifMappingAktif($tanggalHariIni);
                }

                $ketKendala = ! empty($row['keterangan_kendala'])
                    ? $row['keterangan_kendala']
                    : ($adaKendala ? ($data['catatan_kendala'] ?? null) : null);

                $statusPekerjaan = ! empty($row['status_pekerjaan'])
                    ? $row['status_pekerjaan']
                    : ($adaKendala ? 'terkendala' : 'selesai');

                if ($adaKendala && ($statusPekerjaan === 'selesai' || (float)($row['luas_realisasi_ha'] ?? 0) == 0)) {
                    $statusPekerjaan = 'terkendala';
                }

                DetailRealisasi::create([
                    'laporan_realisasi_id' => $laporan->id,
                    'tanggal_pekerjaan' => $tanggalHariIni,
                    'petak_id' => $petakId,
                    'kode_petak_manual' => empty($petakId) ? ($row['kode_petak_manual'] ?? null) : null,
                    'jenis_pekerjaan_id' => $row['jenis_pekerjaan_id'],
                    'luas_realisasi_ha' => $row['luas_realisasi_ha'],
                    'perlu_mapping' => $perluMapping,
                    'status_pekerjaan' => $statusPekerjaan,
                    'keterangan_kendala' => $ketKendala,
                    'tarif_per_ha' => $tarifAktif,
                    'tarif_mapping_per_ha' => $tarifMapping,
                ]);
            }

            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $namaAsli = $file->getClientOriginalName();
                    $path = $file->store('lampiran/realisasi', 'public');

                    LampiranRealisasi::create([
                        'laporan_realisasi_id' => $laporan->id,
                        'nama_berkas_asli' => $namaAsli,
                        'lokasi_berkas' => $path,
                        'jenis_lampiran' => 'Foto / Bukti Pekerjaan',
                        'diunggah_oleh' => Auth::id(),
                    ]);
                }
            }

            // Integrasi Multi-Input Repeater Konsumsi BBM (Wajib jika ada_bbm == true)
            if ($adaBbm) {
                $rawBbmItems = $request->input('bbm_items', []);

                if (empty($rawBbmItems) && ! empty($data['bbm_jumlah_liter']) && ! empty($data['bbm_jenis_bbm_id'])) {
                    $rawBbmItems = [[
                        'jenis_bbm_id' => $data['bbm_jenis_bbm_id'],
                        'keperluan' => 'operasional_drone',
                        'jumlah_liter' => $data['bbm_jumlah_liter'],
                        'keterangan' => $data['bbm_keterangan'] ?? null,
                    ]];
                }

                foreach ($rawBbmItems as $item) {
                    $liter = (float) ($item['jumlah_liter'] ?? 0);
                    $jenisBbmId = (int) ($item['jenis_bbm_id'] ?? 0);
                    if ($liter <= 0 || ! $jenisBbmId) {
                        continue;
                    }

                    $masterBbm = JenisBbm::find($jenisBbmId);
                    $hargaSatuan = $masterBbm?->harga_per_liter ?? 0;
                    $totalBiaya = round($liter * $hargaSatuan, 2);

                    TransaksiBbm::create([
                        'nomor_transaksi' => 'BBM/' . now()->format('Y/m/') . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT),
                        'tim_id' => $data['tim_id'],
                        'jenis_bbm_id' => $jenisBbmId,
                        'jenis_transaksi' => TransaksiBbm::PENGGUNAAN,
                        'tanggal_transaksi' => $data['petak'][0]['tanggal_pekerjaan'] ?? now()->toDateString(),
                        'jumlah_liter' => $liter,
                        'harga_per_liter' => $hargaSatuan > 0 ? $hargaSatuan : null,
                        'total_biaya' => $totalBiaya > 0 ? $totalBiaya : null,
                        'keperluan' => ! empty($item['keperluan']) ? $item['keperluan'] : 'operasional_drone',
                        'keterangan' => ! empty($item['keterangan']) ? $item['keterangan'] : "Konsumsi BBM operasional untuk {$laporan->nomor_laporan}",
                        'status_verifikasi' => TransaksiBbm::MENUNGGU_PEMERIKSAAN,
                        'laporan_realisasi_id' => $laporan->id,
                        'diinput_oleh' => Auth::id(),
                    ]);
                }
            }
        });

        $this->aktivitas->catat(
            $statusLaporan === LaporanRealisasi::MENUNGGU_PEMERIKSAAN ? 'Mengajukan Laporan Realisasi' : 'Menyimpan Draf Realisasi',
            $laporan,
            "Nomor Laporan: {$laporan->nomor_laporan}"
        );

        $pesan = $statusLaporan === LaporanRealisasi::MENUNGGU_PEMERIKSAAN
            ? "Laporan {$laporan->nomor_laporan} berhasil diajukan dan menunggu pemeriksaan Kepala Lapangan."
            : "Laporan {$laporan->nomor_laporan} berhasil disimpan sebagai draf.";

        return redirect()->route('realisasi.show', $laporan)->with('sukses', $pesan);
    }

    public function show(LaporanRealisasi $realisasi): View
    {
        $this->pastikanAksesTim($realisasi->tim_id);

        $realisasi->load([
            'tim.drone', 'distrik', 'spk', 'penginput', 'pemeriksa',
            'detail.petak', 'detail.jenisPekerjaan', 'lampiran', 'transaksiBbm.jenisBbm',
        ]);

        $totalLuas = (float) $realisasi->detail->sum('luas_realisasi_ha');
        $totalLuasMapping = (float) $realisasi->detail->where('perlu_mapping', true)->sum('luas_realisasi_ha');

        $totalNilaiPokok = 0.0;
        $totalNilaiMapping = 0.0;

        foreach ($realisasi->detail as $d) {
            $tarifPokok = (float) ($d->tarif_per_ha ?? 0);
            if ($tarifPokok <= 0 && $d->jenis_pekerjaan_id) {
                $tarifPokok = $this->cariTarifAktif((int) $d->jenis_pekerjaan_id, (string) $d->tanggal_pekerjaan);
            }
            $subPokok = (float) $d->luas_realisasi_ha * $tarifPokok;
            $totalNilaiPokok += $subPokok;

            if ($d->perlu_mapping) {
                $tarifMap = (float) ($d->tarif_mapping_per_ha ?? 0);
                if ($tarifMap <= 0) {
                    $tarifMap = $this->cariTarifMappingAktif((string) $d->tanggal_pekerjaan);
                }
                $subMap = (float) $d->luas_realisasi_ha * $tarifMap;
                $totalNilaiMapping += $subMap;
            }
        }

        $totalNilai = $totalNilaiPokok + $totalNilaiMapping;

        // Kalkulasi pembagian pendapatan operasional:
        // Potongan ADM: 5% dari Total Nilai
        $potonganAdm = round($totalNilai * 0.05, 2);
        // Alokasi Investor: 15% dari Total Nilai
        $bagianInvestor = round($totalNilai * 0.15, 2);
        // Sisa Bersih Operasional: 80% dari Total Nilai
        $bersihOperasional = round($totalNilai - $potonganAdm - $bagianInvestor, 2);

        // Ambil konfigurasi tim yang bertugas
        $tim = $realisasi->tim;

        // 1. Komponen Hak Pilot & Copilot (Gaji Pokok Tetap + Premi Terbang per Ha)
        $gajiPokokPilot = (float) ($tim?->gaji_pokok_pilot ?? 1000000.00);
        $premiPilotPerHa = (float) ($tim?->premi_pilot_per_ha ?? 22000.00);
        $premiPilotHariIni = round($totalLuas * $premiPilotPerHa, 2);

        // 2. Komponen Hak Helper: Fleksibel Per Hektar atau Harian sesuai pengaturan Admin
        $adaHelper = ! empty(trim((string) $realisasi->nama_helper));
        $skemaHelper = $tim?->skema_upah_helper ?? Tim::SKEMA_HELPER_PER_HEKTAR;
        $tarifHelperPerHa = (float) ($tim?->premi_helper_per_ha ?? 10000.00);
        $tarifHelperPerHari = (float) ($tim?->tarif_helper_per_hari ?? 100000.00);
        $hariKerjaHelper = 1;

        if ($adaHelper) {
            $estimasiHelper = $skemaHelper === Tim::SKEMA_HELPER_HARIAN
                ? round($hariKerjaHelper * $tarifHelperPerHari, 2)
                : round($totalLuas * $tarifHelperPerHa, 2);
        } else {
            $estimasiHelper = 0.0;
        }

        // Estimasi sisa alokasi operasional kru
        $estimasiPilotKru = max(0.0, round($bersihOperasional - $estimasiHelper, 2));

        return view('realisasi.show', compact(
            'realisasi', 'totalLuas', 'totalLuasMapping', 'totalNilai',
            'totalNilaiPokok', 'totalNilaiMapping',
            'potonganAdm', 'bagianInvestor', 'bersihOperasional',
            'tim', 'gajiPokokPilot', 'premiPilotPerHa', 'premiPilotHariIni',
            'adaHelper', 'skemaHelper', 'tarifHelperPerHa', 'tarifHelperPerHari',
            'hariKerjaHelper', 'estimasiHelper', 'estimasiPilotKru'
        ));
    }

    public function edit(LaporanRealisasi $realisasi): View
    {
        $pengguna = $this->pastikanPekerjaLapangan();
        $this->pastikanAksesTim($realisasi->tim_id);

        if (! in_array($realisasi->status_laporan, [LaporanRealisasi::DRAF, LaporanRealisasi::PERLU_PERBAIKAN], true)) {
            abort(403, 'Hanya laporan berstatus draf atau perlu perbaikan yang dapat diubah.');
        }

        // Kunci edit laporan: Pekerja HANYA boleh mengedit jika statusnya revisi (perlu_perbaikan) dari Kepala Lapangan
        if ($realisasi->status_laporan !== LaporanRealisasi::PERLU_PERBAIKAN) {
            abort(403, 'Pekerja lapangan hanya diperbolehkan mengedit laporan yang berstatus perlu perbaikan (revisi dari Kepala Lapangan).');
        }

        $idTimBoleh = $pengguna->idTimYangDiakses();

        $daftarTim = Tim::when($idTimBoleh !== null, fn ($q) => $q->whereIn('id', $idTimBoleh))
            ->where('status_tim', 'aktif')
            ->with(['distrik', 'anggota', 'drone'])
            ->orderBy('nama_tim')
            ->get();

        $daftarDistrik = Distrik::where('aktif', true)->orderBy('nama_distrik')->get();
        // Hanya pekerjaan pokok / aplikasi drone (tipe = utama) yang muncul di dropdown, karena mapping adalah add-on di kolom kanan
        $daftarPekerjaan = JenisPekerjaan::utama()->where('aktif', true)->orderBy('nama_pekerjaan')->get();
        $daftarPetak = Petak::where('status_petak', 'aktif')->with('distrik')->orderBy('kode_petak')->get();
        $daftarJenisBbm = JenisBbm::where('aktif', true)->orderBy('nama_bbm')->get();
        $daftarSpk = Spk::where('status_spk', Spk::DISETUJUI)
            ->orWhere('id', $realisasi->spk_id)
            ->with(['distrik', 'jenisPekerjaan', 'detail.petak'])
            ->latest('id')
            ->get();
        $statusPekerjaan = DetailRealisasi::statusPekerjaan();

        $realisasi->load(['detail', 'lampiran', 'spk', 'transaksiBbm.jenisBbm']);

        return view('realisasi.edit', compact(
            'realisasi', 'daftarTim', 'daftarDistrik', 'daftarPekerjaan', 'daftarPetak', 'daftarSpk', 'daftarJenisBbm', 'statusPekerjaan'
        ));
    }

    public function update(Request $request, LaporanRealisasi $realisasi): RedirectResponse
    {
        $pengguna = $this->pastikanPekerjaLapangan();
        $this->pastikanAksesTim($realisasi->tim_id);

        if (! in_array($realisasi->status_laporan, [LaporanRealisasi::DRAF, LaporanRealisasi::PERLU_PERBAIKAN], true)) {
            abort(403, 'Hanya laporan berstatus draf atau perlu perbaikan yang dapat diubah.');
        }

        // Kunci edit laporan: Pekerja HANYA boleh mengedit jika statusnya revisi (perlu_perbaikan) dari Kepala Lapangan
        if ($realisasi->status_laporan !== LaporanRealisasi::PERLU_PERBAIKAN) {
            abort(403, 'Pekerja lapangan hanya diperbolehkan memperbarui laporan yang berstatus perlu perbaikan (revisi dari Kepala Lapangan).');
        }

        $data = $request->validate([
            'nomor_laporan' => ['nullable', 'string', 'max:60'],
            'tim_id' => [
                'required',
                'exists:tim,id',
                function ($attribute, $value, $fail) use ($realisasi) {
                    $timDipilih = Tim::with('drone')->find($value);
                    if ($timDipilih && (int) $value !== (int) $realisasi->tim_id && $timDipilih->drone && in_array($timDipilih->drone->status_operasional, [Drone::STATUS_PERAWATAN, Drone::STATUS_TIDAK_BEROPERASI], true)) {
                        $statusStr = $timDipilih->drone->status_operasional === Drone::STATUS_PERAWATAN ? 'Perawatan' : 'Tidak Beroperasi';
                        $catatan = $timDipilih->drone->catatan_perawatan ? " ({$timDipilih->drone->catatan_perawatan})" : '';
                        $fail("Unit Drone {$timDipilih->drone->nomor_drone} milik tim ini sedang dalam status {$statusStr}{$catatan} dan tidak dapat digunakan untuk penerbangan lapangan.");
                    }
                },
            ],
            'nama_tim_manual' => ['nullable', 'string', 'max:150'],
            'nama_pilot' => ['required', 'string', 'max:150'],
            'nama_copilot' => ['nullable', 'string', 'max:150'],
            'nama_helper' => ['nullable'],
            'nama_helper.*' => ['nullable', 'string', 'max:150'],
            'spk_id' => ['required', 'exists:spk,id'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'distrik_id' => ['required', 'exists:distrik,id'],
            'ajukan' => ['nullable', 'boolean'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'petak' => ['required', 'array', 'min:1'],
            'petak.*.tanggal_pekerjaan' => ['required', 'date'],
            'petak.*.petak_id' => ['nullable', 'exists:petak,id'],
            'petak.*.kode_petak_manual' => ['nullable', 'string', 'max:80'],
            'petak.*.jenis_pekerjaan_id' => ['required', 'exists:jenis_pekerjaan,id'],
            'petak.*.luas_spk_ha' => ['nullable', 'numeric', 'min:0'],
            'petak.*.luas_realisasi_ha' => ['required', 'numeric', 'min:0'],
            'petak.*.perlu_mapping' => ['nullable', 'boolean'],
            'petak.*.status_pekerjaan' => ['nullable', 'string'],
            'petak.*.keterangan_kendala' => ['nullable', 'string', 'max:500'],
            'ada_kendala' => ['nullable', 'in:0,1'],
            'catatan_kendala' => ['nullable', 'string', 'max:1000', 'required_if:ada_kendala,1'],
            'ada_bbm' => ['nullable', 'in:0,1'],
            'keterangan_tanpa_bbm' => ['nullable', 'string', 'max:500', 'required_if:ada_bbm,0'],
            'catat_bbm' => ['nullable', 'boolean'],
            'bbm_items' => ['nullable', 'array'],
            'bbm_items.*.jenis_bbm_id' => ['nullable', 'exists:jenis_bbm,id'],
            'bbm_items.*.keperluan' => ['nullable', 'string', 'max:100'],
            'bbm_items.*.jumlah_liter' => ['nullable', 'numeric', 'min:0.01'],
            'bbm_items.*.keterangan' => ['nullable', 'string', 'max:255'],
            'bbm_jenis_bbm_id' => ['nullable', 'exists:jenis_bbm,id'],
            'bbm_jumlah_liter' => ['nullable', 'numeric', 'min:0.01'],
            'bbm_keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'spk_id.required' => 'Wajib memilih SPK yang diterbitkan oleh Kepala Lapangan.',
            'spk_id.exists' => 'SPK yang dipilih tidak ditemukan dalam sistem.',
            'nama_pilot.required' => 'Nama Pilot wajib diisi untuk pelaporan tim di lapangan.',
            'petak.required' => 'Minimal satu baris pekerjaan petak harus diisi.',
            'petak.*.tanggal_pekerjaan.required' => 'Tanggal pekerjaan wajib diisi.',
            'petak.*.jenis_pekerjaan_id.required' => 'Pilih jenis pekerjaan.',
            'petak.*.luas_realisasi_ha.required' => 'Isi luas realisasi (Ha).',
        ]);

        $spk = Spk::with('distrik')->findOrFail($data['spk_id']);
        if ($spk->status_spk !== Spk::DISETUJUI && $spk->id !== $realisasi->spk_id) {
            return back()->withErrors(['spk_id' => 'SPK yang dipilih belum disetujui atau tidak aktif.'])->withInput();
        }

        // Pastikan distrik laporan sinkron dengan distrik SPK
        if ((int) $data['distrik_id'] !== (int) $spk->distrik_id) {
            return back()->withErrors(['distrik_id' => 'Distrik laporan harus sesuai dengan distrik SPK terpilih (' . ($spk->distrik?->nama_distrik ?? '') . ').'])->withInput();
        }

        // Pastikan setiap petak yang dipilih terdaftar pada target detail_spk dari SPK terpilih (1 SPK = 1 Petak)
        $spk->load('detail');
        $targetDetailSpkUpdate = $spk->detail->first();
        $targetPetakIdUpdate = $targetDetailSpkUpdate?->petak_id;
        $targetPetakIds = $spk->detail->pluck('petak_id')->filter()->all();

        foreach ($data['petak'] as $idx => &$row) {
            if (empty($row['petak_id']) && $targetPetakIdUpdate) {
                $row['petak_id'] = $targetPetakIdUpdate;
            } elseif (! empty($row['petak_id']) && ! empty($targetPetakIds)) {
                if (! in_array((int) $row['petak_id'], $targetPetakIds, true)) {
                    return back()->withErrors(["petak.{$idx}.petak_id" => "Petak pada baris ke-" . ($idx + 1) . " bukan bagian dari target SPK terpilih ({$spk->nomor_spk})."])->withInput();
                }
            }

            if (empty($row['jenis_pekerjaan_id']) && $spk->jenis_pekerjaan_id) {
                $row['jenis_pekerjaan_id'] = $spk->jenis_pekerjaan_id;
            }
        }
        unset($row);

        // Evaluasi kondisi kendala & konsumsi BBM
        $adaKendala = $request->has('ada_kendala')
            ? $request->boolean('ada_kendala')
            : (! empty($data['catatan_kendala']) || collect($data['petak'])->contains(fn ($p) => ($p['status_pekerjaan'] ?? '') === 'terkendala'));

        $adaBbm = $request->has('ada_bbm')
            ? $request->boolean('ada_bbm')
            : ($request->boolean('catat_bbm') || ! empty($data['bbm_items']) || ! empty($data['bbm_jumlah_liter']));

        if ($adaBbm) {
            $rawBbm = $request->input('bbm_items', []);
            $adaItemValid = collect($rawBbm)->contains(fn ($it) => (float)($it['jumlah_liter'] ?? 0) > 0 && ! empty($it['jenis_bbm_id']));
            if (! $adaItemValid && empty($data['bbm_jumlah_liter'])) {
                return back()->withErrors(['bbm_items' => 'Karena memilih "Ada Penggunaan BBM", wajib mengisi minimal satu rincian BBM dengan jumlah liter lebih dari 0.'])->withInput();
            }
        }

        // Jika operasional lancar (tanpa kendala), luas realisasi tidak boleh 0.00 Ha
        $totalLuasInput = collect($data['petak'])->sum(fn ($p) => (float) ($p['luas_realisasi_ha'] ?? 0));
        if (! $adaKendala && $totalLuasInput <= 0) {
            return back()->withErrors(['petak.0.luas_realisasi_ha' => 'Luas realisasi tidak boleh 0.00 Ha jika operasional berjalan lancar tanpa kendala. Jika pekerjaan batal terbang atau terhenti, silakan pilih "Terdapat Kendala Lapangan".'])->withInput();
        }

        $statusLaporan = $request->boolean('ajukan')
            ? LaporanRealisasi::MENUNGGU_PEMERIKSAAN
            : $realisasi->status_laporan;

        DB::transaction(function () use ($data, $spk, $statusLaporan, $adaKendala, $adaBbm, $request, $realisasi) {
            $namaHelper = $this->normalisasiNamaHelper($request->input('nama_helper'));

            $realisasi->update([
                'nomor_laporan' => $data['nomor_laporan'] ?? $realisasi->getRawOriginal('nomor_laporan'),
                'tim_id' => $data['tim_id'],
                'nama_tim_manual' => $data['nama_tim_manual'] ?? null,
                'nama_pilot' => $data['nama_pilot'],
                'nama_copilot' => $data['nama_copilot'] ?? null,
                'nama_helper' => $namaHelper,
                'spk_id' => $spk->id,
                'periode_bulan' => $data['periode_bulan'],
                'periode_tahun' => $data['periode_tahun'],
                'distrik_id' => $data['distrik_id'],
                'status_laporan' => $statusLaporan,
                'ada_kendala' => $adaKendala,
                'catatan_kendala' => $adaKendala ? ($data['catatan_kendala'] ?? null) : null,
                'ada_bbm' => $adaBbm,
                'keterangan_tanpa_bbm' => ! $adaBbm ? ($data['keterangan_tanpa_bbm'] ?? null) : null,
            ]);

            // Replace detail petak
            $realisasi->detail()->delete();
            $spk->loadMissing('detail');
            $detailSpkByPetakId = $spk->detail->keyBy('petak_id');

            foreach ($data['petak'] as $row) {
                $tarifAktif = $this->cariTarifAktif((int) $row['jenis_pekerjaan_id'], (string) $row['tanggal_pekerjaan']);

                $petakId = ! empty($row['petak_id']) ? (int) $row['petak_id'] : null;
                $spkItem = $petakId ? ($detailSpkByPetakId[$petakId] ?? null) : null;
                $perluMapping = isset($row['perlu_mapping'])
                    ? filter_var($row['perlu_mapping'], FILTER_VALIDATE_BOOLEAN)
                    : ($spkItem?->perlu_mapping ?? false);

                $tarifMapping = null;
                if ($perluMapping) {
                    $tarifMapping = $spkItem?->tarif_mapping_per_ha ?? $this->cariTarifMappingAktif((string) $row['tanggal_pekerjaan']);
                }

                $ketKendala = ! empty($row['keterangan_kendala'])
                    ? $row['keterangan_kendala']
                    : ($adaKendala ? ($data['catatan_kendala'] ?? null) : null);

                $statusPekerjaan = ! empty($row['status_pekerjaan'])
                    ? $row['status_pekerjaan']
                    : ($adaKendala ? 'terkendala' : 'selesai');

                if ($adaKendala && ($statusPekerjaan === 'selesai' || (float)($row['luas_realisasi_ha'] ?? 0) == 0)) {
                    $statusPekerjaan = 'terkendala';
                }

                DetailRealisasi::create([
                    'laporan_realisasi_id' => $realisasi->id,
                    'tanggal_pekerjaan' => $row['tanggal_pekerjaan'],
                    'petak_id' => $petakId,
                    'kode_petak_manual' => empty($petakId) ? ($row['kode_petak_manual'] ?? null) : null,
                    'jenis_pekerjaan_id' => $row['jenis_pekerjaan_id'],
                    'luas_realisasi_ha' => $row['luas_realisasi_ha'],
                    'perlu_mapping' => $perluMapping,
                    'status_pekerjaan' => $statusPekerjaan,
                    'keterangan_kendala' => $ketKendala,
                    'tarif_per_ha' => $tarifAktif,
                    'tarif_mapping_per_ha' => $tarifMapping,
                ]);
            }

            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $namaAsli = $file->getClientOriginalName();
                    $path = $file->store('lampiran/realisasi', 'public');

                    LampiranRealisasi::create([
                        'laporan_realisasi_id' => $realisasi->id,
                        'nama_berkas_asli' => $namaAsli,
                        'lokasi_berkas' => $path,
                        'jenis_lampiran' => 'Foto / Bukti Pekerjaan',
                        'diunggah_oleh' => Auth::id(),
                    ]);
                }
            }

            // Integrasi Multi-Input Repeater Konsumsi BBM (Wajib jika ada_bbm == true)
            if ($adaBbm) {
                $rawBbmItems = $request->input('bbm_items', []);

                if (empty($rawBbmItems) && ! empty($data['bbm_jumlah_liter']) && ! empty($data['bbm_jenis_bbm_id'])) {
                    $rawBbmItems = [[
                        'jenis_bbm_id' => $data['bbm_jenis_bbm_id'],
                        'keperluan' => 'operasional_drone',
                        'jumlah_liter' => $data['bbm_jumlah_liter'],
                        'keterangan' => $data['bbm_keterangan'] ?? null,
                    ]];
                }

                // Hapus transaksi BBM menunggu verifikasi lama yang tertaut pada laporan ini untuk diganti dengan repeater baru
                $realisasi->transaksiBbm()->where('status_verifikasi', '!=', TransaksiBbm::TERVERIFIKASI)->delete();

                foreach ($rawBbmItems as $item) {
                    $liter = (float) ($item['jumlah_liter'] ?? 0);
                    $jenisBbmId = (int) ($item['jenis_bbm_id'] ?? 0);
                    if ($liter <= 0 || ! $jenisBbmId) {
                        continue;
                    }

                    $masterBbm = JenisBbm::find($jenisBbmId);
                    $hargaSatuan = $masterBbm?->harga_per_liter ?? 0;
                    $totalBiaya = round($liter * $hargaSatuan, 2);

                    TransaksiBbm::create([
                        'nomor_transaksi' => 'BBM/' . now()->format('Y/m/') . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT),
                        'tim_id' => $data['tim_id'],
                        'jenis_bbm_id' => $jenisBbmId,
                        'jenis_transaksi' => TransaksiBbm::PENGGUNAAN,
                        'tanggal_transaksi' => $data['petak'][0]['tanggal_pekerjaan'] ?? now()->toDateString(),
                        'jumlah_liter' => $liter,
                        'harga_per_liter' => $hargaSatuan > 0 ? $hargaSatuan : null,
                        'total_biaya' => $totalBiaya > 0 ? $totalBiaya : null,
                        'keperluan' => ! empty($item['keperluan']) ? $item['keperluan'] : 'operasional_drone',
                        'keterangan' => ! empty($item['keterangan']) ? $item['keterangan'] : "Konsumsi BBM operasional untuk {$realisasi->nomor_laporan}",
                        'status_verifikasi' => TransaksiBbm::MENUNGGU_PEMERIKSAAN,
                        'laporan_realisasi_id' => $realisasi->id,
                        'diinput_oleh' => Auth::id(),
                    ]);
                }
            } else {
                $realisasi->transaksiBbm()->where('status_verifikasi', '!=', TransaksiBbm::TERVERIFIKASI)->delete();
            }
        });

        $this->aktivitas->catat('Memperbarui Laporan Realisasi', $realisasi, "Nomor: {$realisasi->nomor_laporan}");

        return redirect()->route('realisasi.show', $realisasi)->with('sukses', "Laporan {$realisasi->nomor_laporan} berhasil diperbarui.");
    }

    /**
     * Ajukan draf laporan langsung ke Kepala Lapangan (FR-10).
     */
    public function ajukan(LaporanRealisasi $realisasi): RedirectResponse
    {
        $this->pastikanPekerjaLapangan();
        $this->pastikanAksesTim($realisasi->tim_id);

        if ($realisasi->status_laporan !== LaporanRealisasi::DRAF && $realisasi->status_laporan !== LaporanRealisasi::PERLU_PERBAIKAN) {
            return back()->withErrors(['pesan' => 'Hanya draf atau laporan yang perlu perbaikan yang dapat diajukan.']);
        }

        $realisasi->update(['status_laporan' => LaporanRealisasi::MENUNGGU_PEMERIKSAAN]);

        $this->aktivitas->catat('Mengajukan Laporan Realisasi', $realisasi, "Nomor: {$realisasi->nomor_laporan}");

        return back()->with('sukses', "Laporan {$realisasi->nomor_laporan} berhasil diajukan untuk diperiksa.");
    }

    /**
     * Pemeriksaan dan persetujuan khusus Kepala Lapangan (FR-11).
     */
    public function periksa(Request $request, LaporanRealisasi $realisasi): RedirectResponse
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahKepalaLapangan()) {
            abort(403, 'Hanya Kepala Lapangan yang berhak memeriksa dan menyetujui laporan realisasi.');
        }

        $data = $request->validate([
            'tindakan' => ['required', 'in:setujui,perbaikan,tolak'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $statusBaru = match ($data['tindakan']) {
            'setujui' => LaporanRealisasi::DISETUJUI,
            'perbaikan' => LaporanRealisasi::PERLU_PERBAIKAN,
            'tolak' => LaporanRealisasi::DITOLAK,
        };

        if ($statusBaru === LaporanRealisasi::PERLU_PERBAIKAN && empty(trim((string) $data['catatan']))) {
            return back()->withErrors(['catatan' => 'Catatan pemeriksaan wajib diisi ketika meminta perbaikan.']);
        }

        $realisasi->update([
            'status_laporan' => $statusBaru,
            'diperiksa_oleh' => $pengguna->id,
            'tanggal_pemeriksaan' => now(),
            'catatan_pemeriksaan' => $data['catatan'] ?? null,
        ]);

        $this->aktivitas->catat(
            "Pemeriksaan Realisasi: {$statusBaru}",
            $realisasi,
            "Oleh {$pengguna->nama_lengkap}. Catatan: ".($data['catatan'] ?? '-')
        );

        return back()->with('sukses', "Status laporan berhasil diperbarui menjadi {$statusBaru}.");
    }

    /**
     * Hapus laporan realisasi (hanya draf atau perlu perbaikan).
     */
    public function destroy(LaporanRealisasi $realisasi): RedirectResponse
    {
        $pengguna = $this->pastikanPekerjaLapangan();
        $this->pastikanAksesTim($realisasi->tim_id);

        if ($realisasi->diinput_oleh !== $pengguna->id) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus laporan ini.');
        }

        if ($realisasi->status_laporan === LaporanRealisasi::DISETUJUI) {
            return back()->withErrors(['pesan' => 'Laporan yang telah disetujui tidak dapat dihapus.']);
        }

        $nomorLaporan = $realisasi->nomor_laporan;

        DB::transaction(function () use ($realisasi) {
            foreach ($realisasi->lampiran as $lampiran) {
                if (Storage::disk('public')->exists($lampiran->lokasi_berkas)) {
                    Storage::disk('public')->delete($lampiran->lokasi_berkas);
                }
            }
            $realisasi->delete();
        });

        $this->aktivitas->catat('Menghapus Laporan Realisasi', $realisasi, "Nomor: {$nomorLaporan}");

        return redirect()->route('realisasi.index')->with('sukses', "Laporan {$nomorLaporan} berhasil dihapus.");
    }

    public function unduhLampiran(LampiranRealisasi $lampiran): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $realisasi = $lampiran->laporan;
        if (! $realisasi) {
            abort(404, 'Laporan terkait tidak ditemukan.');
        }

        $this->pastikanAksesTim($realisasi->tim_id);

        if (! Storage::disk('public')->exists($lampiran->lokasi_berkas)) {
            abort(404, 'Berkas lampiran tidak ditemukan di penyimpanan server.');
        }

        $pathAbsolut = Storage::disk('public')->path($lampiran->lokasi_berkas);

        return response()->file($pathAbsolut);
    }

    private function cariTarifAktif(int $jenisPekerjaanId, string $tanggalPekerjaan): float
    {
        $tarif = \App\Models\TarifPekerjaan::where('jenis_pekerjaan_id', $jenisPekerjaanId)
            ->where('berlaku_mulai', '<=', $tanggalPekerjaan)
            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $tanggalPekerjaan))
            ->latest('berlaku_mulai')
            ->value('nilai_tarif');

        if (! $tarif) {
            $tarif = \App\Models\TarifPekerjaan::where('jenis_pekerjaan_id', $jenisPekerjaanId)
                ->latest('berlaku_mulai')
                ->value('nilai_tarif');
        }

        return (float) ($tarif ?? 0.0);
    }

    private function cariTarifMappingAktif(string $tanggalPekerjaan): float
    {
        $pekerjaanMapping = JenisPekerjaan::where('tipe', JenisPekerjaan::TIPE_ADDON)
            ->orWhere('kode_pekerjaan', 'SRV-MAPPING')
            ->first();

        if (! $pekerjaanMapping) {
            return 30000.00;
        }

        $tarif = \App\Models\TarifPekerjaan::where('jenis_pekerjaan_id', $pekerjaanMapping->id)
            ->where('berlaku_mulai', '<=', $tanggalPekerjaan)
            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $tanggalPekerjaan))
            ->latest('berlaku_mulai')
            ->value('nilai_tarif');

        if (! $tarif) {
            $tarif = \App\Models\TarifPekerjaan::where('jenis_pekerjaan_id', $pekerjaanMapping->id)
                ->latest('berlaku_mulai')
                ->value('nilai_tarif');
        }

        return (float) ($tarif ?? 30000.00);
    }

    /**
     * Memastikan hanya Pekerja Lapangan yang dapat mengisi, mengubah, mengajukan, atau menghapus laporan realisasi.
     */
    private function pastikanPekerjaLapangan(): Pengguna
    {
        $pengguna = Auth::user();
        if (! $pengguna || ! $pengguna->adalahPekerjaLapangan()) {
            abort(403, 'Hanya Pekerja Lapangan yang berwenang mengisi, mengubah, atau mengajukan laporan realisasi.');
        }

        return $pengguna;
    }

    private function pastikanAksesTim(int $timId): void
    {
        $pengguna = Auth::user();
        $idTimBoleh = $pengguna->idTimYangDiakses();

        if ($idTimBoleh !== null && ! in_array($timId, $idTimBoleh, true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk data tim ini.');
        }
    }

    /**
     * Menormalisasi input helper (string atau array tanpa batas) menjadi format string rapi.
     */
    private function normalisasiNamaHelper(mixed $input): ?string
    {
        if (is_array($input)) {
            $cleaned = array_values(array_filter(array_map('trim', $input), fn ($v) => $v !== null && $v !== ''));
            return ! empty($cleaned) ? implode(', ', $cleaned) : null;
        }

        if (is_string($input)) {
            $trimmed = trim($input);
            return $trimmed !== '' ? $trimmed : null;
        }

        return null;
    }
}
