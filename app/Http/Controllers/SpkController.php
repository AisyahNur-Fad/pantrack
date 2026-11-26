<?php

namespace App\Http\Controllers;

use App\Models\DetailSpk;
use App\Models\Distrik;
use App\Models\JenisPekerjaan;
use App\Models\LaporanRealisasi;
use App\Models\Petak;
use App\Models\Pt;
use App\Models\Spk;
use App\Models\TarifPekerjaan;
use App\Models\Tim;
use App\Services\RiwayatAktivitasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengelolaan Surat Perintah Kerja (SPK) (FR-04, FR-05, FR-06, FR-07).
 *
 * Aturan penting:
 *  - Kepala Lapangan harus dan wajib membuatkan SPK sebelum pekerja mengerjakan laporan realisasi.
 *  - Target luas SPK tersimpan pada `detail_spk` dan tidak pernah disamakan dengan luas realisasi (BR-05).
 *  - SPK yang diterbitkan Kepala Lapangan langsung berstatus disetujui sehingga siap dikerjakan pekerja.
 *  - Pekerja lapangan melihat SPK yang telah diterbitkan untuk diinput realisasinya.
 */
class SpkController extends Controller
{
    public function __construct(private readonly RiwayatAktivitasService $aktivitas) {}

    public function index(Request $request): View
    {
        $pengguna = Auth::user();
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $distrikId = $request->input('distrik_id');
        $status = $request->input('status');
        $cari = trim((string) $request->input('cari'));

        $query = Spk::query()
            ->with(['distrik', 'jenisPekerjaan', 'penginput', 'penyetuju'])
            ->withCount('detail')
            ->when($bulan, fn ($q) => $q->where('periode_bulan', $bulan))
            ->when($tahun, fn ($q) => $q->where('periode_tahun', $tahun))
            ->when($distrikId, fn ($q) => $q->where('distrik_id', $distrikId))
            ->when($status, fn ($q) => $q->where('status_spk', $status))
            ->when($cari, fn ($q) => $q->where(fn ($sub) => $sub->where('nomor_spk', 'like', "%{$cari}%")->orWhere('keterangan', 'like', "%{$cari}%")));

        // Pekerja lapangan melihat SPK yang disetujui/aktif untuk dikerjakan jika tidak memilih filter status
        if ($pengguna->adalahPekerjaLapangan() && ! $status) {
            $query->where('status_spk', Spk::DISETUJUI);
        }

        $daftarSpk = $query->latest('id')->paginate(15)->withQueryString();
        $daftarDistrik = Distrik::where('aktif', true)->orderBy('nama_distrik')->get();

        return view('spk.index', compact('daftarSpk', 'daftarDistrik', 'bulan', 'tahun', 'distrikId', 'status', 'cari'));
    }

    public function create(): View
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahKepalaLapangan()) {
            abort(403, 'Hanya Kepala Lapangan yang berhak membuat SPK operasional.');
        }

        $daftarPt = Pt::where('aktif', true)->orderBy('nama_pt')->get();
        $daftarDistrik = Distrik::where('aktif', true)->with('pt')->orderBy('nama_distrik')->get();
        // Hanya pekerjaan pokok / aplikasi drone (tipe = utama) yang muncul di dropdown utama
        $daftarPekerjaan = JenisPekerjaan::utama()->where('aktif', true)->orderBy('nama_pekerjaan')->get();
        $daftarPetak = Petak::where('status_petak', 'aktif')->with('distrik')->orderBy('kode_petak')->get();
        $daftarTim = Tim::with('drone')->where('status_tim', 'aktif')->orderBy('nama_tim')->get();

        $tarifMappingDefault = $this->cariTarifMappingAktif(now()->toDateString());
        $mapTarifPekerjaan = $daftarPekerjaan->mapWithKeys(function ($p) {
            return [$p->id => (float) ($p->tarifAktif?->nilai_tarif ?? 0)];
        })->all();

        $mapSingkatanDistrik = $daftarDistrik->mapWithKeys(fn ($d) => [$d->id => $d->singkatan])->all();

        return view('spk.create', compact('daftarPt', 'daftarDistrik', 'daftarPekerjaan', 'daftarPetak', 'daftarTim', 'tarifMappingDefault', 'mapTarifPekerjaan', 'mapSingkatanDistrik'));
    }

    public function store(Request $request): RedirectResponse
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahKepalaLapangan()) {
            abort(403, 'Hanya Kepala Lapangan yang berhak membuat SPK operasional.');
        }

        // Kompatibilitas mundur: Jika pt_id tidak disertakan tetapi distrik_id ada, isi otomatis dari model Distrik
        if (! $request->filled('pt_id') && $request->filled('distrik_id')) {
            $distrikItem = Distrik::find($request->input('distrik_id'));
            if ($distrikItem && $distrikItem->pt_id) {
                $request->merge(['pt_id' => $distrikItem->pt_id]);
            }
        }

        $data = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:100', 'unique:spk,nomor_spk'],
            'tanggal_spk' => ['required', 'date'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'pt_id' => ['required', 'exists:pt,id'],
            'distrik_id' => [
                'required',
                'exists:distrik,id',
                Rule::exists('distrik', 'id')->where(fn ($q) => $q->where('pt_id', $request->input('pt_id'))),
            ],
            'tim_id' => ['nullable', 'exists:tim,id'],
            'jenis_pekerjaan_id' => ['nullable', 'exists:jenis_pekerjaan,id'],
            'berkas_spk' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'petak' => ['required', 'array', 'min:1'],
            'petak.*.petak_id' => [
                'nullable',
                Rule::exists('petak', 'id')->where(fn ($q) => $q->where('distrik_id', $request->input('distrik_id'))),
            ],
            'petak.*.nomor_petak_baru' => ['nullable', 'string', 'max:80'],
            'petak.*.luas_target_ha' => ['required', 'numeric', 'min:0.01'],
            'petak.*.perlu_mapping' => ['nullable'],
            'petak.*.catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'pt_id.required' => 'Pilih perusahaan (PT) terlebih dahulu.',
            'pt_id.exists' => 'Perusahaan (PT) yang dipilih tidak valid.',
            'distrik_id.required' => 'Pilih distrik operasional.',
            'distrik_id.exists' => 'Distrik yang dipilih tidak terdaftar pada perusahaan (PT) yang ditentukan.',
            'petak.required' => 'Minimal satu petak target harus ditambahkan ke dalam SPK.',
            'petak.*.petak_id.exists' => 'Petak yang dipilih tidak terdaftar pada distrik yang ditentukan.',
            'petak.*.luas_target_ha.required' => 'Isi luas target (Ha).',
        ]);

        foreach ($data['petak'] as $idx => $barisPetak) {
            $adaId = !empty($barisPetak['petak_id']);
            $adaBaru = !empty(trim((string) ($barisPetak['nomor_petak_baru'] ?? '')));
            if (!$adaId && !$adaBaru) {
                return back()->withInput()->withErrors([
                    "petak.{$idx}.petak_id" => 'Pilih petak terdaftar atau ketik nomor petak baru.',
                ]);
            }
        }

        if (!empty($data['tim_id'])) {
            $timPilihan = Tim::with('drone')->find($data['tim_id']);
            if ($timPilihan && $timPilihan->drone && in_array($timPilihan->drone->status_operasional, [\App\Models\Drone::STATUS_PERAWATAN, \App\Models\Drone::STATUS_TIDAK_BEROPERASI], true)) {
                $statusTeks = $timPilihan->drone->status_operasional === \App\Models\Drone::STATUS_PERAWATAN ? 'PERAWATAN' : 'TIDAK BEROPERASI';
                return back()->withInput()->withErrors([
                    'tim_id' => "Tim {$timPilihan->nama_tim} tidak dapat ditugaskan karena unit Drone {$timPilihan->drone->nomor_drone} sedang dalam status {$statusTeks}" . ($timPilihan->drone->catatan_perawatan ? " ({$timPilihan->drone->catatan_perawatan})" : "") . ".",
                ]);
            }
        }

        $berkasPath = null;

        if ($request->hasFile('berkas_spk')) {
            $berkasPath = $request->file('berkas_spk')->store('lampiran/spk', 'public');
        }

        DB::transaction(function () use ($data, $berkasPath, $pengguna, &$spk) {
            $distrik = Distrik::findOrFail($data['distrik_id']);
            $prefixSingkat = $distrik->singkatan;
            $prefixLengkap = strtoupper(trim((string) $distrik->kode_distrik));

            $tarifAktif = $this->cariTarifAktif($data['jenis_pekerjaan_id'] ?? null, (string) $data['tanggal_spk']);
            $tarifMapping = $this->cariTarifMappingAktif((string) $data['tanggal_spk']);

            $spk = Spk::create([
                'nomor_spk' => $data['nomor_spk'],
                'tanggal_spk' => $data['tanggal_spk'],
                'periode_bulan' => $data['periode_bulan'],
                'periode_tahun' => $data['periode_tahun'],
                'distrik_id' => $data['distrik_id'],
                'tim_id' => $data['tim_id'] ?? null,
                'jenis_pekerjaan_id' => $data['jenis_pekerjaan_id'] ?? null,
                'diinput_oleh' => $pengguna->id,
                'disetujui_oleh' => null,
                'tanggal_persetujuan' => null,
                'status_spk' => Spk::MENUNGGU_PERSETUJUAN,
                'berkas_spk' => $berkasPath,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            foreach ($data['petak'] as $item) {
                $petakId = $item['petak_id'] ?? null;
                $nomorBaru = trim((string) ($item['nomor_petak_baru'] ?? ''));

                if (empty($petakId) && !empty($nomorBaru)) {
                    $nomorBaruBersih = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $nomorBaru));
                    if ($prefixLengkap !== '' && str_starts_with($nomorBaruBersih, $prefixLengkap)) {
                        $kodePetakFinal = $nomorBaruBersih;
                    } elseif ($prefixSingkat !== '' && str_starts_with($nomorBaruBersih, $prefixSingkat)) {
                        $kodePetakFinal = $nomorBaruBersih;
                    } else {
                        $kodePetakFinal = $prefixSingkat . $nomorBaruBersih;
                    }

                    $petakRecord = Petak::firstOrCreate(
                        ['distrik_id' => $distrik->id, 'kode_petak' => $kodePetakFinal],
                        [
                            'nama_petak' => 'Petak ' . $kodePetakFinal,
                            'luas_petak_ha' => (float) $item['luas_target_ha'],
                            'status_petak' => Petak::STATUS_AKTIF,
                        ]
                    );
                    $petakId = $petakRecord->id;
                }

                $perluMapping = !empty($item['perlu_mapping']);
                DetailSpk::create([
                    'spk_id' => $spk->id,
                    'petak_id' => $petakId,
                    'luas_target_ha' => $item['luas_target_ha'],
                    'tarif_per_ha' => $tarifAktif,
                    'perlu_mapping' => $perluMapping,
                    'tarif_mapping_per_ha' => $perluMapping ? $tarifMapping : null,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }
        });

        $this->aktivitas->catat('Mengajukan SPK', $spk, "Nomor SPK: {$spk->nomor_spk}");

        return redirect()->route('spk.show', $spk)->with('sukses', "SPK {$spk->nomor_spk} berhasil diajukan dan menunggu konfirmasi/penilaian Admin.");
    }

    public function show(Spk $spk): View
    {
        $this->pastikanAksesSpk($spk);

        $spk->load(['distrik', 'tim', 'jenisPekerjaan', 'penginput', 'penyetuju', 'detail.petak', 'laporanRealisasi.tim']);

        $totalLuasTarget = (float) $spk->detail->sum('luas_target_ha');

        $realisasiDisetujuiPerPetak = DB::table('detail_realisasi as d')
            ->join('laporan_realisasi as l', 'l.id', '=', 'd.laporan_realisasi_id')
            ->where('l.spk_id', $spk->id)
            ->where('l.status_laporan', LaporanRealisasi::DISETUJUI)
            ->whereNotNull('d.petak_id')
            ->selectRaw('d.petak_id, SUM(d.luas_realisasi_ha) as total_realisasi')
            ->groupBy('d.petak_id')
            ->pluck('total_realisasi', 'd.petak_id');

        $totalLuasRealisasi = (float) DB::table('detail_realisasi as d')
            ->join('laporan_realisasi as l', 'l.id', '=', 'd.laporan_realisasi_id')
            ->where('l.spk_id', $spk->id)
            ->where('l.status_laporan', LaporanRealisasi::DISETUJUI)
            ->sum('d.luas_realisasi_ha');

        return view('spk.show', compact('spk', 'totalLuasTarget', 'realisasiDisetujuiPerPetak', 'totalLuasRealisasi'));
    }

    public function edit(Spk $spk): View
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahKepalaLapangan()) {
            abort(403, 'Hanya Kepala Lapangan yang berhak mengubah SPK.');
        }

        if ($spk->laporanRealisasi()->exists()) {
            abort(403, 'SPK yang sudah memiliki laporan realisasi tidak dapat diubah lagi.');
        }

        $daftarPt = Pt::where('aktif', true)->orderBy('nama_pt')->get();
        $daftarDistrik = Distrik::where('aktif', true)->with('pt')->orderBy('nama_distrik')->get();
        // Hanya pekerjaan pokok / aplikasi drone yang muncul di dropdown utama
        $daftarPekerjaan = JenisPekerjaan::utama()->where('aktif', true)->orderBy('nama_pekerjaan')->get();
        $daftarPetak = Petak::where('status_petak', 'aktif')->with('distrik')->orderBy('kode_petak')->get();
        $daftarTim = Tim::with('drone')->where('status_tim', 'aktif')->orderBy('nama_tim')->get();

        $spk->load(['detail.petak', 'distrik.pt']);
        $selectedPtId = $spk->distrik?->pt_id;

        $tarifMappingDefault = $this->cariTarifMappingAktif($spk->tanggal_spk?->toDateString() ?: now()->toDateString());
        $mapTarifPekerjaan = $daftarPekerjaan->mapWithKeys(function ($p) {
            return [$p->id => (float) ($p->tarifAktif?->nilai_tarif ?? 0)];
        })->all();

        $mapSingkatanDistrik = $daftarDistrik->mapWithKeys(fn ($d) => [$d->id => $d->singkatan])->all();

        return view('spk.edit', compact('spk', 'daftarPt', 'daftarDistrik', 'daftarPekerjaan', 'daftarPetak', 'daftarTim', 'selectedPtId', 'tarifMappingDefault', 'mapTarifPekerjaan', 'mapSingkatanDistrik'));
    }

    public function update(Request $request, Spk $spk): RedirectResponse
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahKepalaLapangan()) {
            abort(403, 'Hanya Kepala Lapangan yang berhak mengubah SPK.');
        }

        if ($spk->laporanRealisasi()->exists()) {
            abort(403, 'SPK yang sudah memiliki laporan realisasi tidak dapat diubah lagi.');
        }

        // Kompatibilitas mundur: Jika pt_id tidak disertakan tetapi distrik_id ada, isi otomatis dari model Distrik
        if (! $request->filled('pt_id') && $request->filled('distrik_id')) {
            $distrikItem = Distrik::find($request->input('distrik_id'));
            if ($distrikItem && $distrikItem->pt_id) {
                $request->merge(['pt_id' => $distrikItem->pt_id]);
            }
        }

        $data = $request->validate([
            'nomor_spk' => ['required', 'string', 'max:100', 'unique:spk,nomor_spk,'.$spk->id],
            'tanggal_spk' => ['required', 'date'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2035'],
            'pt_id' => ['required', 'exists:pt,id'],
            'distrik_id' => [
                'required',
                'exists:distrik,id',
                Rule::exists('distrik', 'id')->where(fn ($q) => $q->where('pt_id', $request->input('pt_id'))),
            ],
            'tim_id' => ['nullable', 'exists:tim,id'],
            'jenis_pekerjaan_id' => ['nullable', 'exists:jenis_pekerjaan,id'],
            'berkas_spk' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'petak' => ['required', 'array', 'min:1'],
            'petak.*.petak_id' => [
                'nullable',
                Rule::exists('petak', 'id')->where(fn ($q) => $q->where('distrik_id', $request->input('distrik_id'))),
            ],
            'petak.*.nomor_petak_baru' => ['nullable', 'string', 'max:80'],
            'petak.*.luas_target_ha' => ['required', 'numeric', 'min:0.01'],
            'petak.*.perlu_mapping' => ['nullable'],
            'petak.*.catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'pt_id.required' => 'Pilih perusahaan (PT) terlebih dahulu.',
            'pt_id.exists' => 'Perusahaan (PT) yang dipilih tidak valid.',
            'distrik_id.required' => 'Pilih distrik operasional.',
            'distrik_id.exists' => 'Distrik yang dipilih tidak terdaftar pada perusahaan (PT) yang ditentukan.',
            'petak.required' => 'Minimal satu petak target harus ditambahkan ke dalam SPK.',
            'petak.*.petak_id.exists' => 'Petak yang dipilih tidak terdaftar pada distrik yang ditentukan.',
            'petak.*.luas_target_ha.required' => 'Isi luas target (Ha).',
        ]);

        foreach ($data['petak'] as $idx => $barisPetak) {
            $adaId = !empty($barisPetak['petak_id']);
            $adaBaru = !empty(trim((string) ($barisPetak['nomor_petak_baru'] ?? '')));
            if (!$adaId && !$adaBaru) {
                return back()->withInput()->withErrors([
                    "petak.{$idx}.petak_id" => 'Pilih petak terdaftar atau ketik nomor petak baru.',
                ]);
            }
        }

        if (!empty($data['tim_id'])) {
            $timPilihan = Tim::with('drone')->find($data['tim_id']);
            if ($timPilihan && $timPilihan->drone && in_array($timPilihan->drone->status_operasional, [\App\Models\Drone::STATUS_PERAWATAN, \App\Models\Drone::STATUS_TIDAK_BEROPERASI], true)) {
                $statusTeks = $timPilihan->drone->status_operasional === \App\Models\Drone::STATUS_PERAWATAN ? 'PERAWATAN' : 'TIDAK BEROPERASI';
                return back()->withInput()->withErrors([
                    'tim_id' => "Tim {$timPilihan->nama_tim} tidak dapat ditugaskan karena unit Drone {$timPilihan->drone->nomor_drone} sedang dalam status {$statusTeks}" . ($timPilihan->drone->catatan_perawatan ? " ({$timPilihan->drone->catatan_perawatan})" : "") . ".",
                ]);
            }
        }

        if ($request->hasFile('berkas_spk')) {
            if ($spk->berkas_spk) {
                Storage::disk('public')->delete($spk->berkas_spk);
            }
            $spk->berkas_spk = $request->file('berkas_spk')->store('lampiran/spk', 'public');
        }

        DB::transaction(function () use ($data, $spk) {
            $distrik = Distrik::findOrFail($data['distrik_id']);
            $prefixSingkat = $distrik->singkatan;
            $prefixLengkap = strtoupper(trim((string) $distrik->kode_distrik));

            $spk->update([
                'nomor_spk' => $data['nomor_spk'],
                'tanggal_spk' => $data['tanggal_spk'],
                'periode_bulan' => $data['periode_bulan'],
                'periode_tahun' => $data['periode_tahun'],
                'distrik_id' => $data['distrik_id'],
                'tim_id' => $data['tim_id'] ?? null,
                'jenis_pekerjaan_id' => $data['jenis_pekerjaan_id'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Replace detail target petak
            $tarifAktif = $this->cariTarifAktif($data['jenis_pekerjaan_id'] ?? null, (string) $data['tanggal_spk']);
            $tarifMapping = $this->cariTarifMappingAktif((string) $data['tanggal_spk']);
            $spk->detail()->delete();
            foreach ($data['petak'] as $item) {
                $petakId = $item['petak_id'] ?? null;
                $nomorBaru = trim((string) ($item['nomor_petak_baru'] ?? ''));

                if (empty($petakId) && !empty($nomorBaru)) {
                    $nomorBaruBersih = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $nomorBaru));
                    if ($prefixLengkap !== '' && str_starts_with($nomorBaruBersih, $prefixLengkap)) {
                        $kodePetakFinal = $nomorBaruBersih;
                    } elseif ($prefixSingkat !== '' && str_starts_with($nomorBaruBersih, $prefixSingkat)) {
                        $kodePetakFinal = $nomorBaruBersih;
                    } else {
                        $kodePetakFinal = $prefixSingkat . $nomorBaruBersih;
                    }

                    $petakRecord = Petak::firstOrCreate(
                        ['distrik_id' => $distrik->id, 'kode_petak' => $kodePetakFinal],
                        [
                            'nama_petak' => 'Petak ' . $kodePetakFinal,
                            'luas_petak_ha' => (float) $item['luas_target_ha'],
                            'status_petak' => Petak::STATUS_AKTIF,
                        ]
                    );
                    $petakId = $petakRecord->id;
                }

                $perluMapping = !empty($item['perlu_mapping']);
                DetailSpk::create([
                    'spk_id' => $spk->id,
                    'petak_id' => $petakId,
                    'luas_target_ha' => $item['luas_target_ha'],
                    'tarif_per_ha' => $tarifAktif,
                    'perlu_mapping' => $perluMapping,
                    'tarif_mapping_per_ha' => $perluMapping ? $tarifMapping : null,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }
        });

        $this->aktivitas->catat('Memperbarui SPK', $spk, "Nomor SPK: {$spk->nomor_spk}");

        return redirect()->route('spk.show', $spk)->with('sukses', "SPK {$spk->nomor_spk} berhasil diperbarui.");
    }

    public function destroy(Spk $spk): RedirectResponse
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahKepalaLapangan()) {
            abort(403, 'Hanya Kepala Lapangan yang berhak menghapus SPK.');
        }

        if ($spk->laporanRealisasi()->exists()) {
            return back()->withErrors(['error' => 'SPK tidak dapat dihapus karena sudah memiliki laporan realisasi.']);
        }

        $nomor = $spk->nomor_spk;
        DB::transaction(function () use ($spk) {
            $spk->detail()->delete();
            if ($spk->berkas_spk) {
                Storage::disk('public')->delete($spk->berkas_spk);
            }
            $spk->delete();
        });

        $this->aktivitas->catat('Menghapus SPK', null, "Nomor SPK: {$nomor}");

        return redirect()->route('spk.index')->with('sukses', "SPK {$nomor} berhasil dihapus.");
    }

    public function selesai(Spk $spk): RedirectResponse
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahKepalaLapangan()) {
            abort(403, 'Hanya Kepala Lapangan yang berhak menyelesaikan atau menutup SPK.');
        }

        if ($spk->status_spk !== Spk::DISETUJUI) {
            return back()->withErrors(['error' => 'Hanya SPK yang sedang berstatus disetujui yang dapat diselesaikan.']);
        }

        $spk->update([
            'status_spk' => Spk::SELESAI,
        ]);

        $this->aktivitas->catat('Menutup SPK (Selesai)', $spk, "Nomor SPK: {$spk->nomor_spk}");

        return back()->with('sukses', "SPK {$spk->nomor_spk} telah ditandai selesai dan ditutup dari antrian pengerjaan.");
    }

    /**
     * Konfirmasi dan penilaian SPK oleh Admin atau Atasan (FR-05).
     */
    public function verifikasi(Request $request, Spk $spk): RedirectResponse
    {
        $pengguna = Auth::user();
        if (! $pengguna->adalahAdmin() && ! $pengguna->adalahAtasan()) {
            abort(403, 'Hanya Admin atau Atasan yang berhak mengonfirmasi dan menilai SPK.');
        }

        $data = $request->validate([
            'tindakan' => ['required', 'in:setujui,perbaikan,tolak'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $statusBaru = match ($data['tindakan']) {
            'setujui' => Spk::DISETUJUI,
            'perbaikan' => Spk::PERLU_PERBAIKAN,
            'tolak' => Spk::DITOLAK,
        };

        if ($statusBaru === Spk::PERLU_PERBAIKAN && empty(trim((string) $data['catatan']))) {
            return back()->withErrors(['catatan' => 'Catatan perbaikan wajib diisi ketika meminta perbaikan.']);
        }

        $spk->update([
            'status_spk' => $statusBaru,
            'disetujui_oleh' => $pengguna->id,
            'tanggal_persetujuan' => now(),
            'catatan_perbaikan' => $data['catatan'] ?? null,
        ]);

        $this->aktivitas->catat(
            "Verifikasi SPK: {$statusBaru}",
            $spk,
            "Tindakan oleh {$pengguna->nama_lengkap}. Catatan: ".($data['catatan'] ?? '-')
        );

        return back()->with('sukses', "Status SPK berhasil diperbarui menjadi {$statusBaru}.");
    }

    public function unduhBerkas(Spk $spk): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->pastikanAksesSpk($spk);

        if (! $spk->berkas_spk || ! Storage::disk('public')->exists($spk->berkas_spk)) {
            abort(404, 'Berkas SPK tidak ditemukan di penyimpanan server.');
        }

        $pathAbsolut = Storage::disk('public')->path($spk->berkas_spk);

        return response()->file($pathAbsolut);
    }

    private function cariTarifAktif(?int $jenisPekerjaanId, string $tanggalSpk): ?float
    {
        if (! $jenisPekerjaanId) {
            return null;
        }

        $tarif = TarifPekerjaan::where('jenis_pekerjaan_id', $jenisPekerjaanId)
            ->where('berlaku_mulai', '<=', $tanggalSpk)
            ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $tanggalSpk))
            ->latest('berlaku_mulai')
            ->value('nilai_tarif');

        if (! $tarif) {
            $tarif = TarifPekerjaan::where('jenis_pekerjaan_id', $jenisPekerjaanId)
                ->latest('berlaku_mulai')
                ->value('nilai_tarif');
        }

        return $tarif ? (float) $tarif : null;
    }

    private function cariTarifMappingAktif(string $tanggalSpk): float
    {
        $pekerjaanMapping = JenisPekerjaan::addon()->where('kode_pekerjaan', 'SRV-MAPPING')->first();
        if ($pekerjaanMapping) {
            $tarif = $this->cariTarifAktif($pekerjaanMapping->id, $tanggalSpk);
            if ($tarif !== null) {
                return (float) $tarif;
            }
        }

        return 30000.00;
    }

    private function pastikanAksesSpk(Spk $spk): void
    {
        $pengguna = Auth::user();
        if (! $pengguna->punyaPeran(\App\Models\Peran::ADMIN, \App\Models\Peran::PEKERJA_LAPANGAN, \App\Models\Peran::KEPALA_LAPANGAN, \App\Models\Peran::ATASAN)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat SPK ini.');
        }
    }
}
