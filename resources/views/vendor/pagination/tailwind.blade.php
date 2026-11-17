@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-4 text-xs">
        <div class="text-slate-500 font-medium">
            Menampilkan
            @if ($paginator->firstItem())
                <span class="font-bold text-slate-700">{{ $paginator->firstItem() }}</span>
                -
                <span class="font-bold text-slate-700">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            dari total
            <span class="font-bold text-slate-800">{{ $paginator->total() }}</span> data
        </div>

        <div class="inline-flex items-center gap-1.5 bg-slate-50/80 p-1 rounded-xl border border-slate-200/80 shadow-2xs">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <span class="px-2.5 py-1.5 rounded-lg text-slate-300 cursor-not-allowed flex items-center justify-center">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white transition flex items-center justify-center" aria-label="{{ __('pagination.previous') }}">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true">
                        <span class="px-2.5 py-1 text-slate-400 font-semibold">{{ $element }}</span>
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">
                                <span class="px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold shadow-2xs">{{ $page }}</span>
                            </span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white font-semibold transition" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white transition flex items-center justify-center" aria-label="{{ __('pagination.next') }}">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <span class="px-2.5 py-1.5 rounded-lg text-slate-300 cursor-not-allowed flex items-center justify-center">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </span>
                </span>
            @endif
        </div>
    </nav>
@endif
