<?php

use App\Models\Peran;

/*
|--------------------------------------------------------------------------
| Konfigurasi tampilan PANTRACK
|--------------------------------------------------------------------------
| Menu sidebar dibaca dari sini. Setiap item:
|   label : teks menu
|   route : nama route tujuan (item otomatis disembunyikan bila route belum ada)
|   pola  : pola routeIs() untuk menandai menu aktif
|   ikon  : kelas Bootstrap Icons
|   peran : daftar peran yang boleh melihat (null = semua peran)
|
| Menu hanyalah navigasi. Pembatasan akses sebenarnya ada di route (middleware `peran`).
*/

return [
    'perusahaan' => 'PT. Panlibata Andalas Bertuah',
    'nama_lengkap' => 'Panlibata Tracking System',

    'menu' => [
        [
            'judul' => 'Utama',
            'item' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'pola' => 'dashboard', 'ikon' => 'bi-speedometer2', 'peran' => null],
            ],
        ],
        [
            'judul' => 'Operasional',
            'item' => [
                ['label' => 'SPK', 'route' => 'spk.index', 'pola' => 'spk.*', 'ikon' => 'bi-file-earmark-text', 'peran' => [Peran::ADMIN, Peran::PEKERJA_LAPANGAN, Peran::KEPALA_LAPANGAN, Peran::ATASAN]],
                ['label' => 'Laporan Realisasi', 'route' => 'realisasi.index', 'pola' => 'realisasi.*', 'ikon' => 'bi-clipboard2-data', 'peran' => [Peran::PEKERJA_LAPANGAN, Peran::KEPALA_LAPANGAN, Peran::ATASAN]],
                ['label' => 'Monitoring BBM', 'route' => 'bbm.index', 'pola' => 'bbm.*', 'ikon' => 'bi-fuel-pump', 'peran' => [Peran::ADMIN, Peran::KEPALA_LAPANGAN, Peran::PEKERJA_LAPANGAN, Peran::ATASAN]],
            ],
        ],
        [
            'judul' => 'Laporan & Keuangan',
            'item' => [
                ['label' => 'Keuangan Unit Drone', 'route' => 'keuangan.index', 'pola' => 'keuangan.*', 'ikon' => 'bi-wallet2', 'peran' => [Peran::ADMIN, Peran::ATASAN]],
                ['label' => 'Ekspor Excel (7-Sheet)', 'route' => 'ekspor.index', 'pola' => 'ekspor.*', 'ikon' => 'bi-file-earmark-excel', 'peran' => [Peran::ADMIN, Peran::ATASAN]],
            ],
        ],
        [
            'judul' => 'Data Master',
            'item' => [
                // 1. Wilayah & Lahan
                ['label' => 'Perusahaan (PT)', 'route' => 'master.pt.index', 'pola' => 'master.pt.*', 'ikon' => 'bi-buildings', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Wilayah & Lahan'],
                ['label' => 'Distrik', 'route' => 'master.distrik.index', 'pola' => 'master.distrik.*', 'ikon' => 'bi-geo-alt', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Wilayah & Lahan'],
                ['label' => 'Petak', 'route' => 'master.petak.index', 'pola' => 'master.petak.*', 'ikon' => 'bi-grid-3x3-gap', 'peran' => [Peran::KEPALA_LAPANGAN], 'sub_grup' => 'Wilayah & Lahan'],

                // 2. Armada & Personil
                ['label' => 'Drone', 'route' => 'master.drone.index', 'pola' => 'master.drone.*', 'ikon' => 'bi-cpu', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Armada & Personil'],
                ['label' => 'Tim', 'route' => 'master.tim.index', 'pola' => 'master.tim.*', 'ikon' => 'bi-people', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Armada & Personil'],
                ['label' => 'Anggota Tim', 'route' => 'master.anggota-tim.index', 'pola' => 'master.anggota-tim.*', 'ikon' => 'bi-person-badge', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Armada & Personil', 'sub_item' => true],

                // 3. Standar & Tarif
                ['label' => 'Jenis Pekerjaan', 'route' => 'master.jenis-pekerjaan.index', 'pola' => 'master.jenis-pekerjaan.*', 'ikon' => 'bi-tools', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Standar & Tarif'],
                ['label' => 'Tarif Pekerjaan', 'route' => 'master.tarif-pekerjaan.index', 'pola' => 'master.tarif-pekerjaan.*', 'ikon' => 'bi-cash-coin', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Standar & Tarif'],

                // 4. Sistem & Logistik
                ['label' => 'Pengguna', 'route' => 'master.pengguna.index', 'pola' => 'master.pengguna.*', 'ikon' => 'bi-person-gear', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Sistem & Logistik'],
                ['label' => 'Jenis BBM', 'route' => 'master.jenis-bbm.index', 'pola' => 'master.jenis-bbm.*', 'ikon' => 'bi-droplet-half', 'peran' => [Peran::ADMIN], 'sub_grup' => 'Sistem & Logistik'],
            ],
        ],
    ],
];
