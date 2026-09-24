<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-display font-black text-2xl text-foreground tracking-tight flex items-center gap-2">
                    <span>🚒</span> {{ __('Barangay Firefighter Terminal') }}
                </h2>
                <p class="text-xs text-muted font-mono font-bold uppercase tracking-wider mt-1">
                    STATION: Barangay 178 Camarin, Caloocan City • Zone 15, District III
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Live Operational Status Badge -->
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    STATUS: ON-DUTY / AVAILABLE
                </span>

                @hasrole('Admin')
                    <a href="{{ route('incidents.create') }}" 
                       class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-display font-black text-xs uppercase tracking-wider rounded-xl shadow-lg transition flex items-center gap-1.5 shrink-0">
                        <span>+</span> {{ __('Log New Call') }}
                    </a>
                @endhasrole
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-background min-h-screen text-foreground transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Welcome & Personnel Info Card -->
            <div class="bg-card border border-border rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-xl shrink-0">
                        👨‍🚒
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-foreground">
                            Welcome back, {{ Auth::user()->name }}
                        </h3>
                        <p class="text-xs text-muted mt-0.5">
                            Authenticated Personnel Dispatch Access
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-muted uppercase">Role:</span>
                    <span class="px-3 py-1 bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 rounded-lg text-xs font-mono font-bold uppercase">
                        {{ Auth::user()->roles->first()->name ?? 'Staff' }}
                    </span>
                </div>
            </div>

            <!-- Operational Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                
                <!-- Metric 1 -->
                <div class="bg-card border border-border p-5 rounded-2xl shadow-sm space-y-1">
                    <p class="text-[11px] font-mono font-bold uppercase text-muted tracking-wider">Active Dispatches</p>
                    <p class="text-3xl font-black text-foreground">0</p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">All clear in sector</p>
                </div>

                <!-- Metric 2 -->
                <div class="bg-card border border-border p-5 rounded-2xl shadow-sm space-y-1">
                    <p class="text-[11px] font-mono font-bold uppercase text-muted tracking-wider">Station Apparatus</p>
                    <p class="text-3xl font-black text-foreground">3 / 3</p>
                    <p class="text-xs text-muted font-medium">Engines Ready</p>
                </div>

                <!-- Metric 3 -->
                <div class="bg-card border border-border p-5 rounded-2xl shadow-sm space-y-1">
                    <p class="text-[11px] font-mono font-bold uppercase text-muted tracking-wider">Shift Coverage</p>
                    <p class="text-3xl font-black text-foreground">Camarin - District III</p>
                    <p class="text-xs text-muted font-medium">Zone 15 Emergency Response</p>
                </div>

            </div>

            <!-- Active Emergency Dispatches Container -->
            <div class="bg-card border border-border rounded-2xl p-6 sm:p-8 shadow-sm text-center space-y-3">
                <div class="w-12 h-12 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-2xl border border-emerald-500/20 flex items-center justify-center mx-auto text-xl">
                    ✓
                </div>
                <h4 class="font-black text-base text-foreground">
                    No Active Emergency Calls
                </h4>
                <p class="text-xs text-muted max-w-md mx-auto">
                    All clear for Barangay 178 Camarin, Caloocan City District III.
                </p>
            </div>

        </div>
    </div>
</x-app-layout> 