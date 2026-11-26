{{-- Keadaan kosong data (Empty State) PANTRACK Modern --}}
@props(['ikon' => 'bi-inbox', 'judul' => 'Belum ada data', 'rapat' => false])

<div class="kosong {{ $rapat ? 'kosong--rapat py-6 px-4' : 'py-10 sm:py-14 px-6' }} flex flex-col items-center justify-center text-center">
    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-slate-100 border border-slate-200/80 text-slate-400 flex items-center justify-center text-xl sm:text-2xl mb-3 shrink-0">
        <i class="bi {{ $ikon }}"></i>
    </div>
    <strong class="text-sm font-semibold text-slate-800 mb-1">{{ $judul }}</strong>
    @if ($slot->isNotEmpty())
        <div class="text-xs text-slate-500 max-w-sm leading-relaxed">{{ $slot }}</div>
    @endif
</div>
