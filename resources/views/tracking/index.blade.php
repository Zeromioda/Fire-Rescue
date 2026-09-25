@php
    $card = 'rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl';
    $field = 'rounded-2xl border border-border bg-card px-3 py-2 text-sm text-foreground focus:border-primary focus:ring-primary';
    $tz = 'Asia/Manila';
    $fmt = fn ($m) => \App\Models\Incident::formatMinutes($m);
    $mapPoints = $active->map(fn ($i) => [
        'id' => $i->id,
        'lat' => (float) $i->latitude,
        'lng' => (float) $i->longitude,
        'title' => $i->title,
        'stage' => $i->stage(),
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">{{ __('Emergency Response Tracking') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">Live progress of every open incident, from dispatch to fire out.</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground" x-data="{ auto: localStorage.getItem('tracking-auto') !== 'off' }"
                 x-init="$watch('auto', v => { localStorage.setItem('tracking-auto', v ? 'on' : 'off'); window.trackingAuto = v }); window.trackingAuto = auto">
                <span class="relative flex h-2.5 w-2.5">
                    <span x-show="auto" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full" :class="auto ? 'bg-emerald-500' : 'bg-muted-foreground/40'"></span>
                </span>
                <span>Updated {{ now($tz)->format('g:i:s A') }}</span>
                <button type="button" @click="auto = !auto" class="rounded-3xl border border-border bg-card/95 px-3 py-1.5 font-semibold text-foreground transition hover:bg-card-alt" x-text="auto ? 'Auto-refresh on' : 'Auto-refresh off'"></button>
            </div>
        </div>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-8">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <!-- Metrics -->
            <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-6">
                @foreach ([
                    ['Active Incidents', $metrics['active'], 'text-rose-600 dark:text-rose-400', null],
                    ['Units Deployed', $metrics['units_deployed'], 'text-amber-600 dark:text-amber-400', null],
                    ['Crew Deployed', $metrics['personnel_deployed'], 'text-sky-600 dark:text-sky-400', null],
                    ['Avg. Dispatch Time', $fmt($metrics['avg_dispatch']), 'text-foreground', 'Last 30 days'],
                    ['Avg. Response Time', $fmt($metrics['avg_response']), 'text-foreground', 'Call → on scene, 30 days'],
                    ['Avg. Time to Control', $fmt($metrics['avg_control']), 'text-foreground', 'On scene → under control'],
                ] as [$label, $value, $tone, $hint])
                    <div class="{{ $card }} !p-5">
                        <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">{{ $label }}</span>
                        <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums {{ $tone }}">{{ $value }}</p>
                        @if ($hint)<p class="text-[11px] text-muted-foreground">{{ $hint }}</p>@endif
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
                <div class="space-y-6">
                    <!-- Operations map -->
                    <div class="{{ $card }} space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold tracking-tight">Operations Map</h3>
                            <span class="text-xs text-muted-foreground">Green: Station 178 · Red: active incidents</span>
                        </div>
                        <div id="ops-map" class="z-0 h-80 w-full overflow-hidden rounded-2xl border border-border"></div>
                    </div>

                    <!-- Active incidents -->
                    @forelse ($active as $incident)
                        @php($stage = $incident->stage())
                        <div id="incident-{{ $incident->id }}" class="{{ $card }} scroll-mt-6 space-y-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $incident->severityClasses() }}">{{ $incident->severity }}</span>
                                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ \App\Models\Incident::stageClasses($stage) }}">{{ $stage }}</span>
                                    </div>
                                    <a href="{{ route('incidents.show', $incident) }}" class="mt-2 block text-lg font-semibold tracking-tight hover:text-primary">{{ $incident->title }}</a>
                                    <p class="truncate text-sm text-muted-foreground">{{ $incident->location_address }}</p>
                                </div>
                                <div class="shrink-0 text-left sm:text-right">
                                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Elapsed</p>
                                    <p class="text-2xl font-semibold tabular-nums" data-elapsed-since="{{ $incident->created_at->toIso8601String() }}">—</p>
                                </div>
                            </div>

                            <x-stage-progress :incident="$incident" />

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2 text-sm">
                                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">On assignment</p>
                                    @if ($incident->apparatuses->isEmpty() && $incident->personnel->isEmpty())
                                        <p class="text-rose-600 dark:text-rose-400">No resources deployed —
                                            <a href="{{ route('dispatch.index', ['incident' => $incident->id]) }}" class="font-semibold underline">dispatch now</a></p>
                                    @else
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($incident->apparatuses as $unit)
                                                <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">{{ $unit->call_sign }}</span>
                                            @endforeach
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ $incident->personnel->count() }} crew: {{ $incident->personnel->pluck('name')->implode(', ') }}</p>
                                    @endif
                                    <p class="pt-1 text-xs text-muted-foreground">
                                        Response: <span class="font-semibold text-foreground">{{ $fmt($incident->responseMinutes()) }}</span>
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Latest updates</p>
                                    @forelse ($incident->updates->take(3) as $update)
                                        <p class="text-xs"><span class="tabular-nums text-muted-foreground">{{ $update->created_at->timezone($tz)->format('g:i A') }}</span>
                                            @if ($update->stage)<span class="font-semibold">{{ $update->stage }}</span>@endif
                                            <span class="text-muted-foreground">{{ $update->note }}</span></p>
                                    @empty
                                        <p class="text-xs text-muted-foreground">No updates yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Stage update -->
                            <form method="POST" action="{{ route('tracking.update', $incident) }}" class="flex flex-col gap-2 border-t border-border pt-4 md:flex-row">
                                @csrf
                                <select name="stage" class="{{ $field }} md:w-48">
                                    <option value="">Note only</option>
                                    @foreach ($incident->nextStages() as $next)
                                        <option value="{{ $next }}" @selected($loop->first)>Mark {{ $next }}</option>
                                    @endforeach
                                </select>
                                <input name="note" maxlength="1000" placeholder="Field note (e.g. second alarm raised, water supply established)" class="{{ $field }} min-w-0 flex-1">
                                <button type="submit" class="inline-flex items-center justify-center rounded-3xl border border-primary/20 bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">Update</button>
                            </form>
                        </div>
                    @empty
                        <div class="{{ $card }} py-12 text-center">
                            <h3 class="text-lg font-semibold">No active incidents</h3>
                            <p class="mt-1 text-sm text-muted-foreground">All clear for Barangay 178 Camarin.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Activity feed -->
                <div class="{{ $card }} h-fit space-y-4 xl:sticky xl:top-6">
                    <h3 class="text-lg font-semibold tracking-tight">Activity Feed</h3>
                    <ol class="space-y-4">
                        @forelse ($feed as $update)
                            <li class="flex gap-3">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $update->stage ? 'bg-primary' : 'bg-muted-foreground/40' }}"></span>
                                <div class="min-w-0 text-xs">
                                    <p class="tabular-nums text-muted-foreground">{{ $update->created_at->timezone($tz)->diffForHumans() }}{{ $update->user ? ' · '.$update->user->name : '' }}</p>
                                    <a href="{{ $update->incident ? route('incidents.show', $update->incident) : '#' }}" class="block truncate font-semibold text-foreground hover:text-primary">{{ $update->incident->title ?? 'Incident' }}</a>
                                    <p class="text-muted-foreground">{{ $update->stage ? $update->stage.($update->note ? ' — ' : '') : '' }}{{ $update->note }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-muted-foreground">No activity yet.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var station = [14.755200, 121.042800];
            var incidents = {{ \Illuminate\Support\Js::from($mapPoints) }};

            var map = L.map('ops-map', { scrollWheelZoom: false }).setView(station, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);

            var bounds = [station];
            L.circleMarker(station, { radius: 9, color: '#16a34a', fillColor: '#22c55e', fillOpacity: 0.9 }).addTo(map).bindPopup('<b>BFAD Station 178</b>');

            incidents.forEach(function (i) {
                var el = document.createElement('div');
                el.innerHTML = '<b></b><br><span></span><br><a>Jump to incident</a>';
                el.querySelector('b').textContent = i.title;
                el.querySelector('span').textContent = i.stage;
                el.querySelector('a').href = '#incident-' + i.id;
                L.circleMarker([i.lat, i.lng], { radius: 9, color: '#dc2626', fillColor: '#ef4444', fillOpacity: 0.85 }).addTo(map).bindPopup(el);
                bounds.push([i.lat, i.lng]);
            });

            if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });

            // Live elapsed timers
            function tick() {
                document.querySelectorAll('[data-elapsed-since]').forEach(function (el) {
                    var mins = Math.max(0, Math.floor((Date.now() - new Date(el.dataset.elapsedSince)) / 60000));
                    el.textContent = mins >= 60 ? Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm' : mins + 'm';
                });
            }
            tick();
            setInterval(tick, 30000);

            // Auto-refresh every 60s unless the user is typing
            setInterval(function () {
                var el = document.activeElement;
                var typing = el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') && (el.value || '') !== '';
                if (window.trackingAuto && !typing && !document.hidden) location.reload();
            }, 60000);
        });
    </script>
</x-app-layout>
