<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Barangay Firefighter Terminal') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Station: Barangay 178 Camarin, Caloocan City &middot; Zone 15, District III
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Live Operational Status Badge -->
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    On-duty / Available
                </span>

                @hasrole('Admin')
                    <a href="{{ route('incidents.create') }}"
                       class="inline-flex shrink-0 items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90">
                        {{ __('Log New Call') }}
                    </a>
                @endhasrole
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Welcome & Personnel Info Card -->
            <div class="flex flex-col items-start justify-between gap-4 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl sm:flex-row sm:items-center">
                <div>
                    <h3 class="text-lg font-semibold tracking-tight text-foreground">
                        Welcome back, {{ Auth::user()->name }}
                    </h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Authenticated personnel dispatch access
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Role</span>
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                        {{ Auth::user()->roles->first()->name ?? 'Staff' }}
                    </span>
                </div>
            </div>

            <!-- Operational Metrics Grid -->
            <div class="grid gap-6 sm:grid-cols-3">

                <!-- Metric 1 -->
                <div class="space-y-1 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Active Dispatches</p>
                    <p class="text-3xl font-semibold tracking-tight tabular-nums text-foreground">0</p>
                    <p class="text-sm text-emerald-600 dark:text-emerald-400">All clear in sector</p>
                </div>

                <!-- Metric 2 -->
                <div class="space-y-1 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Station Apparatus</p>
                    <p class="text-3xl font-semibold tracking-tight tabular-nums text-foreground">3 / 3</p>
                    <p class="text-sm text-muted-foreground">Engines ready</p>
                </div>

                <!-- Metric 3 -->
                <div class="space-y-1 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Shift Coverage</p>
                    <p class="text-2xl font-semibold tracking-tight text-foreground">Camarin - District III</p>
                    <p class="text-sm text-muted-foreground">Zone 15 emergency response</p>
                </div>

            </div>

            <!-- Active Emergency Dispatches Container -->
            <div class="space-y-2 rounded-3xl border border-border bg-card/95 p-6 text-center shadow-2xl backdrop-blur-xl sm:p-8">
                <h4 class="text-lg font-semibold tracking-tight text-foreground">
                    No Active Emergency Calls
                </h4>
                <p class="mx-auto max-w-md text-sm text-muted-foreground">
                    All clear for Barangay 178 Camarin, Caloocan City District III.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
