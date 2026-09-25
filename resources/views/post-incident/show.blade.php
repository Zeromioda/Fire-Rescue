@php
    $card = 'rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl';
    $tz = 'Asia/Manila';
    $fmt = fn ($m) => \App\Models\Incident::formatMinutes($m);
    $at = fn ($t) => $t ? $t->timezone($tz)->format('M d, Y g:i A') : '—';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wider text-muted-foreground">After-Action Report · Incident #{{ $incident->id }}</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-foreground">{{ $incident->title }}</h2>
            </div>
            <div class="no-print flex flex-wrap gap-2">
                <a href="{{ route('post-incident.index', ['tab' => $incident->after_action_report ? 'filed' : 'pending']) }}" class="inline-flex items-center justify-center rounded-3xl border border-border bg-card/95 px-5 py-2.5 text-sm font-semibold text-foreground transition hover:bg-card-alt">All Reports</a>
                <button type="button" onclick="window.print()" class="inline-flex items-center justify-center rounded-3xl border border-border bg-card/95 px-5 py-2.5 text-sm font-semibold text-foreground transition hover:bg-card-alt">Print</button>
            </div>
        </div>
    </x-slot>

    <style>
        @media print {
            .no-print, aside, .lg\:hidden { display: none !important; }
            .print-plain { box-shadow: none !important; break-inside: avoid; }
            div[class*="lg:pl-72"] { padding-left: 0 !important; }
        }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="no-print"><x-flash /></div>

            <!-- Incident particulars -->
            <div class="{{ $card }} print-plain space-y-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $incident->severityClasses() }}">{{ $incident->severity }} Severity</span>
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ \App\Models\Incident::stageClasses($incident->stage()) }}">{{ $incident->stage() }}</span>
                    <span class="text-xs text-muted-foreground">{{ $incident->category }}</span>
                </div>
                <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div class="sm:col-span-2 lg:col-span-3"><dt class="text-xs uppercase tracking-wider text-muted-foreground">Location</dt><dd>{{ $incident->location_address }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Call received</dt><dd class="tabular-nums">{{ $at($incident->created_at) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Units dispatched</dt><dd class="tabular-nums">{{ $at($incident->dispatched_at) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Arrived on scene</dt><dd class="tabular-nums">{{ $at($incident->arrived_at) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Under control</dt><dd class="tabular-nums">{{ $at($incident->controlled_at) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Fire out / completed</dt><dd class="tabular-nums">{{ $at($incident->resolved_at) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Response time</dt><dd class="font-semibold tabular-nums">{{ $fmt($incident->responseMinutes()) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Caller</dt><dd>{{ $incident->caller_name ?: '—' }} {{ $incident->caller_contact ? '· '.$incident->caller_contact : '' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wider text-muted-foreground">Logged by</dt><dd>{{ $incident->reporter->name ?? '—' }}</dd></div>
                </dl>
                <div>
                    <p class="text-xs uppercase tracking-wider text-muted-foreground">Initial call details</p>
                    <p class="mt-1 text-sm leading-relaxed">{{ $incident->description }}</p>
                </div>
            </div>

            <!-- Resources -->
            <div class="{{ $card }} print-plain grid gap-6 md:grid-cols-2">
                <div>
                    <h3 class="mb-2 font-semibold">Apparatus Deployed</h3>
                    @forelse ($incident->apparatuses as $unit)
                        <p class="border-b border-border py-1.5 text-sm last:border-0">{{ $unit->call_sign }} <span class="text-muted-foreground">· {{ $unit->type }} · {{ $unit->plate_number }}</span></p>
                    @empty
                        <p class="text-sm text-muted-foreground">None recorded.</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="mb-2 font-semibold">Personnel</h3>
                    @forelse ($incident->personnel as $person)
                        <p class="border-b border-border py-1.5 text-sm last:border-0">{{ $person->name }} <span class="text-muted-foreground">· {{ $person->pivot->role }}</span></p>
                    @empty
                        <p class="text-sm text-muted-foreground">None recorded.</p>
                    @endforelse
                </div>
            </div>

            <!-- AI summary & narrative -->
            @if ($incident->after_action_report)
                @if ($incident->ai_summary)
                    <div class="{{ $card }} print-plain space-y-2">
                        <h3 class="text-xs font-medium uppercase tracking-wider text-primary">AI Command Summary</h3>
                        <div class="whitespace-pre-line text-sm leading-relaxed">{{ $incident->ai_summary }}</div>
                    </div>
                @endif
                <div class="{{ $card }} print-plain space-y-2">
                    <h3 class="font-semibold">Responder Narrative</h3>
                    <p class="whitespace-pre-line text-sm leading-relaxed">{{ $incident->after_action_report }}</p>
                </div>
            @endif

            <!-- Timeline -->
            <div class="{{ $card }} print-plain space-y-3">
                <h3 class="font-semibold">Response Log</h3>
                @forelse ($incident->updates->sortBy('created_at') as $update)
                    <p class="text-sm"><span class="tabular-nums text-muted-foreground">{{ $update->created_at->timezone($tz)->format('M d, g:i A') }}</span>
                        @if ($update->stage)<span class="font-semibold">{{ $update->stage }}</span>@endif
                        <span class="text-muted-foreground">{{ $update->note }}{{ $update->user ? ' ('.$update->user->name.')' : '' }}</span></p>
                @empty
                    <p class="text-sm text-muted-foreground">No log entries.</p>
                @endforelse
            </div>

            <!-- File / edit report -->
            <form method="POST" action="{{ route('incidents.submit-report', $incident) }}" class="{{ $card }} no-print space-y-4"
                  x-data="{ open: {{ $incident->after_action_report ? 'false' : 'true' }}, busy: false }" @submit="busy = true">
                @csrf
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold">{{ $incident->after_action_report ? 'Update After-Action Report' : 'File After-Action Report' }}</h3>
                    @if ($incident->after_action_report)
                        <button type="button" @click="open = !open" class="text-sm font-semibold text-primary" x-text="open ? 'Cancel' : 'Edit report'"></button>
                    @endif
                </div>
                <div x-show="open" x-cloak class="space-y-4">
                    <p class="text-xs text-muted-foreground">Include the cause, arrival and fire-out times, casualties, and the resources used. An AI summary is generated when you submit{{ $incident->status !== 'Resolved' ? ', and the incident is closed' : '' }}.</p>
                    <textarea name="after_action_report" rows="8" required minlength="5" maxlength="10000"
                              class="w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm leading-relaxed text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30">{{ old('after_action_report', $incident->after_action_report) }}</textarea>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="busy" class="inline-flex items-center justify-center rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-60">
                            <span x-show="!busy">Submit Report</span>
                            <span x-show="busy" x-cloak>Generating AI summary…</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
