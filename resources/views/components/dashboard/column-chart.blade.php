@props([
    'data',             // list of ['label' => string, 'tip' => string, 'value' => int]
    'unit' => 'call',
    'labelEvery' => 1,  // show every Nth x-axis label
    'height' => 'h-48',
])

@php
    $max = max(1, collect($data)->max('value'));
    $step = (int) ceil($max / 4);
    $top = $step * 4;
    $peak = collect($data)->search(fn ($d) => $d['value'] === collect($data)->max('value') && $d['value'] > 0);
@endphp

<div {{ $attributes->merge(['class' => 'flex gap-3 pt-5']) }}>
    <!-- Y axis ticks -->
    <div class="relative {{ $height }} w-6 shrink-0 text-right text-[11px] tabular-nums text-muted-foreground">
        @foreach ([4, 3, 2, 1, 0] as $t)
            <span class="absolute right-0 -translate-y-1/2" style="top: {{ (4 - $t) * 25 }}%">{{ $t * $step }}</span>
        @endforeach
    </div>

    <div class="min-w-0 flex-1">
        <div class="relative {{ $height }}">
            <!-- Gridlines -->
            @foreach ([0, 25, 50, 75] as $y)
                <div class="absolute inset-x-0 border-t border-border/70" style="top: {{ $y }}%"></div>
            @endforeach
            <div class="absolute inset-x-0 bottom-0 border-t border-muted-foreground/40"></div>

            <!-- Columns: the whole slot is the hover target -->
            <div class="absolute inset-0 flex items-end gap-[2px]">
                @foreach ($data as $i => $d)
                    <div class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end"
                         data-tip="{{ $d['tip'] }}" data-tip-value="{{ $d['value'] }} {{ \Illuminate\Support\Str::plural($unit, $d['value']) }}">
                        @if ($i === $peak)
                            <span class="mb-1 shrink-0 text-[11px] font-semibold tabular-nums text-foreground">{{ $d['value'] }}</span>
                        @endif
                        <div class="w-full max-w-[24px] shrink-0 rounded-t bg-primary transition group-hover:bg-primary/80"
                             style="height: {{ $d['value'] ? max(2, $d['value'] / $top * 100) : 0 }}%"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- X axis labels -->
        <div class="mt-2 flex h-4 gap-[2px] text-[11px] text-muted-foreground">
            @foreach ($data as $i => $d)
                <span class="relative min-w-0 flex-1">
                    @if ($i % $labelEvery === 0)
                        <span class="absolute left-1/2 -translate-x-1/2 whitespace-nowrap">{{ $d['label'] }}</span>
                    @endif
                </span>
            @endforeach
        </div>
    </div>
</div>
