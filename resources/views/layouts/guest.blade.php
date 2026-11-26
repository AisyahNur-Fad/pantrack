<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    @stack('gaya')
</head>
<body>
    {{-- Layout tamu (belum login), dipakai halaman login. --}}
    @yield('konten')

    <script src="{{ asset('js/pantrack.js') }}?v={{ filemtime(public_path('js/pantrack.js')) }}" defer></script>
    @stack('skrip')
</body>
</html>
