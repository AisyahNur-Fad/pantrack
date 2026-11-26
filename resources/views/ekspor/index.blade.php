@extends('layouts.app')

@section('judul', 'Ekspor Laporan Excel')

@section('konten')
    <x-halaman-kepala
        judul="Ekspor Laporan Realisasi Excel"
        deskripsi="Rekapitulasi resmi hasil pekerjaan drone agrikultur berstatus disetujui dalam format Excel standar perusahaan (FR-24, FR-25)."
        ikon="bi-file-earmark-excel">
    </x-halaman-kepala>

    <div class="grid-form mb-4">
        {{-- Formulir Pembuatan Berkas Ekspor --}}
        <div class="kartu">
            <div class="kartu__kepala">
                <h3 class="kartu__judul"><i class="bi bi-file-earmark-spreadsheet teks-utama"></i> Buat Rekapitulasi Excel Baru</h3>
                <span class="lencana lencana--hijau">Standar 11 Kolom PT. Panlibata</span>
            </div>
            <div class="kartu__badan">
                <form method="POST" action="{{ route('ekspor.proses') }}" class="alur-vertikal">
                    @csrf

                    <div class="grid-form">
                        <x-field label="Periode Bulan" for="bulan" :wajib="true">
                            <select id="bulan" name="bulan" class="masukan" required>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected($bulan === $m)>
                                        Bulan {{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </x-field>

                        <x-field label="Periode Tahun" for="tahun" :wajib="true">
                            <select id="tahun" name="tahun" class="masukan" required>
                                @for ($y = now()->year - 1; $y <= now()->year + 1; $y++)
                                    <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                                @endfor
                            </select>
                        </x-field>

                        <x-field label="Rentang Tanggal Mulai (Opsional)" for="tanggal_mulai">
                            <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="masukan" value="{{ $tanggalMulai }}">
                            <small class="teks-pudar">Jika diisi bersama tgl selesai, menggantikan filter bulan.</small>
                        </x-field>

                        <x-field label="Rentang Tanggal Selesai (Opsional)" for="tanggal_selesai">
                            <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="masukan" value="{{ $tanggalSelesai }}">
                            <small class="teks-pudar">Batas akhir pengerjaan realisasi.</small>
                        </x-field>

                        <x-field label="Distrik (Opsional)" for="distrik_id">
                            <select id="distrik_id" name="distrik_id" class="masukan">
                                <option value="">Semua Distrik</option>
                                @foreach ($daftarDistrik as $d)
                                    <option value="{{ $d->id }}" @selected((string) $distrikId === (string) $d->id)>{{ $d->nama_distrik }}</option>
                                @endforeach
                            </select>
                        </x-field>

                        <x-field label="Tim Drone" for="tim_id" bantuan="Disarankan memilih tim tertentu agar Cover, Slip Gaji kru, dan BAP 7-Sheet tepat per unit drone.">
                            <select id="tim_id" name="tim_id" class="masukan">
                                <option value="">Semua Tim (Default Unit Aktif)</option>
                                @foreach ($daftarTim as $t)
                                    <option value="{{ $t->id }}" @selected((string) $timId === (string) $t->id)>
                                        {{ $t->nama_tim }} &bull; Unit {{ $t->drone?->nomor_drone ?? '-' }}{{ !empty($t->drone?->nama_drone) ? ' · '.$t->drone->nama_drone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </x-field>

                        <x-field label="Unit Drone (Opsional)" for="drone_id">
                            <select id="drone_id" name="drone_id" class="masukan">
                                <option value="">Semua Drone</option>
                                @foreach ($daftarDrone as $dr)
                                    <option value="{{ $dr->id }}" @selected((string) $droneId === (string) $dr->id)>{{ $dr->nomor_drone }}{{ !empty($dr->nama_drone) ? ' · '.$dr->nama_drone : '' }} ({{ $dr->tipe_drone }})</option>
                                @endforeach
                            </select>
                        </x-field>

                        <x-field label="Opsi Akses Harga & Keuangan" for="tampilkan_harga">
                            <div class="pt-2">
                                <label class="kotak-centang d-inline-flex align-items-center gap-2">
                                    <input type="checkbox" id="tampilkan_harga" name="tampilkan_harga" value="1" @checked($tampilkanHarga)>
                                    <span><strong>Sertakan Kolom Harga, Nilai & Potongan (ADM 5% & Investor 15%)</strong></span>
                                </label>
                                <p class="teks-pudar teks-kecil mt-1">Hilangkan centang jika berkas Excel ini ditujukan untuk personil lapangan yang tidak berwenang melihat tarif kontrak.</p>
                            </div>
                        </x-field>
                    </div>

                    <div class="alert alert--sukses">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong>Kriteria Rekapitulasi Resmi:</strong>
                            <p class="mb-0">Hanya laporan realisasi berstatus <strong>Disetujui</strong> yang akan dimasukkan ke dalam rekapan (README bab 11 no. 5). Saat ini terdapat <strong>{{ $jumlahSiapEkspor }} baris pekerjaan disetujui</strong> pada filter yang dipilih.</p>
                        </div>
                    </div>

                    <div class="bilah-aksi gap-2 d-flex flex-wrap align-items-center">
                        <button type="submit" name="format" value="excel" class="tombol tombol--utama">
                            <i class="bi bi-file-earmark-excel me-1"></i> Generate & Unduh Excel (.xlsx)
                        </button>
                        <button type="submit" name="format" value="pdf" class="tombol tombol--bahaya">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Generate & Unduh PDF (.pdf)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Penjelasan Format Kolom Perusahaan --}}
        <div class="kartu">
            <div class="kartu__kepala">
                <h3 class="kartu__judul"><i class="bi bi-table teks-utama"></i> Struktur Kolom Ekspor Resmi</h3>
            </div>
            <div class="kartu__badan">
                <p class="teks-pudar teks-kecil mb-3">Kolom berkas Excel disusun konsisten sesuai standar PT. Panlibata Andalas Bertuah dilengkapi penanggung jawab & pembagian keuangan:</p>
                <div class="tabel-bungkus">
                    <table class="tabel teks-kecil">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kolom</th>
                                <th>Keterangan Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>1</td><td class="teks-tebal">TGL</td><td>Tanggal pelaksanaan pengerjaan petak</td></tr>
                            <tr><td>2</td><td class="teks-tebal">SPK</td><td>Nomor SPK rujukan yang disetujui</td></tr>
                            <tr><td>3</td><td class="teks-tebal">TIM</td><td>Nama regu operasional lapangan</td></tr>
                            <tr><td>4</td><td class="teks-tebal">PILOT</td><td>Nama pilot penerbang drone</td></tr>
                            <tr><td>5</td><td class="teks-tebal">HELPER</td><td>Nama helper / pembantu umum</td></tr>
                            <tr><td>6</td><td class="teks-tebal">LUASAN SPK</td><td>Target luasan petak dari detail SPK (Ha)</td></tr>
                            <tr><td>7</td><td class="teks-tebal">Ha</td><td>Satuan luasan target</td></tr>
                            <tr><td>8</td><td class="teks-tebal">PEKERJAAN</td><td>Jenis pengerjaan (Penyemprotan / Penebaran)</td></tr>
                            <tr><td>9</td><td class="teks-tebal">PETAK</td><td>Kode blok petak lahan</td></tr>
                            <tr><td>10</td><td class="teks-tebal">LUAS</td><td>Luas aktual yang terselesaikan (Ha)</td></tr>
                            <tr><td>11</td><td class="teks-tebal">Ha</td><td>Satuan luasan aktual</td></tr>
                            <tr><td>12</td><td class="teks-tebal">HARGA *</td><td>Tarif resmi kontrak per hektar</td></tr>
                            <tr><td>13</td><td class="teks-tebal">TOTAL *</td><td>Nilai kotor (LUAS * HARGA)</td></tr>
                            <tr><td>14</td><td class="teks-tebal">CHECK</td><td>Status verifikasi akhir (Closing / Selesai)</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert--peringatan mt-3 py-2 px-3 teks-kecil">
                    <i class="bi bi-info-circle me-1"></i>
                    <span>* Di bawah tabel ekspor, otomatis terhitung pembagian keuangan: <strong>ADM Kantor 5%</strong>, <strong>Bagi Hasil Investor 15%</strong>, dan <strong>Sisa Operasional 80%</strong>. Kolom harga dapat dinonaktifkan jika dibutuhkan.</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Riwayat Ekspor (FR-25) --}}
    <div class="kartu">
        <div class="kartu__kepala">
            <h3 class="kartu__judul"><i class="bi bi-clock-history teks-utama"></i> Riwayat Ekspor Laporan (Audit Trail)</h3>
        </div>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Berkas</th>
                        <th>Periode</th>
                        <th>Diekspor Oleh</th>
                        <th>Waktu Ekspor</th>
                        <th class="angka">Jumlah Baris</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayatEkspor as $r)
                        <tr>
                            <td class="teks-pudar">{{ $riwayatEkspor->firstItem() + $loop->index }}</td>
                            <td class="teks-tebal">
                                <i class="bi bi-file-earmark-excel teks-sukses me-1"></i>
                                {{ $r->nama_berkas }}
                            </td>
                            <td>{{ Carbon\Carbon::create($r->periode_tahun, $r->periode_bulan, 1)->translatedFormat('F Y') }}</td>
                            <td>{{ $r->pengekspor?->nama_lengkap ?? '—' }}</td>
                            <td>{{ $r->waktu_ekspor ? $r->waktu_ekspor->translatedFormat('d M Y, H:i') : '-' }} WIB</td>
                            <td class="angka teks-tebal">{{ number_format($r->jumlah_baris, 0, ',', '.') }} baris</td>
                            <td class="aksi">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('ekspor.unduh', $r) }}" class="tombol tombol--garis tombol--kecil" title="Unduh Berkas Excel (.xlsx)">
                                        <i class="bi bi-file-earmark-excel teks-sukses me-1"></i> Excel
                                    </a>
                                    <a href="{{ route('ekspor.unduh-pdf', $r) }}" class="tombol tombol--garis tombol--kecil" title="Unduh Berkas PDF (.pdf)">
                                        <i class="bi bi-file-earmark-pdf teks-bahaya me-1"></i> PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-kosong ikon="bi-file-earmark-excel" judul="Belum ada riwayat ekspor Excel">
                                    Pilih periode di atas lalu klik "Generate & Unduh Excel" untuk membuat rekapitulasi pertama.
                                </x-kosong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $riwayatEkspor->links() }}
    </div>
@endsection
