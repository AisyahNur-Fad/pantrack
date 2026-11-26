<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Resmi 7-Sheet {{ $tim?->drone?->nomor_drone ?? 'UNIT DRONE' }} - {{ $labelPeriode }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 8mm 10mm;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 8pt;
            color: #1F2937;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .header-title {
            text-align: center;
            margin-bottom: 10px;
        }
        .header-title h1 {
            font-size: 13pt;
            color: #0F3D2F;
            margin: 0 0 2px 0;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-title h2 {
            font-size: 10pt;
            color: #1E3A8A;
            margin: 0 0 2px 0;
            font-weight: bold;
        }
        .header-title p {
            font-size: 8pt;
            color: #4B5563;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th {
            background-color: #0F3D2F;
            color: #FFFFFF;
            padding: 4px 5px;
            font-size: 7.5pt;
            text-align: center;
            border: 1px solid #CBD5E1;
            font-weight: bold;
        }
        td {
            padding: 3.5px 5px;
            font-size: 7.5pt;
            border: 1px solid #CBD5E1;
            vertical-align: middle;
        }
        .th-navy { background-color: #1E3A8A; color: #fff; }
        .th-emerald { background-color: #047857; color: #fff; }
        .th-crimson { background-color: #991B1B; color: #fff; }
        .th-sky { background-color: #0284C7; color: #fff; }
        
        .bg-gray { background-color: #F8FAFC; }
        .bg-soft-green { background-color: #D1FAE5; }
        .bg-soft-red { background-color: #FEE2E2; }
        .bg-soft-blue { background-color: #E0E7FF; }
        .bg-soft-teal { background-color: #E6FFFA; }
        .bg-mint { background-color: #ECFDF5; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-bold { font-weight: bold; }
        .text-muted { color: #64748B; }
        .border-double-bottom { border-bottom: 2.5px double #065F46; }

        .kartu-grid {
            width: 100%;
            margin-bottom: 6px;
        }
        .kartu-grid td {
            vertical-align: top;
            padding: 0 4px;
            border: none;
        }
        .slip-box {
            border: 1px solid #CBD5E1;
            margin-bottom: 6px;
        }
        .slip-box-header {
            padding: 4px 6px;
            font-weight: bold;
            font-size: 8pt;
            color: #fff;
        }
        .footer-note {
            font-size: 7pt;
            color: #64748B;
            text-align: right;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 6.5pt;
            border-radius: 3px;
            font-weight: bold;
        }
        .badge-sukses { background-color: #D1FAE5; color: #065F46; }
        .badge-kuning { background-color: #FEF3C7; color: #92400E; }
    </style>
</head>
<body>

    {{-- ========================================== --}}
    {{-- HALAMAN 1: COVER & LEMBAR PENGESAHAN       --}}
    {{-- ========================================== --}}
    <div class="header-title" style="margin-top: 15px; margin-bottom: 18px;">
        <h1>PT. PANLIBATA ANDALAS BERTUAH / DRONE BERTUAH</h1>
        <h2>LAPORAN PENGERJAAN & KEUANGAN UNIT DRONE</h2>
        <p>PERIODE: {{ $labelPeriode }} &bull; DOKUMEN CLOSING RESMI</p>
    </div>

    <table style="width: 80%; margin: 0 auto 18px auto;">
        <thead>
            <tr>
                <th colspan="2" style="font-size: 9pt; padding: 6px; background-color: #0F3D2F;">
                    INFORMASI UNIT & TIM OPERASIONAL LAPANGAN
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 40%; font-weight: bold; background-color: #F8FAFC;">Unit Drone & Tipe</td>
                <td style="font-weight: bold; color: #0F3D2F;">{{ ($tim?->drone?->nomor_drone ?? 'DRONE OPERASIONAL') }}{{ !empty($tim?->drone?->nama_drone) ? ' · '.$tim->drone->nama_drone : '' }} ({{ ($tim?->drone?->tipe_drone ?? 'DJI Agras T50') }})</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #F8FAFC;">Nama Tim Lapangan</td>
                <td>{{ $tim?->nama_tim ?? 'Tim Lapangan' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #F8FAFC;">Pilot Utama</td>
                <td>{{ $namaPilot }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #F8FAFC;">Helper Lapangan</td>
                <td>{{ $namaHelper1 }} & {{ $namaHelper2 }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #F8FAFC;">Total Target Luasan SPK</td>
                <td class="text-right text-bold">{{ number_format($totalLuasSpk, 2, ',', '.') }} Ha</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #F8FAFC;">Total Realisasi Aktual Selesai</td>
                <td class="text-right text-bold" style="color: #059669;">{{ number_format($totalLuasRealisasi, 2, ',', '.') }} Ha</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #F8FAFC;">Nilai Hasil Kotor (Gross Revenue)</td>
                <td class="text-right text-bold">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #D1FAE5;">
                <td style="font-weight: bold; color: #065F46;">Hasil Bersih Unit Operasional (80%)</td>
                <td class="text-right text-bold" style="color: #065F46; font-size: 9pt;">Rp {{ number_format($hasilBersih80Persen, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table style="width: 80%; margin: 25px auto 0 auto; border: none;">
        <tr>
            <td colspan="3" class="text-center text-bold" style="border: none; padding-bottom: 12px; font-size: 9pt; color: #0F3D2F;">
                LEMBAR PENGESAHAN LAPORAN CLOSING BULANAN
            </td>
        </tr>
        <tr>
            <td class="text-center text-bold text-muted" style="width: 33.3%; border: none;">Dibuat Oleh:</td>
            <td class="text-center text-bold text-muted" style="width: 33.3%; border: none;">Diperiksa Oleh:</td>
            <td class="text-center text-bold text-muted" style="width: 33.3%; border: none;">Disetujui Oleh:</td>
        </tr>
        <tr style="height: 50px;">
            <td style="border: none;"></td>
            <td style="border: none;"></td>
            <td style="border: none;"></td>
        </tr>
        <tr>
            <td class="text-center text-bold" style="border: none;">
                <u>( Admin Finance / Tiara )</u><br>
                <span class="text-muted" style="font-size: 7.5pt;">Admin & Keuangan</span>
            </td>
            <td class="text-center text-bold" style="border: none;">
                <u>Icuk Harma</u><br>
                <span class="text-muted" style="font-size: 7.5pt;">Head of Operations</span>
            </td>
            <td class="text-center text-bold" style="border: none;">
                <u>Wahyudi</u><br>
                <span class="text-muted" style="font-size: 7.5pt;">Owner / Direktur Utama</span>
            </td>
        </tr>
    </table>
    <div class="footer-note">Halaman 1 dari 7 &bull; Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</div>

    <div class="page-break"></div>

    {{-- ========================================== --}}
    {{-- HALAMAN 2: REKAP PROFIT & BON OPERASIONAL  --}}
    {{-- ========================================== --}}
    <div class="header-title">
        <h1>REKAPITULASI HASIL, OPERASIONAL & PROFIT UNIT DRONE</h1>
        <p>UNIT: {{ $tim?->drone?->nomor_drone ?? 'UNIT DRONE' }} &bull; PERIODE: {{ $labelPeriode }}</p>
    </div>

    {{-- Tabel A: Pendapatan --}}
    <table>
        <thead>
            <tr>
                <th colspan="3" class="text-left" style="background-color: #0F3D2F; font-size: 8pt;">A. PENDAPATAN & BAGI HASIL PEKERJAAN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 60%;">Total Luasan Realisasi Pekerjaan (Disetujui)</td>
                <td class="text-right text-bold" style="width: 25%;">{{ number_format($totalLuasRealisasi, 2, ',', '.') }}</td>
                <td class="text-center" style="width: 15%;">Ha</td>
            </tr>
            <tr>
                <td>Total Nilai Hasil Kotor (Gross Revenue)</td>
                <td class="text-right text-bold">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Potongan Administrasi & Pajak Perusahaan (ADM 5%)</td>
                <td class="text-right">- Rp {{ number_format($adm5Persen, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Bagi Hasil Pemilik Modal / Investor (15%)</td>
                <td class="text-right">- Rp {{ number_format($investor15Persen, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr style="background-color: #D1FAE5;">
                <td class="text-bold" style="color: #065F46;">HASIL BERSIH UNIT OPERASIONAL (80%)</td>
                <td class="text-right text-bold" style="color: #065F46;">Rp {{ number_format($hasilBersih80Persen, 0, ',', '.') }}</td>
                <td class="text-center text-bold" style="color: #065F46;">Rp</td>
            </tr>
        </tbody>
    </table>

    {{-- Tabel B: Pengeluaran Bon --}}
    <table>
        <thead>
            <tr>
                <th colspan="3" class="text-left th-navy" style="font-size: 8pt;">B. PENGELUARAN & BON OPERASIONAL UNIT (BULAN BERJALAN)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 60%;">Operasional Lapangan (Solar BBM, Belanja Logistik, Antar Drone)</td>
                <td class="text-right" style="width: 25%;">Rp {{ number_format($totalOperasional, 0, ',', '.') }}</td>
                <td class="text-center" style="width: 15%;">Rp</td>
            </tr>
            <tr>
                <td>Bon Suku Cadang / Sparepart (Nomor PI Resmi)</td>
                <td class="text-right">Rp {{ number_format($totalSparepart, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Gaji Bersih Tim Lapangan & Admin (Pilot, Helper 1, Helper 2, Adm)</td>
                <td class="text-right">Rp {{ number_format($totalGajiTim, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Bon Dana Cash / Kasbon Kru Lapangan</td>
                <td class="text-right">Rp {{ number_format($totalKasbonKru, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Bon Ransum Konsumsi Tim & Kantin Distrik</td>
                <td class="text-right">Rp {{ number_format($totalRansumKantin, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Rental Mobil Operasional Unit</td>
                <td class="text-right">Rp {{ number_format($totalRentalMobil, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Upah Langsir Pupuk / Pestisida</td>
                <td class="text-right">Rp {{ number_format($totalUpahLangsir, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Enterteint / Koordinasi Lapangan ({{ number_format($totalLuasRealisasi, 2, ',', '.') }} Ha @ Rp {{ number_format($biayaEnterteintPerHa, 0, ',', '.') }})</td>
                <td class="text-right">Rp {{ number_format($totalEnterteint, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr>
                <td>Bon Pribadi Owner / Keperluan Khusus</td>
                <td class="text-right">Rp {{ number_format($totalBonPribadi, 0, ',', '.') }}</td>
                <td class="text-center">Rp</td>
            </tr>
            <tr style="background-color: #FEE2E2;">
                <td class="text-bold" style="color: #991B1B;">TOTAL PENGELUARAN & BON OPERASIONAL</td>
                <td class="text-right text-bold" style="color: #991B1B;">Rp {{ number_format($totalPengeluaranBon, 0, ',', '.') }}</td>
                <td class="text-center text-bold" style="color: #991B1B;">Rp</td>
            </tr>
        </tbody>
    </table>

    {{-- Tabel C: Profit Unit & Tabel D: Piutang --}}
    <table class="kartu-grid">
        <tr>
            <td style="width: 50%;">
                <table>
                    <thead>
                        <tr>
                            <th colspan="2" class="text-left th-emerald" style="font-size: 8pt;">C. SISA HASIL / PROFIT UNIT DRONE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Hasil Bersih Unit (80%)</td>
                            <td class="text-right text-bold">Rp {{ number_format($hasilBersih80Persen, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Total Bon Operasional</td>
                            <td class="text-right text-bold" style="color: #991B1B;">- Rp {{ number_format($totalPengeluaranBon, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #D1FAE5;" class="border-double-bottom">
                            <td class="text-bold" style="color: #065F46; font-size: 8.5pt;">PROFIT BERSIH DRONE BERTUAH</td>
                            <td class="text-right text-bold" style="color: #065F46; font-size: 8.5pt;">Rp {{ number_format($profitBersihDrone, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 50%;">
                <table>
                    <thead>
                        <tr>
                            <th colspan="2" class="text-left th-crimson" style="font-size: 8pt;">D. REKAPITULASI BON / PIUTANG OWNER (PAK WAHYUDI)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sisa Bon / Piutang Bulan Sebelumnya</td>
                            <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($sisaPiutangBulanLalu, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Penggantian Bon Bulan Berjalan</td>
                            <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($totalBonPribadi, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #FEE2E2;">
                            <td class="text-bold" style="color: #991B1B;">TOTAL SISA SALDO PIUTANG BERJALAN</td>
                            <td class="text-right text-bold" style="color: #991B1B;">- Rp {{ number_format($sisaPiutangBulanLalu + $totalBonPribadi, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    <div class="footer-note">Halaman 2 dari 7 &bull; Rekapitulasi Profit & Bon</div>

    <div class="page-break"></div>

    {{-- ========================================== --}}
    {{-- HALAMAN 3: LAMPIRAN REALISASI PEKERJAAN    --}}
    {{-- ========================================== --}}
    <div class="header-title">
        <h1>LAMPIRAN HASIL PEKERJAAN DRONE SPRAYING & MAPPING</h1>
        <p>PT. PANLIBATA ANDALAS BERTUAH &bull; PERIODE: {{ $labelPeriode }} &bull; STANDAR 14 KOLOM</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">NO</th>
                <th style="width: 7%;">TGL</th>
                <th style="width: 11%;">SPK</th>
                <th style="width: 9%;">TIM</th>
                <th style="width: 8%;">PILOT</th>
                <th style="width: 8%;">HELPER</th>
                <th style="width: 7%;">LUAS SPK</th>
                <th style="width: 12%;">PEKERJAAN</th>
                <th style="width: 7%;">PETAK</th>
                <th style="width: 7%;">LUAS (Ha)</th>
                @if($tampilkanHarga)
                    <th style="width: 7%;">HARGA</th>
                    <th style="width: 8%;">TOTAL</th>
                @endif
                <th style="width: 6%;">CHECK</th>
            </tr>
        </thead>
        <tbody>
            @php
                $subtotalLuasSpk = 0;
                $subtotalLuasRealisasi = 0;
                $subtotalNilai = 0;
            @endphp
            @forelse($daftarDetail as $idx => $d)
                @php
                    $laporan = $d->laporan;
                    $spk = $laporan?->spk;
                    $luasSpkItem = (float) ($d->petak?->luas_petak_ha ?? 0);
                    $luasRealisasiItem = (float) $d->luas_realisasi_ha;
                    $tarifItem = (float) ($d->tarif_per_ha ?? 0);
                    $tarifMapItem = $d->perlu_mapping ? (float) ($d->tarif_mapping_per_ha ?? 30000) : 0.0;
                    $tarifTotalItem = $tarifItem + $tarifMapItem;
                    $totalItem = $luasRealisasiItem * $tarifTotalItem;

                    $subtotalLuasSpk += $luasSpkItem;
                    $subtotalLuasRealisasi += $luasRealisasiItem;
                    $subtotalNilai += $totalItem;

                    $checkTgl = $laporan?->tanggal_pemeriksaan 
                        ? $laporan->tanggal_pemeriksaan->format('d/m/Y') 
                        : ($d->tanggal_pekerjaan ? $d->tanggal_pekerjaan->format('d/m/Y') : 'Disetujui');
                    $namaPekerjaan = ($d->jenisPekerjaan?->nama_pekerjaan ?? '-') . ($d->perlu_mapping ? ' (+Mapping)' : '');
                @endphp
                <tr class="{{ $loop->even ? 'bg-gray' : '' }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $d->tanggal_pekerjaan ? $d->tanggal_pekerjaan->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $spk?->nomor_spk ?? '-' }}</td>
                    <td>{{ $laporan?->tim?->nama_tim ?? '-' }}</td>
                    <td>{{ $laporan?->nama_pilot ?? '-' }}</td>
                    <td>{{ $laporan?->nama_helper ?? '—' }}</td>
                    <td class="text-right">{{ number_format($luasSpkItem, 2, ',', '.') }}</td>
                    <td>{{ $namaPekerjaan }}</td>
                    <td class="text-center text-bold">{{ $d->petak?->kode_petak ?? ($d->kode_petak_manual ?: '-') }}</td>
                    <td class="text-right text-bold">{{ number_format($luasRealisasiItem, 2, ',', '.') }}</td>
                    @if($tampilkanHarga)
                        <td class="text-right">{{ number_format($tarifTotalItem, 0, ',', '.') }}</td>
                        <td class="text-right text-bold">{{ number_format($totalItem, 0, ',', '.') }}</td>
                    @endif
                    <td class="text-center" style="font-size: 7pt; color: #059669; font-weight: bold;">{{ $checkTgl }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $tampilkanHarga ? 13 : 11 }}" class="text-center text-muted" style="padding: 10px;">
                        Tidak ada catatan pekerjaan berstatus disetujui untuk periode ini.
                    </td>
                </tr>
            @endforelse
            <tr style="background-color: #E2E8F0; font-weight: bold;">
                <td colspan="6" class="text-center">TOTAL REKAPITULASI</td>
                <td class="text-right">{{ number_format($totalLuasSpk, 2, ',', '.') }}</td>
                <td colspan="2"></td>
                <td class="text-right">{{ number_format($totalLuasRealisasi, 2, ',', '.') }}</td>
                @if($tampilkanHarga)
                    <td></td>
                    <td class="text-right">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
                @endif
                <td class="text-center" style="color: #065F46;">SELESAI</td>
            </tr>
        </tbody>
    </table>

    @if($tampilkanHarga)
        <table style="width: 50%; margin-left: auto;">
            <tr>
                <td style="width: 60%; font-weight: bold;">Biaya Administrasi Perusahaan (ADM 5%):</td>
                <td class="text-right text-bold" style="width: 40%;">Rp {{ number_format($adm5Persen, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Bagi Hasil Investor Modal (15%):</td>
                <td class="text-right text-bold">Rp {{ number_format($investor15Persen, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #D1FAE5;">
                <td style="font-weight: bold; color: #065F46;">Sisa Bersih Operasional Unit (80%):</td>
                <td class="text-right text-bold" style="color: #065F46;">Rp {{ number_format($hasilBersih80Persen, 0, ',', '.') }}</td>
            </tr>
        </table>
    @endif
    <div class="footer-note">Halaman 3 dari 7 &bull; Lampiran Realisasi Pekerjaan</div>

    <div class="page-break"></div>

    {{-- ========================================== --}}
    {{-- HALAMAN 4: SLIP GAJI KRU & ADMIN           --}}
    {{-- ========================================== --}}
    <div class="header-title">
        <h1>SLIP GAJI & INSENTIF KRU OPERASIONAL DRONE</h1>
        <p>PERIODE: {{ $labelPeriode }} &bull; UNIT: {{ $tim?->drone?->nomor_drone ?? 'UNIT DRONE' }}</p>
    </div>

    @php
        $kruSlip = [
            [
                'jabatan' => 'PILOT DRONE',
                'nama' => $namaPilot,
                'pokok_label' => 'Gaji Pokok Pilot',
                'pokok' => $gajiPokokPilot,
                'makan_label' => 'Uang Makan Pilot Bulanan',
                'makan' => $uangMakanPilot,
                'premi_label' => 'Premi (' . number_format($totalLuasRealisasi, 2, ',', '.') . ' Ha @ Rp ' . number_format($premiPilotPerHa, 0, ',', '.') . ')',
                'premi' => $totalLuasRealisasi * $premiPilotPerHa,
                'kasbon' => $kasbonPilot,
                'ransum' => $ransumPerOrang,
                'gaji_bersih' => $gajiBersihPilot,
                'header_bg' => '#0F3D2F',
            ],
            [
                'jabatan' => 'HELPER 1',
                'nama' => $namaHelper1,
                'pokok_label' => 'Gaji Pokok Helper',
                'pokok' => $gajiPokokHelper,
                'makan_label' => 'Uang Makan (' . $hariKerjaHelper . ' Hari @ Rp ' . number_format($tarifMakanHelperPerHari, 0, ',', '.') . ')',
                'makan' => $uangMakanHelper,
                'premi_label' => 'Premi (' . number_format($totalLuasRealisasi, 2, ',', '.') . ' Ha @ Rp ' . number_format($premiHelperPerHa, 0, ',', '.') . ')',
                'premi' => $totalLuasRealisasi * $premiHelperPerHa,
                'kasbon' => $kasbonHelper1,
                'ransum' => $ransumPerOrang,
                'gaji_bersih' => $gajiBersihHelper1,
                'header_bg' => '#0F3D2F',
            ],
            [
                'jabatan' => 'HELPER 2',
                'nama' => $namaHelper2,
                'pokok_label' => 'Gaji Pokok Helper',
                'pokok' => $gajiPokokHelper,
                'makan_label' => 'Uang Makan (' . $hariKerjaHelper . ' Hari @ Rp ' . number_format($tarifMakanHelperPerHari, 0, ',', '.') . ')',
                'makan' => $uangMakanHelper,
                'premi_label' => 'Premi (' . number_format($totalLuasRealisasi, 2, ',', '.') . ' Ha @ Rp ' . number_format($premiHelperPerHa, 0, ',', '.') . ')',
                'premi' => $totalLuasRealisasi * $premiHelperPerHa,
                'kasbon' => $kasbonHelper2,
                'ransum' => $ransumPerOrang,
                'gaji_bersih' => $gajiBersihHelper2,
                'header_bg' => '#0F3D2F',
            ],
            [
                'jabatan' => 'ADMIN TIM LAPANGAN',
                'nama' => 'TIARA / ADM',
                'pokok_label' => 'Honor ADM Tim Bulanan',
                'pokok' => $honorAdmTim,
                'makan_label' => 'Tunjangan Operasional',
                'makan' => 0,
                'premi_label' => 'Insentif Closing Laporan',
                'premi' => 0,
                'kasbon' => 0,
                'ransum' => 0,
                'gaji_bersih' => $honorAdmTim,
                'header_bg' => '#1E3A8A',
            ],
        ];
    @endphp

    <table class="kartu-grid">
        <tr>
            @foreach(array_slice($kruSlip, 0, 2) as $k)
                <td style="width: 50%;">
                    <div class="slip-box">
                        <div class="slip-box-header" style="background-color: {{ $k['header_bg'] }};">
                            {{ $k['jabatan'] }} : {{ strtoupper($k['nama']) }}
                        </div>
                        <table style="margin: 0; border: none;">
                            <tr>
                                <td style="width: 65%;">{{ $k['pokok_label'] }}</td>
                                <td class="text-right" style="width: 35%;">Rp {{ number_format($k['pokok'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>{{ $k['makan_label'] }}</td>
                                <td class="text-right">Rp {{ number_format($k['makan'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>{{ $k['premi_label'] }}</td>
                                <td class="text-right">Rp {{ number_format($k['premi'], 0, ',', '.') }}</td>
                            </tr>
                            <tr style="background-color: #F1F5F9; font-weight: bold;">
                                <td>Subtotal Pendapatan Kotor</td>
                                <td class="text-right">Rp {{ number_format($k['pokok'] + $k['makan'] + $k['premi'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td style="color: #991B1B;">Potongan Kasbon / Bon Cash</td>
                                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($k['kasbon'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td style="color: #991B1B;">Potongan Bon Ransum (1/3 Bagian)</td>
                                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($k['ransum'], 0, ',', '.') }}</td>
                            </tr>
                            <tr style="background-color: #FEE2E2; font-weight: bold;">
                                <td style="color: #991B1B;">Total Potongan</td>
                                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($k['kasbon'] + $k['ransum'], 0, ',', '.') }}</td>
                            </tr>
                            <tr style="background-color: #D1FAE5; font-weight: bold;" class="border-double-bottom">
                                <td style="color: #065F46;">Gaji Bersih Diterima</td>
                                <td class="text-right" style="color: #065F46; font-size: 8.5pt;">Rp {{ number_format($k['gaji_bersih'], 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            @endforeach
        </tr>
        <tr>
            @foreach(array_slice($kruSlip, 2, 2) as $k)
                <td style="width: 50%;">
                    <div class="slip-box">
                        <div class="slip-box-header" style="background-color: {{ $k['header_bg'] }};">
                            {{ $k['jabatan'] }} : {{ strtoupper($k['nama']) }}
                        </div>
                        <table style="margin: 0; border: none;">
                            <tr>
                                <td style="width: 65%;">{{ $k['pokok_label'] }}</td>
                                <td class="text-right" style="width: 35%;">Rp {{ number_format($k['pokok'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>{{ $k['makan_label'] }}</td>
                                <td class="text-right">Rp {{ number_format($k['makan'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>{{ $k['premi_label'] }}</td>
                                <td class="text-right">Rp {{ number_format($k['premi'], 0, ',', '.') }}</td>
                            </tr>
                            <tr style="background-color: #F1F5F9; font-weight: bold;">
                                <td>Subtotal Pendapatan Kotor</td>
                                <td class="text-right">Rp {{ number_format($k['pokok'] + $k['makan'] + $k['premi'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td style="color: #991B1B;">Potongan Kasbon / Bon Cash</td>
                                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($k['kasbon'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td style="color: #991B1B;">Potongan Bon Ransum (1/3 Bagian)</td>
                                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($k['ransum'], 0, ',', '.') }}</td>
                            </tr>
                            <tr style="background-color: #FEE2E2; font-weight: bold;">
                                <td style="color: #991B1B;">Total Potongan</td>
                                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($k['kasbon'] + $k['ransum'], 0, ',', '.') }}</td>
                            </tr>
                            <tr style="background-color: #D1FAE5; font-weight: bold;" class="border-double-bottom">
                                <td style="color: #065F46;">Gaji Bersih Diterima</td>
                                <td class="text-right" style="color: #065F46; font-size: 8.5pt;">Rp {{ number_format($k['gaji_bersih'], 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <table style="width: 100%; margin-top: 4px;">
        <tr style="background-color: #ECFDF5; font-size: 9pt;">
            <td class="text-bold" style="padding: 6px; color: #064E3B;">TOTAL DANA GAJI & PREMI KRU UNIT (PILOT, HELPER 1, HELPER 2, ADM)</td>
            <td class="text-right text-bold" style="padding: 6px; color: #064E3B; font-size: 9.5pt;">Rp {{ number_format($totalGajiTim, 0, ',', '.') }}</td>
        </tr>
    </table>
    <div class="footer-note">Halaman 4 dari 7 &bull; Slip Gaji Kru Lapangan & Admin Tim</div>

    <div class="page-break"></div>

    {{-- ========================================== --}}
    {{-- HALAMAN 5: RINCIAN OPERASIONAL & BON       --}}
    {{-- ========================================== --}}
    <div class="header-title">
        <h1>RINCIAN PENGELUARAN KAS OPERASIONAL & BON UNIT</h1>
        <p>UNIT: {{ $tim?->drone?->nomor_drone ?? 'UNIT DRONE' }} &bull; PERIODE: {{ $labelPeriode }} &bull; AKUMULASI YTD</p>
    </div>

    {{-- 1. Operasional Lapangan --}}
    <table>
        <thead>
            <tr>
                <th colspan="7" class="text-left" style="background-color: #0F3D2F;">1. OPERASIONAL LAPANGAN (SOLAR BBM, BELANJA LOGISTIK, ANTAR DRONE)</th>
            </tr>
            <tr style="background-color: #E2E8F0; color: #1F2937;">
                <th style="width: 4%;">NO</th>
                <th style="width: 10%;">TANGGAL</th>
                <th style="width: 18%;">KATEGORI</th>
                <th style="width: 32%;">RINCIAN / KETERANGAN</th>
                <th style="width: 12%;">JUMLAH (Rp)</th>
                <th style="width: 12%;">YTD</th>
                <th style="width: 12%;">NO. BUKTI</th>
            </tr>
        </thead>
        <tbody>
            @php
                $ops = $daftarPengeluaran->whereIn('kategori_biaya', [
                    \App\Models\PengeluaranOperasionalUnit::OPERASIONAL_LAPANGAN,
                    \App\Models\PengeluaranOperasionalUnit::RENTAL_MOBIL,
                    \App\Models\PengeluaranOperasionalUnit::UPAH_LANGSIR,
                ]);
                $ytdOps = 0;
            @endphp
            @forelse($ops as $idx => $p)
                @php $ytdOps += (float)$p->nominal; @endphp
                <tr class="{{ $loop->even ? 'bg-gray' : '' }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $p->tanggal_transaksi ? $p->tanggal_transaksi->format('d/m/Y') : '-' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $p->kategori_biaya)) }}</td>
                    <td>{{ $p->keterangan }}</td>
                    <td class="text-right text-bold">{{ number_format($p->nominal, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($ytdOps, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $p->nomor_bukti_pi ?? '-' }}</td>
                </tr>
            @empty
                @if($daftarBbm->isNotEmpty())
                    @foreach($daftarBbm as $b)
                        @php $ytdOps += (float)$b->total_biaya; @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ $b->tanggal_transaksi ? $b->tanggal_transaksi->format('d/m/Y') : '-' }}</td>
                            <td>BBM Solar</td>
                            <td>Pembelian BBM Solar: {{ $b->jumlah_liter }} Liter</td>
                            <td class="text-right text-bold">{{ number_format($b->total_biaya, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($ytdOps, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $b->nomor_struk_nota ?? $b->nomor_transaksi }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada pengeluaran operasional lapangan tercatat.</td></tr>
                @endif
            @endforelse
            <tr style="background-color: #E2E8F0; font-weight: bold;">
                <td colspan="4" class="text-center">SUBTOTAL OPERASIONAL LAPANGAN</td>
                <td class="text-right">Rp {{ number_format($totalOperasional, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalOperasional, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- 2. Sparepart --}}
    <table>
        <thead>
            <tr>
                <th colspan="7" class="text-left th-sky">2. BON SUKU CADANG / SPAREPART (DENGAN NOMOR PI RESMI)</th>
            </tr>
            <tr style="background-color: #E2E8F0; color: #1F2937;">
                <th style="width: 4%;">NO</th>
                <th style="width: 10%;">TANGGAL</th>
                <th style="width: 18%;">NOMOR PI / BON</th>
                <th style="width: 32%;">NAMA SUKU CADANG</th>
                <th style="width: 12%;">JUMLAH (Rp)</th>
                <th style="width: 12%;">YTD</th>
                <th style="width: 12%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sp = $daftarPengeluaran->where('kategori_biaya', \App\Models\PengeluaranOperasionalUnit::SPAREPART);
                $ytdSp = 0;
            @endphp
            @forelse($sp as $idx => $s)
                @php $ytdSp += (float)$s->nominal; @endphp
                <tr class="{{ $loop->even ? 'bg-gray' : '' }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $s->tanggal_transaksi ? $s->tanggal_transaksi->format('d/m/Y') : '-' }}</td>
                    <td class="text-center text-bold">{{ $s->nomor_bukti_pi ?? '-' }}</td>
                    <td>{{ $s->keterangan }}</td>
                    <td class="text-right text-bold">{{ number_format($s->nominal, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($ytdSp, 0, ',', '.') }}</td>
                    <td class="text-center"><span class="badge badge-sukses">Terpasang</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Tidak ada pengeluaran bon suku cadang untuk periode ini.</td></tr>
            @endforelse
            <tr style="background-color: #E0F2FE; font-weight: bold;">
                <td colspan="4" class="text-center">SUBTOTAL BON SUKU CADANG (SPAREPART)</td>
                <td class="text-right">Rp {{ number_format($totalSparepart, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalSparepart, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- 3. Kasbon Kru --}}
    <table>
        <thead>
            <tr>
                <th colspan="7" class="text-left th-navy">3. BON DANA CASH / KASBON KRU LAPANGAN</th>
            </tr>
            <tr style="background-color: #E2E8F0; color: #1F2937;">
                <th style="width: 4%;">NO</th>
                <th style="width: 10%;">TANGGAL</th>
                <th style="width: 18%;">NAMA KRU</th>
                <th style="width: 32%;">KEPERLUAN KASBON</th>
                <th style="width: 12%;">JUMLAH (Rp)</th>
                <th style="width: 12%;">YTD</th>
                <th style="width: 12%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @php $ytdKasbon = 0; @endphp
            @forelse($daftarKasbon as $idx => $kb)
                @php $ytdKasbon += (float)$kb->jumlah_kasbon; @endphp
                <tr class="{{ $loop->even ? 'bg-gray' : '' }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $kb->tanggal_kasbon ? $kb->tanggal_kasbon->format('d/m/Y') : '-' }}</td>
                    <td class="text-bold">{{ $kb->nama_personil }} ({{ strtoupper($kb->peran_kru) }})</td>
                    <td>{{ $kb->keterangan ?? 'Kasbon Operasional' }}</td>
                    <td class="text-right text-bold">{{ number_format($kb->jumlah_kasbon, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($ytdKasbon, 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span class="badge {{ $kb->status_lunas ? 'badge-sukses' : 'badge-kuning' }}">
                            {{ $kb->status_lunas ? 'Lunas' : 'Dipotong Gaji' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">Tidak ada catatan pinjaman kasbon kru untuk periode ini.</td></tr>
            @endforelse
            <tr style="background-color: #FEF3C7; font-weight: bold;">
                <td colspan="4" class="text-center">SUBTOTAL BON DANA CASH / KASBON KRU</td>
                <td class="text-right">Rp {{ number_format($totalKasbonKru, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalKasbonKru, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <div class="footer-note">Halaman 5 dari 7 &bull; Rincian Bon Operasional, Sparepart & Kasbon</div>

    <div class="page-break"></div>

    {{-- ========================================== --}}
    {{-- HALAMAN 6: RANSUM & KANTIN DISTRIK        --}}
    {{-- ========================================== --}}
    <div class="header-title">
        <h1>REKAPITULASI BON RANSUM & KANTIN DISTRIK</h1>
        <p>UNIT: {{ $tim?->drone?->nomor_drone ?? 'UNIT DRONE' }} &bull; PERIODE: {{ $labelPeriode }} &bull; BEBAN KONSUMSI</p>
    </div>

    <table>
        <thead>
            <tr>
                <th colspan="5" class="text-left" style="background-color: #16A34A;">1. BON RANSUM & KONSUMSI TIM DRONE</th>
            </tr>
            <tr style="background-color: #E2E8F0; color: #1F2937;">
                <th style="width: 5%;">NO</th>
                <th style="width: 15%;">TANGGAL</th>
                <th style="width: 45%;">KETERANGAN BELANJA RANSUM</th>
                <th style="width: 20%;">NOMINAL (Rp)</th>
                <th style="width: 15%;">PEMBAGIAN</th>
            </tr>
        </thead>
        <tbody>
            @php $ransumList = $daftarPengeluaran->where('kategori_biaya', \App\Models\PengeluaranOperasionalUnit::RANSUM_KANTIN); @endphp
            @forelse($ransumList as $idx => $r)
                <tr class="{{ $loop->even ? 'bg-gray' : '' }}">
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $r->tanggal_transaksi ? $r->tanggal_transaksi->format('d/m/Y') : '-' }}</td>
                    <td>{{ $r->keterangan }}</td>
                    <td class="text-right text-bold">Rp {{ number_format($r->nominal, 0, ',', '.') }}</td>
                    <td class="text-center">Beban Kru (Dibagi 3)</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">Tidak ada pengeluaran bon ransum tercatat pada periode ini.</td></tr>
            @endforelse
            <tr style="background-color: #DCFCE7; font-weight: bold;">
                <td colspan="3" class="text-center">TOTAL BON RANSUM BULAN BERJALAN</td>
                <td class="text-right">Rp {{ number_format($totalRansumKantin, 0, ',', '.') }}</td>
                <td class="text-center">100%</td>
            </tr>
        </tbody>
    </table>

    <table style="width: 70%; margin-top: 15px;">
        <thead>
            <tr>
                <th colspan="3" class="text-left th-navy">2. PEMBAGIAN BEBAN RANSUM PER PERSONIL (DIPOTONG DARI GAJI)</th>
            </tr>
            <tr style="background-color: #E2E8F0; color: #1F2937;">
                <th style="width: 10%;">NO</th>
                <th style="width: 55%;">NAMA KRU LAPANGAN</th>
                <th style="width: 35%;">POTONGAN RANSUM (1/3 BAGIAN)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td class="text-bold">{{ $namaPilot }} (Pilot)</td>
                <td class="text-right text-bold">Rp {{ number_format($ransumPerOrang, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-center">2</td>
                <td class="text-bold">{{ $namaHelper1 }} (Helper 1)</td>
                <td class="text-right text-bold">Rp {{ number_format($ransumPerOrang, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-center">3</td>
                <td class="text-bold">{{ $namaHelper2 }} (Helper 2)</td>
                <td class="text-right text-bold">Rp {{ number_format($ransumPerOrang, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #E0E7FF; font-weight: bold;">
                <td colspan="2" class="text-center">TOTAL DIBEBANKAN KE KRU LAPANGAN</td>
                <td class="text-right">Rp {{ number_format($ransumPerOrang * 3, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    <div class="footer-note">Halaman 6 dari 7 &bull; Ransum & Kantin Distrik</div>

    <div class="page-break"></div>

    {{-- ========================================== --}}
    {{-- HALAMAN 7: BERITA ACARA PEMBAYARAN (BAP)   --}}
    {{-- ========================================== --}}
    <div class="header-title">
        <h1>BERITA ACARA PEMBAYARAN (BAP) & PROFIT UNIT</h1>
        <p>UNIT: {{ $tim?->drone?->nomor_drone ?? 'UNIT DRONE' }} &bull; PERIODE: {{ $labelPeriode }} &bull; FORMAT RESMI 2-BLOK</p>
    </div>

    {{-- BLOK 1: RINCIAN BON OPERASIONAL --}}
    <table>
        <thead>
            <tr>
                <th colspan="3" class="text-left" style="background-color: #0F3D2F;">BLOK 1: RINCIAN BON OPERASIONAL UNIT DRONE BERTUAH</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 60%;">1. Operasional Lapangan (Solar BBM, Belanja Logistik)</td>
                <td class="text-right" style="width: 20%;">Rp {{ number_format($totalOperasional, 0, ',', '.') }}</td>
                <td class="text-right" style="width: 20%;"></td>
            </tr>
            <tr>
                <td>2. Bon Suku Cadang / Sparepart (PI Resmi)</td>
                <td class="text-right">Rp {{ number_format($totalSparepart, 0, ',', '.') }}</td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td>3. Bon Dana Cash / Kasbon Kru Lapangan</td>
                <td class="text-right">Rp {{ number_format($totalKasbonKru, 0, ',', '.') }}</td>
                <td class="text-right"></td>
            </tr>
            <tr style="background-color: #FEE2E2; font-weight: bold;">
                <td style="color: #991B1B;">TOTAL RINCIAN BON OPERASIONAL</td>
                <td></td>
                <td class="text-right" style="color: #991B1B;">Rp {{ number_format($totalRincian, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- BLOK 2: PEMBAYARAN KANTOR --}}
    <table>
        <thead>
            <tr>
                <th colspan="3" class="text-left th-navy">BLOK 2: PEMBAYARAN LANGSUNG KANTOR (TRANSFER KRU & VENDOR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3" class="text-bold" style="background-color: #F8FAFC;">A. Gaji Bersih Tim Lapangan & Admin:</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">- Pilot ({{ $namaPilot }})</td>
                <td class="text-right">Rp {{ number_format($gajiBersihPilot, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">- Helper 1 ({{ $namaHelper1 }})</td>
                <td class="text-right">Rp {{ number_format($gajiBersihHelper1, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">- Helper 2 ({{ $namaHelper2 }})</td>
                <td class="text-right">Rp {{ number_format($gajiBersihHelper2, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">- Admin Tim (Tiara / ADM)</td>
                <td class="text-right">Rp {{ number_format($honorAdmTim, 0, ',', '.') }}</td>
                <td class="text-right text-bold">Rp {{ number_format($totalGajiTim, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>B. Enterteint & Koordinasi Lapangan</td>
                <td class="text-right">Rp {{ number_format($totalEnterteint, 0, ',', '.') }}</td>
                <td class="text-right text-bold">Rp {{ number_format($totalEnterteint, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>C. Bon Ransum & Logistik Tim</td>
                <td class="text-right">Rp {{ number_format($totalRansumKantin, 0, ',', '.') }}</td>
                <td class="text-right text-bold">Rp {{ number_format($totalRansumKantin, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>D. Rental Mobil Operasional</td>
                <td class="text-right">Rp {{ number_format($totalRentalMobil, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr>
                <td>E. Upah Langsiran Lapangan</td>
                <td class="text-right">Rp {{ number_format($totalUpahLangsir, 0, ',', '.') }}</td>
                <td class="text-right text-bold">Rp {{ number_format($totalRentalMobil + $totalUpahLangsir, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #E0E7FF; font-weight: bold;">
                <td style="color: #1E3A8A;">TOTAL PEMBAYARAN KANTOR</td>
                <td></td>
                <td class="text-right" style="color: #1E3A8A;">Rp {{ number_format($totalPembayaran, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- BLOK 3: REKAPITULASI AKHIR & PROFIT BERSIH --}}
    <table style="width: 75%; margin: 8px auto 14px auto;">
        <thead>
            <tr>
                <th colspan="2" class="th-emerald" style="font-size: 8.5pt;">REKAPITULASI AKHIR PROFIT UNIT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 65%; font-weight: bold;">HASIL BERSIH UNIT (80% NET OPERASIONAL)</td>
                <td class="text-right text-bold" style="width: 35%;">Rp {{ number_format($hasilBersih80Persen, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Operasional & Bon Dana Cash (Blok 1)</td>
                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($totalRincian, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Bayaran Kantor: Gaji, Enterteint, Ransum, Rental (Blok 2)</td>
                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($totalPembayaran, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Bon Lainnya (Bon Pribadi Owner)</td>
                <td class="text-right" style="color: #991B1B;">- Rp {{ number_format($totalBonPribadi, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #D1FAE5;" class="border-double-bottom">
                <td class="text-bold" style="color: #065F46; font-size: 9.5pt;">PROFIT BERSIH DRONE BERTUAH (DITRANSFER)</td>
                <td class="text-right text-bold" style="color: #065F46; font-size: 10pt;">Rp {{ number_format($profitBersihDrone, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table style="width: 75%; margin: 15px auto 0 auto; border: none;">
        <tr>
            <td class="text-center text-bold text-muted" style="width: 50%; border: none;">Made by,</td>
            <td class="text-center text-bold text-muted" style="width: 50%; border: none;">Approve by,</td>
        </tr>
        <tr style="height: 40px;">
            <td style="border: none;"></td>
            <td style="border: none;"></td>
        </tr>
        <tr>
            <td class="text-center text-bold" style="border: none;">
                <u>Tiara</u><br>
                <span class="text-muted" style="font-size: 7.5pt;">Drone Bertuah / Admin</span>
            </td>
            <td class="text-center text-bold" style="border: none;">
                <u>Wahyudi</u><br>
                <span class="text-muted" style="font-size: 7.5pt;">Owner Unit</span>
            </td>
        </tr>
    </table>
    <div class="footer-note">Halaman 7 dari 7 &bull; Berita Acara Pembayaran (BAP)</div>

</body>
</html>

