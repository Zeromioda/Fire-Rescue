<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    Barangay Firefighter Terminal
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Station: <span class="font-medium text-foreground">Barangay 178 Camarin, Caloocan City, Zone 15, District III</span>
                </p>
            </div>

            <!-- Duty Status Toggle Form -->
            <form action="{{ route('responder.toggle-availability') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-6 py-3 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                    <span class="h-2.5 w-2.5 rounded-full animate-pulse {{ Auth::user()->is_available ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    <span>Status: {{ Auth::user()->is_available ? 'On-duty / Available' : 'On-call / Busy' }}</span>
                </button>
            </form>
        </div>
    </x-slot>

    <!-- Leaflet & Routing Machine Assets -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

    <style>
        /* Scoped Compact Leaflet Routing Overlay */
        .leaflet-routing-container {
            background-color: rgba(255, 255, 255, 0.95) !important;
            border-radius: 16px !important;
            padding: 8px 12px !important;
            font-size: 12px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
            max-height: 150px !important;
            overflow-y: auto !important;
            border: 1px solid #e2e8f0 !important;
        }
        .dark .leaflet-routing-container {
            background-color: hsl(var(--card) / 0.95) !important;
            color: hsl(var(--foreground)) !important;
            border-color: hsl(var(--border)) !important;
        }
    </style>

    <div class="py-8">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Active Emergency Dispatches Section -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold tracking-tight text-foreground">
                    Active Emergency Dispatches <span class="tabular-nums text-muted-foreground">({{ $activeIncidents->count() }})</span>
                </h3>

                @if($activeIncidents->isEmpty())
                    <div class="space-y-2 rounded-3xl border border-border bg-card/95 p-6 text-center shadow-2xl backdrop-blur-xl sm:p-10">
                        <h4 class="text-lg font-semibold tracking-tight text-foreground">No Active Emergency Calls</h4>
                        <p class="mx-auto max-w-sm text-sm text-muted-foreground">All clear for Barangay 178 Camarin, Caloocan City District III.</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($activeIncidents as $incident)
                            <div class="grid grid-cols-1 gap-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl lg:grid-cols-2">

                                <!-- Left Column: Incident Details & Forms -->
                                <div class="flex min-w-0 flex-col justify-between space-y-4">
                                    <div class="space-y-4">
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border pb-4">
                                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ strtolower($incident->severity) === 'high' || strtolower($incident->severity) === 'critical' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400' }}">
                                                {{ $incident->severity }} Priority
                                            </span>
                                            <span class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                                <span>Status</span>
                                                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">{{ $incident->status }}</span>
                                            </span>
                                        </div>

                                        <div>
                                            <h3 class="text-xl font-semibold tracking-tight text-foreground">{{ $incident->title }}</h3>
                                            <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                                                <span id="address-text-{{ $incident->id }}">{{ $incident->location_address }}</span>
                                            </p>
                                        </div>

                                        <div class="space-y-1 rounded-2xl bg-card-alt p-4">
                                            <span class="block text-xs font-medium uppercase tracking-wider text-muted-foreground">Dispatch Notes / Observations</span>
                                            <p class="text-sm leading-relaxed text-foreground">{{ $incident->description }}</p>
                                        </div>

                                        <!-- Status Update Form -->
                                        <form action="{{ route('incidents.update-status', $incident) }}" method="POST" class="space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <label for="status-{{ $incident->id }}" class="mb-2 block text-sm font-medium text-foreground">Update Responding Status</label>
                                            <div class="flex flex-col gap-3 sm:flex-row">
                                                <select id="status-{{ $incident->id }}" name="status" class="w-full flex-1 rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30">
                                                    <option value="Dispatched" {{ $incident->status === 'Dispatched' ? 'selected' : '' }}>En Route (Dispatched)</option>
                                                    <option value="Under Control" {{ $incident->status === 'Under Control' ? 'selected' : '' }}>On-Scene / Under Control</option>
                                                    <option value="Resolved" {{ $incident->status === 'Resolved' ? 'selected' : '' }}>Fire Extinguished / Complete</option>
                                                </select>
                                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90">
                                                    Update
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- After-Action Report Form -->
                                    <form action="{{ route('incidents.submit-report', $incident) }}" method="POST" class="space-y-3 border-t border-border pt-4">
                                        @csrf
                                        <label for="report-{{ $incident->id }}" class="mb-2 block text-sm font-medium text-foreground">
                                            Fire Incident After-Action Report
                                        </label>

                                        <input type="hidden" name="location_address" id="input-address-{{ $incident->id }}" value="{{ $incident->location_address }}">
                                        <input type="hidden" name="latitude" id="input-lat-{{ $incident->id }}" value="{{ $incident->latitude }}">
                                        <input type="hidden" name="longitude" id="input-lng-{{ $incident->id }}" value="{{ $incident->longitude }}">

                                        <textarea id="report-{{ $incident->id }}" name="after_action_report" rows="3" required placeholder="Log cause of fire, casualties, equipment used, and extinguishment time..." class="w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm leading-relaxed text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30">{{ $incident->after_action_report }}</textarea>

                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90">
                                            Submit Final Report &amp; Complete
                                        </button>
                                    </form>
                                </div>

                                <!-- Right Column: Interactive Road Map -->
                                <div class="flex min-w-0 flex-col justify-between space-y-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="text-sm">
                                            <span class="block text-xs font-medium uppercase tracking-wider text-muted-foreground">Target GPS</span>
                                            <span class="font-medium tabular-nums text-foreground" id="coords-display-{{ $incident->id }}">{{ number_format($incident->latitude, 6) }}, {{ number_format($incident->longitude, 6) }}</span>
                                        </div>
                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $incident->latitude }},{{ $incident->longitude }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                                            Open Google Maps
                                        </a>
                                    </div>

                                    <!-- Map Container Wrapper -->
                                    <div class="relative w-full overflow-hidden rounded-2xl border border-border">
                                        <div id="map-{{ $incident->id }}" class="z-0 h-[380px] w-full"></div>
                                    </div>

                                    <div class="rounded-2xl bg-card-alt p-4 text-center">
                                        <p class="text-xs text-muted-foreground">
                                            <span class="font-medium text-foreground">Station Base:</span> Brgy. 178 Camarin | Drag destination pin to refine route &amp; geocode location.
                                        </p>
                                    </div>
                                </div>

                            </div>

                            <!-- OSRM Routing Script -->
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    var stationLat = 14.755200;
                                    var stationLng = 121.042800;

                                    var incLat = parseFloat("{{ $incident->latitude }}") || 14.755097;
                                    var incLng = parseFloat("{{ $incident->longitude }}") || 121.052449;

                                    var map = L.map('map-{{ $incident->id }}', {
                                        zoomControl: true,
                                        scrollWheelZoom: false
                                    }).setView([incLat, incLng], 14);

                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                        maxZoom: 19,
                                        attribution: '© OpenStreetMap'
                                    }).addTo(map);

                                    var stationIcon = L.icon({
                                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                        iconSize: [25, 41],
                                        iconAnchor: [12, 41],
                                        popupAnchor: [1, -34],
                                        shadowSize: [41, 41]
                                    });

                                    var routingControl = L.Routing.control({
                                        waypoints: [
                                            L.latLng(stationLat, stationLng),
                                            L.latLng(incLat, incLng)
                                        ],
                                        routeWhileDragging: true,
                                        showAlternatives: false,
                                        lineOptions: {
                                            styles: [{ color: '#dc2626', opacity: 0.9, weight: 6 }]
                                        },
                                        createMarker: function(i, waypoint, n) {
                                            if (i === 0) {
                                                return L.marker(waypoint.latLng, { icon: stationIcon })
                                                    .bindPopup('<b>Fire Station Base</b><br>Brgy. 178 Camarin, Caloocan');
                                            } else {
                                                var targetMarker = L.marker(waypoint.latLng, { draggable: true })
                                                    .bindPopup('<b>Incident Pinpoint</b>');

                                                targetMarker.on('dragend', function(e) {
                                                    var pos = targetMarker.getLatLng();

                                                    document.getElementById('coords-display-{{ $incident->id }}').innerText = pos.lat.toFixed(6) + ', ' + pos.lng.toFixed(6);
                                                    document.getElementById('input-lat-{{ $incident->id }}').value = pos.lat.toFixed(6);
                                                    document.getElementById('input-lng-{{ $incident->id }}').value = pos.lng.toFixed(6);

                                                    routingControl.spliceWaypoints(1, 1, L.latLng(pos.lat, pos.lng));

                                                    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${pos.lat}&lon=${pos.lng}`)
                                                        .then(res => res.json())
                                                        .then(data => {
                                                            if (data && data.display_name) {
                                                                document.getElementById('address-text-{{ $incident->id }}').innerText = data.display_name;
                                                                document.getElementById('input-address-{{ $incident->id }}').value = data.display_name;
                                                            }
                                                        })
                                                        .catch(err => console.error(err));
                                                });

                                                return targetMarker;
                                            }
                                        }
                                    }).addTo(map);

                                    setTimeout(function() {
                                        map.invalidateSize();
                                    }, 400);
                                });
                            </script>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Recently Resolved Section -->
            @if(isset($resolvedIncidents) && $resolvedIncidents->isNotEmpty())
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold tracking-tight text-foreground">
                        Recently Resolved Incidents
                        <span class="block text-sm font-normal text-muted-foreground">Barangay 178 Camarin</span>
                    </h3>

                    <div class="grid gap-6 md:grid-cols-2">
                        @foreach($resolvedIncidents as $resolved)
                            <div class="min-w-0 space-y-3 rounded-3xl border border-border bg-card/95 p-5 shadow-2xl backdrop-blur-xl">
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-foreground">{{ $resolved->title }}</h4>
                                    <span class="shrink-0 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">Resolved</span>
                                </div>
                                <p class="text-sm text-muted-foreground">{{ $resolved->location_address }}</p>
                                @if($resolved->after_action_report)
                                    <div class="rounded-2xl bg-card-alt p-4 text-sm text-foreground">
                                        <span class="mb-1 block text-xs font-medium uppercase tracking-wider text-primary">Final Report</span>
                                        {{ $resolved->after_action_report }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
