{{--
    Idle auto-logout. After (idle_timeout - idle_warning) seconds without activity a
    countdown card appears; when it reaches 0 the user is logged out.
    Activity is shared across tabs via localStorage, and a throttled keep-alive
    ping keeps the server-side check (LogoutIdleUsers middleware) in sync.
--}}
<div x-data="idleTimeout({
        timeout: {{ config('auth.idle_timeout') }},
        warning: {{ config('auth.idle_warning') }},
        pingEvery: {{ \App\Http\Middleware\LogoutIdleUsers::KEEP_ALIVE_GRACE }},
        keepAliveUrl: '{{ route('session.keep-alive') }}',
        logoutUrl: '{{ route('logout') }}',
        loginUrl: '{{ route('login') }}',
     })"
     x-show="warning"
     x-cloak
     x-transition.opacity
     class="fixed inset-0 z-[1000] flex items-center justify-center bg-background/70 backdrop-blur-sm p-4"
     role="alertdialog" aria-modal="true" aria-labelledby="idle-title" aria-describedby="idle-desc">

    <div x-show="warning" x-transition.scale.95
         class="w-full max-w-sm space-y-6 rounded-3xl border border-border bg-card/95 p-6 text-center shadow-2xl backdrop-blur-xl sm:p-8">

        <!-- Countdown Ring -->
        <div class="relative w-24 h-24 mx-auto">
            <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="44" fill="none" stroke-width="8" class="stroke-border" stroke="currentColor"></circle>
                <circle cx="50" cy="50" r="44" fill="none" stroke-width="8" stroke-linecap="round"
                        class="text-primary transition-all duration-1000 ease-linear" stroke="currentColor"
                        stroke-dasharray="276.46"
                        :stroke-dashoffset="276.46 * (1 - remaining / warningSeconds)"></circle>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-semibold tracking-tight text-foreground tabular-nums" x-text="remaining"></span>
                <span class="text-xs text-muted-foreground">sec</span>
            </div>
        </div>

        <!-- Text -->
        <div class="space-y-2">
            <h2 id="idle-title" class="text-xl font-semibold tracking-tight text-foreground">
                Are you still there?
            </h2>
            <p id="idle-desc" class="text-sm leading-relaxed text-muted-foreground">
                You've been inactive for a while. For security, you'll be logged out in
                <span class="font-semibold text-foreground tabular-nums" x-text="remaining"></span>
                <span x-text="remaining === 1 ? 'second' : 'seconds'"></span>.
            </p>
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-3">
            <button type="button" @click="stay()" x-ref="stayButton"
                    class="inline-flex w-full items-center justify-center rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90">
                Stay logged in
            </button>
            <button type="button" @click="logout(false)"
                    class="inline-flex w-full items-center justify-center rounded-3xl border border-border bg-card/95 px-6 py-3 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                Log out now
            </button>
        </div>
    </div>
</div>

<script>
    function idleTimeout({ timeout, warning, pingEvery, keepAliveUrl, logoutUrl, loginUrl }) {
        const KEY = 'idle:lastActivity';
        const LOGOUT_KEY = 'idle:loggedOut';
        const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
        const readShared = () => {
            try { return parseInt(localStorage.getItem(KEY), 10) || 0; } catch (e) { return 0; }
        };
        const writeShared = (t) => {
            try { localStorage.setItem(KEY, String(t)); } catch (e) {}
        };

        return {
            warning: false,
            remaining: warning,
            warningSeconds: warning,
            lastActivity: Date.now(),
            lastPing: Date.now(),
            loggingOut: false,

            init() {
                writeShared(this.lastActivity);

                const onActivity = () => {
                    // Once the card is up, only the "Stay Logged In" button counts
                    if (!this.warning) this.touch();
                };
                ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'wheel']
                    .forEach(e => window.addEventListener(e, onActivity, { passive: true }));

                // Activity or a "stay" in another tab
                window.addEventListener('storage', (e) => {
                    if (e.key === KEY) this.sync();
                    // Another tab already logged out: follow it to the login page
                    if (e.key === LOGOUT_KEY && !this.loggingOut) {
                        this.loggingOut = true;
                        window.location.href = loginUrl;
                    }
                });

                setInterval(() => this.tick(), 1000);
            },

            touch() {
                const now = Date.now();
                // Throttle writes: mousemove fires constantly
                if (now - this.lastActivity < 1000) return;
                this.lastActivity = now;
                writeShared(now);

                if (now - this.lastPing >= pingEvery * 1000) this.ping();
            },

            sync() {
                this.lastActivity = Math.max(this.lastActivity, readShared());
                this.tick();
            },

            tick() {
                if (this.loggingOut) return;

                // Another tab may have seen more recent activity
                this.lastActivity = Math.max(this.lastActivity, readShared());

                const idleFor = (Date.now() - this.lastActivity) / 1000;
                const left = Math.ceil(timeout - idleFor);

                if (left <= 0) return this.logout(true);

                if (left <= warning) {
                    this.remaining = left;
                    if (!this.warning) {
                        this.warning = true;
                        this.$nextTick(() => this.$refs.stayButton?.focus());
                    }
                } else {
                    this.warning = false;
                    this.remaining = warning;
                }
            },

            async ping() {
                this.lastPing = Date.now();
                try {
                    const res = await fetch(keepAliveUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf(),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    // Session already ended on the server (idle, expired or CSRF mismatch)
                    if (res.status === 401 || res.status === 419) {
                        this.loggingOut = true;
                        window.location.href = loginUrl;
                    }
                } catch (e) { /* offline: the next ping will retry */ }
            },

            stay() {
                this.warning = false;
                this.remaining = warning;
                this.lastActivity = Date.now();
                writeShared(this.lastActivity);
                this.ping();
            },

            async logout(idle) {
                if (this.loggingOut) return;
                this.loggingOut = true;
                this.warning = false;

                // fetch + manual redirect (instead of a form post) so several tabs
                // timing out together don't hit a "419 Page Expired" error, and the
                // "logged out due to inactivity" flash is kept for the login page.
                try {
                    await fetch(logoutUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                        body: new URLSearchParams({ reason: idle ? 'idle' : '' }),
                        credentials: 'same-origin',
                        redirect: 'manual',
                    });
                } catch (e) { /* the server-side idle check will still end the session */ }

                try { localStorage.setItem(LOGOUT_KEY, String(Date.now())); } catch (e) {}
                window.location.href = loginUrl;
            },
        };
    }
</script>
