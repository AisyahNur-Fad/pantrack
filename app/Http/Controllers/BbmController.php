<?php

namespace App\Http\Controllers;

use App\Models\JenisBbm;
use App\Models\LampiranBbm;
use App\Models\Pengguna;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use App\Services\BbmService;
use App\Services\RiwayatAktivitasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Pengelolaan dan Monitoring BBM per Tim (FR-16 s/d FR-21).
 *
 * Aturan penting:
 *  - Saldo BBM dihitung per tim secara terisolasi (FR-20).
 *  - Pekerja menginput pengajuan, penerimaan, dan penggunaan BBM (FR-16, 17, 18).
 *  - Admin memeriksa dan memverifikasi transaksi BBM (FR-19).
 *  - Hanya transaksi terverifikasi yang memengaruhi saldo stok resmi (FR-20, 21).
 */
class BbmController extends Controller
{
    public function __construct(
        private readonly BbmService $bbmService,
        private readonly RiwayatAktivitasService $aktivitas
    ) {}

    public function index(Request $request): View
    {
        $pengguna = Auth::user();
        $idTimBoleh = $pengguna->idTimYangDiakses();

        $timId = $request->input('tim_id');
        $jenisBbmId = $request->input('jenis_bbm_id');
        $jenisTransaksi = $request->input('jenis_transaksi');
        $status = $request->input('status');
        $bulan = (int) $request->input('bulan', 0);
        $tahun = (int) $request->input('tahun', 0);

        // Jika pekerja lapangan hanya punya 1 tim dan belum memilih filter tim, pasang otomatis
        if ($pengguna->adalahPekerjaLapangan() && ! $timId && ! empty($idTimBoleh)) {
            $timId = $idTimBoleh[0];
        }

        $query = TransaksiBbm::query()
            ->with(['tim', 'jenisBbm', 'penginput', 'pemeriksa', 'lampiran', 'laporanRealisasi.spk'])
            ->withCount('lampiran')
            ->when($idTimBoleh !== null, fn ($q) => $q->whereIn('tim_id', $idTimBoleh))
            ->when($timId, fn ($q) => $q->where('tim_id', $timId))
            ->when($jenisBbmId, fn ($q) => $q->where('jenis_bbm_id', $jenisBbmId))
            ->when($jenisTransaksi, function ($q) use ($jenisTransaksi) {
                if ($jenisTransaksi === 'masuk') {
                    return $q->whereIn('jenis_transaksi', [TransaksiBbm::PENERIMAAN, TransaksiBbm::STOK_AWAL]);
                }

                return $q->where('jenis_transaksi', $jenisTransaksi);
            })
            ->when($status, fn ($q) => $q->where('status_verifikasi', $status))
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal_transaksi', $bulan))
            ->when($tahun, fn ($q) => $q->whereYear('tanggal_transaksi', $tahun));

        $daftarTransaksi = $query->latest('tanggal_transaksi')->latest('id')->paginate(15)->withQueryString();

        $daftarTim = Tim::when($idTimBoleh !== null, fn ($q) => $q->whereIn('id', $idTimBoleh))
            ->where('status_tim', 'aktif')
            ->orderBy('nama_tim')
            ->get();

        $daftarJenisBbm = JenisBbm::where('aktif', true)->orderBy('nama_bbm')->get();

        // Hitung ringkasan tab cepat agar selalu akurat sesuai cakupan akses pengguna
        $baseTabQuery = TransaksiBbm::query()
            ->when($idTimBoleh !== null, fn ($q) => $q->whereIn('tim_id', $idTimBoleh))
            ->when($timId, fn ($q) => $q->where('tim_id', $timId));

        $tabCounts = [
            'semua' => (clone $baseTabQuery)->count(),
            'menunggu' => (clone $baseTabQuery)->where('status_verifikasi', TransaksiBbm::MENUNGGU_PEMERIKSAAN)->count(),
            'perbaikan' => (clone $baseTabQuery)->where('status_verifikasi', TransaksiBbm::PERLU_PERBAIKAN)->count(),
            'masuk' => (clone $baseTabQuery)->whereIn('jenis_transaksi', [TransaksiBbm::PENERIMAAN, TransaksiBbm::STOK_AWAL])->count(),
            'keluar' => (clone $baseTabQuery)->where('jenis_transaksi', TransaksiBbm::PENGGUNAAN)->count(),
        ];

        // Kartu Ringkasan Stok Tim
        $ringkasanTim = $this->bbmService->ringkasanTim($idTimBoleh);

        $totalBiayaMasukBulanIni = (float) TransaksiBbm::query()
            ->when($idTimBoleh !== null, fn ($q) => $q->whereIn('tim_id', $idTimBoleh))
            ->where('jenis_transaksi', TransaksiBbm::PENERIMAAN)
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->sum('total_biaya');

        $ringkasanGlobal = [
            'total_tim' => $ringkasanTim->count(),
            'tim_beraktivitas' => $ringkasanTim->filter(fn (array $item) =>
                abs((float) $item['saldo']) > 0
                || $item['menunggu'] > 0
                || $item['penerimaan_bulan_ini'] > 0
                || $item['penggunaan_bulan_ini'] > 0
            )->count(),
            'tim_ada_saldo' => $ringkasanTim->filter(fn (array $item) => (float) $item['saldo'] > 0)->count(),
            'tim_defisit' => $ringkasanTim->filter(fn (array $item) => (float) $item['saldo'] < 0)->count(),
            'total_menunggu' => (int) $ringkasanTim->sum('menunggu'),
            'total_masuk_bulan_ini' => round((float) $ringkasanTim->sum('penerimaan_bulan_ini'), 1),
            'total_keluar_bulan_ini' => round((float) $ringkasanTim->sum('penggunaan_bulan_ini'), 1),
            'total_biaya_bulan_ini' => $totalBiayaMasukBulanIni,
        ];

        return view('bbm.index', compact(
            'daftarTransaksi', 'daftarTim', 'daftarJenisBbm', 'ringkasanTim', 'ringkasanGlobal', 'tabCounts',
            'timId', 'jenisBbmId', 'jenisTransaksi', 'status', 'bulan', 'tahun'
        ));
    }

    public function create(): View
    {
        $pengguna = Auth::user();
        $idTimBoleh = $pengguna->idTimYangDiakses();

        $daftarTim = Tim::when($idTimBoleh !== null, fn ($q) => $q->whereIn('id', $idTimBoleh))
            ->where('status_tim', 'aktif')
            ->orderBy('nama_tim')
            ->get();

        if ($daftarTim->isEmpty()) {
            abort(403, 'Anda belum memiliki tim lapangan yang aktif.');
        }

        $daftarJenisBbm = JenisBbm::where('aktif', true)->orderBy('nama_bbm')->get();
        $jenisTransaksi = $this->daftarJenisTransaksiUntukPengguna($pengguna);
        $saldoPerTim = $this->petaSaldoTim($idTimBoleh);

        return view('bbm.create', compact('daftarTim', 'daftarJenisBbm', 'jenisTransaksi', 'saldoPerTim'));
    }

    public function store(Request $request): RedirectResponse
    {
        $pengguna = Auth::user();
        $idTimBoleh = $pengguna->idTimYangDiakses();
        $jenisBoleh = array_keys($this->daftarJenisTransaksiUntukPengguna($pengguna));

        $data = $request->validate([
            'nomor_transaksi' => ['nullable', 'string', 'max:60', 'unique:transaksi_bbm,nomor_transaksi'],
            'tim_id' => [
                'required',
                'exists:tim,id',
                function ($attribute, $value, $fail) use ($idTimBoleh) {
                    if ($idTimBoleh !== null && ! in_array((int) $value, $idTimBoleh, true)) {
                        $fail('Anda tidak memiliki izin mencatat BBM untuk tim ini.');
                    }
                },
            ],
            'jenis_bbm_id' => ['required', 'exists:jenis_bbm,id'],
            'jenis_transaksi' => ['required', 'in:' . implode(',', $jenisBoleh)],
            'tanggal_transaksi' => ['required', 'date'],
            'jumlah_liter' => ['required', 'numeric', 'min:0.01'],
            'harga_per_liter' => ['nullable', 'numeric', 'min:0'],
            'total_biaya' => ['nullable', 'numeric', 'min:0'],
            'nomor_struk_nota' => ['nullable', 'string', 'max:100'],
            'keperluan' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if (empty(trim((string) ($data['nomor_transaksi'] ?? '')))) {
            $data['nomor_transaksi'] = 'BBM/' . now()->format('Y/m/') . strtoupper(substr(uniqid(), -5));
        }

        $data = $this->siapkanDataMutasi($data);

        // Cek jika transaksi penggunaan melebihi sisa stok yang tersedia saat ini
        if ($data['jenis_transaksi'] === TransaksiBbm::PENGGUNAAN) {
            $saldoTersedia = $this->bbmService->hitungSaldoTersedia((int) $data['tim_id'], (int) $data['jenis_bbm_id']);
            $saldoResmi = $this->bbmService->hitungSaldoResmi((int) $data['tim_id'], (int) $data['jenis_bbm_id']);

            if ((float) $data['jumlah_liter'] > $saldoTersedia) {
                $sedangVerifikasi = max(0.0, round($saldoResmi - $saldoTersedia, 2));
                return back()->withInput()->withErrors([
                    'jumlah_liter' => "Jumlah pemakaian ({$data['jumlah_liter']} L) melebihi sisa saldo tersedia ({$saldoTersedia} L). Sisa saldo resmi: {$saldoResmi} L, sedang menunggu verifikasi: {$sedangVerifikasi} L.",
                ]);
            }
        }

        // Admin yang input stok awal otomatis terverifikasi
        $statusAwal = ($pengguna->adalahAdmin() && $data['jenis_transaksi'] === TransaksiBbm::STOK_AWAL)
            ? TransaksiBbm::TERVERIFIKASI
            : TransaksiBbm::MENUNGGU_PEMERIKSAAN;

        DB::transaction(function () use ($data, $statusAwal, $request, &$transaksi) {
            $transaksi = TransaksiBbm::create([
                'nomor_transaksi' => $data['nomor_transaksi'],
                'tim_id' => $data['tim_id'],
                'jenis_bbm_id' => $data['jenis_bbm_id'],
                'jenis_transaksi' => $data['jenis_transaksi'],
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'jumlah_liter' => $data['jumlah_liter'],
                'harga_per_liter' => $data['harga_per_liter'] ?? null,
                'total_biaya' => $data['total_biaya'],
                'nomor_struk_nota' => $data['nomor_struk_nota'] ?? null,
                'keperluan' => $data['keperluan'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'status_verifikasi' => $statusAwal,
                'diinput_oleh' => Auth::id(),
            ]);

            $this->simpanLampiranBerkas($request, $transaksi);
        });

        $this->aktivitas->catat(
            "Mencatat BBM ({$transaksi->jenis_transaksi})",
            $transaksi,
            "Nomor Transaksi: {$transaksi->nomor_transaksi}, {$transaksi->jumlah_liter} Liter"
        );

        return redirect()->route('bbm.show', $transaksi)->with('sukses', "Transaksi BBM {$transaksi->nomor_transaksi} berhasil dicatat.");
    }

    public function show(TransaksiBbm $bbm): View
    {
        $this->pastikanAksesTim($bbm->tim_id);

        $bbm->load(['tim.distrik', 'tim.drone', 'jenisBbm', 'penginput', 'pemeriksa', 'lampiran', 'laporanRealisasi.spk']);
        $saldoTimSaatIni = $this->bbmService->ringkasanTim([$bbm->tim_id])->first();

        return view('bbm.show', compact('bbm', 'saldoTimSaatIni'));
    }

    /**
     * Verifikasi transaksi BBM khusus Admin (FR-19).
     */
    public function verifikasi(Request $request, TransaksiBbm $bbm): RedirectResponse
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahAdmin()) {
            abort(403, 'Hanya Admin yang berwenang memverifikasi transaksi BBM.');
        }

        $data = $request->validate([
            'tindakan' => ['required', 'in:verifikasi,perbaikan,tolak'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $statusBaru = match ($data['tindakan']) {
            'verifikasi' => TransaksiBbm::TERVERIFIKASI,
            'perbaikan' => TransaksiBbm::PERLU_PERBAIKAN,
            'tolak' => TransaksiBbm::DITOLAK,
        };

        if ($statusBaru === TransaksiBbm::PERLU_PERBAIKAN && empty(trim((string) $data['catatan']))) {
            return back()->withErrors(['catatan' => 'Catatan pemeriksaan wajib diisi ketika meminta perbaikan.']);
        }

        // Pastikan verifikasi penggunaan tidak menyebabkan saldo resmi menjadi negatif (defisit)
        if ($statusBaru === TransaksiBbm::TERVERIFIKASI && $bbm->jenis_transaksi === TransaksiBbm::PENGGUNAAN) {
            $saldoResmi = $this->bbmService->hitungSaldoResmi($bbm->tim_id, $bbm->jenis_bbm_id);
            if ((float) $bbm->jumlah_liter > $saldoResmi) {
                return back()->withErrors([
                    'tindakan' => "Tidak dapat memverifikasi. Jumlah penggunaan ({$bbm->jumlah_liter} L) melebihi saldo resmi tim saat ini ({$saldoResmi} L).",
                ]);
            }
        }

        $bbm->update([
            'status_verifikasi' => $statusBaru,
            'diperiksa_oleh' => $pengguna->id,
            'tanggal_pemeriksaan' => now(),
            'catatan_pemeriksaan' => $data['catatan'] ?? null,
        ]);

        $this->aktivitas->catat(
            "Verifikasi BBM: {$statusBaru}",
            $bbm,
            "Oleh Admin {$pengguna->nama_lengkap}. Catatan: ".($data['catatan'] ?? '-')
        );

        return back()->with('sukses', "Transaksi BBM berhasil diperbarui menjadi {$statusBaru}.");
    }

    public function edit(TransaksiBbm $bbm): View
    {
        $this->pastikanAksesTim($bbm->tim_id);

        if ($bbm->status_verifikasi === TransaksiBbm::TERVERIFIKASI) {
            abort(403, 'Transaksi BBM yang sudah terverifikasi terkunci permanen dan tidak dapat diubah demi integritas data.');
        }

        $pengguna = Auth::user();
        if (! $pengguna->adalahAdmin() && $bbm->diinput_oleh !== $pengguna->id) {
            abort(403, 'Hanya penginput transaksi atau Admin yang berhak mengubah data ini.');
        }

        $bbm->load('lampiran');
        $idTimBoleh = $pengguna->idTimYangDiakses();
        $daftarTim = Tim::when($idTimBoleh !== null, fn ($q) => $q->whereIn('id', $idTimBoleh))
            ->where('status_tim', 'aktif')
            ->orderBy('nama_tim')
            ->get();
        $daftarJenisBbm = JenisBbm::where('aktif', true)->orderBy('nama_bbm')->get();
        $jenisTransaksi = $this->daftarJenisTransaksiUntukPengguna($pengguna);
        $saldoPerTim = $this->petaSaldoTim($idTimBoleh);

        return view('bbm.edit', compact('bbm', 'daftarTim', 'daftarJenisBbm', 'jenisTransaksi', 'saldoPerTim'));
    }

    public function update(Request $request, TransaksiBbm $bbm): RedirectResponse
    {
        $this->pastikanAksesTim($bbm->tim_id);

        if ($bbm->status_verifikasi === TransaksiBbm::TERVERIFIKASI) {
            abort(403, 'Transaksi BBM yang sudah terverifikasi terkunci permanen dan tidak dapat diubah.');
        }

        $pengguna = Auth::user();
        if (! $pengguna->adalahAdmin() && $bbm->diinput_oleh !== $pengguna->id) {
            abort(403, 'Hanya penginput transaksi atau Admin yang berhak mengubah data ini.');
        }

        $idTimBoleh = $pengguna->idTimYangDiakses();
        $jenisBoleh = array_keys($this->daftarJenisTransaksiUntukPengguna($pengguna));

        $data = $request->validate([
            'tim_id' => [
                'required', 'exists:tim,id',
                function ($attribute, $value, $fail) use ($idTimBoleh) {
                    if ($idTimBoleh !== null && ! in_array((int) $value, $idTimBoleh, true)) {
                        $fail('Anda tidak memiliki hak akses untuk tim ini.');
                    }
                },
            ],
            'jenis_bbm_id' => ['required', 'exists:jenis_bbm,id'],
            'jenis_transaksi' => ['required', 'in:' . implode(',', $jenisBoleh)],
            'tanggal_transaksi' => ['required', 'date'],
            'jumlah_liter' => ['required', 'numeric', 'min:0.01'],
            'harga_per_liter' => ['nullable', 'numeric', 'min:0'],
            'total_biaya' => ['nullable', 'numeric', 'min:0'],
            'nomor_struk_nota' => ['nullable', 'string', 'max:100'],
            'keperluan' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'lampiran.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $data = $this->siapkanDataMutasi($data);

        if ($data['jenis_transaksi'] === TransaksiBbm::PENGGUNAAN) {
            $saldoTersedia = $this->bbmService->hitungSaldoTersedia((int) $data['tim_id'], (int) $data['jenis_bbm_id']);
            if ($bbm->jenis_transaksi === TransaksiBbm::PENGGUNAAN && (int) $bbm->tim_id === (int) $data['tim_id'] && (int) $bbm->jenis_bbm_id === (int) $data['jenis_bbm_id']) {
                $saldoTersedia += (float) $bbm->jumlah_liter;
            }
            if ((float) $data['jumlah_liter'] > $saldoTersedia) {
                return back()->withErrors([
                    'jumlah_liter' => "Saldo fisik BBM tidak mencukupi. Sisa saldo tersedia: " . number_format($saldoTersedia, 2, ',', '.') . " Liter.",
                ])->withInput();
            }
        }

        $literSebelum = (float) $bbm->jumlah_liter;

        DB::transaction(function () use ($data, $request, $bbm) {
            $bbm->update([
                'tim_id' => $data['tim_id'],
                'jenis_bbm_id' => $data['jenis_bbm_id'],
                'jenis_transaksi' => $data['jenis_transaksi'],
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'jumlah_liter' => $data['jumlah_liter'],
                'harga_per_liter' => $data['harga_per_liter'] ?? null,
                'total_biaya' => $data['total_biaya'],
                'nomor_struk_nota' => $data['nomor_struk_nota'] ?? null,
                'keperluan' => $data['keperluan'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'status_verifikasi' => TransaksiBbm::MENUNGGU_PEMERIKSAAN,
            ]);

            $this->simpanLampiranBerkas($request, $bbm);
        });

        $this->aktivitas->catat(
            "Memperbarui Transaksi BBM ({$bbm->nomor_transaksi})",
            $bbm,
            "Revisi dari {$literSebelum} L menjadi {$bbm->jumlah_liter} L oleh {$pengguna->nama_lengkap}"
        );

        return redirect()->route('bbm.show', $bbm)->with('sukses', "Transaksi BBM {$bbm->nomor_transaksi} berhasil diperbarui dan diajukan ulang untuk verifikasi.");
    }

    public function destroy(TransaksiBbm $bbm): RedirectResponse
    {
        $this->pastikanAksesTim($bbm->tim_id);

        if ($bbm->status_verifikasi === TransaksiBbm::TERVERIFIKASI) {
            abort(403, 'Transaksi BBM yang sudah terverifikasi tidak dapat dihapus.');
        }

        $pengguna = Auth::user();
        if (! $pengguna->adalahAdmin() && $bbm->diinput_oleh !== $pengguna->id) {
            abort(403, 'Hanya penginput transaksi atau Admin yang dapat membatalkan transaksi ini.');
        }

        $nomor = $bbm->nomor_transaksi;
        DB::transaction(function () use ($bbm) {
            foreach ($bbm->lampiran as $lamp) {
                if (Storage::disk('public')->exists($lamp->lokasi_berkas)) {
                    Storage::disk('public')->delete($lamp->lokasi_berkas);
                }
            }
            $bbm->lampiran()->delete();
            $bbm->delete();
        });

        $this->aktivitas->catat("Menghapus Transaksi BBM", null, "Nomor transaksi: {$nomor}");

        return redirect()->route('bbm.index')->with('sukses', "Transaksi BBM {$nomor} berhasil dibatalkan/dihapus.");
    }

    public function unduhLampiran(LampiranBbm $lampiran): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $bbm = $lampiran->transaksi;
        if (! $bbm) {
            abort(404, 'Transaksi BBM terkait tidak ditemukan.');
        }

        $this->pastikanAksesTim($bbm->tim_id);

        if (! Storage::disk('public')->exists($lampiran->lokasi_berkas)) {
            abort(404, 'Berkas lampiran BBM tidak ditemukan di penyimpanan server.');
        }

        $pathAbsolut = Storage::disk('public')->path($lampiran->lokasi_berkas);

        return response()->file($pathAbsolut);
    }

    /**
     * Data ringkas transaksi terbaru per tim untuk pop-up detail di halaman monitoring BBM.
     */
    public function detailTim(Tim $tim): JsonResponse
    {
        $this->pastikanAksesTim($tim->id);

        $tim->loadMissing(['distrik', 'drone', 'kepalaTim']);

        $totalMasukTerverifikasi = (float) TransaksiBbm::query()
            ->where('tim_id', $tim->id)
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->whereIn('jenis_transaksi', [TransaksiBbm::STOK_AWAL, TransaksiBbm::PENERIMAAN])
            ->sum('jumlah_liter');

        $totalKeluarTerverifikasi = (float) TransaksiBbm::query()
            ->where('tim_id', $tim->id)
            ->where('status_verifikasi', TransaksiBbm::TERVERIFIKASI)
            ->where('jenis_transaksi', TransaksiBbm::PENGGUNAAN)
            ->sum('jumlah_liter');

        $transaksi = TransaksiBbm::query()
            ->with('jenisBbm')
            ->where('tim_id', $tim->id)
            ->latest('tanggal_transaksi')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (TransaksiBbm $tx) {
                $tanda = match ($tx->jenis_transaksi) {
                    TransaksiBbm::STOK_AWAL, TransaksiBbm::PENERIMAAN => '+',
                    TransaksiBbm::PENGGUNAAN => '-',
                    TransaksiBbm::PENYESUAIAN => '±',
                    default => '',
                };

                $warnaStatus = match ($tx->status_verifikasi) {
                    TransaksiBbm::TERVERIFIKASI => 'hijau',
                    TransaksiBbm::MENUNGGU_PEMERIKSAAN => 'emas',
                    TransaksiBbm::PERLU_PERBAIKAN => 'merah',
                    default => 'abu',
                };

                return [
                    'nomor_transaksi' => $tx->nomor_transaksi,
                    'tanggal' => $tx->tanggal_transaksi ? $tx->tanggal_transaksi->translatedFormat('d M Y') : '-',
                    'jenis_bbm' => $tx->jenisBbm?->nama_bbm ?? '-',
                    'jenis_transaksi' => $tx->jenis_transaksi,
                    'label_jenis' => TransaksiBbm::jenisTransaksi()[$tx->jenis_transaksi] ?? $tx->jenis_transaksi,
                    'jumlah_format' => $tanda . number_format((float) $tx->jumlah_liter, 2, ',', '.') . ' L',
                    'keperluan' => $tx->labelKeperluan(),
                    'keterangan' => $tx->keterangan,
                    'status_verifikasi' => $tx->status_verifikasi,
                    'label_status' => TransaksiBbm::statusVerifikasi()[$tx->status_verifikasi] ?? $tx->status_verifikasi,
                    'warna_status' => $warnaStatus,
                    'url_show' => route('bbm.show', $tx),
                ];
            })
            ->values();

        return response()->json([
            'tim' => [
                'id' => $tim->id,
                'nama_tim' => $tim->nama_tim,
                'distrik' => $tim->distrik?->nama_distrik,
                'drone' => $tim->drone ? ($tim->drone->nomor_drone . ($tim->drone->nama_drone ? ' · ' . $tim->drone->nama_drone : '')) : null,
                'kepala_tim' => $tim->kepalaTim?->nama_lengkap,
                'total_masuk_format' => '+' . number_format($totalMasukTerverifikasi, 1, ',', '.') . ' L',
                'total_keluar_format' => '-' . number_format($totalKeluarTerverifikasi, 1, ',', '.') . ' L',
            ],
            'transaksi' => $transaksi,
        ]);
    }

    /**
     * Daftar jenis transaksi yang diizinkan sesuai peran pengguna.
     * Pekerja lapangan tidak boleh mencatat Stok Awal atau Koreksi Stok (±).
     */
    private function daftarJenisTransaksiUntukPengguna(Pengguna $pengguna): array
    {
        $jenis = TransaksiBbm::jenisTransaksi();

        if (! $pengguna->adalahAdmin()) {
            unset($jenis[TransaksiBbm::STOK_AWAL], $jenis[TransaksiBbm::PENYESUAIAN]);
        }

        return $jenis;
    }

    /**
     * Peta saldo per tim dan per jenis BBM untuk ditampilkan langsung di formulir input/ubah.
     */
    private function petaSaldoTim(?array $idTimBoleh): array
    {
        return $this->bbmService->ringkasanTim($idTimBoleh)
            ->mapWithKeys(fn (array $item) => [
                $item['tim']->id => [
                    'nama_tim' => $item['tim']->nama_tim,
                    'saldo' => $item['saldo'],
                    'saldo_per_jenis' => $item['saldo_per_jenis'],
                ],
            ])
            ->all();
    }

    /**
     * Menyamakan perhitungan harga satuan, keperluan default, dan total biaya pada store/update.
     */
    private function siapkanDataMutasi(array $data): array
    {
        $masterBbm = JenisBbm::find($data['jenis_bbm_id']);
        if (empty($data['harga_per_liter']) && $masterBbm) {
            $data['harga_per_liter'] = $masterBbm->harga_per_liter ?? 0;
        }

        if (in_array($data['jenis_transaksi'], [TransaksiBbm::STOK_AWAL, TransaksiBbm::PENERIMAAN], true)) {
            $data['keperluan'] = 'stok_masuk';
        } elseif ($data['jenis_transaksi'] === TransaksiBbm::PENGGUNAAN && empty($data['keperluan'])) {
            $data['keperluan'] = 'operasional_drone';
        }

        $data['total_biaya'] = ! empty($data['total_biaya'])
            ? (float) $data['total_biaya']
            : (! empty($data['harga_per_liter']) ? round((float) $data['jumlah_liter'] * (float) $data['harga_per_liter'], 2) : null);

        return $data;
    }

    /**
     * Menyimpan berkas foto struk / nota lampiran ke penyimpanan publik.
     */
    private function simpanLampiranBerkas(Request $request, TransaksiBbm $transaksi): void
    {
        if (! $request->hasFile('lampiran')) {
            return;
        }

        foreach ($request->file('lampiran') as $file) {
            $namaAsli = $file->getClientOriginalName();
            $path = $file->store('lampiran/bbm', 'public');

            LampiranBbm::create([
                'transaksi_bbm_id' => $transaksi->id,
                'nama_berkas_asli' => $namaAsli,
                'lokasi_berkas' => $path,
                'diunggah_oleh' => Auth::id(),
            ]);
        }
    }

    private function pastikanAksesTim(int $timId): void
    {
        $pengguna = Auth::user();
        $idTimBoleh = $pengguna->idTimYangDiakses();

        if ($idTimBoleh !== null && ! in_array($timId, $idTimBoleh, true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk transaksi BBM tim ini.');
        }
    }
}
