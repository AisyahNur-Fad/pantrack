<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route berdasarkan peran, contoh: ->middleware('peran:admin,kepala_lapangan').
 * Pembatasan dilakukan di backend, bukan sekadar menyembunyikan menu (PRD bab 4).
 */
class BatasiPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $pengguna = $request->user();

        if (! $pengguna || ! $pengguna->punyaPeran(...$peran)) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
        }

        return $next($request);
    }
}
