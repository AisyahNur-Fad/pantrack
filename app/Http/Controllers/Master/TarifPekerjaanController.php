<?php

namespace App\Http\Controllers\Master;

use App\Models\JenisPekerjaan;
use App\Models\Spk;
use App\Models\TarifPekerjaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Riwayat tarif pekerjaan. Tarif hanya dicatat; perhitungan HARGA/TOTAL belum dilakukan
 * karena rumus resmi perusahaan belum ditetapkan (PRD bab 14).
 */
class TarifPekerjaanController extends MasterDataController
{
    protected function modelClass(): string
    {
        return TarifPekerjaan::class;
    }

    protected function judul(): string
    {
        return 'Tarif Pekerjaan';
    }

    protected function prefixRute(): string
    {
        return 'master.tarif-pekerjaan';
    }

    protected function ikon(): string
    {
        return 'bi-cash-coin';
    }

    protected function deskripsi(): string
    {
        return 'Riwayat tarif per jenis pekerjaan beserta masa berlakunya. Terbagi menjadi Tarif Aktif dan Arsip Riwayat.';
    }

    public function index(Request $request): View
    {
        $response = parent::index($request);
        $data = $response->getData();

        $today = now()->toDateString();
        $jenisId = $request->query('jenis_pekerjaan_id');

        // 1. Query Tarif Aktif (Berlaku hari ini: berlaku_mulai <= today dan belum berakhir)
        $queryAktif = TarifPekerjaan::with(['jenisPekerjaan', 'pembuat'])
            ->where('berlaku_mulai', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $today);
            })
            ->when($jenisId, fn ($q) => $q->where('jenis_pekerjaan_id', $jenisId))
            ->orderBy('jenis_pekerjaan_id', 'asc')
            ->orderBy('berlaku_mulai', 'desc');

        // 2. Query Riwayat Tarif (Masa lalu: berlaku_sampai < today)
        $queryRiwayat = TarifPekerjaan::with(['jenisPekerjaan', 'pembuat'])
            ->whereNotNull('berlaku_sampai')
            ->where('berlaku_sampai', '<', $today)
            ->when($jenisId, fn ($q) => $q->where('jenis_pekerjaan_id', $jenisId))
            ->orderBy('berlaku_sampai', 'desc');

        // Pencarian kata kunci
        $kataKunci = trim((string) $request->query('cari'));
        if ($kataKunci !== '') {
            $pola = '%' . addcslashes($kataKunci, '%_\\') . '%';
            $filterCari = function ($q) use ($pola) {
                $q->whereHas('jenisPekerjaan', function ($jq) use ($pola) {
                    $jq->where('nama_pekerjaan', 'like', $pola)
                       ->orWhere('kode_pekerjaan', 'like', $pola);
                })->orWhere('keterangan', 'like', $pola);
            };
            $queryAktif->where($filterCari);
            $queryRiwayat->where($filterCari);
        }

        $tarifAktif = $queryAktif->get();
        $riwayatTarif = $queryRiwayat->get();

        $data['tarifAktif'] = $tarifAktif;
        $data['riwayatTarif'] = $riwayatTarif;

        // Metrik Ringkasan Tarif
        $data['ringkasanTarif'] = [
            'total' => TarifPekerjaan::count(),
            'total_aktif' => TarifPekerjaan::where('berlaku_mulai', '<=', $today)
                ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $today))
                ->count(),
            'total_riwayat' => TarifPekerjaan::whereNotNull('berlaku_sampai')
                ->where('berlaku_sampai', '<', $today)
                ->count(),
            'total_jenis' => JenisPekerjaan::count(),
        ];

        return view('master.index', $data);
    }

    protected function relasi(): array
    {
        return ['jenisPekerjaan', 'pembuat'];
    }

    protected function urutan(): array
    {
        return ['jenis_pekerjaan_id' => 'asc', 'berlaku_mulai' => 'desc'];
    }

    protected function filter(): array
    {
        return [
            ['name' => 'jenis_pekerjaan_id', 'label' => 'Semua pekerjaan', 'options' => fn () => $this->opsiJenisPekerjaan()],
        ];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Jenis pekerjaan', 'key' => 'jenisPekerjaan.nama_pekerjaan'],
            ['label' => 'Tarif per Ha', 'key' => 'nilai_tarif', 'tipe' => 'rupiah'],
            ['label' => 'Berlaku mulai', 'key' => 'berlaku_mulai', 'tipe' => 'tanggal'],
            ['label' => 'Berlaku sampai', 'key' => 'berlaku_sampai', 'tipe' => 'tanggal'],
            ['label' => 'Dibuat oleh', 'key' => 'pembuat.nama_lengkap'],
        ];
    }

    public function create(): View
    {
        $gantiJenisId = request()->query('ganti_jenis_id') ?: request()->query('jenis_pekerjaan_id');
        $tarifAktif = null;

        if ($gantiJenisId) {
            $jenis = JenisPekerjaan::find($gantiJenisId);
            $tarifAktif = $jenis?->tarifAktif;
        }

        $view = parent::create();
        $data = $view->getData();
        $data['tarifAktifTerdahulu'] = $tarifAktif;

        return view('master.form', $data);
    }

    public function edit(string $id): View
    {
        $record = TarifPekerjaan::with('jenisPekerjaan')->findOrFail($id);
        $terkunciSpk = $record->digunakanDiSpk();

        $view = parent::edit($id);
        $data = $view->getData();
        $data['terkunciSpk'] = $terkunciSpk;

        return view('master.form', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request, null);

        $record = DB::transaction(function () use ($data) {
            $mulai = $data['berlaku_mulai'];

            // AUTO-ARCHIVE: Cari tarif sebelumnya yang masih aktif atau terbuka untuk jenis pekerjaan ini
            $tarifLamaList = TarifPekerjaan::where('jenis_pekerjaan_id', $data['jenis_pekerjaan_id'])
                ->where('berlaku_mulai', '<', $mulai)
                ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $mulai))
                ->get();

            foreach ($tarifLamaList as $tarifLama) {
                $tglTutup = \Carbon\Carbon::parse($mulai)->subDay()->toDateString();
                $nominalBaruFormatted = 'Rp '.number_format((float) $data['nilai_tarif'], 0, ',', '.');
                $catatanArsip = 'Otomatis diarsipkan saat penetapan tarif baru '.$nominalBaruFormatted.' per '.$mulai;

                $ketLama = trim((string) $tarifLama->keterangan);
                $ketBaru = $ketLama !== '' ? $ketLama.' | ['.$catatanArsip.']' : '['.$catatanArsip.']';

                $tarifLama->update([
                    'berlaku_sampai' => $tglTutup,
                    'keterangan' => $ketBaru,
                ]);

                $this->aktivitas->catat(
                    'Mengarsipkan tarif terdahulu',
                    $tarifLama,
                    $this->label($tarifLama).' diakhiri sampai '.$tglTutup.' karena pembaruan tarif baru '.$nominalBaruFormatted
                );
            }

            $record = TarifPekerjaan::create($data);
            $this->setelahSimpan($record, $data, true);
            $this->aktivitas->catat('Menambah data '.$this->judul(), $record, $this->label($record));

            return $record;
        });

        $namaPekerjaan = $record->jenisPekerjaan?->nama_pekerjaan ?? 'Pekerjaan';
        $nominal = 'Rp '.number_format((float) $record->nilai_tarif, 0, ',', '.');
        $tglMulai = \Carbon\Carbon::parse($record->berlaku_mulai)->isoFormat('D MMMM Y');

        return redirect()->route($this->prefixRute().'.index')
            ->with('sukses', "Tarif baru untuk {$namaPekerjaan} ({$nominal} / Ha) berhasil ditetapkan mulai {$tglMulai}. Tarif lama otomatis diarsipkan ke Tabel Riwayat.");
    }

    public function destroy(string $id): RedirectResponse
    {
        $record = TarifPekerjaan::with('jenisPekerjaan')->findOrFail($id);

        if ($record->digunakanDiSpk()) {
            return back()->with('galat', 'Tarif '.$this->label($record).' tidak dapat dihapus karena telah digunakan pada dokumen SPK operasional. Data ini wajib dipertahankan sebagai arsip audit historis.');
        }

        return parent::destroy($id);
    }

    protected function field(): array
    {
        $gantiJenisId = request()->query('ganti_jenis_id') ?: request()->query('jenis_pekerjaan_id');

        return [
            [
                'name' => 'jenis_pekerjaan_id',
                'label' => 'Jenis pekerjaan',
                'tipe' => 'pilih',
                'wajib' => true,
                'default' => $gantiJenisId,
                'options' => fn () => $this->opsiJenisPekerjaan(),
            ],
            [
                'name' => 'nilai_tarif',
                'label' => 'Tarif per Ha (Rp)',
                'tipe' => 'angka',
                'step' => '0.01',
                'wajib' => true,
            ],
            [
                'name' => 'berlaku_mulai',
                'label' => 'Berlaku mulai',
                'tipe' => 'tanggal',
                'wajib' => true,
                'default' => now()->toDateString(),
            ],
            [
                'name' => 'berlaku_sampai',
                'label' => 'Berlaku sampai',
                'tipe' => 'tanggal',
                'bantuan' => 'Kosongkan bila tarif berlaku tanpa batas akhir (otomatis diarsipkan saat ada pembaruan tarif baru).',
            ],
            [
                'name' => 'keterangan',
                'label' => 'Keterangan / Dasar Penetapan Tarif',
                'tipe' => 'area',
                'lebar' => 'penuh',
            ],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'jenis_pekerjaan_id' => ['required', 'exists:jenis_pekerjaan,id'],
            'nilai_tarif' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'berlaku_mulai' => ['required', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:berlaku_mulai'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** Pembuat tarif dicatat otomatis saat data baru dibuat. */
    protected function olahData(array $data, ?Model $record): array
    {
        if (! $record) {
            $data['dibuat_oleh'] = Auth::id();
        }

        return $data;
    }

    /**
     * Validasi tumpang tindih dan proteksi integritas finansial SPK (Immutable Lock).
     */
    protected function validasiTambahan(array $data, ?Model $record): void
    {
        $mulai = $data['berlaku_mulai'];
        $sampai = $data['berlaku_sampai'] ?? null;
        $jenisId = $data['jenis_pekerjaan_id'];

        if (! $record) {
            // MODE TAMBAH / GANTI TARIF BARU:
            // 1. Cek apakah sudah ada tarif yang mulai berlaku pada tanggal yang persis sama
            $adaTanggalSama = TarifPekerjaan::where('jenis_pekerjaan_id', $jenisId)
                ->where('berlaku_mulai', $mulai)
                ->exists();

            if ($adaTanggalSama) {
                throw ValidationException::withMessages([
                    'berlaku_mulai' => 'Sudah ada tarif untuk jenis pekerjaan ini yang mulai berlaku pada tanggal '.\Carbon\Carbon::parse($mulai)->format('d/m/Y').'. Gunakan menu Koreksi jika ingin mengubah tarif tersebut.',
                ]);
            }

            // 2. Cek apakah ada tarif yang sudah dijadwalkan di masa depan melebihi $mulai
            $adaTarifLebihBaru = TarifPekerjaan::where('jenis_pekerjaan_id', $jenisId)
                ->where('berlaku_mulai', '>', $mulai)
                ->when($sampai, fn ($q) => $q->where('berlaku_mulai', '<=', $sampai))
                ->first();

            if ($adaTarifLebihBaru && ! $sampai) {
                throw ValidationException::withMessages([
                    'berlaku_mulai' => 'Terdapat tarif yang mulai berlaku setelah tanggal ini (mulai '.\Carbon\Carbon::parse($adaTarifLebihBaru->berlaku_mulai)->format('d/m/Y').'). Tentukan tanggal "Berlaku sampai" agar tidak tumpang tindih.',
                ]);
            }
        } else {
            // MODE KOREKSI / UBAH DATA TARIF EKSIS:
            // 1. Proteksi Immutability jika tarif sudah dipakai di transaksi SPK
            if ($record instanceof TarifPekerjaan && $record->digunakanDiSpk()) {
                if (abs((float) $data['nilai_tarif'] - (float) $record->nilai_tarif) > 0.001) {
                    throw ValidationException::withMessages([
                        'nilai_tarif' => 'Tarif ini telah digunakan pada dokumen SPK operasional sehingga nilainya terkunci demi integritas audit keuangan. Silakan gunakan menu "Ganti Tarif" untuk menerbitkan tarif baru.',
                    ]);
                }

                if ((int) $data['jenis_pekerjaan_id'] !== (int) $record->jenis_pekerjaan_id) {
                    throw ValidationException::withMessages([
                        'jenis_pekerjaan_id' => 'Jenis pekerjaan tidak dapat diubah karena tarif ini telah memiliki transaksi SPK operasional.',
                    ]);
                }
            }

            // 2. Cek tumpang tindih dengan record tarif lain
            $tumpangTindih = TarifPekerjaan::where('jenis_pekerjaan_id', $jenisId)
                ->where('id', '!=', $record->id)
                ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $mulai))
                ->when($sampai, fn ($q) => $q->where('berlaku_mulai', '<=', $sampai))
                ->exists();

            if ($tumpangTindih) {
                throw ValidationException::withMessages([
                    'berlaku_mulai' => 'Periode tarif ini tumpang tindih dengan arsip tarif lain untuk jenis pekerjaan yang sama.',
                ]);
            }
        }
    }

    protected function label(Model $record): string
    {
        return ($record->jenisPekerjaan?->nama_pekerjaan ?? 'Tarif').' mulai '.$record->berlaku_mulai?->format('d/m/Y');
    }

    private function opsiJenisPekerjaan(): array
    {
        return JenisPekerjaan::orderBy('nama_pekerjaan')->pluck('nama_pekerjaan', 'id')->all();
    }
}
