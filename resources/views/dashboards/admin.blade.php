<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-extrabold text-xl text-primary flex items-center gap-2">
                <span>🛡️</span> {{ __('Barangay Dispatch Command Center') }}
            </h2>
            <a href="{{ route('incidents.create') }}" class="px-4 py-2 bg-accent hover:opacity-90 text-accent-foreground font-display font-bold text-xs rounded-lg shadow-md transition uppercase tracking-wide">
                + Dispatch Emergency Call
            </a>
        </div>
    </x-slot>

    <!-- Leaflet CSS/JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-8 bg-background min-h-screen text-foreground">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-card border border-border rounded-lg p-5 shadow-sm">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Total Emergency Calls</span>
                    <p class="text-3xl font-display font-extrabold text-foreground mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-card-alt border border-border rounded-lg p-5 shadow-sm">
                    <span class="text-xs font-bold text-accent uppercase tracking-wider">Active Responders Dispatched</span>
                    <p class="text-3xl font-display font-extrabold text-accent mt-1">{{ $stats['active'] }}</p>
                </div>
                <div class="bg-card border border-border rounded-lg p-5 shadow-sm">
                    <span class="text-xs font-bold text-primary uppercase tracking-wider">Resolved Cases</span>
                    <p class="text-3xl font-display font-extrabold text-primary mt-1">{{ $stats['resolved'] }}</p>
                </div>
            </div>

            <!-- Incident Live Stream -->
            <div class="space-y-4">
                <h3 class="text-xs font-display font-bold uppercase tracking-wider text-muted">Live Dispatch Stream</h3>

                @foreach($incidents as $incident)
                    <div class="bg-card border border-border rounded-lg p-6 grid grid-cols-1 md:grid-cols-2 gap-6 shadow-sm">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-3 py-1 bg-destructive/10 border border-destructive/20 text-destructive font-bold text-[10px] rounded-full uppercase">
                                    {{ $incident->severity }} Severity
                                </span>
                                <span class="text-xs font-bold text-accent">{{ $incident->status }}</span>
                            </div>

                            <h4 class="text-lg font-display font-extrabold text-foreground">{{ $incident->title }}</h4>
                            <p class="text-xs text-muted">📍 {{ $incident->location_address }}</p>
                            <p class="text-xs text-foreground bg-card-alt p-3 rounded-lg border border-border">{{ $incident->description }}</p>

                            <!-- AI Executive Summary & Report Block (Moved Inside Loop) -->
                            @if($incident->ai_summary)
                                <div class="p-4 bg-primary/5 border border-primary/20 rounded-xl space-y-2 mt-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-display font-black uppercase tracking-wider text-primary flex items-center gap-1.5">
                                            <span>🤖</span> AI COMMAND SUMMARY
                                        </span>
                                        <span class="text-[10px] bg-primary/10 text-primary font-bold px-2 py-0.5 rounded-full">Barangay 178 Camarin</span>
                                    </div>
                                    <div class="text-xs text-foreground/90 font-sans leading-relaxed whitespace-pre-line">
                                        {{ $incident->ai_summary }}
                                    </div>
                                </div>
                            @elseif($incident->after_action_report)
                                <div class="p-3 bg-card-alt border border-border rounded-xl text-xs space-y-1 mt-3">
                                    <span class="font-bold text-muted uppercase">Raw Responder Report:</span>
                                    <p class="text-foreground">{{ $incident->after_action_report }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Map -->
                        <div>
                            <div id="admin-map-{{ $incident->id }}" class="h-48 w-full rounded-lg border border-border"></div>
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