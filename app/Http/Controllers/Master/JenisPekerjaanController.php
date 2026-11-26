<?php

namespace App\Http\Controllers\Master;

use App\Models\JenisPekerjaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JenisPekerjaanController extends MasterDataController
{
    protected function modelClass(): string
    {
        return JenisPekerjaan::class;
    }

    protected function judul(): string
    {
        return 'Jenis Pekerjaan';
    }

    protected function prefixRute(): string
    {
        return 'master.jenis-pekerjaan';
    }

    protected function ikon(): string
    {
        return 'bi-tools';
    }

    protected function deskripsi(): string
    {
        return 'Jenis pekerjaan drone, mencakup pekerjaan pokok (aplikasi drone) dan layanan/biaya tambahan (add-on).';
    }

    public function index(Request $request): View
    {
        $response = parent::index($request);
        $data = $response->getData();

        $kataKunci = trim((string) $request->query('cari'));
        $queryUtama = JenisPekerjaan::utama()->with('tarif')->orderBy('kode_pekerjaan');
        $queryAddon = JenisPekerjaan::addon()->with('tarif')->orderBy('kode_pekerjaan');

        if ($kataKunci !== '') {
            $pola = '%' . addcslashes($kataKunci, '%_\\') . '%';
            $filterCari = function ($q) use ($pola) {
                $q->where('kode_pekerjaan', 'like', $pola)
                  ->orWhere('nama_pekerjaan', 'like', $pola)
                  ->orWhere('keterangan', 'like', $pola);
            };
            $queryUtama->where($filterCari);
            $queryAddon->where($filterCari);
        }

        $data['pekerjaanUtama'] = $queryUtama->get();
        $data['pekerjaanAddon'] = $queryAddon->get();

        $data['ringkasanJenis'] = [
            'total' => JenisPekerjaan::count(),
            'total_utama' => JenisPekerjaan::utama()->count(),
            'total_addon' => JenisPekerjaan::addon()->count(),
            'total_aktif' => JenisPekerjaan::where('aktif', true)->count(),
        ];

        return view('master.index', $data);
    }

    protected function pencarian(): array
    {
        return ['kode_pekerjaan', 'nama_pekerjaan'];
    }

    protected function urutan(): array
    {
        return ['kode_pekerjaan' => 'asc'];
    }

    protected function relasi(): array
    {
        return ['tarif'];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Kode', 'key' => 'kode_pekerjaan'],
            ['label' => 'Nama pekerjaan', 'key' => 'nama_pekerjaan'],
            ['label' => 'Tarif Aktif Saat Ini', 'key' => 'tarif_aktif.nilai_tarif', 'tipe' => 'rupiah_tarif'],
            ['label' => 'Keterangan', 'key' => 'keterangan'],
            ['label' => 'Status', 'key' => 'aktif', 'tipe' => 'bool'],
        ];
    }

    protected function field(): array
    {
        return [
            ['name' => 'kode_pekerjaan', 'label' => 'Kode pekerjaan', 'tipe' => 'teks', 'wajib' => true, 'bantuan' => 'Huruf, angka, strip, atau garis bawah. Otomatis menjadi huruf kapital.'],
            ['name' => 'nama_pekerjaan', 'label' => 'Nama pekerjaan', 'tipe' => 'teks', 'wajib' => true],
            ['name' => 'tipe', 'label' => 'Kategori Pekerjaan', 'tipe' => 'pilih', 'options' => JenisPekerjaan::daftarTipe(), 'default' => JenisPekerjaan::TIPE_UTAMA, 'wajib' => true, 'bantuan' => 'Pilih apakah pekerjaan pokok (muncul di dropdown SPK) atau layanan tambahan/addon (muncul sebagai opsi mapping).'],
            ['name' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'area', 'lebar' => 'penuh'],
            ['name' => 'aktif', 'label' => 'Jenis pekerjaan aktif', 'tipe' => 'centang', 'default' => true],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'kode_pekerjaan' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('jenis_pekerjaan', 'kode_pekerjaan')->ignore($record?->id)],
            'nama_pekerjaan' => ['required', 'string', 'max:120'],
            'tipe' => ['required', Rule::in(array_keys(JenisPekerjaan::daftarTipe()))],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['required', 'boolean'],
        ];
    }

    protected function olahData(array $data, ?Model $record): array
    {
        $data['kode_pekerjaan'] = mb_strtoupper(trim($data['kode_pekerjaan']));

        return $data;
    }

    protected function label(Model $record): string
    {
        return $record->nama_pekerjaan;
    }

    public function destroy(string $id): \Illuminate\Http\RedirectResponse
    {
        $record = JenisPekerjaan::withCount('spk')->findOrFail($id);

        if ($record->spk_count > 0) {
            return back()->with('galat', 'Jenis pekerjaan "'.$this->label($record).'" tidak dapat dihapus karena telah digunakan pada '.$record->spk_count.' dokumen SPK operasional. Silakan nonaktifkan bila sudah tidak digunakan.');
        }

        return parent::destroy($id);
    }
}
