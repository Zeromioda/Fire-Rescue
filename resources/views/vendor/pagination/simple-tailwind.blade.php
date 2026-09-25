@php
    $btn = 'inline-flex items-center justify-center rounded-3xl border px-4 py-2 text-sm font-semibold transition';
    $idle = $btn.' border-border bg-card/95 text-foreground hover:bg-card-alt';
    $disabled = $btn.' cursor-not-allowed border-border bg-card/95 text-muted-foreground opacity-50';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex items-center justify-between gap-2 rounded-3xl border border-border bg-card/95 p-4 shadow-2xl backdrop-blur-xl sm:px-6">
        @if ($paginator->onFirstPage())
            <span class="{{ $disabled }}" aria-disabled="true">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }}">Previous</a>
        @endif

        <span class="text-sm tabular-nums text-muted-foreground">Page {{ $paginator->currentPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }}">Next</a>
        @else
            <span class="{{ $disabled }}" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
