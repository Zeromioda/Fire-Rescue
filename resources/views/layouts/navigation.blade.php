@php
    $navItem = fn (bool $active) => $active
        ? 'flex items-center rounded-3xl px-4 py-2.5 text-sm font-semibold bg-primary text-primary-foreground shadow-lg shadow-primary/20 transition'
        : 'flex items-center rounded-3xl px-4 py-2.5 text-sm font-medium text-muted-foreground hover:bg-card-alt hover:text-foreground transition';
@endphp

<div class="relative">

    <!-- Desktop: reopen button when sidebar is hidden -->
    <button type="button" x-show="!sidebarOpen" x-cloak @click="sidebarOpen = true"
            class="fixed left-4 top-4 z-40 hidden rounded-3xl border border-border bg-card/95 px-5 py-2.5 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt lg:inline-flex">
        Menu
    </button>

    <!-- Mobile Top Bar -->
    <div class="lg:hidden sticky top-0 z-30 px-4 pt-4">
        <div class="flex items-center justify-between rounded-3xl border border-border bg-card/95 px-5 py-3 shadow-2xl backdrop-blur-xl">
            <div class="leading-tight">
                <p class="text-base font-semibold tracking-tight text-foreground">BFAD 178</p>
                <p class="text-xs text-muted-foreground">Dispatch Ops</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-theme-toggle class="rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                    <span class="dark:hidden">Dark</span>
                    <span class="hidden dark:inline">Light</span>
                </button>
                <button type="button" @click="mobileOpen = !mobileOpen" class="rounded-3xl border border-border bg-card/95 p-2 text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt" aria-label="Toggle menu">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                        <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Backdrop -->
    <div x-show="mobileOpen" x-cloak @click="mobileOpen = false" class="fixed inset-0 z-40 bg-background/70 backdrop-blur-sm lg:hidden" x-transition.opacity></div>

    <!-- Sidebar -->
    <aside
        :class="[
            mobileOpen ? 'translate-x-0' : '-translate-x-[120%]',
            sidebarOpen ? 'lg:translate-x-0' : 'lg:-translate-x-[120%]',
        ]"
        class="fixed inset-y-4 left-4 z-50 flex w-64 flex-col justify-between gap-4 rounded-3xl border border-border bg-card/95 p-4 shadow-2xl backdrop-blur-xl transition-transform duration-300"
    >
        <div class="min-h-0 space-y-6 overflow-y-auto">
            <!-- Brand -->
            <div class="flex items-start justify-between gap-2 px-2 pt-2">
                <div>
                    <p class="text-lg font-semibold tracking-tight text-foreground">BFAD 178</p>
                    <p class="text-xs text-muted-foreground">Dispatch Ops · Camarin</p>
                </div>
                <button type="button" @click="sidebarOpen = false"
                        class="hidden rounded-3xl border border-border bg-card/95 px-3 py-1.5 text-xs font-semibold text-muted-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt hover:text-foreground lg:inline-flex">
                    Hide
                </button>
            </div>

            <!-- Navigation -->
            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}" class="{{ $navItem(request()->routeIs('dashboard')) }}">
                    {{ __('Dashboard') }}
                </a>

                @role('Admin')
                    <p class="px-4 pb-1 pt-5 text-xs font-medium uppercase tracking-wider text-muted-foreground">Operations</p>

                    <a href="{{ route('incidents.index') }}" class="{{ $navItem(request()->routeIs('incidents.*')) }}">
                        {{ __('Fire Incident Reporting') }}
                    </a>
                    <a href="{{ route('dispatch.index') }}" class="{{ $navItem(request()->routeIs('dispatch.*')) }}">
                        {{ __('Rescue Operation Dispatch') }}
                    </a>
                    <a href="{{ route('equipment.index') }}" class="{{ $navItem(request()->routeIs('equipment.*')) }}">
                        {{ __('Resource & Equipment') }}
                    </a>
                    <a href="{{ route('tracking.index') }}" class="{{ $navItem(request()->routeIs('tracking.*')) }}">
                        {{ __('Emergency Response Tracking') }}
                    </a>
                    <a href="{{ route('post-incident.index') }}" class="{{ $navItem(request()->routeIs('post-incident.*')) }}">
                        {{ __('Post-Incident Reporting') }}
                    </a>
                @else
                    <a href="{{ route('equipment.index') }}" class="{{ $navItem(request()->routeIs('equipment.*')) }}">
                        {{ __('Resource & Equipment') }}
                    </a>
                @endrole

                @role('Admin')
                    <p class="px-4 pb-1 pt-5 text-xs font-medium uppercase tracking-wider text-muted-foreground">Admin</p>

                    <a href="{{ route('admin.firefighters.index') }}" class="{{ $navItem(request()->routeIs('admin.firefighters.*')) }}">
                        {{ __('Manage Personnel') }}
                    </a>
                    <a href="{{ route('backlog.index') }}" class="{{ $navItem(request()->routeIs('backlog.*')) }}">
                        {{ __('Station Backlog') }}
                    </a>
                    <a href="{{ route('reports.index') }}" class="{{ $navItem(request()->routeIs('reports.*')) }}">
                        {{ __('AI Analysis & Reports') }}
                    </a>
                @endrole
            </nav>
        </div>

        <!-- Footer: Theme & User -->
        <div class="space-y-3">
            <button type="button" data-theme-toggle class="flex w-full items-center justify-between rounded-3xl border border-border bg-card/95 px-4 py-2.5 text-sm font-medium text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                <span>Theme</span>
                <span class="text-muted-foreground">
                    <span class="dark:hidden">Light</span>
                    <span class="hidden dark:inline">Dark</span>
                </span>
            </button>

            <div class="rounded-3xl border border-border bg-card/95 p-4 shadow-2xl backdrop-blur-xl">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-sm font-semibold text-primary">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-foreground">{{ Auth::user()->name }}</p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ Auth::user()->getRoleNames()->first() ?? (Auth::user()->rank ?? 'Firefighter') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-border pt-3 text-sm font-medium">
                    <a href="{{ route('profile.edit') }}" class="text-muted-foreground transition hover:text-foreground">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-destructive transition hover:opacity-80">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </aside>
</div>

