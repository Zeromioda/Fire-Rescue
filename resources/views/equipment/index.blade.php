<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-extrabold text-xl text-foreground flex items-center gap-2">
            <span>🚒</span> {{ __('Equipment & Apparatus Fleet Management') }}
        </h2>
    </x-slot>

    <div class="p-6 md:p-8 space-y-8 max-w-7xl mx-auto">

        <!-- Add Forms: Exclusive to Admin -->
        @role('Admin')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Register Apparatus Form -->
            <div x-data="{ open: true }" class="bg-card border border-border rounded-xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-sm font-display font-bold text-foreground flex items-center gap-2">
                        <span>🚒</span> Register New Apparatus Vehicle
                    </h3>
                    <button @click="open = !open" type="button" class="text-xs text-muted hover:text-foreground font-semibold">
                        <span x-text="open ? '- Collapse Form' : '+ Expand Form'"></span>
                    </button>
                </div>

                <form x-show="open" action="{{ route('apparatus.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Call Sign</label>
                        <input type="text" name="call_sign" placeholder="e.g. Engine 1" required class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Plate Number</label>
                        <input type="text" name="plate_number" placeholder="e.g. 123-ABCD" required class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Type</label>
                        <input type="text" name="type" placeholder="e.g. Ladder Truck, Pumper" required class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Status</label>
                            <select name="status" class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                                <option value="available">Available</option>
                                <option value="dispatched">Dispatched</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Fuel Level (%)</label>
                            <input type="number" name="fuel_level_percent" min="0" max="100" value="100" required class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-primary text-primary-foreground font-display font-bold text-xs rounded-lg shadow hover:opacity-90 transition uppercase tracking-wider">
                        Add Apparatus to Fleet
                    </button>
                </form>
            </div>

            <!-- Add Equipment Form -->
            <div x-data="{ open: true }" class="bg-card border border-border rounded-xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-sm font-display font-bold text-foreground flex items-center gap-2">
                        <span>📦</span> Add Gear / Equipment Item
                    </h3>
                    <button @click="open = !open" type="button" class="text-xs text-muted hover:text-foreground font-semibold">
                        <span x-text="open ? '- Collapse Form' : '+ Expand Form'"></span>
                    </button>
                </div>

                <form x-show="open" action="{{ route('equipment.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Item Name</label>
                        <input type="text" name="name" placeholder="e.g. Fire Hose 50ft" required class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Category</label>
                        <input type="text" name="category" placeholder="e.g. Hose, SCBA, Medical Kit" required class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Quantity</label>
                            <input type="number" name="quantity" min="1" value="1" required class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-muted mb-1">Status</label>
                            <select name="status" class="w-full bg-card-alt border border-border rounded-lg px-3 py-2 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary" required>
                                <option value="Available">Available</option>
                                <option value="Assigned">Assigned</option>
                                <option value="In Maintenance">In Maintenance</option>
                                <option value="Decommissioned">Decommissioned</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-primary text-primary-foreground font-display font-bold text-xs rounded-lg shadow hover:opacity-90 transition uppercase tracking-wider">
                        Add Equipment Item
                    </button>
                </form>
            </div>

        </div>
        @endrole

        <!-- Station Apparatus Fleet List -->
        <div class="bg-card border border-border rounded-xl p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-display font-bold text-foreground flex items-center gap-2 border-b border-border pb-3">
                <span>🚒</span> Station Apparatus Fleet
            </h3>
            @if(isset($apparatuses) && $apparatuses->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-border text-muted uppercase font-bold tracking-wider">
                                <th class="py-2 px-3">Call Sign</th>
                                <th class="py-2 px-3">Plate No.</th>
                                <th class="py-2 px-3">Type</th>
                                <th class="py-2 px-3">Fuel (%)</th>
                                <th class="py-2 px-3">Status</th>
                                @role('Admin') <th class="py-2 px-3 text-right">Action</th> @endrole
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($apparatuses as $apparatus)
                                <tr x-data="{ editing: false }">
                                    <form action="{{ route('apparatus.update', $apparatus->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')

                                        <!-- Call Sign -->
                                        <td class="py-3 px-3 font-bold text-foreground">
                                            @role('Admin')
                                                <input x-show="editing" type="text" name="call_sign" value="{{ $apparatus->call_sign }}" class="bg-card-alt border border-border rounded px-2 py-1 text-xs w-28">
                                                <span x-show="!editing">{{ $apparatus->call_sign }}</span>
                                            @else
                                                {{ $apparatus->call_sign }}
                                            @endrole
                                        </td>

                                        <!-- Plate No. -->
                                        <td class="py-3 px-3 text-muted">
                                            @role('Admin')
                                                <input x-show="editing" type="text" name="plate_number" value="{{ $apparatus->plate_number }}" class="bg-card-alt border border-border rounded px-2 py-1 text-xs w-24">
                                                <span x-show="!editing">{{ $apparatus->plate_number }}</span>
                                            @else
                                                {{ $apparatus->plate_number }}
                                            @endrole
                                        </td>

                                        <!-- Type -->
                                        <td class="py-3 px-3 text-foreground">{{ $apparatus->type }}</td>

                                        <!-- Fuel Level (Both Roles Can Edit) -->
                                        <td class="py-3 px-3 text-foreground">
                                            <div class="flex items-center gap-1">
                                                <input type="number" name="fuel_level_percent" min="0" max="100" value="{{ $apparatus->fuel_level_percent }}" class="bg-card-alt border border-border rounded px-2 py-1 text-xs w-16 focus:ring-1 focus:ring-primary">
                                                <span class="text-xs text-muted">%</span>
                                            </div>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-3 px-3">
                                            <select name="status" onchange="this.form.submit()" class="bg-card-alt border border-border rounded px-2 py-1 text-xs text-foreground font-bold uppercase focus:ring-1 focus:ring-primary">
                                                <option value="available" {{ $apparatus->status === 'available' ? 'selected' : '' }}>Available</option>
                                                <option value="dispatched" {{ $apparatus->status === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                                <option value="maintenance" {{ $apparatus->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                            </select>
                                        </td>

                                        <!-- Admin Inline Action Toggle -->
                                        @role('Admin')
                                        <td class="py-3 px-3 text-right">
                                            <button x-show="!editing" @click.prevent="editing = true" class="text-xs text-primary font-bold hover:underline">Edit Details</button>
                                            <div x-show="editing" class="flex gap-2 justify-end">
                                                <button type="submit" class="text-xs bg-primary text-primary-foreground px-2 py-1 rounded font-bold">Save</button>
                                                <button @click.prevent="editing = false" class="text-xs text-muted hover:underline">Cancel</button>
                                            </div>
                                        </td>
                                        @endrole
                                    </form>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-muted italic">No apparatus vehicles registered.</p>
            @endif
        </div>

        <!-- Equipment Inventory List -->
        <div class="bg-card border border-border rounded-xl p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-display font-bold text-foreground flex items-center gap-2 border-b border-border pb-3">
                <span>📦</span> Equipment & Gear Inventory
            </h3>
            @if(isset($equipment) && $equipment->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-border text-muted uppercase font-bold tracking-wider">
                                <th class="py-2 px-3">Asset Tag</th>
                                <th class="py-2 px-3">Item Name</th>
                                <th class="py-2 px-3">Category</th>
                                <th class="py-2 px-3">Quantity</th>
                                <th class="py-2 px-3">Status</th>
                                @role('Admin') <th class="py-2 px-3 text-right">Action</th> @endrole
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($equipment as $item)
                                <tr x-data="{ editing: false }">
                                    <form action="{{ route('equipment.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')

                                        <!-- Asset Tag (Admin Only Edit) -->
                                        <td class="py-3 px-3 font-bold text-muted">
                                            @role('Admin')
                                                <input x-show="editing" type="text" name="asset_tag" value="{{ $item->asset_tag }}" class="bg-card-alt border border-border rounded px-2 py-1 text-xs w-28">
                                                <span x-show="!editing">{{ $item->asset_tag }}</span>
                                            @else
                                                {{ $item->asset_tag }}
                                            @endrole
                                        </td>

                                        <!-- Item Name (Admin Only Edit) -->
                                        <td class="py-3 px-3 font-bold text-foreground">
                                            @role('Admin')
                                                <input x-show="editing" type="text" name="name" value="{{ $item->name }}" class="bg-card-alt border border-border rounded px-2 py-1 text-xs w-32">
                                                <span x-show="!editing">{{ $item->name }}</span>
                                            @else
                                                {{ $item->name }}
                                            @endrole
                                        </td>

                                        <!-- Category (Admin Only Edit) -->
                                        <td class="py-3 px-3 text-muted">
                                            @role('Admin')
                                                <input x-show="editing" type="text" name="category" value="{{ $item->category }}" class="bg-card-alt border border-border rounded px-2 py-1 text-xs w-28">
                                                <span x-show="!editing">{{ $item->category }}</span>
                                            @else
                                                {{ $item->category }}
                                            @endrole
                                        </td>

                                        <!-- Quantity (Admin Only Edit) -->
                                        <td class="py-3 px-3 text-foreground">
                                            @role('Admin')
                                                <input x-show="editing" type="number" name="quantity" value="{{ $item->quantity }}" min="0" class="bg-card-alt border border-border rounded px-2 py-1 text-xs w-16">
                                                <span x-show="!editing">{{ $item->quantity }}</span>
                                            @else
                                                {{ $item->quantity }}
                                            @endrole
                                        </td>

                                        <!-- Status -->
                                        <td class="py-3 px-3">
                                            <select name="status" onchange="this.form.submit()" class="bg-card-alt border border-border rounded px-2 py-1 text-xs text-foreground font-bold uppercase focus:ring-1 focus:ring-primary">
                                                <option value="Available" {{ $item->status === 'Available' ? 'selected' : '' }}>Available</option>
                                                <option value="Assigned" {{ $item->status === 'Assigned' ? 'selected' : '' }}>Assigned</option>
                                                <option value="In Maintenance" {{ $item->status === 'In Maintenance' ? 'selected' : '' }}>In Maintenance</option>
                                                @role('Admin')
                                                    <option value="Decommissioned" {{ $item->status === 'Decommissioned' ? 'selected' : '' }}>Decommissioned</option>
                                                @endrole
                                            </select>
                                        </td>

                                        <!-- Admin Inline Action Toggle -->
                                        @role('Admin')
                                        <td class="py-3 px-3 text-right">
                                            <button x-show="!editing" @click.prevent="editing = true" class="text-xs text-primary font-bold hover:underline">Edit Details</button>
                                            <div x-show="editing" class="flex gap-2 justify-end">
                                                <button type="submit" class="text-xs bg-primary text-primary-foreground px-2 py-1 rounded font-bold">Save</button>
                                                <button @click.prevent="editing = false" class="text-xs text-muted hover:underline">Cancel</button>
                                            </div>
                                        </td>
                                        @endrole
                                    </form>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-muted italic">No equipment items found in inventory.</p>
            @endif
        </div>

    </div>
</x-app-layout>