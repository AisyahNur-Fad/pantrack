<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    @stack('gaya')
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans min-h-screen selection:bg-emerald-500 selection:text-white">
    {{--
        Kerangka halaman setelah login. Setiap halaman cukup:
            @extends('layouts.app')
            @section('judul', 'Nama Halaman')
            @section('konten') ... @endsection
        Bagian kerangka diedit pada file partial masing-masing:
            sidebar -> partials/sidebar.blade.php
            navbar  -> partials/navbar.blade.php
            footer  -> partials/footer.blade.php
    --}}
    <div class="app min-h-screen bg-slate-50">
        @include('partials.sidebar')

        <div class="app__utama">
            @include('partials.navbar')

            <main class="app__konten">
                @include('partials.notifikasi')
                @yield('konten')
            </main>

            @include('partials.footer')
        </div>
    </div>

    @include('partials.dialog-konfirmasi')

    <script src="{{ asset('js/pantrack.js') }}?v={{ filemtime(public_path('js/pantrack.js')) }}" defer></script>
    @stack('skrip')
</body>
</html>
