@extends('layouts.app')

@section('judul', $pengeluaran ? 'Edit Pengeluaran Unit' : 'Catat Pengeluaran Unit')

@section('konten')
    <x-halaman-kepala
        :judul="$pengeluaran ? 'Edit Pengeluaran Operasional Unit' : 'Catat Pengeluaran Operasional Unit'"
        deskripsi="Form pencatatan biaya operasional, pembelian suku cadang/sparepart, ransum konsumsi, sewa mobil, dan bon unit drone."
        ikon="bi-receipt">
        <a href="{{ route('keuangan.index', ['tim_id' => $timId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--garis">
            <i class="bi bi-arrow-left"></i> Kembali ke Rekap Keuangan
        </a>
    </x-halaman-kepala>

    <div class="kartu">
        <div class="kartu__badan">
            <form method="POST"
                  action="{{ $pengeluaran ? route('keuangan.pengeluaran.update', $pengeluaran) : route('keuangan.pengeluaran.store') }}"
                  enctype="multipart/form-data"
                  class="alur-vertikal">
                @csrf
                @if ($pengeluaran)
                    @method('PUT')
                @endif

                <div class="grid-form">
                    <x-field label="Unit Tim Drone" for="tim_id" :wajib="true">
                        <select id="tim_id" name="tim_id" class="masukan @error('tim_id') masukan--error @enderror" required>
                            @foreach ($daftarTim as $t)
                                <option value="{{ $t->id }}" @selected(old('tim_id', $pengeluaran?->tim_id ?? $timId) == $t->id)>
                                    {{ $t->nama_tim }} &bull; Unit: {{ $t->drone?->nomor_drone ?? '-' }}{{ !empty($t->drone?->nama_drone) ? ' · '.$t->drone->nama_drone : '' }} ({{ $t->drone?->tipe_drone ?? '' }})
                                </option>
                            @endforeach
                        </select>
                    </x-field>

                    <x-field label="Periode Bulan & Tahun" for="periode_bulan" :wajib="true">
                        <div class="baris-tombol" style="gap: 0.5rem; display: flex;">
                            <select id="periode_bulan" name="periode_bulan" class="masukan @error('periode_bulan') masukan--error @enderror" required style="flex: 2;">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected((int) old('periode_bulan', $pengeluaran?->periode_bulan ?? $bulan) === $m)>
                                        {{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                            <select id="periode_tahun" name="periode_tahun" class="masukan @error('periode_tahun') masukan--error @enderror" required style="flex: 1;">
                                @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                    <option value="{{ $y }}" @selected((int) old('periode_tahun', $pengeluaran?->periode_tahun ?? $tahun) === $y)>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </x-field>

                    <x-field label="Tanggal Transaksi / Pengeluaran" for="tanggal_transaksi" :wajib="true">
                        <input type="date" id="tanggal_transaksi" name="tanggal_transaksi"
                               value="{{ old('tanggal_transaksi', $pengeluaran?->tanggal_transaksi?->toDateString() ?? now()->toDateString()) }}"
                               class="masukan @error('tanggal_transaksi') masukan--error @enderror" required>
                    </x-field>

                    <x-field label="Kategori Pengeluaran" for="kategori_biaya" :wajib="true" bantuan="Pilih kategori biaya. Perhatian: Pembelian Solar BBM yang telah dicatat di menu 'Monitoring BBM' otomatis masuk ke laporan closing dan tidak perlu dicatat ulang di sini.">
                        <select id="kategori_biaya" name="kategori_biaya" class="masukan @error('kategori_biaya') masukan--error @enderror" required>
                            @foreach ($kategoriBiaya as $key => $label)
                                <option value="{{ $key }}" @selected(old('kategori_biaya', $pengeluaran?->kategori_biaya) === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </x-field>

                    <x-field label="Nominal Biaya (Rp)" for="nominal" :wajib="true">
                        <input type="number" id="nominal" name="nominal" min="1" step="any"
                               value="{{ old('nominal', $pengeluaran?->nominal) }}"
                               placeholder="Contoh: 1500000"
                               class="masukan @error('nominal') masukan--error @enderror" required>
                    </x-field>

                    <x-field label="Nomor Bukti / No. PI (Proforma Invoice / Bon)" for="nomor_bukti_pi" bantuan="Nomor referensi nota/bon dari toko atau proforma invoice (contoh: PIHAN180826001).">
                        <input type="text" id="nomor_bukti_pi" name="nomor_bukti_pi"
                               value="{{ old('nomor_bukti_pi', $pengeluaran?->nomor_bukti_pi) }}"
                               placeholder="Contoh: PIHAN180826001 / BKM-08"
                               class="masukan @error('nomor_bukti_pi') masukan--error @enderror">
                    </x-field>

                    <x-field label="Keterangan / Rincian Belanja" for="keterangan" :wajib="true" class="lebar-penuh" bantuan="Tuliskan keterangan detail barang/jasa yang dibeli atau dibayarkan.">
                        <textarea id="keterangan" name="keterangan" rows="3"
                                  class="masukan @error('keterangan') masukan--error @enderror"
                                  placeholder="Contoh: Belanja solar, pelumas, konsumsi ransum lapangan, atau pengantaran drone"
                                  required>{{ old('keterangan', $pengeluaran?->keterangan) }}</textarea>
                    </x-field>

                    <x-field label="Lampiran Struk / Nota (Opsional)" for="lampiran_nota" bantuan="Unggah foto kuitansi atau file nota (JPG, PNG, PDF maks 5MB).">
                        <input type="file" id="lampiran_nota" name="lampiran_nota"
                               accept=".jpg,.jpeg,.png,.pdf"
                               class="masukan @error('lampiran_nota') masukan--error @enderror">
                        @if ($pengeluaran?->lampiran_nota)
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $pengeluaran->lampiran_nota) }}" target="_blank" class="tautan-aksi">
                                    <i class="bi bi-file-earmark-check"></i> Lihat Nota Tersimpan Sebelumnya
                                </a>
                            </div>
                        @endif
                    </x-field>
                </div>

                <div class="baris-tombol mt-4">
                    <button type="submit" class="tombol tombol--utama">
                        <i class="bi bi-check2-circle"></i> {{ $pengeluaran ? 'Simpan Perubahan' : 'Catat Pengeluaran' }}
                    </button>
                    <a href="{{ route('keuangan.index', ['tim_id' => $timId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--garis">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
