@php
    $pekerja = $ringkasan['pekerja'] ?? [];
    $timSaya = $pekerja['tim'] ?? null;
    $labelPeran = $pekerja['labelPeranKru'] ?? ($pengguna->label_peran ?? 'Pekerja Lapangan');
    $isHelper = $pekerja['isHelper'] ?? false;
    $droneInfo = $pekerja['droneInfo'] ?? null;

    $totalKotor = max(1, ($pekerja['gajiPokok'] ?? 0) + ($pekerja['uangMakan'] ?? 0) + ($pekerja['totalPremi'] ?? 0));
    $persenPokok = round((($pekerja['gajiPokok'] ?? 0) / $totalKotor) * 100);
    $persenMakan = round((($pekerja['uangMakan'] ?? 0) / $totalKotor) * 100);
    $persenPremi = max(0, 100 - ($persenPokok + $persenMakan));
@endphp

{{-- Row Atas: Sapaan Pekerja & Kartu Penghasilan Utama --}}
<div class="dasbor-baris-atas">
    {{-- Welcome Banner Pekerja --}}
    <div class="sapaan-modern">
        <div class="sapaan-modern__konten">
            <span class="sapaan-modern__peran">
                <i class="bi bi-person-badge"></i> {{ $labelPeran }} · {{ $timSaya->nama_tim ?? 'Tim Anda' }}
            </span>
            <h1 class="sapaan-modern__judul">{{ $sapaanWaktu }}, {{ $pengguna->nama_lengkap }}! ☀️</h1>
            <p class="sapaan-modern__deskripsi">
                Armada: <strong>{{ $timSaya->drone->nomor_drone ?? 'Drone Aktif' }}{{ !empty($timSaya->drone?->nama_drone) ? ' · '.$timSaya->drone->nama_drone : '' }}</strong> ({{ $timSaya->drone->tipe_drone ?? '-' }}). Pantau estimasi penghasilan premi terbang, kerjakan SPK aktif, dan catat realisasi harian Anda.
            </p>
            <a href="{{ route('realisasi.create') }}" class="sapaan-modern__aksi">
                <i class="bi bi-plus-circle"></i>
                <span>Input Realisasi Hari Ini</span>
            </a>
        </div>
        <div class="sapaan-modern__grafik sembunyi-mobile">
            <svg width="140" height="120" viewBox="0 0 160 140" fill="none">
                <ellipse cx="80" cy="70" rx="24" ry="14" fill="#14513f" />
                <ellipse cx="80" cy="68" rx="20" ry="10" fill="#1b6b52" />
                <circle cx="80" cy="66" r="6" fill="#34ab83" />
                <path d="M60 62 L32 40" stroke="#14513f" stroke-width="4.5" stroke-linecap="round" />
                <path d="M100 62 L128 40" stroke="#14513f" stroke-width="4.5" stroke-linecap="round" />
                <path d="M62 76 L34 98" stroke="#14513f" stroke-width="4.5" stroke-linecap="round" />
                <path d="M98 76 L126 98" stroke="#14513f" stroke-width="4.5" stroke-linecap="round" />
                <ellipse cx="30" cy="38" rx="20" ry="5" fill="#b7e6d2" stroke="#238a68" stroke-width="2" class="rotor" />
                <circle cx="30" cy="38" r="3.5" fill="#0f3d2f" />
                <ellipse cx="130" cy="38" rx="20" ry="5" fill="#b7e6d2" stroke="#238a68" stroke-width="2" class="rotor" />
                <circle cx="130" cy="38" r="3.5" fill="#0f3d2f" />
                <ellipse cx="32" cy="100" rx="20" ry="5" fill="#b7e6d2" stroke="#238a68" stroke-width="2" class="rotor" />
                <circle cx="32" cy="100" r="3.5" fill="#0f3d2f" />
                <ellipse cx="128" cy="100" rx="20" ry="5" fill="#b7e6d2" stroke="#238a68" stroke-width="2" class="rotor" />
                <circle cx="128" cy="100" r="3.5" fill="#0f3d2f" />
            </svg>
        </div>
    </div>

    {{-- 3 Antrean Tindakan Cepat Pekerja --}}
    <div class="antrean-grid">
        <a href="{{ route('spk.index') }}" class="kartu-antrean kartu-antrean--biru" title="Lihat SPK Tim">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-file-earmark-text"></i></div>
                <div class="kartu-antrean__label">SPK Tim Aktif</div>
                <div class="kartu-antrean__nilai">{{ count($pekerja['spkAktif'] ?? []) }}</div>
            </div>
            <div class="kartu-antrean__tautan">Buka SPK <i class="bi bi-chevron-right"></i></div>
        </a>

        <a href="{{ route('realisasi.index', ['status' => 'perlu_perbaikan']) }}" class="kartu-antrean kartu-antrean--merah" title="Laporan Perlu Perbaikan">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="kartu-antrean__label">Perlu Perbaikan</div>
                <div class="kartu-antrean__nilai">{{ count($pekerja['laporanPerluPerbaikan'] ?? []) }}</div>
            </div>
            <div class="kartu-antrean__tautan">Revisi Sekarang <i class="bi bi-chevron-right"></i></div>
        </a>

        <a href="{{ route('bbm.create') }}" class="kartu-antrean kartu-antrean--kuning" title="Catat BBM Tim">
            <div>
                <div class="kartu-antrean__ikon"><i class="bi bi-fuel-pump"></i></div>
                <div class="kartu-antrean__label">Catat BBM</div>
                <div class="kartu-antrean__nilai"><i class="bi bi-plus-lg text-lg"></i></div>
            </div>
            <div class="kartu-antrean__tautan">Input Bon BBM <i class="bi bi-chevron-right"></i></div>
        </a>
    </div>
</div>

{{-- Peringatan & Daftar Tindakan Cepat: Laporan Perlu Perbaikan Segera --}}
@if (!empty($pekerja['laporanPerluPerbaikan']) && count($pekerja['laporanPerluPerbaikan']) > 0)
    <div class="kartu-peringatan-klasik kartu-peringatan-klasik--bahaya">
        <div class="kartu-peringatan-klasik__kepala">
            <h3 class="kartu-peringatan-klasik__judul text-rose-800">
                <i class="bi bi-exclamation-octagon-fill text-rose-600"></i>
                Laporan Memerlukan Perbaikan Segera ({{ count($pekerja['laporanPerluPerbaikan']) }})
            </h3>
            <a href="{{ route('realisasi.index', ['status' => 'perlu_perbaikan']) }}" class="text-xs font-bold text-rose-700 hover:underline">
                Buka Semua Filter Perlu Perbaikan &rarr;
            </a>
        </div>
        <div class="kartu-peringatan-klasik__badan">
            <p class="text-xs text-rose-800 mb-3 font-medium">
                Laporan realisasi berikut dikembalikan oleh Kepala Lapangan. Perbaiki segera agar laporan dapat disetujui dan masuk kalkulasi premi terbang:
            </p>
            <div class="kartu-peringatan-klasik__daftar">
                @foreach ($pekerja['laporanPerluPerbaikan'] as $lap)
                    <div class="kartu-peringatan-klasik__item">
                        <div class="kartu-peringatan-klasik__item-konten">
                            <div class="flex items-center gap-2 font-bold text-slate-800 text-sm">
                                <span class="badge badge--merah" style="font-size: 10.5px; padding: 2px 8px;">Revisi</span>
                                <span>SPK: {{ $lap->spk->nomor_spk ?? '-' }}</span>
                                <span class="text-slate-400 font-normal">&middot; {{ $lap->created_at?->translatedFormat('d M Y') }}</span>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                Distrik: <strong>{{ $lap->distrik->nama_distrik ?? '-' }}</strong> &middot; 
                                Luas: <strong>{{ $format($lap->total_luas_realisasi ?? 0) }} Ha</strong>
                                @if ($lap->pemeriksa)
                                    &middot; Diperiksa oleh: <strong>{{ $lap->pemeriksa->nama_lengkap }}</strong>
                                @endif
                            </div>
                            @if (!empty($lap->catatan_pemeriksaan))
                                <div class="catatan-inspeksi-box">
                                    <i class="bi bi-chat-left-dots-fill text-rose-500 mr-1"></i>
                                    <strong>Catatan Pemeriksa:</strong> {{ $lap->catatan_pemeriksaan }}
                                </div>
                            @endif
                        </div>
                        <div class="kartu-peringatan-klasik__item-aksi">
                            <a href="{{ route('realisasi.edit', $lap->id) }}" class="tombol tombol--utama tombol--kecil" style="font-size: 12px; padding: 6px 14px; white-space: nowrap;">
                                <i class="bi bi-pencil-square"></i> Perbaiki Sekarang
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

{{-- Header Bagian Periode & Form Filter --}}
<div class="dasbor-bagian-kepala">
    <div>
        <h2 class="dasbor-bagian-judul">Ringkasan Operasional & Penghasilan</h2>
        <p class="dasbor-bagian-sub">Perhitungan performa dan slip gaji estimasi periode <strong>{{ $periode }}</strong></p>
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

{{-- 6 Grid Kartu Khusus Pekerja (Penghasilan & Terbang) --}}
<div class="kpi-grid">
    {{-- KPI 1: Estimasi Penghasilan Bersih (Take Home Pay) --}}
    <div class="kartu-kpi kartu-kpi--hijau">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-wallet2"></i></div>
            <span class="kartu-kpi__tag">{{ $labelPeran }}</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Penghasilan Bersih (THP)</div>
            <div class="kartu-kpi__nilai" style="font-size: 20px;">Rp {{ number_format($pekerja['estimasiBersih'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan">
                <span>Setelah potong kasbon</span>
                <i class="bi bi-check-circle text-emerald-600"></i>
            </div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 85%;"></div></div>
        </div>
    </div>

    {{-- KPI 2: Premi Terbang Tim --}}
    <div class="kartu-kpi kartu-kpi--kuning">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-award"></i></div>
            <span class="kartu-kpi__tag">Premi/Ha</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Premi Terbang Disetujui</div>
            <div class="kartu-kpi__nilai" style="font-size: 20px;">Rp {{ number_format($pekerja['totalPremi'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan">
                <span>{{ $format($pekerja['luasDisetujuiTim'] ?? 0) }} Ha × Rp {{ number_format($pekerja['premiPerHa'] ?? 0, 0, ',', '.') }}</span>
                <i class="bi bi-speedometer2"></i>
            </div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 75%;"></div></div>
        </div>
    </div>

    {{-- KPI 3: Gaji Pokok & Uang Makan --}}
    <div class="kartu-kpi kartu-kpi--biru">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-cash-stack"></i></div>
            <span class="kartu-kpi__tag">{{ $isHelper ? 'Makan Harian' : 'Gaji + Makan' }}</span>
        </div>
        <div>
            <div class="kartu-kpi__label">{{ $isHelper ? 'Uang Makan Kerja' : 'Gaji Pokok & Makan' }}</div>
            <div class="kartu-kpi__nilai" style="font-size: 20px;">Rp {{ number_format(($pekerja['gajiPokok'] ?? 0) + ($pekerja['uangMakan'] ?? 0), 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan">
                @if ($isHelper)
                    <span>{{ $pekerja['hariKerjaAktif'] ?? 0 }} hari × Rp {{ number_format($pekerja['tarifMakanHarian'] ?? 0, 0, ',', '.') }}</span>
                @else
                    <span>Pokok Rp {{ number_format($pekerja['gajiPokok'] ?? 0, 0, ',', '.') }} + Makan</span>
                @endif
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 100%;"></div></div>
        </div>
    </div>

    {{-- KPI 4: Kasbon Belum Lunas --}}
    <div class="kartu-kpi kartu-kpi--abu">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-receipt-cutoff"></i></div>
            <span class="kartu-kpi__tag">Potongan Bon</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Kasbon Belum Lunas</div>
            <div class="kartu-kpi__nilai" style="font-size: 20px;">Rp {{ number_format($pekerja['kasbon'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan">
                <span>{{ ($pekerja['kasbon'] ?? 0) > 0 ? 'Potongan slip gaji' : 'Tidak ada pinjaman' }}</span>
                <i class="bi bi-dash-circle text-rose-500"></i>
            </div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: {{ ($pekerja['kasbon'] ?? 0) > 0 ? '60%' : '5%' }};"></div></div>
        </div>
    </div>

    {{-- KPI 5: Realisasi Hari Ini --}}
    <div class="kartu-kpi kartu-kpi--aksen">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-sun"></i></div>
            <span class="kartu-kpi__tag">Hari Ini</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Luas Terbang Hari Ini</div>
            <div class="kartu-kpi__nilai">{{ $format($ringkasan['luasHariIni']) }} <small>Ha</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan">
                <span>Realisasi harian tim</span>
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 50%;"></div></div>
        </div>
    </div>

    {{-- KPI 6: Realisasi Bulan Berjalan (MTD) --}}
    <div class="kartu-kpi kartu-kpi--hijau">
        <div class="kartu-kpi__atas">
            <div class="kartu-kpi__ikon-box"><i class="bi bi-check2-square"></i></div>
            <span class="kartu-kpi__tag">MTD</span>
        </div>
        <div>
            <div class="kartu-kpi__label">Total Luas Disetujui</div>
            <div class="kartu-kpi__nilai">{{ $format($pekerja['luasDisetujuiTim'] ?? 0) }} <small>Ha</small></div>
        </div>
        <div class="kartu-kpi__bawah">
            <div class="kartu-kpi__keterangan">
                <span>Akumulasi {{ $namaBulan($bulan) }}</span>
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="kartu-kpi__progres"><div class="kartu-kpi__progres-isi" style="width: 70%;"></div></div>
        </div>
    </div>
</div>

{{-- Widget Kesehatan & Pemeliharaan Armada Drone Tim --}}
@if ($droneInfo)
    @php
        $persenBatas = min(100, round($droneInfo['persen_batas_servis'], 1));
        $warnaBatas = $droneInfo['perlu_servis'] ? '#ef4444' : ($persenBatas >= 80 ? '#f59e0b' : '#10b981');
    @endphp
    <div class="kartu-modern mb-5" style="border-left: 4px solid {{ $warnaBatas }};">
        <div class="kartu-modern__kepala flex justify-between items-center">
            <h3 class="kartu-modern__judul flex items-center gap-2">
                <i class="bi bi-cpu text-emerald-600"></i>
                Status Armada Drone Tim: <strong>{{ $droneInfo['nomor_drone'] }} &middot; {{ $droneInfo['nama_drone'] }}</strong>
                <span class="text-xs text-slate-400 font-normal">({{ $droneInfo['tipe_drone'] ?? '-' }})</span>
            </h3>
            <span class="badge {{ $droneInfo['perlu_servis'] ? 'badge--merah' : ($droneInfo['status_operasional'] === 'aktif' ? 'badge--hijau' : 'badge--kuning') }}">
                <i class="bi {{ $droneInfo['perlu_servis'] ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
                {{ $droneInfo['perlu_servis'] ? 'Waktunya Servis Berkala' : ucfirst($droneInfo['status_operasional']) }}
            </span>
        </div>
        <div class="kartu-modern__badan" style="padding: 16px 20px;">
            <div class="kesehatan-armada-grid">
                {{-- Kolom 1: Jam Terbang / Luas Akumulasi --}}
                <div>
                    <div class="text-xs text-slate-500 font-semibold mb-1">Total Terbang Akumulatif</div>
                    <div class="text-xl font-extrabold text-slate-800">
                        {{ $format($droneInfo['total_luas_terbang_ha']) }} <small class="text-xs font-bold text-slate-500">Ha</small>
                    </div>
                    <div class="text-xs text-slate-400 mt-1">
                        Ambang batas servis: <strong>{{ $format($droneInfo['ambang_batas_servis_ha']) }} Ha</strong>
                    </div>
                </div>

                {{-- Kolom 2: Progress Bar Menuju Servis --}}
                <div>
                    <div class="flex justify-between items-center text-xs font-bold mb-1.5">
                        <span class="text-slate-600">Siklus Servis Berkala</span>
                        <span style="color: {{ $warnaBatas }};">{{ $persenBatas }}% Terpakai</span>
                    </div>
                    <div style="background: #eef3f0; height: 10px; border-radius: 999px; overflow: hidden; margin-bottom: 6px;">
                        <div style="width: {{ $persenBatas }}%; height: 100%; background: {{ $warnaBatas }}; border-radius: 999px; transition: width 0.6s ease;"></div>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        @if ($droneInfo['perlu_servis'])
                            <span class="text-rose-600 font-bold"><i class="bi bi-exclamation-circle-fill"></i> Batas servis tercapai! Segera laporkan ke teknisi.</span>
                        @else
                            <span>Sisa batas terbang aman: <strong>{{ $format($droneInfo['sisa_menuju_servis']) }} Ha</strong></span>
                        @endif
                        <span>{{ !empty($droneInfo['catatan_perawatan']) ? 'Catatan: '.$droneInfo['catatan_perawatan'] : 'Armada siap terbang' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- 2 Kolom Bawah: SPK Aktif Tim & Rincian Slip Gaji Estimasi (Disertai Grafik Komposisi) --}}
<div class="dasbor-split-bawah">
    {{-- SPK Aktif Tim --}}
    <div class="kartu-modern">
        <div class="kartu-modern__kepala">
            <h3 class="kartu-modern__judul">
                <i class="bi bi-file-earmark-play text-emerald-600"></i> SPK Aktif Tim Saya
            </h3>
            <a href="{{ route('spk.index') }}" class="text-xs font-bold text-emerald-700 hover:underline">Semua SPK →</a>
        </div>
        <div class="kartu-modern__badan" style="padding: 0;">
            @forelse ($pekerja['spkAktif'] ?? [] as $spk)
                @if ($loop->first)
                    <div class="tabel-bungkus">
                    <table class="tabel-mini-modern">
                        <thead>
                            <tr>
                                <th>No SPK</th>
                                <th>Distrik & Pekerjaan</th>
                                <th class="angka">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                @endif
                <tr>
                    <td>
                        <div class="font-bold text-slate-800">{{ $spk->nomor_spk }}</div>
                        <div class="text-xs text-slate-400">{{ $spk->tanggal_spk?->translatedFormat('d M Y') }}</div>
                    </td>
                    <td>
                        <div class="font-semibold">{{ $spk->distrik->nama_distrik ?? '-' }}</div>
                        <div class="text-xs text-slate-500">{{ $spk->jenisPekerjaan->nama_pekerjaan ?? '-' }}</div>
                    </td>
                    <td class="angka">
                        <a href="{{ route('realisasi.create', ['spk_id' => $spk->id]) }}" class="tombol tombol--utama tombol--kecil" style="padding: 5px 12px; font-size: 11.5px;">
                            <i class="bi bi-pencil-square"></i> Input
                        </a>
                    </td>
                </tr>
                @if ($loop->last)
                        </tbody>
                    </table>
                    </div>
                @endif
            @empty
                <x-kosong rapat ikon="bi-file-earmark-check" judul="Belum ada SPK aktif untuk tim Anda saat ini" />
            @endforelse
        </div>
    </div>

    {{-- Rincian Estimasi Slip Gaji + Grafik Komposisi Pendapatan --}}
    <div class="kartu-modern">
        <div class="kartu-modern__kepala flex-antara item-tengah">
            <h3 class="kartu-modern__judul">
                <i class="bi bi-calculator text-emerald-600"></i> Rincian Slip Penghasilan (Estimasi)
            </h3>
            <span class="lencana lencana--biru teks-kecil" style="font-size: 11px; white-space: nowrap;">
                {{ $labelPeran }} &middot; {{ $periode }}
            </span>
        </div>
        <div class="kartu-modern__badan">
            <div class="kartu-gaji">
                <div class="kartu-gaji__baris">
                    <span class="kartu-gaji__label">
                        <i class="bi bi-briefcase text-slate-500"></i>
                        <span class="kartu-gaji__label-teks">
                            <strong>Gaji Pokok</strong>
                        </span>
                    </span>
                    <span class="kartu-gaji__nilai">Rp {{ number_format($pekerja['gajiPokok'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="kartu-gaji__baris">
                    <span class="kartu-gaji__label">
                        <i class="bi bi-cup-hot text-amber-500"></i>
                        <span class="kartu-gaji__label-teks">
                            <strong>Uang Makan</strong>
                            @if ($isHelper)
                                <span class="kartu-gaji__subteks">({{ $pekerja['hariKerjaAktif'] ?? 0 }} hari kerja &times; Rp {{ number_format($pekerja['tarifMakanHarian'] ?? 0, 0, ',', '.') }})</span>
                            @endif
                        </span>
                    </span>
                    <span class="kartu-gaji__nilai">Rp {{ number_format($pekerja['uangMakan'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="kartu-gaji__baris">
                    <span class="kartu-gaji__label">
                        <i class="bi bi-airplane text-emerald-600"></i>
                        <span class="kartu-gaji__label-teks">
                            <strong>Premi Terbang Disetujui</strong>
                            <span class="kartu-gaji__subteks">{{ $format($pekerja['luasDisetujuiTim'] ?? 0) }} Ha &times; Rp {{ number_format($pekerja['premiPerHa'] ?? 0, 0, ',', '.') }}/Ha</span>
                        </span>
                    </span>
                    <span class="kartu-gaji__nilai" style="color: #059669;">+Rp {{ number_format($pekerja['totalPremi'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="kartu-gaji__baris">
                    <span class="kartu-gaji__label">
                        <i class="bi bi-receipt text-rose-500"></i>
                        <span class="kartu-gaji__label-teks">
                            <strong>Potongan Kasbon Kru</strong>
                        </span>
                    </span>
                    <span class="kartu-gaji__nilai text-rose-600">-Rp {{ number_format($pekerja['kasbon'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="kartu-gaji__baris kartu-gaji__baris--total">
                    <span class="text-emerald-900 font-bold" style="font-size: 13.5px;">Estimasi Bersih Diterima:</span>
                    <span class="text-emerald-700 font-extrabold" style="font-size: 16px;">Rp {{ number_format($pekerja['estimasiBersih'] ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Grafik Komposisi Pendapatan Kotor --}}
            <div class="chart-komposisi-wadah">
                <div class="flex-antara item-tengah">
                    <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                        <i class="bi bi-pie-chart-fill text-emerald-600"></i> Visual Komposisi Kotor
                    </span>
                    <span class="text-xs text-emerald-700 font-extrabold">Rp {{ number_format($pekerja['estimasiKotor'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="chart-komposisi-bar" title="Gaji Pokok: {{ $persenPokok }}%, Uang Makan: {{ $persenMakan }}%, Premi: {{ $persenPremi }}%">
                    <div class="chart-komposisi-segmen chart-komposisi-segmen--pokok" style="width: {{ $persenPokok }}%;" title="Gaji Pokok: {{ $persenPokok }}%"></div>
                    <div class="chart-komposisi-segmen chart-komposisi-segmen--makan" style="width: {{ $persenMakan }}%;" title="Uang Makan: {{ $persenMakan }}%"></div>
                    <div class="chart-komposisi-segmen chart-komposisi-segmen--premi" style="width: {{ $persenPremi }}%;" title="Premi Terbang: {{ $persenPremi }}%"></div>
                </div>
                <div class="chart-komposisi-kartu-grid">
                    <div class="chart-komposisi-mini-kartu">
                        <span class="chart-komposisi-mini-kartu__label"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block flex-shrink-0"></span> Gaji Pokok</span>
                        <span class="chart-komposisi-mini-kartu__nilai">{{ $persenPokok }}%</span>
                    </div>
                    <div class="chart-komposisi-mini-kartu">
                        <span class="chart-komposisi-mini-kartu__label"><span class="w-2 h-2 rounded-full bg-cyan-500 inline-block flex-shrink-0"></span> Uang Makan</span>
                        <span class="chart-komposisi-mini-kartu__nilai">{{ $persenMakan }}%</span>
                    </div>
                    <div class="chart-komposisi-mini-kartu">
                        <span class="chart-komposisi-mini-kartu__label"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block flex-shrink-0"></span> Premi Terbang</span>
                        <span class="chart-komposisi-mini-kartu__nilai">{{ $persenPremi }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Status Laporan Realisasi Donut Multi-Segment --}}
<div class="chart-kartu">
    <div class="chart-kartu__kepala">
        <h3 class="chart-kartu__judul">
            <i class="bi bi-pie-chart text-emerald-600"></i> Status Laporan Realisasi Tim Saya — {{ $periode }}
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
