@php
    use App\Models\Incident;

    $card = 'rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl';
    $fmt = fn ($m) => Incident::formatMinutes($m);
    $pct = fn ($part, $whole) => $whole ? round($part / $whole * 100) : 0;

    // Stage colors match the stage badges used across the app (validated for colorblind separation)
    $stageColors = ['Reported' => '#f43f5e', 'Dispatched' => '#f59e0b', 'On Scene' => '#8b5cf6', 'Under Control' => '#0ea5e9'];
    $severityColors = ['Critical' => '#d03b3b', 'High' => '#ec835a', 'Medium' => '#fab219', 'Low' => '#0ea5e9'];
    $unitStates = [
        'Available' => ['#0ca30c', 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'],
        'Deployed' => ['#f59e0b', 'bg-amber-500/10 text-amber-600 dark:text-amber-400'],
        'Maintenance' => ['#0ea5e9', 'bg-sky-500/10 text-sky-600 dark:text-sky-400'],
        'Out of Service' => ['#d03b3b', 'bg-rose-500/10 text-rose-600 dark:text-rose-400'],
    ];
    $fuelColor = fn ($level) => $level < 35 ? '#d03b3b' : ($level < 60 ? '#fab219' : '#0ca30c');

    $delta = $stats['last_30'] - $stats['prev_30'];
    $pipelineTotal = array_sum($pipeline);
    $severityMax = max(1, max($severity));
    $categoryMax = max(1, max($categories ?: [0]));
    $busiestHour = collect($hourly)->sortByDesc('value')->first();

    $mapPoints = $active->map(fn ($i) => [
        'id' => $i->id,
        'lat' => (float) $i->latitude,
        'lng' => (float) $i->longitude,
        'title' => $i->title,
        'stage' => $i->stage(),
        'severity' => $i->severity,
        'color' => $severityColors[$i->severity] ?? '#d03b3b',
        'url' => route('incidents.show', $i),
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">{{ __('Barangay Dispatch Command Center') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">BFAD Station 178 · Camarin, Caloocan City · {{ now($tz)->format('l, F j, Y · g:i A') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tracking.index') }}" class="inline-flex items-center justify-center rounded-3xl border border-border bg-card px-5 py-3 text-sm font-semibold text-foreground transition hover:bg-card-alt">
                    Live Tracking
                </a>
                <a href="{{ route('incidents.create') }}" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl transition hover:bg-primary/90">
                    Dispatch Emergency Call
                </a>
            </div>
        </div>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-8">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            @if ($stats['awaiting'] > 0)
                <a href="{{ route('dispatch.index') }}" class="flex items-center justify-between gap-4 rounded-3xl border border-rose-500/30 bg-rose-500/10 px-6 py-4 text-sm transition hover:bg-rose-500/15">
                    <span class="flex items-center gap-3">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                        </span>
                        <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $stats['awaiting'] }} {{ Str::plural('call', $stats['awaiting']) }} awaiting dispatch</span>
                        <span class="hidden text-muted-foreground sm:inline">— no units assigned yet</span>
                    </span>
                    <span class="font-semibold text-rose-600 dark:text-rose-400">Assign units →</span>
                </a>
            @endif

            <!-- KPI tiles -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                <div class="{{ $card }} !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Total Emergency Calls</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight text-foreground">{{ number_format($stats['total']) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        <span class="font-semibold text-foreground">{{ $stats['today'] }}</span> today ·
                        <span class="font-semibold text-foreground">{{ $stats['last_30'] }}</span> in 30 days
                        @if ($delta !== 0)
                            <span class="{{ $delta > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">({{ $delta > 0 ? '▲' : '▼' }} {{ abs($delta) }})</span>
                        @endif
                    </p>
                </div>

                <div class="{{ $card }} !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Active Incidents</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight text-accent">{{ $stats['active'] }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        @if ($stats['awaiting'])
                            <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $stats['awaiting'] }}</span> awaiting dispatch
                        @else
                            All calls have units assigned
                        @endif
                    </p>
                </div>

                <div class="{{ $card }} !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Resolved Cases</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight text-primary">{{ $stats['resolved'] }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <div class="h-1.5 flex-1 rounded-full bg-card-alt">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $stats['resolution_rate'] }}%"></div>
                        </div>
                        <span class="text-xs font-semibold tabular-nums text-foreground">{{ $stats['resolution_rate'] }}%</span>
                    </div>
                </div>

                <div class="{{ $card }} !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Avg. Response Time</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight text-foreground">{{ $fmt($stats['avg_response']) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Call → first unit on scene</p>
                </div>

                <a href="{{ route('equipment.index') }}" class="{{ $card }} !p-5 transition hover:border-primary/40">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Units Available</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight text-foreground">{{ $stats['units_available'] }}<span class="text-lg text-muted-foreground"> / {{ $stats['units_total'] }}</span></p>
                    <div class="mt-2 h-1.5 rounded-full bg-card-alt">
                        <div class="h-full rounded-full bg-primary" style="width: {{ $pct($stats['units_available'], $stats['units_total']) }}%"></div>
                    </div>
                </a>

                <a href="{{ route('admin.firefighters.index') }}" class="{{ $card }} !p-5 transition hover:border-primary/40">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Crew Available</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight text-foreground">{{ $stats['crew_available'] }}<span class="text-lg text-muted-foreground"> / {{ $stats['crew_total'] }}</span></p>
                    <p class="mt-1 text-xs text-muted-foreground"><span class="font-semibold text-foreground">{{ $stats['crew_deployed'] }}</span> deployed on incidents</p>
                </a>
            </div>

            <!-- Call volume + response pipeline -->
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="{{ $card }} xl:col-span-2" x-data="{ view: 'weekly' }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight">Emergency Call Volume</h3>
                            <p class="text-sm text-muted-foreground" x-text="view === 'weekly' ? 'Calls logged per week, last 12 weeks' : 'Calls logged per month, last 12 months'"></p>
                        </div>
                        <div class="inline-flex rounded-3xl border border-border bg-card-alt p-1 text-xs font-semibold">
                            <button type="button" @click="view = 'weekly'" class="rounded-3xl px-3 py-1.5 transition" :class="view === 'weekly' ? 'bg-card text-foreground shadow' : 'text-muted-foreground'">Weekly</button>
                            <button type="button" @click="view = 'monthly'" class="rounded-3xl px-3 py-1.5 transition" :class="view === 'monthly' ? 'bg-card text-foreground shadow' : 'text-muted-foreground'">Monthly</button>
                        </div>
                    </div>
                    <x-dashboard.column-chart :data="$trend['weekly']" x-show="view === 'weekly'" class="mt-4" height="h-56" />
                    <x-dashboard.column-chart :data="$trend['monthly']" x-show="view === 'monthly'" x-cloak class="mt-4" height="h-56" />
                </div>

                <div class="{{ $card }} flex flex-col gap-5">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">Response Pipeline</h3>
                        <p class="text-sm text-muted-foreground">Where the {{ $pipelineTotal }} open {{ Str::plural('incident', $pipelineTotal) }} stand now</p>
                    </div>

                    @if ($pipelineTotal)
                        <div class="flex h-7 gap-[2px] overflow-hidden rounded-lg">
                            @foreach ($pipeline as $stage => $count)
                                @if ($count)
                                    <div class="h-full" style="flex-grow: {{ $count }}; background: {{ $stageColors[$stage] }}" data-tip="{{ $stage }}" data-tip-value="{{ $count }} {{ Str::plural('incident', $count) }}"></div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="h-7 rounded-lg bg-card-alt"></div>
                    @endif

                    <ul class="grid grid-cols-2 gap-3 text-sm">
                        @foreach ($pipeline as $stage => $count)
                            <li class="flex items-center justify-between gap-2 rounded-2xl bg-card-alt px-3 py-2">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $stageColors[$stage] }}"></span>
                                    <span class="truncate text-muted-foreground">{{ $stage }}</span>
                                </span>
                                <span class="font-semibold tabular-nums text-foreground">{{ $count }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-auto divide-y divide-border border-t border-border">
                        @foreach ([
                            ['Avg. time to dispatch', $stats['avg_dispatch'], 'Call → units assigned'],
                            ['Avg. response time', $stats['avg_response'], 'Call → on scene'],
                            ['Avg. time to control', $stats['avg_control'], 'On scene → under control'],
                        ] as [$label, $minutes, $hint])
                            <div class="flex items-center justify-between gap-4 py-3 text-sm">
                                <span>
                                    <span class="block text-foreground">{{ $label }}</span>
                                    <span class="text-xs text-muted-foreground">{{ $hint }}</span>
                                </span>
                                <span class="text-lg font-semibold tabular-nums text-foreground">{{ $fmt($minutes) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Operations map + priority queue -->
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="{{ $card }} space-y-3 xl:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-lg font-semibold tracking-tight">Operations Map</h3>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-primary"></span>Station 178</span>
                            @foreach ($severityColors as $level => $color)
                                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $color }}"></span>{{ $level }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div id="ops-map" class="z-0 h-96 w-full overflow-hidden rounded-2xl border border-border"></div>
                </div>

                <div class="{{ $card }} flex flex-col">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold tracking-tight">Live Dispatch Queue</h3>
                        <a href="{{ route('incidents.index') }}" class="text-sm font-semibold text-primary hover:underline">View all</a>
                    </div>
                    <ul class="mt-4 max-h-96 flex-1 space-y-3 overflow-y-auto pr-1">
                        @forelse ($active as $incident)
                            @php($stage = $incident->stage())
                            <li class="rounded-2xl border border-border bg-card-alt/60 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $incident->severityClasses() }}">{{ $incident->severity }}</span>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ Incident::stageClasses($stage) }}">{{ $stage }}</span>
                                    <span class="ml-auto text-[11px] tabular-nums text-muted-foreground">{{ $incident->created_at->diffForHumans(short: true) }}</span>
                                </div>
                                <a href="{{ route('incidents.show', $incident) }}" class="mt-2 block font-semibold leading-snug text-foreground hover:text-primary">{{ $incident->title }}</a>
                                <p class="truncate text-xs text-muted-foreground">{{ $incident->location_address }}</p>
                                <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                                    <span>{{ $incident->apparatuses->count() }} {{ Str::plural('unit', $incident->apparatuses->count()) }} · {{ $incident->personnel->count() }} crew</span>
                                    @if ($stage === 'Reported')
                                        <a href="{{ route('dispatch.index', ['incident' => $incident->id]) }}" class="font-semibold text-rose-600 hover:underline dark:text-rose-400">Dispatch →</a>
                                    @else
                                        <a href="{{ route('tracking.index') }}#incident-{{ $incident->id }}" class="font-semibold text-primary hover:underline">Track →</a>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="flex h-full flex-col items-center justify-center rounded-2xl bg-card-alt p-8 text-center">
                                <span class="text-sm font-semibold text-foreground">No active incidents</span>
                                <span class="mt-1 text-xs text-muted-foreground">All clear in Barangay 178.</span>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- Incident analytics -->
            <div class="grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
                <div class="{{ $card }}">
                    <h3 class="text-lg font-semibold tracking-tight">Severity Breakdown</h3>
                    <p class="text-sm text-muted-foreground">All {{ $stats['total'] }} recorded incidents</p>
                    <ul class="mt-5 space-y-4">
                        @foreach ($severity as $level => $count)
                            <li class="text-sm" data-tip="{{ $level }} severity" data-tip-value="{{ $count }} {{ Str::plural('incident', $count) }} · {{ $pct($count, $stats['total']) }}%">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="text-foreground">{{ $level }}</span>
                                    <span class="tabular-nums text-muted-foreground"><span class="font-semibold text-foreground">{{ $count }}</span> · {{ $pct($count, $stats['total']) }}%</span>
                                </div>
                                <div class="h-2.5 rounded-r bg-card-alt">
                                    <div class="h-full rounded-r" style="width: {{ $count / $severityMax * 100 }}%; background: {{ $severityColors[$level] }}"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="{{ $card }}">
                    <h3 class="text-lg font-semibold tracking-tight">Incident Types</h3>
                    <p class="text-sm text-muted-foreground">Most common emergency categories</p>
                    <ul class="mt-5 space-y-4">
                        @forelse ($categories as $category => $count)
                            <li class="text-sm" data-tip="{{ $category }}" data-tip-value="{{ $count }} {{ Str::plural('incident', $count) }} · {{ $pct($count, $stats['total']) }}%">
                                <div class="mb-1.5 flex items-center justify-between gap-3">
                                    <span class="truncate text-foreground">{{ $category }}</span>
                                    <span class="font-semibold tabular-nums text-foreground">{{ $count }}</span>
                                </div>
                                <div class="h-2.5 rounded-r bg-card-alt">
                                    <div class="h-full rounded-r bg-primary" style="width: {{ $count / $categoryMax * 100 }}%"></div>
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-muted-foreground">No incidents recorded yet.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="{{ $card }} lg:col-span-2 2xl:col-span-1">
                    <h3 class="text-lg font-semibold tracking-tight">Calls by Hour of Day</h3>
                    <p class="text-sm text-muted-foreground">
                        @if ($busiestHour && $busiestHour['value'])
                            Busiest window: <span class="font-semibold text-foreground">{{ $busiestHour['tip'] }}</span>
                        @else
                            When emergency calls come in
                        @endif
                    </p>
                    <x-dashboard.column-chart :data="$hourly" :label-every="3" class="mt-4" height="h-48" />
                </div>
            </div>

            <!-- Readiness -->
            <div class="grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
                <!-- Fleet -->
                <div class="{{ $card }} flex flex-col">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold tracking-tight">Fleet Readiness</h3>
                        <a href="{{ route('equipment.index') }}" class="text-sm font-semibold text-primary hover:underline">Manage</a>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        @foreach ($unitStates as $state => [$color, $classes])
                            @php($n = $fleet->where('state', $state)->count())
                            @if ($n || $state !== 'Out of Service')
                                <span class="rounded-full px-2.5 py-1 font-medium {{ $classes }}">{{ $n }} {{ $state }}</span>
                            @endif
                        @endforeach
                    </div>
                    <ul class="mt-4 flex-1 divide-y divide-border">
                        @forelse ($fleet as $unit)
                            <li class="flex items-center gap-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-foreground">{{ $unit->call_sign }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ $unit->type }} · {{ $unit->plate_number }}</p>
                                </div>
                                <div class="w-24 shrink-0" data-tip="{{ $unit->call_sign }} fuel" data-tip-value="{{ $unit->fuel_level_percent }}%">
                                    <div class="mb-1 text-right text-[11px] tabular-nums text-muted-foreground">Fuel {{ $unit->fuel_level_percent }}%</div>
                                    <div class="h-1.5 rounded-r bg-card-alt">
                                        <div class="h-full rounded-r" style="width: {{ min(100, max(0, $unit->fuel_level_percent)) }}%; background: {{ $fuelColor($unit->fuel_level_percent) }}"></div>
                                    </div>
                                </div>
                                <span class="w-24 shrink-0 rounded-full px-2.5 py-1 text-center text-[11px] font-medium {{ $unitStates[$unit->state][1] }}">{{ $unit->state }}</span>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-muted-foreground">No apparatus registered.</li>
                        @endforelse
                    </ul>
                </div>

                <!-- Station readiness -->
                <div class="{{ $card }} space-y-6">
                    <h3 class="text-lg font-semibold tracking-tight">Station Readiness</h3>

                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-foreground">Crew on duty</span>
                            <a href="{{ route('admin.firefighters.index') }}" class="text-xs font-semibold text-primary hover:underline">Personnel</a>
                        </div>
                        @php($standby = $stats['crew_standby'])
                        @php($offDuty = max(0, $stats['crew_total'] - $stats['crew_deployed'] - $standby))
                        <div class="mt-2 flex h-3 gap-[2px] overflow-hidden rounded">
                            @foreach ([['Deployed', $stats['crew_deployed'], '#f59e0b'], ['Standby', $standby, '#0ca30c'], ['Off duty', $offDuty, 'hsl(var(--muted-foreground) / 0.35)']] as [$label, $n, $color])
                                @if ($n)
                                    <div style="flex-grow: {{ $n }}; background: {{ $color }}" data-tip="{{ $label }}" data-tip-value="{{ $n }} {{ Str::plural('firefighter', $n) }}"></div>
                                @endif
                            @endforeach
                            @if (! $stats['crew_total'])
                                <div class="flex-1 bg-card-alt"></div>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background: #f59e0b"></span>Deployed <b class="text-foreground">{{ $stats['crew_deployed'] }}</b></span>
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full" style="background: #0ca30c"></span>Standby <b class="text-foreground">{{ $standby }}</b></span>
                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-muted-foreground/35"></span>Off duty <b class="text-foreground">{{ $offDuty }}</b></span>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-foreground">Equipment inventory</span>
                            <a href="{{ route('equipment.index') }}" class="text-xs font-semibold text-primary hover:underline">Inventory</a>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            @foreach ($equipment['by_status'] as $status => $qty)
                                <div class="rounded-2xl bg-card-alt px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wider text-muted-foreground">{{ $status }}</p>
                                    <p class="text-lg font-semibold tabular-nums text-foreground">{{ number_format($qty) }}</p>
                                </div>
                            @endforeach
                        </div>
                        @if ($equipment['expiring'])
                            <p class="mt-2 rounded-2xl bg-amber-500/10 px-3 py-2 text-xs font-medium text-amber-700 dark:text-amber-400">⚠ {{ $equipment['expiring'] }} {{ Str::plural('item', $equipment['expiring']) }} expired or expiring within 30 days</p>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-foreground">Open backlog tickets <b class="tabular-nums">{{ $backlog['open'] }}</b></span>
                            <a href="{{ route('backlog.index') }}" class="text-xs font-semibold text-primary hover:underline">Backlog</a>
                        </div>
                        <div class="mt-2 grid grid-cols-4 gap-2 text-center">
                            @foreach ($backlog['by_priority'] as $priority => $n)
                                <div class="rounded-2xl bg-card-alt px-2 py-2">
                                    <p class="text-lg font-semibold tabular-nums text-foreground">{{ $n }}</p>
                                    <p class="flex items-center justify-center gap-1 text-[11px] text-muted-foreground">
                                        <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $severityColors[$priority] }}"></span>{{ $priority }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Activity feed -->
                <div class="{{ $card }} lg:col-span-2 2xl:col-span-1">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold tracking-tight">Recent Activity</h3>
                        <a href="{{ route('tracking.index') }}" class="text-sm font-semibold text-primary hover:underline">Tracking</a>
                    </div>
                    <ol class="mt-4 space-y-4 border-l border-border pl-5">
                        @forelse ($feed as $update)
                            <li class="relative">
                                <span class="absolute -left-[26px] top-1 h-2.5 w-2.5 rounded-full ring-4 ring-card" style="background: {{ $stageColors[$update->stage] ?? ($update->stage === 'Resolved' ? '#0ca30c' : 'hsl(var(--muted-foreground))') }}"></span>
                                <p class="text-[11px] text-muted-foreground">{{ $update->created_at->diffForHumans() }}{{ $update->user ? ' · '.$update->user->name : '' }}</p>
                                <a href="{{ $update->incident ? route('incidents.show', $update->incident) : '#' }}" class="block truncate text-sm font-semibold text-foreground hover:text-primary">{{ $update->incident->title ?? 'Incident' }}</a>
                                <p class="line-clamp-2 text-xs text-muted-foreground">{{ $update->stage ? $update->stage.($update->note ? ' — ' : '') : '' }}{{ $update->note }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-muted-foreground">No activity yet.</li>
                        @endforelse
                    </ol>
                </div>
            </div>

            <!-- Recently resolved -->
            <div class="{{ $card }}">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold tracking-tight">Recently Resolved</h3>
                    <a href="{{ route('post-incident.index') }}" class="text-sm font-semibold text-primary hover:underline">Post-incident reports</a>
                </div>
                <div class="-mx-6 mt-4 overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wider text-muted-foreground">
                                <th class="px-6 py-3 font-medium">Incident</th>
                                <th class="px-3 py-3 font-medium">Type</th>
                                <th class="px-3 py-3 font-medium">Severity</th>
                                <th class="px-3 py-3 font-medium">Reported</th>
                                <th class="px-3 py-3 text-right font-medium">Response</th>
                                <th class="px-6 py-3 text-right font-medium">Report</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($recentResolved as $incident)
                                <tr class="transition hover:bg-card-alt/60">
                                    <td class="max-w-xs px-6 py-3">
                                        <a href="{{ route('incidents.show', $incident) }}" class="block truncate font-semibold text-foreground hover:text-primary">{{ $incident->title }}</a>
                                        <span class="block truncate text-xs text-muted-foreground">{{ $incident->location_address }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-muted-foreground">{{ $incident->category ?: '—' }}</td>
                                    <td class="px-3 py-3"><span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $incident->severityClasses() }}">{{ $incident->severity }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-3 tabular-nums text-muted-foreground">{{ $incident->created_at->timezone($tz)->format('M j, g:i A') }}</td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums text-foreground">{{ $fmt($incident->responseMinutes()) }}</td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('post-incident.show', $incident) }}" class="text-xs font-semibold hover:underline {{ $incident->ai_summary || $incident->after_action_report ? 'text-primary' : 'text-amber-600 dark:text-amber-400' }}">
                                            {{ $incident->ai_summary ? 'AI summary' : ($incident->after_action_report ? 'Report filed' : 'Report pending') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-6 text-center text-muted-foreground">No resolved incidents yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Shared chart tooltip -->
    <div id="chart-tip" class="pointer-events-none fixed z-50 hidden rounded-xl border border-border bg-card px-3 py-2 text-xs shadow-xl">
        <p class="text-muted-foreground" data-tip-label></p>
        <p class="font-semibold tabular-nums text-foreground" data-tip-text></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Operations map: station plus every open incident, colored by severity
            var station = [14.755200, 121.042800];
            var incidents = {{ \Illuminate\Support\Js::from($mapPoints) }};

            var map = L.map('ops-map', { scrollWheelZoom: false }).setView(station, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);

            var bounds = [station];
            L.circleMarker(station, { radius: 9, color: '#ffffff', weight: 2, fillColor: '#22c55e', fillOpacity: 1 }).addTo(map).bindPopup('<b>BFAD Station 178</b>');

            incidents.forEach(function (i) {
                var el = document.createElement('div');
                el.innerHTML = '<b></b><br><span></span><br><a>Open incident</a>';
                el.querySelector('b').textContent = i.title;
                el.querySelector('span').textContent = i.severity + ' · ' + i.stage;
                el.querySelector('a').href = i.url;
                L.circleMarker([i.lat, i.lng], { radius: 9, color: '#ffffff', weight: 2, fillColor: i.color, fillOpacity: 1 }).addTo(map).bindPopup(el);
                bounds.push([i.lat, i.lng]);
            });

            if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });

            // Hover tooltip for any element carrying data-tip
            var tip = document.getElementById('chart-tip');
            document.querySelectorAll('[data-tip]').forEach(function (el) {
                el.addEventListener('mouseenter', function () {
                    tip.querySelector('[data-tip-label]').textContent = el.dataset.tip;
                    tip.querySelector('[data-tip-text]').textContent = el.dataset.tipValue || '';
                    tip.classList.remove('hidden');
                });
                el.addEventListener('mousemove', function (e) {
                    var x = Math.min(e.clientX + 14, window.innerWidth - tip.offsetWidth - 8);
                    tip.style.left = x + 'px';
                    tip.style.top = (e.clientY - tip.offsetHeight - 12) + 'px';
                });
                el.addEventListener('mouseleave', function () { tip.classList.add('hidden'); });
            });
        });
    </script>
</x-app-layout>
