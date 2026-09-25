<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-display font-black text-2xl text-foreground tracking-tight flex items-center gap-2">
                    🧠 {{ __('AI Analysis & Reports') }}
                </h2>
                <p class="text-xs text-muted font-mono uppercase tracking-wider mt-0.5">
                    BFAD Station 178 • Incident Intelligence Summary
                </p>
            </div>
            <div class="flex items-center gap-2 no-print">
                <button type="button" onclick="window.print()"
                        class="px-4 py-2 bg-card-alt border border-border hover:bg-card text-foreground font-display font-bold text-xs rounded-lg transition uppercase tracking-wide">
                    🖨️ Print
                </button>
                <form method="POST" action="{{ route('reports.generate') }}" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <button type="submit" :disabled="busy || {{ ! $aiReady || $stats['total'] === 0 ? 'true' : 'false' }}"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-display font-bold text-xs rounded-lg shadow-md transition uppercase tracking-wide">
                        <span x-show="!busy">✨ {{ $report && $report->period === $period ? 'Regenerate' : 'Generate' }} AI Analysis</span>
                        <span x-show="busy" x-cloak>⏳ Analysing incidents…</span>
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

    <div class="py-8 bg-background min-h-screen text-foreground">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="no-print p-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-600 text-xs font-bold">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="no-print p-3 rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-500 text-xs font-bold">{{ session('error') }}</div>
            @endif
            @unless ($aiReady)
                <div class="no-print p-3 rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-600 text-xs font-bold">
                    AI analysis is not configured yet. Set <span class="font-mono">AI_API_KEY</span> to enable report generation.
                </div>
            @endunless

            <!-- Period Filter -->
            <div class="no-print flex flex-wrap gap-2">
                @foreach ($periods as $key => $label)
                    <a href="{{ route('reports.index', ['period' => $key]) }}"
                       class="px-3 py-1.5 rounded-full text-[11px] font-bold border transition {{ $period === (string) $key ? 'bg-rose-600 border-rose-600 text-white shadow-md shadow-rose-600/20' : 'bg-card border-border text-muted hover:text-foreground' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <p class="text-[11px] font-mono text-muted uppercase tracking-wider">
                Reporting period: {{ $periods[$period] }}
                ({{ $start ? $start->timezone($timezone)->format('M d, Y') : 'All records' }} – {{ $end->timezone($timezone)->format('M d, Y') }})
            </p>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="print-card p-5 bg-card border border-border rounded-2xl shadow-sm">
                    <p class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Total Incidents</p>
                    <p class="text-3xl font-black text-foreground mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="print-card p-5 bg-card border border-border rounded-2xl shadow-sm">
                    <p class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Active / Open</p>
                    <p class="text-3xl font-black text-amber-500 mt-1">{{ $stats['active'] }}</p>
                </div>
                <div class="print-card p-5 bg-card border border-border rounded-2xl shadow-sm">
                    <p class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Resolved</p>
                    <p class="text-3xl font-black text-emerald-500 mt-1">{{ $stats['resolved'] }}</p>
                </div>
                <div class="print-card p-5 bg-card border border-border rounded-2xl shadow-sm">
                    <p class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Resolution Rate</p>
                    <p class="text-3xl font-black text-rose-500 mt-1">{{ $stats['resolution_rate'] }}%</p>
                </div>
                <div class="print-card p-5 bg-card border border-border rounded-2xl shadow-sm col-span-2 lg:col-span-1">
                    <p class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Avg. Time to Resolve</p>
                    <p class="text-3xl font-black text-foreground mt-1">
                        {{ $stats['avg_resolution_hours'] !== null ? $stats['avg_resolution_hours'].'h' : '—' }}
                    </p>
                </div>
            </div>

            <!-- Breakdowns -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                @foreach ([
                    'Severity Breakdown' => ['data' => $stats['by_severity'], 'bar' => 'bg-rose-500'],
                    'Status Breakdown' => ['data' => $stats['by_status'], 'bar' => 'bg-amber-500'],
                    'Incident Categories' => ['data' => $stats['by_category'], 'bar' => 'bg-sky-500'],
                ] as $title => $block)
                    <div class="print-card p-5 bg-card border border-border rounded-2xl shadow-sm space-y-3">
                        <h3 class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">{{ $title }}</h3>
                        @forelse ($block['data'] as $label => $count)
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-bold text-foreground">{{ $label }}</span>
                                    <span class="font-mono text-muted">{{ $count }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-card-alt overflow-hidden">
                                    <div class="h-full rounded-full {{ $block['bar'] }}" style="width: {{ $stats['total'] ? round($count / $stats['total'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-muted italic">No data.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="print-card p-5 bg-card border border-border rounded-2xl shadow-sm space-y-2 lg:col-span-2">
                    <h3 class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Top Incident Locations</h3>
                    @forelse ($stats['top_locations'] as $location => $count)
                        <div class="flex justify-between gap-4 text-xs py-1.5 border-b border-border last:border-0">
                            <span class="text-foreground">📍 {{ $location }}</span>
                            <span class="font-mono font-bold text-rose-500">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-muted italic">No data.</p>
                    @endforelse
                </div>
                <div class="print-card p-5 bg-card border border-border rounded-2xl shadow-sm space-y-3">
                    <h3 class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Activity Patterns</h3>
                    <div class="text-xs flex justify-between"><span class="text-muted">Busiest hour</span><span class="font-bold">{{ $stats['busiest_hour'] ?? '—' }}</span></div>
                    <div class="text-xs flex justify-between"><span class="text-muted">Busiest day</span><span class="font-bold">{{ $stats['busiest_day'] ?? '—' }}</span></div>
                    <div class="text-xs flex justify-between"><span class="text-muted">After-action reports filed</span><span class="font-bold">{{ $stats['with_after_action_report'] }} / {{ $stats['total'] }}</span></div>
                </div>
            </div>

            <!-- AI Analysis -->
            <div class="print-card bg-card border border-primary/20 rounded-2xl p-6 shadow-sm space-y-6 relative overflow-hidden">
                <div class="absolute -top-16 -right-16 w-40 h-40 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h3 class="font-black text-lg text-foreground flex items-center gap-2">🤖 AI Command Analysis</h3>
                    @if ($report)
                        <span class="text-[10px] font-mono text-muted uppercase tracking-wider">
                            {{ $periods[$report->period] ?? $report->period }} • {{ $report->incident_count }} incidents •
                            Generated {{ $report->created_at->timezone($timezone)->format('M d, Y h:i A') }}
                            @if ($report->generatedBy) by {{ $report->generatedBy->name }} @endif
                        </span>
                    @endif
                </div>

                @if ($report)
                    @php($a = $report->analysis)

                    @if ($report->period !== $period)
                        <p class="no-print text-[11px] text-amber-600 font-bold">You are viewing a saved report from a different period than the statistics above.</p>
                    @endif

                    <section class="space-y-2">
                        <h4 class="text-[10px] font-display font-black uppercase tracking-wider text-rose-500">Executive Summary</h4>
                        <p class="text-sm leading-relaxed text-foreground">{{ $a['executive_summary'] }}</p>
                    </section>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <section class="space-y-2">
                            <h4 class="text-[10px] font-display font-black uppercase tracking-wider text-rose-500">Key Findings</h4>
                            <ul class="space-y-1.5 text-xs text-foreground/90 list-disc pl-4">
                                @forelse ($a['key_findings'] as $item)
                                    <li>{{ $item }}</li>
                                @empty
                                    <li class="list-none -ml-4 text-muted italic">None identified.</li>
                                @endforelse
                            </ul>
                        </section>

                        <section class="space-y-2">
                            <h4 class="text-[10px] font-display font-black uppercase tracking-wider text-rose-500">Risk Patterns</h4>
                            <ul class="space-y-1.5 text-xs text-foreground/90 list-disc pl-4">
                                @forelse ($a['risk_patterns'] as $item)
                                    <li>{{ $item }}</li>
                                @empty
                                    <li class="list-none -ml-4 text-muted italic">No recurring patterns supported by the data.</li>
                                @endforelse
                            </ul>
                        </section>
                    </div>

                    @if ($a['operational_performance'])
                        <section class="space-y-2">
                            <h4 class="text-[10px] font-display font-black uppercase tracking-wider text-rose-500">Operational Performance</h4>
                            <p class="text-xs leading-relaxed text-foreground/90">{{ $a['operational_performance'] }}</p>
                        </section>
                    @endif

                    <section class="space-y-2">
                        <h4 class="text-[10px] font-display font-black uppercase tracking-wider text-rose-500">Recommendations</h4>
                        <div class="space-y-2">
                            @forelse ($a['recommendations'] as $rec)
                                @php($tone = ['High' => 'bg-rose-500/10 text-rose-500 border-rose-500/20', 'Medium' => 'bg-amber-500/10 text-amber-600 border-amber-500/20', 'Low' => 'bg-sky-500/10 text-sky-600 border-sky-500/20'][$rec['priority']])
                                <div class="flex items-start gap-3 p-3 rounded-xl bg-card-alt border border-border">
                                    <span class="shrink-0 px-2 py-0.5 rounded-full border text-[10px] font-mono font-bold uppercase {{ $tone }}">{{ $rec['priority'] }}</span>
                                    <p class="text-xs text-foreground">{{ $rec['action'] }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-muted italic">No recommendations.</p>
                            @endforelse
                        </div>
                    </section>

                    @if (count($a['data_limitations']))
                        <section class="space-y-2">
                            <h4 class="text-[10px] font-display font-black uppercase tracking-wider text-muted">Data Limitations</h4>
                            <ul class="space-y-1 text-[11px] text-muted list-disc pl-4">
                                @foreach ($a['data_limitations'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    <p class="text-[10px] text-muted italic border-t border-border pt-3">
                        AI-generated analysis based only on recorded incident data. Verify critical figures before official use.
                    </p>
                @else
                    <div class="text-center py-10 space-y-2">
                        <p class="text-3xl">📊</p>
                        <p class="text-sm font-bold text-foreground">No AI analysis for this period yet</p>
                        <p class="text-xs text-muted">
                            @if ($stats['total'] === 0)
                                There are no incidents recorded in this period.
                            @else
                                Click <span class="font-bold">Generate AI Analysis</span> to summarise {{ $stats['total'] }} incident{{ $stats['total'] === 1 ? '' : 's' }}.
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <!-- Report History -->
            @if ($history->isNotEmpty())
                <div class="no-print bg-card border border-border rounded-2xl p-6 shadow-sm space-y-3">
                    <h3 class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Saved Reports</h3>
                    <div class="divide-y divide-border">
                        @foreach ($history as $item)
                            <a href="{{ route('reports.index', ['period' => $item->period, 'report' => $item->id]) }}"
                               class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 py-2.5 text-xs hover:text-rose-500 transition {{ $report && $report->id === $item->id ? 'text-rose-500 font-bold' : 'text-foreground' }}">
                                <span>{{ $periods[$item->period] ?? $item->period }} • {{ $item->incident_count }} incidents</span>
                                <span class="font-mono text-muted">
                                    {{ $item->created_at->timezone($timezone)->format('M d, Y h:i A') }}
                                    @if ($item->generatedBy) • {{ $item->generatedBy->name }} @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
