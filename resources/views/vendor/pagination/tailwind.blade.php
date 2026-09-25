@php
    $btn = 'inline-flex min-w-10 items-center justify-center rounded-3xl border px-4 py-2 text-sm font-semibold transition';
    $idle = $btn.' border-border bg-card/95 text-foreground hover:bg-card-alt';
    $active = $btn.' border-primary/20 bg-primary text-primary-foreground shadow-lg shadow-primary/20';
    $disabled = $btn.' cursor-not-allowed border-border bg-card/95 text-muted-foreground opacity-50';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-col gap-4 rounded-3xl border border-border bg-card/95 p-4 shadow-2xl backdrop-blur-xl sm:flex-row sm:items-center sm:justify-between sm:px-6">

        <p class="text-center text-sm text-muted-foreground sm:text-left">
            @if ($paginator->firstItem())
                Showing <span class="font-semibold tabular-nums text-foreground">{{ $paginator->firstItem() }}</span>–<span class="font-semibold tabular-nums text-foreground">{{ $paginator->lastItem() }}</span>
                of <span class="font-semibold tabular-nums text-foreground">{{ $paginator->total() }}</span>
            @else
                {{ $paginator->count() }} results
            @endif
        </p>

        <div class="flex items-center justify-between gap-2 sm:justify-end">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }}" aria-disabled="true">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }}">Previous</a>
            @endif

            {{-- Page numbers (tablet and up) --}}
            <div class="hidden items-center gap-1.5 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-sm text-muted-foreground" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $active }} tabular-nums" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $idle }} tabular-nums" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Page position (phones) --}}
            <span class="text-sm tabular-nums text-muted-foreground sm:hidden">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </span>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }}">Next</a>
            @else
                <span class="{{ $disabled }}" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
