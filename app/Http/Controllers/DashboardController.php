<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Dashboard operasional (FR-22) dengan filter periode (FR-23). */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $filter = $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2020,2100'],
        ]);

        $bulan = (int) ($filter['bulan'] ?? now()->month);
        $tahun = (int) ($filter['tahun'] ?? now()->year);

        return view('dashboard.index', [
            'pengguna' => $request->user(),
            'bulan' => $bulan,
            'tahun' => $tahun,
            'ringkasan' => $this->dashboard->ringkasan($request->user(), $bulan, $tahun),
        ]);
    }
}
