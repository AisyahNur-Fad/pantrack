<?php

namespace App\Http\Controllers\Master;

use App\Models\Distrik;
use App\Models\Petak;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PetakController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Petak::class;
    }

    protected function judul(): string
    {
        return 'Petak';
    }

    protected function prefixRute(): string
    {
        return 'master.petak';
    }

    protected function ikon(): string
    {
        return 'bi-grid-3x3-gap';
    }

    protected function deskripsi(): string
    {
        return 'Petak lahan per distrik. Kode petak harus unik di dalam satu distrik.';
    }

    protected function relasi(): array
    {
        return ['distrik'];
    }

    protected function pencarian(): array
    {
        return ['kode_petak', 'nama_petak', 'jenis_lahan'];
    }

    protected function urutan(): array
    {
        return ['distrik_id' => 'asc', 'kode_petak' => 'asc'];
    }

    protected function filter(): array
    {
        return [
            ['name' => 'distrik_id', 'label' => 'Semua distrik', 'options' => fn () => Distrik::orderBy('nama_distrik')->pluck('nama_distrik', 'id')->all()],
            ['name' => 'status_petak', 'label' => 'Semua status', 'options' => Petak::statusPetak()],
        ];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Kode petak', 'key' => 'kode_petak'],
            ['label' => 'Nama petak', 'key' => 'nama_petak'],
            ['label' => 'Distrik', 'key' => 'distrik.nama_distrik'],
            ['label' => 'Jenis lahan', 'key' => 'jenis_lahan'],
            ['label' => 'Luas (Ha)', 'key' => 'luas_petak_ha', 'tipe' => 'desimal'],
            ['label' => 'Status', 'key' => 'status_petak', 'tipe' => 'badge', 'opsi' => Petak::statusPetak(), 'badge' => [Petak::STATUS_AKTIF => 'success', Petak::STATUS_TIDAK_AKTIF => 'netral']],
        ];
    }

    public function index(Request $request): View
    {
        $response = parent::index($request);
        $data = $response->getData();

        $totalPetak = Petak::count();
        $totalLuasHa = (float) Petak::sum('luas_petak_ha');
        $totalAktif = Petak::where('status_petak', Petak::STATUS_AKTIF)->count();
        $totalTidakAktif = Petak::where('status_petak', Petak::STATUS_TIDAK_AKTIF)->count();
        $totalDistrik = Petak::distinct('distrik_id')->count();
        $rataRataLuas = $totalPetak > 0 ? ($totalLuasHa / $totalPetak) : 0;

        $data['ringkasanPetak'] = [
            'total' => $totalPetak,
            'total_luas' => $totalLuasHa,
            'aktif' => $totalAktif,
            'tidak_aktif' => $totalTidakAktif,
            'total_distrik' => $totalDistrik,
            'rata_luas' => $rataRataLuas,
        ];

        return view('master.index', $data);
    }

    protected function field(): array
    {
        return [
            ['name' => 'distrik_id', 'label' => 'Distrik', 'tipe' => 'pilih', 'wajib' => true, 'options' => fn () => Distrik::orderBy('nama_distrik')->pluck('nama_distrik', 'id')->all()],
            ['name' => 'kode_petak', 'label' => 'Kode petak', 'tipe' => 'teks', 'wajib' => true],
            ['name' => 'nama_petak', 'label' => 'Nama petak', 'tipe' => 'teks'],
            ['name' => 'jenis_lahan', 'label' => 'Jenis lahan', 'tipe' => 'teks'],
            ['name' => 'luas_petak_ha', 'label' => 'Luas petak (Ha)', 'tipe' => 'angka', 'step' => '0.01'],
            ['name' => 'status_petak', 'label' => 'Status petak', 'tipe' => 'pilih', 'options' => Petak::statusPetak(), 'wajib' => true, 'default' => Petak::STATUS_AKTIF],
            ['name' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'area', 'lebar' => 'penuh'],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'distrik_id' => ['required', 'exists:distrik,id'],
            'kode_petak' => [
                'required', 'string', 'max:80',
                Rule::unique('petak', 'kode_petak')
                    ->where('distrik_id', request('distrik_id'))
                    ->ignore($record?->id),
            ],
            'nama_petak' => ['nullable', 'string', 'max:150'],
            'jenis_lahan' => ['nullable', 'string', 'max:100'],
            'luas_petak_ha' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'status_petak' => ['required', Rule::in(array_keys(Petak::statusPetak()))],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function pesan(): array
    {
        return ['kode_petak.unique' => 'Kode petak ini sudah terdaftar pada distrik yang dipilih.'];
    }

    protected function olahData(array $data, ?Model $record): array
    {
        $data['kode_petak'] = trim($data['kode_petak']);

        return $data;
    }

    protected function label(Model $record): string
    {
        return $record->kode_petak;
    }
}
