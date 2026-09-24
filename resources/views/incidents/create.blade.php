<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display font-extrabold text-xl text-primary flex items-center gap-2">
                <span>🚨</span> {{ __('Log Incoming Fire Incident Call') }}
            </h2>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-card border border-border text-foreground hover:bg-card-alt font-display font-bold text-xs rounded-lg transition uppercase tracking-wide">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <!-- Leaflet Map CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-8 bg-background min-h-screen text-foreground">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('incidents.store') }}" method="POST" class="bg-card border border-border p-6 rounded-xl shadow-sm space-y-6">
                @csrf

                <h3 class="text-xs font-display font-bold uppercase tracking-wider text-muted border-b border-border pb-3">
                    Emergency Incident Details
                </h3>

                <!-- Incident Title -->
                <div>
                    <label class="block text-xs font-display font-bold uppercase text-muted mb-1">Incident Title / Emergency Type *</label>
                    <input type="text" name="title" required placeholder="e.g., Commercial Structure Fire / Residential Blaze" class="w-full bg-background border-border text-sm text-foreground rounded-lg p-3 focus:ring-primary focus:border-primary">
                </div>

                <!-- Severity Level & Auto-Detect -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-display font-bold uppercase text-muted mb-1">Severity Level *</label>
                        <select name="severity" required class="w-full bg-background border-border text-sm text-foreground rounded-lg p-3 focus:ring-primary focus:border-primary">
                            <option value="Low">Low (1st Alarm)</option>
                            <option value="Medium" selected>Medium (2nd Alarm)</option>
                            <option value="High">High (3rd Alarm)</option>
                            <option value="Critical">Critical (General Alarm)</option>
                        </select>
                    </div>
                </div>

                <!-- Address Input & Search Button -->
                <div>
                    <label class="block text-xs font-display font-bold uppercase text-muted mb-1">Street Address / Landmark *</label>
                    <div class="flex gap-2">
                        <input type="text" id="location_address" name="location_address" required placeholder="1071 Quirino Highway, Brgy. Kaligayahan Novaliches, Quezon City" class="flex-1 bg-background border-border text-sm text-foreground rounded-lg p-3 focus:ring-primary focus:border-primary">
                        <button type="button" onclick="searchAddressOnMap()" class="px-6 py-3 bg-primary text-primary-foreground font-display font-bold text-xs rounded-lg uppercase tracking-wider hover:opacity-90 transition">
                            Search
                        </button>
                    </div>
                    <p class="text-[11px] text-muted mt-1">Type an address and click Search, or click/drag the pin directly on the map below.</p>
                </div>

                <!-- Interactive Map Pinpoint Box -->
                <div class="border border-border rounded-lg p-3 bg-card-alt">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-xs font-display font-bold uppercase text-muted">
                            Interactive Location Map Pinpoint
                        </label>
                        <span class="text-[11px] font-bold text-primary bg-background px-2.5 py-1 rounded-full border border-border">
                            Click or Drag Pin to Set Address
                        </span>
                    </div>

                    <!-- Map Container with explicit CSS height -->
                    <div id="dispatch-map" style="height: 380px; width: 100%; border-radius: 8px;" class="z-0 border border-border"></div>
                </div>

                <!-- Latitude & Longitude Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-display font-bold uppercase text-muted mb-1">Latitude *</label>
                        <input type="text" id="latitude" name="latitude" readonly required class="w-full bg-card-alt border-border text-sm text-foreground font-mono rounded-lg p-3 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-display font-bold uppercase text-muted mb-1">Longitude *</label>
                        <input type="text" id="longitude" name="longitude" readonly required class="w-full bg-card-alt border-border text-sm text-foreground font-mono rounded-lg p-3 cursor-not-allowed">
                    </div>
                </div>

                <!-- Notes & Field Observations -->
                <div>
                    <label class="block text-xs font-display font-bold uppercase text-muted mb-1">Dispatcher Notes & Field Observations *</label>
                    <textarea name="description" rows="4" required placeholder="Describe caller statements, fire status, trapped occupants, chemical risks..." class="w-full bg-background border-border text-sm text-foreground rounded-lg p-3 focus:ring-primary focus:border-primary"></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-4 bg-accent hover:opacity-90 text-accent-foreground font-display font-extrabold text-sm rounded-lg uppercase tracking-wider shadow-md transition flex items-center justify-center gap-2">
                    🚨 Dispatch Emergency Call
                </button>
            </form>
        </div>
    </div>

    <!-- Interactive Map Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Default center: Quezon City
            var defaultLat = 14.660108;
            var defaultLng = 120.998721;

            // Initialize Map
            var map = L.map('dispatch-map').setView([defaultLat, defaultLng], 14);

            // Load OpenStreetMap Tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add Draggable Red Marker Pin
            var marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

            // Set initial inputs
            updateCoordinates(defaultLat, defaultLng);

            // Force map calculation after render
            setTimeout(function() {
                map.invalidateSize();
            }, 300);

            // Drag Pin Event -> Update Lat/Lng & Reverse Geocode Address
            marker.on('dragend', function (e) {
                var position = marker.getLatLng();
                updateCoordinates(position.lat, position.lng);
                reverseGeocode(position.lat, position.lng);
            });

            // Click Map Event -> Move Pin & Update Lat/Lng & Reverse Geocode Address
            map.on('click', function (e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;
                marker.setLatLng([lat, lng]);
                updateCoordinates(lat, lng);
                reverseGeocode(lat, lng);
            });

            // Helper: Update hidden/display coordinate fields
            function updateCoordinates(lat, lng) {
                document.getElementById('latitude').value = parseFloat(lat).toFixed(6);
                document.getElementById('longitude').value = parseFloat(lng).toFixed(6);
            }

            // Reverse Geocode: Get street address from pin lat/lng
            function reverseGeocode(lat, lng) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            document.getElementById('location_address').value = data.display_name;
                        }
                    })
                    .catch(err => console.error("Geocoding error:", err));
            }

            // Search Address: Move map and pin to typed address
            window.searchAddressOnMap = function() {
                var address = document.getElementById('location_address').value;
                if (!address) return;

                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            var lat = parseFloat(data[0].lat);
                            var lng = parseFloat(data[0].lon);

                            map.setView([lat, lng], 16);
                            marker.setLatLng([lat, lng]);
                            updateCoordinates(lat, lng);
                        } else {
                            alert("Address not found on map. Please try a different landmark or click directly on the map.");
                        }
                    })
                    .catch(err => console.error("Search error:", err));
            };

            // Auto Detect Browser GPS Location
            window.detectUserLocation = function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;

                        map.setView([lat, lng], 16);
                        marker.setLatLng([lat, lng]);
                        updateCoordinates(lat, lng);
                        reverseGeocode(lat, lng);
                    }, function () {
                        alert("Device GPS location permission was denied or unavailable.");
                    });
                } else {
                    alert("Geolocation is not supported by this browser.");
                }
            };
        });
    </script>
</x-app-layout>