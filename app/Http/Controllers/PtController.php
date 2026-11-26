<?php

namespace App\Http\Controllers;

use App\Models\Distrik;
use App\Models\Pt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller API untuk Perusahaan (PT) dan Distrik Cascading Dropdown.
 */
class PtController extends Controller
{
    /**
     * Mengambil daftar semua PT aktif untuk pilihan dropdown.
     * GET /api/pt
     */
    public function apiDaftarPt(): JsonResponse
    {
        $daftarPt = Pt::query()
            ->where('aktif', true)
            ->orderBy('nama_pt')
            ->get(['id', 'nama_pt', 'kode_pt', 'keterangan']);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Daftar perusahaan (PT) aktif berhasil diambil.',
            'data' => $daftarPt,
        ]);
    }

    /**
     * Mengambil daftar Distrik aktif yang difilter berdasarkan pt_id (query param atau all).
     * GET /api/distrik?pt_id={id}
     */
    public function apiDaftarDistrik(Request $request): JsonResponse
    {
        $query = Distrik::query()
            ->where('aktif', true)
            ->with('pt:id,nama_pt,kode_pt');

        if ($request->filled('pt_id')) {
            $query->where('pt_id', $request->input('pt_id'));
        }

        $distrik = $query->orderBy('nama_distrik')->get(['id', 'pt_id', 'kode_distrik', 'nama_distrik', 'keterangan']);

        return response()->json([
            'sukses' => true,
            'pesan' => 'Daftar distrik berhasil diambil.',
            'total' => $distrik->count(),
            'data' => $distrik,
        ]);
    }

    /**
     * Mengambil daftar Distrik aktif untuk PT spesifik melalui route parameter.
     * GET /api/pt/{pt}/distrik
     */
    public function apiDistrikPerPt(Pt $pt): JsonResponse
    {
        $distrik = $pt->distrik()
            ->where('aktif', true)
            ->orderBy('nama_distrik')
            ->get(['id', 'pt_id', 'kode_distrik', 'nama_distrik', 'keterangan']);

        return response()->json([
            'sukses' => true,
            'pesan' => "Daftar distrik untuk {$pt->nama_pt} berhasil diambil.",
            'pt' => [
                'id' => $pt->id,
                'nama_pt' => $pt->nama_pt,
                'kode_pt' => $pt->kode_pt,
            ],
            'total' => $distrik->count(),
            'data' => $distrik,
        ]);
    }
}

