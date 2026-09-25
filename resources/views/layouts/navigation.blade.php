<div x-data="{ open: true, mobileOpen: false }" class="relative">

    <!-- Mobile Top Header Bar -->
    <div class="lg:hidden flex items-center justify-between h-16 px-4 bg-card border-b border-border text-foreground">
        <div class="flex items-center gap-2 font-display font-black text-lg">
            <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-600 border border-rose-500/20 text-xs">🚒</span>
            <span>BFAD 178</span>
        </div>
        <div class="flex items-center gap-2">
            <button id="theme-toggle-mobile" type="button" class="p-2 rounded-xl bg-card-alt border border-border text-foreground">
                <span class="dark:hidden">🌙</span>
                <span class="hidden dark:inline">☀️</span>
            </button>
            <button @click="mobileOpen = !mobileOpen" class="p-2 rounded-xl border border-border text-muted hover:text-foreground">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': mobileOpen, 'inline-flex': !mobileOpen }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': !mobileOpen, 'inline-flex': mobileOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Backdrop Overlay for Mobile -->
    <div x-show="mobileOpen" @click="mobileOpen = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden" x-transition.opacity></div>

    <!-- Sidebar Container -->
    <aside 
        :class="{
            'translate-x-0': mobileOpen, 
            '-translate-x-full lg:translate-x-0': !mobileOpen,
            'w-64': open,
            'w-20': !open
        }"
        class="fixed top-0 left-0 z-50 h-screen bg-card border-r border-border transition-all duration-300 flex flex-col justify-between shadow-2xl"
    >
        <!-- Top Section: Brand & Navigation -->
        <div class="space-y-6">
            
            <!-- Brand Logo Header -->
            <div class="h-16 flex items-center justify-between px-4 border-b border-border">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="p-2 rounded-xl bg-rose-500/10 text-rose-600 border border-rose-500/20 font-black text-sm flex-shrink-0">
                        🚒
                    </div>
                    <div x-show="open" class="font-display font-black text-lg text-foreground tracking-tight whitespace-nowrap transition-opacity">
                        BFAD 178
                        <span class="block text-[9px] font-mono text-muted uppercase tracking-widest font-bold">Dispatch Ops</span>
                    </div>
                </div>

                <!-- Desktop Collapse Toggle -->
                <button @click="open = !open" class="hidden lg:flex p-1.5 rounded-lg border border-border text-muted hover:text-foreground hover:bg-card-alt">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': !open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="px-3 space-y-1">
                
                <!-- Dashboard Link -->
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('dashboard') ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'text-muted hover:text-foreground hover:bg-card-alt' }}">
                    <span class="text-base flex-shrink-0">📊</span>
                    <span x-show="open" class="whitespace-nowrap">{{ __('Dashboard') }}</span>
                </a>

                <!-- Equipment & Fleet Link -->
                <a href="{{ route('equipment.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('equipment.*') ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'text-muted hover:text-foreground hover:bg-card-alt' }}">
                    <span class="text-base flex-shrink-0">📦</span>
                    <span x-show="open" class="whitespace-nowrap">{{ __('Equipment & Fleet') }}</span>
                </a>

                <!-- STRICTLY ADMIN-ONLY SECTION -->
                @role('Admin')
                    <div class="pt-2 my-2 border-t border-border">
                        <p x-show="open" class="px-3 text-[9px] font-mono font-bold text-muted uppercase tracking-wider mb-1">Admin Command</p>

                        <!-- Manage Personnel -->
                        <a href="{{ route('admin.firefighters.index') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.firefighters.*') ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'text-muted hover:text-foreground hover:bg-card-alt' }}">
                            <span class="text-base flex-shrink-0">👨‍🚒</span>
                            <span x-show="open" class="whitespace-nowrap">{{ __('Manage Personnel') }}</span>
                        </a>

                        <!-- Station Backlog / Access Log -->
                        <a href="{{ route('backlog.index') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('backlog.*') ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'text-muted hover:text-foreground hover:bg-card-alt' }}">
                            <span class="text-base flex-shrink-0">📋</span>
                            <span x-show="open" class="whitespace-nowrap">{{ __('Station Backlog') }}</span>
                        </a>

                        <!-- AI Analysis & Reports -->
                        <a href="{{ route('reports.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('reports.*') ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'text-muted hover:text-foreground hover:bg-card-alt' }}">
                            <span class="text-base flex-shrink-0">🧠</span>
                            <span x-show="open" class="whitespace-nowrap">{{ __('AI Analysis & Reports') }}</span>
                        </a>
                    </div>
                @endrole

            </nav>
        </div>

        <!-- Bottom Section: Theme Toggle & User Profile -->
        <div class="p-3 border-t border-border space-y-3 bg-card-alt/50">
            
            <!-- Dark / Light Mode Toggle Button -->
            <button id="theme-toggle" type="button" 
                    class="w-full flex items-center justify-between p-2.5 rounded-xl border border-border text-foreground hover:bg-card transition text-xs font-bold shadow-sm">
                <div class="flex items-center gap-3">
                    <span id="theme-toggle-dark-icon" class="hidden text-base">🌙</span>
                    <span id="theme-toggle-light-icon" class="hidden text-base">☀️</span>
                    <span x-show="open" class="whitespace-nowrap">Toggle Mode</span>
                </div>
            </button>

            <!-- User Info Card & Actions -->
            <div class="p-2 bg-card rounded-xl border border-border space-y-2">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-600 border border-rose-500/20 flex items-center justify-center font-bold text-xs flex-shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div x-show="open" class="overflow-hidden">
                        <p class="text-xs font-bold text-foreground truncate">{{ Auth::user()->name }}</p>
                        <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase bg-primary/10 text-primary border border-primary/20">
                            {{ Auth::user()->getRoleNames()->first() ?? (Auth::user()->rank ?? 'Firefighter') }}
                        </span>
                    </div>
                </div>

                <!-- Quick Profile & Logout Actions (Properly bound to open state) -->
                <div x-show="open" class="pt-2 border-t border-border flex items-center justify-between text-[11px] font-bold">
                    <a href="{{ route('profile.edit') }}" class="text-muted hover:text-foreground transition">Profile</a>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-rose-600 hover:text-rose-500 transition">Log Out</button>
                    </form>
                </div>

                <!-- Fallback Logout Icon Button when Sidebar is Collapsed -->
                <div x-show="!open" class="pt-2 border-t border-border flex justify-center">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Log Out" class="text-rose-600 hover:text-rose-500 transition text-sm">
                            🚪
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </aside>
</div>

<!-- Theme Handler Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var darkIcon = document.getElementById('theme-toggle-dark-icon');
        var lightIcon = document.getElementById('theme-toggle-light-icon');
        var themeBtn = document.getElementById('theme-toggle');
        var themeBtnMobile = document.getElementById('theme-toggle-mobile');

        function syncIcons() {
            if (document.documentElement.classList.contains('dark')) {
                if (lightIcon) lightIcon.classList.remove('hidden');
                if (darkIcon) darkIcon.classList.add('hidden');
            } else {
                if (darkIcon) darkIcon.classList.remove('hidden');
                if (lightIcon) lightIcon.classList.add('hidden');
            }
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            syncIcons();
        }

        syncIcons();
        if (themeBtn) themeBtn.addEventListener('click', toggleTheme);
        if (themeBtnMobile) themeBtnMobile.addEventListener('click', toggleTheme);
    });
</script>