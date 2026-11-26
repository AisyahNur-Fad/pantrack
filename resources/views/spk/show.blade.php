@extends('layouts.app')

@section('judul', 'Detail SPK: ' . $spk->nomor_spk)

@section('konten')
    <x-halaman-kepala
        :judul="'SPK: ' . $spk->nomor_spk"
        deskripsi="Rincian target operasional, daftar petak, dan riwayat persetujuan SPK."
        ikon="bi-file-earmark-text">
        <div class="flex-antara gap-2">
            <a href="{{ route('spk.index') }}" class="tombol tombol--garis">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
            @if (auth()->user()->adalahKepalaLapangan() && ! $spk->laporanRealisasi()->exists() && $spk->status_spk !== 'selesai')
                <a href="{{ route('spk.edit', $spk) }}" class="tombol tombol--utama">
                    <i class="bi bi-pencil"></i> Ubah SPK
                </a>
            @endif
            @if (auth()->user()->adalahKepalaLapangan() && $spk->status_spk === 'disetujui')
                <form method="POST" action="{{ route('spk.selesai', $spk) }}" onsubmit="return confirm('Tandai SPK ini selesai dan tutup dari antrian pengerjaan?');" style="display: inline;">
                    @csrf
                    <button type="submit" class="tombol tombol--garis" style="color: var(--warna-hijau, #10b981); border-color: var(--warna-hijau, #10b981);">
                        <i class="bi bi-check-circle-fill"></i> Tandai SPK Selesai
                    </button>
                </form>
            @endif
            @if (auth()->user()->adalahPekerjaLapangan() && $spk->status_spk === 'disetujui')
                <a href="{{ route('realisasi.create', ['spk_id' => $spk->id]) }}" class="tombol tombol--utama">
                    <i class="bi bi-clipboard-plus"></i> Input Realisasi
                </a>
            @endif
        </div>
    </x-halaman-kepala>

    @if ($spk->status_spk === 'perlu_perbaikan')
        <div class="alert alert--bahaya mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Catatan Perbaikan dari Kepala Lapangan:</strong>
                <p class="mb-0">{{ $spk->catatan_perbaikan ?? 'Silakan lakukan revisi data sesuai arahan.' }}</p>
            </div>
        </div>
    @endif

    <div class="grid-form mb-4">
        <div class="kartu">
            <h3 class="judul-sub mb-3">Informasi SPK</h3>
            <table class="tabel-info">
                <tr>
                    <td class="teks-pudar" style="width: 35%">Nomor SPK</td>
                    <td class="teks-tebal">{{ $spk->nomor_spk }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Tanggal SPK</td>
                    <td>{{ $spk->tanggal_spk ? $spk->tanggal_spk->translatedFormat('d F Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Periode</td>
                    <td>{{ Carbon\Carbon::create($spk->periode_tahun, $spk->periode_bulan, 1)->translatedFormat('F Y') }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Distrik</td>
                    <td>{{ $spk->distrik?->nama_distrik ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Penugasan Tim</td>
                    <td>
                        @if ($spk->tim)
                            <span class="lencana lencana--biru"><i class="bi bi-people-fill"></i> {{ $spk->tim->nama_tim }}</span>
                        @else
                            <span class="teks-pudar">Terbuka untuk Semua Tim di Distrik Ini</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Jenis Pekerjaan Target</td>
                    <td>{{ $spk->jenisPekerjaan?->nama_pekerjaan ?? 'Semua Jenis / Campuran' }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Layanan Mapping</td>
                    <td>
                        @if ($spk->ada_layanan_mapping)
                            <span class="lencana lencana--info" style="font-size: 0.825rem; font-weight: 600;">
                                <i class="bi bi-geo-alt-fill"></i> Termasuk Mapping ({{ number_format($spk->total_luas_mapping, 2, ',', '.') }} Ha)
                            </span>
                        @else
                            <span class="teks-pudar">Tanpa Mapping (Petak Lama)</span>
                        @endif
                    </td>
                </tr>
                @if ($spk->total_estimasi_biaya > 0)
                <tr>
                    <td class="teks-pudar">Estimasi Anggaran SPK</td>
                    <td>
                        <strong style="font-size: 1.05rem; color: #059669;">Rp {{ number_format($spk->total_estimasi_biaya, 0, ',', '.') }}</strong>
                        <div class="teks-kecil teks-pudar mt-1">
                            Pokok: Rp {{ number_format($spk->total_biaya_pokok, 0, ',', '.') }}
                            @if ($spk->ada_layanan_mapping)
                                | Mapping: Rp {{ number_format($spk->total_biaya_mapping, 0, ',', '.') }}
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="teks-pudar">Keterangan</td>
                    <td>{{ $spk->keterangan ?: '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="kartu">
            <h3 class="judul-sub mb-3">Status & Alur Persetujuan</h3>
            <table class="tabel-info">
                <tr>
                    <td class="teks-pudar" style="width: 35%">Status SPK</td>
                    <td>
                        @php
                            $warna = match ($spk->status_spk) {
                                'disetujui' => 'hijau',
                                'selesai' => 'biru',
                                'menunggu_persetujuan' => 'emas',
                                'perlu_perbaikan' => 'merah',
                                'ditolak' => 'abu',
                                default => 'abu',
                            };
                            $label = \App\Models\Spk::statusSpk()[$spk->status_spk] ?? $spk->status_spk;
                        @endphp
                        <x-lencana :warna="$warna" :teks="$label" />
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Diinput Oleh</td>
                    <td>{{ $spk->penginput?->nama_lengkap ?? '—' }} ({{ $spk->created_at->translatedFormat('d M Y, H:i') }})</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Penyetuju / Pemeriksa</td>
                    <td>{{ $spk->penyetuju?->nama_lengkap ?? 'Belum disetujui' }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Waktu Persetujuan</td>
                    <td>{{ $spk->tanggal_persetujuan ? $spk->tanggal_persetujuan->translatedFormat('d M Y, H:i') : '—' }}</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Total Target Luas</td>
                    <td class="teks-tebal teks-utama">{{ number_format($totalLuasTarget, 2, ',', '.') }} Ha</td>
                </tr>
                <tr>
                    <td class="teks-pudar">Total Realisasi Disetujui</td>
                    <td class="teks-tebal">
                        {{ number_format($totalLuasRealisasi, 2, ',', '.') }} Ha
                        @php
                            $totalPersen = $totalLuasTarget > 0 ? round(($totalLuasRealisasi / $totalLuasTarget) * 100, 1) : 0;
                        @endphp
                        <span class="lencana lencana--{{ $totalPersen >= 100 ? 'hijau' : ($totalPersen > 0 ? 'biru' : 'abu') }} ms-1">
                            {{ $totalPersen }}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="teks-pudar">Berkas Fisik</td>
                    <td>
                        @if ($spk->berkas_spk)
                            <a href="{{ route('spk.berkas', $spk) }}" target="_blank" class="tombol tombol--garis tombol--kecil">
                                <i class="bi bi-file-earmark-arrow-down"></i> Unduh Berkas SPK
                            </a>
                        @else
                            <span class="teks-pudar">Tidak ada berkas diunggah</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Tabel Target Petak --}}
    <div class="kartu mb-4">
        <div class="flex-antara item-tengah mb-3">
            <h3 class="judul-sub">Daftar Petak Target ({{ $spk->detail->count() }} Petak)</h3>
            <div class="flex-tengah gap-2">
                <span class="lencana lencana--biru">Target: {{ number_format($totalLuasTarget, 2, ',', '.') }} Ha</span>
                <span class="lencana lencana--hijau">Realisasi Disetujui: {{ number_format($totalLuasRealisasi, 2, ',', '.') }} Ha</span>
            </div>
        </div>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Petak</th>
                        <th>Nama / Blok Petak</th>
                        <th>Jenis Lahan</th>
                        <th class="angka">Luas Target (Ha)</th>
                        <th style="text-align: center;">Layanan Mapping</th>
                        <th class="angka">Estimasi Biaya</th>
                        <th class="angka">Realisasi (Ha)</th>
                        <th style="min-width: 140px;">Progres</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($spk->detail as $item)
                        @php
                            $realisasiPetak = (float) ($realisasiDisetujuiPerPetak[$item->petak_id] ?? 0.0);
                            $persenPetak = $item->luas_target_ha > 0 ? round(($realisasiPetak / $item->luas_target_ha) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td class="teks-pudar">{{ $loop->iteration }}</td>
                            <td class="teks-tebal">{{ $item->petak?->kode_petak ?? '-' }}</td>
                            <td>{{ $item->petak?->nama_petak ?: '—' }}</td>
                            <td>{{ $item->petak?->jenis_lahan ?: '—' }}</td>
                            <td class="angka teks-tebal">{{ number_format($item->luas_target_ha, 2, ',', '.') }} Ha</td>
                            <td style="text-align: center;">
                                @if ($item->perlu_mapping)
                                    <span class="lencana lencana--info" style="font-size: 0.75rem;">
                                        <i class="bi bi-geo-alt-fill"></i> Mapping (+Rp {{ number_format($item->tarif_mapping_per_ha ?? 30000, 0, ',', '.') }})
                                    </span>
                                @else
                                    <span class="teks-pudar teks-kecil">—</span>
                                @endif
                            </td>
                            <td class="angka">
                                @if ($item->subtotal_biaya > 0)
                                    <span style="font-weight: 600; color: #0f172a; font-size: 0.85rem;">
                                        Rp {{ number_format($item->subtotal_biaya, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="teks-pudar teks-kecil">—</span>
                                @endif
                            </td>
                            <td class="angka teks-tebal teks-utama">{{ number_format($realisasiPetak, 2, ',', '.') }} Ha</td>
                            <td>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 2px;">
                                    <span class="teks-tebal">{{ $persenPetak }}%</span>
                                    <span class="teks-pudar">{{ $persenPetak >= 100 ? 'Selesai' : 'Berjalan' }}</span>
                                </div>
                                <div style="height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: {{ min(100, $persenPetak) }}%; background: {{ $persenPetak >= 100 ? '#10b981' : ($persenPetak > 0 ? '#0284c7' : '#cbd5e1') }}; border-radius: 4px; transition: width 0.3s ease;"></div>
                                </div>
                            </td>
                            <td>{{ $item->catatan ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="baris-total">
                        <td colspan="4" class="teks-kanan teks-tebal">Total Akumulasi:</td>
                        <td class="angka teks-tebal">{{ number_format($totalLuasTarget, 2, ',', '.') }} Ha</td>
                        <td style="text-align: center;">
                            @if ($spk->ada_layanan_mapping)
                                <span class="lencana lencana--info" style="font-size: 0.75rem;">
                                    {{ number_format($spk->total_luas_mapping, 2, ',', '.') }} Ha Mapping
                                </span>
                            @else
                                <span class="teks-pudar teks-kecil">—</span>
                            @endif
                        </td>
                        <td class="angka teks-tebal teks-sukses">
                            @if ($spk->total_estimasi_biaya > 0)
                                Rp {{ number_format($spk->total_estimasi_biaya, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="angka teks-tebal teks-utama">{{ number_format($totalLuasRealisasi, 2, ',', '.') }} Ha</td>
                        <td>
                            <strong class="teks-kecil teks-{{ $totalPersen >= 100 ? 'sukses' : 'utama' }}">{{ $totalPersen }}% Tercapai</strong>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Laporan Realisasi yang Dikerjakan Pekerja untuk SPK Ini --}}
    @if ($spk->laporanRealisasi->isNotEmpty())
        <div class="kartu mb-4">
            <div class="flex-antara item-tengah mb-3">
                <h3 class="judul-sub"><i class="bi bi-clipboard2-check teks-utama"></i> Laporan Realisasi Pekerjaan ({{ $spk->laporanRealisasi->count() }})</h3>
                <span class="teks-pudar teks-kecil">Laporan yang dikerjakan oleh pekerja lapangan berdasarkan SPK ini</span>
            </div>

            <div class="tabel-bungkus">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>Tanggal Pelaksanaan</th>
                            <th>Tim Operasional</th>
                            <th>Pilot</th>
                            <th>Status Laporan</th>
                            <th class="aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spk->laporanRealisasi as $lap)
                            @php
                                $tglReal = $lap->detail->first()?->tanggal_pekerjaan?->translatedFormat('d M Y') 
                                    ?? $lap->tanggal_input?->translatedFormat('d M Y') 
                                    ?? $lap->created_at?->translatedFormat('d M Y');
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('realisasi.show', $lap) }}" class="teks-tebal tautan-tabel">
                                        {{ $tglReal }}
                                    </a>
                                </td>
                                <td>{{ $lap->tim?->nama_tim ?? '-' }}</td>
                                <td>{{ $lap->nama_pilot }}</td>
                                <td>
                                    <x-lencana :warna="match($lap->status_laporan) { 'disetujui' => 'hijau', 'menunggu_pemeriksaan' => 'emas', 'perlu_perbaikan' => 'merah', default => 'abu' }" :teks="\App\Models\LaporanRealisasi::statusLaporan()[$lap->status_laporan] ?? $lap->status_laporan" />
                                </td>
                                <td class="aksi">
                                    <a href="{{ route('realisasi.show', $lap) }}" class="tombol tombol--garis tombol--kecil">Lihat Laporan</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Formulir Konfirmasi & Penilaian SPK khusus Admin / Atasan --}}
    @if ((auth()->user()->adalahAdmin() || auth()->user()->adalahAtasan()) && $spk->status_spk !== 'disetujui')
        <div class="kartu">
            <h3 class="judul-sub mb-2"><i class="bi bi-check2-circle teks-utama"></i> Konfirmasi & Penilaian SPK (Admin)</h3>
            <p class="teks-pudar teks-kecil mb-3">Periksa kecocokan nomor SPK, daftar petak target yang dilaporkan Kepala Lapangan, kebutuhan mapping, dan estimasi anggaran operasional. Anda dapat mengonfirmasi/menyetujui, mengembalikan untuk perbaikan, atau menolak SPK ini.</p>

            <form method="POST" action="{{ route('spk.verifikasi', $spk) }}" class="alur-vertikal">
                @csrf

                <x-field label="Catatan Pemeriksaan / Arahan Penilaian" for="catatan" bantuan="Wajib diisi bila meminta perbaikan atau menolak SPK.">
                    <textarea id="catatan" name="catatan" rows="3" class="masukan" placeholder="Tuliskan catatan arahan jika ada penyesuaian target petak, anggaran, atau nomor SPK…">{{ old('catatan') }}</textarea>
                </x-field>

                <div class="bilah-aksi">
                    <button type="submit" name="tindakan" value="setujui" class="tombol tombol--utama">
                        <i class="bi bi-check-lg"></i> Setujui & Konfirmasi SPK
                    </button>
                    <button type="submit" name="tindakan" value="perbaikan" class="tombol tombol--garis">
                        <i class="bi bi-arrow-counterclockwise"></i> Kembalikan untuk Perbaikan
                    </button>
                    <button type="submit" name="tindakan" value="tolak" class="tombol tombol--garis teks-bahaya">
                        <i class="bi bi-x-circle"></i> Tolak SPK
                    </button>
                </div>
            </form>
        </div>
    @endif
@endsection
