@php
    $card = 'rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl';
    $input = 'w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">{{ __('Fire Incident Reporting') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">Log incoming emergency calls and review every reported incident.</p>
            </div>
            <a href="{{ route('incidents.create') }}" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90">
                Log New Incident
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Total Incidents', $counts['total'], 'text-foreground'],
                    ['Open Incidents', $counts['active'], 'text-accent'],
                    ['Awaiting Dispatch', $counts['awaiting_dispatch'], 'text-rose-600 dark:text-rose-400'],
                    ['Reported Today', $counts['today'], 'text-primary'],
                ] as [$label, $value, $tone])
                    <div class="{{ $card }}">
                        <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">{{ $label }}</span>
                        <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums {{ $tone }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Filters -->
            <form method="GET" class="{{ $card }} grid gap-3 md:grid-cols-[1fr_auto_auto_auto_auto]">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search title, address or caller…" class="{{ $input }}">
                <select name="stage" class="{{ $input }} md:w-44">
                    <option value="">All stages</option>
                    @foreach (\App\Models\Incident::STAGES as $stage)
                        <option value="{{ $stage }}" @selected(($filters['stage'] ?? '') === $stage)>{{ $stage }}</option>
                    @endforeach
                </select>
                <select name="severity" class="{{ $input }} md:w-40">
                    <option value="">All severities</option>
                    @foreach (\App\Models\Incident::SEVERITIES as $severity)
                        <option value="{{ $severity }}" @selected(($filters['severity'] ?? '') === $severity)>{{ $severity }}</option>
                    @endforeach
                </select>
                <select name="category" class="{{ $input }} md:w-44">
                    <option value="">All categories</option>
                    @foreach (\App\Models\Incident::CATEGORIES as $category)
                        <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-3xl border border-primary/20 bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">Filter</button>
                    @if (array_filter($filters))
                        <a href="{{ route('incidents.index') }}" class="inline-flex items-center justify-center rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm font-semibold text-foreground transition hover:bg-card-alt">Clear</a>
                    @endif
                </div>
            </form>

            <!-- Incident Log -->
            <div class="{{ $card }} !p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b border-border text-xs uppercase tracking-wider text-muted-foreground">
                            <tr>
                                <th class="px-6 py-4 font-medium">Incident</th>
                                <th class="px-4 py-4 font-medium">Severity</th>
                                <th class="px-4 py-4 font-medium">Stage</th>
                                <th class="px-4 py-4 font-medium">Reported</th>
                                <th class="px-4 py-4 font-medium">Response</th>
                                <th class="px-4 py-4 font-medium">Deployed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($incidents as $incident)
                                @php($stage = $incident->stage())
                                <tr class="transition hover:bg-card-alt/60">
                                    <td class="max-w-sm px-6 py-4">
                                        <a href="{{ route('incidents.show', $incident) }}" class="font-semibold text-foreground hover:text-primary">{{ $incident->title }}</a>
                                        <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ $incident->category }} · {{ $incident->location_address }}</p>
                                    </td>
                                    <td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium {{ $incident->severityClasses() }}">{{ $incident->severity }}</span></td>
                                    <td class="px-4 py-4"><span class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium {{ \App\Models\Incident::stageClasses($stage) }}">{{ $stage }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-4 text-xs tabular-nums text-muted-foreground">
                                        {{ $incident->created_at->timezone('Asia/Manila')->format('M d, Y') }}<br>{{ $incident->created_at->timezone('Asia/Manila')->format('g:i A') }}
                                    </td>
                                    <td class="px-4 py-4 text-xs tabular-nums text-foreground">{{ \App\Models\Incident::formatMinutes($incident->responseMinutes()) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-xs text-muted-foreground">{{ $incident->apparatuses_count }} units · {{ $incident->personnel_count }} crew</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-muted-foreground">No incidents match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $incidents->links() }}
        </div>
    </div>
</x-app-layout>
