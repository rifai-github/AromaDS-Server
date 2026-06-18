@php
    // Allowed per-page options (max 100)
    $perPageOptions = [25, 50, 100];
    $currentPerPage = (int) $paginator->perPage();
    if (!in_array($currentPerPage, $perPageOptions, true)) {
        // include the current value so the selector still reflects reality
        $perPageOptions[] = $currentPerPage;
        sort($perPageOptions);
    }
    // Base query params (without page/per_page) so we can rebuild URLs
    $baseQuery = collect(request()->query())->except(['page', 'per_page'])->toArray();
    $pageParam = method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page';
    $buildUrl = function ($page, $perPage) use ($paginator, $baseQuery, $pageParam) {
        $params = array_merge($baseQuery, [$pageParam => $page, 'per_page' => $perPage]);
        return $paginator->path() . '?' . http_build_query($params);
    };
    $pjId = 'pjump_' . substr(md5($paginator->path() . $pageParam), 0, 6);
@endphp

@if ($paginator->total() > 0)
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="pagination-bar flex flex-wrap items-center justify-between gap-3 w-full text-sm">

        {{-- Left: per-page selector + info --}}
        <div class="flex items-center gap-2 text-gray-600">
            <span>Tampilkan</span>
            <select onchange="window.location.href = this.value"
                    class="bg-gray-100 rounded-lg px-2 py-1 border border-gray-300 focus:outline-none focus:border-[#214589]">
                @foreach ($perPageOptions as $opt)
                    <option value="{{ $buildUrl(1, $opt) }}" {{ $currentPerPage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <span class="hidden sm:inline">
                ({{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }})
            </span>
        </div>

        {{-- Center: prev / page numbers / next --}}
        <div class="flex items-center gap-2">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm disabled" aria-disabled="true">
                    <i class="fas fa-chevron-left"></i> Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-chevron-left"></i> Sebelumnya
                </a>
            @endif

            {{-- Page numbers --}}
            <div class="page-numbers flex items-center gap-1">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="ellipsis text-gray-400 px-1">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="page-number active">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="page-number">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-secondary btn-sm">
                    Selanjutnya <i class="fas fa-chevron-right"></i>
                </a>
            @else
                <span class="btn btn-secondary btn-sm disabled" aria-disabled="true">
                    Selanjutnya <i class="fas fa-chevron-right"></i>
                </span>
            @endif
        </div>

        {{-- Right: jump to page --}}
        <div class="flex items-center gap-2 text-gray-600">
            <span>Halaman</span>
            <input type="number" min="1" max="{{ $paginator->lastPage() }}"
                   value="{{ $paginator->currentPage() }}"
                   id="{{ $pjId }}_input"
                   class="w-16 bg-gray-100 rounded-lg px-2 py-1 border border-gray-300 text-center focus:outline-none focus:border-[#214589]"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('{{ $pjId }}_go').click();}">
            <span>dari {{ $paginator->lastPage() }}</span>
            <button type="button" id="{{ $pjId }}_go" class="btn btn-secondary btn-sm"
                    data-base="{{ $buildUrl('__PAGE__', $currentPerPage) }}"
                    data-last="{{ $paginator->lastPage() }}"
                    onclick="(function(b){
                        var v = parseInt(document.getElementById('{{ $pjId }}_input').value, 10);
                        var last = parseInt(b.getAttribute('data-last'), 10);
                        if(isNaN(v) || v < 1){ v = 1; }
                        if(v > last){ v = last; }
                        window.location.href = b.getAttribute('data-base').replace('__PAGE__', v);
                    })(this)">Go</button>
        </div>
    </nav>
@endif
