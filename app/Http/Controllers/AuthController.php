<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\RiwayatAktivitasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Alur login dan logout (FR-01). */
class AuthController extends Controller
{
    public function __construct(private readonly RiwayatAktivitasService $aktivitas) {}

    public function formLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            $this->aktivitas->catat(
                'Login gagal',
                'pengguna',
                'Percobaan login gagal untuk "'.Str::limit((string) $request->input('nama_pengguna'), 60).'"'
            );

            throw $e;
        }

        $request->session()->regenerate();

        $this->aktivitas->catat('Login', Auth::user());

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->aktivitas->catat('Logout', Auth::user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah keluar dari PANTRACK.');
    }
}
