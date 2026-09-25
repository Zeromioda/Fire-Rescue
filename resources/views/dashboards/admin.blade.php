<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                {{ __('Barangay Dispatch Command Center') }}
            </h2>
            <a href="{{ route('incidents.create') }}" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90">
                Dispatch Emergency Call
            </a>
        </div>
    </x-slot>

    <!-- Leaflet CSS/JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-8">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Stats Bar -->
            <div class="grid gap-6 sm:grid-cols-3">
                <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Total Emergency Calls</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums text-foreground">{{ $stats['total'] }}</p>
                </div>
                <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Active Responders Dispatched</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums text-accent">{{ $stats['active'] }}</p>
                </div>
                <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Resolved Cases</span>
                    <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums text-primary">{{ $stats['resolved'] }}</p>
                </div>
            </div>

            <!-- Incident Live Stream -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold tracking-tight text-foreground">Live Dispatch Stream</h3>

                @foreach($incidents as $incident)
                    <div class="grid grid-cols-1 gap-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl md:grid-cols-2">
                        <div class="min-w-0 space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="rounded-full bg-rose-500/10 px-3 py-1 text-xs font-medium text-rose-600 dark:text-rose-400">
                                    {{ $incident->severity }} Severity
                                </span>
                                <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-medium text-accent">{{ $incident->status }}</span>
                            </div>

                            <div>
                                <h4 class="text-lg font-semibold tracking-tight text-foreground">{{ $incident->title }}</h4>
                                <p class="mt-1 text-sm text-muted-foreground">{{ $incident->location_address }}</p>
                            </div>
                            <p class="rounded-2xl bg-card-alt p-4 text-sm text-foreground">{{ $incident->description }}</p>

                            <!-- AI Executive Summary & Report Block (Moved Inside Loop) -->
                            @if($incident->ai_summary)
                                <div class="space-y-2 rounded-2xl bg-card-alt p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-xs font-medium uppercase tracking-wider text-primary">AI Command Summary</span>
                                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">Barangay 178 Camarin</span>
                                    </div>
                                    <div class="whitespace-pre-line text-sm leading-relaxed text-foreground">
                                        {{ $incident->ai_summary }}
                                    </div>
                                </div>
                            @elseif($incident->after_action_report)
                                <div class="space-y-1 rounded-2xl bg-card-alt p-4 text-sm">
                                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Raw Responder Report</span>
                                    <p class="text-foreground">{{ $incident->after_action_report }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Map -->
                        <div class="min-w-0">
                            <div id="admin-map-{{ $incident->id }}" class="z-0 h-48 w-full overflow-hidden rounded-2xl border border-border"></div>
                            <script>
                                document.addEventListener("DOMContentLoaded", function () {
                                    var map = L.map('admin-map-{{ $incident->id }}').setView([{{ $incident->latitude }}, {{ $incident->longitude }}], 14);
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                                    L.marker([{{ $incident->latitude }}, {{ $incident->longitude }}]).addTo(map);
                                });
                            </script>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
