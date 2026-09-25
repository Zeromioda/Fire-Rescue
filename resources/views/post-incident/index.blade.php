@php
    $card = 'rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl';
    $input = 'w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30';
    $tz = 'Asia/Manila';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-foreground">{{ __('Post-Incident Reporting') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">File after-action reports and review the AI-summarised incident archive.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-4">
                @foreach ([
                    ['Awaiting Report', $counts['pending'], 'text-rose-600 dark:text-rose-400'],
                    ['Reports Filed', $counts['filed'], 'text-foreground'],
                    ['AI Summaries', $counts['with_ai'], 'text-primary'],
                    ['Reports with Casualties', $counts['casualty_reports'], 'text-amber-600 dark:text-amber-400'],
                ] as [$label, $value, $tone])
                    <div class="{{ $card }}">
                        <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">{{ $label }}</span>
                        <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums {{ $tone }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="inline-flex rounded-3xl border border-border bg-card/95 p-1 text-sm font-semibold">
                    <a href="{{ route('post-incident.index', ['tab' => 'pending']) }}" class="rounded-3xl px-5 py-2 transition {{ $tab === 'pending' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground' }}">Awaiting Report ({{ $counts['pending'] }})</a>
                    <a href="{{ route('post-incident.index', ['tab' => 'filed']) }}" class="rounded-3xl px-5 py-2 transition {{ $tab === 'filed' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground' }}">Filed Reports ({{ $counts['filed'] }})</a>
                </div>
                <form method="GET" class="flex gap-2 md:w-96">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="search" name="q" value="{{ $term }}" placeholder="Search incidents…" class="{{ $input }}">
                </form>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                @forelse ($incidents as $incident)
                    <div class="{{ $card }} flex flex-col gap-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $incident->severityClasses() }}">{{ $incident->severity }}</span>
                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ \App\Models\Incident::stageClasses($incident->stage()) }}">{{ $incident->stage() }}</span>
                                    <span class="text-xs text-muted-foreground">{{ $incident->category }}</span>
                                </div>
                                <h3 class="mt-2 font-semibold tracking-tight">{{ $incident->title }}</h3>
                                <p class="truncate text-xs text-muted-foreground">{{ $incident->location_address }}</p>
                            </div>
                            <p class="shrink-0 text-right text-xs tabular-nums text-muted-foreground">
                                {{ $incident->created_at->timezone($tz)->format('M d, Y') }}<br>{{ $incident->apparatuses_count }} units · {{ $incident->personnel_count }} crew
                            </p>
                        </div>

                        @if ($incident->ai_summary)
                            <div class="line-clamp-5 whitespace-pre-line rounded-2xl bg-card-alt p-4 text-xs leading-relaxed">{{ $incident->ai_summary }}</div>
                        @elseif ($incident->after_action_report)
                            <p class="line-clamp-4 rounded-2xl bg-card-alt p-4 text-xs leading-relaxed">{{ $incident->after_action_report }}</p>
                        @else
                            <p class="rounded-2xl bg-rose-500/5 p-4 text-xs text-rose-600 dark:text-rose-400">No after-action report has been filed for this incident yet.</p>
                        @endif

                        <div class="mt-auto flex justify-end">
                            <a href="{{ route('post-incident.show', $incident) }}" class="inline-flex items-center justify-center rounded-3xl border px-5 py-2 text-sm font-semibold transition {{ $incident->after_action_report ? 'border-border bg-card/95 text-foreground hover:bg-card-alt' : 'border-primary/20 bg-primary text-primary-foreground hover:bg-primary/90' }}">
                                {{ $incident->after_action_report ? 'Open Report' : 'File Report' }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="{{ $card }} py-12 text-center lg:col-span-2">
                        <p class="text-sm text-muted-foreground">{{ $tab === 'pending' ? 'Every closed incident has an after-action report.' : 'No reports match your search.' }}</p>
                    </div>
                @endforelse
            </div>

            {{ $incidents->links() }}
        </div>
    </div>
</x-app-layout>
