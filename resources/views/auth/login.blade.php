<x-guest-layout>
    <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 bg-background transition-colors duration-200">
        
        <!-- Fixed Max-Width Card Container -->
        <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl p-6 sm:p-8 space-y-6 relative overflow-hidden">
            
            <!-- Background Accent Glow -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-primary/10 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Card Header: Branding & Theme Switcher -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-2xl shadow-sm shrink-0">
                        🚒
                    </div>
                    <div>
                        <h2 class="font-display font-black text-xl text-foreground tracking-tight">
                            Dispatch Terminal
                        </h2>
                        <p class="text-[11px] text-muted font-mono font-bold uppercase tracking-wider">
                            BFAD Station 178 • Camarin
                        </p>
                    </div>
                </div>

                <!-- Theme Toggle Button -->
                <button id="theme-toggle-btn" type="button" class="p-2.5 rounded-xl bg-card-alt border border-border text-foreground hover:bg-card transition-all flex items-center justify-center text-xs font-bold gap-1 shadow-sm cursor-pointer shrink-0" title="Toggle Theme">
                    <span id="theme-toggle-dark-icon" class="hidden">🌙</span>
                    <span id="theme-toggle-light-icon" class="hidden">☀️</span>
                </button>
            </div>

            <!-- Session Status Alert -->
            <x-auth-session-status class="text-xs font-bold text-emerald-600 bg-emerald-500/10 p-3 rounded-xl border border-emerald-500/20" :status="session('status')" />

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email / Service ID -->
                <div>
                    <label for="email" class="block text-[10px] font-display font-black uppercase text-muted tracking-wider mb-1.5">
                        Email / Service ID
                    </label>
                    <input id="email" type="email" name="email" :value="old('email')" required autofocus 
                           placeholder="responder@caloocan.gov.ph"
                           style="color: inherit;"
                           class="w-full bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-white border border-slate-300 dark:border-zinc-600 rounded-xl p-3.5 text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary transition shadow-sm placeholder:text-slate-400 dark:placeholder:text-zinc-400" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-500" />
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-[10px] font-display font-black uppercase text-muted tracking-wider">
                            Password
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-primary hover:underline">
                                Forgot?
                            </a>
                        @endif
                    </div>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           placeholder="••••••••"
                           style="color: inherit;"
                           class="w-full bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-white border border-slate-300 dark:border-zinc-600 rounded-xl p-3.5 text-xs font-medium focus:ring-2 focus:ring-primary focus:border-primary transition shadow-sm placeholder:text-slate-400 dark:placeholder:text-zinc-400" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-500" />
                </div>

                <!-- Remember Device -->
                <div class="flex items-center justify-between pt-1">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer">
                        <input id="remember_me" type="checkbox" name="remember" class="rounded border-border bg-background text-primary focus:ring-primary w-4 h-4">
                        <span class="ms-2 text-xs font-bold text-muted">Remember this terminal</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-primary hover:opacity-90 text-primary-foreground font-display font-black text-xs rounded-xl uppercase tracking-wider transition-all shadow-md cursor-pointer flex items-center justify-center gap-2 mt-2">
                    <span>Access Terminal</span>
                    <span>➔</span>
                </button>
            </form>

            <!-- Card Footer -->
            <div class="pt-4 border-t border-border text-center">
                <p class="text-[11px] text-muted font-sans">
                    Zone 15, District III, Caloocan City • Fire & Rescue System
                </p>
            </div>
        </div>

    </div>

    <!-- Light / Dark Mode Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var darkIcon = document.getElementById('theme-toggle-dark-icon');
            var lightIcon = document.getElementById('theme-toggle-light-icon');
            var toggleBtn = document.getElementById('theme-toggle-btn');

            function syncUI() {
                var isDark = document.documentElement.classList.contains('dark');
                if (isDark) {
                    if (lightIcon) lightIcon.classList.remove('hidden');
                    if (darkIcon) darkIcon.classList.add('hidden');
                } else {
                    if (darkIcon) darkIcon.classList.remove('hidden');
                    if (lightIcon) lightIcon.classList.add('hidden');
                }
            }

            syncUI();

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    }
                    syncUI();
                });
            }
        });
    </script>
</x-guest-layout>