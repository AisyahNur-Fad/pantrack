{{-- Bagian <head> bersama untuk semua layout: meta, font, ikon, dan satu-satunya file CSS aplikasi. --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="PANTRACK — sistem monitoring realisasi pekerjaan drone agrikultur PT. Panlibata Andalas Bertuah.">
<meta name="robots" content="noindex, nofollow">

<title>@yield('judul', 'PANTRACK') — PANTRACK</title>

{{-- Favicon --}}
<link rel="icon" type="image/png" href="{{ asset('storage/images/logo.png') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/pantrack.css') }}?v={{ filemtime(public_path('css/pantrack.css')) }}">

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Inisialisasi instan sidebar collapsible untuk mencegah flicker saat refresh --}}
<script>
    (function () {
        try {
            if (localStorage.getItem('pantrack_sidebar_ciut') === '1' && window.innerWidth >= 992) {
                document.documentElement.classList.add('sidebar-menciut');
            }
        } catch (e) {}
    })();
</script>
