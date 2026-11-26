@extends('layouts.app')

@section('judul', 'Detail Transaksi BBM: ' . $bbm->nomor_transaksi)

@section('konten')
    @php
        $pengguna = auth()->user();
        $bolehKelola = ($pengguna->adalahAdmin() || $pengguna->adalahPekerjaLapangan())
            && $bbm->status_verifikasi !== \App\Models\TransaksiBbm::TERVERIFIKASI
            && ($pengguna->adalahAdmin() || $bbm->diinput_oleh === $pengguna->id);
        $bolehLihatBiaya = $pengguna->adalahAdmin() || $pengguna->adalahAtasan() || $bbm->diinput_oleh === $pengguna->id;
        $labelTx = \App\Models\TransaksiBbm::jenisTransaksi()[$bbm->jenis_transaksi] ?? $bbm->jenis_transaksi;
        $warnaTx = match ($bbm->jenis_transaksi) {
            'penerimaan', 'stok_awal' => 'hijau',
            'penggunaan' => 'merah',
            'pengajuan' => 'biru',
            default => 'abu',
        };
    @endphp

    <x-halaman-kepala
        :judul="'Transaksi BBM · ' . $bbm->nomor_transaksi"
        deskripsi="Rincian jumlah bahan bakar, bukti nota, dan status verifikasi tim."
        ikon="bi-fuel-pump">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('bbm.index') }}" class="tombol tombol--garis">
                <i class="bi bi-arrow-left"></i> Kembali ke Monitoring
            </a>
            @if ($bolehKelola)
                <a href="{{ route('bbm.edit', $bbm) }}" class="tombol tombol--utama">
                    <i class="bi bi-pencil"></i> Ubah
                </a>
                <form method="POST"
                      action="{{ route('bbm.destroy', $bbm) }}"
                      data-konfirmasi="Batalkan dan hapus transaksi BBM {{ $bbm->nomor_transaksi }}?"
                      style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tombol tombol--garis teks-bahaya">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </form>
            @endif
        </div>
    </x-halaman-kepala>

    {{-- Banner Status Transaksi yang Jelas --}}
    @if ($bbm->status_verifikasi === 'terverifikasi')
        <div class="alert alert--sukses mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <div>
                <strong>Transaksi Sah & Terverifikasi</strong>
                <div class="teks-kecil">Data ini sudah masuk ke perhitungan saldo resmi tim dan terkunci agar riwayat stok tetap akurat.</div>
            </div>
        </div>
    @elseif ($bbm->status_verifikasi === 'perlu_perbaikan')
        <div class="alert alert--bahaya mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div style="flex: 1;">
                <strong>Perlu Perbaikan dari Admin</strong>
                <p class="mb-0">{{ $bbm->catatan_pemeriksaan ?: 'Silakan perbaiki data transaksi atau lengkapi foto struk nota.' }}</p>
            </div>
            @if ($bolehKelola)
                <a href="{{ route('bbm.edit', $bbm) }}" class="tombol tombol--bahaya tombol--kecil">Perbaiki Sekarang</a>
            @endif
        </div>
    @elseif ($bbm->status_verifikasi === 'ditolak')
        <div class="alert alert--bahaya mb-4">
            <i class="bi bi-x-circle-fill"></i>
            <div>
                <strong>Transaksi Ditolak</strong>
                <div class="teks-kecil">{{ $bbm->catatan_pemeriksaan ?: 'Transaksi ini ditolak dan tidak memengaruhi saldo tim.' }}</div>
            </div>
        </div>
    @else
        <div class="alert mb-4" style="background: var(--peringatan-bg, #fef3c7); border-color: #fde68a; color: #92400e;">
            <i class="bi bi-clock-fill"></i>
            <div>
                <strong>Menunggu Verifikasi Admin</strong>
                <div class="teks-kecil">Belum menambah/memotong saldo resmi tim sampai diperiksa oleh Admin.</div>
            </div>
        </div>
    @endif

    <div class="grid-form mb-4">
        {{-- Kolom Kiri: Informasi Utama BBM --}}
        <div class="kartu">
            <div class="kartu__kepala" style="padding: 14px 20px;">
                <h3 class="kartu__judul" style="margin: 0;">Rincian Bahan Bakar</h3>
                <x-lencana :warna="$warnaTx" :teks="$labelTx" />
            </div>
            <div class="kartu__badan" style="padding: 16px 20px;">
                <table class="tabel-info">
                    <tr>
                        <td class="teks-pudar" style="width: 38%">Tim Drone</td>
                        <td class="teks-tebal">
                            <a href="{{ route('bbm.index', ['tim_id' => $bbm->tim_id]) }}" class="tautan-tabel">
                                {{ $bbm->tim?->nama_tim ?? '-' }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="teks-pudar">Jenis BBM</td>
                        <td class="teks-tebal">{{ $bbm->jenisBbm?->nama_bbm ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="teks-pudar">Tanggal</td>
                        <td>{{ $bbm->tanggal_transaksi ? $bbm->tanggal_transaksi->translatedFormat('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="teks-pudar">Jumlah</td>
                        <td class="teks-tebal teks-utama" style="font-size: 1.05rem;">{{ number_format($bbm->jumlah_liter, 2, ',', '.') }} Liter</td>
                    </tr>
                    @if ($bolehLihatBiaya && $bbm->total_biaya && in_array($bbm->jenis_transaksi, ['penerimaan', 'stok_awal', 'pengajuan']))
                        <tr>
                            <td class="teks-pudar">Total Biaya</td>
                            <td class="teks-tebal" style="color: #059669;">Rp {{ number_format($bbm->total_biaya, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($bolehLihatBiaya && $bbm->harga_per_liter && in_array($bbm->jenis_transaksi, ['penerimaan', 'stok_awal', 'pengajuan']))
                        <tr>
                            <td class="teks-pudar">Harga / Liter</td>
                            <td>Rp {{ number_format($bbm->harga_per_liter, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($bbm->nomor_struk_nota)
                        <tr>
                            <td class="teks-pudar">No. Struk / Nota</td>
                            <td class="teks-tebal">{{ $bbm->nomor_struk_nota }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="teks-pudar">Keperluan</td>
                        <td>{{ $bbm->labelKeperluan() }}</td>
                    </tr>
                    <tr>
                        <td class="teks-pudar">Keterangan</td>
                        <td>{{ $bbm->keterangan ?: '—' }}</td>
                    </tr>
                    @if ($bbm->laporanRealisasi)
                        <tr>
                            <td class="teks-pudar">Sumber Realisasi</td>
                            <td>
                                <a href="{{ route('realisasi.show', $bbm->laporanRealisasi) }}" class="teks-tebal tautan-tabel">
                                    <i class="bi bi-airplane"></i> {{ $bbm->laporanRealisasi->spk?->nomor_spk ?? $bbm->laporanRealisasi->nomor_laporan }}
                                </a>
                            </td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Kolom Kanan: Jejak Pemeriksaan & Sisa Saldo Tim --}}
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div class="kartu">
                <div class="kartu__kepala" style="padding: 14px 20px;">
                    <h3 class="kartu__judul" style="margin: 0;">Jejak Pencatatan & Pemeriksaan</h3>
                </div>
                <div class="kartu__badan" style="padding: 16px 20px;">
                    <table class="tabel-info">
                        <tr>
                            <td class="teks-pudar" style="width: 38%">Dicatat Oleh</td>
                            <td>
                                <strong>{{ $bbm->penginput?->nama_lengkap ?? '—' }}</strong>
                                <div class="teks-kecil teks-pudar">{{ $bbm->created_at->translatedFormat('d M Y, H:i') }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="teks-pudar">Pemeriksa (Admin)</td>
                            <td>{{ $bbm->pemeriksa?->nama_lengkap ?? 'Belum diperiksa' }}</td>
                        </tr>
                        <tr>
                            <td class="teks-pudar">Waktu Periksa</td>
                            <td>{{ $bbm->tanggal_pemeriksaan ? $bbm->tanggal_pemeriksaan->translatedFormat('d M Y, H:i') : '—' }}</td>
                        </tr>
                        @if ($bbm->catatan_pemeriksaan)
                            <tr>
                                <td class="teks-pudar">Catatan Admin</td>
                                <td>{{ $bbm->catatan_pemeriksaan }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if (!empty($saldoTimSaatIni))
                <div class="kartu kartu--pudar">
                    <div class="kartu__badan" style="padding: 16px 20px;">
                        <div class="flex-antara item-tengah mb-2">
                            <span class="teks-kecil teks-pudar">Sisa Saldo Resmi Tim Saat Ini</span>
                            <strong class="{{ $saldoTimSaatIni['saldo'] < 0 ? 'teks-bahaya' : 'teks-utama' }}">
                                {{ number_format($saldoTimSaatIni['saldo'], 2, ',', '.') }} Liter
                            </strong>
                        </div>
                        @if (!empty($saldoTimSaatIni['saldo_per_jenis']))
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                @foreach ($saldoTimSaatIni['saldo_per_jenis'] as $spj)
                                    <span style="background: #fff; border: 1px solid #e2e8f0; border-radius: 999px; padding: 3px 10px; font-size: 12px;">
                                        <span class="teks-pudar">{{ $spj['nama_bbm'] }}:</span>
                                        <strong>{{ number_format($spj['saldo'], 1, ',', '.') }} L</strong>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Lampiran Bukti Nota / Struk --}}
    @if ($bbm->lampiran->isNotEmpty())
        <div class="kartu mb-4">
            <div class="kartu__kepala" style="padding: 14px 20px;">
                <h3 class="kartu__judul" style="margin: 0;"><i class="bi bi-receipt teks-utama"></i> Bukti Struk / Nota</h3>
            </div>
            <div class="kartu__badan" style="padding: 16px 20px;">
                <div class="grid-form">
                    @foreach ($bbm->lampiran as $lamp)
                        <div class="kartu kartu--pudar p-3">
                            <div class="flex-antara item-tengah mb-2">
                                <span class="teks-tebal teks-kecil">{{ $lamp->nama_berkas_asli }}</span>
                                <a href="{{ route('bbm.lampiran', $lamp) }}" target="_blank" class="tombol tombol--garis tombol--kecil">
                                    <i class="bi bi-box-arrow-up-right"></i> Buka
                                </a>
                            </div>
                            @if (preg_match('/\.(jpg|jpeg|png)$/i', $lamp->lokasi_berkas))
                                <a href="{{ route('bbm.lampiran', $lamp) }}" target="_blank">
                                    <img src="{{ route('bbm.lampiran', $lamp) }}" alt="Bukti Struk Nota" class="gambar-bukti">
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Panel Verifikasi Admin --}}
    @if ($pengguna->adalahAdmin() && $bbm->status_verifikasi !== 'terverifikasi')
        <div class="kartu">
            <div class="kartu__kepala" style="padding: 14px 20px;">
                <div>
                    <h3 class="kartu__judul" style="margin: 0;"><i class="bi bi-shield-check teks-utama"></i> Tindakan Verifikasi Admin</h3>
                    <div class="teks-kecil teks-pudar">Pastikan jumlah liter, bukti nota, dan keperluan sudah sesuai sebelum menyetujui.</div>
                </div>
            </div>
            <div class="kartu__badan" style="padding: 18px 20px;">
                <form method="POST" action="{{ route('bbm.verifikasi', $bbm) }}" class="alur-vertikal">
                    @csrf

                    <x-field label="Catatan Pemeriksaan" for="catatan" bantuan="Wajib diisi bila meminta perbaikan atau menolak transaksi.">
                        <textarea id="catatan" name="catatan" rows="3" class="masukan" placeholder="Tuliskan catatan jika bukti kurang jelas atau angka perlu diperbaiki…">{{ old('catatan') }}</textarea>
                    </x-field>

                    <div class="bilah-aksi">
                        <button type="submit" name="tindakan" value="verifikasi" class="tombol tombol--utama">
                            <i class="bi bi-check-lg"></i> Setujui & Verifikasi
                        </button>
                        <button type="submit" name="tindakan" value="perbaikan" class="tombol tombol--garis">
                            <i class="bi bi-arrow-counterclockwise"></i> Minta Perbaikan
                        </button>
                        <button type="submit" name="tindakan" value="tolak" class="tombol tombol--garis teks-bahaya">
                            <i class="bi bi-x-circle"></i> Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
