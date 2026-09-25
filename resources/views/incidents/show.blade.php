@php
    $card = 'rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl';
    $btn = 'inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-5 py-2.5 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt';
    $stage = $incident->stage();
    $tz = 'Asia/Manila';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $incident->severityClasses() }}">{{ $incident->severity }} Severity</span>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ \App\Models\Incident::stageClasses($stage) }}">{{ $stage }}</span>
                    <span class="text-xs text-muted-foreground">#{{ $incident->id }} · {{ $incident->category }}</span>
                </div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-foreground">{{ $incident->title }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ $incident->location_address }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('incidents.index') }}" class="{{ $btn }}">All Incidents</a>
                @if ($incident->isActive())
                    <a href="{{ route('dispatch.index', ['incident' => $incident->id]) }}" class="{{ $btn }}">Dispatch Resources</a>
                    <a href="{{ route('tracking.index') }}#incident-{{ $incident->id }}" class="{{ $btn }}">Track Response</a>
                @endif
                <a href="{{ route('post-incident.show', $incident) }}" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-2xl transition hover:bg-primary/90">
                    After-Action Report
                </a>
            </div>
        </div>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-8">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="{{ $card }}">
                <x-stage-progress :incident="$incident" />
                <div class="mt-5 grid gap-4 border-t border-border pt-5 text-sm sm:grid-cols-3">
                    <div><span class="block text-xs uppercase tracking-wider text-muted-foreground">Dispatch time</span><span class="font-semibold tabular-nums">{{ \App\Models\Incident::formatMinutes($incident->dispatchMinutes()) }}</span></div>
                    <div><span class="block text-xs uppercase tracking-wider text-muted-foreground">Response time (call → on scene)</span><span class="font-semibold tabular-nums">{{ \App\Models\Incident::formatMinutes($incident->responseMinutes()) }}</span></div>
                    <div><span class="block text-xs uppercase tracking-wider text-muted-foreground">Total duration</span><span class="font-semibold tabular-nums">{{ \App\Models\Incident::formatMinutes($incident->resolved_at ? (int) round($incident->created_at->diffInMinutes($incident->resolved_at)) : (int) round($incident->created_at->diffInMinutes(now()))) }}{{ $incident->resolved_at ? '' : ' (ongoing)' }}</span></div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Call details -->
                <div class="{{ $card }} space-y-4">
                    <h3 class="text-lg font-semibold tracking-tight">Call Details</h3>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Reported</dt><dd class="tabular-nums">{{ $incident->created_at->timezone($tz)->format('M d, Y g:i A') }}</dd></div>
                        <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Logged by</dt><dd>{{ $incident->reporter->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Caller</dt><dd>{{ $incident->caller_name ?: '—' }}</dd></div>
                        <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Caller contact</dt><dd class="tabular-nums">{{ $incident->caller_contact ?: '—' }}</dd></div>
                    </dl>
                    <div class="rounded-2xl bg-card-alt p-4 text-sm leading-relaxed">{{ $incident->description }}</div>
                    @if ($incident->dispatcher_notes)
                        <p class="text-xs text-muted-foreground">Dispatcher notes: {{ $incident->dispatcher_notes }}</p>
                    @endif
                </div>

                <!-- Map -->
                <div class="{{ $card }} space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-lg font-semibold tracking-tight">Location</h3>
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $incident->latitude }},{{ $incident->longitude }}" target="_blank" rel="noopener" class="text-xs font-semibold text-primary hover:underline">Directions</a>
                    </div>
                    <div id="incident-map" class="z-0 h-72 w-full overflow-hidden rounded-2xl border border-border"></div>
                    <p class="text-xs tabular-nums text-muted-foreground">{{ number_format($incident->latitude, 6) }}, {{ number_format($incident->longitude, 6) }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Deployed resources -->
                <div class="{{ $card }} space-y-4">
                    <h3 class="text-lg font-semibold tracking-tight">Deployed Resources</h3>
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">Apparatus</p>
                        @forelse ($incident->apparatuses as $unit)
                            <div class="flex items-center justify-between border-b border-border py-2 text-sm last:border-0">
                                <span><span class="font-semibold">{{ $unit->call_sign }}</span> <span class="text-muted-foreground">· {{ $unit->type }}</span></span>
                                <span class="text-xs {{ $unit->pivot->released_at ? 'text-muted-foreground' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $unit->pivot->released_at ? 'Released '.\Illuminate\Support\Carbon::parse($unit->pivot->released_at)->timezone($tz)->format('g:i A') : 'On assignment' }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">No apparatus dispatched.</p>
                        @endforelse
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">Personnel</p>
                        @forelse ($incident->personnel as $person)
                            <div class="flex items-center justify-between border-b border-border py-2 text-sm last:border-0">
                                <span class="font-medium">{{ $person->name }}</span>
                                <span class="text-xs text-muted-foreground">{{ $person->pivot->role }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">No personnel assigned.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Timeline -->
                <div class="{{ $card }} space-y-4">
                    <h3 class="text-lg font-semibold tracking-tight">Response Timeline</h3>
                    <ol class="space-y-4">
                        @forelse ($incident->updates as $update)
                            <li class="flex gap-3">
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $update->stage ? 'bg-primary' : 'bg-muted-foreground/40' }}"></span>
                                <div class="min-w-0 text-sm">
                                    <p class="text-xs tabular-nums text-muted-foreground">
                                        {{ $update->created_at->timezone($tz)->format('M d, g:i A') }}{{ $update->user ? ' · '.$update->user->name : '' }}
                                    </p>
                                    @if ($update->stage)
                                        <p class="font-semibold">{{ $update->stage }}</p>
                                    @endif
                                    @if ($update->note)
                                        <p class="text-muted-foreground">{{ $update->note }}</p>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-muted-foreground">No updates recorded yet.</li>
                        @endforelse
                    </ol>
                </div>
            </div>

            @if ($incident->ai_summary)
                <div class="{{ $card }} space-y-2">
                    <span class="text-xs font-medium uppercase tracking-wider text-primary">AI Command Summary</span>
                    <div class="whitespace-pre-line text-sm leading-relaxed">{{ $incident->ai_summary }}</div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var map = L.map('incident-map', { scrollWheelZoom: false }).setView([{{ $incident->latitude }}, {{ $incident->longitude }}], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
            L.marker([{{ $incident->latitude }}, {{ $incident->longitude }}]).addTo(map);
        });
    </script>
</x-app-layout>
