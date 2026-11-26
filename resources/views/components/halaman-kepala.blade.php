{{-- Kepala halaman: <x-halaman-kepala judul="..." deskripsi="..." ikon="bi-..."> tombol aksi </x-halaman-kepala> --}}
@props(['judul', 'deskripsi' => null, 'ikon' => null])

<div class="halaman-kepala flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="halaman-kepala__judul flex items-center gap-3.5">
        @if ($ikon)
            <div class="halaman-kepala__ikon w-11 h-11 shrink-0 flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 text-xl shadow-xs">
                <i class="bi {{ $ikon }}"></i>
            </div>
        @endif
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 leading-tight">{{ $judul }}</h1>
            @if ($deskripsi)
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl leading-normal">{{ $deskripsi }}</p>
            @endif
        </div>
    </div>

    @if ($slot->isNotEmpty())
        <div class="halaman-kepala__aksi flex flex-wrap items-center gap-2.5">{{ $slot }}</div>
    @endif
</div>
