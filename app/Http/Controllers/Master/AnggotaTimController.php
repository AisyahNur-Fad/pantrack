<?php

namespace App\Http\Controllers\Master;

use App\Models\AnggotaTim;
use App\Models\Pengguna;
use App\Models\Tim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class AnggotaTimController extends MasterDataController
{
    protected function modelClass(): string
    {
        return AnggotaTim::class;
    }

    protected function judul(): string
    {
        return 'Anggota Tim';
    }

    protected function prefixRute(): string
    {
        return 'master.anggota-tim';
    }

    protected function ikon(): string
    {
        return 'bi-person-badge';
    }

    protected function deskripsi(): string
    {
        return 'Pilot, co-pilot, helper, dan anggota lain pada setiap tim beserta periode penugasannya.';
    }

    protected function relasi(): array
    {
        return ['tim', 'pengguna'];
    }

    protected function pencarian(): array
    {
        return ['nama_anggota'];
    }

    protected function urutan(): array
    {
        return ['tim_id' => 'asc', 'jabatan_tim' => 'asc', 'nama_anggota' => 'asc'];
    }

    protected function filter(): array
    {
        return [
            ['name' => 'tim_id', 'label' => 'Semua tim', 'options' => fn () => Tim::orderBy('nama_tim')->pluck('nama_tim', 'id')->all()],
            ['name' => 'jabatan_tim', 'label' => 'Semua jabatan', 'options' => AnggotaTim::jabatanTim()],
        ];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Nama anggota', 'key' => 'nama_anggota'],
            ['label' => 'Tim', 'key' => 'tim.nama_tim'],
            ['label' => 'Jabatan', 'key' => 'jabatan_tim', 'tipe' => 'badge', 'opsi' => AnggotaTim::jabatanTim(), 'badge' => [
                AnggotaTim::PILOT => 'info', AnggotaTim::CO_PILOT => 'info', AnggotaTim::HELPER => 'netral', AnggotaTim::ANGGOTA_LAIN => 'netral',
            ]],
            ['label' => 'Akun', 'key' => 'pengguna.nama_pengguna'],
            ['label' => 'Mulai', 'key' => 'tanggal_mulai', 'tipe' => 'tanggal'],
            ['label' => 'Selesai', 'key' => 'tanggal_selesai', 'tipe' => 'tanggal'],
        ];
    }

    protected function field(): array
    {
        return [
            ['name' => 'tim_id', 'label' => 'Tim', 'tipe' => 'pilih', 'wajib' => true, 'options' => fn () => Tim::orderBy('nama_tim')->pluck('nama_tim', 'id')->all()],
            ['name' => 'jabatan_tim', 'label' => 'Jabatan dalam tim', 'tipe' => 'pilih', 'wajib' => true, 'options' => AnggotaTim::jabatanTim()],
            [
                'name' => 'pengguna_id', 'label' => 'Akun pengguna (opsional)', 'tipe' => 'pilih', 'kosong' => 'Tidak memiliki akun',
                'options' => fn () => Pengguna::where('aktif', true)->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id')->all(),
                'bantuan' => 'Isi hanya bila anggota memiliki akun PANTRACK. Helper boleh tanpa akun.',
            ],
            ['name' => 'nama_anggota', 'label' => 'Nama anggota', 'tipe' => 'teks', 'bantuan' => 'Wajib diisi bila tidak memilih akun pengguna.'],
            ['name' => 'tanggal_mulai', 'label' => 'Tanggal mulai penugasan', 'tipe' => 'tanggal'],
            ['name' => 'tanggal_selesai', 'label' => 'Tanggal selesai penugasan', 'tipe' => 'tanggal'],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'tim_id' => ['required', 'exists:tim,id'],
            'jabatan_tim' => ['required', Rule::in(array_keys(AnggotaTim::jabatanTim()))],
            'pengguna_id' => ['nullable', 'exists:pengguna,id'],
            'nama_anggota' => ['required_without:pengguna_id', 'nullable', 'string', 'max:150'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ];
    }

    /** Nama anggota diisi otomatis dari akun bila dikosongkan. */
    protected function olahData(array $data, ?Model $record): array
    {
        if (empty($data['nama_anggota']) && ! empty($data['pengguna_id'])) {
            $data['nama_anggota'] = Pengguna::whereKey($data['pengguna_id'])->value('nama_lengkap');
        }

        return $data;
    }
}
