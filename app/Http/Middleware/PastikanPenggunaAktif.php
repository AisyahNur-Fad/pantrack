<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jika akun dinonaktifkan admin saat pengguna masih login, sesi langsung diakhiri.
 */
class PastikanPenggunaAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna && ! $pengguna->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['nama_pengguna' => 'Akun Anda sudah dinonaktifkan. Hubungi admin.']);
        }

        return $next($request);
    }
}
