@extends('layouts.app')

@section('judul', 'Dashboard')

@php
    $format = fn ($angka) => number_format((float) $angka, 2, ',', '.');
    $namaBulan = fn (int $b) => \Carbon\Carbon::create(2000, $b, 1)->translatedFormat('F');
    $periode = $namaBulan($bulan).' '.$tahun;

    $jam = (int) now()->format('H');
    $sapaanWaktu = match (true) {
        $jam >= 4 && $jam < 11 => 'Selamat Pagi',
        $jam >= 11 && $jam < 15 => 'Selamat Siang',
        $jam >= 15 && $jam < 18 => 'Selamat Sore',
        default => 'Selamat Malam',
    };

    $statusLaporan = \App\Models\LaporanRealisasi::statusLaporan();
    $totalLaporan = array_sum($ringkasan['laporanPerStatus']);
    $jumlahDisetujui = $ringkasan['laporanPerStatus']['disetujui'] ?? 0;
    $persenDisetujui = $totalLaporan > 0 ? (int) round(($jumlahDisetujui / $totalLaporan) * 100) : 0;

    // Kalkulasi Arsitektur Multi-Segment Donut SVG
    $kelilingDonut = 282.74; // 2 * pi * 45
    $donutSegmen = [];
    $offsetAkumulasi = 0;
    $warnaStatusHex = [
        'disetujui' => '#10b981',
        'menunggu_pemeriksaan' => '#f59e0b',
        'perlu_perbaikan' => '#3b82f6',
        'draf' => '#94a3b8',
        'ditolak' => '#ef4444',
    ];

    foreach ($statusLaporan as $kode => $label) {
        $jml = $ringkasan['laporanPerStatus'][$kode] ?? 0;
        $persen = $totalLaporan > 0 ? ($jml / $totalLaporan) * 100 : 0;
        $stroke = ($persen / 100) * $kelilingDonut;
        $donutSegmen[$kode] = [
            'label' => $label,
            'jumlah' => $jml,
            'persen' => round($persen, 1),
            'stroke' => $stroke,
            'offset' => -$offsetAkumulasi,
            'warna' => $warnaStatusHex[$kode] ?? '#cbd5e1',
        ];
        $offsetAkumulasi += $stroke;
    }
@endphp

@section('konten')
<div class="dasbor-grid animasi-masuk">

    {{-- ==================== KOLOM KIRI (UTAMA) ==================== --}}
    <div class="dasbor-utama">

        {{-- Partial Dashboard Sesuai Peran Pengguna --}}
        @if ($pengguna->adalahPekerjaLapangan())
            @include('dashboard.partials.pekerja')
        @elseif ($pengguna->adalahKepalaLapangan())
            @include('dashboard.partials.kepala')
        @elseif ($pengguna->adalahAdmin())
            @include('dashboard.partials.admin')
        @else
            @include('dashboard.partials.atasan')
        @endif

        {{-- Banner Aksi Bawah Ekspor (Semua Peran Kecuali Pekerja) --}}
        @if (!$pengguna->adalahPekerjaLapangan())
            <div class="banner-tujuan">
                <div class="banner-tujuan__kiri">
                    <div class="banner-tujuan__ikon"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                    <div>
                        <div class="banner-tujuan__judul">Ekspor Laporan Resmi 7-Sheet & Berita Acara</div>
                        <div class="banner-tujuan__sub">Rekapitulasi otomatis mencakup Realisasi, BBM, Jam Terbang Drone, Slip Gaji Kru, dan BAP Resmi.</div>
                    </div>
                </div>
                <a href="{{ route('ekspor.index') }}" class="banner-tujuan__tombol">
                    <span>Ekspor Sekarang</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        @endif

    </div>

    {{-- ==================== KOLOM KANAN (SIDEBAR WIDGETS) ==================== --}}
    <div class="dasbor-samping">
        @include('dashboard.partials.sidebar-info')
    </div>

</div>
@endsection
