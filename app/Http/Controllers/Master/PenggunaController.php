<?php

namespace App\Http\Controllers\Master;

use App\Models\Peran;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Pengelolaan pengguna (FR-02). Akun tidak dihapus: cukup dinonaktifkan agar jejak
 * penginput/pemeriksa pada data lama tetap utuh.
 */
class PenggunaController extends MasterDataController
{
    protected function modelClass(): string
    {
        return Pengguna::class;
    }

    protected function judul(): string
    {
        return 'Pengguna';
    }

    protected function prefixRute(): string
    {
        return 'master.pengguna';
    }

    protected function ikon(): string
    {
        return 'bi-person-gear';
    }

    protected function deskripsi(): string
    {
        return 'Akun pengguna dan peran. Akun tidak dihapus, tetapi dinonaktifkan.';
    }

    protected function bolehHapus(): bool
    {
        return false;
    }

    protected function relasi(): array
    {
        return ['peran'];
    }

    protected function pencarian(): array
    {
        return ['nama_lengkap', 'nama_pengguna', 'email'];
    }

    protected function urutan(): array
    {
        return ['nama_lengkap' => 'asc'];
    }

    protected function filter(): array
    {
        return [
            ['name' => 'peran_id', 'label' => 'Semua peran', 'options' => fn () => $this->opsiPeran()],
            ['name' => 'aktif', 'label' => 'Semua status', 'options' => ['1' => 'Aktif', '0' => 'Nonaktif']],
        ];
    }

    protected function kolom(): array
    {
        return [
            ['label' => 'Nama lengkap', 'key' => 'nama_lengkap'],
            ['label' => 'Nama pengguna', 'key' => 'nama_pengguna'],
            ['label' => 'Peran', 'key' => 'peran.label', 'tipe' => 'badge', 'badge' => [
                'Admin' => 'info', 'Kepala Lapangan' => 'success', 'Pekerja Lapangan' => 'netral', 'Atasan' => 'warning',
            ]],
            ['label' => 'Email', 'key' => 'email'],
            ['label' => 'Telepon', 'key' => 'nomor_telepon'],
            ['label' => 'Status', 'key' => 'aktif', 'tipe' => 'bool'],
        ];
    }

    protected function field(): array
    {
        return [
            ['name' => 'nama_lengkap', 'label' => 'Nama lengkap', 'tipe' => 'teks', 'wajib' => true],
            ['name' => 'nama_pengguna', 'label' => 'Nama pengguna', 'tipe' => 'teks', 'wajib' => true, 'bantuan' => 'Dipakai untuk login. Hanya huruf, angka, strip (-), dan garis bawah (_).'],
            ['name' => 'peran_id', 'label' => 'Peran', 'tipe' => 'pilih', 'wajib' => true, 'options' => fn () => $this->opsiPeran()],
            ['name' => 'email', 'label' => 'Email', 'tipe' => 'email'],
            ['name' => 'nomor_telepon', 'label' => 'Nomor telepon', 'tipe' => 'telepon', 'bantuan' => 'Angka saja, tanpa spasi atau tanda baca.'],
            ['name' => 'aktif', 'label' => 'Akun aktif', 'tipe' => 'centang', 'default' => true],
            ['name' => 'kata_sandi', 'label' => 'Kata sandi', 'tipe' => 'password', 'bantuan' => 'Minimal 8 karakter, kombinasi huruf dan angka. Kosongkan saat mengubah jika tidak ingin mengganti.'],
            ['name' => 'kata_sandi_confirmation', 'label' => 'Ulangi kata sandi', 'tipe' => 'password'],
        ];
    }

    protected function aturan(?Model $record): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nama_pengguna' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('pengguna', 'nama_pengguna')->ignore($record?->id)],
            'peran_id' => ['required', 'exists:peran,id'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('pengguna', 'email')->ignore($record?->id)],
            'nomor_telepon' => ['nullable', 'regex:/^[0-9]+$/', 'min:8', 'max:20'],
            'aktif' => ['required', 'boolean'],
            'kata_sandi' => [
                $record ? 'nullable' : 'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ];
    }

    protected function pesan(): array
    {
        return ['nomor_telepon.regex' => 'Nomor telepon hanya boleh berisi angka.'];
    }

    protected function olahData(array $data, ?Model $record): array
    {
        // Kata sandi kosong saat mengubah berarti tidak diganti.
        if (empty($data['kata_sandi'])) {
            unset($data['kata_sandi']);
        }

        return $data;
    }

    /** Admin tidak boleh mengunci dirinya sendiri dari sistem. */
    protected function validasiTambahan(array $data, ?Model $record): void
    {
        if (! $record || $record->id !== Auth::id()) {
            return;
        }

        if (! $data['aktif']) {
            throw ValidationException::withMessages(['aktif' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        if ((int) $data['peran_id'] !== (int) $record->peran_id) {
            throw ValidationException::withMessages(['peran_id' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
        }
    }

    protected function label(Model $record): string
    {
        return $record->nama_lengkap.' ('.$record->nama_pengguna.')';
    }

    private function opsiPeran(): array
    {
        return Peran::orderBy('id')->get()->mapWithKeys(fn ($p) => [$p->id => $p->label])->all();
    }
}
