<?php

namespace App\Http\Controllers\Master;

use App\Models\Pt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Pengelolaan Master Perusahaan (PT) oleh Admin.
 */
class PtController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Pt::class;
    }

    protected function judul(): string
    {
        return 'Perusahaan (PT)';
    }

    protected function prefixRute(): string
    {
        return 'master.pt';
    }

    protected function ikon(): string
    {
        return 'bi-buildings';
    }

    protected function deskripsi(): string
    {
        return 'Daftar Perusahaan (PT) induk yang menaungi wilayah kerja distrik dan unit kerja drone.';
    }

    protected function pencarian(): array
    {
        return ['nama_pt', 'kode_pt', 'keterangan'];
    }

    protected function urutan(): array
    {
        return ['nama_pt' => 'asc'];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Kode PT', 'key' => 'kode_pt'],
            ['label' => 'Nama Perusahaan (PT)', 'key' => 'nama_pt'],
            ['label' => 'Keterangan', 'key' => 'keterangan'],
            ['label' => 'Status', 'key' => 'aktif', 'tipe' => 'bool'],
        ];
    }

    protected function field(): array
    {
        return [
            ['name' => 'kode_pt', 'label' => 'Kode Perusahaan (PT)', 'tipe' => 'teks', 'wajib' => true],
            ['name' => 'nama_pt', 'label' => 'Nama Perusahaan (PT)', 'tipe' => 'teks', 'wajib' => true],
            ['name' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'area', 'lebar' => 'penuh'],
            ['name' => 'aktif', 'label' => 'PT Aktif', 'tipe' => 'centang', 'default' => true],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'kode_pt' => ['required', 'string', 'max:50', Rule::unique('pt', 'kode_pt')->ignore($record?->id)],
            'nama_pt' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['required', 'boolean'],
        ];
    }
}

