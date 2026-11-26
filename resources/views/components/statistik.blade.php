{{-- Kartu statistik KPI PANTRACK Modern --}}
@props(['label', 'nilai', 'ikon', 'warna' => 'hijau', 'satuan' => null])

@php
    $petaIkon = [
        'hijau' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'success' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'aksen' => 'bg-amber-50 text-amber-600 border-amber-100',
        'warning' => 'bg-amber-50 text-amber-600 border-amber-100',
        'emas' => 'bg-amber-50 text-amber-600 border-amber-100',
        'biru' => 'bg-sky-50 text-sky-600 border-sky-100',
        'info' => 'bg-sky-50 text-sky-600 border-sky-100',
        'merah' => 'bg-rose-50 text-rose-600 border-rose-100',
        'danger' => 'bg-rose-50 text-rose-600 border-rose-100',
        'abu' => 'bg-slate-100 text-slate-600 border-slate-200',
        'netral' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $gayaIkon = $petaIkon[$warna] ?? 'bg-emerald-50 text-emerald-600 border-emerald-100';
@endphp

<div class="statistik statistik--{{ $warna }} bg-white border border-slate-200/80 rounded-xl p-4 sm:p-5 shadow-xs hover:shadow-sm hover:border-slate-300 transition-all duration-150 flex items-center gap-4">
    <div class="statistik__ikon w-12 h-12 rounded-xl flex items-center justify-center text-xl shrink-0 border {{ $gayaIkon }}">
        <i class="bi {{ $ikon }}"></i>
    </div>
    <div class="min-w-0 flex-1">
        <div class="statistik__nilai text-xl sm:text-2xl font-bold tracking-tight text-slate-900 leading-tight">
            {{ $nilai }}@if ($satuan)<small class="text-xs font-medium text-slate-500 ml-1">{{ $satuan }}</small>@endif
        </div>
        <div class="statistik__label text-xs font-medium text-slate-500 mt-1 truncate">{{ $label }}</div>
    </div>
</div>
