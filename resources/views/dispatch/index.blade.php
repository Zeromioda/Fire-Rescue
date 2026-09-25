@php
    $card = 'rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl';
    $input = 'w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30';
    $tz = 'Asia/Manila';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">{{ __('Rescue Operation Dispatch') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">Assign apparatus and response crews to open incidents.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-emerald-500/10 px-3 py-1.5 font-medium text-emerald-600 dark:text-emerald-400">{{ $availableUnits->count() }} / {{ $apparatuses->count() }} units available</span>
                <span class="rounded-full bg-sky-500/10 px-3 py-1.5 font-medium text-sky-600 dark:text-sky-400">{{ $availableResponders->count() }} / {{ $responders->count() }} responders available</span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            @if ($incidents->isEmpty())
                <div class="{{ $card }} py-12 text-center">
                    <h3 class="text-lg font-semibold">No open incidents</h3>
                    <p class="mt-1 text-sm text-muted-foreground">All incidents are resolved. New calls logged in Fire Incident Reporting will appear here for dispatch.</p>
                    <a href="{{ route('incidents.create') }}" class="mt-4 inline-flex items-center justify-center rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">Log New Incident</a>
                </div>
            @else
                <div class="grid gap-6 xl:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">

                    <!-- Open incidents queue -->
                    <div class="space-y-3">
                        <h3 class="px-1 text-sm font-semibold uppercase tracking-wider text-muted-foreground">Open Incidents ({{ $incidents->count() }})</h3>
                        @foreach ($incidents as $incident)
                            @php($stage = $incident->stage())
                            <a href="{{ route('dispatch.index', ['incident' => $incident->id]) }}"
                               class="block rounded-3xl border p-4 transition {{ $selected && $selected->id === $incident->id ? 'border-primary bg-primary/5' : 'border-border bg-card/95 hover:bg-card-alt' }}">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $incident->severityClasses() }}">{{ $incident->severity }}</span>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ \App\Models\Incident::stageClasses($stage) }}">{{ $stage === 'Reported' ? 'Awaiting Dispatch' : $stage }}</span>
                                </div>
                                <p class="mt-2 text-sm font-semibold text-foreground">{{ $incident->title }}</p>
                                <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ $incident->location_address }}</p>
                                <p class="mt-2 text-xs text-muted-foreground">
                                    {{ $incident->created_at->timezone($tz)->diffForHumans() }} · {{ $incident->apparatuses->count() }} units · {{ $incident->personnel->count() }} crew
                                </p>
                            </a>
                        @endforeach
                    </div>

                    <!-- Selected incident -->
                    @if ($selected)
                        <div class="space-y-6">
                            <div class="{{ $card }} space-y-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-xs text-muted-foreground">#{{ $selected->id }} · {{ $selected->category }} · reported {{ $selected->created_at->timezone($tz)->format('M d, g:i A') }}</p>
                                        <h3 class="mt-1 text-xl font-semibold tracking-tight">{{ $selected->title }}</h3>
                                        <p class="mt-1 text-sm text-muted-foreground">{{ $selected->location_address }}</p>
                                    </div>
                                    <a href="{{ route('incidents.show', $selected) }}" class="shrink-0 text-sm font-semibold text-primary hover:underline">View details</a>
                                </div>
                                <p class="rounded-2xl bg-card-alt p-4 text-sm leading-relaxed">{{ $selected->description }}</p>
                                @if ($selected->caller_name || $selected->caller_contact)
                                    <p class="text-xs text-muted-foreground">Caller: {{ $selected->caller_name ?: 'Unknown' }} {{ $selected->caller_contact ? '· '.$selected->caller_contact : '' }}</p>
                                @endif

                                <!-- Currently deployed -->
                                <div class="border-t border-border pt-4">
                                    <p class="mb-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">Currently deployed</p>
                                    @if ($selected->apparatuses->isEmpty() && $selected->personnel->isEmpty())
                                        <p class="text-sm text-rose-600 dark:text-rose-400">No resources deployed yet.</p>
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($selected->apparatuses as $unit)
                                                <form method="POST" action="{{ route('dispatch.release', [$selected, $unit]) }}" class="inline-flex items-center gap-2 rounded-full bg-amber-500/10 py-1 pl-3 pr-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                                                    @csrf
                                                    {{ $unit->call_sign }}
                                                    <button type="submit" title="Release unit" onclick="return confirm('Release {{ $unit->call_sign }} back to service?')" class="rounded-full px-2 py-0.5 hover:bg-amber-500/20">Release</button>
                                                </form>
                                            @endforeach
                                            @foreach ($selected->personnel as $person)
                                                <span class="rounded-full bg-sky-500/10 px-3 py-1 text-xs font-medium text-sky-700 dark:text-sky-300">{{ $person->name }} · {{ $person->pivot->role }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Dispatch form -->
                            <form method="POST" action="{{ route('dispatch.store', $selected) }}" class="{{ $card }} space-y-6"
                                  x-data="{ busy: false }" @submit="busy = true">
                                @csrf
                                <h3 class="text-lg font-semibold tracking-tight">{{ $selected->status === 'Pending' ? 'Dispatch Resources' : 'Send Reinforcements' }}</h3>

                                <div>
                                    <p class="mb-2 text-sm font-medium">Apparatus</p>
                                    @if ($availableUnits->isEmpty())
                                        <p class="text-sm text-muted-foreground">No apparatus is available right now.</p>
                                    @else
                                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($availableUnits as $unit)
                                                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-border bg-card-alt/50 p-3 text-sm transition has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                                    <input type="checkbox" name="apparatus_ids[]" value="{{ $unit->id }}" class="rounded border-border text-primary focus:ring-primary" @checked(in_array($unit->id, old('apparatus_ids', [])))>
                                                    <span class="min-w-0">
                                                        <span class="block font-semibold">{{ $unit->call_sign }}</span>
                                                        <span class="block truncate text-xs text-muted-foreground">{{ $unit->type }} · fuel {{ $unit->fuel_level_percent }}%{{ $unit->water_capacity_liters ? ' · '.number_format($unit->water_capacity_liters).'L' : '' }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    @php($busyUnits = $apparatuses->where('status', '!=', 'available'))
                                    @if ($busyUnits->isNotEmpty())
                                        <p class="mt-2 text-xs text-muted-foreground">Unavailable: {{ $busyUnits->map(fn ($u) => $u->call_sign.' ('.$u->status.')')->implode(', ') }}</p>
                                    @endif
                                </div>

                                <div>
                                    <p class="mb-2 text-sm font-medium">Response crew <span class="font-normal text-muted-foreground">— choose a role to assign a responder</span></p>
                                    @if ($availableResponders->isEmpty())
                                        <p class="text-sm text-muted-foreground">No responders are available right now.</p>
                                    @else
                                        <div class="grid gap-2 md:grid-cols-2">
                                            @foreach ($availableResponders as $person)
                                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-card-alt/50 p-3">
                                                    <div class="min-w-0 text-sm">
                                                        <p class="truncate font-semibold">{{ $person->name }}</p>
                                                        <p class="text-xs text-muted-foreground">{{ $person->badge_number ?? 'No badge' }}</p>
                                                    </div>
                                                    <select name="personnel[{{ $person->id }}]" class="w-40 shrink-0 rounded-2xl border border-border bg-card px-3 py-2 text-xs text-foreground focus:border-primary focus:ring-primary">
                                                        <option value="">Not assigned</option>
                                                        @foreach ($roles as $role)
                                                            <option value="{{ $role }}" @selected(old('personnel.'.$person->id) === $role)>{{ $role }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label for="note" class="mb-2 block text-sm font-medium">Dispatch note <span class="font-normal text-muted-foreground">(optional)</span></label>
                                    <input id="note" name="note" value="{{ old('note') }}" maxlength="1000" placeholder="e.g. Approach via Zabarte Road, hydrant at corner" class="{{ $input }}">
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" :disabled="busy" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl transition hover:bg-primary/90 disabled:opacity-60">
                                        <span x-show="!busy">{{ $selected->status === 'Pending' ? 'Dispatch Now' : 'Send Reinforcements' }}</span>
                                        <span x-show="busy" x-cloak>Dispatching…</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
