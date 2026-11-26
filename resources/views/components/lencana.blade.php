{{-- Lencana status soft-pill modern PANTRACK --}}
@props(['warna' => 'netral', 'teks' => null])

@php
    $petaWarna = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'hijau' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'emas' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'merah' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'biru' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'ungu' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'netral' => 'bg-slate-100 text-slate-700 ring-slate-500/20',
        'abu' => 'bg-slate-100 text-slate-700 ring-slate-500/20',
    ];
    $gaya = $petaWarna[$warna] ?? 'bg-slate-100 text-slate-700 ring-slate-500/20';
@endphp

<span class="lencana lencana--{{ $warna }} inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $gaya }} transition-colors">
    <span class="w-1.5 h-1.5 rounded-full bg-current shrink-0"></span>
    <span>{{ $teks ?? $slot }}</span>
</span>
