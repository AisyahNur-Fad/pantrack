{{-- Sidebar. Variabel $menu disiapkan oleh App\View\Composers\MenuComposer (sudah difilter per peran). --}}
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <div class="sidebar__kepala">
        <a href="{{ route('dashboard') }}" class="sidebar__merek">
            <span class="sidebar__logo">@include('partials.logo')</span>
            <span class="sidebar__nama">PANTRACK
                <span class="sidebar__sub">Panlibata Tracking System</span>
            </span>
        </a>
    </div>

    <nav class="sidebar__nav">
        @foreach ($menu as $grup)
            <div class="sidebar__judul">{{ $grup['judul'] }}</div>

            @php $prevSubGrup = null; @endphp
            @foreach ($grup['item'] as $item)
                @if (!empty($item['sub_grup']) && $item['sub_grup'] !== $prevSubGrup)
                    @php $prevSubGrup = $item['sub_grup']; @endphp
                    <div class="sidebar__sub-judul">
                        <span class="sidebar__sub-judul-teks">{{ $item['sub_grup'] }}</span>
                    </div>
                @endif

                <a href="{{ route($item['route']) }}"
                   class="sidebar__tautan {{ request()->routeIs($item['pola']) ? 'sidebar__tautan--aktif' : '' }} {{ !empty($item['sub_item']) ? 'sidebar__tautan--sub' : '' }}"
                   title="{{ $item['label'] }}"
                   @if (request()->routeIs($item['pola'])) aria-current="page" @endif>
                    @if (!empty($item['sub_item']))
                        <span class="sidebar__cabang-ikon" aria-hidden="true">
                            <i class="bi bi-arrow-return-right"></i>
                        </span>
                    @endif
                    <i class="bi {{ $item['ikon'] }}"></i>
                    <span class="sidebar__tautan-label" style="flex: 1;">{{ $item['label'] }}</span>
                    @if (!empty($item['badge']) && $item['badge'] > 0)
                        <span class="sidebar__badge">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sidebar__promo">
        <div class="sidebar__promo-ikon"><i class="bi bi-crosshair"></i></div>
        <div class="sidebar__promo-judul">Pantau Presisi Drone</div>
        <div class="sidebar__promo-sub">Operasional petak sawit dan BBM tersinkronisasi presisi.</div>
    </div>

    <div class="sidebar__kaki">{{ config('pantrack.perusahaan') }}</div>
</aside>
<div class="lapisan-sidebar" data-sidebar-tutup></div>
