<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display font-black text-2xl text-foreground tracking-tight flex items-center gap-2">
                    📋 {{ __('Personnel Access & Session Log') }}
                </h2>
                <p class="text-xs text-muted font-mono uppercase tracking-wider mt-0.5">
                    BFAD Station 178 • Security & Terminal Activity Audit
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-background min-h-screen text-foreground">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Stat Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 bg-card border border-border rounded-2xl shadow-sm">
                    <p class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Total Terminal Logins</p>
                    <p class="text-3xl font-black text-foreground mt-1">{{ $totalLogins }}</p>
                </div>

                <div class="p-5 bg-card border border-border rounded-2xl shadow-sm">
                    <p class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Today's Active Responders</p>
                    <p class="text-3xl font-black text-rose-500 mt-1">{{ $todayLogins }}</p>
                </div>

                <div class="p-5 bg-card border border-border rounded-2xl shadow-sm">
                    <p class="text-[10px] font-mono font-bold uppercase text-muted tracking-wider">Unique Terminal Devices</p>
                    <p class="text-3xl font-black text-foreground mt-1">{{ $uniqueDevices }}</p>
                </div>
            </div>

            <!-- Access Log Table -->
            <div class="bg-card border border-border rounded-2xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-black text-lg text-foreground">Authentication History</h3>
                    <span class="px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20 text-[10px] font-mono font-bold uppercase">
                        Live Monitor
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-border uppercase text-[10px] font-mono text-muted">
                            <tr>
                                <th class="py-3 px-3">Personnel</th>
                                <th class="py-3 px-3">Login Time</th>
                                <th class="py-3 px-3">Device / Hardware</th>
                                <th class="py-3 px-3">IP Address</th>
                                <th class="py-3 px-3">Terminal Location</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($logs as $log)
                                <tr class="hover:bg-card-alt/50 transition">
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-foreground">{{ $log->user->name ?? 'Unknown User' }}</div>
                                        <div class="text-[10px] text-muted">{{ $log->user->email ?? 'N/A' }}</div>
                                    </td>
                                    <td class="py-3 px-3 font-mono font-bold text-rose-500">
                                        {{ $log->login_at ? \Carbon\Carbon::parse($log->login_at)->format('M d, Y • h:i:s A') : 'N/A' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-background border border-border text-[11px] font-medium">
                                            💻 {{ $log->device_type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-mono text-muted">
                                        {{ $log->ip_address }}
                                    </td>
                                    <td class="py-3 px-3 text-muted font-medium">
                                        📍 {{ $log->location }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-muted italic">
                                        No login sessions recorded yet. Log out and log back in to generate your first audit record!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-4">
                    {{ $logs->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>