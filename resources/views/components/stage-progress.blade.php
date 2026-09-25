@props(['incident'])

@php($current = $incident->stageIndex())

<ol class="grid grid-cols-5 gap-1.5">
    @foreach (\App\Models\Incident::STAGES as $i => $stage)
        @php($at = $incident->milestone($stage))
        <li class="min-w-0">
            <div class="h-1.5 rounded-full {{ $i <= $current ? 'bg-primary' : 'bg-card-alt' }}"></div>
            <p class="mt-1.5 truncate text-[11px] font-medium {{ $i <= $current ? 'text-foreground' : 'text-muted-foreground' }}">{{ $stage }}</p>
            <p class="truncate text-[11px] tabular-nums text-muted-foreground">{{ $at ? $at->timezone('Asia/Manila')->format('g:i A') : '—' }}</p>
        </li>
    @endforeach
</ol>
