<?php

namespace App\Http\Controllers\Master;

use App\Models\Distrik;
use App\Models\Pt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class DistrikController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Distrik::class;
    }

    protected function judul(): string
    {
        return 'Distrik';
    }

    protected function prefixRute(): string
    {
        return 'master.distrik';
    }

    protected function ikon(): string
    {
        return 'bi-geo-alt';
    }

    protected function deskripsi(): string
    {
        return 'Wilayah kerja yang menjadi dasar petak lahan, SPK, dan laporan realisasi.';
    }

    protected function relasi(): array
    {
        return ['pt'];
    }

    protected function filter(): array
    {
        return [
            ['name' => 'pt_id', 'label' => 'Semua Perusahaan (PT)', 'options' => fn () => Pt::orderBy('nama_pt')->pluck('nama_pt', 'id')->all()],
        ];
    }

    protected function pencarian(): array
    {
        return ['kode_distrik', 'nama_distrik', 'keterangan'];
    }

    protected function urutan(): array
    {
        return ['nama_distrik' => 'asc'];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Perusahaan (PT)', 'key' => 'pt.nama_pt'],
            ['label' => 'Kode', 'key' => 'kode_distrik'],
            ['label' => 'Nama Distrik', 'key' => 'nama_distrik'],
            ['label' => 'Keterangan', 'key' => 'keterangan'],
            ['label' => 'Status', 'key' => 'aktif', 'tipe' => 'bool'],
        ];
    }

    protected function field(): array
    {
        return [
            ['name' => 'pt_id', 'label' => 'Perusahaan (PT)', 'tipe' => 'pilih', 'wajib' => false, 'options' => fn () => Pt::where('aktif', true)->orderBy('nama_pt')->pluck('nama_pt', 'id')->all()],
            ['name' => 'kode_distrik', 'label' => 'Kode Distrik (Contoh: DAS)', 'tipe' => 'teks', 'wajib' => false],
            ['name' => 'nama_distrik', 'label' => 'Nama Distrik (Contoh: DAS - DISTRIK AIR SUGIHAN)', 'tipe' => 'teks', 'wajib' => true],
            ['name' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'area', 'lebar' => 'penuh'],
            ['name' => 'aktif', 'label' => 'Distrik aktif', 'tipe' => 'centang', 'default' => true],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'pt_id' => ['nullable', 'exists:pt,id'],
            'kode_distrik' => ['nullable', 'string', 'max:20'],
            'nama_distrik' => ['required', 'string', 'max:120', Rule::unique('distrik', 'nama_distrik')->ignore($record?->id)],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['required', 'boolean'],
        ];
    }
}
