<?php

namespace App\Http\Controllers;

use App\Models\KasbonPersonil;
use App\Models\PengeluaranOperasionalUnit;
use App\Models\PenutupanPeriode;
use App\Models\Tim;
use App\Models\TransaksiBbm;
use App\Services\RiwayatAktivitasService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Pengelolaan Keuangan, Bon Operasional & Kasbon Kru Unit Drone.
 * Khusus Admin untuk mencatat pengeluaran unit sebelum diekspor ke format resmi 7-Sheet.
 */
class KeuanganUnitController extends Controller
{
    public function __construct(private readonly RiwayatAktivitasService $aktivitas) {}

    public function index(Request $request): View
    {
        $this->pastikanAksesAdmin();

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $timId = $request->input('tim_id');

        $daftarTim = Tim::where('status_tim', 'aktif')->with('drone')->orderBy('nama_tim')->get();
        if (! $timId && $daftarTim->isNotEmpty()) {
            $timId = $daftarTim->first()->id;
        }

        $daftarPengeluaran = PengeluaranOperasionalUnit::query()
            ->when($timId, fn ($q) => $q->where('tim_id', $timId))
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->with(['tim.drone', 'penginput'])
            ->orderBy('tanggal_transaksi')
            ->get();

        $daftarKasbon = KasbonPersonil::query()
            ->when($timId, fn ($q) => $q->where('tim_id', $timId))
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->with(['tim.drone', 'penginput'])
            ->orderBy('tanggal_kasbon')
            ->get();

        // Ambil transaksi BBM terverifikasi otomatis dari modul BBM untuk menghindari dobel catat
        $daftarBbm = TransaksiBbm::query()
            ->when($timId, fn ($q) => $q->where('tim_id', $timId))
            ->whereMonth('tanggal_transaksi', $bulan)
            ->whereYear('tanggal_transaksi', $tahun)
            ->where('status_verifikasi', 'diverifikasi')
            ->with(['jenisBbm', 'tim.drone'])
            ->orderBy('tanggal_transaksi')
            ->get();
        $totalBbmOtomatis = (float) $daftarBbm->sum('total_biaya');

        // Ringkasan nominal per kategori
        $totalOperasional = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::OPERASIONAL_LAPANGAN)->sum('nominal');
        $totalSparepart = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::SPAREPART)->sum('nominal');
        $totalRansumKantin = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::RANSUM_KANTIN)->sum('nominal');
        $totalRentalMobil = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::RENTAL_MOBIL)->sum('nominal');
        $totalUpahLangsir = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::UPAH_LANGSIR)->sum('nominal');
        $totalBonPribadi = (float) $daftarPengeluaran->where('kategori_biaya', PengeluaranOperasionalUnit::BON_PRIBADI)->sum('nominal');
        $totalKasbonKru = (float) $daftarKasbon->sum('jumlah_kasbon');

        $timTerpilih = $daftarTim->firstWhere('id', (int) $timId);

        // Status penutupan periode (closing)
        $penutupanPeriode = PenutupanPeriode::query()
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->when($timId, function ($q) use ($timId) {
                $q->where(function ($sub) use ($timId) {
                    $sub->where('tim_id', $timId)->orWhereNull('tim_id');
                });
            })
            ->with(['penutup', 'pembuka'])
            ->first();

        $isTerkunci = $penutupanPeriode?->status === PenutupanPeriode::STATUS_TERKUNCI;

        return view('keuangan.index', compact(
            'daftarTim', 'timId', 'timTerpilih', 'bulan', 'tahun',
            'daftarPengeluaran', 'daftarKasbon', 'daftarBbm', 'totalBbmOtomatis',
            'totalOperasional', 'totalSparepart', 'totalRansumKantin',
            'totalRentalMobil', 'totalUpahLangsir', 'totalBonPribadi', 'totalKasbonKru',
            'penutupanPeriode', 'isTerkunci'
        ));
    }

    public function createPengeluaran(Request $request): View|RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $daftarTim = Tim::where('status_tim', 'aktif')->with('drone')->orderBy('nama_tim')->get();
        $kategoriBiaya = PengeluaranOperasionalUnit::kategoriBiaya();
        $timId = $request->input('tim_id', $daftarTim->first()?->id);
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        if (PenutupanPeriode::apakahTerkunci($bulan, $tahun, $timId ? (int) $timId : null)) {
            return redirect()->route('keuangan.index', compact('timId', 'bulan', 'tahun'))
                ->with('galat', "Periode pembukuan bulan {$bulan}/{$tahun} telah ditutup (terkunci). Pembukuan harus dibuka kembali terlebih dahulu jika ingin menambah pengeluaran baru.");
        }

        return view('keuangan.form-pengeluaran', [
            'pengeluaran' => null,
            'daftarTim' => $daftarTim,
            'kategoriBiaya' => $kategoriBiaya,
            'timId' => $timId,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    }

    public function storePengeluaran(Request $request): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $data = $request->validate([
            'tim_id' => ['required', 'exists:tim,id'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'tanggal_transaksi' => ['required', 'date'],
            'kategori_biaya' => ['required', 'in:operasional_lapangan,sparepart,ransum_kantin,rental_mobil,upah_langsir,bon_pribadi,lain_lain'],
            'keterangan' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'nomor_bukti_pi' => ['nullable', 'string', 'max:100'],
            'lampiran_nota' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if (PenutupanPeriode::apakahTerkunci((int) $data['periode_bulan'], (int) $data['periode_tahun'], (int) $data['tim_id'])) {
            return back()->withInput()->with('galat', "Periode pembukuan bulan {$data['periode_bulan']}/{$data['periode_tahun']} telah ditutup (terkunci). Penambahan pengeluaran operasional baru dinonaktifkan.");
        }

        $path = null;
        if ($request->hasFile('lampiran_nota')) {
            $path = $request->file('lampiran_nota')->store('lampiran/keuangan', 'public');
        }

        $pengeluaran = PengeluaranOperasionalUnit::create([
            'tim_id' => $data['tim_id'],
            'periode_bulan' => $data['periode_bulan'],
            'periode_tahun' => $data['periode_tahun'],
            'tanggal_transaksi' => $data['tanggal_transaksi'],
            'kategori_biaya' => $data['kategori_biaya'],
            'keterangan' => $data['keterangan'],
            'nominal' => $data['nominal'],
            'nomor_bukti_pi' => $data['nomor_bukti_pi'] ?? null,
            'lampiran_nota' => $path,
            'diinput_oleh' => Auth::id(),
        ]);

        $this->aktivitas->catat(
            'Mencatat Pengeluaran Unit',
            $pengeluaran,
            "Kategori: {$pengeluaran->kategori_biaya}, Nominal: Rp " . number_format($pengeluaran->nominal, 0, ',', '.')
        );

        return redirect()->route('keuangan.index', [
            'tim_id' => $data['tim_id'],
            'bulan' => $data['periode_bulan'],
            'tahun' => $data['periode_tahun'],
        ])->with('sukses', 'Pengeluaran operasional unit berhasil dicatat.');
    }

    public function editPengeluaran(PengeluaranOperasionalUnit $pengeluaran): View|RedirectResponse
    {
        $this->pastikanAksesAdmin();

        if (PenutupanPeriode::apakahTerkunci($pengeluaran->periode_bulan, $pengeluaran->periode_tahun, $pengeluaran->tim_id)) {
            return redirect()->route('keuangan.index', [
                'tim_id' => $pengeluaran->tim_id,
                'bulan' => $pengeluaran->periode_bulan,
                'tahun' => $pengeluaran->periode_tahun,
            ])->with('galat', 'Pengeluaran ini berada pada periode pembukuan yang telah ditutup. Silakan buka kembali periode untuk mengubah data.');
        }

        $daftarTim = Tim::where('status_tim', 'aktif')->with('drone')->orderBy('nama_tim')->get();
        $kategoriBiaya = PengeluaranOperasionalUnit::kategoriBiaya();

        return view('keuangan.form-pengeluaran', [
            'pengeluaran' => $pengeluaran,
            'daftarTim' => $daftarTim,
            'kategoriBiaya' => $kategoriBiaya,
            'timId' => $pengeluaran->tim_id,
            'bulan' => $pengeluaran->periode_bulan,
            'tahun' => $pengeluaran->periode_tahun,
        ]);
    }

    public function updatePengeluaran(Request $request, PengeluaranOperasionalUnit $pengeluaran): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $data = $request->validate([
            'tim_id' => ['required', 'exists:tim,id'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'tanggal_transaksi' => ['required', 'date'],
            'kategori_biaya' => ['required', 'in:operasional_lapangan,sparepart,ransum_kantin,rental_mobil,upah_langsir,bon_pribadi,lain_lain'],
            'keterangan' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:1'],
            'nomor_bukti_pi' => ['nullable', 'string', 'max:100'],
            'lampiran_nota' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if (PenutupanPeriode::apakahTerkunci($pengeluaran->periode_bulan, $pengeluaran->periode_tahun, $pengeluaran->tim_id) ||
            PenutupanPeriode::apakahTerkunci((int) $data['periode_bulan'], (int) $data['periode_tahun'], (int) $data['tim_id'])) {
            return back()->withInput()->with('galat', 'Tidak dapat memperbarui pengeluaran pada periode pembukuan yang telah ditutup.');
        }

        $path = $pengeluaran->lampiran_nota;
        if ($request->hasFile('lampiran_nota')) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('lampiran_nota')->store('lampiran/keuangan', 'public');
        }

        $pengeluaran->update([
            'tim_id' => $data['tim_id'],
            'periode_bulan' => $data['periode_bulan'],
            'periode_tahun' => $data['periode_tahun'],
            'tanggal_transaksi' => $data['tanggal_transaksi'],
            'kategori_biaya' => $data['kategori_biaya'],
            'keterangan' => $data['keterangan'],
            'nominal' => $data['nominal'],
            'nomor_bukti_pi' => $data['nomor_bukti_pi'] ?? null,
            'lampiran_nota' => $path,
        ]);

        $this->aktivitas->catat(
            'Memperbarui Pengeluaran Unit',
            $pengeluaran,
            "Nominal: Rp " . number_format($pengeluaran->nominal, 0, ',', '.')
        );

        return redirect()->route('keuangan.index', [
            'tim_id' => $data['tim_id'],
            'bulan' => $data['periode_bulan'],
            'tahun' => $data['periode_tahun'],
        ])->with('sukses', 'Pengeluaran operasional unit berhasil diperbarui.');
    }

    public function destroyPengeluaran(PengeluaranOperasionalUnit $pengeluaran): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $timId = $pengeluaran->tim_id;
        $bulan = $pengeluaran->periode_bulan;
        $tahun = $pengeluaran->periode_tahun;

        if (PenutupanPeriode::apakahTerkunci($bulan, $tahun, $timId)) {
            return back()->with('galat', 'Tidak dapat menghapus pengeluaran pada periode pembukuan yang telah ditutup.');
        }

        if ($pengeluaran->lampiran_nota && Storage::disk('public')->exists($pengeluaran->lampiran_nota)) {
            Storage::disk('public')->delete($pengeluaran->lampiran_nota);
        }

        $pengeluaran->delete();

        $this->aktivitas->catat('Menghapus Pengeluaran Unit', null, "Keterangan: {$pengeluaran->keterangan}");

        return redirect()->route('keuangan.index', compact('timId', 'bulan', 'tahun'))
            ->with('sukses', 'Pengeluaran berhasil dihapus.');
    }

    public function createKasbon(Request $request): View|RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $daftarTim = Tim::where('status_tim', 'aktif')->with(['drone', 'anggota'])->orderBy('nama_tim')->get();
        $timId = $request->input('tim_id', $daftarTim->first()?->id);
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        if (PenutupanPeriode::apakahTerkunci($bulan, $tahun, $timId ? (int) $timId : null)) {
            return redirect()->route('keuangan.index', compact('timId', 'bulan', 'tahun'))
                ->with('galat', "Periode pembukuan bulan {$bulan}/{$tahun} telah ditutup (closing). Kasbon personil periode ini tetap terhitung dalam pembukuan, namun penambahan kasbon baru dilarang.");
        }

        return view('keuangan.form-kasbon', [
            'kasbon' => null,
            'daftarTim' => $daftarTim,
            'timId' => $timId,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    }

    public function storeKasbon(Request $request): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $data = $request->validate([
            'tim_id' => ['required', 'exists:tim,id'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'tanggal_kasbon' => ['required', 'date'],
            'nama_personil' => ['required', 'string', 'max:150'],
            'peran_kru' => ['required', 'in:pilot,helper,pelangsir,lainnya'],
            'jumlah_kasbon' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'status_lunas' => ['nullable', 'boolean'],
        ]);

        if (PenutupanPeriode::apakahTerkunci((int) $data['periode_bulan'], (int) $data['periode_tahun'], (int) $data['tim_id'])) {
            return back()->withInput()->with('galat', "Periode pembukuan bulan {$data['periode_bulan']}/{$data['periode_tahun']} telah ditutup (closing). Kasbon yang telah ada tetap terhitung dalam pembukuan, namun penambahan kasbon baru dilarang karena periode sudah ditutup.");
        }

        $kasbon = KasbonPersonil::create([
            'tim_id' => $data['tim_id'],
            'periode_bulan' => $data['periode_bulan'],
            'periode_tahun' => $data['periode_tahun'],
            'tanggal_kasbon' => $data['tanggal_kasbon'],
            'nama_personil' => $data['nama_personil'],
            'peran_kru' => $data['peran_kru'],
            'jumlah_kasbon' => $data['jumlah_kasbon'],
            'keterangan' => $data['keterangan'] ?? null,
            'status_lunas' => $request->boolean('status_lunas', false),
            'diinput_oleh' => Auth::id(),
        ]);

        $this->aktivitas->catat(
            'Mencatat Kasbon Personil',
            $kasbon,
            "{$kasbon->nama_personil} ({$kasbon->peran_kru}): Rp " . number_format($kasbon->jumlah_kasbon, 0, ',', '.')
        );

        return redirect()->route('keuangan.index', [
            'tim_id' => $data['tim_id'],
            'bulan' => $data['periode_bulan'],
            'tahun' => $data['periode_tahun'],
        ])->with('sukses', 'Kasbon personil berhasil dicatat.');
    }

    public function editKasbon(KasbonPersonil $kasbon): View|RedirectResponse
    {
        $this->pastikanAksesAdmin();

        if (PenutupanPeriode::apakahTerkunci($kasbon->periode_bulan, $kasbon->periode_tahun, $kasbon->tim_id)) {
            return redirect()->route('keuangan.index', [
                'tim_id' => $kasbon->tim_id,
                'bulan' => $kasbon->periode_bulan,
                'tahun' => $kasbon->periode_tahun,
            ])->with('galat', 'Kasbon ini berada pada periode pembukuan yang telah ditutup. Silakan buka kembali periode untuk mengubah data.');
        }

        $daftarTim = Tim::where('status_tim', 'aktif')->with('drone')->orderBy('nama_tim')->get();

        return view('keuangan.form-kasbon', [
            'kasbon' => $kasbon,
            'daftarTim' => $daftarTim,
            'timId' => $kasbon->tim_id,
            'bulan' => $kasbon->periode_bulan,
            'tahun' => $kasbon->periode_tahun,
        ]);
    }

    public function updateKasbon(Request $request, KasbonPersonil $kasbon): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $data = $request->validate([
            'tim_id' => ['required', 'exists:tim,id'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'tanggal_kasbon' => ['required', 'date'],
            'nama_personil' => ['required', 'string', 'max:150'],
            'peran_kru' => ['required', 'in:pilot,helper,pelangsir,lainnya'],
            'jumlah_kasbon' => ['required', 'numeric', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'status_lunas' => ['nullable', 'boolean'],
        ]);

        if (PenutupanPeriode::apakahTerkunci($kasbon->periode_bulan, $kasbon->periode_tahun, $kasbon->tim_id) ||
            PenutupanPeriode::apakahTerkunci((int) $data['periode_bulan'], (int) $data['periode_tahun'], (int) $data['tim_id'])) {
            return back()->withInput()->with('galat', 'Tidak dapat memperbarui kasbon pada periode pembukuan yang telah ditutup.');
        }

        $kasbon->update([
            'tim_id' => $data['tim_id'],
            'periode_bulan' => $data['periode_bulan'],
            'periode_tahun' => $data['periode_tahun'],
            'tanggal_kasbon' => $data['tanggal_kasbon'],
            'nama_personil' => $data['nama_personil'],
            'peran_kru' => $data['peran_kru'],
            'jumlah_kasbon' => $data['jumlah_kasbon'],
            'keterangan' => $data['keterangan'] ?? null,
            'status_lunas' => $request->boolean('status_lunas', false),
        ]);

        $this->aktivitas->catat(
            'Memperbarui Kasbon Personil',
            $kasbon,
            "{$kasbon->nama_personil}: Rp " . number_format($kasbon->jumlah_kasbon, 0, ',', '.')
        );

        return redirect()->route('keuangan.index', [
            'tim_id' => $data['tim_id'],
            'bulan' => $data['periode_bulan'],
            'tahun' => $data['periode_tahun'],
        ])->with('sukses', 'Kasbon personil berhasil diperbarui.');
    }

    public function toggleLunasKasbon(KasbonPersonil $kasbon): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        if (PenutupanPeriode::apakahTerkunci($kasbon->periode_bulan, $kasbon->periode_tahun, $kasbon->tim_id)) {
            return back()->with('galat', 'Tidak dapat mengubah status kasbon pada periode pembukuan yang telah ditutup.');
        }

        $kasbon->status_lunas = ! $kasbon->status_lunas;
        $kasbon->save();

        $statusTeks = $kasbon->status_lunas ? 'Lunas / Terpotong Gaji' : 'Belum Terpotong';
        $this->aktivitas->catat('Ubah Status Kasbon', $kasbon, "Status kasbon {$kasbon->nama_personil} diubah menjadi {$statusTeks}");

        return back()->with('sukses', "Status kasbon personil {$kasbon->nama_personil} berhasil diubah: {$statusTeks}.");
    }

    public function destroyKasbon(KasbonPersonil $kasbon): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $timId = $kasbon->tim_id;
        $bulan = $kasbon->periode_bulan;
        $tahun = $kasbon->periode_tahun;

        if (PenutupanPeriode::apakahTerkunci($bulan, $tahun, $timId)) {
            return back()->with('galat', 'Tidak dapat menghapus kasbon pada periode pembukuan yang telah ditutup.');
        }

        $kasbon->delete();

        $this->aktivitas->catat('Menghapus Kasbon Personil', null, "Nama: {$kasbon->nama_personil}");

        return redirect()->route('keuangan.index', compact('timId', 'bulan', 'tahun'))
            ->with('sukses', 'Kasbon berhasil dihapus.');
    }

    public function tutupPeriode(Request $request): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $data = $request->validate([
            'tim_id' => ['nullable', 'exists:tim,id'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $timId = ! empty($data['tim_id']) ? (int) $data['tim_id'] : null;

        $penutupan = PenutupanPeriode::updateOrCreate(
            [
                'tim_id' => $timId,
                'periode_bulan' => (int) $data['periode_bulan'],
                'periode_tahun' => (int) $data['periode_tahun'],
            ],
            [
                'status' => PenutupanPeriode::STATUS_TERKUNCI,
                'ditutup_oleh' => Auth::id(),
                'waktu_tutup' => Carbon::now(),
                'catatan' => $data['catatan'] ?? 'Penutupan periode pembukuan bulanan unit.',
            ]
        );

        $this->aktivitas->catat(
            'Tutup Periode Pembukuan',
            $penutupan,
            "Periode {$data['periode_bulan']}/{$data['periode_tahun']} berhasil ditutup dan dikunci."
        );

        return redirect()->route('keuangan.index', [
            'tim_id' => $timId,
            'bulan' => $data['periode_bulan'],
            'tahun' => $data['periode_tahun'],
        ])->with('sukses', "Periode pembukuan bulan {$data['periode_bulan']}/{$data['periode_tahun']} telah resmi ditutup (closing). Kasbon dan pengeluaran tetap masuk dalam pembukuan, namun penambahan data baru telah dikunci.");
    }

    public function bukaPeriode(Request $request): RedirectResponse
    {
        $this->pastikanAksesAdmin();

        $data = $request->validate([
            'tim_id' => ['nullable', 'exists:tim,id'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'alasan_buka' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $timId = ! empty($data['tim_id']) ? (int) $data['tim_id'] : null;

        $penutupan = PenutupanPeriode::where('periode_bulan', (int) $data['periode_bulan'])
            ->where('periode_tahun', (int) $data['periode_tahun'])
            ->when($timId, function ($q) use ($timId) {
                $q->where(function ($sub) use ($timId) {
                    $sub->where('tim_id', $timId)->orWhereNull('tim_id');
                });
            })
            ->first();

        if ($penutupan) {
            $penutupan->update([
                'status' => PenutupanPeriode::STATUS_TERBUKA,
                'dibuka_oleh' => Auth::id(),
                'waktu_buka' => Carbon::now(),
                'alasan_buka' => $data['alasan_buka'],
            ]);

            $this->aktivitas->catat(
                'Buka Kembali Periode Pembukuan',
                $penutupan,
                "Periode {$data['periode_bulan']}/{$data['periode_tahun']} dibuka kembali. Alasan: {$data['alasan_buka']}"
            );
        }

        return redirect()->route('keuangan.index', [
            'tim_id' => $timId,
            'bulan' => $data['periode_bulan'],
            'tahun' => $data['periode_tahun'],
        ])->with('sukses', "Periode pembukuan bulan {$data['periode_bulan']}/{$data['periode_tahun']} berhasil dibuka kembali. Anda sekarang dapat menambah atau mengubah data.");
    }

    private function pastikanAksesAdmin(): void
    {
        $pengguna = Auth::user();
        if (! $pengguna || (! $pengguna->adalahAdmin() && ! $pengguna->punyaPeran(\App\Models\Peran::ATASAN))) {
            abort(403, 'Hanya Admin atau Atasan yang berwenang mengelola keuangan dan bon unit operasional.');
        }
    }
}
