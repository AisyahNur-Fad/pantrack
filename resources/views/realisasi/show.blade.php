@extends('layouts.app')

@section('judul', 'Detail Realisasi SPK: ' . ($realisasi->spk?->nomor_spk ?? $realisasi->nomor_laporan))

@section('konten')
    <x-halaman-kepala
        :judul="'Realisasi SPK: ' . ($realisasi->spk?->nomor_spk ?? $realisasi->nomor_laporan)"
        deskripsi="Detail realisasi pekerjaan petak drone, lampiran dokumentasi, dan status verifikasi."
        ikon="bi-clipboard2-check">
        <div class="flex-antara gap-2">
            <a href="{{ route('realisasi.index') }}" class="tombol tombol--garis">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>

            @php
                $bisaEditLaporan = auth()->user()?->adalahPekerjaLapangan()
                    && $realisasi->status_laporan === \App\Models\LaporanRealisasi::PERLU_PERBAIKAN;
            @endphp
            @if ($bisaEditLaporan)
                <a href="{{ route('realisasi.edit', $realisasi) }}" class="tombol tombol--garis">
                    <i class="bi bi-pencil"></i> Ubah Laporan
                </a>
            @endif

            @if (auth()->user()?->adalahPekerjaLapangan() && $realisasi->status_laporan === 'draf')
                <form method="POST" action="{{ route('realisasi.ajukan', $realisasi) }}">
                    @csrf
                    <button type="submit" class="tombol tombol--utama">
                        <i class="bi bi-send-check"></i> Ajukan ke Kepala Lapangan
                    </button>
                </form>
            @endif
        </div>
    </x-halaman-kepala>

    @if ($realisasi->status_laporan === 'perlu_perbaikan')
        <div class="alert alert--bahaya mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Catatan Perbaikan dari Kepala Lapangan:</strong>
                <p class="mb-0">{{ $realisasi->catatan_pemeriksaan ?? 'Silakan lakukan revisi data sesuai arahan.' }}</p>
            </div>
        </div>
    @endif

    <div class="grid-form mb-4">
        <div class="kartu">
            <h3 class="judul-sub mb-3">Informasi Laporan & Tim Lapangan</h3>
            <table class="tabel-info">
                <tr>
                    <td class="teks-pudar" style="width: 35%">Surat Perintah Kerja (SPK)</td>
                    <td>
                        @if ($realisasi->spk)
                            <a href="{{ route('spk.show', $realisasi->spk) }}" class="teks-tebal tautan-tabel" style="font-size: 1.05rem;">
                                {{ $realisasi->spk->nomor_spk }}
                            </a>
                            @if ($realisasi->spk->jenisPekerjaan)
                                <div class="teks-kecil teks-pudar mt-0.5"><i class="bi bi-tag"></i> {{ $realisasi->spk->jenisPekerjaan->nama_pekerjaan }}</div>
                            @endif
                        @else
                            <span class="teks-pudar">— Tanpa SPK —</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Tanggal Pelaksanaan</td>
                    <td class="teks-tebal">
                        {{ $realisasi->detail->first()?->tanggal_pekerjaan?->translatedFormat('l, d F Y') ?? $realisasi->tanggal_input?->translatedFormat('l, d F Y') ?? $realisasi->created_at->translatedFormat('l, d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Tim Drone</td>
                    <td>
                        {{ $realisasi->tim?->nama_tim ?? '-' }}
                        @if ($realisasi->nama_tim_manual)
                            <span class="teks-pudar teks-kecil d-block">Alias: {{ $realisasi->nama_tim_manual }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Personil Tim</td>
                    <td>
                        <div><strong>Pilot:</strong> {{ $realisasi->nama_pilot ?? '-' }}</div>
                        @if ($realisasi->nama_copilot)
                            <div><strong>Co-Pilot:</strong> {{ $realisasi->nama_copilot }}</div>
                        @endif
                        @if ($realisasi->nama_helper)
                            @php
                                $daftarHelper = $realisasi->daftar_helper;
                            @endphp
                            <div>
                                <strong>Helper{{ count($daftarHelper) > 1 ? ' (' . count($daftarHelper) . ' Orang)' : '' }}:</strong>
                                @if (count($daftarHelper) > 1)
                                    <div class="mt-1 flex gap-1 flex-wrap">
                                        @foreach ($daftarHelper as $h)
                                            <span class="lencana lencana--abu teks-kecil"><i class="bi bi-person"></i> {{ $h }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    {{ $realisasi->nama_helper }}
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Distrik</td>
                    <td>{{ $realisasi->distrik?->nama_distrik ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Periode</td>
                    <td>{{ Carbon\Carbon::create($realisasi->periode_tahun, $realisasi->periode_bulan, 1)->translatedFormat('F Y') }}</td>
                </tr>
                @if ($realisasi->getRawOriginal('nomor_laporan'))
                <tr>
                    <td class="teks-pudar">ID Dokumen Internal</td>
                    <td class="teks-kecil teks-pudar font-mono">{{ $realisasi->getRawOriginal('nomor_laporan') }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="kartu">
            <h3 class="judul-sub mb-3">Status & Pemeriksaan</h3>
            <table class="tabel-info">
                <tr>
                    <td class="teks-pudar" style="width: 35%">Status Laporan</td>
                    <td>
                        @php
                            $warna = match ($realisasi->status_laporan) {
                                'disetujui' => 'hijau',
                                'menunggu_pemeriksaan' => 'emas',
                                'perlu_perbaikan' => 'merah',
                                'draf' => 'abu',
                                'ditolak' => 'abu',
                                default => 'abu',
                            };
                            $label = \App\Models\LaporanRealisasi::statusLaporan()[$realisasi->status_laporan] ?? $realisasi->status_laporan;
                        @endphp
                        <x-lencana :warna="$warna" :teks="$label" />
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Diinput Oleh</td>
                    <td>{{ $realisasi->penginput?->nama_lengkap ?? '—' }} ({{ $realisasi->created_at->translatedFormat('d M Y, H:i') }})</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Pemeriksa</td>
                    <td>{{ $realisasi->pemeriksa?->nama_lengkap ?? 'Belum diperiksa' }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Waktu Pemeriksaan</td>
                    <td>{{ $realisasi->tanggal_pemeriksaan ? $realisasi->tanggal_pemeriksaan->translatedFormat('d M Y, H:i') : '—' }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Total Realisasi</td>
                    <td class="teks-tebal teks-utama">{{ number_format($totalLuas, 2, ',', '.') }} Ha</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Total Nilai Pekerjaan</td>
                    <td class="teks-tebal" style="color: var(--warna-aksen, #0d6efd);">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Kondisi Operasional</td>
                    <td>
                        @if ($realisasi->ada_kendala)
                            <span class="lencana lencana--merah"><i class="bi bi-exclamation-triangle-fill"></i> Terdapat Kendala</span>
                            @if ($realisasi->catatan_kendala)
                                <div class="teks-kecil mt-1" style="color: #b91c1c;"><strong>Detail:</strong> {{ $realisasi->catatan_kendala }}</div>
                            @endif
                        @else
                            <span class="lencana lencana--hijau"><i class="bi bi-check-circle-fill"></i> Lancar (Tanpa Kendala)</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Konsumsi BBM</td>
                    <td>
                        @if ($realisasi->ada_bbm)
                            <span class="lencana lencana--biru"><i class="bi bi-fuel-pump-fill"></i> Ada Penggunaan BBM</span>
                        @else
                            <span class="lencana lencana--abu"><i class="bi bi-slash-circle"></i> Tidak Ada Penggunaan BBM</span>
                            @if ($realisasi->keterangan_tanpa_bbm)
                                <div class="teks-kecil teks-pudar mt-1"><strong>Alasan:</strong> {{ $realisasi->keterangan_tanpa_bbm }}</div>
                            @endif
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Tabel Detail Realisasi Petak --}}
    <div class="kartu mb-4">
        <div class="flex-antara item-tengah mb-3">
            <h3 class="judul-sub">Daftar Pengerjaan Petak ({{ $realisasi->detail->count() }} Baris)</h3>
            <div class="flex gap-2">
                <span class="lencana lencana--hijau">Total Realisasi: {{ number_format($totalLuas, 2, ',', '.') }} Ha</span>
                @if (($totalLuasMapping ?? 0) > 0)
                    <span class="lencana lencana--biru">🗺️ Mapping: {{ number_format($totalLuasMapping, 2, ',', '.') }} Ha (+Rp {{ number_format($totalNilaiMapping ?? 0, 0, ',', '.') }})</span>
                @endif
                <span class="lencana lencana--emas">Total Nilai: Rp {{ number_format($totalNilai, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Petak</th>
                        <th>Jenis Pekerjaan</th>
                        <th class="angka">Luas (Ha)</th>
                        <th class="angka">Tarif / Ha</th>
                        <th class="angka">Subtotal Nilai</th>
                        <th>Status</th>
                        <th>Keterangan / Kendala</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($realisasi->detail as $item)
                        @php
                            $tarif = (float) ($item->tarif_per_ha ?? 0);
                            if ($tarif <= 0 && $item->jenis_pekerjaan_id) {
                                $tarif = (float) (\App\Models\TarifPekerjaan::where('jenis_pekerjaan_id', $item->jenis_pekerjaan_id)
                                    ->where('berlaku_mulai', '<=', $item->tanggal_pekerjaan)
                                    ->where(fn ($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', $item->tanggal_pekerjaan))
                                    ->latest('berlaku_mulai')
                                    ->value('nilai_tarif') ?? 0);
                            }
                            $subtotalPokok = (float) $item->luas_realisasi_ha * $tarif;
                            $tarifMap = (float) ($item->tarif_mapping_per_ha ?? 30000);
                            $subtotalMapping = $item->perlu_mapping ? ((float) $item->luas_realisasi_ha * $tarifMap) : 0.0;
                            $subtotal = $subtotalPokok + $subtotalMapping;
                        @endphp
                        <tr>
                            <td class="teks-pudar">{{ $loop->iteration }}</td>
                            <td>{{ $item->tanggal_pekerjaan ? $item->tanggal_pekerjaan->translatedFormat('d M Y') : '-' }}</td>
                            <td class="teks-tebal">
                                {{ $item->petak?->kode_petak ?? ($item->kode_petak_manual ?: '-') }}
                                @if ($item->petak?->nama_petak)
                                    <span class="teks-pudar teks-kecil d-block">{{ $item->petak->nama_petak }}</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $item->jenisPekerjaan?->nama_pekerjaan ?? '-' }}</div>
                                @if ($item->perlu_mapping)
                                    <span class="lencana lencana--biru teks-kecil mt-1 d-inline-flex item-tengah gap-1" title="Layanan Tambahan Mapping Aktif">
                                        <i class="bi bi-geo-alt"></i> +Mapping (+Rp {{ number_format($tarifMap, 0, ',', '.') }}/Ha)
                                    </span>
                                @endif
                            </td>
                            <td class="angka teks-tebal">{{ number_format($item->luas_realisasi_ha, 2, ',', '.') }} Ha</td>
                            <td class="angka">
                                <div>Rp {{ number_format($tarif, 0, ',', '.') }}</div>
                                @if ($item->perlu_mapping)
                                    <span class="teks-pudar teks-kecil d-block">+Rp {{ number_format($tarifMap, 0, ',', '.') }} (Map)</span>
                                @endif
                            </td>
                            <td class="angka teks-tebal">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $st = $item->status_pekerjaan;
                                    $warnaSt = match ($st) {
                                        'selesai' => 'hijau',
                                        'dalam_proses' => 'biru',
                                        'terkendala' => 'merah',
                                        default => 'emas',
                                    };
                                    $labelSt = \App\Models\DetailRealisasi::statusPekerjaan()[$st] ?? $st;
                                @endphp
                                <x-lencana :warna="$warnaSt" :teks="$labelSt" />
                            </td>
                            <td>{{ $item->keterangan_kendala ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="baris-total">
                        <td colspan="4" class="teks-kanan teks-tebal">Total Akumulasi Realisasi:</td>
                        <td class="angka teks-tebal teks-utama">{{ number_format($totalLuas, 2, ',', '.') }} Ha</td>
                        <td></td>
                        <td class="angka teks-tebal teks-utama">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Rincian Alokasi Pendapatan Operasional (Kalkulasi Pendapatan, ADM 5%, Investor 15%, dan Helper) --}}
    @if (auth()->user()?->adalahAdmin())
        <div class="kartu mb-4" style="border-left: 4px solid var(--warna-aksen, #0284c7);">
            <div class="flex-antara item-tengah mb-3">
                <div>
                    <h3 class="judul-sub"><i class="bi bi-calculator teks-utama"></i> Rincian Alokasi Pendapatan Pekerjaan</h3>
                    <p class="teks-pudar teks-kecil">Perhitungan pembagian pendapatan operasional: Potongan ADM (5%), Investor (15%), dan Hak Lapangan (80%).</p>
                </div>
                <span class="lencana lencana--hijau">Total Bruto: Rp {{ number_format($totalNilai, 0, ',', '.') }}</span>
            </div>

            <div class="grid-form">
                <div class="kartu kartu--pudar p-3">
                    <span class="teks-pudar teks-kecil d-block">Potongan Administrasi (ADM 5%)</span>
                    <strong class="teks-besar" style="color: var(--warna-merah, #ef4444);">- Rp {{ number_format($potonganAdm, 0, ',', '.') }}</strong>
                    <small class="teks-pudar d-block mt-1">Alokasi manajemen & administrasi kantor</small>
                </div>

                <div class="kartu kartu--pudar p-3">
                    <span class="teks-pudar teks-kecil d-block">Bagi Hasil Investor (15%)</span>
                    <strong class="teks-besar" style="color: var(--warna-emas, #f59e0b);">- Rp {{ number_format($bagianInvestor, 0, ',', '.') }}</strong>
                    <small class="teks-pudar d-block mt-1">Alokasi bagi hasil kepemilikan modal/unit</small>
                </div>

                <div class="kartu kartu--pudar p-3">
                    <span class="teks-pudar teks-kecil d-block">Sisa Bersih Operasional (80%)</span>
                    <strong class="teks-besar teks-utama">Rp {{ number_format($bersihOperasional, 0, ',', '.') }}</strong>
                    <small class="teks-pudar d-block mt-1">Alokasi tim lapangan & kru penerbangan</small>
                </div>
            </div>

            <div class="p-3 mt-3" style="background: var(--warna-latar-kartu, #f8f9fa); border-radius: 8px; border: 1px dashed var(--warna-garis, #dee2e6);">
                <div class="flex-antara item-tengah">
                    <div>
                        <strong><i class="bi bi-cash-stack teks-utama"></i> Estimasi Jatah Tunai Helper Lapangan:</strong>
                        <span class="teks-pudar ms-2">{{ $realisasi->nama_helper ? 'Helper: ' . $realisasi->nama_helper : 'Tidak ada helper terdaftar' }}</span>
                        @if ($adaHelper)
                            <span class="lencana {{ $skemaHelper === 'harian' ? 'lencana--biru' : 'lencana--emas' }} ms-1">
                                {{ $skemaHelper === 'harian' ? 'Skema Harian' : 'Skema Per Hektar' }}
                            </span>
                        @endif
                    </div>
                    <strong class="teks-tebal" style="color: #059669; font-size: 1.1rem;">
                        Rp {{ number_format($estimasiHelper, 0, ',', '.') }}
                    </strong>
                </div>
                @if ($adaHelper)
                    <div class="teks-kecil teks-pudar mt-1">
                        @if ($skemaHelper === 'harian')
                            Perhitungan: {{ $hariKerjaHelper }} Hari Kerja &times; Rp {{ number_format($tarifHelperPerHari, 0, ',', '.') }}/Hari
                        @else
                            Perhitungan: {{ number_format($totalLuas, 2, ',', '.') }} Ha &times; Rp {{ number_format($tarifHelperPerHa, 0, ',', '.') }}/Ha
                        @endif
                    </div>
                @endif
                <div class="flex-antara item-tengah mt-2 pt-2" style="border-top: 1px solid var(--warna-garis, #e2e8f0);">
                    <span class="teks-pudar teks-kecil">Estimasi Sisa Alokasi Pilot & Kru Drone:</span>
                    <strong class="teks-kecil teks-utama">Rp {{ number_format($estimasiPilotKru, 0, ',', '.') }}</strong>
                </div>
                <small class="teks-pudar d-block mt-2" style="font-size: 0.78rem;">
                    * Catatan: Sistem PANTRACK hanya menghitung totalan dan rincian alokasi ini sebagai referensi kalkulasi operasional. Penyaluran upah tunai helper dilakukan langsung oleh Pilot di lapangan.
                </small>
            </div>
        </div>
    @elseif (auth()->user()?->adalahPekerjaLapangan())
        <div class="kartu mb-4" style="border-left: 4px solid var(--warna-hijau, #10b981);">
            <div class="flex-antara item-tengah mb-3">
                <div>
                    <h3 class="judul-sub"><i class="bi bi-cash-coin teks-utama"></i> Hak dan Estimasi Pendapatan Kru Lapangan</h3>
                    <p class="teks-pudar teks-kecil">Transparansi pendapatan dan jatah tunai helper berdasarkan luasan realisasi pekerjaan penerbangan petak.</p>
                </div>
                <div class="flex gap-2">
                    <span class="lencana lencana--hijau">Luas Dikerjakan: {{ number_format($totalLuas, 2, ',', '.') }} Ha</span>
                </div>
            </div>

            <div class="grid-form">
                {{-- Bagian Pilot & Copilot: Gaji Pokok + Tambahan Premi Terbang --}}
                <div class="kartu kartu--pudar p-3">
                    <div class="flex-antara item-tengah mb-2">
                        <span class="teks-tebal"><i class="bi bi-person-badge teks-utama"></i> Pilot: {{ $realisasi->nama_pilot ?: '-' }}</span>
                        <span class="lencana lencana--biru teks-kecil">Gaji Tetap + Premi</span>
                    </div>

                    <div class="teks-kecil mb-1">
                        <span class="teks-pudar">Gaji Pokok (Tetap):</span>
                        <strong class="float-end">Rp {{ number_format($gajiPokokPilot, 0, ',', '.') }}</strong>
                    </div>

                    <div class="teks-kecil mb-2">
                        <span class="teks-pudar">Premi Terbang ({{ number_format($totalLuas, 2, ',', '.') }} Ha &times; Rp {{ number_format($premiPilotPerHa, 0, ',', '.') }}/Ha):</span>
                        <strong class="float-end" style="color: #0d6efd;">+ Rp {{ number_format($premiPilotHariIni, 0, ',', '.') }}</strong>
                    </div>

                    <div class="pt-2 mt-2 flex-antara item-tengah" style="border-top: 1px solid var(--warna-garis, #e2e8f0);">
                        <span class="teks-tebal teks-kecil">Estimasi Subtotal Hak Pilot:</span>
                        <strong class="teks-besar teks-utama">Rp {{ number_format($gajiPokokPilot + $premiPilotHariIni, 0, ',', '.') }}</strong>
                    </div>
                    <small class="teks-pudar d-block mt-1" style="font-size: 0.75rem;">* Estimasi alokasi operasional dihitung sebelum potongan kasbon dan ransum resmi di slip gaji.</small>
                </div>

                {{-- Bagian Helper: Harian atau Per Hektar Sesuai Skema Pilihan Admin --}}
                <div class="kartu kartu--pudar p-3">
                    <div class="flex-antara item-tengah mb-2">
                        <span class="teks-tebal"><i class="bi bi-person-gear" style="color: #059669;"></i> Helper: {{ $realisasi->nama_helper ?: 'Tidak Ada Helper' }}</span>
                        @if ($adaHelper)
                            <span class="lencana {{ $skemaHelper === 'harian' ? 'lencana--biru' : 'lencana--emas' }} teks-kecil">
                                {{ $skemaHelper === 'harian' ? 'Skema Harian' : 'Skema Per Hektar' }}
                            </span>
                        @else
                            <span class="lencana lencana--abu teks-kecil">Tanpa Helper</span>
                        @endif
                    </div>

                    @if ($adaHelper)
                        <div class="teks-kecil mb-1">
                            <span class="teks-pudar">Metode Perhitungan:</span>
                            <span class="float-end teks-tebal">
                                @if ($skemaHelper === 'harian')
                                    {{ $hariKerjaHelper }} Hari Kerja &times; Rp {{ number_format($tarifHelperPerHari, 0, ',', '.') }}/Hari
                                @else
                                    {{ number_format($totalLuas, 2, ',', '.') }} Ha &times; Rp {{ number_format($tarifHelperPerHa, 0, ',', '.') }}/Ha
                                @endif
                            </span>
                        </div>

                        <div class="pt-2 mt-2 flex-antara item-tengah" style="border-top: 1px solid var(--warna-garis, #e2e8f0);">
                            <span class="teks-tebal teks-kecil">Estimasi Jatah Tunai Helper:</span>
                            <strong class="teks-besar" style="color: #059669;">Rp {{ number_format($estimasiHelper, 0, ',', '.') }}</strong>
                        </div>
                        <small class="teks-pudar d-block mt-1" style="font-size: 0.75rem;">* Jatah upah tunai helper wajib diserahkan langsung oleh Pilot kepada Helper di posko/lapangan.</small>
                    @else
                        <p class="teks-pudar teks-kecil mt-2 mb-0">Tidak ada helper lapangan yang didaftarkan pada laporan realisasi ini.</p>
                    @endif
                </div>
            </div>

            <small class="teks-pudar d-block mt-3" style="font-size: 0.78rem;">
                * Catatan: Sistem PANTRACK menampilkan transparansi luasan pengerjaan ({{ number_format($totalLuas, 2, ',', '.') }} Ha) dan pembagian kompensasi kru secara riil. Rincian penerimaan bulanan resmi (gaji pokok, uang makan, dan premi) dapat dilihat pada Slip Gaji.
            </small>
        </div>
    @endif

    {{-- Konsumsi BBM Terkait --}}
    @if ($realisasi->transaksiBbm->isNotEmpty())
        <div class="kartu mb-4">
            <h3 class="judul-sub mb-3"><i class="bi bi-fuel-pump teks-utama"></i> Konsumsi BBM Terhubung (Operasional Penerbangan)</h3>
            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Jenis BBM</th>
                            <th>Keperluan</th>
                            <th class="angka">Jumlah Liter</th>
                            <th class="angka">Total Biaya</th>
                            <th>Status Verifikasi</th>
                            <th>Keterangan</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($realisasi->transaksiBbm as $bbm)
                            <tr>
                                <td class="teks-tebal">{{ $bbm->nomor_transaksi }}</td>
                                <td>{{ $bbm->tanggal_transaksi ? $bbm->tanggal_transaksi->translatedFormat('d M Y') : '-' }}</td>
                                <td>{{ $bbm->jenisBbm?->nama_bbm ?? '-' }}</td>
                                <td>{{ $bbm->labelKeperluan() }}</td>
                                <td class="angka teks-tebal">{{ number_format($bbm->jumlah_liter, 2, ',', '.') }} L</td>
                                <td class="angka">{{ $bbm->total_biaya ? 'Rp ' . number_format($bbm->total_biaya, 0, ',', '.') : '—' }}</td>
                                <td>
                                    <x-lencana :warna="$bbm->status_verifikasi === 'terverifikasi' ? 'hijau' : ($bbm->status_verifikasi === 'perlu_perbaikan' ? 'merah' : 'emas')" :teks="\App\Models\TransaksiBbm::statusVerifikasi()[$bbm->status_verifikasi] ?? $bbm->status_verifikasi" />
                                </td>
                                <td>{{ $bbm->keterangan ?: '—' }}</td>
                                <td class="aksi">
                                    <a href="{{ route('bbm.show', $bbm) }}" class="tombol tombol--garis tombol--kecil">
                                        <i class="bi bi-eye"></i> Detail BBM
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif (!$realisasi->ada_bbm && $realisasi->keterangan_tanpa_bbm)
        <div class="kartu mb-4" style="background: var(--warna-latar-kartu, #f8fafc); border: 1px dashed var(--warna-garis, #cbd5e1);">
            <div class="flex item-tengah gap-3 p-1">
                <i class="bi bi-slash-circle teks-pudar" style="font-size: 1.5rem;"></i>
                <div>
                    <strong style="color: #475569;">Laporan ini Tidak Menggunakan Konsumsi BBM Operasional</strong>
                    <p class="teks-pudar teks-kecil mb-0">Alasan: {{ $realisasi->keterangan_tanpa_bbm }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Lampiran Dokumentasi --}}
    @if ($realisasi->lampiran->isNotEmpty())
        <div class="kartu mb-4">
            <h3 class="judul-sub mb-3"><i class="bi bi-images teks-utama"></i> Lampiran & Bukti Foto Lapangan</h3>
            <div class="grid-form">
                @foreach ($realisasi->lampiran as $lamp)
                    <div class="kartu kartu--pudar p-3">
                        <div class="flex-antara item-tengah mb-2">
                            <span class="teks-tebal teks-kecil">{{ $lamp->nama_berkas_asli }}</span>
                            <a href="{{ route('realisasi.lampiran', $lamp) }}" target="_blank" class="tombol tombol--garis tombol--kecil">
                                <i class="bi bi-box-arrow-up-right"></i> Buka
                            </a>
                        </div>
                        @if (preg_match('/\.(jpg|jpeg|png)$/i', $lamp->lokasi_berkas))
                            <a href="{{ route('realisasi.lampiran', $lamp) }}" target="_blank">
                                <img src="{{ route('realisasi.lampiran', $lamp) }}" alt="Bukti Lapangan" class="gambar-bukti">
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Formulir Pemeriksaan khusus Kepala Lapangan (FR-11) --}}
    @if (auth()->user()->adalahKepalaLapangan() && $realisasi->status_laporan === 'menunggu_pemeriksaan')
        <div class="kartu">
            <h3 class="judul-sub mb-2"><i class="bi bi-check2-circle teks-utama"></i> Pemeriksaan Laporan Realisasi (Kepala Lapangan)</h3>
            <p class="teks-pudar teks-kecil mb-3">Periksa kecocokan petak, tanggal pengerjaan, luasan hektar aktual, dan status kendala. Laporan yang disetujui akan masuk ke perhitungan rekapitulasi dan ekspor resmi (BR-02).</p>

            <form method="POST" action="{{ route('realisasi.periksa', $realisasi) }}" class="alur-vertikal">
                @csrf

                <x-field label="Catatan Pemeriksaan / Arahan Perbaikan" for="catatan" bantuan="Wajib diisi bila meminta perbaikan atau menolak laporan.">
                    <textarea id="catatan" name="catatan" rows="3" class="masukan" placeholder="Tuliskan catatan evaluasi atau perbaikan jika ada ketidaksesuaian data…">{{ old('catatan') }}</textarea>
                </x-field>

                <div class="bilah-aksi">
                    <button type="submit" name="tindakan" value="setujui" class="tombol tombol--utama">
                        <i class="bi bi-check-lg"></i> Setujui Laporan Realisasi
                    </button>
                    <button type="submit" name="tindakan" value="perbaikan" class="tombol tombol--garis">
                        <i class="bi bi-arrow-counterclockwise"></i> Kembalikan untuk Perbaikan
                    </button>
                    <button type="submit" name="tindakan" value="tolak" class="tombol tombol--garis teks-bahaya">
                        <i class="bi bi-x-circle"></i> Tolak Laporan
                    </button>
                </div>
            </form>
        </div>
    @endif
@endsection
