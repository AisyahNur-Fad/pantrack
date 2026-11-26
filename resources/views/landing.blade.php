<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="PANTRACK — Sistem Manajemen Monitoring Drone Agrikultur & Akuntansi Operasional Terpadu PT. Panlibata Andalas Bertuah.">
    <meta name="robots" content="noindex, nofollow">

    <title>PANTRACK — Sistem Manajemen Monitoring Drone Agrikultur Terpadu</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('storage/images/logo.png') }}">

    {{-- Tipografi Inter & Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Tailwind CSS CDN dengan Konfigurasi Tema PANTRACK --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            950: '#041710',
                            900: '#072118',
                            850: '#0b2e24',
                            800: '#14513f',
                            700: '#1b6b52',
                            600: '#238a68',
                            500: '#10b981',
                            400: '#34d399',
                            300: '#6ee7b7',
                            200: '#a7f3d0',
                            100: '#d1fae5',
                            50: '#ecfdf5',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            overflow-x: hidden;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #072118;
        }
        ::-webkit-scrollbar-thumb {
            background: #1b6b52;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #10b981;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-brand-500 selection:text-white">

    {{-- =========================================================================
         1. HEADER / NAVBAR
         ========================================================================= --}}
    <header class="sticky top-0 z-50 bg-[#072118]/90 backdrop-blur-md border-b border-brand-800/40 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            {{-- Logo & Brand --}}
            <a href="{{ route('landing') }}" class="flex items-center gap-3.5 group no-underline">
                <img src="{{ asset('storage/images/logo.png') }}" alt="Logo DRONE BERTUAH" class="w-11 h-11 object-contain drop-shadow-md group-hover:scale-105 transition-transform duration-200">
                <div class="flex flex-col">
                    <span class="text-xl font-extrabold tracking-wider text-white leading-none">PANTRACK</span>
                    <span class="text-[10px] font-semibold text-brand-200/80 tracking-wide mt-1 uppercase">PT. Panlibata Andalas Bertuah</span>
                </div>
            </a>

            {{-- Navigasi Desktop --}}
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="#beranda" class="hover:text-brand-300 transition-colors duration-150">Beranda</a>
                <a href="#standar-operasional" class="hover:text-brand-300 transition-colors duration-150">Standar Operasional</a>
                <a href="#modul-sistem" class="hover:text-brand-300 transition-colors duration-150">Modul Kerja</a>
                <a href="#alur-prosedur" class="hover:text-brand-300 transition-colors duration-150">Alur Prosedur</a>
            </nav>

            {{-- CTA Tombol Masuk ke Sistem --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 via-teal-500 to-brand-500 hover:from-emerald-400 hover:to-teal-400 text-brand-950 font-bold text-sm tracking-wide shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/35 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200">
                    <i class="bi bi-box-arrow-in-right text-base font-bold"></i>
                    <span>Masuk ke Sistem</span>
                </a>
            </div>
        </div>
    </header>


    {{-- =========================================================================
         2. HERO SECTION (Sesuai Referensi Desain: Left-Aligned, Gradient Overlay, & Rounded-Full CTA)
         ========================================================================= --}}
    <section id="beranda" class="relative min-h-screen flex items-center overflow-hidden bg-black">
        
        {{-- Gambar Latar Belakang (w-full, h-full, object-cover) --}}
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('storage/images/login.jpg') }}"
                 alt="PANTRACK Drone Agrikultur Perkebunan Sawit"
                 class="w-full h-full object-cover object-center">
        </div>
        
        {{-- Linear Gradient Overlay: Pekat Gelap di Sisi Kiri, Memudar ke Transparan di Sisi Kanan --}}
        <div class="absolute inset-0 z-0 bg-gradient-to-r from-black/95 via-black/70 to-transparent pointer-events-none md:w-3/4 lg:w-3/5"></div>
        <div class="absolute inset-0 z-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent pointer-events-none"></div>

        {{-- Kontainer Konten (Rata Kiri, Lebar Terbatas di Sisi Kiri Layar) --}}
        <div class="relative z-10 w-full max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 py-24 sm:py-32 flex flex-col justify-center">
            
            <div class="max-w-2xl text-left">
                
                {{-- 1. Elemen Badge (Pill) di Atas Judul --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out inline-flex mb-6"
                     data-delay="100">
                    <span class="px-5 py-1.5 rounded-full border border-amber-400/80 bg-black/30 text-amber-400 text-xs sm:text-sm font-medium tracking-wide inline-flex items-center">
                        Sistem Operasional Internal — PT. Panlibata Andalas Bertuah
                    </span>
                </div>

                {{-- 2. Tipografi Judul Utama (Sangat Besar, Tebal, Putih Bersih) --}}
                <h1 class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out text-5xl sm:text-6xl lg:text-7xl font-bold text-white tracking-tight leading-[1.08]"
                    data-delay="200">
                    Sistem Monitoring<br>
                    Drone Agrikultur
                </h1>

                {{-- 3. Deskripsi Operasional Internal --}}
                <p class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out mt-6 text-base sm:text-lg text-gray-300 max-w-xl leading-relaxed font-normal"
                   data-delay="300">
                    Sistem informasi resmi untuk penerbitan SPK, pelaporan realisasi penerbangan, pengawasan logistik BBM, dan rekapitulasi pembukuan operasional unit kerja PT. Panlibata Andalas Bertuah.
                </p>

                {{-- 4. Grup Tombol Call to Action --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out mt-8 flex flex-wrap items-center gap-4 sm:gap-5"
                     data-delay="400">
                    
                    {{-- Tombol Utama: Masuk ke Akun Kerja --}}
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-3.5 px-7 py-3.5 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm sm:text-base shadow-xl shadow-black/50 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 group no-underline">
                        <span>Masuk ke Akun Kerja</span>
                        <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-950 flex items-center justify-center text-xs font-black transition-transform duration-200 group-hover:translate-x-0.5">
                            <i class="bi bi-arrow-right"></i>
                        </span>
                    </a>

                    {{-- Tombol Sekunder: Panduan Alur Prosedur --}}
                    <a href="#alur-prosedur"
                       class="inline-flex items-center gap-3.5 px-7 py-3.5 rounded-full bg-transparent hover:bg-amber-400/10 border border-amber-400 text-amber-400 font-semibold text-sm sm:text-base hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 group no-underline">
                        <span>Alur Prosedur Kerja</span>
                        <span class="w-6 h-6 rounded-full bg-amber-400 text-slate-950 flex items-center justify-center text-xs font-black transition-transform duration-200 group-hover:translate-x-0.5">
                            <i class="bi bi-arrow-right"></i>
                        </span>
                    </a>

                </div>

            </div>

            {{-- 5. Metric Key Stats Bar (Statistik Operasional Terkini) --}}
            <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out mt-16 pt-8 border-t border-white/10 w-full max-w-4xl grid grid-cols-2 sm:grid-cols-4 gap-4 text-left"
                 data-delay="500">
                <div class="bg-black/40 backdrop-blur-sm border border-white/10 rounded-2xl p-4 shadow-lg">
                    <div class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Distrik Operasional</div>
                    <div class="mt-1 text-2xl font-extrabold text-white">{{ $totalDistrik ?? 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">Wilayah Aktif Terdata</div>
                </div>
                <div class="bg-black/40 backdrop-blur-sm border border-white/10 rounded-2xl p-4 shadow-lg">
                    <div class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Armada Drone</div>
                    <div class="mt-1 text-2xl font-extrabold text-white">{{ $totalDrone ?? 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">Unit Siap Terbang</div>
                </div>
                <div class="bg-black/40 backdrop-blur-sm border border-white/10 rounded-2xl p-4 shadow-lg">
                    <div class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Tim Lapangan</div>
                    <div class="mt-1 text-2xl font-extrabold text-white">{{ $totalTim ?? 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">Pilot & Kru Terdaftar</div>
                </div>
                <div class="bg-black/40 backdrop-blur-sm border border-white/10 rounded-2xl p-4 shadow-lg">
                    <div class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Total Realisasi (Ha)</div>
                    <div class="mt-1 text-2xl font-extrabold text-emerald-400">{{ number_format($totalLuasRealisasi ?? 0, 1, ',', '.') }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">Hektar Terverifikasi</div>
                </div>
            </div>

        </div>

    </section>


    {{-- =========================================================================
         3. STANDAR OPERASIONAL (FOKUS & NILAI KERJA UNIT INTERNAL)
         ========================================================================= --}}
    <section id="standar-operasional" class="py-24 bg-white border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Section Heading --}}
            <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out text-center max-w-3xl mx-auto mb-16">
                <span class="inline-block px-3.5 py-1 rounded-full bg-emerald-50 text-brand-700 text-xs font-bold uppercase tracking-wider mb-3">
                    Standar Operasional Internal
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Fokus & Tata Kelola Operasional Unit Kerja
                </h2>
                <p class="mt-4 text-base sm:text-lg text-slate-600 leading-relaxed">
                    Pedoman kerja digital yang diterapkan di lingkungan PT. Panlibata Andalas Bertuah untuk menjamin akurasi data lapangan, efisiensi logistik, dan akuntabilitas administrasi.
                </p>
            </div>

            {{-- 4 Grid Standar Kerja --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                {{-- Pilar 1: Sentralisasi Data --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out group relative bg-slate-50 hover:bg-white rounded-2xl p-8 border border-slate-200 hover:border-brand-500/40 shadow-sm hover:shadow-xl hover:-translate-y-1 flex flex-col justify-between"
                     data-delay="0">
                    <div>
                        <div class="w-14 h-14 rounded-xl bg-brand-50 text-brand-700 border border-brand-200/60 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 group-hover:bg-brand-700 group-hover:text-white transition-all duration-300">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Sentralisasi Data Lapangan</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Menggantikan pencatatan manual yang terpencar di grup pesan menjadi satu pangkalan data resmi. Seluruh riwayat SPK, log penerbangan, dan dokumentasi kerja tersimpan rapi dan dapat diaudit setiap saat.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-200/80 flex items-center text-xs font-semibold text-brand-700">
                        <span>Pangkalan Data Tunggal</span>
                        <i class="bi bi-arrow-right ml-1.5 transition-transform group-hover:translate-x-1"></i>
                    </div>
                </div>

                {{-- Pilar 2: Akurasi Luasan --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out group relative bg-slate-50 hover:bg-white rounded-2xl p-8 border border-slate-200 hover:border-brand-500/40 shadow-sm hover:shadow-xl hover:-translate-y-1 flex flex-col justify-between"
                     data-delay="150">
                    <div>
                        <div class="w-14 h-14 rounded-xl bg-teal-50 text-teal-700 border border-teal-200/60 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 group-hover:bg-teal-700 group-hover:text-white transition-all duration-300">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Akurasi Luasan & Petak</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Memastikan kesesuaian luasan hektar aktual (Ha) dengan Surat Perintah Kerja (SPK). Sistem menghitung persentase deviasi realisasi secara otomatis per petak untuk validasi objektif Kepala Lapangan.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-200/80 flex items-center text-xs font-semibold text-teal-700">
                        <span>Kesesuaian SPK & Aktual</span>
                        <i class="bi bi-arrow-right ml-1.5 transition-transform group-hover:translate-x-1"></i>
                    </div>
                </div>

                {{-- Pilar 3: Akuntabilitas Logistik BBM --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out group relative bg-slate-50 hover:bg-white rounded-2xl p-8 border border-slate-200 hover:border-brand-500/40 shadow-sm hover:shadow-xl hover:-translate-y-1 flex flex-col justify-between"
                     data-delay="300">
                    <div>
                        <div class="w-14 h-14 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/60 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 group-hover:bg-amber-600 group-hover:text-white transition-all duration-300">
                            <i class="bi bi-fuel-pump-fill"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Akuntabilitas Logistik BBM</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Mengawasi mutasi saldo bahan bakar per tim drone dengan harga satuan fixed yang ditetapkan Admin, kalkulasi biaya real-time, dan verifikasi wajib atas bukti nota struk pembelian.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-200/80 flex items-center text-xs font-semibold text-amber-700">
                        <span>Mutasi Tertib & Terverifikasi</span>
                        <i class="bi bi-arrow-right ml-1.5 transition-transform group-hover:translate-x-1"></i>
                    </div>
                </div>

                {{-- Pilar 4: Ketepatan Pembukuan --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out group relative bg-slate-50 hover:bg-white rounded-2xl p-8 border border-slate-200 hover:border-brand-500/40 shadow-sm hover:shadow-xl hover:-translate-y-1 flex flex-col justify-between"
                     data-delay="450">
                    <div>
                        <div class="w-14 h-14 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/60 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 group-hover:bg-emerald-700 group-hover:text-white transition-all duration-300">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Ketepatan Pembukuan & Upah</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Merekapitulasi perhitungan upah kru secara transparan dengan pemotongan kasbon per periode kerja, serta menghasilkan buku besar 7-Sheet Excel dan Berita Acara Pemeriksaan (BAP) PDF siap cetak.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-200/80 flex items-center text-xs font-semibold text-emerald-700">
                        <span>Standar Audit Internal</span>
                        <i class="bi bi-arrow-right ml-1.5 transition-transform group-hover:translate-x-1"></i>
                    </div>
                </div>

            </div>
        </div>
    </section>


    {{-- =========================================================================
         4. MODUL SISTEM KERJA (INTERNAL SYSTEM MODULES)
         ========================================================================= --}}
    <section id="modul-sistem" class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Section Heading --}}
            <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out text-center max-w-3xl mx-auto mb-16">
                <span class="inline-block px-3.5 py-1 rounded-full bg-brand-100 text-brand-800 text-xs font-bold uppercase tracking-wider mb-3">
                    Modul Kerja Internal
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Fasilitas & Modul Pendukung Tugas
                </h2>
                <p class="mt-4 text-base sm:text-lg text-slate-600 leading-relaxed">
                    Instrumen kerja terintegrasi yang disesuaikan dengan kebutuhan operasional drone di perkebunan PT. Panlibata Andalas Bertuah.
                </p>
            </div>

            {{-- 4 Showcase Cards Modul Kerja --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                {{-- Modul 1: SPK Terstruktur --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 flex flex-col justify-between"
                     data-delay="0">
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-700 flex items-center justify-center text-2xl border border-brand-100">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Penerbitan & Pengelolaan SPK (Multi-Petak)</h3>
                                <span class="text-xs font-medium text-slate-500">Form Standar FR-01 s/d FR-06 Operasional Lapangan</span>
                            </div>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed mb-6">
                            Kepala Lapangan menerbitkan penugasan kerja terstruktur dengan memilih beberapa petak kerja dalam distrik, menetapkan target luasan hektar, memilih tim pelaksana, armada drone, serta jenis pekerjaan semprot/pupuk/survey.
                        </p>
                        <ul class="space-y-2.5 text-xs font-medium text-slate-700">
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Penugasan multi-petak dengan validasi distrik terisolasi</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Pencegahan bentrok jadwal penugasan armada drone</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Fitur tandai SPK selesai untuk penguncian data tugas</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Modul 2: Pelaporan Lapangan Dinamis --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 flex flex-col justify-between"
                     data-delay="150">
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-2xl border border-teal-100">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Pelaporan Realisasi Terbang & Helper Lapangan</h3>
                                <span class="text-xs font-medium text-slate-500">Form Standar FR-07 s/d FR-16 Realisasi Pekerjaan</span>
                            </div>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed mb-6">
                            Pilot menginput laporan harian secara sistematis: mencatat luasan hektar selesai per petak, mendaftarkan helper warga lokal harian (maksimal 3 personil), serta mencatat konsumsi BBM operasional per peralatan.
                        </p>
                        <ul class="space-y-2.5 text-xs font-medium text-slate-700">
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Input dinamis helper warga lokal maksimal 3 personil</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Rincian konsumsi BBM multi-alat otomatis memotong stok tim</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Unggah dokumentasi foto bukti pelaksanaan penerbangan</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Modul 3: Verifikasi Berjenjang --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 flex flex-col justify-between"
                     data-delay="300">
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-700 flex items-center justify-center text-2xl border border-brand-100">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Validasi Berjenjang & Jejak Audit (Audit Trail)</h3>
                                <span class="text-xs font-medium text-slate-500">Pengawasan Mutasi, Status & Catatan Aktivitas</span>
                            </div>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed mb-6">
                            Pemeriksaan data bertahap dari draf pekerja, evaluasi Kepala Lapangan, hingga persetujuan verifikasi transaksi BBM oleh Admin. Setiap aktivitas pengguna tercatat otomatis demi kepatuhan audit internal.
                        </p>
                        <ul class="space-y-2.5 text-xs font-medium text-slate-700">
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Modal persetujuan cepat verifikasi transaksi BBM oleh Admin</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Penguncian otomatis data yang telah disetujui/diverifikasi</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Log riwayat aktivitas pengguna lengkap dengan catatan waktu dan IP</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Modul 4: Ekspor Laporan --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-sm hover:shadow-lg transition-shadow duration-300 flex flex-col justify-between"
                     data-delay="450">
                    <div>
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl border border-emerald-100">
                                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Penyusunan Rekapitulasi Laporan (Excel & PDF)</h3>
                                <span class="text-xs font-medium text-slate-500">Dokumen Arsip & Legalitas BAP Unit</span>
                            </div>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed mb-6">
                            Menerbitkan laporan berkala unit kerja secara otomatis: format rekapitulasi komprehensif 7-Sheet Excel (SPK, realisasi, tim, BBM, biaya, kasbon) serta dokumen Berita Acara Pemeriksaan (BAP) PDF siap tanda tangan.
                        </p>
                        <ul class="space-y-2.5 text-xs font-medium text-slate-700">
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Buku Besar 7-Sheet Excel terstruktur dengan formula otomatis</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Format Berita Acara Pemeriksaan (BAP) PDF resmi</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Fitur penguncian periode bulanan demi menjaga integritas saldo</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>
    </section>


    {{-- =========================================================================
         5. ALUR PROSEDUR KERJA (SOP OPERASIONAL)
         ========================================================================= --}}
    <section id="alur-prosedur" class="py-24 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Section Heading --}}
            <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out text-center max-w-3xl mx-auto mb-16">
                <span class="inline-block px-3.5 py-1 rounded-full bg-brand-50 text-brand-700 text-xs font-bold uppercase tracking-wider mb-3">
                    Standar Operasional Prosedur (SOP)
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Tahapan Pelaksanaan Tugas Lapangan
                </h2>
                <p class="mt-4 text-base sm:text-lg text-slate-600 leading-relaxed">
                    Rangkaian proses kerja resmi yang menghubungkan personil lapangan dengan staf administrasi dan manajemen perusahaan.
                </p>
            </div>

            {{-- 4-Step Horizontal Timeline Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 relative">
                
                {{-- Step 1 --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out relative bg-slate-50 rounded-2xl p-6 border border-slate-200/90 hover:border-brand-500/50 hover:bg-white shadow-sm hover:shadow-md transition-all duration-200"
                     data-delay="0">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl font-black text-brand-200">01</span>
                        <div class="w-9 h-9 rounded-lg bg-brand-100 text-brand-800 flex items-center justify-center font-bold text-sm">
                            <i class="bi bi-sliders"></i>
                        </div>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Persiapan Master & SPK</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Admin memastikan data distrik, petak, tarif, dan jenis BBM telah terkonfigurasi. Kepala Lapangan menerbitkan SPK digital dengan sasaran petak kerja dan menugaskan tim pelaksana.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out relative bg-slate-50 rounded-2xl p-6 border border-slate-200/90 hover:border-brand-500/50 hover:bg-white shadow-sm hover:shadow-md transition-all duration-200"
                     data-delay="150">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl font-black text-teal-200">02</span>
                        <div class="w-9 h-9 rounded-lg bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-sm">
                            <i class="bi bi-airplane-engines"></i>
                        </div>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Pelaksanaan & Realisasi</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Kru lapangan melaksanakan penyemprotan/pemupukan, mencatat capaian luasan hektar riil, mendaftarkan helper warga lokal, dan mencatat rincian konsumsi BBM yang digunakan.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out relative bg-slate-50 rounded-2xl p-6 border border-slate-200/90 hover:border-brand-500/50 hover:bg-white shadow-sm hover:shadow-md transition-all duration-200"
                     data-delay="300">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl font-black text-amber-200">03</span>
                        <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-sm">
                            <i class="bi bi-patch-check"></i>
                        </div>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Verifikasi Lapangan & BBM</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Kepala Lapangan memverifikasi laporan fisik dan lampiran foto pekerjaan. Admin memeriksa bukti nota BBM untuk memvalidasi penambahan atau pemakaian saldo secara akurat.
                    </p>
                </div>

                {{-- Step 4 --}}
                <div class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out relative bg-slate-50 rounded-2xl p-6 border border-slate-200/90 hover:border-brand-500/50 hover:bg-white shadow-sm hover:shadow-md transition-all duration-200"
                     data-delay="450">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl font-black text-emerald-200">04</span>
                        <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Pembukuan & Rekapitulasi</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Perhitungan upah bersih tim setelah dikurangi potongan kasbon berkala, penutupan periode pembukuan bulanan, dan penerbitan laporan 7-Sheet Excel serta berkas BAP PDF.
                    </p>
                </div>

            </div>

        </div>
    </section>


    {{-- =========================================================================
         6. PORTAL MASUK KERJA (INTERNAL ACCESS BANNER)
         ========================================================================= --}}
    <section class="reveal-on-scroll opacity-0 translate-y-10 transition-all duration-700 ease-out py-20 bg-brand-950 relative overflow-hidden text-white">
        <div class="absolute inset-0 z-0 bg-[radial-gradient(circle_at_bottom_left,rgba(16,185,129,0.25),transparent_50%)]"></div>
        
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                Portal Masuk Operasional & Administrasi
            </h2>
            <p class="mt-4 text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Silakan masuk menggunakan akun kerja resmi Anda untuk memulai penerbitan SPK, pengisian laporan penerbangan lapangan, atau verifikasi pembukuan unit kerja.
            </p>
            <div class="mt-8 flex justify-center">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-3 px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 via-teal-500 to-brand-500 hover:from-emerald-400 hover:to-teal-400 text-brand-950 font-bold text-base shadow-xl shadow-emerald-500/30 hover:shadow-emerald-400/50 hover:-translate-y-1 transition-all duration-200">
                    <i class="bi bi-box-arrow-in-right text-xl"></i>
                    <span>Masuk ke Akun Kerja</span>
                </a>
            </div>
        </div>
    </section>


    {{-- =========================================================================
         7. FOOTER RESMI PERUSAHAAN
         ========================================================================= --}}
    <footer class="bg-[#03110b] text-slate-400 text-xs py-12 border-t border-brand-900/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">
                
                {{-- Info Brand --}}
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3.5 mb-4">
                        <img src="{{ asset('storage/images/logo.png') }}" alt="Logo DRONE BERTUAH" class="w-11 h-11 object-contain drop-shadow-md">
                        <div class="flex flex-col">
                            <span class="text-xl font-extrabold tracking-wider text-white leading-none">PANTRACK</span>
                            <span class="text-[10px] font-semibold text-brand-300/80 tracking-wide mt-1 uppercase">PT. Panlibata Andalas Bertuah</span>
                        </div>
                    </div>
                    <p class="text-slate-400 text-xs sm:text-sm leading-relaxed max-w-md">
                        Sistem Informasi Manajemen Monitoring Operasional Drone & Akuntansi Unit Kerja milik <strong>PT. Panlibata Andalas Bertuah</strong>. Dibangun dan dioperasikan khusus untuk pengelolaan operasional internal perusahaan.
                    </p>
                </div>

                {{-- Tautan Navigasi Internal --}}
                <div>
                    <h4 class="text-white font-semibold text-sm mb-3">Navigasi Informasi</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#beranda" class="hover:text-emerald-400 transition-colors">Beranda</a></li>
                        <li><a href="#standar-operasional" class="hover:text-emerald-400 transition-colors">Standar Operasional</a></li>
                        <li><a href="#modul-sistem" class="hover:text-emerald-400 transition-colors">Modul Kerja</a></li>
                        <li><a href="#alur-prosedur" class="hover:text-emerald-400 transition-colors">Alur Prosedur (SOP)</a></li>
                    </ul>
                </div>

                {{-- Akses Masuk & Status --}}
                <div>
                    <h4 class="text-white font-semibold text-sm mb-3">Akses Petugas</h4>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <a href="{{ route('login') }}" class="text-emerald-400 hover:text-emerald-300 font-semibold inline-flex items-center gap-1.5">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span>Portal Masuk Karyawan</span>
                            </a>
                        </li>
                        <li class="text-slate-500">Status Server: <span class="text-emerald-400 font-medium">● Normal Operasional</span></li>
                        <li class="text-slate-500">Lingkungan: Sistem Internal Produksi</li>
                    </ul>
                </div>

            </div>

            <div class="pt-8 border-t border-brand-900/60 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                <div class="flex items-center justify-center sm:justify-start gap-2.5">
                    <img src="{{ asset('storage/images/logo.png') }}" alt="DRONE BERTUAH" class="w-5 h-5 object-contain">
                    <p>&copy; {{ date('Y') }} PT. Panlibata Andalas Bertuah. All rights reserved. (Sistem Operasional Internal).</p>
                </div>
                <p class="text-slate-500">Panlibata Tracking System &bull; Monitoring Pekerjaan, Logistik BBM & Akuntansi Unit</p>
            </div>
        </div>
    </footer>


    {{-- =========================================================================
         8. SCRIPT ANIMASI SCROLL (INTERSECTION OBSERVER API - VANILLA JS)
         ========================================================================= --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const elementsToReveal = document.querySelectorAll('.reveal-on-scroll');
            
            if ('IntersectionObserver' in window) {
                const observerOptions = {
                    root: null,
                    rootMargin: '0px 0px -40px 0px', // Trigger saat elemen mendekati 40px dari bawah viewport
                    threshold: 0.12                   // 12% elemen terlihat untuk memicu animasi
                };

                const scrollObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const target = entry.target;
                            const delayMs = parseInt(target.getAttribute('data-delay') || '0', 10);

                            setTimeout(() => {
                                // Hapus kondisi tersembunyi
                                target.classList.remove('opacity-0', 'translate-y-10');
                                // Terapkan kondisi tampak
                                target.classList.add('opacity-100', 'translate-y-0');
                            }, delayMs);

                            // Unobserve agar animasi HANYA terjadi SATU KALI (tidak berulang saat scroll balik)
                            observer.unobserve(target);
                        }
                    });
                }, observerOptions);

                elementsToReveal.forEach(el => scrollObserver.observe(el));
            } else {
                // Fallback untuk browser lawas tanpa dukungan IntersectionObserver
                elementsToReveal.forEach(el => {
                    el.classList.remove('opacity-0', 'translate-y-10');
                    el.classList.add('opacity-100', 'translate-y-0');
                });
            }
        });
    </script>

</body>
</html>
