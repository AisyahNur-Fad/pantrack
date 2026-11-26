@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav class="paginasi" role="navigation" aria-label="Navigasi halaman">
        <div>
            Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</strong>
            dari <strong>{{ $paginator->total() }}</strong> data
        </div>

        @if ($paginator->hasPages())
            <ul class="paginasi__daftar">
                {{-- Sebelumnya --}}
                @if ($paginator->onFirstPage())
                    <li class="paginasi__item paginasi__item--mati"><span><i class="bi bi-chevron-left"></i></span></li>
                @else
                    <li class="paginasi__item"><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></a></li>
                @endif

                {{-- Nomor halaman --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="paginasi__item paginasi__item--mati"><span>{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $halaman => $url)
                            @if ($halaman == $paginator->currentPage())
                                <li class="paginasi__item paginasi__item--aktif" aria-current="page"><span>{{ $halaman }}</span></li>
                            @else
                                <li class="paginasi__item"><a href="{{ $url }}">{{ $halaman }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Berikutnya --}}
                @if ($paginator->hasMorePages())
                    <li class="paginasi__item"><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></a></li>
                @else
                    <li class="paginasi__item paginasi__item--mati"><span><i class="bi bi-chevron-right"></i></span></li>
                @endif
            </ul>
        @endif
    </nav>
@endif
