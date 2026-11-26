<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Validasi dan proses autentikasi login (nama pengguna atau email + kata sandi). */
class LoginRequest extends FormRequest
{
    private const MAKS_PERCOBAAN = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_pengguna' => ['required', 'string', 'max:150'],
            'kata_sandi' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_pengguna' => 'nama pengguna',
            'kata_sandi' => 'kata sandi',
        ];
    }

    /**
     * Mencoba login. Hanya akun aktif yang diterima.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->pastikanTidakTerkunci();

        $masukan = trim((string) $this->input('nama_pengguna'));
        $kolom = str_contains($masukan, '@') ? 'email' : 'nama_pengguna';

        $berhasil = Auth::attempt([
            $kolom => $masukan,
            'password' => $this->input('kata_sandi'),
            'aktif' => 1,
        ]);

        if (! $berhasil) {
            RateLimiter::hit($this->kunciPembatas());

            throw ValidationException::withMessages([
                'nama_pengguna' => 'Nama pengguna atau kata sandi salah, atau akun tidak aktif.',
            ]);
        }

        RateLimiter::clear($this->kunciPembatas());
    }

    private function pastikanTidakTerkunci(): void
    {
        if (! RateLimiter::tooManyAttempts($this->kunciPembatas(), self::MAKS_PERCOBAAN)) {
            return;
        }

        event(new Lockout($this));

        $detik = RateLimiter::availableIn($this->kunciPembatas());

        throw ValidationException::withMessages([
            'nama_pengguna' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik.",
        ]);
    }

    private function kunciPembatas(): string
    {
        return Str::lower($this->input('nama_pengguna')).'|'.$this->ip();
    }
}
