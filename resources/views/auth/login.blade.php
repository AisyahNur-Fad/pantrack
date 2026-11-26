@extends('layouts.guest')

@section('judul', 'Masuk')

@push('gaya')
<style>
    /* Kunci tinggi satu layar penuh dan cegah scrollbar di seluruh browser */
    html, body {
        height: 100vh !important;
        max-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
    }

    /* White Glassmorphism Card: Elegan, Bersih, Presisi & Kontras Tinggi */
    .login-white-glass {
        background: rgba(255, 255, 255, 0.88) !important;
        backdrop-filter: blur(24px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(24px) saturate(180%) !important;
        border: 1px solid rgba(255, 255, 255, 0.95) !important;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.6) !important;
    }
    .login-white-glass:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 35px 70px -15px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.8) !important;
    }

    /* Input Wrapper Modern dengan Flexbox Presisi (Mencegah Ikon Menabrak / Naik ke Atas) */
    .login-input-wrapper {
        position: relative;
        display: flex;
        align-items: stretch;
        width: 100%;
        height: 48px;
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 14px;
        box-sizing: border-box;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }
    .login-input-wrapper:hover {
        border-color: #94a3b8;
    }
    .login-input-wrapper:focus-within {
        border-color: #059669 !important;
        box-shadow: 0 0 0 3.5px rgba(16, 185, 129, 0.2) !important;
        background: #ffffff !important;
    }
    .login-input-wrapper--error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3.5px rgba(239, 68, 68, 0.15) !important;
    }

    /* Ikon Kiri (Tengah Vertikal Sempurna) */
    .login-input-icon {
        width: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #64748b;
        font-size: 1.15rem;
        pointer-events: none;
        transition: color 0.2s ease;
    }
    .login-input-wrapper:focus-within .login-input-icon {
        color: #059669;
    }
    .login-input-wrapper--error .login-input-icon {
        color: #ef4444;
    }

    /* Elemen Input Utama */
    .login-input-field {
        flex: 1;
        height: 100%;
        width: 100%;
        border: none !important;
        outline: none !important;
        background: transparent !important;
        padding: 0 14px 0 0 !important;
        margin: 0 !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #0f172a !important;
        box-shadow: none !important;
        box-sizing: border-box !important;
    }
    .login-input-field::placeholder {
        color: #94a3b8 !important;
        font-weight: 400 !important;
    }

    /* Mencegah Tampilan Autofill Browser Menjadi Berantakan / Biru Pucat */
    .login-input-field:-webkit-autofill,
    .login-input-field:-webkit-autofill:hover, 
    .login-input-field:-webkit-autofill:focus,
    .login-input-field:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
        -webkit-text-fill-color: #0f172a !important;
        transition: background-color 5000s ease-in-out 0s !important;
    }

    /* Tombol Tampilkan / Sembunyikan Sandi (Kanan) */
    .login-input-action {
        width: 44px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 1.1rem;
        cursor: pointer;
        outline: none;
        padding: 0;
        margin: 0;
        transition: all 0.2s ease;
    }
    .login-input-action:hover {
        color: #0f172a;
        background: rgba(241, 245, 249, 0.6);
    }

    /* Tombol Masuk Sistem */
    .login-btn-submit {
        width: 100%;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background: #047857;
        color: #ffffff;
        font-size: 15px;
        font-weight: 700;
        border-radius: 9999px;
        border: 1px solid rgba(16, 185, 129, 0.35);
        box-shadow: 0 4px 14px 0 rgba(4, 120, 87, 0.3);
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }
    .login-btn-submit:hover {
        background: #059669;
        transform: translateY(-1.5px);
        box-shadow: 0 6px 20px 0 rgba(4, 120, 87, 0.4);
    }
    .login-btn-submit:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px 0 rgba(4, 120, 87, 0.3);
    }

    .login-btn-icon-pill {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #fbbf24;
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        transition: transform 0.2s ease;
    }
    .login-btn-submit:hover .login-btn-icon-pill {
        transform: translateX(2px);
    }
</style>
@endpush

@section('konten')
{{-- Layout Satu Layar Penuh (No Scroll, Centered Vertically & Horizontally) --}}
<main class="relative h-screen w-full overflow-hidden flex items-center justify-center p-4 sm:p-6 font-sans bg-slate-950">
    
    {{-- Gambar Latar Belakang (Identik dengan Landing Page: /storage/images/login.jpg) --}}
    <div class="absolute inset-0 z-0">
        <img src="{{ asset('storage/images/login.jpg') }}"
             alt="PANTRACK Drone Agrikultur Perkebunan Sawit"
             class="w-full h-full object-cover object-center filter brightness-[0.92] contrast-[1.05]">
    </div>
    
    {{-- Overlay Gelap Halus untuk Kedalaman Visual --}}
    <div class="absolute inset-0 z-0 bg-gradient-to-b from-black/45 via-black/25 to-[#072118]/70 pointer-events-none"></div>
    <div class="absolute inset-0 z-0 bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.15),transparent_70%)] pointer-events-none"></div>

    {{-- White Glassmorphism Card: Putih, Semi-Transparan, Elevated, & Proporsional --}}
    <div class="login-white-glass relative z-10 w-full max-w-[430px] sm:max-w-[450px] rounded-3xl p-7 sm:p-9 shadow-2xl transition-all duration-300 ease-out">
        
        {{-- Header Card: Brand Logo & Navigasi Kembali --}}
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-gray-200/80">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group no-underline">
                <img src="{{ asset('storage/images/logo.png') }}" alt="Logo DRONE BERTUAH" class="w-11 h-11 object-contain group-hover:scale-105 transition-transform duration-200 drop-shadow-md">
                <div>
                    <span class="block text-lg font-extrabold tracking-wider text-gray-900 leading-tight">PANTRACK</span>
                    <span class="block text-[10px] font-semibold text-emerald-800 tracking-wide uppercase">PT. Panlibata Andalas</span>
                </div>
            </a>

            {{-- Link Kembali ke Beranda --}}
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:text-emerald-950 transition-colors duration-200 group no-underline">
                <i class="bi bi-arrow-left text-sm transition-transform duration-200 group-hover:-translate-x-1"></i>
                <span>Beranda</span>
            </a>
        </div>

        {{-- Pill Badge: Tema Hijau Agrikultur --}}
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full border border-emerald-300 bg-emerald-50/90 text-emerald-800 text-xs font-semibold tracking-wide mb-3 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
            <span>Portal Masuk Petugas</span> 
        </div>

        {{-- Judul & Deskripsi Form (Teks Gelap & Kontras Tinggi) --}}
        <div class="mb-5">
            <h1 class="text-2xl sm:text-[28px] font-extrabold text-gray-900 tracking-tight leading-tight">
                Selamat Datang
            </h1>
            <p class="text-xs sm:text-sm text-gray-600 mt-1 leading-relaxed font-normal">
                Masuk dengan akun kredensial resmi Anda untuk melanjutkan.
            </p>
        </div>

        {{-- Notifikasi Info --}}
        @if (session('info'))
            <div class="mb-4 flex items-center gap-3 p-3 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl text-xs font-medium shadow-xs" role="status">
                <i class="bi bi-info-circle-fill text-base text-emerald-600 shrink-0"></i>
                <div class="leading-relaxed">{{ session('info') }}</div>
            </div>
        @endif

        {{-- Form Login --}}
        <form class="flex flex-col gap-4" method="POST" action="{{ route('login.proses') }}">
            @csrf

            {{-- Input Field 1: Nama Pengguna atau Email --}}
            <div class="flex flex-col gap-1.5 text-left">
                <label for="nama_pengguna" class="text-sm font-semibold text-gray-700">
                    Nama Pengguna atau Email
                </label>
                <div class="login-input-wrapper {{ $errors->has('nama_pengguna') ? 'login-input-wrapper--error' : '' }}">
                    <div class="login-input-icon">
                        <i class="bi bi-person"></i>
                    </div>
                    <input type="text"
                           id="nama_pengguna"
                           name="nama_pengguna"
                           value="{{ old('nama_pengguna') }}"
                           autocomplete="username"
                           autofocus
                           required
                           placeholder="nama_pengguna atau email"
                           class="login-input-field">
                </div>
                @error('nama_pengguna')
                    <span class="inline-flex items-center gap-1.5 text-xs text-red-600 font-medium mt-1">
                        <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                    </span>
                @enderror
            </div>

            {{-- Input Field 2: Kata Sandi --}}
            <div class="flex flex-col gap-1.5 text-left">
                <label for="kata_sandi" class="text-sm font-semibold text-gray-700">
                    Kata Sandi
                </label>
                <div class="login-input-wrapper {{ $errors->has('kata_sandi') ? 'login-input-wrapper--error' : '' }}">
                    <div class="login-input-icon">
                        <i class="bi bi-lock"></i>
                    </div>
                    <input type="password"
                           id="kata_sandi"
                           name="kata_sandi"
                           autocomplete="current-password"
                           required
                           placeholder="••••••••"
                           class="login-input-field">
                    <button type="button"
                            class="login-input-action"
                            data-lihat-sandi="#kata_sandi"
                            aria-label="Tampilkan kata sandi"
                            tabindex="-1">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('kata_sandi')
                    <span class="inline-flex items-center gap-1.5 text-xs text-red-600 font-medium mt-1">
                        <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                    </span>
                @enderror
            </div>

            {{-- Tombol Masuk (Hijau Tema PANTRACK + Teks Putih + Lingkaran Kuning Panah) --}}
            <button type="submit"
                    class="login-btn-submit mt-2 group"
                    data-cegah-ganda>
                <span>Masuk ke Sistem</span>
                <span class="login-btn-icon-pill">
                    <i class="bi bi-arrow-right"></i>
                </span>
            </button>
        </form>

        {{-- Footer Card --}}
        <div class="mt-6 pt-4 border-t border-gray-300/60 flex items-center justify-between text-[11px] text-gray-500">
            <div class="flex items-center gap-1.5">
                <img src="{{ asset('storage/images/logo.png') }}" alt="Logo" class="w-3.5 h-3.5 object-contain">
                <span>&copy; {{ now()->year }} PT. Panlibata Andalas Bertuah</span>
            </div>
            <span class="text-emerald-800 font-medium">Sistem Operasional Internal</span>
        </div>

    </div>

</main>
@endsection
