@extends('layouts.app')

@section('judul', 'Riwayat Aktivitas')

@section('konten')
    <x-halaman-kepala
        judul="Riwayat Aktivitas"
        ikon="bi-clock-history"
        :deskripsi="$lihatSemua ? 'Seluruh aktivitas penting di PANTRACK. Catatan hanya dapat dibaca dan tidak dapat diubah.' : 'Aktivitas Anda sendiri di PANTRACK. Catatan hanya dapat dibaca dan tidak dapat diubah.'" />

    <div class="kartu">
        <form method="GET" class="bilah-alat">
            <div class="cari-bungkus">
                <i class="bi bi-search"></i>
                <input type="search" name="cari" value="{{ $cari }}" class="masukan" placeholder="Cari aktivitas atau keterangan…">
            </div>

            <select name="jenis_data" class="masukan" data-kirim-otomatis aria-label="Jenis data">
                <option value="">Semua jenis data</option>
                @foreach ($jenisData as $jenis)
                    <option value="{{ $jenis }}" @selected(request('jenis_data') === $jenis)>{{ $jenis }}</option>
                @endforeach
            </select>

            <button type="submit" class="tombol tombol--garis"><i class="bi bi-funnel"></i> Terapkan</button>

            @if ($cari !== '' || request()->filled('jenis_data'))
                <a href="{{ route('riwayat.index') }}" class="tombol tombol--garis"><i class="bi bi-x-lg"></i> Reset</a>
            @endif
        </form>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aktivitas</th>
                        <th>Data</th>
                        <th>Keterangan</th>
                        <th>Alamat IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayat as $r)
                        <tr>
                            <td class="teks-redup" style="white-space: nowrap">{{ $r->created_at?->translatedFormat('d M Y, H:i') }}</td>
                            <td>{{ $r->pengguna?->nama_lengkap ?? 'Tamu / tidak dikenal' }}</td>
                            <td class="tebal">{{ $r->nama_aktivitas }}</td>
                            <td><x-lencana warna="netral">{{ $r->jenis_data }}{{ $r->id_data ? ' #'.$r->id_data : '' }}</x-lencana></td>
                            <td class="teks-redup">{{ $r->keterangan ?? '—' }}</td>
                            <td class="teks-pudar">{{ $r->alamat_ip ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-kosong ikon="bi-clock-history" judul="Belum ada riwayat aktivitas">
                                    Aktivitas seperti login, perubahan data, dan persetujuan akan tercatat di sini.
                                </x-kosong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $riwayat->links() }}
    </div>
@endsection
