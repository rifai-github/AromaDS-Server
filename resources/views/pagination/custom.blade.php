@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="btn disabled">
                    <i class="fas fa-chevron-left"></i>
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn">
                    <i class="fas fa-chevron-left"></i>
                    Sebelumnya
                </a>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn">
                    Selanjutnya
                    <i class="fas fa-chevron-right"></i>
                </a>
            @else
                <span class="btn disabled">
                    Selanjutnya
                    <i class="fas fa-chevron-right"></i>
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="btn disabled">
                        <i class="fas fa-chevron-left"></i>
                        Sebelumnya
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="btn">
                        <i class="fas fa-chevron-left"></i>
                        Sebelumnya
                    </a>
                @endif
            </div>

            <div class="page-numbers">
                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="ellipsis">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="btn active">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            <div>
                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="btn">
                        Selanjutnya
                        <i class="fas fa-chevron-right"></i>
                    </a>
                @else
                    <span class="btn disabled">
                        Selanjutnya
                        <i class="fas fa-chevron-right"></i>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
