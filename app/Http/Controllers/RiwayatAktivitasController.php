<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Modul Riwayat Aktivitas telah dinonaktifkan untuk seluruh hak akses pengguna.
 */
class RiwayatAktivitasController extends Controller
{
    public function index(Request $request)
    {
        abort(403, 'Modul riwayat aktivitas telah dinonaktifkan untuk seluruh hak akses.');
    }
}
