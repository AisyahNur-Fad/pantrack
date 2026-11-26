<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
    {{-- Layout halaman galat (403, 404, 419, 500, 503). Variabel: $kode, $judulGalat, $pesanGalat. --}}
    <div class="galat-halaman">
        <div class="galat-halaman__kartu">
            <div class="galat-halaman__kode">{{ $kode }}</div>
            <h1>{{ $judulGalat }}</h1>
            <p>{{ $pesanGalat }}</p>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="tombol tombol--utama">
                <i class="bi bi-house"></i> Kembali ke beranda
            </a>
        </div>
    </div>
</body>
</html>
