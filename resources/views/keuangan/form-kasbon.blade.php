@extends('layouts.app')

@section('judul', $kasbon ? 'Edit Kasbon Personil' : 'Catat Kasbon Kru')

@section('konten')
    <x-halaman-kepala
        :judul="$kasbon ? 'Edit Kasbon Personil / Kru' : 'Catat Kasbon Personil / Kru'"
        deskripsi="Form pencatatan pinjaman uang muka (kasbon) pilot, helper, atau tenaga pelangsir yang akan otomatis memotong slip gaji pada laporan bulanan."
        ikon="bi-person-dash">
        <a href="{{ route('keuangan.index', ['tim_id' => $timId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--garis">
            <i class="bi bi-arrow-left"></i> Kembali ke Rekap Keuangan
        </a>
    </x-halaman-kepala>

    <div class="kartu">
        <div class="kartu__badan">
            <form method="POST"
                  action="{{ $kasbon ? route('keuangan.kasbon.update', $kasbon) : route('keuangan.kasbon.store') }}"
                  class="alur-vertikal">
                @csrf
                @if ($kasbon)
                    @method('PUT')
                @endif

                <div class="grid-form">
                    <x-field label="Unit Tim Drone" for="tim_id" :wajib="true">
                        <select id="tim_id" name="tim_id" class="masukan @error('tim_id') masukan--error @enderror" required>
                            @foreach ($daftarTim as $t)
                                <option value="{{ $t->id }}" @selected(old('tim_id', $kasbon?->tim_id ?? $timId) == $t->id)>
                                    {{ $t->nama_tim }} &bull; Unit: {{ $t->drone?->nomor_drone ?? '-' }}{{ !empty($t->drone?->nama_drone) ? ' · '.$t->drone->nama_drone : '' }} ({{ $t->drone?->tipe_drone ?? '' }})
                                </option>
                            @endforeach
                        </select>
                    </x-field>

                    <x-field label="Periode Bulan & Tahun" for="periode_bulan" :wajib="true">
                        <div class="baris-tombol" style="gap: 0.5rem; display: flex;">
                            <select id="periode_bulan" name="periode_bulan" class="masukan @error('periode_bulan') masukan--error @enderror" required style="flex: 2;">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected((int) old('periode_bulan', $kasbon?->periode_bulan ?? $bulan) === $m)>
                                        {{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                            <select id="periode_tahun" name="periode_tahun" class="masukan @error('periode_tahun') masukan--error @enderror" required style="flex: 1;">
                                @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                    <option value="{{ $y }}" @selected((int) old('periode_tahun', $kasbon?->periode_tahun ?? $tahun) === $y)>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </x-field>

                    <x-field label="Tanggal Kasbon Diambil" for="tanggal_kasbon" :wajib="true">
                        <input type="date" id="tanggal_kasbon" name="tanggal_kasbon"
                               value="{{ old('tanggal_kasbon', $kasbon?->tanggal_kasbon?->toDateString() ?? now()->toDateString()) }}"
                               class="masukan @error('tanggal_kasbon') masukan--error @enderror" required>
                    </x-field>

                    <x-field label="Peran Personil" for="peran_kru" :wajib="true" bantuan="Pilih jabatan kru untuk dialokasikan pada Slip Gaji.">
                        <select id="peran_kru" name="peran_kru" class="masukan @error('peran_kru') masukan--error @enderror" required>
                            <option value="pilot" @selected(old('peran_kru', $kasbon?->peran_kru) === 'pilot')>Pilot Drone</option>
                            <option value="helper" @selected(old('peran_kru', $kasbon?->peran_kru) === 'helper')>Helper 1 / Helper 2</option>
                            <option value="pelangsir" @selected(old('peran_kru', $kasbon?->peran_kru) === 'pelangsir')>Pelangsir Air & Logistik</option>
                            <option value="lainnya" @selected(old('peran_kru', $kasbon?->peran_kru) === 'lainnya')>Kru Lainnya</option>
                        </select>
                    </x-field>

                    <x-field label="Nama Personil / Anggota" for="nama_personil" :wajib="true" bantuan="Ketik nama personil yang mengambil kasbon.">
                        <input type="text" id="nama_personil" name="nama_personil"
                               value="{{ old('nama_personil', $kasbon?->nama_personil) }}"
                               placeholder="Contoh: Pranoto / Diego / Heru"
                               class="masukan @error('nama_personil') masukan--error @enderror" required>
                    </x-field>

                    <x-field label="Jumlah Kasbon (Rp)" for="jumlah_kasbon" :wajib="true">
                        <input type="number" id="jumlah_kasbon" name="jumlah_kasbon" min="1" step="any"
                               value="{{ old('jumlah_kasbon', $kasbon?->jumlah_kasbon) }}"
                               placeholder="Contoh: 1000000"
                               class="masukan @error('jumlah_kasbon') masukan--error @enderror" required>
                    </x-field>

                    <x-field label="Keterangan / Keperluan" for="keterangan" class="lebar-penuh" bantuan="Tuliskan catatan pinjaman (opsional).">
                        <textarea id="keterangan" name="keterangan" rows="2"
                                  class="masukan @error('keterangan') masukan--error @enderror"
                                  placeholder="Contoh: Pinjaman uang muka kebutuhan keluarga">{{ old('keterangan', $kasbon?->keterangan) }}</textarea>
                    </x-field>

                    <x-field label="Status Pelunasan Kasbon" for="status_lunas" class="lebar-penuh" bantuan="Centang jika kasbon ini sudah dipotong dari slip gaji kru atau telah dilunasi.">
                        <label class="kotak-centang d-inline-flex align-items-center gap-2">
                            <input type="checkbox" id="status_lunas" name="status_lunas" value="1" @checked((bool) old('status_lunas', $kasbon?->status_lunas))>
                            <span><strong>Tandai Kasbon Ini Lunas / Sudah Terpotong dari Slip Gaji</strong></span>
                        </label>
                    </x-field>
                </div>

                <div class="baris-tombol mt-4">
                    <button type="submit" class="tombol tombol--utama">
                        <i class="bi bi-check2-circle"></i> {{ $kasbon ? 'Simpan Perubahan' : 'Catat Kasbon' }}
                    </button>
                    <a href="{{ route('keuangan.index', ['tim_id' => $timId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--garis">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
