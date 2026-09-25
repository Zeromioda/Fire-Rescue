@php
    $inputClass = 'w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30';
    $cellInputClass = 'rounded-3xl border border-border bg-card/95 px-4 py-2 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30';
    $labelClass = 'mb-2 block text-sm font-medium text-foreground';
    $primaryBtn = 'inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90';
    $smallPrimaryBtn = 'inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90';
    $smallSecondaryBtn = 'inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt';
    $cardClass = 'rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl';
    $thClass = 'py-3 px-4 text-xs font-medium uppercase tracking-wider text-muted-foreground';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Equipment & Apparatus Fleet Management') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">Track station apparatus and gear inventory.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Add Forms: Exclusive to Admin -->
            @role('Admin')
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- Register Apparatus Form -->
                <div x-data="{ open: true }" class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-4 border-b border-border pb-4">
                        <h3 class="text-lg font-semibold tracking-tight text-foreground">Register New Apparatus Vehicle</h3>
                        <button @click="open = !open" type="button" class="{{ $smallSecondaryBtn }}">
                            <span x-text="open ? 'Collapse' : 'Expand'"></span>
                        </button>
                    </div>

                    <form x-show="open" action="{{ route('apparatus.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="{{ $labelClass }}">Call Sign</label>
                            <input type="text" name="call_sign" placeholder="e.g. Engine 1" required class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Plate Number</label>
                            <input type="text" name="plate_number" placeholder="e.g. 123-ABCD" required class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Type</label>
                            <input type="text" name="type" placeholder="e.g. Ladder Truck, Pumper" required class="{{ $inputClass }}">
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="{{ $labelClass }}">Status</label>
                                <select name="status" class="{{ $inputClass }}">
                                    <option value="available">Available</option>
                                    <option value="dispatched">Dispatched</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Fuel Level (%)</label>
                                <input type="number" name="fuel_level_percent" min="0" max="100" value="100" required class="{{ $inputClass }} tabular-nums">
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="{{ $primaryBtn }}">Add Apparatus to Fleet</button>
                        </div>
                    </form>
                </div>

                <!-- Add Equipment Form -->
                <div x-data="{ open: true }" class="{{ $cardClass }} space-y-4">
                    <div class="flex items-center justify-between gap-4 border-b border-border pb-4">
                        <h3 class="text-lg font-semibold tracking-tight text-foreground">Add Gear / Equipment Item</h3>
                        <button @click="open = !open" type="button" class="{{ $smallSecondaryBtn }}">
                            <span x-text="open ? 'Collapse' : 'Expand'"></span>
                        </button>
                    </div>

                    <form x-show="open" action="{{ route('equipment.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="{{ $labelClass }}">Item Name</label>
                            <input type="text" name="name" placeholder="e.g. Fire Hose 50ft" required class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Category</label>
                            <input type="text" name="category" placeholder="e.g. Hose, SCBA, Medical Kit" required class="{{ $inputClass }}">
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="{{ $labelClass }}">Quantity</label>
                                <input type="number" name="quantity" min="1" value="1" required class="{{ $inputClass }} tabular-nums">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Status</label>
                                <select name="status" class="{{ $inputClass }}" required>
                                    <option value="Available">Available</option>
                                    <option value="Assigned">Assigned</option>
                                    <option value="In Maintenance">In Maintenance</option>
                                    <option value="Decommissioned">Decommissioned</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="{{ $primaryBtn }}">Add Equipment Item</button>
                        </div>
                    </form>
                </div>

            </div>
            @endrole

            <!-- Station Apparatus Fleet List -->
            <div class="{{ $cardClass }} space-y-4">
                <h3 class="text-lg font-semibold tracking-tight text-foreground">Station Apparatus Fleet</h3>
                @if(isset($apparatuses) && $apparatuses->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="table-stack w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-border">
                                    <th class="{{ $thClass }}">Call Sign</th>
                                    <th class="{{ $thClass }}">Plate No.</th>
                                    <th class="{{ $thClass }}">Type</th>
                                    <th class="{{ $thClass }}">Fuel (%)</th>
                                    <th class="{{ $thClass }}">Status</th>
                                    @role('Admin') <th class="{{ $thClass }} text-right">Action</th> @endrole
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach($apparatuses as $apparatus)
                                    <tr x-data="{ editing: false }">
                                        <form action="{{ route('apparatus.update', $apparatus->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')

                                            <!-- Call Sign -->
                                            <td class="py-3 px-4 text-sm font-semibold text-foreground whitespace-nowrap">
                                                @role('Admin')
                                                    <input x-show="editing" type="text" name="call_sign" value="{{ $apparatus->call_sign }}" class="{{ $cellInputClass }} w-32">
                                                    <span x-show="!editing">{{ $apparatus->call_sign }}</span>
                                                @else
                                                    {{ $apparatus->call_sign }}
                                                @endrole
                                            </td>

                                            <!-- Plate No. -->
                                            <td class="py-3 px-4 text-sm text-muted-foreground whitespace-nowrap">
                                                @role('Admin')
                                                    <input x-show="editing" type="text" name="plate_number" value="{{ $apparatus->plate_number }}" class="{{ $cellInputClass }} w-32">
                                                    <span x-show="!editing">{{ $apparatus->plate_number }}</span>
                                                @else
                                                    {{ $apparatus->plate_number }}
                                                @endrole
                                            </td>

                                            <!-- Type -->
                                            <td class="py-3 px-4 text-sm text-foreground whitespace-nowrap">{{ $apparatus->type }}</td>

                                            <!-- Fuel Level (Both Roles Can Edit) -->
                                            <td class="py-3 px-4 text-sm text-foreground">
                                                <div class="flex items-center gap-2">
                                                    <input type="number" name="fuel_level_percent" min="0" max="100" value="{{ $apparatus->fuel_level_percent }}" class="{{ $cellInputClass }} w-24 tabular-nums">
                                                    <span class="text-xs text-muted-foreground">%</span>
                                                </div>
                                            </td>

                                            <!-- Status -->
                                            <td class="py-3 px-4 text-sm">
                                                <select name="status" onchange="this.form.submit()" class="{{ $cellInputClass }} pr-10">
                                                    <option value="available" {{ $apparatus->status === 'available' ? 'selected' : '' }}>Available</option>
                                                    <option value="dispatched" {{ $apparatus->status === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                                    <option value="maintenance" {{ $apparatus->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                                </select>
                                            </td>

                                            <!-- Admin Inline Action Toggle -->
                                            @role('Admin')
                                            <td class="py-3 px-4 text-sm text-right whitespace-nowrap">
                                                <button x-show="!editing" @click.prevent="editing = true" class="{{ $smallSecondaryBtn }}">Edit Details</button>
                                                <div x-show="editing" class="flex gap-2 justify-end">
                                                    <button type="submit" class="{{ $smallPrimaryBtn }}">Save</button>
                                                    <button @click.prevent="editing = false" class="{{ $smallSecondaryBtn }}">Cancel</button>
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
                    <p class="text-sm text-muted-foreground">No apparatus vehicles registered.</p>
                @endif
            </div>

            <!-- Equipment Inventory List -->
            <div class="{{ $cardClass }} space-y-4">
                <h3 class="text-lg font-semibold tracking-tight text-foreground">Equipment & Gear Inventory</h3>
                @if(isset($equipment) && $equipment->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="table-stack w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-border">
                                    <th class="{{ $thClass }}">Asset Tag</th>
                                    <th class="{{ $thClass }}">Item Name</th>
                                    <th class="{{ $thClass }}">Category</th>
                                    <th class="{{ $thClass }}">Quantity</th>
                                    <th class="{{ $thClass }}">Status</th>
                                    @role('Admin') <th class="{{ $thClass }} text-right">Action</th> @endrole
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach($equipment as $item)
                                    <tr x-data="{ editing: false }">
                                        <form action="{{ route('equipment.update', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')

                                            <!-- Asset Tag (Admin Only Edit) -->
                                            <td class="py-3 px-4 text-sm text-muted-foreground whitespace-nowrap tabular-nums">
                                                @role('Admin')
                                                    <input x-show="editing" type="text" name="asset_tag" value="{{ $item->asset_tag }}" class="{{ $cellInputClass }} w-32">
                                                    <span x-show="!editing">{{ $item->asset_tag }}</span>
                                                @else
                                                    {{ $item->asset_tag }}
                                                @endrole
                                            </td>

                                            <!-- Item Name (Admin Only Edit) -->
                                            <td class="py-3 px-4 text-sm font-semibold text-foreground whitespace-nowrap">
                                                @role('Admin')
                                                    <input x-show="editing" type="text" name="name" value="{{ $item->name }}" class="{{ $cellInputClass }} w-40">
                                                    <span x-show="!editing">{{ $item->name }}</span>
                                                @else
                                                    {{ $item->name }}
                                                @endrole
                                            </td>

                                            <!-- Category (Admin Only Edit) -->
                                            <td class="py-3 px-4 text-sm text-muted-foreground whitespace-nowrap">
                                                @role('Admin')
                                                    <input x-show="editing" type="text" name="category" value="{{ $item->category }}" class="{{ $cellInputClass }} w-32">
                                                    <span x-show="!editing">{{ $item->category }}</span>
                                                @else
                                                    {{ $item->category }}
                                                @endrole
                                            </td>

                                            <!-- Quantity (Admin Only Edit) -->
                                            <td class="py-3 px-4 text-sm text-foreground tabular-nums">
                                                @role('Admin')
                                                    <input x-show="editing" type="number" name="quantity" value="{{ $item->quantity }}" min="0" class="{{ $cellInputClass }} w-24 tabular-nums">
                                                    <span x-show="!editing">{{ $item->quantity }}</span>
                                                @else
                                                    {{ $item->quantity }}
                                                @endrole
                                            </td>

                                            <!-- Status -->
                                            <td class="py-3 px-4 text-sm">
                                                <select name="status" onchange="this.form.submit()" class="{{ $cellInputClass }} pr-10">
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
                                            <td class="py-3 px-4 text-sm text-right whitespace-nowrap">
                                                <button x-show="!editing" @click.prevent="editing = true" class="{{ $smallSecondaryBtn }}">Edit Details</button>
                                                <div x-show="editing" class="flex gap-2 justify-end">
                                                    <button type="submit" class="{{ $smallPrimaryBtn }}">Save</button>
                                                    <button @click.prevent="editing = false" class="{{ $smallSecondaryBtn }}">Cancel</button>
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
                    <p class="text-sm text-muted-foreground">No equipment items found in inventory.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
