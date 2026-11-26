<?php

namespace App\Http\Controllers;

use App\Services\RiwayatAktivitasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengelolaan profil mandiri dan ganti kata sandi untuk semua pengguna.
 */
class ProfilController extends Controller
{
    public function __construct(private readonly RiwayatAktivitasService $aktivitas) {}

    public function index(): View
    {
        $pengguna = Auth::user();
        $pengguna->load(['peran', 'keanggotaanTim.tim', 'timYangDipimpin']);

        return view('profil.index', compact('pengguna'));
    }

    public function update(Request $request): RedirectResponse
    {
        $pengguna = Auth::user();

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nama_pengguna' => [
                'required',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('pengguna', 'nama_pengguna')->ignore($pengguna->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('pengguna', 'email')->ignore($pengguna->id),
            ],
            'nomor_telepon' => ['nullable', 'regex:/^[0-9]+$/', 'min:8', 'max:20'],
        ], [
            'nama_pengguna.alpha_dash' => 'Nama pengguna hanya boleh berisi huruf, angka, tanda strip, dan garis bawah.',
            'nama_pengguna.unique' => 'Nama pengguna ini sudah digunakan oleh pengguna lain.',
            'email.unique' => 'Email ini sudah digunakan oleh pengguna lain.',
            'nomor_telepon.regex' => 'Nomor telepon hanya boleh berisi angka.',
        ]);

        $pengguna->update($data);

        $this->aktivitas->catat('Memperbarui Profil', $pengguna, "Memperbarui data identitas profil mandiri");

        return back()->with('sukses', 'Data profil Anda berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $pengguna = Auth::user();

        $request->validate([
            'kata_sandi_lama' => ['required', 'string'],
            'kata_sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'kata_sandi_lama.required' => 'Kata sandi saat ini wajib diisi.',
            'kata_sandi.required' => 'Kata sandi baru wajib diisi.',
            'kata_sandi.min' => 'Kata sandi baru minimal terdiri dari 8 karakter.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        if (! Hash::check($request->input('kata_sandi_lama'), $pengguna->kata_sandi)) {
            return back()->withErrors(['kata_sandi_lama' => 'Kata sandi saat ini yang Anda masukkan salah.']);
        }

        $pengguna->update([
            'kata_sandi' => Hash::make($request->input('kata_sandi')),
        ]);

        $this->aktivitas->catat('Mengubah Kata Sandi', $pengguna, 'Mengubah kata sandi akun mandiri');

        return back()->with('sukses', 'Kata sandi Anda berhasil diperbarui. Gunakan kata sandi baru saat login berikutnya.');
    }
}
