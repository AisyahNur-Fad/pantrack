@extends('layouts.app')

@section('judul', 'Keuangan & Bon Unit Drone')

@section('konten')
    <x-halaman-kepala
        judul="Keuangan & Bon Operasional Unit Drone"
        deskripsi="Pencatatan kas operasional, suku cadang (sparepart), konsumsi ransum, dan kasbon kru lapangan untuk penyusunan laporan Excel 7-Sheet resmi."
        ikon="bi-wallet2">
        <div class="flex gap-2">
            @if ($isTerkunci)
                <button type="button" class="tombol tombol--pudar" disabled title="Periode pembukuan telah ditutup. Buka kembali periode untuk mencatat pengeluaran.">
                    <i class="bi bi-lock"></i> Catat Pengeluaran Unit
                </button>
                <button type="button" class="tombol tombol--pudar" disabled title="Periode pembukuan telah ditutup. Kasbon yang ada tetap terhitung, namun penambahan baru dikunci.">
                    <i class="bi bi-lock"></i> Catat Kasbon Kru
                </button>
                <button type="button" class="tombol tombol--utama" onclick="document.getElementById('dialog-buka-periode').showModal()">
                    <i class="bi bi-unlock"></i> Buka Periode
                </button>
            @else
                <a href="{{ route('keuangan.pengeluaran.create', ['tim_id' => $timId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--utama">
                    <i class="bi bi-plus-circle"></i> Catat Pengeluaran Unit
                </a>
                <a href="{{ route('keuangan.kasbon.create', ['tim_id' => $timId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--garis">
                    <i class="bi bi-person-dash"></i> Catat Kasbon Kru
                </a>
                <button type="button" class="tombol tombol--garis teks-bahaya" onclick="document.getElementById('dialog-tutup-periode').showModal()">
                    <i class="bi bi-lock"></i> Tutup Periode
                </button>
            @endif
        </div>
    </x-halaman-kepala>

    {{-- Banner Status Penutupan Periode (Closing) --}}
    @if ($isTerkunci)
        <div class="kartu mb-4" style="border-left: 5px solid #dc2626; background: linear-gradient(to right, #fff5f5, #ffffff);">
            <div class="kartu__badan p-3 flex-antara item-tengah flex-wrap gap-3">
                <div class="flex item-tengah gap-3">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div>
                        <div class="flex item-tengah gap-2 mb-1 flex-wrap">
                            <strong style="font-size: 16px; color: #991b1b;">Periode Pembukuan Ditutup (Terkunci)</strong>
                            <span class="lencana lencana--merah">
                                <i class="bi bi-lock"></i> CLOSED
                            </span>
                        </div>
                        <p class="teks-kecil mb-0" style="color: #7f1d1d;">
                            Ditutup oleh <strong>{{ $penutupanPeriode?->penutup?->nama_lengkap ?? 'Admin' }}</strong>
                            pada {{ $penutupanPeriode?->waktu_tutup ? $penutupanPeriode->waktu_tutup->translatedFormat('d M Y, H:i') : '-' }}.
                            @if ($penutupanPeriode?->catatan)
                                <span class="teks-pudar">({{ $penutupanPeriode->catatan }})</span>
                            @endif
                        </p>
                        <small class="d-block mt-1" style="color: #991b1b;">
                            <i class="bi bi-info-circle-fill"></i> <strong>Ketentuan Periode Tutup:</strong> Kasbon personil yang sudah tercatat <em>tetap terhitung penuh</em> dalam penjumlahan pembukuan & slip gaji. Namun penambahan kasbon atau pengeluaran baru dilarang.
                        </small>
                    </div>
                </div>
                <div>
                    <button type="button" class="tombol tombol--utama" onclick="document.getElementById('dialog-buka-periode').showModal()">
                        <i class="bi bi-unlock"></i> Buka Kembali Periode
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="kartu mb-4" style="border-left: 5px solid #16a34a; background: linear-gradient(to right, #f0fdf4, #ffffff);">
            <div class="kartu__badan p-3 flex-antara item-tengah flex-wrap gap-3">
                <div class="flex item-tengah gap-3">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                        <i class="bi bi-unlock-fill"></i>
                    </div>
                    <div>
                        <div class="flex item-tengah gap-2 mb-1 flex-wrap">
                            <strong style="font-size: 16px; color: #166534;">Periode Pembukuan Aktif (Terbuka)</strong>
                            <span class="lencana lencana--hijau">
                                <i class="bi bi-unlock"></i> OPEN
                            </span>
                        </div>
                        <p class="teks-kecil mb-0" style="color: #14532d;">
                            Pencatatan pengeluaran kas operasional dan kasbon kru untuk bulan <strong>{{ Carbon\Carbon::create(2026, $bulan, 1)->translatedFormat('F') }} {{ $tahun }}</strong> dapat dilakukan secara normal.
                            @if ($penutupanPeriode && $penutupanPeriode->status === 'terbuka' && $penutupanPeriode->dibuka_oleh)
                                <br><span class="teks-pudar teks-kecil">Terakhir dibuka kembali oleh <strong>{{ $penutupanPeriode->pembuka?->nama_lengkap ?? 'Admin' }}</strong> pada {{ $penutupanPeriode->waktu_buka?->translatedFormat('d M Y, H:i') }} (Alasan: "{{ $penutupanPeriode->alasan_buka }}")</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div>
                    <button type="button" class="tombol tombol--garis teks-bahaya" onclick="document.getElementById('dialog-tutup-periode').showModal()">
                        <i class="bi bi-lock"></i> Tutup Periode (Closing)
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Filter Periode & Unit Tim --}}
    <div class="kartu mb-4">
        <div class="kartu__badan">
            <form method="GET" action="{{ route('keuangan.index') }}" class="grid-form align-items-end">
                <x-field label="Unit Tim Drone" for="tim_id">
                    <select id="tim_id" name="tim_id" class="masukan" onchange="this.form.submit()">
                        @foreach ($daftarTim as $t)
                            <option value="{{ $t->id }}" @selected((string) $timId === (string) $t->id)>
                                {{ $t->nama_tim }} &bull; Unit: {{ $t->drone?->nomor_drone ?? '-' }}{{ !empty($t->drone?->nama_drone) ? ' · '.$t->drone->nama_drone : '' }} ({{ $t->drone?->tipe_drone ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </x-field>

                <x-field label="Periode Bulan" for="bulan">
                    <select id="bulan" name="bulan" class="masukan" onchange="this.form.submit()">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($bulan === $m)>
                                Bulan {{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </x-field>

                <x-field label="Periode Tahun" for="tahun">
                    <select id="tahun" name="tahun" class="masukan" onchange="this.form.submit()">
                        @for ($y = now()->year - 1; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </x-field>

                <div class="form-grup">
                    <button type="submit" class="tombol tombol--garis w-100">
                        <i class="bi bi-filter"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Peringatan Pencegahan Dobel Catat BBM --}}
    <div class="alert alert--sukses mb-4">
        <i class="bi bi-shield-check"></i>
        <div>
            <strong>Integrasi Biaya BBM Solar Terverifikasi:</strong>
            <p class="mb-0">
                Data pembelian BBM Solar dari modul <strong>Monitoring BBM</strong> (total <strong>Rp {{ number_format($totalBbmOtomatis, 0, ',', '.') }}</strong> dari {{ $daftarBbm->count() }} transaksi terverifikasi) secara otomatis ditarik ke dalam laporan penutupan buku. Anda <em>tidak perlu mencatat ulang</em> pembelian solar tersebut di buku pengeluaran kas agar tidak terjadi pencatatan ganda.
            </p>
        </div>
    </div>

    {{-- Kartu Ringkasan Biaya Unit Periode Ini --}}
    <div class="grid-form mb-4">
        <div class="kartu kartu--pudar p-3">
            <span class="teks-pudar teks-kecil d-block">Operasional Lapangan</span>
            <strong class="teks-besar teks-utama">Rp {{ number_format($totalOperasional > 0 ? $totalOperasional : $totalBbmOtomatis, 0, ',', '.') }}</strong>
            <small class="teks-pudar d-block mt-1">Solar BBM, belanja logistik, antar drone</small>
        </div>

        <div class="kartu kartu--pudar p-3">
            <span class="teks-pudar teks-kecil d-block">Suku Cadang / Sparepart</span>
            <strong class="teks-besar" style="color: #0284c7;">Rp {{ number_format($totalSparepart, 0, ',', '.') }}</strong>
            <small class="teks-pudar d-block mt-1">Pembelian sparepart resmi (No. PI)</small>
        </div>

        <div class="kartu kartu--pudar p-3">
            <span class="teks-pudar teks-kecil d-block">Total Kasbon Personil</span>
            <strong class="teks-besar" style="color: #ea580c;">Rp {{ number_format($totalKasbonKru, 0, ',', '.') }}</strong>
            <small class="teks-pudar d-block mt-1">Potongan kasbon pilot, helper, & pelangsir</small>
        </div>

        <div class="kartu kartu--pudar p-3">
            <span class="teks-pudar teks-kecil d-block">Bon Ransum & Kantin</span>
            <strong class="teks-besar" style="color: #16a34a;">Rp {{ number_format($totalRansumKantin, 0, ',', '.') }}</strong>
            <small class="teks-pudar d-block mt-1">Katering tim & bon kantin distrik</small>
        </div>

        <div class="kartu kartu--pudar p-3">
            <span class="teks-pudar teks-kecil d-block">Rental Mobil & Langsir</span>
            <strong class="teks-besar" style="color: #7c3aed;">Rp {{ number_format($totalRentalMobil + $totalUpahLangsir, 0, ',', '.') }}</strong>
            <small class="teks-pudar d-block mt-1">Sewa armada mobil & upah langsir</small>
        </div>

        <div class="kartu kartu--pudar p-3">
            <span class="teks-pudar teks-kecil d-block">Bon Keperluan Pribadi</span>
            <strong class="teks-besar" style="color: #dc2626;">Rp {{ number_format($totalBonPribadi, 0, ',', '.') }}</strong>
            <small class="teks-pudar d-block mt-1">Bon penarikan pribadi / lainnya</small>
        </div>
    </div>

    {{-- Tabel 1: Rincian Pengeluaran Operasional Unit --}}
    <div class="kartu mb-4">
        <div class="kartu__kepala flex-antara item-tengah">
            <h3 class="kartu__judul">
                <i class="bi bi-receipt teks-utama"></i> Buku Pengeluaran Operasional Unit ({{ $daftarPengeluaran->count() }} Transaksi)
            </h3>
            @if ($isTerkunci)
                <span class="lencana lencana--merah" title="Periode ditutup: Penambahan pengeluaran dinonaktifkan">
                    <i class="bi bi-lock"></i> Periode Ditutup
                </span>
            @else
                <a href="{{ route('keuangan.pengeluaran.create', ['tim_id' => $timId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--garis tombol--kecil">
                    <i class="bi bi-plus"></i> Tambah Pengeluaran
                </a>
            @endif
        </div>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan / Rincian</th>
                        <th>No. Bukti / PI</th>
                        <th class="angka">Nominal (Rp)</th>
                        <th>Nota / Struk</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarPengeluaran as $p)
                        <tr>
                            <td class="teks-pudar">{{ $loop->iteration }}</td>
                            <td>{{ $p->tanggal_transaksi ? $p->tanggal_transaksi->translatedFormat('d M Y') : '-' }}</td>
                            <td>
                                <x-lencana :warna="match($p->kategori_biaya) {
                                    'operasional_lapangan' => 'biru',
                                    'sparepart' => 'emas',
                                    'ransum_kantin' => 'hijau',
                                    'rental_mobil', 'upah_langsir' => 'ungu',
                                    'bon_pribadi' => 'merah',
                                    default => 'abu',
                                }" :teks="\App\Models\PengeluaranOperasionalUnit::kategoriBiaya()[$p->kategori_biaya] ?? $p->kategori_biaya" />
                            </td>
                            <td class="teks-tebal">{{ $p->keterangan }}</td>
                            <td>{{ $p->nomor_bukti_pi ?: '—' }}</td>
                            <td class="angka teks-tebal">Rp {{ number_format($p->nominal, 0, ',', '.') }}</td>
                            <td>
                                @if ($p->lampiran_nota)
                                    <a href="{{ Storage::url($p->lampiran_nota) }}" target="_blank" class="tombol tombol--garis tombol--kecil">
                                        <i class="bi bi-paperclip"></i> Lihat Nota
                                    </a>
                                @else
                                    <span class="teks-pudar">—</span>
                                @endif
                            </td>
                            <td class="aksi">
                                @if ($isTerkunci)
                                    <span class="teks-pudar teks-kecil"><i class="bi bi-lock"></i> Terkunci</span>
                                @else
                                    <div class="flex gap-1 justify-content-end">
                                        <a href="{{ route('keuangan.pengeluaran.edit', $p) }}" class="tombol tombol--garis tombol--kecil" title="Ubah">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('keuangan.pengeluaran.destroy', $p) }}" onsubmit="return confirm('Hapus catatan pengeluaran ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol tombol--garis tombol--kecil teks-bahaya" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-kosong ikon="bi-receipt" judul="Belum ada catatan pengeluaran operasional">
                                    Klik tombol "Tambah Pengeluaran" di atas untuk mencatat nota belanja, solar, sparepart, atau sewa unit.
                                </x-kosong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabel 2: Transaksi BBM Solar Terverifikasi (Sinkron Otomatis dari Modul BBM) --}}
    <div class="kartu mb-4">
        <div class="kartu__kepala flex-antara item-tengah">
            <h3 class="kartu__judul">
                <i class="bi bi-fuel-pump-fill" style="color: #059669;"></i> Transaksi BBM Solar Terverifikasi ({{ $daftarBbm->count() }} Transaksi &bull; Rp {{ number_format($totalBbmOtomatis, 0, ',', '.') }})
            </h3>
            <a href="{{ route('bbm.index') }}" class="tautan-aksi">
                <i class="bi bi-box-arrow-up-right"></i> Buka Modul BBM
            </a>
        </div>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>No. Transaksi</th>
                        <th>Jenis BBM</th>
                        <th class="angka">Liter</th>
                        <th class="angka">Harga / Liter</th>
                        <th class="angka">Total Biaya</th>
                        <th>No. Struk</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarBbm as $b)
                        <tr>
                            <td class="teks-pudar">{{ $loop->iteration }}</td>
                            <td>{{ $b->tanggal_transaksi ? $b->tanggal_transaksi->translatedFormat('d M Y') : '-' }}</td>
                            <td class="teks-tebal">{{ $b->nomor_transaksi }}</td>
                            <td>{{ $b->jenisBbm?->nama_bbm ?? 'Solar' }}</td>
                            <td class="angka">{{ number_format($b->jumlah_liter, 2, ',', '.') }} L</td>
                            <td class="angka">Rp {{ number_format($b->harga_per_liter, 0, ',', '.') }}</td>
                            <td class="angka teks-tebal" style="color: #059669;">Rp {{ number_format($b->total_biaya, 0, ',', '.') }}</td>
                            <td>{{ $b->nomor_struk_nota ?: '—' }}</td>
                            <td>
                                <x-lencana warna="hijau" teks="Diverifikasi" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-kosong ikon="bi-fuel-pump" judul="Belum ada transaksi BBM terverifikasi pada periode ini">
                                    Transaksi penerimaan BBM solar dengan harga dan struk akan otomatis tampil di sini setelah diverifikasi Admin.
                                </x-kosong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabel 3: Rincian Kasbon Personil Tim --}}
    <div class="kartu">
        <div class="kartu__kepala flex-antara item-tengah">
            <h3 class="kartu__judul">
                <i class="bi bi-person-dash teks-utama"></i> Buku Kasbon Personil Tim ({{ $daftarKasbon->count() }} Penarikan &bull; Rp {{ number_format($totalKasbonKru, 0, ',', '.') }})
            </h3>
            @if ($isTerkunci)
                <span class="lencana lencana--merah" title="Periode ditutup: Penambahan kasbon baru dikunci, kasbon yang ada tetap terhitung otomatis">
                    <i class="bi bi-lock"></i> Penambahan Kasbon Dikunci
                </span>
            @else
                <a href="{{ route('keuangan.kasbon.create', ['tim_id' => $timId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="tombol tombol--garis tombol--kecil">
                    <i class="bi bi-plus"></i> Tambah Kasbon Kru
                </a>
            @endif
        </div>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Personil</th>
                        <th>Peran / Jabatan</th>
                        <th class="angka">Jumlah Kasbon</th>
                        <th>Keterangan</th>
                        <th>Status Pelunasan</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarKasbon as $k)
                        <tr>
                            <td class="teks-pudar">{{ $loop->iteration }}</td>
                            <td>{{ $k->tanggal_kasbon ? $k->tanggal_kasbon->translatedFormat('d M Y') : '-' }}</td>
                            <td class="teks-tebal">{{ $k->nama_personil }}</td>
                            <td>
                                <x-lencana :warna="match($k->peran_kru) {
                                    'pilot' => 'hijau',
                                    'helper' => 'biru',
                                    'pelangsir' => 'emas',
                                    default => 'abu',
                                }" :teks="\App\Models\KasbonPersonil::peranKru()[$k->peran_kru] ?? $k->peran_kru" />
                            </td>
                            <td class="angka teks-tebal" style="color: #ea580c;">Rp {{ number_format($k->jumlah_kasbon, 0, ',', '.') }}</td>
                            <td>{{ $k->keterangan ?: '—' }}</td>
                            <td>
                                @if ($k->status_lunas)
                                    <x-lencana warna="hijau" teks="Lunas / Terpotong" />
                                @else
                                    <x-lencana warna="kuning" teks="Belum Terpotong" />
                                @endif
                            </td>
                            <td class="aksi">
                                @if ($isTerkunci)
                                    <span class="teks-pudar teks-kecil"><i class="bi bi-lock"></i> Terkunci (Tetap Masuk Hitungan)</span>
                                @else
                                    <div class="flex gap-1 justify-content-end align-items-center">
                                        <form method="POST" action="{{ route('keuangan.kasbon.toggle-lunas', $k) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="tombol tombol--garis tombol--kecil" title="{{ $k->status_lunas ? 'Tandai Belum Terpotong' : 'Tandai Lunas / Terpotong Gaji' }}">
                                                <i class="bi {{ $k->status_lunas ? 'bi-arrow-counterclockwise teks-pudar' : 'bi-check-circle teks-utama' }}"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('keuangan.kasbon.edit', $k) }}" class="tombol tombol--garis tombol--kecil" title="Ubah">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('keuangan.kasbon.destroy', $k) }}" onsubmit="return confirm('Hapus catatan kasbon ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tombol tombol--garis tombol--kecil teks-bahaya" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-kosong ikon="bi-person-dash" judul="Belum ada catatan kasbon kru pada periode ini">
                                    Klik tombol "Tambah Kasbon Kru" di atas jika ada penarikan kasbon oleh pilot atau helper.
                                </x-kosong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Dialog Tutup Periode (Closing) --}}
    <dialog id="dialog-tutup-periode" class="dialog" style="max-width: 500px;">
        <form method="POST" action="{{ route('keuangan.periode.tutup') }}">
            @csrf
            <input type="hidden" name="tim_id" value="{{ $timId }}">
            <input type="hidden" name="periode_bulan" value="{{ $bulan }}">
            <input type="hidden" name="periode_tahun" value="{{ $tahun }}">

            <div class="dialog__badan" style="text-align: left;">
                <div class="dialog__ikon" style="color: #dc2626; background: #fee2e2;">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <h2 class="dialog__judul text-center">Tutup Periode Pembukuan</h2>
                <p class="dialog__pesan text-center">
                    Tutup dan kunci pembukuan <strong>{{ $timTerpilih?->nama_tim }}</strong> untuk bulan <strong>{{ Carbon\Carbon::create(2026, $bulan, 1)->translatedFormat('F') }} {{ $tahun }}</strong>.
                </p>

                <div class="alert alert--sukses mb-3" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534; font-size: 12.5px;">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <strong>Perhitungan Kasbon Aman:</strong>
                        <p class="mb-0">Semua kasbon yang telah dicatat tetap dihitung dalam laporan keuangan & slip gaji. Namun penambahan kasbon atau pengeluaran baru akan dicegah.</p>
                    </div>
                </div>

                <x-field label="Catatan Penutupan (Opsional)" for="catatan_tutup">
                    <textarea id="catatan_tutup" name="catatan" class="masukan" rows="2" placeholder="Contoh: Closing bulanan selesai diverifikasi supervisor."></textarea>
                </x-field>
            </div>

            <div class="dialog__aksi">
                <button type="button" class="tombol tombol--garis" onclick="document.getElementById('dialog-tutup-periode').close()">
                    Batal
                </button>
                <button type="submit" class="tombol tombol--bahaya">
                    <i class="bi bi-lock"></i> Ya, Tutup Periode
                </button>
            </div>
        </form>
    </dialog>

    {{-- Dialog Buka Kembali Periode --}}
    <dialog id="dialog-buka-periode" class="dialog" style="max-width: 500px;">
        <form method="POST" action="{{ route('keuangan.periode.buka') }}">
            @csrf
            <input type="hidden" name="tim_id" value="{{ $timId }}">
            <input type="hidden" name="periode_bulan" value="{{ $bulan }}">
            <input type="hidden" name="periode_tahun" value="{{ $tahun }}">

            <div class="dialog__badan" style="text-align: left;">
                <div class="dialog__ikon" style="color: #16a34a; background: #dcfce7;">
                    <i class="bi bi-unlock-fill"></i>
                </div>
                <h2 class="dialog__judul text-center">Buka Kembali Periode</h2>
                <p class="dialog__pesan text-center">
                    Buka kembali pembukuan <strong>{{ $timTerpilih?->nama_tim }}</strong> untuk bulan <strong>{{ Carbon\Carbon::create(2026, $bulan, 1)->translatedFormat('F') }} {{ $tahun }}</strong> agar penyesuaian atau penambahan transaksi dapat dilakukan.
                </p>

                <x-field label="Alasan Pembukaan Kembali (Wajib Diisi)" for="alasan_buka">
                    <textarea id="alasan_buka" name="alasan_buka" class="masukan" rows="3" required placeholder="Contoh: Penyesuaian nota sparepart tertinggal atau revisi bon kasbon kru."></textarea>
                </x-field>
            </div>

            <div class="dialog__aksi">
                <button type="button" class="tombol tombol--garis" onclick="document.getElementById('dialog-buka-periode').close()">
                    Batal
                </button>
                <button type="submit" class="tombol tombol--utama">
                    <i class="bi bi-unlock"></i> Konfirmasi Buka Periode
                </button>
            </div>
        </form>
    </dialog>
@endsection
