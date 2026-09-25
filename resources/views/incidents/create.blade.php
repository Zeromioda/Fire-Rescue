<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Log Incoming Fire Incident Call') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">Record the emergency details and pinpoint the location.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-6 py-3 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <!-- Leaflet Map CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-8">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <form action="{{ route('incidents.store') }}" method="POST" class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl space-y-6">
                @csrf

                <h3 class="border-b border-border pb-4 text-lg font-semibold tracking-tight text-foreground">
                    Emergency Incident Details
                </h3>

                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Incident Title -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Incident Title / Emergency Type *</label>
                        <input type="text" name="title" required placeholder="e.g., Commercial Structure Fire / Residential Blaze" class="w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30">
                    </div>

                    <!-- Severity Level -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Severity Level *</label>
                        <select name="severity" required class="w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30">
                            <option value="Low">Low (1st Alarm)</option>
                            <option value="Medium" selected>Medium (2nd Alarm)</option>
                            <option value="High">High (3rd Alarm)</option>
                            <option value="Critical">Critical (General Alarm)</option>
                        </select>
                    </div>
                </div>

                <!-- Address Input & Search Button -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-foreground">Street Address / Landmark *</label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input type="text" id="location_address" name="location_address" required placeholder="1071 Quirino Highway, Brgy. Kaligayahan Novaliches, Quezon City" class="min-w-0 flex-1 w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30">
                        <button type="button" onclick="searchAddressOnMap()" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-6 py-3 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                            Search
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">Type an address and click Search, or click/drag the pin directly on the map below.</p>
                </div>

                <!-- Interactive Map Pinpoint Box -->
                <div class="rounded-2xl bg-card-alt p-4 space-y-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span class="text-sm font-medium text-foreground">Interactive Location Map Pinpoint</span>
                        <span class="text-xs text-muted-foreground">Click or drag the pin to set the address</span>
                    </div>

                    <!-- Map Container with explicit CSS height -->
                    <div id="dispatch-map" style="height: 380px; width: 100%;" class="z-0 overflow-hidden rounded-2xl border border-border"></div>
                </div>

                <!-- Latitude & Longitude Fields -->
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Latitude *</label>
                        <input type="text" id="latitude" name="latitude" readonly required class="w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30 tabular-nums cursor-not-allowed text-muted-foreground">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Longitude *</label>
                        <input type="text" id="longitude" name="longitude" readonly required class="w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30 tabular-nums cursor-not-allowed text-muted-foreground">
                    </div>
                </div>

                <!-- Notes & Field Observations -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-foreground">Dispatcher Notes & Field Observations *</label>
                    <textarea name="description" rows="4" required placeholder="Describe caller statements, fire status, trapped occupants, chemical risks..." class="w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90">
                        Dispatch Emergency Call
                    </button>
                </div>
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