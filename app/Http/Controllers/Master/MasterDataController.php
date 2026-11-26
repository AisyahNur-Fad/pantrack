<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Services\RiwayatAktivitasService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kerangka CRUD data master. Alurnya selalu sama:
 *   daftar (cari + filter + paginasi) -> form -> validasi server-side -> simpan -> catat riwayat aktivitas.
 *
 * Controller turunan cukup mendefinisikan model, kolom tabel, field formulir, dan aturan validasi.
 * Tampilan memakai view bersama: resources/views/master/{index,form}.blade.php.
 */
abstract class MasterDataController extends Controller
{
    private const PER_HALAMAN = 15;

    public function __construct(protected readonly RiwayatAktivitasService $aktivitas) {}

    /* ------------------------------------------------------------------
     | Definisi yang wajib diisi controller turunan
     | ------------------------------------------------------------------ */

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** Nama data dalam bentuk tunggal, contoh: "Distrik". */
    abstract protected function judul(): string;

    /** Awalan nama route, contoh: "master.distrik". */
    abstract protected function prefixRute(): string;

    /**
     * Kolom tabel daftar. Setiap elemen:
     * ['label' => ..., 'key' => 'atribut' atau 'relasi.atribut', 'tipe' => teks|bool|tanggal|desimal|rupiah|badge, 'badge' => [nilai => warna], 'opsi' => [nilai => label]]
     */
    abstract protected function kolom(): array;

    /**
     * Field formulir. Setiap elemen:
     * ['name', 'label', 'tipe' => teks|email|telepon|password|angka|tanggal|area|pilih|centang, 'options' => array|Closure, 'wajib' => bool, 'bantuan', 'default', 'step', 'lebar' => 'penuh']
     */
    abstract protected function field(): array;

    /** Aturan validasi Laravel. $record null berarti proses tambah. */
    abstract protected function aturan(?Model $record): array;

    /* ------------------------------------------------------------------
     | Pengaturan opsional
     | ------------------------------------------------------------------ */

    protected function ikon(): string
    {
        return 'bi-database';
    }

    protected function deskripsi(): string
    {
        return '';
    }

    /** Kolom database yang dicari lewat kotak pencarian. */
    protected function pencarian(): array
    {
        return [];
    }

    /** Relasi yang di-eager-load untuk tabel daftar. */
    protected function relasi(): array
    {
        return [];
    }

    /** Filter dropdown pada daftar: [['name' => kolom, 'label' => ..., 'options' => array|Closure]]. */
    protected function filter(): array
    {
        return [];
    }

    protected function urutan(): array
    {
        return ['id' => 'desc'];
    }

    protected function bolehHapus(): bool
    {
        return true;
    }

    /** Teks yang menggambarkan satu record pada log aktivitas dan konfirmasi hapus. */
    protected function label(Model $record): string
    {
        $key = $this->kolom()[0]['key'];

        return (string) data_get($record, $key, '#'.$record->getKey());
    }

    protected function pesan(): array
    {
        return [];
    }

    /** Menyesuaikan data tervalidasi sebelum disimpan (normalisasi, nilai turunan, dll). */
    protected function olahData(array $data, ?Model $record): array
    {
        return $data;
    }

    /** Validasi lanjutan yang tidak cukup dengan aturan biasa. Lempar ValidationException bila gagal. */
    protected function validasiTambahan(array $data, ?Model $record): void {}

    /** Hook yang dipanggil setelah data berhasil disimpan (store / update). */
    protected function setelahSimpan(Model $record, array $data, bool $isBaru): void {}

    /* ------------------------------------------------------------------
     | Aksi CRUD
     | ------------------------------------------------------------------ */

    public function index(Request $request): View
    {
        $query = $this->modelClass()::query()->with($this->relasi());

        $kataKunci = trim((string) $request->query('cari'));
        if ($kataKunci !== '' && $this->pencarian()) {
            $pola = '%'.addcslashes($kataKunci, '%_\\').'%';
            $query->where(function ($q) use ($pola) {
                foreach ($this->pencarian() as $kolom) {
                    $q->orWhere($kolom, 'like', $pola);
                }
            });
        }

        $filter = $this->filterTerselesaikan();
        foreach ($filter as $f) {
            if ($request->filled($f['name'])) {
                $query->where($f['name'], $request->query($f['name']));
            }
        }

        foreach ($this->urutan() as $kolom => $arah) {
            $query->orderBy($kolom, $arah);
        }

        $baris = $query->paginate(self::PER_HALAMAN)->withQueryString();

        return view('master.index', [
            'judul' => $this->judul(),
            'deskripsi' => $this->deskripsi(),
            'ikon' => $this->ikon(),
            'prefix' => $this->prefixRute(),
            'kolom' => $this->kolom(),
            'baris' => $baris,
            'labelBaris' => $baris->getCollection()->mapWithKeys(fn ($r) => [$r->getKey() => $this->label($r)]),
            'bisaCari' => (bool) $this->pencarian(),
            'filter' => $filter,
            'bolehHapus' => $this->bolehHapus(),
            'cari' => $kataKunci,
        ]);
    }

    public function create(): View
    {
        return $this->tampilkanForm(null);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request, null);

        $record = DB::transaction(function () use ($data) {
            $record = $this->modelClass()::create($data);
            $this->setelahSimpan($record, $data, true);
            $this->aktivitas->catat('Menambah data '.$this->judul(), $record, $this->label($record));

            return $record;
        });

        return $this->kembaliKeDaftar('Data '.$this->judul().' "'.$this->label($record).'" berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        return $this->tampilkanForm($this->modelClass()::findOrFail($id));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $record = $this->modelClass()::findOrFail($id);
        $data = $this->validasi($request, $record);

        DB::transaction(function () use ($record, $data) {
            $record->fill($data);
            $kolomBerubah = array_keys($record->getDirty());
            $record->save();
            $this->setelahSimpan($record, $data, false);

            $this->aktivitas->catat(
                'Mengubah data '.$this->judul(),
                $record,
                $this->label($record).($kolomBerubah ? ' (kolom: '.implode(', ', $kolomBerubah).')' : ' (tanpa perubahan)')
            );
        });

        return $this->kembaliKeDaftar('Data '.$this->judul().' "'.$this->label($record).'" berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        abort_unless($this->bolehHapus(), 405);

        $record = $this->modelClass()::findOrFail($id);
        $label = $this->label($record);

        try {
            $record->delete();
        } catch (QueryException $e) {
            // 23000 = pelanggaran foreign key: data masih dipakai tabel lain.
            if ($e->getCode() === '23000') {
                return back()->with('galat', 'Data "'.$label.'" masih digunakan oleh data lain sehingga tidak dapat dihapus. Nonaktifkan data tersebut bila tersedia.');
            }

            throw $e;
        }

        $this->aktivitas->catat('Menghapus data '.$this->judul(), $record, $label);

        return $this->kembaliKeDaftar('Data '.$this->judul().' "'.$label.'" berhasil dihapus.');
    }

    /** Mengubah status aktif/nonaktif data secara instan (Toggle Switch). */
    public function toggleStatus(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $record = $this->modelClass()::findOrFail($id);

        $isAktif = false;
        $labelStatus = '';

        if (array_key_exists('status_operasional', $record->getAttributes()) || in_array('status_operasional', $record->getFillable(), true)) {
            $isAktif = ($record->status_operasional === \App\Models\Drone::STATUS_AKTIF);
            $baru = $isAktif ? \App\Models\Drone::STATUS_TIDAK_BEROPERASI : \App\Models\Drone::STATUS_AKTIF;
            $record->status_operasional = $baru;
            if ($baru === \App\Models\Drone::STATUS_AKTIF) {
                $record->catatan_perawatan = null;
            }
            $isAktif = ($baru === \App\Models\Drone::STATUS_AKTIF);
            $labelStatus = \App\Models\Drone::statusOperasional()[$baru] ?? ($isAktif ? 'Aktif' : 'Tidak beroperasi');
        } elseif (array_key_exists('status_petak', $record->getAttributes()) || in_array('status_petak', $record->getFillable(), true)) {
            $isAktif = ($record->status_petak === \App\Models\Petak::STATUS_AKTIF);
            $baru = $isAktif ? \App\Models\Petak::STATUS_TIDAK_AKTIF : \App\Models\Petak::STATUS_AKTIF;
            $record->status_petak = $baru;
            $isAktif = ($baru === \App\Models\Petak::STATUS_AKTIF);
            $labelStatus = \App\Models\Petak::statusPetak()[$baru] ?? ($isAktif ? 'Aktif' : 'Tidak aktif');
        } elseif (array_key_exists('status_tim', $record->getAttributes()) || in_array('status_tim', $record->getFillable(), true)) {
            $isAktif = ($record->status_tim === \App\Models\Tim::STATUS_AKTIF);
            if (! $isAktif && $record->drone_id) {
                $bentrok = \App\Models\Tim::where('drone_id', $record->drone_id)
                    ->where('status_tim', \App\Models\Tim::STATUS_AKTIF)
                    ->where('id', '!=', $record->id)
                    ->first();
                if ($bentrok) {
                    $pesan = 'Drone sedang digunakan tim aktif "'.$bentrok->nama_tim.'". Nonaktifkan tim tersebut terlebih dahulu.';
                    if ($request->wantsJson()) {
                        return response()->json(['sukses' => false, 'pesan' => $pesan], 422);
                    }
                    return back()->with('galat', $pesan);
                }
            }
            $baru = $isAktif ? \App\Models\Tim::STATUS_TIDAK_AKTIF : \App\Models\Tim::STATUS_AKTIF;
            $record->status_tim = $baru;
            $isAktif = ($baru === \App\Models\Tim::STATUS_AKTIF);
            $labelStatus = \App\Models\Tim::statusTim()[$baru] ?? ($isAktif ? 'Aktif' : 'Tidak aktif');
        } elseif (array_key_exists('aktif', $record->getAttributes()) || in_array('aktif', $record->getFillable(), true)) {
            if ($record instanceof \App\Models\Pengguna && $record->id === auth()->id() && $record->aktif) {
                $pesan = 'Anda tidak dapat menonaktifkan akun Anda sendiri.';
                if ($request->wantsJson()) {
                    return response()->json(['sukses' => false, 'pesan' => $pesan], 422);
                }
                return back()->with('galat', $pesan);
            }
            $record->aktif = ! (bool) $record->aktif;
            $isAktif = (bool) $record->aktif;
            $labelStatus = $isAktif ? 'Aktif' : 'Nonaktif';
        } else {
            abort(400, 'Entitas ini tidak memiliki status yang dapat dialihkan.');
        }

        $record->save();

        $label = $this->label($record);
        $this->aktivitas->catat(
            'Mengubah status '.$this->judul(),
            $record,
            $label.' menjadi '.$labelStatus
        );

        $pesanSukses = 'Status '.$this->judul().' "'.$label.'" berhasil diubah menjadi '.$labelStatus.'.';

        if ($request->wantsJson()) {
            return response()->json([
                'sukses' => true,
                'aktif' => $isAktif,
                'label' => $labelStatus,
                'pesan' => $pesanSukses,
            ]);
        }

        return back()->with('sukses', $pesanSukses);
    }

    /* ------------------------------------------------------------------
     | Bagian internal
     | ------------------------------------------------------------------ */

    protected function validasi(Request $request, ?Model $record): array
    {
        $data = $request->validate($this->aturan($record), $this->pesan(), $this->namaAtribut());
        $data = $this->olahData($data, $record);
        $this->validasiTambahan($data, $record);

        return $data;
    }

    private function tampilkanForm(?Model $record): View
    {
        return view('master.form', [
            'judul' => $this->judul(),
            'ikon' => $this->ikon(),
            'prefix' => $this->prefixRute(),
            'record' => $record,
            'field' => $this->fieldTerselesaikan(),
        ]);
    }

    private function kembaliKeDaftar(string $pesan): RedirectResponse
    {
        return redirect()->route($this->prefixRute().'.index')->with('sukses', $pesan);
    }

    /** Memetakan nama kolom ke label agar pesan validasi memakai bahasa pengguna. */
    private function namaAtribut(): array
    {
        return collect($this->field())->mapWithKeys(fn ($f) => [$f['name'] => mb_strtolower($f['label'])])->all();
    }

    /** Mengevaluasi `options` yang berupa Closure agar view menerima array siap pakai. */
    private function fieldTerselesaikan(): array
    {
        return array_map(function (array $f) {
            if (isset($f['options'])) {
                $f['options'] = value($f['options']);
            }

            return $f;
        }, $this->field());
    }

    private function filterTerselesaikan(): array
    {
        return array_map(function (array $f) {
            $f['options'] = value($f['options']);

            return $f;
        }, $this->filter());
    }
}
