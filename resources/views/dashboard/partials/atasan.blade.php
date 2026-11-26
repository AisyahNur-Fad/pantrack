@php
    $atasan = $ringkasan['atasan'] ?? [];
    $distrikList = $ringkasan['rekapDistrik'] ?? collect();
    $maxDistrikHa = max(1, $distrikList->max(fn ($d) => max($d->luas_target, $d->luas_realisasi)));
    $momLuas = $atasan['pertumbuhanLuasMoM'] ?? null;
    $momHasil = $atasan['pertumbuhanHasilMoM'] ?? null;
@endphp

<div class="dasbor-baris-atas">
    {{-- Welcome Banner Atasan --}}
    <div class="sapaan-modern">
        <div class="sapaan-modern__konten">
            <span class="sapaan-modern__peran">
                <i class="bi bi-person-badge"></i> {{ $pengguna->label_peran }}
            </span>
            <h1 class="sapaan-modern__judul">{{ $sapaanWaktu }}, {{ $pengguna->nama_lengkap }}! ☀️</h1>
            <p class="sapaan-modern__deskripsi">
                Executive Overview: Pantau kinerja makro proyek perkebunan, efisiensi konsumsi BBM unit drone, tren pertumbuhan bulanan, serta pembagian hasil BAP resmi 80/15/5.
            </p>
            <a href="{{ route('ekspor.index') }}" class="sapaan-modern__aksi">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span>Ekspor Laporan (Excel 7-Sheet)</span>
            </a>
        </div>
        <div class="sapaan-modern__grafik sembunyi-mobile">
            <svg width="140" height="120" viewBox="0 0 160 140" fill="none">
                <circle cx="80" cy="70" r="40" fill="#14513f" />
                <path d="M55 75 L70 60 L85 70 L105 50" stroke="#f2b134" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                <circle cx="105" cy="50" r="5" fill="#f2b134" />
            </svg>
        </div>
    </div>

    {{-- 3 Kartu Eksekutif Cepat --}}
    <div class="antrean-grid">
        <div class="kartu-antrean kartu-antrean--kuning">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="kartu-antrean__label">Capaian Target SPK</div>
                <div class="kartu-antrean__nilai">{{ $atasan['persenKetercapaian'] ?? 0 }}%</div>
            </div>
            <div class="kartu-antrean__tautan">Target: {{ $format($atasan['luasTargetSpk'] ?? $ringkasan['luasTargetSpk']) }} Ha</div>
        </div>

        <div class="kartu-antrean kartu-antrean--biru">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-droplet-half"></i></div>
                <div class="kartu-antrean__label">Efisiensi BBM</div>
                <div class="kartu-antrean__nilai">{{ $atasan['rasioBbmPerHa'] ?? 0 }} <small style="font-size: 13px;">L/Ha</small></div>
            </div>
            <div class="kartu-antrean__tautan">
                @if (isset($atasan['deltaRasioBbm']) && $atasan['deltaRasioBbm'] != 0)
                    <span>{{ $atasan['deltaRasioBbm'] > 0 ? '+'.$atasan['deltaRasioBbm'] : $atasan['deltaRasioBbm'] }} L vs bln lalu</span>
                @else
                    <span>Rasio Pemakaian Armada</span>
                @endif
            </div>
        </div>

        <div class="kartu-antrean kartu-antrean--hijau">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-pie-chart"></i></div>
                <div class="kartu-antrean__label">Status Disetujui</div>
                <div class="kartu-antrean__nilai">{{ $persenDisetujui }}%</div>
            </div>
            <div class="kartu-antrean__tautan">{{ $jumlahDisetujui }} dari {{ $totalLaporan }} laporan</div>
        </div>
    </div>
</div>

{{-- Header Bagian Periode & Form Filter --}}
<div class="dasbor-bagian-kepala">
    <div>
        <h2 class="dasbor-bagian-judul">Ringkasan Eksekutif & Kinerja Makro</h2>
        <p class="dasbor-bagian-sub">Performa produktivitas dan estimasi finansial periode <strong>{{ $periode }}</strong></p>
    </div>

    <form method="GET" class="filter-periode-modern">
        <select name="bulan" data-kirim-otomatis aria-label="Bulan">
            @foreach (range(1, 12) as $b)
                <option value="{{ $b }}" @selected($b === $bulan)>{{ $namaBulan($b) }}</option>
            @endforeach
        </select>
        <span class="teks-pudar">/</span>
        <select name="tahun" data-kirim-otomatis aria-label="Tahun">
            @foreach (range(now()->year + 1, now()->year - 5) as $t)
                <option value="{{ $t }}" @selected($t === $tahun)>{{ $t }}</option>
            @endforeach
        </select>
        <button type="submit" class="tombol-filter"><i class="bi bi-funnel"></i> Terapkan</button>
    </form>
</div>

{{-- 4 Executive Metric Cards dengan Pertumbuhan MoM --}}
<div class="kpi-grid--4">
    <div class="kartu-kpi kartu-kpi--hijau">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-check2-circle"></i></div>
            <span class="kartu-kpi__tag">
                @if ($momLuas !== null)
                    <span style="color: {{ $momLuas >= 0 ? '#10b981' : '#ef4444' }};">
                        <i class="bi {{ $momLuas >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                        {{ $momLuas > 0 ? '+'.$momLuas : $momLuas }}% MoM
                    </span>
                @else
                    Realisasi
                @endif
            </span>
        </div>
        <div>
            <div class="kartu-kpi__label">Total Luas Realisasi</div>
            <div class="kartu-kpi__nilai">{{ $format($atasan['luasRealisasi'] ?? $ringkasan['luasRealisasi']) }} <small>Ha</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>MTD: {{ $format($ringkasan['luasBulanBerjalan']) }} Ha</span><i class="bi bi-graph-up"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 85%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--kuning">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-cash-coin"></i></div>
            <span class="kartu-kpi__tag">
                @if ($momHasil !== null)
                    <span style="color: {{ $momHasil >= 0 ? '#10b981' : '#ef4444' }};">
                        <i class="bi {{ $momHasil >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                        {{ $momHasil > 0 ? '+'.$momHasil : $momHasil }}% MoM
                    </span>
                @else
                    Pendapatan
                @endif
            </span>
        </div>
        <div>
            <div class="kartu-kpi__label">Total Nilai Pekerjaan</div>
            <div class="kartu-kpi__nilai" style="font-size: 19px;">Rp {{ number_format($atasan['totalNilai'] ?? $ringkasan['totalNilaiRealisasi'], 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Bruto seluruh distrik</span><i class="bi bi-calculator"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 75%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--biru">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-shield-check"></i></div>
            <span class="kartu-kpi__tag">BAP 80%</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Hasil Bersih 80% (BAP)</div>
            <div class="kartu-kpi__nilai" style="font-size: 19px;">Rp {{ number_format($atasan['hasilBersih80'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Porsi unit (Investor 15% · Adm 5%)</span><i class="bi bi-pie-chart"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 80%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--aksen">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-trophy"></i></div>
            <span class="kartu-kpi__tag">{{ $atasan['marginProfitPersen'] ?? 0 }}% Margin</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Estimasi Profit Bersih</div>
            <div class="kartu-kpi__nilai" style="font-size: 19px;">Rp {{ number_format($atasan['estimasiProfitBersih'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Setelah biaya operasional unit</span><i class="bi bi-stars"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: {{ min(100, $atasan['marginProfitPersen'] ?? 70) }}%;"></div></div>
        </div>
    </div>
</div>

{{-- Widget Komposisi Omset & Skema Bagi Hasil Resmi BAP --}}
<div class="kartu-modern mb-5">
    <div class="kartu-modern__kepala flex justify-between items-center">
        <h3 class="kartu-modern__judul flex items-center gap-2">
            <i class="bi bi-pie-chart-fill text-emerald-600"></i>
            Struktur Pendapatan & Distribusi Hasil BAP Resmi (80% / 15% / 5%)
        </h3>
        <span class="text-xs text-slate-400 font-semibold">{{ $periode }}</span>
    </div>
    <div class="kartu-modern__badan" style="padding: 16px 20px;">
        <div class="bap-distribusi-grid">
            {{-- Kolom Kiri: Breakdown Pekerjaan Pokok vs Mapping Add-on --}}
            <div class="panel-klasik">
                <div class="text-xs font-bold text-slate-700 mb-2 flex justify-between items-center">
                    <span>Komposisi Sumber Pendapatan</span>
                    <span class="text-emerald-700 font-extrabold">Rp {{ number_format($atasan['totalNilai'] ?? 0, 0, ',', '.') }}</span>
                </div>
                {{-- Stacked Bar Omset --}}
                <div style="background: #e2e8f0; height: 10px; border-radius: 999px; overflow: hidden; display: flex; margin-bottom: 12px;">
                    <div style="width: {{ $atasan['persenPokok'] ?? 100 }}%; height: 100%; background: #10b981;" title="Pokok: {{ $atasan['persenPokok'] ?? 100 }}%"></div>
                    <div style="width: {{ $atasan['persenMapping'] ?? 0 }}%; height: 100%; background: #0284c7;" title="Mapping: {{ $atasan['persenMapping'] ?? 0 }}%"></div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px;">
                        <span class="text-slate-500 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Aplikasi Pokok:</span>
                        <div class="font-extrabold text-slate-800 text-sm mt-0.5">Rp {{ number_format($atasan['nilaiPokok'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-slate-400 text-xs">{{ $atasan['persenPokok'] ?? 100 }}% kontribusi</div>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px;">
                        <span class="text-slate-500 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-sky-500 inline-block"></span> Mapping Add-on:</span>
                        <div class="font-extrabold text-slate-800 text-sm mt-0.5">Rp {{ number_format($atasan['nilaiMapping'] ?? 0, 0, ',', '.') }}</div>
                        <div class="text-slate-400 text-xs">{{ $atasan['persenMapping'] ?? 0 }}% kontribusi</div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Distribusi Bagi Hasil BAP 80/15/5 --}}
            <div class="panel-klasik">
                <div class="text-xs font-bold text-slate-700 mb-2 flex justify-between items-center">
                    <span>Distribusi Berita Acara Pembayaran (BAP)</span>
                    <span class="text-slate-400 font-normal">Standar Kontrak Resmi</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #d1fae5; border-radius: 8px; padding: 6px 12px; font-size: 12px;">
                        <span class="font-semibold text-emerald-900"><i class="bi bi-check-circle-fill text-emerald-600 mr-1"></i> Unit Operasional (80%)</span>
                        <span class="font-bold text-emerald-700">Rp {{ number_format($atasan['hasilBersih80'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 12px; font-size: 12px;">
                        <span class="font-semibold text-slate-700"><i class="bi bi-people-fill text-blue-600 mr-1"></i> Investor / Pemilik Modal (15%)</span>
                        <span class="font-bold text-slate-700">Rp {{ number_format($atasan['investor15'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 12px; font-size: 12px;">
                        <span class="font-semibold text-slate-700"><i class="bi bi-building text-amber-600 mr-1"></i> Administrasi PT (5%)</span>
                        <span class="font-bold text-slate-700">Rp {{ number_format($atasan['admPt5'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 1. Grafik Batang Komparasi Target vs Realisasi Per Distrik --}}
<div class="chart-kartu">
    <div class="chart-kartu__kepala">
        <h3 class="chart-kartu__judul">
            <i class="bi bi-bar-chart-line text-emerald-600"></i> Kinerja Target vs Realisasi per Distrik (Ha)
        </h3>
        <div class="chart-kartu__legenda">
            <span class="chart-kartu__legenda-item chart-kartu__legenda-item--target">Target SPK</span>
            <span class="chart-kartu__legenda-item chart-kartu__legenda-item--realisasi">Realisasi Disetujui</span>
        </div>
    </div>
    <div class="chart-kartu__badan">
        @if ($distrikList->isEmpty())
            <x-kosong rapat ikon="bi-geo-alt" judul="Belum ada data distrik periode ini" />
        @else
            <div class="chart-komparasi-area">
                <div class="chart-komparasi-grid">
                    @foreach ($distrikList as $d)
                        @php
                            $tinggiTarget = min(100, max(8, round(($d->luas_target / $maxDistrikHa) * 100)));
                            $tinggiRealisasi = min(100, max(8, round(($d->luas_realisasi / $maxDistrikHa) * 100)));
                            $persenCapaian = $d->luas_target > 0 ? round(($d->luas_realisasi / $d->luas_target) * 100) : 0;
                        @endphp
                        <div class="chart-komparasi-kolom" title="{{ $d->nama }}: Target {{ $format($d->luas_target) }} Ha, Realisasi {{ $format($d->luas_realisasi) }} Ha ({{ $persenCapaian }}%)">
                            <div class="chart-komparasi-batang-group">
                                <div class="chart-batang chart-batang--target" style="height: {{ $tinggiTarget }}%;">
                                    <span class="chart-batang-nilai">{{ $format($d->luas_target) }}</span>
                                </div>
                                <div class="chart-batang chart-batang--realisasi" style="height: {{ $tinggiRealisasi }}%;">
                                    <span class="chart-batang-nilai">{{ $format($d->luas_realisasi) }}</span>
                                </div>
                            </div>
                            <div class="chart-komparasi-label">{{ $d->nama }}</div>
                            <div class="chart-komparasi-sub {{ $persenCapaian >= 100 ? 'text-emerald-700' : 'text-blue-600' }}">{{ $persenCapaian }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

{{-- 2. Dua Kolom: Status Laporan Multi-Segment Donut & Produktivitas Tiap Tim --}}
<div class="dasbor-split-bawah">
    {{-- Status Donut Multi-Segment --}}
    <div class="chart-kartu">
        <div class="chart-kartu__kepala">
            <h3 class="chart-kartu__judul">
                <i class="bi bi-pie-chart text-emerald-600"></i> Status Laporan Realisasi — {{ $periode }}
            </h3>
            <a href="{{ route('realisasi.index', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="text-xs font-semibold text-emerald-700 hover:underline flex items-center gap-1" title="Buka seluruh laporan realisasi periode ini">
                <span>Total: {{ $totalLaporan }} Laporan</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="chart-kartu__badan">
            @if ($totalLaporan === 0)
                <x-kosong rapat ikon="bi-clipboard2" judul="Belum ada laporan pada periode ini" />
            @else
                <div class="chart-donut-wadah">
                    <div class="chart-donut-lingkaran">
                        <svg viewBox="0 0 120 120" class="chart-donut-svg">
                            <circle cx="60" cy="60" r="45" fill="none" stroke="#eef3f0" stroke-width="12" />
                            @foreach ($donutSegmen as $kode => $seg)
                                @if ($seg['stroke'] > 0)
                                    <a href="{{ route('realisasi.index', ['status' => $kode, 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                                       aria-label="Lihat laporan {{ $seg['label'] }} ({{ $seg['jumlah'] }} laporan)">
                                        <circle cx="60" cy="60" r="45" fill="none"
                                                stroke="{{ $seg['warna'] }}"
                                                stroke-width="12"
                                                stroke-dasharray="{{ $seg['stroke'] }} {{ $kelilingDonut - $seg['stroke'] }}"
                                                stroke-dashoffset="{{ $seg['offset'] }}"
                                                class="chart-donut-segment"
                                                title="{{ $seg['label'] }}: {{ $seg['jumlah'] }} laporan ({{ $seg['persen'] }}%) &mdash; Klik untuk melihat" />
                                    </a>
                                @endif
                            @endforeach
                        </svg>
                        <a href="{{ route('realisasi.index', ['status' => 'disetujui', 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                           class="chart-donut-tengah"
                           style="text-decoration: none; pointer-events: auto; cursor: pointer;"
                           title="Lihat laporan berstatus Disetujui">
                            <span class="chart-donut-tengah__persen">{{ $persenDisetujui }}%</span>
                            <span class="chart-donut-tengah__label">Disetujui</span>
                        </a>
                    </div>
                    <div class="chart-donut-daftar">
                        @foreach ($donutSegmen as $kode => $seg)
                            <a href="{{ route('realisasi.index', ['status' => $kode, 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                               class="chart-donut-baris chart-donut-baris--tautan"
                               style="text-decoration: none; color: inherit; cursor: pointer;"
                               title="Buka daftar laporan {{ $seg['label'] }} ({{ $seg['jumlah'] }} laporan)">
                                <span class="chart-donut-baris__label">
                                    <span class="chart-donut-baris__titik" style="background: {{ $seg['warna'] }};"></span>
                                    {{ $seg['label'] }}
                                </span>
                                <span class="chart-donut-baris__nilai">
                                    <strong>{{ $seg['jumlah'] }}</strong>
                                    <span class="chart-donut-baris__persen">{{ $seg['persen'] }}%</span>
                                    <i class="bi bi-chevron-right text-slate-400 ms-1" style="font-size: 11px;"></i>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Proportional Stacked Bar --}}
                <div class="chart-bar-tumpuk" title="Distribusi Status Laporan">
                    @foreach ($donutSegmen as $kode => $seg)
                        @if ($seg['persen'] > 0)
                            <a href="{{ route('realisasi.index', ['status' => $kode, 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                               class="chart-bar-tumpuk__segmen"
                               style="width: {{ $seg['persen'] }}%; background: {{ $seg['warna'] }}; display: block; height: 100%; cursor: pointer;"
                               title="{{ $seg['label'] }}: {{ $seg['jumlah'] }} laporan ({{ $seg['persen'] }}%) &mdash; Klik untuk melihat"></a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Tabel Ringkas Produktivitas Tiap Tim Lapangan --}}
    <div class="kartu-modern">
        <div class="kartu-modern__kepala">
            <h3 class="kartu-modern__judul">
                <i class="bi bi-people text-emerald-600"></i> Produktivitas Tiap Tim Lapangan
            </h3>
            <span class="text-xs text-slate-400 font-semibold">{{ $periode }}</span>
        </div>
        <div class="kartu-modern__badan" style="padding: 0;">
            @forelse ($ringkasan['rekapTim'] as $baris)
                @if ($loop->first)
                    <div class="tabel-bungkus"><table class="tabel-mini-modern"><thead><tr><th>Tim</th><th class="angka">Baris Petak</th><th class="angka">Realisasi (Ha)</th></tr></thead><tbody>
                @endif
                <tr>
                    <td class="font-bold text-slate-800">{{ $baris->nama }}</td>
                    <td class="angka text-slate-500">{{ $baris->jumlah_baris }} baris</td>
                    <td class="angka font-bold text-emerald-700">{{ $format($baris->luas) }} Ha</td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <x-kosong rapat ikon="bi-people" judul="Belum ada data tim periode ini" />
            @endforelse
        </div>
    </div>
</div>
