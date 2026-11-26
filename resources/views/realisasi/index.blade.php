@extends('layouts.app')

@section('judul', 'Laporan Realisasi Pekerjaan')

@section('konten')
    <x-halaman-kepala
        judul="Laporan Realisasi Pekerjaan"
        deskripsi="Pencatatan hasil kerja drone agrikultur per petak, tanggal, dan tim operasional (FR-08 s/d FR-12)."
        ikon="bi-clipboard2-data">
        @if (auth()->user()->adalahPekerjaLapangan())
            <a href="{{ route('realisasi.create') }}" class="tombol tombol--utama">
                <i class="bi bi-plus-lg"></i> Input Laporan Realisasi
            </a>
        @elseif (auth()->user()->adalahKepalaLapangan())
            <a href="{{ route('realisasi.index', ['status' => 'menunggu_pemeriksaan']) }}" class="tombol tombol--garis">
                <i class="bi bi-check2-circle"></i> Periksa Antrean Laporan
            </a>
        @else
            <span class="lencana lencana--abu" style="padding: 8px 14px;">
                <i class="bi bi-eye"></i> Mode Pantau
            </span>
        @endif
    </x-halaman-kepala>

    <div class="kartu">
        <form method="GET" class="bilah-alat">
            <div class="cari-bungkus">
                <i class="bi bi-search"></i>
                <input type="search" name="cari" value="{{ $cari }}" class="masukan" placeholder="Cari nomor SPK, pilot, atau distrik…">
            </div>

            <select name="tim_id" class="masukan" data-kirim-otomatis aria-label="Tim Drone">
                <option value="">Semua Tim</option>
                @foreach ($daftarTim as $t)
                    <option value="{{ $t->id }}" @selected((string) $timId === (string) $t->id)>{{ $t->nama_tim }}</option>
                @endforeach
            </select>

            <select name="distrik_id" class="masukan" data-kirim-otomatis aria-label="Distrik">
                <option value="">Semua Distrik</option>
                @foreach ($daftarDistrik as $d)
                    <option value="{{ $d->id }}" @selected((string) $distrikId === (string) $d->id)>{{ $d->nama_distrik }}</option>
                @endforeach
            </select>

            <select name="status" class="masukan" data-kirim-otomatis aria-label="Status Laporan">
                <option value="">Semua Status</option>
                <option value="draf" @selected($status === 'draf')>Draf</option>
                <option value="menunggu_pemeriksaan" @selected($status === 'menunggu_pemeriksaan')>Menunggu Pemeriksaan</option>
                <option value="perlu_perbaikan" @selected($status === 'perlu_perbaikan')>Perlu Perbaikan</option>
                <option value="disetujui" @selected($status === 'disetujui')>Disetujui</option>
                <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
            </select>

            <select name="bulan" class="masukan" data-kirim-otomatis aria-label="Bulan">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($bulan === $m)>Bulan {{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                @endfor
            </select>

            <select name="tahun" class="masukan" data-kirim-otomatis aria-label="Tahun">
                @for ($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                @endfor
            </select>

            <button type="submit" class="tombol tombol--garis"><i class="bi bi-funnel"></i> Terapkan</button>
            @if ($cari !== '' || $timId || $distrikId || $status)
                <a href="{{ route('realisasi.index') }}" class="tombol tombol--garis"><i class="bi bi-x-lg"></i> Reset</a>
            @endif
        </form>

        @if ($status)
            @php
                $statusList = \App\Models\LaporanRealisasi::statusLaporan();
                $labelStatusTerpilih = $statusList[$status] ?? ucfirst($status);
                $warnaBadge = match ($status) {
                    'disetujui' => 'hijau',
                    'menunggu_pemeriksaan' => 'emas',
                    'perlu_perbaikan' => 'biru',
                    'draf' => 'abu',
                    'ditolak' => 'merah',
                    default => 'abu',
                };
            @endphp
            <div class="mb-3 p-3 flex-antara item-tengah" style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid var(--warna-aksen, #10b981); border-radius: 8px;">
                <div class="flex item-tengah gap-2">
                    <i class="bi bi-funnel-fill teks-utama"></i>
                    <span>Menampilkan filter status: <x-lencana :warna="$warnaBadge" :teks="$labelStatusTerpilih" /> (Periode {{ Carbon\Carbon::create(2026, $bulan, 1)->translatedFormat('F') }} {{ $tahun }})</span>
                </div>
                <a href="{{ route('realisasi.index', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--garis tombol--kecil" title="Hapus filter status dan tampilkan semua status">
                    <i class="bi bi-x-circle"></i> Tampilkan Semua Status
                </a>
            </div>
        @endif

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th style="width: 48px;">No</th>
                        <th>Surat Perintah Kerja (SPK)</th>
                        <th>Tanggal Pelaksanaan</th>
                        <th>Tim Drone & Pilot</th>
                        <th>Distrik</th>
                        <th class="angka">Petak & Luas Realisasi</th>
                        <th>Status</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarLaporan as $lap)
                        @php
                            $spkNomor = $lap->spk?->nomor_spk;
                            $tglTampil = $lap->detail->first()?->tanggal_pekerjaan?->translatedFormat('d M Y') 
                                ?? $lap->tanggal_input?->translatedFormat('d M Y') 
                                ?? $lap->created_at?->translatedFormat('d M Y');
                            $firstPetak = $lap->detail->first();
                            $petakNama = $firstPetak?->petak?->kode_petak ?? $firstPetak?->kode_petak_manual ?? ($lap->detail_count > 0 ? $lap->detail_count . ' Petak' : '-');
                            $totalHa = (float) $lap->detail->sum('luas_realisasi_ha');
                        @endphp
                        <tr>
                            <td class="teks-pudar">{{ $daftarLaporan->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('realisasi.show', $lap) }}" class="teks-tebal tautan-tabel" style="font-size: 14px;">
                                    {{ $spkNomor ?? ($lap->spk_id ? ('SPK #' . $lap->spk_id) : ($lap->getRawOriginal('nomor_laporan') ?: 'Laporan #' . $lap->id)) }}
                                </a>
                                @if ($lap->spk?->jenisPekerjaan)
                                    <div class="teks-kecil teks-pudar mt-0.5">
                                        <i class="bi bi-tag"></i> {{ $lap->spk->jenisPekerjaan->nama_pekerjaan }}
                                    </div>
                                @elseif (!$spkNomor)
                                    <div class="teks-kecil teks-bahaya mt-0.5">
                                        <i class="bi bi-exclamation-triangle"></i> Data Lama (Tanpa SPK)
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="teks-tebal" style="font-size: 13.5px;">{{ $tglTampil }}</div>
                                <div class="teks-kecil teks-pudar">{{ Carbon\Carbon::create($lap->periode_tahun, $lap->periode_bulan, 1)->translatedFormat('M Y') }}</div>
                            </td>
                            <td>
                                <div class="teks-tebal">{{ $lap->tim?->nama_tim ?? '-' }}</div>
                                @if ($lap->nama_pilot)
                                    <div class="teks-kecil teks-pudar"><i class="bi bi-person"></i> Pilot: {{ $lap->nama_pilot }}</div>
                                @endif
                            </td>
                            <td>{{ $lap->distrik?->nama_distrik ?? '-' }}</td>
                            <td class="angka">
                                <div class="teks-tebal">{{ number_format($totalHa, 2, ',', '.') }} Ha</div>
                                <div class="teks-kecil teks-pudar">{{ $petakNama }}</div>
                            </td>
                            <td>
                                @php
                                    $warna = match ($lap->status_laporan) {
                                        'disetujui' => 'hijau',
                                        'menunggu_pemeriksaan' => 'emas',
                                        'perlu_perbaikan' => 'merah',
                                        'draf' => 'abu',
                                        'ditolak' => 'abu',
                                        default => 'abu',
                                    };
                                    $label = \App\Models\LaporanRealisasi::statusLaporan()[$lap->status_laporan] ?? $lap->status_laporan;
                                @endphp
                                <x-lencana :warna="$warna" :teks="$label" />
                            </td>
                            <td class="aksi">
                                <a href="{{ route('realisasi.show', $lap) }}" class="tombol tombol--garis tombol--kecil" title="Lihat Rincian">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                @php
                                    $bisaEditLaporan = auth()->user()?->adalahPekerjaLapangan()
                                        && $lap->status_laporan === \App\Models\LaporanRealisasi::PERLU_PERBAIKAN;
                                @endphp
                                @if ($bisaEditLaporan)
                                    <a href="{{ route('realisasi.edit', $lap) }}" class="tombol tombol--garis tombol--kecil" title="Ubah Laporan">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-kosong ikon="bi-clipboard2-data" judul="Belum ada laporan realisasi pekerjaan">
                                    @if (auth()->user()->adalahPekerjaLapangan())
                                        Klik tombol "Input Laporan Realisasi" untuk melaporkan pengerjaan petak harian tim Anda.
                                    @else
                                        Belum ada data laporan realisasi yang sesuai dengan filter saat ini.
                                    @endif
                                </x-kosong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $daftarLaporan->links() }}
    </div>
@endsection
