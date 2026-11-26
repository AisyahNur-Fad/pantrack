{{-- Pesan flash (sukses, galat, info) dan ringkasan galat validasi. --}}
@if (session('sukses'))
    <div class="notifikasi notifikasi--sukses" role="status">
        <i class="bi bi-check-circle-fill"></i>
        <div class="notifikasi__isi">{{ session('sukses') }}</div>
        <button type="button" class="notifikasi__tutup" data-notifikasi-tutup aria-label="Tutup">&times;</button>
    </div>
@endif

@if (session('info'))
    <div class="notifikasi notifikasi--info" role="status">
        <i class="bi bi-info-circle-fill"></i>
        <div class="notifikasi__isi">{{ session('info') }}</div>
        <button type="button" class="notifikasi__tutup" data-notifikasi-tutup aria-label="Tutup">&times;</button>
    </div>
@endif

@if (session('galat') || session('gagal'))
    <div class="notifikasi notifikasi--galat" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <div class="notifikasi__isi">{{ session('galat') ?? session('gagal') }}</div>
        <button type="button" class="notifikasi__tutup" data-notifikasi-tutup aria-label="Tutup">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="notifikasi notifikasi--galat" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div class="notifikasi__isi">
            <strong>Data belum dapat disimpan.</strong> Periksa isian yang ditandai merah:
            <ul>
                @foreach ($errors->all() as $galat)
                    <li>{{ $galat }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="notifikasi__tutup" data-notifikasi-tutup aria-label="Tutup">&times;</button>
    </div>
@endif
