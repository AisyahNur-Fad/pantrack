{{-- Navbar atas: tombol menu (mobile), judul halaman, tanggal, dan menu pengguna. --}}
@php($pengguna = auth()->user())
<header class="navbar">
    <button type="button" class="navbar__menu" data-sidebar-toggle aria-label="Buka menu mobile">
        <i class="bi bi-list"></i>
    </button>

    <button type="button" class="navbar__ciut-tombol sembunyi-mobile" data-sidebar-ciut-toggle title="Ciutkan / Perluas Sidebar (Ctrl+B)">
        <i class="bi bi-text-indent-left"></i>
    </button>

    <div class="navbar__judul">PANTRACK <span class="teks-pudar">/</span> <strong>@yield('judul', 'Dashboard')</strong></div>

    <div class="navbar__ruang"></div>

    <div class="navbar__tanggal">
        <i class="bi bi-calendar3"></i>
        {{ now()->translatedFormat('l, d F Y') }}
    </div>

    <a href="{{ route('realisasi.index') }}" class="navbar__notif-tombol" title="Antrean Pemeriksaan">
        <i class="bi bi-bell"></i>
        <span class="navbar__notif-titik"></span>
    </a>

    <div class="dropdown" data-dropdown>
        <button type="button" class="pengguna-tombol" data-dropdown-tombol aria-haspopup="true">
            <span class="avatar">{{ $pengguna->inisial }}</span>
            <span class="pengguna-tombol__teks">
                <span class="pengguna-tombol__nama">{{ $pengguna->nama_lengkap }}</span>
                <span class="pengguna-tombol__peran">{{ $pengguna->label_peran }}</span>
            </span>
            <i class="bi bi-chevron-down teks-pudar"></i>
        </button>

        <div class="dropdown__menu" role="menu">
            <div class="dropdown__info">
                <strong>{{ $pengguna->nama_lengkap }}</strong>
                <span>{{ '@'.$pengguna->nama_pengguna }} · {{ $pengguna->label_peran }}</span>
            </div>
            <a href="{{ route('profil.index') }}" class="dropdown__tautan" role="menuitem">
                <i class="bi bi-person-gear"></i> Profil & Ganti Kata Sandi
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown__aksi" role="menuitem">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </button>
            </form>
        </div>

    </div>
</header>
