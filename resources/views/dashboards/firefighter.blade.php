<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-display font-black text-xl text-primary flex items-center gap-2 tracking-tight">
                    <span>🚒</span> Barangay Firefighter Terminal
                </h2>
                <p class="text-xs text-muted font-mono mt-0.5">
                    STATION: <span class="font-bold text-foreground">Barangay 178 Camarin, Caloocan City, Zone 15, District III</span>
                </p>
            </div>

            <!-- Duty Status Toggle Form -->
            <form action="{{ route('responder.toggle-availability') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl font-display font-extrabold text-xs uppercase tracking-wider transition-all border flex items-center gap-2 shadow-sm cursor-pointer {{ Auth::user()->is_available ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30 hover:bg-rose-500/20' }}">
                    <span class="w-2.5 h-2.5 rounded-full animate-pulse {{ Auth::user()->is_available ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    <span>STATUS: {{ Auth::user()->is_available ? 'ON-DUTY / AVAILABLE' : 'ON-CALL / BUSY' }}</span>
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
            border-radius: 12px !important;
            padding: 8px 12px !important;
            font-size: 11px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
            max-height: 150px !important;
            overflow-y: auto !important;
            border: 1px solid #e2e8f0 !important;
        }
        .dark .leaflet-routing-container {
            background-color: rgba(27, 34, 26, 0.95) !important;
            color: #edf3ea !important;
            border-color: #2c362c !important;
        }
    </style>

    <div class="py-8 bg-background min-h-screen text-foreground transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Active Emergency Dispatches Section -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-display font-extrabold uppercase tracking-wider text-muted flex items-center gap-2">
                        <span>🚨</span> ACTIVE EMERGENCY DISPATCHES ({{ $activeIncidents->count() }})
                    </h3>
                </div>

                @if($activeIncidents->isEmpty())
                    <div class="bg-card border border-border p-10 rounded-2xl text-center space-y-3 shadow-sm">
                        <div class="w-12 h-12 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto text-xl">✅</div>
                        <h4 class="font-display font-bold text-foreground text-sm">No Active Emergency Calls</h4>
                        <p class="text-xs text-muted max-w-sm mx-auto">All clear for Barangay 178 Camarin, Caloocan City District III.</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($activeIncidents as $incident)
                            <div class="bg-card border border-border rounded-2xl p-6 shadow-sm grid grid-cols-1 lg:grid-cols-2 gap-6">
                                
                                <!-- Left Column: Incident Details & Forms -->
                                <div class="space-y-4 flex flex-col justify-between">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center border-b border-border pb-3">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-display font-black uppercase tracking-wider {{ strtolower($incident->severity) === 'high' || strtolower($incident->severity) === 'critical' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' }}">
                                                {{ $incident->severity }} Priority
                                            </span>
                                            <span class="text-xs font-mono font-bold text-muted flex items-center gap-1.5">
                                                <span>STATUS:</span>
                                                <span class="px-2 py-0.5 rounded bg-primary/10 text-primary uppercase font-bold">{{ $incident->status }}</span>
                                            </span>
                                        </div>

                                        <div>
                                            <h3 class="text-xl font-display font-extrabold text-foreground tracking-tight">{{ $incident->title }}</h3>
                                            <p class="text-xs text-muted mt-1.5 flex items-start gap-1.5 leading-relaxed">
                                                <span class="shrink-0">📍</span> 
                                                <span id="address-text-{{ $incident->id }}" class="font-medium text-foreground/90">{{ $incident->location_address }}</span>
                                            </p>
                                        </div>

                                        <div class="bg-card-alt border border-border p-3.5 rounded-xl space-y-1">
                                            <span class="block text-[10px] font-display font-bold uppercase text-muted tracking-wider">DISPATCH NOTES / OBSERVATIONS</span>
                                            <p class="text-xs text-foreground/90 font-sans leading-relaxed">{{ $incident->description }}</p>
                                        </div>

                                        <!-- Status Update Form -->
                                        <form action="{{ route('incidents.update-status', $incident) }}" method="POST" class="bg-background border border-border p-3 rounded-xl space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <label class="block text-[10px] font-display font-bold uppercase text-muted">Update Responding Status</label>
                                            <div class="flex gap-2">
                                                <select name="status" class="flex-1 bg-card border-border text-xs text-foreground rounded-lg p-2.5 font-bold focus:ring-primary focus:border-primary">
                                                    <option value="Dispatched" {{ $incident->status === 'Dispatched' ? 'selected' : '' }}>En Route (Dispatched)</option>
                                                    <option value="Under Control" {{ $incident->status === 'Under Control' ? 'selected' : '' }}>On-Scene / Under Control</option>
                                                    <option value="Resolved" {{ $incident->status === 'Resolved' ? 'selected' : '' }}>Fire Extinguished / Complete</option>
                                                </select>
                                                <button type="submit" class="px-4 py-2.5 bg-primary text-primary-foreground font-display font-bold text-xs uppercase tracking-wider rounded-lg hover:opacity-90 transition">
                                                    Update
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- After-Action Report Form -->
                                    <form action="{{ route('incidents.submit-report', $incident) }}" method="POST" class="space-y-3 pt-3 border-t border-border">
                                        @csrf
                                        <label class="block text-xs font-display font-bold uppercase text-muted tracking-wider">
                                            Fire Incident After-Action Report
                                        </label>

                                        <input type="hidden" name="location_address" id="input-address-{{ $incident->id }}" value="{{ $incident->location_address }}">
                                        <input type="hidden" name="latitude" id="input-lat-{{ $incident->id }}" value="{{ $incident->latitude }}">
                                        <input type="hidden" name="longitude" id="input-lng-{{ $incident->id }}" value="{{ $incident->longitude }}">

                                        <textarea name="after_action_report" rows="3" required placeholder="Log cause of fire, casualties, equipment used, and extinguishment time..." class="w-full bg-background border-border text-xs text-foreground rounded-xl p-3 focus:ring-primary focus:border-primary leading-relaxed">{{ $incident->after_action_report }}</textarea>

                                        <button type="submit" class="w-full py-3 bg-accent hover:opacity-90 text-accent-foreground font-display font-extrabold text-xs rounded-xl uppercase tracking-wider transition shadow-sm">
                                            Submit Final Report & Complete
                                        </button>
                                    </form>
                                </div>

                                <!-- Right Column: Interactive Road Map -->
                                <div class="space-y-3 flex flex-col justify-between">
                                    <div class="flex flex-wrap justify-between items-center text-xs gap-2">
                                        <span class="font-display font-bold uppercase text-muted flex items-center gap-1">
                                            <span>📍 TARGET GPS:</span>
                                            <span class="font-mono text-foreground font-bold" id="coords-display-{{ $incident->id }}">{{ number_format($incident->latitude, 6) }}, {{ number_format($incident->longitude, 6) }}</span>
                                        </span>
                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $incident->latitude }},{{ $incident->longitude }}" target="_blank" class="font-bold text-primary hover:underline flex items-center gap-1 bg-primary/10 px-2.5 py-1 rounded-lg">
                                            <span>🗺️</span> Open Google Maps
                                        </a>
                                    </div>

                                    <!-- Map Container Wrapper -->
                                    <div class="relative w-full rounded-xl overflow-hidden border border-border shadow-inner">
                                        <div id="map-{{ $incident->id }}" class="w-full h-[380px] z-0"></div>
                                    </div>

                                    <div class="p-2.5 bg-card-alt rounded-xl border border-border text-center">
                                        <p class="text-[11px] text-muted">
                                            💡 <b>Station Base:</b> Brgy. 178 Camarin | Drag destination pin to refine route & geocode location.
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
                <div class="pt-6 border-t border-border space-y-4">
                    <h3 class="text-xs font-display font-extrabold uppercase tracking-wider text-muted">
                        RECENTLY RESOLVED INCIDENTS (BARANGAY 178 CAMARIN)
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($resolvedIncidents as $resolved)
                            <div class="bg-card border border-border rounded-xl p-4 space-y-2 shadow-sm">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-display font-bold text-sm text-foreground">{{ $resolved->title }}</h4>
                                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20 uppercase">Resolved</span>
                                </div>
                                <p class="text-xs text-muted">📍 {{ $resolved->location_address }}</p>
                                @if($resolved->after_action_report)
                                    <div class="bg-card-alt p-3 rounded-lg text-xs text-foreground font-sans mt-2 border border-border">
                                        <b class="text-primary uppercase text-[10px] block mb-0.5">Final Report:</b>
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