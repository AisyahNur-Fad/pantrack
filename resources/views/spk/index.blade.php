@extends('layouts.app')

@section('judul', 'Surat Perintah Kerja (SPK)')

@section('konten')
    <x-halaman-kepala
        judul="Surat Perintah Kerja (SPK)"
        deskripsi="Target pekerjaan drone agrikultur per petak dan distrik yang menjadi acuan resmi operasional lapangan."
        ikon="bi-file-earmark-text">
        @if (auth()->user()->adalahKepalaLapangan())
            <a href="{{ route('spk.create') }}" class="tombol tombol--utama">
                <i class="bi bi-plus-lg"></i> Buat SPK Baru
            </a>
        @endif
    </x-halaman-kepala>

    <div class="kartu">
        <form method="GET" class="bilah-alat">
            <div class="cari-bungkus">
                <i class="bi bi-search"></i>
                <input type="search" name="cari" value="{{ $cari }}" class="masukan" placeholder="Cari nomor SPK atau keterangan…">
            </div>

            <select name="distrik_id" class="masukan" data-kirim-otomatis aria-label="Distrik">
                <option value="">Semua Distrik</option>
                @foreach ($daftarDistrik as $d)
                    <option value="{{ $d->id }}" @selected((string) $distrikId === (string) $d->id)>{{ $d->nama_distrik }}</option>
                @endforeach
            </select>

            <select name="status" class="masukan" data-kirim-otomatis aria-label="Status SPK">
                <option value="">Semua Status</option>
                <option value="menunggu_persetujuan" @selected($status === 'menunggu_persetujuan')>Menunggu Persetujuan</option>
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
            @if ($cari !== '' || $distrikId || $status)
                <a href="{{ route('spk.index') }}" class="tombol tombol--garis"><i class="bi bi-x-lg"></i> Reset</a>
            @endif
        </form>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor SPK</th>
                        <th>Tanggal</th>
                        <th>Periode</th>
                        <th>Distrik</th>
                        <th>Jenis Pekerjaan</th>
                        <th class="angka">Jumlah Petak</th>
                        <th>Status</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSpk as $spk)
                        <tr>
                            <td class="teks-pudar">{{ $daftarSpk->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('spk.show', $spk) }}" class="teks-tebal tautan-tabel">
                                    {{ $spk->nomor_spk }}
                                </a>
                                @if ($spk->berkas_spk)
                                    <i class="bi bi-paperclip teks-pudar" title="Ada lampiran berkas"></i>
                                @endif
                            </td>
                            <td>{{ $spk->tanggal_spk ? $spk->tanggal_spk->translatedFormat('d M Y') : '-' }}</td>
                            <td>{{ Carbon\Carbon::create($spk->periode_tahun, $spk->periode_bulan, 1)->translatedFormat('M Y') }}</td>
                            <td>{{ $spk->distrik?->nama_distrik ?? '-' }}</td>
                            <td>{{ $spk->jenisPekerjaan?->nama_pekerjaan ?? 'Semua Jenis' }}</td>
                            <td class="angka">{{ $spk->detail_count }} petak</td>
                            <td>
                                @php
                                    $warna = match ($spk->status_spk) {
                                        'disetujui' => 'hijau',
                                        'menunggu_persetujuan' => 'emas',
                                        'perlu_perbaikan' => 'merah',
                                        'ditolak' => 'abu',
                                        default => 'abu',
                                    };
                                    $label = \App\Models\Spk::statusSpk()[$spk->status_spk] ?? $spk->status_spk;
                                @endphp
                                <x-lencana :warna="$warna" :teks="$label" />
                            </td>
                            <td class="aksi">
                                <a href="{{ route('spk.show', $spk) }}" class="tombol tombol--garis tombol--kecil" title="Lihat Detail">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                @if (auth()->user()->adalahKepalaLapangan() && ! $spk->laporanRealisasi()->exists())
                                    <a href="{{ route('spk.edit', $spk) }}" class="tombol tombol--garis tombol--kecil" title="Ubah SPK">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-kosong ikon="bi-file-earmark-text" judul="Belum ada Surat Perintah Kerja (SPK)">
                                    Klik tombol "Buat SPK Baru" untuk menambahkan target pekerjaan operasional drone.
                                </x-kosong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $daftarSpk->links() }}
    </div>
@endsection
