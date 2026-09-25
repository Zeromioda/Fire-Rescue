<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Personnel Access & Session Log') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    BFAD Station 178 · Security and terminal activity audit
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Stat Cards Grid -->
            <div class="grid grid-cols-2 gap-3 sm:gap-6 lg:grid-cols-3">
                <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Total Terminal Logins</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-foreground">{{ $totalLogins }}</p>
                </div>

                <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Today's Active Responders</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-primary">{{ $todayLogins }}</p>
                </div>

                <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Unique Terminal Devices</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-foreground">{{ $uniqueDevices }}</p>
                </div>
            </div>

            <!-- Access Log Table -->
            <div class="space-y-4 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold tracking-tight text-foreground">Authentication History</h3>
                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        Live Monitor
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table-stack w-full text-left text-sm">
                        <thead class="border-b border-border">
                            <tr class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Personnel</th>
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Login Time</th>
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Device / Hardware</th>
                                <th class="whitespace-nowrap px-4 py-3 font-medium">IP Address</th>
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Terminal Location</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($logs as $log)
                                <tr class="transition hover:bg-card-alt/50">
                                    <td class="px-4 py-3 text-sm">
                                        <div class="font-medium text-foreground">{{ $log->user->name ?? 'Unknown User' }}</div>
                                        <div class="text-xs text-muted-foreground">{{ $log->user->email ?? 'N/A' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm tabular-nums text-foreground">
                                        {{ $log->login_at ? \Carbon\Carbon::parse($log->login_at)->format('M d, Y · h:i:s A') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex whitespace-nowrap rounded-full bg-card-alt px-3 py-1 text-xs font-medium text-foreground">
                                            {{ $log->device_type }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm tabular-nums text-muted-foreground">
                                        {{ $log->ip_address }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ $log->location }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                        No login sessions recorded yet. Log out and log back in to generate your first audit record.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $logs->links() }}

        </div>
    </div>
</x-app-layout>
