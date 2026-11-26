@extends('layouts.app')

@section('judul', 'Monitoring & Transaksi BBM')

@section('konten')
    @php
        $pengguna = auth()->user();
        $bolehInputBbm = $pengguna->adalahAdmin() || $pengguna->adalahPekerjaLapangan();
        $bolehLihatBiaya = $pengguna->adalahAdmin() || $pengguna->adalahAtasan();
        $deskripsiHalaman = match (true) {
            $pengguna->adalahAdmin() => 'Verifikasi bukti nota, kelola stok awal, dan pantau saldo BBM tiap tim.',
            $pengguna->adalahPekerjaLapangan() => 'Pantau sisa bahan bakar tim Anda dan catat pembelian atau pemakaian BBM.',
            $pengguna->adalahKepalaLapangan() => 'Pantau kesiapan stok bahan bakar setiap tim di lapangan (mode pantau).',
            default => 'Ringkasan stok, pemakaian, dan biaya BBM seluruh tim (mode pantau).',
        };
    @endphp

    <x-halaman-kepala
        judul="Monitoring & Transaksi BBM"
        :deskripsi="$deskripsiHalaman"
        ikon="bi-fuel-pump">
        @if ($bolehInputBbm)
            <a href="{{ route('bbm.create', $timId ? ['tim_id' => $timId] : []) }}" class="tombol tombol--utama">
                <i class="bi bi-plus-lg"></i> Catat Transaksi BBM
            </a>
        @else
            <span class="lencana lencana--abu" style="padding: 8px 14px;">
                <i class="bi bi-eye"></i> Mode Pantau
            </span>
        @endif
    </x-halaman-kepala>

    {{-- Ringkasan BBM: Adaptif sesuai peran & jumlah tim --}}
    @if ($ringkasanTim->count() === 1)
        @php $itemTunggal = $ringkasanTim->first(); @endphp
        <div class="kartu kartu--statistik mb-4 bbm-hero-tunggal">
            <div class="flex-antara item-tengah mb-2" style="flex-wrap: wrap; gap: 10px;">
                <div>
                    <div class="teks-kecil teks-pudar">Sisa Saldo Resmi Tim Anda</div>
                    <div class="teks-tebal teks-utama" style="font-size: 1.1rem;">{{ $itemTunggal['tim']->nama_tim }}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    @if ($tabCounts['perbaikan'] > 0)
                        <a href="{{ route('bbm.index', ['tim_id' => $itemTunggal['tim']->id, 'status' => 'perlu_perbaikan']) }}"
                           class="lencana lencana--merah" style="text-decoration: none;">
                            <i class="bi bi-exclamation-circle"></i> {{ $tabCounts['perbaikan'] }} perlu perbaikan <i class="bi bi-arrow-right-short"></i>
                        </a>
                    @endif
                    @if ($itemTunggal['menunggu'] > 0)
                        <a href="{{ route('bbm.index', ['tim_id' => $itemTunggal['tim']->id, 'status' => 'menunggu_pemeriksaan']) }}"
                           class="lencana lencana--emas" style="text-decoration: none;">
                            <i class="bi bi-clock"></i> {{ $itemTunggal['menunggu'] }} menunggu verifikasi
                        </a>
                    @endif
                    <span class="lencana {{ $itemTunggal['saldo'] < 0 ? 'lencana--merah' : 'lencana--hijau' }}">
                        {{ $itemTunggal['saldo'] < 0 ? 'Defisit Stok' : 'Saldo Aman' }}
                    </span>
                </div>
            </div>

            <div class="flex-antara item-tengah" style="flex-wrap: wrap; gap: 16px;">
                <div>
                    <div class="angka-besar {{ $itemTunggal['saldo'] < 0 ? 'teks-bahaya' : 'teks-utama' }}">
                        {{ number_format($itemTunggal['saldo'], 2, ',', '.') }} <span class="teks-kecil teks-biasa">Liter</span>
                    </div>
                    @if (!empty($itemTunggal['saldo_per_jenis']))
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;">
                            @foreach ($itemTunggal['saldo_per_jenis'] as $spj)
                                <span class="bbm-jenis-pill">
                                    <span class="teks-pudar">{{ $spj['nama_bbm'] }}:</span>
                                    <strong class="{{ $spj['saldo'] < 0 ? 'teks-bahaya' : 'teks-utama' }}">{{ number_format($spj['saldo'], 1, ',', '.') }} L</strong>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="teks-kecil" style="display: flex; gap: 18px; flex-wrap: wrap;">
                    <a href="{{ route('bbm.index', ['tim_id' => $itemTunggal['tim']->id, 'jenis_transaksi' => 'masuk']) }}" class="tautan-tabel">
                        Masuk bln ini: <strong class="teks-sukses">+{{ number_format($itemTunggal['penerimaan_bulan_ini'], 1, ',', '.') }} L</strong>
                    </a>
                    <a href="{{ route('bbm.index', ['tim_id' => $itemTunggal['tim']->id, 'jenis_transaksi' => 'penggunaan']) }}" class="tautan-tabel">
                        Pakai bln ini: <strong class="teks-bahaya">-{{ number_format($itemTunggal['penggunaan_bulan_ini'], 1, ',', '.') }} L</strong>
                    </a>
                </div>
            </div>
        </div>
    @elseif ($ringkasanTim->count() > 1)
        @php
            $filterMenungguAktif = $status === 'menunggu_pemeriksaan';
            $filterMasukAktif = $jenisTransaksi === 'masuk' || $jenisTransaksi === 'penerimaan';
            $filterKeluarAktif = $jenisTransaksi === 'penggunaan';
            $timAktifFilter = $timId ? $ringkasanTim->first(fn ($i) => (string) $i['tim']->id === (string) $timId) : null;
        @endphp

        {{-- 3 Kartu Aksi Cepat Minimalis --}}
        <div class="grid-statistik mb-3">
            <a href="{{ $filterMenungguAktif ? route('bbm.index') : route('bbm.index', ['status' => 'menunggu_pemeriksaan']) }}"
               class="kartu kartu--statistik bbm-kartu-aksi {{ $filterMenungguAktif ? 'bbm-kartu-aksi--aktif' : '' }}">
                <div class="flex-antara item-tengah mb-2">
                    <span class="teks-kecil teks-pudar teks-tebal">Menunggu Verifikasi</span>
                    <span class="lencana {{ $ringkasanGlobal['total_menunggu'] > 0 ? 'lencana--emas' : 'lencana--hijau' }}">
                        {{ $ringkasanGlobal['total_menunggu'] > 0 ? 'Perlu Cek' : 'Aman' }}
                    </span>
                </div>
                <div class="angka-besar {{ $ringkasanGlobal['total_menunggu'] > 0 ? '' : 'teks-utama' }}" style="{{ $ringkasanGlobal['total_menunggu'] > 0 ? 'color: var(--peringatan);' : '' }}">
                    {{ $ringkasanGlobal['total_menunggu'] }} <span class="teks-kecil teks-biasa">Transaksi</span>
                </div>
                <div class="teks-kecil teks-pudar mt-2 flex-antara">
                    <span>{{ $filterMenungguAktif ? 'Sedang difilter · Klik untuk tampilkan semua' : 'Klik untuk periksa antrean' }}</span>
                    <i class="bi {{ $filterMenungguAktif ? 'bi-x-circle' : 'bi-arrow-right' }}"></i>
                </div>
            </a>

            <div class="kartu kartu--statistik">
                <div class="flex-antara item-tengah mb-2">
                    <span class="teks-kecil teks-pudar teks-tebal">Aktivitas {{ now()->translatedFormat('F Y') }}</span>
                    @if ($bolehLihatBiaya && $ringkasanGlobal['total_biaya_bulan_ini'] > 0)
                        <span class="lencana lencana--biru">Rp {{ number_format($ringkasanGlobal['total_biaya_bulan_ini'], 0, ',', '.') }}</span>
                    @else
                        <span class="lencana lencana--biru">Bulan Ini</span>
                    @endif
                </div>
                <div style="display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ $filterMasukAktif ? route('bbm.index') : route('bbm.index', ['jenis_transaksi' => 'masuk', 'bulan' => now()->month, 'tahun' => now()->year]) }}"
                       class="bbm-pill-mutasi {{ $filterMasukAktif ? 'bbm-pill-mutasi--aktif' : '' }}">
                        <span class="teks-kecil teks-pudar">Masuk:</span>
                        <strong class="teks-sukses" style="font-size: 1.1rem;">+{{ number_format($ringkasanGlobal['total_masuk_bulan_ini'], 1, ',', '.') }} L</strong>
                    </a>
                    <a href="{{ $filterKeluarAktif ? route('bbm.index') : route('bbm.index', ['jenis_transaksi' => 'penggunaan', 'bulan' => now()->month, 'tahun' => now()->year]) }}"
                       class="bbm-pill-mutasi {{ $filterKeluarAktif ? 'bbm-pill-mutasi--aktif' : '' }}">
                        <span class="teks-kecil teks-pudar">Pakai:</span>
                        <strong class="teks-bahaya" style="font-size: 1.1rem;">-{{ number_format($ringkasanGlobal['total_keluar_bulan_ini'], 1, ',', '.') }} L</strong>
                    </a>
                </div>
                <div class="teks-kecil teks-pudar mt-2">
                    Klik angka Masuk / Pakai untuk memfilter tabel
                </div>
            </div>

            <button type="button"
                    class="kartu kartu--statistik bbm-kartu-aksi"
                    style="text-align: left; width: 100%; cursor: pointer; background: var(--permukaan, #fff);"
                    onclick="bukaModalSemuaTim()">
                <div class="flex-antara item-tengah mb-2">
                    <span class="teks-kecil teks-pudar teks-tebal">Kondisi Stok Tim</span>
                    @if ($ringkasanGlobal['tim_defisit'] > 0)
                        <span class="lencana lencana--merah">{{ $ringkasanGlobal['tim_defisit'] }} Defisit</span>
                    @else
                        <span class="lencana lencana--hijau">{{ $ringkasanGlobal['total_tim'] }} Tim</span>
                    @endif
                </div>
                <div class="angka-besar teks-utama">
                    {{ $ringkasanGlobal['tim_ada_saldo'] }} <span class="teks-kecil teks-biasa">/ {{ $ringkasanGlobal['total_tim'] }} Tim Ada Saldo</span>
                </div>
                <div class="teks-kecil teks-pudar mt-2 flex-antara">
                    <span>
                        @if ($timAktifFilter)
                            Filter aktif: <strong>{{ $timAktifFilter['tim']->nama_tim }}</strong>
                        @else
                            Klik untuk lihat daftar {{ $ringkasanGlobal['total_tim'] }} tim
                        @endif
                    </span>
                    <i class="bi bi-window-stack"></i>
                </div>
            </button>
        </div>

        {{-- Strip Saldo Tim Aktif (hanya tim yang memiliki saldo atau antrean) --}}
        <div class="kartu mb-4" id="panel-saldo-tim">
            <div class="kartu__kepala" style="padding: 12px 20px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h2 class="kartu__judul" style="font-size: 14.5px; margin: 0;">
                        <i class="bi bi-fuel-pump teks-utama"></i> Saldo Tim Aktif
                    </h2>
                    <span class="teks-kecil teks-pudar">Klik <strong>Lihat</strong> untuk pop-up saldo & riwayat tim</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    @if ($timId)
                        <a href="{{ route('bbm.index', array_filter(['jenis_bbm_id' => $jenisBbmId, 'jenis_transaksi' => $jenisTransaksi, 'status' => $status, 'bulan' => $bulan, 'tahun' => $tahun], fn ($v) => $v !== null && $v !== '' && $v !== 0)) }}"
                           class="tombol tombol--garis tombol--kecil">
                            <i class="bi bi-x-lg"></i> Lepas Filter Tim
                        </a>
                    @endif
                    @if ($ringkasanGlobal['total_tim'] > $ringkasanGlobal['tim_beraktivitas'])
                        <button type="button" class="tombol tombol--garis tombol--kecil" onclick="bukaModalSemuaTim()">
                            <i class="bi bi-grid-3x3-gap"></i> Semua {{ $ringkasanGlobal['total_tim'] }} Tim
                        </button>
                    @endif
                </div>
            </div>

            <div class="tabel-bungkus">
                <table class="tabel bbm-tabel-padat" id="tabel-saldo-tim">
                    <thead>
                        <tr>
                            <th>Tim Drone</th>
                            <th>Rincian Jenis BBM</th>
                            <th>Bulan Ini</th>
                            <th class="angka">Saldo Resmi</th>
                            <th>Antrean</th>
                            <th class="aksi">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $adaTimTampil = false; @endphp
                        @foreach ($ringkasanTim as $item)
                            @php
                                $sedangDipilih = (string) $timId === (string) $item['tim']->id;
                                $punyaAktivitas = abs((float) $item['saldo']) > 0
                                    || $item['menunggu'] > 0
                                    || $item['penerimaan_bulan_ini'] > 0
                                    || $item['penggunaan_bulan_ini'] > 0
                                    || $sedangDipilih;
                                $urlTim = route('bbm.index', array_merge(request()->except(['page', 'tim_id']), ['tim_id' => $item['tim']->id]));
                                $urlMenungguTim = route('bbm.index', ['tim_id' => $item['tim']->id, 'status' => 'menunggu_pemeriksaan']);
                                $payloadTim = [
                                    'id' => $item['tim']->id,
                                    'nama_tim' => $item['tim']->nama_tim,
                                    'distrik' => $item['tim']->distrik?->nama_distrik,
                                    'drone' => $item['tim']->drone ? ($item['tim']->drone->nomor_drone . ($item['tim']->drone->nama_drone ? ' · ' . $item['tim']->drone->nama_drone : '')) : null,
                                    'saldo' => (float) $item['saldo'],
                                    'saldo_format' => number_format($item['saldo'], 2, ',', '.') . ' Liter',
                                    'per_jenis' => collect($item['saldo_per_jenis'])->map(fn ($spj) => [
                                        'nama_bbm' => $spj['nama_bbm'],
                                        'saldo' => (float) $spj['saldo'],
                                        'saldo_format' => number_format($spj['saldo'], 1, ',', '.') . ' L',
                                    ])->values(),
                                    'masuk_format' => '+' . number_format($item['penerimaan_bulan_ini'], 1, ',', '.') . ' L',
                                    'keluar_format' => '-' . number_format($item['penggunaan_bulan_ini'], 1, ',', '.') . ' L',
                                    'menunggu' => (int) $item['menunggu'],
                                    'url_detail' => route('bbm.detail-tim', $item['tim']->id),
                                    'url_filter' => $urlTim,
                                    'url_menunggu' => $urlMenungguTim,
                                    'url_catat' => $bolehInputBbm ? route('bbm.create', ['tim_id' => $item['tim']->id]) : null,
                                ];
                            @endphp
                            @if ($punyaAktivitas)
                                @php $adaTimTampil = true; @endphp
                                <tr class="bbm-baris-tim {{ $sedangDipilih ? 'bbm-baris-tim--aktif' : '' }}"
                                    onclick='bukaModalDetailTim(@json($payloadTim))'>
                                    <td>
                                        <span class="teks-tebal tautan-tabel">{{ $item['tim']->nama_tim }}</span>
                                        @if ($sedangDipilih)
                                            <span class="lencana lencana--hijau" style="margin-left: 6px;">Dipilih</span>
                                        @endif
                                    </td>
                                    <td class="teks-kecil">
                                        @foreach ($item['saldo_per_jenis'] as $spj)
                                            <span class="bbm-jenis-pill" style="margin-right: 4px;">
                                                <span class="teks-pudar">{{ $spj['nama_bbm'] }}:</span>
                                                <strong class="{{ $spj['saldo'] < 0 ? 'teks-bahaya' : '' }}">{{ number_format($spj['saldo'], 1, ',', '.') }} L</strong>
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="teks-kecil" style="white-space: nowrap;">
                                        <span class="teks-sukses">+{{ number_format($item['penerimaan_bulan_ini'], 1, ',', '.') }} L</span>
                                        <span class="teks-pudar"> / </span>
                                        <span class="teks-bahaya">-{{ number_format($item['penggunaan_bulan_ini'], 1, ',', '.') }} L</span>
                                    </td>
                                    <td class="angka teks-tebal {{ $item['saldo'] < 0 ? 'teks-bahaya' : 'teks-utama' }}" style="white-space: nowrap;">
                                        {{ number_format($item['saldo'], 2, ',', '.') }} L
                                    </td>
                                    <td style="white-space: nowrap;">
                                        @if ($item['menunggu'] > 0)
                                            <a href="{{ $urlMenungguTim }}" class="lencana lencana--emas" style="text-decoration: none;"
                                               onclick="event.stopPropagation()">
                                                <i class="bi bi-clock"></i> {{ $item['menunggu'] }} menunggu
                                            </a>
                                        @else
                                            <span class="teks-kecil teks-pudar">Aman</span>
                                        @endif
                                    </td>
                                    <td class="aksi" onclick="event.stopPropagation()">
                                        <button type="button"
                                                class="tombol tombol--garis tombol--kecil"
                                                onclick='bukaModalDetailTim(@json($payloadTim))'>
                                            <i class="bi bi-eye"></i> Lihat
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach

                        @if (! $adaTimTampil)
                            <tr>
                                <td colspan="6" class="teks-kecil teks-pudar" style="text-align: center; padding: 14px;">
                                    Belum ada tim dengan saldo atau aktivitas BBM bulan ini. Klik <strong>"Semua {{ $ringkasanGlobal['total_tim'] }} Tim"</strong> untuk melihat seluruh daftar tim.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Kartu Riwayat Transaksi dengan Tab Cepat Minimalis --}}
    <div class="kartu">
        @php
            $tabAktif = match (true) {
                $status === 'menunggu_pemeriksaan' => 'menunggu',
                $status === 'perlu_perbaikan' => 'perbaikan',
                $jenisTransaksi === 'masuk' || $jenisTransaksi === 'penerimaan' => 'masuk',
                $jenisTransaksi === 'penggunaan' => 'keluar',
                default => ($status || $jenisTransaksi ? 'lainnya' : 'semua'),
            };
            $baseQueryTim = $timId ? ['tim_id' => $timId] : [];
        @endphp

        <div class="bbm-tabs">
            <a href="{{ route('bbm.index', $baseQueryTim) }}"
               class="bbm-tab {{ $tabAktif === 'semua' ? 'bbm-tab--aktif' : '' }}">
                Semua <span class="bbm-tab__angka">{{ $tabCounts['semua'] }}</span>
            </a>
            <a href="{{ route('bbm.index', array_merge($baseQueryTim, ['status' => 'menunggu_pemeriksaan'])) }}"
               class="bbm-tab {{ $tabAktif === 'menunggu' ? 'bbm-tab--aktif' : '' }}">
                Menunggu Verifikasi
                <span class="bbm-tab__angka {{ $tabCounts['menunggu'] > 0 ? 'bbm-tab__angka--emas' : '' }}">{{ $tabCounts['menunggu'] }}</span>
            </a>
            <a href="{{ route('bbm.index', array_merge($baseQueryTim, ['jenis_transaksi' => 'masuk'])) }}"
               class="bbm-tab {{ $tabAktif === 'masuk' ? 'bbm-tab--aktif' : '' }}">
                Stok Masuk (+) <span class="bbm-tab__angka">{{ $tabCounts['masuk'] }}</span>
            </a>
            <a href="{{ route('bbm.index', array_merge($baseQueryTim, ['jenis_transaksi' => 'penggunaan'])) }}"
               class="bbm-tab {{ $tabAktif === 'keluar' ? 'bbm-tab--aktif' : '' }}">
                Pemakaian (-) <span class="bbm-tab__angka">{{ $tabCounts['keluar'] }}</span>
            </a>
            @if ($tabCounts['perbaikan'] > 0)
                <a href="{{ route('bbm.index', array_merge($baseQueryTim, ['status' => 'perlu_perbaikan'])) }}"
                   class="bbm-tab {{ $tabAktif === 'perbaikan' ? 'bbm-tab--aktif' : '' }}">
                    Perlu Perbaikan
                    <span class="bbm-tab__angka bbm-tab__angka--merah">{{ $tabCounts['perbaikan'] }}</span>
                </a>
            @endif
        </div>

        <form method="GET" class="bilah-alat">
            @if ($ringkasanTim->count() > 1)
                <select name="tim_id" class="masukan" data-kirim-otomatis aria-label="Tim Drone">
                    <option value="">Semua Tim</option>
                    @foreach ($daftarTim as $t)
                        <option value="{{ $t->id }}" @selected((string) $timId === (string) $t->id)>{{ $t->nama_tim }}</option>
                    @endforeach
                </select>
            @elseif ($timId)
                <input type="hidden" name="tim_id" value="{{ $timId }}">
            @endif

            <select name="jenis_bbm_id" class="masukan" data-kirim-otomatis aria-label="Jenis BBM">
                <option value="">Semua Jenis BBM</option>
                @foreach ($daftarJenisBbm as $jb)
                    <option value="{{ $jb->id }}" @selected((string) $jenisBbmId === (string) $jb->id)>{{ $jb->nama_bbm }}</option>
                @endforeach
            </select>

            <select name="jenis_transaksi" class="masukan" data-kirim-otomatis aria-label="Jenis Transaksi">
                <option value="">Semua Mutasi</option>
                <option value="masuk" @selected($jenisTransaksi === 'masuk')>Semua Stok Masuk (+)</option>
                @foreach (\App\Models\TransaksiBbm::jenisTransaksi() as $k => $v)
                    <option value="{{ $k }}" @selected($jenisTransaksi === $k)>{{ $v }}</option>
                @endforeach
            </select>

            <select name="status" class="masukan" data-kirim-otomatis aria-label="Status Verifikasi">
                <option value="">Semua Status</option>
                @foreach (\App\Models\TransaksiBbm::statusVerifikasi() as $k => $v)
                    <option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>
                @endforeach
            </select>

            <select name="bulan" class="masukan" data-kirim-otomatis aria-label="Bulan">
                <option value="0" @selected($bulan === 0)>Semua Bulan</option>
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($bulan === $m)>{{ Carbon\Carbon::create(2026, $m, 1)->translatedFormat('F') }}</option>
                @endfor
            </select>

            <select name="tahun" class="masukan" data-kirim-otomatis aria-label="Tahun">
                <option value="0" @selected($tahun === 0)>Semua Tahun</option>
                @for ($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                @endfor
            </select>

            @if (($ringkasanTim->count() > 1 && $timId) || $jenisBbmId || $jenisTransaksi || $status || $bulan !== 0 || $tahun !== 0)
                <a href="{{ route('bbm.index') }}" class="tombol tombol--garis"><i class="bi bi-x-lg"></i> Reset</a>
            @endif
        </form>

        <div class="tabel-bungkus">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>Tanggal & Nomor</th>
                        @if ($ringkasanTim->count() > 1)
                            <th>Tim Drone</th>
                        @endif
                        <th>BBM & Keperluan</th>
                        <th class="angka">Jumlah</th>
                        @if ($bolehLihatBiaya)
                            <th class="angka">Biaya</th>
                        @endif
                        <th>Status</th>
                        <th class="aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarTransaksi as $tx)
                        @php
                            $labelTx = \App\Models\TransaksiBbm::jenisTransaksi()[$tx->jenis_transaksi] ?? $tx->jenis_transaksi;
                            $warnaSt = match ($tx->status_verifikasi) {
                                'terverifikasi' => 'hijau',
                                'menunggu_pemeriksaan' => 'emas',
                                'perlu_perbaikan' => 'merah',
                                default => 'abu',
                            };
                            $labelSt = \App\Models\TransaksiBbm::statusVerifikasi()[$tx->status_verifikasi] ?? $tx->status_verifikasi;
                            $lampiranModal = $tx->lampiran->map(fn ($l) => [
                                'nama' => $l->nama_berkas_asli,
                                'url' => route('bbm.lampiran', $l),
                                'is_gambar' => (bool) preg_match('/\.(jpg|jpeg|png)$/i', $l->lokasi_berkas),
                            ])->values();
                        @endphp
                        <tr>
                            <td>
                                <div class="teks-tebal" style="white-space: nowrap;">
                                    {{ $tx->tanggal_transaksi ? $tx->tanggal_transaksi->translatedFormat('d M Y') : '-' }}
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <a href="{{ route('bbm.show', $tx) }}" class="teks-kecil tautan-tabel">{{ $tx->nomor_transaksi }}</a>
                                    @if ($tx->lampiran_count > 0)
                                        <span class="teks-kecil teks-pudar" title="{{ $tx->lampiran_count }} lampiran struk">
                                            <i class="bi bi-paperclip"></i>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            @if ($ringkasanTim->count() > 1)
                                <td>
                                    <div class="teks-tebal">{{ $tx->tim?->nama_tim ?? '-' }}</div>
                                    @if ($tx->laporanRealisasi)
                                        <span class="lencana lencana--biru" style="font-size: 11px; margin-top: 2px;">
                                            <i class="bi bi-airplane"></i> Dari Realisasi
                                        </span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span class="teks-tebal">{{ $tx->jenisBbm?->nama_bbm ?? '-' }}</span>
                                    <span class="teks-kecil teks-pudar">· {{ $tx->labelKeperluan() }}</span>
                                    @if ($ringkasanTim->count() === 1 && $tx->laporanRealisasi)
                                        <span class="lencana lencana--biru" style="font-size: 11px;">Dari Realisasi</span>
                                    @endif
                                </div>
                                @if ($tx->keterangan || $tx->nomor_struk_nota)
                                    <div class="teks-kecil teks-pudar">
                                        @if ($tx->nomor_struk_nota)
                                            Nota: {{ $tx->nomor_struk_nota }}@if($tx->keterangan) · @endif
                                        @endif
                                        {{ \Illuminate\Support\Str::limit($tx->keterangan, 55) }}
                                    </div>
                                @endif
                            </td>
                            <td class="angka teks-tebal" style="white-space: nowrap;">
                                @if (in_array($tx->jenis_transaksi, ['penerimaan', 'stok_awal']))
                                    <span class="teks-sukses">+{{ number_format($tx->jumlah_liter, 2, ',', '.') }} L</span>
                                @elseif ($tx->jenis_transaksi === 'penggunaan')
                                    <span class="teks-bahaya">-{{ number_format($tx->jumlah_liter, 2, ',', '.') }} L</span>
                                @elseif ($tx->jenis_transaksi === 'penyesuaian')
                                    <span class="teks-info">±{{ number_format($tx->jumlah_liter, 2, ',', '.') }} L</span>
                                @else
                                    <span>{{ number_format($tx->jumlah_liter, 2, ',', '.') }} L</span>
                                @endif
                                <div class="teks-kecil teks-pudar" style="font-weight: normal;">{{ $labelTx }}</div>
                            </td>
                            @if ($bolehLihatBiaya)
                                <td class="angka teks-kecil" style="white-space: nowrap;">
                                    @if ($tx->total_biaya && in_array($tx->jenis_transaksi, ['penerimaan', 'stok_awal', 'pengajuan']))
                                        <strong>Rp {{ number_format($tx->total_biaya, 0, ',', '.') }}</strong>
                                    @else
                                        <span class="teks-pudar">—</span>
                                    @endif
                                </td>
                            @endif
                            <td style="white-space: nowrap;">
                                <x-lencana :warna="$warnaSt" :teks="$labelSt" />
                            </td>
                            <td class="aksi" style="white-space: nowrap;">
                                <div style="display: flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                    <a href="{{ route('bbm.show', $tx) }}" class="tombol tombol--garis tombol--kecil">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    @if ($pengguna->adalahAdmin() && $tx->status_verifikasi !== \App\Models\TransaksiBbm::TERVERIFIKASI)
                                        @php
                                            $payloadVerif = [
                                                'nomor' => $tx->nomor_transaksi,
                                                'tim' => $tx->tim?->nama_tim ?? '-',
                                                'bbm' => $tx->jenisBbm?->nama_bbm ?? '-',
                                                'liter' => number_format($tx->jumlah_liter, 2, ',', '.'),
                                                'biaya' => $tx->total_biaya ? 'Rp ' . number_format($tx->total_biaya, 0, ',', '.') : '-',
                                                'nota' => $tx->nomor_struk_nota ?: '-',
                                                'jenis' => $labelTx,
                                                'keperluan' => $tx->labelKeperluan(),
                                                'keterangan' => $tx->keterangan ?: '-',
                                                'lampiran' => $lampiranModal,
                                                'url' => route('bbm.verifikasi', $tx),
                                            ];
                                        @endphp
                                        <button type="button"
                                                class="tombol tombol--utama tombol--kecil"
                                                onclick='bukaModalVerifikasi(@json($payloadVerif))'>
                                            <i class="bi bi-check2-circle"></i> Verifikasi
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($ringkasanTim->count() > 1 ? 6 : 5) + ($bolehLihatBiaya ? 1 : 0) }}">
                                <x-kosong ikon="bi-fuel-pump" judul="Belum ada transaksi BBM ditemukan">
                                    @if ($bolehInputBbm)
                                        Klik tombol "Catat Transaksi BBM" untuk mencatat pengisian atau pemakaian BBM, atau ubah filter di atas.
                                    @else
                                        Tidak ada data BBM yang sesuai dengan filter saat ini.
                                    @endif
                                </x-kosong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $daftarTransaksi->links() }}
    </div>

    {{-- Modal Verifikasi Transaksi BBM (Khusus Admin, lengkap dengan Preview Struk) --}}
    @if ($pengguna->adalahAdmin())
        <dialog id="dialog-verifikasi-bbm" class="dialog bbm-dialog" style="width: min(620px, calc(100vw - 32px));">
            <form id="form-verifikasi-bbm" method="POST" action="">
                @csrf
                <div class="bbm-dialog__kepala">
                    <div>
                        <div class="teks-kecil teks-pudar">Pemeriksaan Bukti & Saldo BBM</div>
                        <h2 class="bbm-dialog__judul">Verifikasi Transaksi BBM</h2>
                    </div>
                    <button type="button" class="bbm-dialog__tutup" aria-label="Tutup" onclick="document.getElementById('dialog-verifikasi-bbm').close()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="dialog__badan">
                    <div class="bbm-modal-kotak mb-3" style="font-size: 13.5px;">
                        <div class="bbm-modal-baris-stat" style="margin-top: 0;">
                            <span class="teks-pudar">Nomor / Nota SPBU:</span>
                            <span><strong id="modal-tx-nomor">-</strong> · <span id="modal-tx-nota" class="teks-pudar">-</span></span>
                        </div>
                        <div class="bbm-modal-baris-stat">
                            <span class="teks-pudar">Tim Drone:</span>
                            <strong id="modal-tx-tim">-</strong>
                        </div>
                        <div class="bbm-modal-baris-stat">
                            <span class="teks-pudar">BBM & Jenis:</span>
                            <span id="modal-tx-bbm" class="teks-tebal">-</span>
                        </div>
                        <div class="bbm-modal-baris-stat">
                            <span class="teks-pudar">Jumlah & Biaya:</span>
                            <span><strong id="modal-tx-liter" class="teks-utama">-</strong> (<span id="modal-tx-biaya">-</span>)</span>
                        </div>
                        <div class="bbm-modal-baris-stat">
                            <span class="teks-pudar">Keperluan / Ket:</span>
                            <span id="modal-tx-ket" style="text-align: right;">-</span>
                        </div>
                    </div>

                    <div id="modal-tx-lampiran-box" class="mb-3" style="display: none;">
                        <div class="teks-kecil teks-pudar teks-tebal mb-2"><i class="bi bi-receipt"></i> Lampiran Bukti Nota / Struk</div>
                        <div id="modal-tx-lampiran-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px;"></div>
                    </div>

                    <div class="form-grup mb-3">
                        <label class="form-label">Keputusan Verifikasi <span class="wajib">*</span></label>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <label class="bbm-radio-kartu">
                                <input type="radio" name="tindakan" value="verifikasi" checked>
                                <span class="teks-sukses teks-tebal"><i class="bi bi-check-circle-fill"></i> Setujui</span>
                            </label>
                            <label class="bbm-radio-kartu">
                                <input type="radio" name="tindakan" value="perbaikan">
                                <span style="color: var(--peringatan);" class="teks-tebal"><i class="bi bi-arrow-counterclockwise"></i> Minta Perbaikan</span>
                            </label>
                            <label class="bbm-radio-kartu">
                                <input type="radio" name="tindakan" value="tolak">
                                <span class="teks-bahaya teks-tebal"><i class="bi bi-x-circle-fill"></i> Tolak</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-grup">
                        <label class="form-label" for="modal-tx-catatan">Catatan Pemeriksaan</label>
                        <textarea id="modal-tx-catatan" name="catatan" rows="2" class="masukan" placeholder="Wajib diisi jika meminta perbaikan atau menolak…"></textarea>
                    </div>
                </div>

                <div class="dialog__aksi" style="justify-content: flex-end;">
                    <button type="button" class="tombol tombol--garis tombol--kecil" onclick="document.getElementById('dialog-verifikasi-bbm').close()">Batal</button>
                    <button type="submit" class="tombol tombol--utama tombol--kecil">
                        <i class="bi bi-check2-circle"></i> Simpan Verifikasi
                    </button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($ringkasanTim->count() > 1)
        {{-- Pop-up Daftar Saldo Semua Tim --}}
        <dialog id="dialog-semua-tim-bbm" class="dialog bbm-dialog bbm-dialog--lebar">
            <div class="bbm-dialog__kepala">
                <div>
                    <h2 class="bbm-dialog__judul">
                        <i class="bi bi-fuel-pump teks-utama"></i> Saldo BBM Seluruh Tim ({{ $ringkasanTim->count() }})
                    </h2>
                    <p class="teks-kecil teks-pudar" style="margin: 2px 0 0;">Pilih tim untuk melihat rincian saldo dan riwayat transaksi terbarunya.</p>
                </div>
                <button type="button" class="bbm-dialog__tutup" aria-label="Tutup pop-up" onclick="document.getElementById('dialog-semua-tim-bbm').close()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="dialog__badan">
                <div class="bbm-modal-alat">
                    <input type="search" id="cari-tim-bbm" class="masukan" placeholder="Cari nama tim atau distrik…" oninput="filterDaftarSemuaTim()">
                    <div class="bbm-chip-grup" id="filter-chip-semua-tim">
                        <button type="button" class="bbm-chip bbm-chip--aktif" data-filter="semua" onclick="setFilterChipSemuaTim(this)">Semua ({{ $ringkasanTim->count() }})</button>
                        <button type="button" class="bbm-chip" data-filter="saldo" onclick="setFilterChipSemuaTim(this)">Ada Saldo ({{ $ringkasanGlobal['tim_ada_saldo'] }})</button>
                        <button type="button" class="bbm-chip" data-filter="menunggu" onclick="setFilterChipSemuaTim(this)">Menunggu ({{ $ringkasanTim->where('menunggu', '>', 0)->count() }})</button>
                        @if ($ringkasanGlobal['tim_defisit'] > 0)
                            <button type="button" class="bbm-chip" data-filter="defisit" onclick="setFilterChipSemuaTim(this)">Defisit ({{ $ringkasanGlobal['tim_defisit'] }})</button>
                        @endif
                    </div>
                </div>

                <div class="bbm-modal-tabel-bungkus">
                    <table class="tabel bbm-tabel-padat" id="tabel-semua-tim-modal">
                        <thead>
                            <tr>
                                <th>Tim Drone</th>
                                <th>Rincian Jenis BBM</th>
                                <th class="angka">Saldo Resmi</th>
                                <th>Antrean</th>
                                <th class="aksi">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ringkasanTim as $item)
                                @php
                                    $urlTimModal = route('bbm.index', array_merge(request()->except(['page', 'tim_id']), ['tim_id' => $item['tim']->id]));
                                    $urlMenungguModal = route('bbm.index', ['tim_id' => $item['tim']->id, 'status' => 'menunggu_pemeriksaan']);
                                    $payloadModal = [
                                        'id' => $item['tim']->id,
                                        'nama_tim' => $item['tim']->nama_tim,
                                        'distrik' => $item['tim']->distrik?->nama_distrik,
                                        'drone' => $item['tim']->drone ? ($item['tim']->drone->nomor_drone . ($item['tim']->drone->nama_drone ? ' · ' . $item['tim']->drone->nama_drone : '')) : null,
                                        'saldo' => (float) $item['saldo'],
                                        'saldo_format' => number_format($item['saldo'], 2, ',', '.') . ' Liter',
                                        'per_jenis' => collect($item['saldo_per_jenis'])->map(fn ($spj) => [
                                            'nama_bbm' => $spj['nama_bbm'],
                                            'saldo' => (float) $spj['saldo'],
                                            'saldo_format' => number_format($spj['saldo'], 1, ',', '.') . ' L',
                                        ])->values(),
                                        'masuk_format' => '+' . number_format($item['penerimaan_bulan_ini'], 1, ',', '.') . ' L',
                                        'keluar_format' => '-' . number_format($item['penggunaan_bulan_ini'], 1, ',', '.') . ' L',
                                        'menunggu' => (int) $item['menunggu'],
                                        'url_detail' => route('bbm.detail-tim', $item['tim']->id),
                                        'url_filter' => $urlTimModal,
                                        'url_menunggu' => $urlMenungguModal,
                                        'url_catat' => $bolehInputBbm ? route('bbm.create', ['tim_id' => $item['tim']->id]) : null,
                                    ];
                                @endphp
                                <tr class="bbm-baris-tim"
                                    data-nama-tim="{{ strtolower($item['tim']->nama_tim . ' ' . ($item['tim']->distrik?->nama_distrik ?? '')) }}"
                                    data-ada-saldo="{{ (float) $item['saldo'] > 0 ? '1' : '0' }}"
                                    data-defisit="{{ (float) $item['saldo'] < 0 ? '1' : '0' }}"
                                    data-menunggu="{{ $item['menunggu'] > 0 ? '1' : '0' }}"
                                    onclick='bukaModalDetailTim(@json($payloadModal), true)'>
                                    <td>
                                        <div class="teks-tebal">{{ $item['tim']->nama_tim }}</div>
                                        @if ($item['tim']->distrik)
                                            <div class="teks-kecil teks-pudar">{{ $item['tim']->distrik->nama_distrik }}</div>
                                        @endif
                                    </td>
                                    <td class="teks-kecil">
                                        @foreach ($item['saldo_per_jenis'] as $spj)
                                            <span class="bbm-jenis-pill" style="margin-right: 4px;">
                                                <span class="teks-pudar">{{ $spj['nama_bbm'] }}:</span>
                                                <strong class="{{ $spj['saldo'] < 0 ? 'teks-bahaya' : '' }}">{{ number_format($spj['saldo'], 1, ',', '.') }} L</strong>
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="angka teks-tebal {{ $item['saldo'] < 0 ? 'teks-bahaya' : 'teks-utama' }}" style="white-space: nowrap;">
                                        {{ number_format($item['saldo'], 2, ',', '.') }} L
                                    </td>
                                    <td style="white-space: nowrap;">
                                        @if ($item['menunggu'] > 0)
                                            <span class="lencana lencana--emas">{{ $item['menunggu'] }} menunggu</span>
                                        @else
                                            <span class="teks-kecil teks-pudar">Aman</span>
                                        @endif
                                    </td>
                                    <td class="aksi" style="white-space: nowrap;" onclick="event.stopPropagation()">
                                        <button type="button" class="tombol tombol--garis tombol--kecil" onclick='bukaModalDetailTim(@json($payloadModal), true)'>
                                            <i class="bi bi-eye"></i> Lihat
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </dialog>

        {{-- Pop-up Detail Saldo & Transaksi Terbaru Tim --}}
        <dialog id="dialog-detail-tim-bbm" class="dialog bbm-dialog">
            <div class="bbm-dialog__kepala">
                <div>
                    <div class="teks-kecil teks-pudar">Ringkasan Saldo & Riwayat BBM Tim</div>
                    <h2 id="detail-tim-nama" class="bbm-dialog__judul">-</h2>
                    <div id="detail-tim-meta" class="teks-kecil teks-pudar" style="margin-top: 2px;"></div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span id="detail-tim-lencana" class="lencana lencana--hijau">Saldo Resmi</span>
                    <button type="button" class="bbm-dialog__tutup" aria-label="Tutup pop-up" onclick="document.getElementById('dialog-detail-tim-bbm').close()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <div class="dialog__badan">
                <div class="bbm-modal-ringkasan">
                    <div class="bbm-modal-kotak bbm-modal-kotak--utama">
                        <div class="teks-kecil teks-pudar">Sisa Saldo Resmi</div>
                        <div id="detail-tim-saldo" class="angka-besar teks-utama" style="margin: 6px 0 8px;">0,00 Liter</div>
                        <div id="detail-tim-per-jenis" style="display: flex; gap: 6px; flex-wrap: wrap;"></div>
                    </div>

                    <div class="bbm-modal-kotak">
                        <div class="teks-kecil teks-pudar">Bulan Ini ({{ now()->translatedFormat('M Y') }})</div>
                        <div class="bbm-modal-baris-stat">
                            <span class="teks-kecil teks-pudar">Masuk:</span>
                            <strong id="detail-tim-masuk" class="teks-sukses">+0,0 L</strong>
                        </div>
                        <div class="bbm-modal-baris-stat">
                            <span class="teks-kecil teks-pudar">Pakai:</span>
                            <strong id="detail-tim-keluar" class="teks-bahaya">-0,0 L</strong>
                        </div>
                        <a id="detail-tim-antrean" href="#" class="lencana lencana--emas" style="display: none; text-decoration: none; margin-top: 8px; font-size: 11.5px;"></a>
                    </div>

                    <div class="bbm-modal-kotak">
                        <div class="teks-kecil teks-pudar">Total Akumulasi Sah</div>
                        <div class="bbm-modal-baris-stat">
                            <span class="teks-kecil teks-pudar">Total Masuk:</span>
                            <strong id="detail-tim-total-masuk" class="teks-sukses">—</strong>
                        </div>
                        <div class="bbm-modal-baris-stat">
                            <span class="teks-kecil teks-pudar">Total Terpakai:</span>
                            <strong id="detail-tim-total-keluar" class="teks-bahaya">—</strong>
                        </div>
                        <div class="teks-kecil teks-pudar" style="margin-top: 8px; font-size: 11px;">
                            Hanya transaksi terverifikasi
                        </div>
                    </div>
                </div>

                <div class="flex-antara item-tengah mb-2" style="margin-top: 16px;">
                    <strong class="teks-kecil teks-pudar" style="text-transform: uppercase; letter-spacing: 0.04em;">
                        <i class="bi bi-clock-history"></i> Riwayat Transaksi Terbaru
                    </strong>
                    <span class="teks-kecil teks-pudar" id="detail-tim-jumlah-riwayat"></span>
                </div>

                <div class="bbm-modal-tabel-bungkus">
                    <table class="tabel bbm-tabel-padat">
                        <thead>
                            <tr>
                                <th>Tanggal & Nomor</th>
                                <th>BBM & Keperluan</th>
                                <th class="angka">Mutasi</th>
                                <th>Status</th>
                                <th class="aksi">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detail-tim-transaksi-body">
                            <tr>
                                <td colspan="5" class="teks-kecil teks-pudar" style="text-align: center; padding: 18px;">Memuat riwayat transaksi…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dialog__aksi">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" id="btn-detail-kembali-semua" class="tombol tombol--garis tombol--kecil" style="display: none;" onclick="kembaliKeDaftarSemuaTim()">
                        <i class="bi bi-arrow-left"></i> Semua Tim
                    </button>
                    <button type="button" class="tombol tombol--garis tombol--kecil" onclick="document.getElementById('dialog-detail-tim-bbm').close()">
                        Tutup
                    </button>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a id="detail-tim-btn-filter" href="#" class="tombol tombol--garis tombol--kecil">
                        <i class="bi bi-funnel"></i> Filter Tabel Utama
                    </a>
                    <a id="detail-tim-btn-catat" href="#" class="tombol tombol--utama tombol--kecil" style="display: none;">
                        <i class="bi bi-plus-lg"></i> Catat BBM Tim Ini
                    </a>
                </div>
            </div>
        </dialog>
    @endif

    @push('gaya')
    <style>
        .bbm-hero-tunggal {
            background: linear-gradient(135deg, #f3fbf7 0%, #ffffff 70%);
            border-color: #c5e8d6;
        }
        .bbm-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 14px 24px 0;
            border-bottom: 1px solid var(--garis, #e2e8f0);
            background: #fff;
            border-top-left-radius: inherit;
            border-top-right-radius: inherit;
        }
        .bbm-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            font-size: 13.5px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: color 0.15s ease, border-color 0.15s ease;
        }
        .bbm-tab:hover {
            color: var(--hijau-700, #165e45);
        }
        .bbm-tab--aktif {
            color: var(--hijau-700, #165e45);
            border-bottom-color: var(--hijau-600, #1b6b52);
        }
        .bbm-tab__angka {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 20px;
            padding: 0 7px;
            border-radius: 999px;
            font-size: 11.5px;
            background: #f1f5f9;
            color: #475569;
        }
        .bbm-tab__angka--emas {
            background: var(--peringatan-bg, #fef3c7);
            color: var(--peringatan, #b45309);
        }
        .bbm-tab__angka--merah {
            background: var(--bahaya-bg, #fee2e2);
            color: var(--bahaya, #dc2626);
        }
        .bbm-kartu-aksi {
            display: block;
            text-decoration: none;
            color: inherit;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .bbm-kartu-aksi:hover {
            transform: translateY(-2px);
            border-color: var(--hijau-500, #23966e);
            box-shadow: 0 6px 18px rgba(20, 83, 62, 0.08);
        }
        .bbm-kartu-aksi--aktif {
            border-color: var(--hijau-600, #1b6b52);
            background: #f2fbf7;
            box-shadow: 0 0 0 2px rgba(27, 107, 82, 0.14);
        }
        .bbm-pill-mutasi {
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 10px;
            text-decoration: none;
            border: 1px solid transparent;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .bbm-pill-mutasi:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .bbm-pill-mutasi--aktif {
            background: #eff6ff;
            border-color: #93c5fd;
        }
        .bbm-baris-tim {
            cursor: pointer;
            transition: background-color 0.12s ease;
        }
        .bbm-baris-tim:hover td {
            background-color: #f4faf6;
        }
        .bbm-baris-tim--aktif td {
            background-color: #ecfdf5 !important;
        }
        .bbm-radio-kartu {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border: 1px solid var(--garis, #e2e8f0);
            border-radius: 10px;
            cursor: pointer;
            background: #fff;
            font-size: 13px;
        }
        .bbm-dialog {
            width: min(860px, calc(100vw - 32px));
            max-width: 95vw;
            max-height: 90vh;
            margin: auto;
            overflow: hidden;
        }
        .bbm-dialog--lebar {
            width: min(920px, calc(100vw - 32px));
        }
        .bbm-dialog[open] {
            display: flex;
            flex-direction: column;
        }
        .bbm-dialog__kepala {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 24px 14px;
            border-bottom: 1px solid var(--garis, #e2e8f0);
            background: #fff;
            flex-shrink: 0;
        }
        .bbm-dialog__judul {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--teks, #1e293b);
        }
        .bbm-dialog__tutup {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--garis, #e2e8f0);
            background: #fff;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .bbm-dialog__tutup:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .bbm-dialog .dialog__badan {
            text-align: left;
            padding: 18px 24px;
            overflow-y: auto;
            flex: 1 1 auto;
        }
        .bbm-dialog .dialog__aksi {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 14px 24px;
            border-top: 1px solid var(--garis, #e2e8f0);
            background: #fafcfb;
            flex-shrink: 0;
        }
        .bbm-dialog .dialog__aksi .tombol {
            flex: 0 0 auto;
            white-space: nowrap;
        }
        .bbm-modal-ringkasan {
            display: grid;
            grid-template-columns: 1.35fr 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 720px) {
            .bbm-modal-ringkasan {
                grid-template-columns: 1fr;
            }
            .bbm-tabs {
                padding-left: 14px;
                padding-right: 14px;
            }
        }
        .bbm-modal-kotak {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
        }
        .bbm-modal-kotak--utama {
            background: linear-gradient(135deg, #f2fbf7 0%, #ffffff 100%);
            border-color: #bfe7d4;
        }
        .bbm-modal-baris-stat {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
            margin-top: 6px;
        }
        .bbm-jenis-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 2px 9px;
            font-size: 12px;
            white-space: nowrap;
        }
        .bbm-modal-tabel-bungkus {
            max-height: 290px;
            overflow-y: auto;
            overflow-x: auto;
            border: 1px solid var(--garis, #e2e8f0);
            border-radius: 12px;
            background: #fff;
        }
        #dialog-semua-tim-bbm .bbm-modal-tabel-bungkus {
            max-height: 54vh;
        }
        .bbm-tabel-padat th,
        .bbm-tabel-padat td {
            padding: 10px 12px;
            font-size: 13px;
            vertical-align: middle;
        }
        .bbm-modal-alat {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .bbm-modal-alat .masukan {
            flex: 1 1 240px;
            min-width: 200px;
        }
        .bbm-chip-grup {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .bbm-chip {
            border: 1px solid var(--garis, #cbd5e1);
            background: #fff;
            color: #475569;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .bbm-chip:hover {
            border-color: var(--hijau-500, #23966e);
            color: var(--hijau-700, #165e45);
        }
        .bbm-chip--aktif {
            background: var(--hijau-600, #1b6b52);
            border-color: var(--hijau-600, #1b6b52);
            color: #fff;
        }
        .bbm-chip--aktif:hover {
            color: #fff;
        }
    </style>
    @endpush

    @push('skrip')
    <script>
        let filterChipSemuaTimAktif = 'semua';

        function escapeHtmlBbm(teks) {
            return String(teks ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function bukaModalVerifikasi(data) {
            const dlg = document.getElementById('dialog-verifikasi-bbm');
            const form = document.getElementById('form-verifikasi-bbm');
            if (!dlg || !form) return;

            form.action = data.url;
            document.getElementById('modal-tx-nomor').textContent = data.nomor;
            document.getElementById('modal-tx-nota').textContent = data.nota && data.nota !== '-' ? `Nota: ${data.nota}` : 'Tanpa no. nota';
            document.getElementById('modal-tx-tim').textContent = data.tim;
            document.getElementById('modal-tx-bbm').textContent = `${data.bbm} (${data.jenis})`;
            document.getElementById('modal-tx-liter').textContent = `${data.liter} Liter`;
            document.getElementById('modal-tx-biaya').textContent = data.biaya;
            document.getElementById('modal-tx-ket').textContent = data.keterangan && data.keterangan !== '-'
                ? `${data.keperluan} · ${data.keterangan}`
                : data.keperluan;
            document.getElementById('modal-tx-catatan').value = '';

            const boxLamp = document.getElementById('modal-tx-lampiran-box');
            const listLamp = document.getElementById('modal-tx-lampiran-list');
            const lampiran = Array.isArray(data.lampiran) ? data.lampiran : [];
            if (boxLamp && listLamp) {
                if (lampiran.length > 0) {
                    boxLamp.style.display = 'block';
                    listLamp.innerHTML = lampiran.map((l) => {
                        if (l.is_gambar) {
                            return `<a href="${escapeHtmlBbm(l.url)}" target="_blank" style="display:block;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;text-decoration:none;">
                                <img src="${escapeHtmlBbm(l.url)}" alt="${escapeHtmlBbm(l.nama)}" style="width:100%;height:120px;object-fit:cover;display:block;">
                                <div class="teks-kecil teks-pudar" style="padding:6px 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtmlBbm(l.nama)}</div>
                            </a>`;
                        }
                        return `<a href="${escapeHtmlBbm(l.url)}" target="_blank" class="tombol tombol--garis tombol--kecil" style="justify-content:flex-start;">
                            <i class="bi bi-file-earmark-pdf"></i> ${escapeHtmlBbm(l.nama)}
                        </a>`;
                    }).join('');
                } else {
                    boxLamp.style.display = 'none';
                    listLamp.innerHTML = '';
                }
            }

            dlg.showModal();
        }

        function bukaModalSemuaTim() {
            const dlg = document.getElementById('dialog-semua-tim-bbm');
            const inputCari = document.getElementById('cari-tim-bbm');
            if (!dlg) return;

            if (inputCari) {
                inputCari.value = '';
            }
            const btnSemua = document.querySelector('#filter-chip-semua-tim .bbm-chip[data-filter="semua"]');
            if (btnSemua) {
                setFilterChipSemuaTim(btnSemua);
            } else {
                filterDaftarSemuaTim();
            }
            dlg.showModal();
        }

        function setFilterChipSemuaTim(btn) {
            filterChipSemuaTimAktif = btn.getAttribute('data-filter') || 'semua';
            document.querySelectorAll('#filter-chip-semua-tim .bbm-chip').forEach((el) => {
                el.classList.toggle('bbm-chip--aktif', el === btn);
            });
            filterDaftarSemuaTim();
        }

        function filterDaftarSemuaTim() {
            const input = document.getElementById('cari-tim-bbm');
            const q = String(input ? input.value : '').trim().toLowerCase();
            const baris = document.querySelectorAll('#tabel-semua-tim-modal tbody tr[data-nama-tim]');
            baris.forEach((tr) => {
                const nama = tr.getAttribute('data-nama-tim') || '';
                const cocokTeks = !q || nama.includes(q);
                let cocokChip = true;
                if (filterChipSemuaTimAktif === 'saldo') {
                    cocokChip = tr.getAttribute('data-ada-saldo') === '1';
                } else if (filterChipSemuaTimAktif === 'menunggu') {
                    cocokChip = tr.getAttribute('data-menunggu') === '1';
                } else if (filterChipSemuaTimAktif === 'defisit') {
                    cocokChip = tr.getAttribute('data-defisit') === '1';
                }
                tr.style.display = (cocokTeks && cocokChip) ? '' : 'none';
            });
        }

        function kembaliKeDaftarSemuaTim() {
            const dlgDetail = document.getElementById('dialog-detail-tim-bbm');
            if (dlgDetail && dlgDetail.open) dlgDetail.close();
            bukaModalSemuaTim();
        }

        async function bukaModalDetailTim(tim, dariModalSemua = false) {
            const dlgSemua = document.getElementById('dialog-semua-tim-bbm');
            if (dlgSemua && dlgSemua.open) {
                dlgSemua.close();
            }

            const dlg = document.getElementById('dialog-detail-tim-bbm');
            if (!dlg) return;

            document.getElementById('detail-tim-nama').textContent = tim.nama_tim || '-';

            const infoMeta = [];
            if (tim.distrik) infoMeta.push(`Distrik: ${tim.distrik}`);
            if (tim.drone) infoMeta.push(`Drone: ${tim.drone}`);
            document.getElementById('detail-tim-meta').textContent = infoMeta.join(' · ');

            const lencana = document.getElementById('detail-tim-lencana');
            const saldoEl = document.getElementById('detail-tim-saldo');
            if (tim.saldo < 0) {
                lencana.className = 'lencana lencana--merah';
                lencana.textContent = 'Defisit Stok';
                saldoEl.className = 'angka-besar teks-bahaya';
            } else {
                lencana.className = 'lencana lencana--hijau';
                lencana.textContent = 'Saldo Resmi';
                saldoEl.className = 'angka-besar teks-utama';
            }
            saldoEl.textContent = tim.saldo_format || '0,00 Liter';

            const perJenisEl = document.getElementById('detail-tim-per-jenis');
            if (Array.isArray(tim.per_jenis) && tim.per_jenis.length) {
                perJenisEl.innerHTML = tim.per_jenis.map((pj) => {
                    const cls = pj.saldo < 0 ? 'teks-bahaya' : '';
                    return `<span class="bbm-jenis-pill"><span class="teks-pudar">${escapeHtmlBbm(pj.nama_bbm)}:</span> <strong class="${cls}">${escapeHtmlBbm(pj.saldo_format)}</strong></span>`;
                }).join('');
            } else {
                perJenisEl.innerHTML = '';
            }

            document.getElementById('detail-tim-masuk').textContent = tim.masuk_format || '+0,0 L';
            document.getElementById('detail-tim-keluar').textContent = tim.keluar_format || '-0,0 L';
            document.getElementById('detail-tim-total-masuk').textContent = 'Memuat…';
            document.getElementById('detail-tim-total-keluar').textContent = 'Memuat…';

            const antreanEl = document.getElementById('detail-tim-antrean');
            if (tim.menunggu > 0) {
                antreanEl.style.display = 'inline-flex';
                antreanEl.href = tim.url_menunggu;
                antreanEl.innerHTML = `<i class="bi bi-clock"></i> ${tim.menunggu} menunggu verifikasi →`;
            } else {
                antreanEl.style.display = 'none';
            }

            document.getElementById('detail-tim-btn-filter').href = tim.url_filter || '#';

            const btnCatat = document.getElementById('detail-tim-btn-catat');
            if (btnCatat) {
                if (tim.url_catat) {
                    btnCatat.style.display = 'inline-flex';
                    btnCatat.href = tim.url_catat;
                } else {
                    btnCatat.style.display = 'none';
                }
            }

            const btnKembali = document.getElementById('btn-detail-kembali-semua');
            if (btnKembali) {
                btnKembali.style.display = dariModalSemua ? 'inline-flex' : 'none';
            }

            document.getElementById('detail-tim-jumlah-riwayat').textContent = '';
            const tbody = document.getElementById('detail-tim-transaksi-body');
            tbody.innerHTML = '<tr><td colspan="5" class="teks-kecil teks-pudar" style="text-align: center; padding: 18px;">Memuat riwayat transaksi…</td></tr>';

            dlg.showModal();

            try {
                const res = await fetch(tim.url_detail, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Gagal memuat data');
                const json = await res.json();
                const infoTim = json.tim || {};
                const rows = json.transaksi || [];

                const metaLengkap = [];
                if (infoTim.distrik) metaLengkap.push(`Distrik: ${infoTim.distrik}`);
                if (infoTim.drone) metaLengkap.push(`Drone: ${infoTim.drone}`);
                if (infoTim.kepala_tim) metaLengkap.push(`PJ Tim: ${infoTim.kepala_tim}`);
                if (metaLengkap.length) {
                    document.getElementById('detail-tim-meta').textContent = metaLengkap.join(' · ');
                }

                document.getElementById('detail-tim-total-masuk').textContent = infoTim.total_masuk_format || '+0,0 L';
                document.getElementById('detail-tim-total-keluar').textContent = infoTim.total_keluar_format || '-0,0 L';
                document.getElementById('detail-tim-jumlah-riwayat').textContent = rows.length ? `${rows.length} transaksi terakhir` : '';

                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="teks-kecil teks-pudar" style="text-align: center; padding: 20px;">Belum ada transaksi BBM yang tercatat untuk tim ini.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map((tx) => {
                    const clsMutasi = tx.jenis_transaksi === 'penggunaan'
                        ? 'teks-bahaya'
                        : ((tx.jenis_transaksi === 'penerimaan' || tx.jenis_transaksi === 'stok_awal') ? 'teks-sukses' : '');
                    const subKet = tx.keterangan
                        ? `${escapeHtmlBbm(tx.keperluan)} · ${escapeHtmlBbm(tx.keterangan)}`
                        : escapeHtmlBbm(tx.keperluan);
                    return `
                        <tr>
                            <td>
                                <div class="teks-tebal" style="white-space: nowrap;">${escapeHtmlBbm(tx.tanggal)}</div>
                                <a href="${escapeHtmlBbm(tx.url_show)}" class="teks-kecil tautan-tabel">${escapeHtmlBbm(tx.nomor_transaksi)}</a>
                            </td>
                            <td>
                                <div class="teks-tebal">${escapeHtmlBbm(tx.jenis_bbm)}</div>
                                <div class="teks-kecil teks-pudar">${subKet}</div>
                            </td>
                            <td class="angka teks-tebal ${clsMutasi}" style="white-space: nowrap;">${escapeHtmlBbm(tx.jumlah_format)}</td>
                            <td style="white-space: nowrap;"><span class="lencana lencana--${escapeHtmlBbm(tx.warna_status)}">${escapeHtmlBbm(tx.label_status)}</span></td>
                            <td class="aksi" style="white-space: nowrap;">
                                <a href="${escapeHtmlBbm(tx.url_show)}" class="tombol tombol--garis tombol--kecil"><i class="bi bi-eye"></i> Detail</a>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (e) {
                document.getElementById('detail-tim-total-masuk').textContent = '—';
                document.getElementById('detail-tim-total-keluar').textContent = '—';
                tbody.innerHTML = '<tr><td colspan="5" class="teks-kecil teks-bahaya" style="text-align: center; padding: 18px;">Gagal memuat riwayat transaksi tim.</td></tr>';
            }
        }

        ['dialog-semua-tim-bbm', 'dialog-detail-tim-bbm', 'dialog-verifikasi-bbm'].forEach((id) => {
            const dlg = document.getElementById(id);
            if (dlg) {
                dlg.addEventListener('click', (e) => {
                    if (e.target === dlg) dlg.close();
                });
            }
        });
    </script>
    @endpush
@endsection
