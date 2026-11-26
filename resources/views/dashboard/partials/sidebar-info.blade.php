{{-- Widget 1: Stok BBM per Tim --}}
<div class="kartu-modern">
    <div class="kartu-modern__kepala">
        <h3 class="kartu-modern__judul">
            <i class="bi bi-fuel-pump text-emerald-700"></i> Stok BBM per Tim
        </h3>
        @if (!$pengguna->adalahPekerjaLapangan())
            <a href="{{ route('bbm.index') }}" class="text-xs font-bold text-emerald-700 hover:underline">Kelola →</a>
        @endif
    </div>
    <div class="kartu-modern__badan" style="padding: 14px;">
        @forelse ($ringkasan['ringkasanBbm'] as $b)
            @if ($loop->first) <div class="bbm-daftar"> @endif
            <div class="bbm-item">
                <div class="bbm-item__tim">
                    <div class="bbm-item__ikon"><i class="bi bi-droplet-half"></i></div>
                    <div>
                        <div class="bbm-item__nama">{{ $b['tim']->nama_tim }}</div>
                        <div class="bbm-item__sub">Saldo Resmi Tangki</div>
                    </div>
                </div>
                <div class="bbm-item__saldo">
                    <div class="bbm-item__saldo-nilai">{{ number_format($b['saldo'], 2, ',', '.') }} L</div>
                    <div class="bbm-item__aliran">
                        <span class="teks-sukses" title="Masuk bulan ini">+{{ number_format($b['penerimaan_bulan_ini'], 1, ',', '.') }}L</span>
                        <span class="teks-pudar">/</span>
                        <span class="teks-bahaya" title="Penggunaan bulan ini">-{{ number_format($b['penggunaan_bulan_ini'], 1, ',', '.') }}L</span>
                    </div>
                </div>
            </div>
            @if ($loop->last) </div> @endif
        @empty
            <x-kosong rapat ikon="bi-fuel-pump" judul="Belum ada data BBM tim" />
        @endforelse
    </div>
</div>

{{-- Widget 3: Quick Links Sesuai Peran --}}
<div class="tautan-cepat-box">
    <div class="tautan-cepat-box__judul">Tautan Cepat</div>
    <div class="tautan-cepat-grid">
        @if ($pengguna->adalahPekerjaLapangan())
            <a href="{{ route('realisasi.create') }}" class="tautan-cepat-item tautan-cepat-item--hijau" title="Input Realisasi">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-plus-circle"></i></div>
                <span class="tautan-cepat-item__label">Input Lap</span>
            </a>
            <a href="{{ route('bbm.create') }}" class="tautan-cepat-item tautan-cepat-item--kuning" title="Catat BBM">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-fuel-pump"></i></div>
                <span class="tautan-cepat-item__label">Bon BBM</span>
            </a>
            <a href="{{ route('spk.index') }}" class="tautan-cepat-item tautan-cepat-item--biru" title="Daftar SPK Tim">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-file-earmark-text"></i></div>
                <span class="tautan-cepat-item__label">SPK Tim</span>
            </a>
            <a href="{{ route('profil.index') }}" class="tautan-cepat-item tautan-cepat-item--ungu" title="Profil & Akun">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-person-gear"></i></div>
                <span class="tautan-cepat-item__label">Profil</span>
            </a>
        @elseif ($pengguna->adalahKepalaLapangan())
            <a href="{{ route('spk.create') }}" class="tautan-cepat-item tautan-cepat-item--hijau" title="Buat SPK Baru">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-file-earmark-plus"></i></div>
                <span class="tautan-cepat-item__label">Buat SPK</span>
            </a>
            <a href="{{ route('realisasi.index', ['status' => 'menunggu_pemeriksaan']) }}" class="tautan-cepat-item tautan-cepat-item--biru" title="Periksa Laporan">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-clipboard2-check"></i></div>
                <span class="tautan-cepat-item__label">Periksa</span>
            </a>
            <a href="{{ route('master.drone.index') }}" class="tautan-cepat-item tautan-cepat-item--ungu" title="Armada Drone">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-cpu"></i></div>
                <span class="tautan-cepat-item__label">Drone</span>
            </a>
            <a href="{{ route('master.petak.index') }}" class="tautan-cepat-item tautan-cepat-item--kuning" title="Master Petak">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-grid-3x3"></i></div>
                <span class="tautan-cepat-item__label">Petak</span>
            </a>
        @elseif ($pengguna->adalahAdmin())
            <a href="{{ route('bbm.index', ['status_verifikasi' => 'menunggu']) }}" class="tautan-cepat-item tautan-cepat-item--kuning" title="Verifikasi BBM">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-fuel-pump"></i></div>
                <span class="tautan-cepat-item__label">Verif BBM</span>
            </a>
            <a href="{{ route('keuangan.index') }}" class="tautan-cepat-item tautan-cepat-item--biru" title="Keuangan Unit & Kasbon">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-wallet2"></i></div>
                <span class="tautan-cepat-item__label">Keuangan</span>
            </a>
            <a href="{{ route('ekspor.index') }}" class="tautan-cepat-item tautan-cepat-item--hijau" title="Ekspor 7-Sheet">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-file-earmark-excel"></i></div>
                <span class="tautan-cepat-item__label">Ekspor</span>
            </a>
            <a href="{{ route('master.drone.index') }}" class="tautan-cepat-item tautan-cepat-item--ungu" title="Master Drone">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-cpu"></i></div>
                <span class="tautan-cepat-item__label">Drone</span>
            </a>
        @else
            <a href="{{ route('ekspor.index') }}" class="tautan-cepat-item tautan-cepat-item--hijau" title="Ekspor Laporan">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-file-earmark-excel"></i></div>
                <span class="tautan-cepat-item__label">Excel BAP</span>
            </a>
            <a href="{{ route('keuangan.index') }}" class="tautan-cepat-item tautan-cepat-item--biru" title="Laporan Keuangan Unit">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-cash-stack"></i></div>
                <span class="tautan-cepat-item__label">Keuangan</span>
            </a>
            <a href="{{ route('realisasi.index') }}" class="tautan-cepat-item tautan-cepat-item--kuning" title="Realisasi Lapangan">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-clipboard2-data"></i></div>
                <span class="tautan-cepat-item__label">Realisasi</span>
            </a>
            <a href="{{ route('spk.index') }}" class="tautan-cepat-item tautan-cepat-item--ungu" title="Daftar SPK">
                <div class="tautan-cepat-item__ikon"><i class="bi bi-file-earmark-text"></i></div>
                <span class="tautan-cepat-item__label">SPK</span>
            </a>
        @endif
    </div>
</div>

