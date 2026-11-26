@php
    $kepala = $ringkasan['kepala'] ?? [];
    $timList = $kepala['performaTim'] ?? collect();
    $maxHa = max(1, $timList->max(fn ($t) => max($t->target_ha, $t->realisasi_ha)));
    $pipeline = $kepala['pipeline'] ?? [];
    $sla = $kepala['slaSummary'] ?? ['total' => 0, 'hijau' => 0, 'kuning' => 0, 'merah' => 0];
    $petakTerkendala = $kepala['petakTerkendala'] ?? collect();
@endphp

<div class="dasbor-baris-atas">
    {{-- Welcome Banner Kepala Lapangan --}}
    <div class="sapaan-modern">
        <div class="sapaan-modern__konten">
            <span class="sapaan-modern__peran">
                <i class="bi bi-person-badge"></i> {{ $pengguna->label_peran }}
            </span>
            <h1 class="sapaan-modern__judul">{{ $sapaanWaktu }}, {{ $pengguna->nama_lengkap }}! ☀️</h1>
            <p class="sapaan-modern__deskripsi">
                Periksa laporan realisasi harian dari tim lapangan, terbitkan SPK target operasional, dan pantau ketercapaian target per distrik.
            </p>
            <a href="{{ route('spk.create') }}" class="sapaan-modern__aksi">
                <i class="bi bi-file-earmark-plus"></i>
                <span>Buat SPK Baru</span>
            </a>
        </div>
        <div class="sapaan-modern__grafik sembunyi-mobile">
            <svg width="140" height="120" viewBox="0 0 160 140" fill="none">
                <ellipse cx="80" cy="70" rx="24" ry="14" fill="#14513f" />
                <ellipse cx="80" cy="68" rx="20" ry="10" fill="#1b6b52" />
                <circle cx="80" cy="66" r="6" fill="#34ab83" />
                <path d="M60 62 L32 40" stroke="#14513f" stroke-width="4.5" stroke-linecap="round" />
                <path d="M100 62 L128 40" stroke="#14513f" stroke-width="4.5" stroke-linecap="round" />
                <ellipse cx="30" cy="38" rx="20" ry="5" fill="#b7e6d2" stroke="#238a68" stroke-width="2" class="rotor" />
                <ellipse cx="130" cy="38" rx="20" ry="5" fill="#b7e6d2" stroke="#238a68" stroke-width="2" class="rotor" />
            </svg>
        </div>
    </div>

    {{-- 3 Antrean Tindakan Cepat Kepala Lapangan --}}
    <div class="antrean-grid">
        <a href="{{ route('realisasi.index', ['status' => 'menunggu_pemeriksaan']) }}" class="kartu-antrean kartu-antrean--kuning" title="Periksa Laporan Realisasi">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-clipboard2-pulse"></i></div>
                <div class="kartu-antrean__label">Laporan Menunggu</div>
                <div class="kartu-antrean__nilai">{{ $ringkasan['menunggu']['laporan'] }}</div>
            </div>
            <div class="kartu-antrean__tautan">Periksa Sekarang <i class="bi bi-chevron-right"></i></div>
        </a>

        <a href="{{ route('spk.index', ['status' => 'menunggu_persetujuan']) }}" class="kartu-antrean kartu-antrean--biru" title="Persetujuan SPK">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-file-earmark-text"></i></div>
                <div class="kartu-antrean__label">SPK Menunggu</div>
                <div class="kartu-antrean__nilai">{{ $ringkasan['menunggu']['spk'] }}</div>
            </div>
            <div class="kartu-antrean__tautan">Lihat Antrean <i class="bi bi-chevron-right"></i></div>
        </a>

        <a href="{{ route('realisasi.index', ['status' => 'perlu_perbaikan']) }}" class="kartu-antrean kartu-antrean--merah" title="Laporan Perlu Perbaikan">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-arrow-repeat"></i></div>
                <div class="kartu-antrean__label">Dalam Perbaikan</div>
                <div class="kartu-antrean__nilai">{{ count($kepala['laporanPerbaikan'] ?? []) }}</div>
            </div>
            <div class="kartu-antrean__tautan">Pantau Revisi <i class="bi bi-chevron-right"></i></div>
        </a>
    </div>
</div>

{{-- Header Bagian Periode & Form Filter --}}
<div class="dasbor-bagian-kepala">
    <div>
        <h2 class="dasbor-bagian-judul">Monitoring Operasional Lapangan</h2>
        <p class="dasbor-bagian-sub">Statistik target SPK vs capaian realisasi periode <strong>{{ $periode }}</strong></p>
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

{{-- 6 KPI Metrik Lapangan --}}
@php
    $capaianSpk = $ringkasan['luasTargetSpk'] > 0 ? min(100, round(($ringkasan['luasRealisasi'] / $ringkasan['luasTargetSpk']) * 100)) : 0;
@endphp
<div class="kpi-grid">
    <div class="kartu-kpi kartu-kpi--biru">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-bullseye"></i></div>
            <span class="kartu-kpi__tag">Target SPK</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Luas Target SPK</div>
            <div class="kartu-kpi__nilai">{{ $format($ringkasan['luasTargetSpk']) }} <small>Ha</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Total target disetujui</span><i class="bi bi-check-circle"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: {{ $capaianSpk }}%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--hijau">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-check2-square"></i></div>
            <span class="kartu-kpi__tag">Tervalidasi</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Luas Realisasi Disetujui</div>
            <div class="kartu-kpi__nilai">{{ $format($ringkasan['luasRealisasi']) }} <small>Ha</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Capaian target: <strong>{{ $capaianSpk }}%</strong></span><i class="bi bi-speedometer2"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: {{ $capaianSpk }}%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--kuning">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-cash-coin"></i></div>
            <span class="kartu-kpi__tag">Nilai Pekerjaan</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Total Nilai Realisasi</div>
            <div class="kartu-kpi__nilai" style="font-size: 20px;">Rp {{ number_format($ringkasan['totalNilaiRealisasi'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Berdasarkan tarif berlaku</span><i class="bi bi-calculator"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: {{ $capaianSpk }}%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--aksen">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-sun"></i></div>
            <span class="kartu-kpi__tag">Hari Ini</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Realisasi Hari Ini</div>
            <div class="kartu-kpi__nilai">{{ $format($ringkasan['luasHariIni']) }} <small>Ha</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Penerbangan harian</span><i class="bi bi-clock-history"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: {{ $ringkasan['luasHariIni'] > 0 ? 100 : 0 }}%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--abu">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-calendar-check"></i></div>
            <span class="kartu-kpi__tag">MTD</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Realisasi MTD</div>
            <div class="kartu-kpi__nilai">{{ $format($ringkasan['luasBulanBerjalan']) }} <small>Ha</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Akumulasi bulan berjalan</span><i class="bi bi-graph-up"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: {{ $capaianSpk }}%;"></div></div>
        </div>
    </div>

    <a href="{{ route('realisasi.index', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="kartu-kpi kartu-kpi--hijau" style="text-decoration: none; color: inherit; display: block;" title="Buka seluruh laporan realisasi periode ini">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-clipboard2-data"></i></div>
            <span class="kartu-kpi__tag">Laporan <i class="bi bi-arrow-up-right"></i></span>
        </div>
        <div>
            <div class="kartu-kpi__label">Total Laporan Masuk</div>
            <div class="kartu-kpi__nilai">{{ $totalLaporan }} <small>Laporan</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>{{ $jumlahDisetujui }} telah disetujui</span><i class="bi bi-check-all"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: {{ $persenDisetujui }}%;"></div></div>
        </div>
    </a>
</div>

{{-- Visual Pipeline: Pre-Flight Mapping vs Aplikasi Drone --}}
<div class="kartu-modern mb-5">
    <div class="kartu-modern__kepala flex justify-between items-center">
        <h3 class="kartu-modern__judul flex items-center gap-2">
            <i class="bi bi-diagram-3-fill text-emerald-600"></i>
            Pipeline Operasional: Pre-Flight Mapping vs Aplikasi Drone
        </h3>
        <span class="text-xs text-slate-400 font-semibold">{{ $periode }}</span>
    </div>
    <div class="kartu-modern__badan" style="padding: 16px 20px;">
        <div class="pipeline-grid">
            {{-- Pipeline 1: Pre-Flight Drone Mapping (Add-on) --}}
            <div class="panel-klasik panel-klasik--info">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2.5">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 15px;">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-sm">Pemetaan Petak (Mapping)</div>
                            <div class="text-xs text-slate-400">Survei kontur & batas terbang petak</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-extrabold text-sky-700 text-base">{{ $pipeline['persenMapping'] ?? 0 }}%</div>
                        <div class="text-xs text-slate-500">{{ $format($pipeline['realisasiMappingHa'] ?? 0) }} / {{ $format($pipeline['targetMappingHa'] ?? 0) }} Ha</div>
                    </div>
                </div>
                <div style="background: #e2e8f0; height: 8px; border-radius: 999px; overflow: hidden; margin-top: 8px;">
                    <div style="width: {{ $pipeline['persenMapping'] ?? 0 }}%; height: 100%; background: linear-gradient(90deg, #38bdf8, #0284c7); border-radius: 999px; transition: width 0.6s ease;"></div>
                </div>
                <div class="text-xs text-slate-500 mt-2 flex justify-between">
                    <span>Layanan Tambahan (Add-on SPK)</span>
                    <span>{{ ($pipeline['targetMappingHa'] ?? 0) > 0 ? 'Sedang berlangsung' : 'Tidak ada target mapping bulan ini' }}</span>
                </div>
            </div>

            {{-- Pipeline 2: Eksekusi Aplikasi Utama (Spraying / Spreading) --}}
            <div class="panel-klasik panel-klasik--sukses">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2.5">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 15px;">
                            <i class="bi bi-droplet-half"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-sm">Aplikasi Semprot & Sebar</div>
                            <div class="text-xs text-slate-400">Spraying pupuk / spreading benih</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-extrabold text-emerald-700 text-base">{{ $pipeline['persenAplikasi'] ?? 0 }}%</div>
                        <div class="text-xs text-slate-500">{{ $format($pipeline['realisasiAplikasiHa'] ?? 0) }} / {{ $format($pipeline['targetAplikasiHa'] ?? 0) }} Ha</div>
                    </div>
                </div>
                <div style="background: #e2e8f0; height: 8px; border-radius: 999px; overflow: hidden; margin-top: 8px;">
                    <div style="width: {{ $pipeline['persenAplikasi'] ?? 0 }}%; height: 100%; background: linear-gradient(90deg, #34d399, #059669); border-radius: 999px; transition: width 0.6s ease;"></div>
                </div>
                <div class="text-xs text-slate-500 mt-2 flex justify-between">
                    <span>Aplikasi Pokok SPK</span>
                    <span>{{ $format(max(0, ($pipeline['targetAplikasiHa'] ?? 0) - ($pipeline['realisasiAplikasiHa'] ?? 0))) }} Ha sisa target</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Peringatan Petak Terkendala di Lapangan (Jika Ada) --}}
@if ($petakTerkendala->isNotEmpty())
    <div class="kartu-peringatan-klasik kartu-peringatan-klasik--peringatan">
        <div class="kartu-peringatan-klasik__kepala">
            <h3 class="kartu-peringatan-klasik__judul text-amber-800">
                <i class="bi bi-exclamation-triangle-fill text-amber-600"></i>
                Kendala Lapangan Butuh Tindak Lanjut ({{ count($petakTerkendala) }} Petak)
            </h3>
            <span class="badge badge--kuning">Perlu Atensi</span>
        </div>
        <div class="kartu-peringatan-klasik__badan">
            <div class="kartu-peringatan-klasik__daftar">
                @foreach ($petakTerkendala as $ptk)
                    <div class="kartu-peringatan-klasik__item">
                        <div class="kartu-peringatan-klasik__item-konten">
                            <div class="font-bold text-slate-800 text-xs">
                                Tim: {{ $ptk->laporan?->tim?->nama_tim ?? '-' }} &middot; Distrik: {{ $ptk->laporan?->distrik?->nama_distrik ?? '-' }}
                                <span class="text-slate-400 font-normal">({{ $ptk->tanggal_pekerjaan?->translatedFormat('d M Y') }})</span>
                            </div>
                            <div class="text-xs text-amber-900 mt-1 font-medium">
                                <i class="bi bi-info-circle mr-1"></i> {{ $ptk->keterangan_kendala ?? 'Terkendala operasional cuaca/teknis' }}
                            </div>
                        </div>
                        <div class="kartu-peringatan-klasik__item-aksi">
                            <a href="{{ route('realisasi.show', $ptk->laporan_realisasi_id) }}" class="tombol tombol--garis tombol--kecil" style="font-size: 11px; padding: 4px 10px; white-space: nowrap;">
                                Lihat Laporan
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

{{-- 1. Grafik Batang Komparasi Capaian Tiap Tim (Target vs Realisasi) --}}
<div class="chart-kartu">
    <div class="chart-kartu__kepala">
        <h3 class="chart-kartu__judul">
            <i class="bi bi-bar-chart-line text-emerald-600"></i> Performa Capaian Target Tiap Tim (Ha)
        </h3>
        <div class="chart-kartu__legenda">
            <span class="chart-kartu__legenda-item chart-kartu__legenda-item--target">Target SPK</span>
            <span class="chart-kartu__legenda-item chart-kartu__legenda-item--realisasi">Realisasi Disetujui</span>
        </div>
    </div>
    <div class="chart-kartu__badan">
        @if ($timList->isEmpty())
            <x-kosong rapat ikon="bi-bar-chart" judul="Belum ada data target atau realisasi tim periode ini" />
        @else
            <div class="chart-komparasi-area">
                <div class="chart-komparasi-grid">
                    @foreach ($timList as $t)
                        @php
                            $tinggiTarget = min(100, max(8, round(($t->target_ha / $maxHa) * 100)));
                            $tinggiRealisasi = min(100, max(8, round(($t->realisasi_ha / $maxHa) * 100)));
                        @endphp
                        <a href="{{ route('realisasi.index', array_filter(['tim_id' => $t->id ?? null, 'bulan' => $bulan, 'tahun' => $tahun])) }}"
                           class="chart-komparasi-kolom"
                           style="text-decoration: none; color: inherit; cursor: pointer;"
                           title="{{ $t->nama_tim }}: Target {{ $format($t->target_ha) }} Ha, Realisasi {{ $format($t->realisasi_ha) }} Ha ({{ $t->persen }}%) &mdash; Klik untuk melihat laporan tim">
                            <div class="chart-komparasi-batang-group">
                                <div class="chart-batang chart-batang--target" style="height: {{ $tinggiTarget }}%;">
                                    <span class="chart-batang-nilai">{{ $format($t->target_ha) }}</span>
                                </div>
                                <div class="chart-batang chart-batang--realisasi" style="height: {{ $tinggiRealisasi }}%;">
                                    <span class="chart-batang-nilai">{{ $format($t->realisasi_ha) }}</span>
                                </div>
                            </div>
                            <div class="chart-komparasi-label">{{ $t->nama_tim }}</div>
                            <div class="chart-komparasi-sub {{ $t->persen >= 100 ? 'text-emerald-700' : 'text-amber-600' }}">{{ $t->persen }}%</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

{{-- 2. Antrean Laporan Menunggu (dengan SLA Tracking) --}}
<div class="kartu-modern mb-5">
    <div class="kartu-modern__kepala">
        <h3 class="kartu-modern__judul">
            <i class="bi bi-hourglass-split text-amber-600"></i> Antrean Laporan Butuh Verifikasi
        </h3>
            <a href="{{ route('realisasi.index', ['status' => 'menunggu_pemeriksaan']) }}" class="text-xs font-bold text-emerald-700 hover:underline">Semua Antrean ({{ $sla['total'] }}) →</a>
        </div>

        {{-- SLA Status Radar Bar --}}
        <div class="sla-radar-bar">
            <span class="badge badge--hijau" style="font-size: 10.5px; padding: 2px 8px;">
                <i class="bi bi-check-circle"></i> &lt; 24 Jam: <strong>{{ $sla['hijau'] }}</strong>
            </span>
            <span class="badge badge--kuning" style="font-size: 10.5px; padding: 2px 8px;">
                <i class="bi bi-clock-history"></i> 24 - 48 Jam: <strong>{{ $sla['kuning'] }}</strong>
            </span>
            @if ($sla['merah'] > 0)
                <span class="badge badge--merah" style="font-size: 10.5px; padding: 2px 8px;">
                    <i class="bi bi-exclamation-triangle-fill"></i> &gt; 48 Jam (Overdue): <strong>{{ $sla['merah'] }}</strong>
                </span>
            @endif
        </div>

        <div class="kartu-modern__badan" style="padding: 0;">
            @forelse ($kepala['antreanLaporan'] ?? [] as $lap)
                @if ($loop->first)
                    <div class="tabel-bungkus">
                    <table class="tabel-mini-modern">
                        <thead>
                            <tr>
                                <th>Laporan & Usia</th>
                                <th>Tim & Petak</th>
                                <th class="angka">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                @endif
                @php
                    $jam = (int) $lap->created_at->diffInHours(now());
                    $slaBadge = match(true) {
                        $jam < 24 => 'badge--hijau',
                        $jam < 48 => 'badge--kuning',
                        default => 'badge--merah',
                    };
                    $slaText = match(true) {
                        $jam < 1 => 'Baru saja',
                        $jam < 24 => $jam.'j lalu',
                        $jam < 48 => '1 hari lalu',
                        default => (int)($jam / 24).'h lalu (Overdue)',
                    };
                @endphp
                <tr>
                    <td>
                        <div class="font-bold text-slate-800">{{ $lap->nomor_laporan }}</div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="badge {{ $slaBadge }}" style="font-size: 10px; padding: 1px 6px;">
                                <i class="bi bi-clock"></i> {{ $slaText }}
                            </span>
                            <span class="text-xs text-slate-400">{{ $lap->created_at?->translatedFormat('d M H:i') }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="font-semibold text-slate-800">{{ $lap->tim->nama_tim ?? '-' }}</div>
                        <div class="text-xs text-slate-500">
                            {{ $lap->distrik->nama_distrik ?? '-' }} &middot; <strong>{{ $format($lap->total_luas_realisasi) }} Ha</strong>
                        </div>
                    </td>
                    <td class="angka">
                        <a href="{{ route('realisasi.show', $lap->id) }}" class="tombol tombol--garis tombol--kecil" style="padding: 5px 12px; font-size: 11.5px; white-space: nowrap;">
                            <i class="bi bi-check2-circle"></i> Periksa
                        </a>
                    </td>
                </tr>
                @if ($loop->last)
                        </tbody>
                    </table>
                    </div>
                @endif
            @empty
                <x-kosong rapat ikon="bi-check-all" judul="Tidak ada laporan menunggu pemeriksaan" />
            @endforelse
        </div>
    </div>

    {{-- Status Donut Multi-Segment --}}
    <div class="chart-kartu mb-5">
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
