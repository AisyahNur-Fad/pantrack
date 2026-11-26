@php
    $admin = $ringkasan['admin'] ?? [];
    $distrikList = $ringkasan['rekapDistrik'] ?? collect();
    $maxDistrikHa = max(1, $distrikList->max(fn ($d) => max($d->luas_target, $d->luas_realisasi)));
    $anomaliBbm = $admin['anomaliBbm'] ?? [];
    $droneServis = $admin['droneMendekatiServis'] ?? collect();
@endphp

<div class="dasbor-baris-atas">
    {{-- Welcome Banner Admin --}}
    <div class="sapaan-modern">
        <div class="sapaan-modern__konten">
            <span class="sapaan-modern__peran">
                <i class="bi bi-person-badge"></i> {{ $pengguna->label_peran }}
            </span>
            <h1 class="sapaan-modern__judul">{{ $sapaanWaktu }}, {{ $pengguna->nama_lengkap }}! ☀️</h1>
            <p class="sapaan-modern__deskripsi">
                Kelola verifikasi transaksi BBM, kasbon personil kru, keuangan operasional unit drone, dan pembukuan bulanan sebelum ekspor.
            </p>
            <a href="{{ route('keuangan.index') }}" class="sapaan-modern__aksi">
                <i class="bi bi-wallet2"></i>
                <span>Buka Keuangan Unit</span>
            </a>
        </div>
        <div class="sapaan-modern__grafik sembunyi-mobile">
            <svg width="140" height="120" viewBox="0 0 160 140" fill="none">
                <rect x="30" y="30" width="100" height="80" rx="16" fill="#14513f" />
                <rect x="40" y="40" width="80" height="60" rx="10" fill="#1b6b52" />
                <circle cx="80" cy="70" r="14" fill="#34ab83" />
                <path d="M72 70 L78 76 L90 64" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
    </div>

    {{-- Antrean Tindakan Cepat Admin --}}
    <div class="antrean-grid">
        <a href="{{ route('spk.index', ['status' => 'menunggu_persetujuan']) }}" class="kartu-antrean kartu-antrean--kuning" title="Konfirmasi & Penilaian SPK dari Kepala Lapangan">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-file-earmark-check"></i></div>
                <div class="kartu-antrean__label">SPK Menunggu</div>
                <div class="kartu-antrean__nilai">{{ $ringkasan['menunggu']['spk'] }}</div>
            </div>
            <div class="kartu-antrean__tautan">Nilai & Konfirmasi <i class="bi bi-chevron-right"></i></div>
        </a>

        <a href="{{ route('bbm.index', ['status_verifikasi' => 'menunggu']) }}" class="kartu-antrean kartu-antrean--merah" title="Verifikasi Transaksi BBM">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-fuel-pump"></i></div>
                <div class="kartu-antrean__label">BBM Menunggu</div>
                <div class="kartu-antrean__nilai">{{ $ringkasan['menunggu']['bbm'] }}</div>
            </div>
            <div class="kartu-antrean__tautan">Verifikasi Sekarang <i class="bi bi-chevron-right"></i></div>
        </a>

        <a href="{{ route('keuangan.index') }}" class="kartu-antrean kartu-antrean--kuning" title="Kasbon Kru Belum Lunas">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-cash-stack"></i></div>
                <div class="kartu-antrean__label">Kasbon Belum Lunas</div>
                <div class="kartu-antrean__nilai" style="font-size: 16px;">Rp {{ number_format($admin['kasbonBelumLunas'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="kartu-antrean__tautan">Kelola Kasbon <i class="bi bi-chevron-right"></i></div>
        </a>

        <div class="kartu-antrean kartu-antrean--biru">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-lock"></i></div>
                <div class="kartu-antrean__label">Periode Buku</div>
                <div class="kartu-antrean__nilai" style="font-size: 16px;">{{ ($admin['isTerkunci'] ?? false) ? 'Terkunci' : 'Terbuka' }}</div>
            </div>
            <div class="kartu-antrean__tautan"><span class="badge {{ ($admin['isTerkunci'] ?? false) ? 'text-rose-600' : 'text-emerald-700' }}">{{ ($admin['isTerkunci'] ?? false) ? 'Closed' : 'Active' }}</span></div>
        </div>
    </div>
</div>

{{-- Header Bagian Periode & Form Filter --}}
<div class="dasbor-bagian-kepala">
    <div>
        <h2 class="dasbor-bagian-judul">Ringkasan Finansial & Operasional</h2>
        <p class="dasbor-bagian-sub">Rangkuman biaya, BBM, dan aset periode <strong>{{ $periode }}</strong></p>
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

{{-- Peringatan Radar Anomali Konsumsi BBM (> 1.50 L/Ha) --}}
@if (!empty($anomaliBbm))
    <div class="kartu-peringatan-klasik kartu-peringatan-klasik--bahaya">
        <div class="kartu-peringatan-klasik__kepala">
            <h3 class="kartu-peringatan-klasik__judul text-rose-800">
                <i class="bi bi-shield-exclamation text-rose-600"></i>
                Radar Anomali Rasio BBM: {{ count($anomaliBbm) }} Tim Melebihi Batas Wajar (> 1.50 L/Ha)
            </h3>
            <span class="badge badge--merah">Perhatian Finansial</span>
        </div>
        <div class="kartu-peringatan-klasik__badan">
            <p class="text-xs text-rose-800 mb-3 font-medium">
                Tim-tim berikut terdeteksi menggunakan bahan bakar genset di atas standar normal (1.20 L/Ha). Disarankan untuk mengecek kondisi genset atau memverifikasi bukti struk bon BBM:
            </p>
            <div class="grid-kolom-2">
                @foreach ($anomaliBbm as $anomali)
                    <div class="kartu-peringatan-klasik__item">
                        <div class="kartu-peringatan-klasik__item-konten">
                            <div class="font-bold text-slate-800 text-sm">{{ $anomali['nama_tim'] }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                Luas: <strong>{{ $format($anomali['luas_ha']) }} Ha</strong> &middot; BBM: <strong>{{ number_format($anomali['total_liter'], 1, ',', '.') }} L</strong>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-extrabold text-rose-600 text-sm">{{ $anomali['rasio_l_per_ha'] }} L/Ha</div>
                            <div class="text-xs text-rose-500 font-semibold">+{{ $anomali['kelebihan_persen'] }}% dari wajar</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

{{-- 6 KPI Metrik Admin --}}
<div class="kpi-grid">
    <div class="kartu-kpi kartu-kpi--kuning">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-cash-coin"></i></div>
            <span class="kartu-kpi__tag">Nilai Pekerjaan</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Total Nilai Pekerjaan</div>
            <div class="kartu-kpi__nilai" style="font-size: 20px;">Rp {{ number_format($ringkasan['totalNilaiRealisasi'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Pendapatan kotor unit</span><i class="bi bi-calculator"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 80%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--abu">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-wallet"></i></div>
            <span class="kartu-kpi__tag">Bon Operasional</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Pengeluaran Bon Unit</div>
            <div class="kartu-kpi__nilai" style="font-size: 20px;">Rp {{ number_format($admin['totalPengeluaranBon'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Rental, sparepart, logistik</span><i class="bi bi-receipt"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 60%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--biru">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-person-lines-fill"></i></div>
            <span class="kartu-kpi__tag">Kasbon Periode</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Total Kasbon Periode Ini</div>
            <div class="kartu-kpi__nilai" style="font-size: 20px;">Rp {{ number_format($admin['totalKasbonPeriode'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Pinjaman kru tercatat</span><i class="bi bi-cash"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 50%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--hijau">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-cpu"></i></div>
            <span class="kartu-kpi__tag">Armada Drone</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Drone Aktif Beroperasi</div>
            <div class="kartu-kpi__nilai">{{ $admin['droneAktif'] ?? 0 }} <small>/ {{ $admin['totalDrone'] ?? 0 }} Unit</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>Status siap terbang</span><i class="bi bi-check2"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 90%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--aksen">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-check2-square"></i></div>
            <span class="kartu-kpi__tag">Realisasi Ha</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Luas Realisasi (Disetujui)</div>
            <div class="kartu-kpi__nilai">{{ $format($ringkasan['luasRealisasi']) }} <small>Ha</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>MTD: {{ $format($ringkasan['luasBulanBerjalan']) }} Ha</span><i class="bi bi-graph-up"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 75%;"></div></div>
        </div>
    </div>

    <div class="kartu-kpi kartu-kpi--hijau">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-clipboard2-data"></i></div>
            <span class="kartu-kpi__tag">Laporan</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Total Laporan Realisasi</div>
            <div class="kartu-kpi__nilai">{{ $totalLaporan }} <small>Laporan</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan"><span>{{ $jumlahDisetujui }} disetujui</span><i class="bi bi-check-all"></i></div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 85%;"></div></div>
        </div>
    </div>
</div>

{{-- Widget Kesehatan Finansial Kru & Kasbon Safety Ratio --}}
<div class="kartu-modern mb-5">
    <div class="kartu-modern__kepala flex justify-between items-center">
        <h3 class="kartu-modern__judul flex items-center gap-2">
            <i class="bi bi-shield-check text-emerald-600"></i>
            Kesehatan Finansial Kru & Rasio Keamanan Kasbon
        </h3>
        @php
            $safetyRatio = $admin['kasbonSafetyRatio'] ?? 0;
            $safetyStatus = $admin['kasbonSafetyStatus'] ?? 'aman';
            $badgeColor = match($safetyStatus) {
                'aman' => 'badge--hijau',
                'waspada' => 'badge--kuning',
                default => 'badge--merah',
            };
            $statusLabel = match($safetyStatus) {
                'aman' => 'Rasio Kasbon Aman (≤ 40%)',
                'waspada' => 'Rasio Kasbon Waspada (40-70%)',
                default => 'Rasio Kasbon Kritis (> 70%)',
            };
        @endphp
        <span class="badge {{ $badgeColor }}">
            <i class="bi bi-check-circle-fill"></i> {{ $statusLabel }}
        </span>
    </div>
    <div class="kartu-modern__badan" style="padding: 16px 20px;">
        <div class="kasbon-safety-grid">
            {{-- Kolom 1: Kasbon Kru Belum Lunas --}}
            <div class="panel-klasik panel-klasik--bahaya">
                <div class="text-xs text-rose-700 font-bold mb-1">Total Kasbon Belum Lunas</div>
                <div class="text-xl font-extrabold text-rose-900">
                    Rp {{ number_format($admin['kasbonBelumLunas'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-xs text-rose-600 mt-1 flex justify-between">
                    <span>Pilot: Rp {{ number_format($admin['kasbonPilot'] ?? 0, 0, ',', '.') }}</span>
                    <span>Helper: Rp {{ number_format($admin['kasbonHelper'] ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Kolom 2: Estimasi Insentif Terbang Periode Ini --}}
            <div class="panel-klasik panel-klasik--sukses">
                <div class="text-xs text-emerald-700 font-bold mb-1">Estimasi Hak Premi Terbang Kru</div>
                <div class="text-xl font-extrabold text-emerald-900">
                    Rp {{ number_format($admin['totalHakPremiKru'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-xs text-emerald-600 mt-1">
                    Plafon penjamin potongan slip gaji kru
                </div>
            </div>

            {{-- Kolom 3: Kasbon Safety Ratio Bar --}}
            <div class="panel-klasik">
                <div class="flex justify-between items-center text-xs font-bold mb-1.5">
                    <span class="text-slate-600">Rasio Kasbon terhadap Premi</span>
                    <span class="{{ $safetyStatus === 'aman' ? 'text-emerald-700' : ($safetyStatus === 'waspada' ? 'text-amber-600' : 'text-rose-600') }}">
                        {{ $safetyRatio }}%
                    </span>
                </div>
                <div style="background: #e2e8f0; height: 10px; border-radius: 999px; overflow: hidden; margin-bottom: 6px;">
                    <div style="width: {{ min(100, $safetyRatio) }}%; height: 100%; background: {{ $safetyStatus === 'aman' ? '#10b981' : ($safetyStatus === 'waspada' ? '#f59e0b' : '#ef4444') }}; border-radius: 999px; transition: width 0.6s ease;"></div>
                </div>
                <div class="text-xs text-slate-500">
                    {{ $safetyStatus === 'aman' ? 'Kru memiliki margin penghasilan aman setelah potongan kasbon.' : 'Kru mendekati batas maksimal pinjaman. Batasi pengajuan kasbon baru.' }}
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

{{-- 2. Dua Kolom: Transaksi BBM Menunggu & Kasbon Personil Terbaru --}}
<div class="dasbor-split-bawah">
    {{-- Antrean Transaksi BBM --}}
    <div class="kartu-modern">
        <div class="kartu-modern__kepala">
            <h3 class="kartu-modern__judul">
                <i class="bi bi-fuel-pump text-rose-600"></i> Antrean BBM Menunggu Verifikasi
            </h3>
            <a href="{{ route('bbm.index', ['status_verifikasi' => 'menunggu']) }}" class="text-xs font-bold text-emerald-700 hover:underline">Semua BBM →</a>
        </div>
        <div class="kartu-modern__badan" style="padding: 0;">
            @forelse ($admin['transaksiBbmMenunggu'] ?? [] as $tBbm)
                @if ($loop->first)
                    <div class="tabel-bungkus"><table class="tabel-mini-modern"><thead><tr><th>Tim & Armada</th><th class="angka">Liter / Biaya</th><th class="angka">Aksi</th></tr></thead><tbody>
                @endif
                <tr>
                    <td>
                        <div class="font-bold text-slate-800">{{ $tBbm->tim->nama_tim ?? '-' }}</div>
                        <div class="text-xs text-slate-400">
                            {{ $tBbm->tanggal_transaksi?->translatedFormat('d M Y') }} &middot; 
                            {{ $tBbm->jenisBbm->nama_bbm ?? 'Solar Genset' }}
                            @if (!empty($tBbm->nomor_struk_nota))
                                &middot; Nota: {{ $tBbm->nomor_struk_nota }}
                            @endif
                        </div>
                    </td>
                    <td class="angka">
                        <div class="font-bold text-slate-700">{{ number_format($tBbm->jumlah_liter, 1, ',', '.') }} L</div>
                        <div class="text-xs text-slate-500">Rp {{ number_format($tBbm->total_biaya ?? 0, 0, ',', '.') }}</div>
                    </td>
                    <td class="angka">
                        <a href="{{ route('bbm.show', $tBbm->id) }}" class="tombol tombol--garis tombol--kecil" style="padding: 5px 12px; font-size: 11.5px; white-space: nowrap;">
                            <i class="bi bi-shield-check"></i> Verifikasi
                        </a>
                    </td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <x-kosong rapat ikon="bi-check-all" judul="Semua transaksi BBM sudah diverifikasi" />
            @endforelse
        </div>
    </div>

    {{-- Catatan Kasbon Terbaru --}}
    <div class="kartu-modern">
        <div class="kartu-modern__kepala">
            <h3 class="kartu-modern__judul">
                <i class="bi bi-cash-stack text-emerald-600"></i> Kasbon Personil Terbaru
            </h3>
            <a href="{{ route('keuangan.index') }}" class="text-xs font-bold text-emerald-700 hover:underline">Kelola Kasbon →</a>
        </div>
        <div class="kartu-modern__badan" style="padding: 0;">
            @forelse ($admin['kasbonTerbaru'] ?? [] as $k)
                @if ($loop->first)
                    <div class="tabel-bungkus"><table class="tabel-mini-modern"><thead><tr><th>Personil</th><th>Jumlah</th><th class="angka">Status</th></tr></thead><tbody>
                @endif
                <tr>
                    <td>
                        <div class="font-bold text-slate-800">{{ $k->nama_personil }}</div>
                        <div class="text-xs text-slate-400">{{ $k->tim->nama_tim ?? '-' }} · {{ ucfirst($k->peran_kru) }}</div>
                    </td>
                    <td class="font-bold text-slate-700">Rp {{ number_format($k->jumlah_kasbon, 0, ',', '.') }}</td>
                    <td class="angka">
                        <span class="badge {{ $k->status_lunas ? 'badge--sukses' : 'badge--kuning' }}" style="font-size: 11px;">
                            {{ $k->status_lunas ? 'Lunas' : 'Belum Lunas' }}
                        </span>
                    </td>
                </tr>
                @if ($loop->last)</tbody></table></div>@endif
            @empty
                <x-kosong rapat ikon="bi-cash" judul="Belum ada catatan kasbon pada periode ini" />
            @endforelse
        </div>
    </div>
</div>
