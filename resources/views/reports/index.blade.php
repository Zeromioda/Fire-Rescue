<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('AI Analysis & Reports') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    BFAD Station 178 · Incident intelligence summary
                </p>
            </div>
            <div class="no-print flex flex-wrap items-center gap-3">
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-6 py-3 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                    Print
                </button>
                <form method="POST" action="{{ route('reports.generate') }}" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <button type="submit" :disabled="busy || {{ ! $aiReady || $stats['total'] === 0 ? 'true' : 'false' }}"
                            class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-show="!busy">{{ $report && $report->period === $period ? 'Regenerate' : 'Generate' }} AI Analysis</span>
                        <span x-show="busy" x-cloak>Analysing incidents…</span>
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <style>
        [x-cloak] { display: none !important; }
        @media print {
            .no-print, aside, .lg\:hidden { display: none !important; }
            main, body { background: #fff !important; }
            .print-card { break-inside: avoid; box-shadow: none !important; }
        }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="no-print rounded-3xl border border-border bg-card/95 p-5 text-sm font-medium text-emerald-600 shadow-2xl backdrop-blur-xl dark:text-emerald-400">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="no-print rounded-3xl border border-border bg-card/95 p-5 text-sm font-medium text-destructive shadow-2xl backdrop-blur-xl">{{ session('error') }}</div>
            @endif
            @unless ($aiReady)
                <div class="no-print rounded-3xl border border-border bg-card/95 p-5 text-sm font-medium text-amber-600 shadow-2xl backdrop-blur-xl dark:text-amber-400">
                    AI analysis is not configured yet. Set <span class="font-semibold">AI_API_KEY</span> to enable report generation.
                </div>
            @endunless

            <!-- Period Filter -->
            <div class="no-print flex flex-wrap gap-3">
                @foreach ($periods as $key => $label)
                    <a href="{{ route('reports.index', ['period' => $key]) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-3xl border px-5 py-2.5 text-sm font-semibold shadow-2xl backdrop-blur-xl transition {{ $period === (string) $key ? 'border-primary/20 bg-primary text-primary-foreground hover:bg-primary/90' : 'border-border bg-card/95 text-foreground hover:bg-card-alt' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                Reporting period: {{ $periods[$period] }}
                ({{ $start ? $start->timezone($timezone)->format('M d, Y') : 'All records' }} – {{ $end->timezone($timezone)->format('M d, Y') }})
            </p>

            <!-- Stat Cards -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                <div class="print-card rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Total Incidents</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-foreground">{{ $stats['total'] }}</p>
                </div>
                <div class="print-card rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Active / Open</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-amber-600 dark:text-amber-400">{{ $stats['active'] }}</p>
                </div>
                <div class="print-card rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Resolved</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-emerald-600 dark:text-emerald-400">{{ $stats['resolved'] }}</p>
                </div>
                <div class="print-card rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Resolution Rate</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-primary">{{ $stats['resolution_rate'] }}%</p>
                </div>
                <div class="print-card rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl sm:col-span-2 lg:col-span-1">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Avg. Time to Resolve</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-foreground">
                        {{ $stats['avg_resolution_hours'] !== null ? $stats['avg_resolution_hours'].'h' : '—' }}
                    </p>
                </div>
            </div>

            <!-- Breakdowns -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ([
                    'Severity Breakdown' => ['data' => $stats['by_severity'], 'bar' => 'bg-rose-500'],
                    'Status Breakdown' => ['data' => $stats['by_status'], 'bar' => 'bg-amber-500'],
                    'Incident Categories' => ['data' => $stats['by_category'], 'bar' => 'bg-sky-500'],
                ] as $title => $block)
                    <div class="print-card space-y-4 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                        <h3 class="text-lg font-semibold tracking-tight text-foreground">{{ $title }}</h3>
                        @forelse ($block['data'] as $label => $count)
                            <div>
                                <div class="mb-1.5 flex justify-between text-sm">
                                    <span class="font-medium text-foreground">{{ $label }}</span>
                                    <span class="tabular-nums text-muted-foreground">{{ $count }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-card-alt">
                                    <div class="h-full rounded-full {{ $block['bar'] }}" style="width: {{ $stats['total'] ? round($count / $stats['total'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">No data.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="print-card space-y-4 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl lg:col-span-2">
                    <h3 class="text-lg font-semibold tracking-tight text-foreground">Top Incident Locations</h3>
                    <div class="divide-y divide-border">
                        @forelse ($stats['top_locations'] as $location => $count)
                            <div class="flex justify-between gap-4 py-3 text-sm">
                                <span class="min-w-0 break-words text-foreground">{{ $location }}</span>
                                <span class="shrink-0 font-semibold tabular-nums text-primary">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">No data.</p>
                        @endforelse
                    </div>
                </div>
                <div class="print-card space-y-4 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <h3 class="text-lg font-semibold tracking-tight text-foreground">Activity Patterns</h3>
                    <div class="divide-y divide-border">
                        <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-muted-foreground">Busiest hour</span><span class="font-semibold tabular-nums text-foreground">{{ $stats['busiest_hour'] ?? '—' }}</span></div>
                        <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-muted-foreground">Busiest day</span><span class="font-semibold text-foreground">{{ $stats['busiest_day'] ?? '—' }}</span></div>
                        <div class="flex justify-between gap-4 py-3 text-sm"><span class="text-muted-foreground">After-action reports filed</span><span class="font-semibold tabular-nums text-foreground">{{ $stats['with_after_action_report'] }} / {{ $stats['total'] }}</span></div>
                    </div>
                </div>
            </div>

            <!-- AI Analysis -->
            <div class="print-card space-y-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-semibold tracking-tight text-foreground">AI Command Analysis</h3>
                    @if ($report)
                        <span class="text-xs text-muted-foreground">
                            {{ $periods[$report->period] ?? $report->period }} · <span class="tabular-nums">{{ $report->incident_count }}</span> incidents ·
                            Generated {{ $report->created_at->timezone($timezone)->format('M d, Y h:i A') }}
                            @if ($report->generatedBy) by {{ $report->generatedBy->name }} @endif
                        </span>
                    @endif
                </div>

                @if ($report)
                    @php($a = $report->analysis)

                    @if ($report->period !== $period)
                        <p class="no-print text-sm font-medium text-amber-600 dark:text-amber-400">You are viewing a saved report from a different period than the statistics above.</p>
                    @endif

                    <section class="space-y-2">
                        <h4 class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Executive Summary</h4>
                        <p class="text-sm leading-relaxed text-foreground">{{ $a['executive_summary'] }}</p>
                    </section>

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <section class="space-y-2">
                            <h4 class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Key Findings</h4>
                            <ul class="list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-foreground">
                                @forelse ($a['key_findings'] as $item)
                                    <li>{{ $item }}</li>
                                @empty
                                    <li class="-ml-5 list-none text-muted-foreground">None identified.</li>
                                @endforelse
                            </ul>
                        </section>

                        <section class="space-y-2">
                            <h4 class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Risk Patterns</h4>
                            <ul class="list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-foreground">
                                @forelse ($a['risk_patterns'] as $item)
                                    <li>{{ $item }}</li>
                                @empty
                                    <li class="-ml-5 list-none text-muted-foreground">No recurring patterns supported by the data.</li>
                                @endforelse
                            </ul>
                        </section>
                    </div>

                    @if ($a['operational_performance'])
                        <section class="space-y-2">
                            <h4 class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Operational Performance</h4>
                            <p class="text-sm leading-relaxed text-foreground">{{ $a['operational_performance'] }}</p>
                        </section>
                    @endif

                    <section class="space-y-3">
                        <h4 class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Recommendations</h4>
                        <div class="space-y-3">
                            @forelse ($a['recommendations'] as $rec)
                                @php($tone = ['High' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400', 'Medium' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400', 'Low' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400'][$rec['priority']])
                                <div class="flex flex-col items-start gap-3 rounded-2xl bg-card-alt p-4 sm:flex-row">
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-medium {{ $tone }}">{{ $rec['priority'] }}</span>
                                    <p class="text-sm leading-relaxed text-foreground">{{ $rec['action'] }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-muted-foreground">No recommendations.</p>
                            @endforelse
                        </div>
                    </section>

                    @if (count($a['data_limitations']))
                        <section class="space-y-2">
                            <h4 class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Data Limitations</h4>
                            <ul class="list-disc space-y-1 pl-5 text-sm leading-relaxed text-muted-foreground">
                                @foreach ($a['data_limitations'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    <p class="border-t border-border pt-4 text-xs text-muted-foreground">
                        AI-generated analysis based only on recorded incident data. Verify critical figures before official use.
                    </p>
                @else
                    <div class="space-y-2 py-10 text-center">
                        <p class="text-lg font-semibold tracking-tight text-foreground">No AI analysis for this period yet</p>
                        <p class="text-sm text-muted-foreground">
                            @if ($stats['total'] === 0)
                                There are no incidents recorded in this period.
                            @else
                                Click <span class="font-semibold text-foreground">Generate AI Analysis</span> to summarise {{ $stats['total'] }} incident{{ $stats['total'] === 1 ? '' : 's' }}.
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <!-- Report History -->
            @if ($history->isNotEmpty())
                <div class="no-print space-y-4 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <h3 class="text-lg font-semibold tracking-tight text-foreground">Saved Reports</h3>
                    <div class="divide-y divide-border">
                        @foreach ($history as $item)
                            <a href="{{ route('reports.index', ['period' => $item->period, 'report' => $item->id]) }}"
                               class="flex flex-col justify-between gap-1 py-3 text-sm transition hover:text-primary sm:flex-row sm:items-center {{ $report && $report->id === $item->id ? 'font-semibold text-primary' : 'text-foreground' }}">
                                <span>{{ $periods[$item->period] ?? $item->period }} · <span class="tabular-nums">{{ $item->incident_count }}</span> incidents</span>
                                <span class="text-xs tabular-nums text-muted-foreground">
                                    {{ $item->created_at->timezone($timezone)->format('M d, Y h:i A') }}
                                    @if ($item->generatedBy) · {{ $item->generatedBy->name }} @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
