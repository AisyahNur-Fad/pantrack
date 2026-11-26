<?php

namespace App\Http\Controllers\Master;

use App\Models\JenisBbm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class JenisBbmController extends MasterDataController
{
    protected function modelClass(): string
    {
        return JenisBbm::class;
    }

    protected function judul(): string
    {
        return 'Jenis BBM';
    }

    protected function prefixRute(): string
    {
        return 'master.jenis-bbm';
    }

    protected function ikon(): string
    {
        return 'bi-fuel-pump';
    }

    protected function deskripsi(): string
    {
        return 'Jenis bahan bakar beserta satuannya.';
    }

    protected function pencarian(): array
    {
        return ['nama_bbm'];
    }

    protected function urutan(): array
    {
        return ['nama_bbm' => 'asc'];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Nama BBM', 'key' => 'nama_bbm'],
            ['label' => 'Harga per Liter', 'key' => 'harga_per_liter', 'tipe' => 'rupiah'],
            ['label' => 'Satuan', 'key' => 'satuan'],
            ['label' => 'Status', 'key' => 'aktif', 'tipe' => 'bool'],
        ];
    }

    protected function field(): array
    {
        return [
            ['name' => 'nama_bbm', 'label' => 'Nama BBM', 'tipe' => 'teks', 'wajib' => true],
            ['name' => 'harga_per_liter', 'label' => 'Harga Satuan per Liter (Rp)', 'tipe' => 'angka', 'step' => 'any', 'wajib' => true, 'default' => 0, 'bantuan' => 'Harga patokan per liter (fixed) yang otomatis diterapkan pada transaksi BBM.'],
            ['name' => 'satuan', 'label' => 'Satuan', 'tipe' => 'teks', 'wajib' => true, 'default' => 'liter'],
            ['name' => 'aktif', 'label' => 'Jenis BBM aktif', 'tipe' => 'centang', 'default' => true],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'nama_bbm' => ['required', 'string', 'max:80', Rule::unique('jenis_bbm', 'nama_bbm')->ignore($record?->id)],
            'harga_per_liter' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
            'aktif' => ['required', 'boolean'],
        ];
    }
}
