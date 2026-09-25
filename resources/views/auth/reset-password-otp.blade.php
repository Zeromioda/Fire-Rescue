<x-guest-layout>
    <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 bg-background transition-colors duration-200">
        <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl p-6 sm:p-8 space-y-6 relative overflow-hidden">

            <!-- Background Accent Glow -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-primary/10 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center space-y-2">
                <div class="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-2xl shadow-sm mx-auto">
                    🔑
                </div>
                <h2 class="font-display font-black text-xl text-foreground tracking-tight">
                    Reset Your Password
                </h2>
                <p class="text-xs text-muted">
                    Enter the 6-digit code we sent to your email, then choose a new password.
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="text-xs font-bold text-emerald-600 bg-emerald-500/10 p-3 rounded-xl border border-emerald-500/20" :status="session('status')" />

            <form method="POST" action="{{ route('password.otp.reset') }}" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Registered Personnel Email</label>
                    <input id="email" type="email" name="email" value="{{ $email }}" required
                        class="w-full bg-card-alt border border-border rounded-xl px-4 py-2.5 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                <!-- OTP -->
                <div>
                    <label for="otp" class="block text-[10px] font-display font-black uppercase text-muted tracking-wider mb-1.5 text-center">
                        6-Digit OTP Code
                    </label>
                    <input id="otp" type="text" name="otp" required autofocus maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                           placeholder="123456"
                           style="color: inherit;"
                           class="w-full bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-white border border-slate-300 dark:border-zinc-600 rounded-xl p-3.5 text-center text-lg font-mono font-bold tracking-widest focus:ring-2 focus:ring-primary focus:border-primary transition shadow-sm placeholder:text-slate-400 dark:placeholder:text-zinc-400" />
                    <x-input-error :messages="$errors->get('otp')" class="mt-1.5 text-xs text-rose-500 text-center" />
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">New Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                        class="w-full bg-card-alt border border-border rounded-xl px-4 py-2.5 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Confirm New Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                        class="w-full bg-card-alt border border-border rounded-xl px-4 py-2.5 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                </div>

                <button type="submit" class="w-full py-3.5 bg-primary hover:opacity-90 text-primary-foreground font-display font-black text-xs rounded-xl uppercase tracking-wider transition-all shadow-md cursor-pointer flex items-center justify-center gap-2 mt-2">
                    <span>Reset Password</span>
                    <span>➔</span>
                </button>
            </form>

            <!-- Back to Login -->
            <div class="pt-4 border-t border-border text-center">
                <a href="{{ route('login') }}" class="text-[11px] font-bold text-muted hover:text-primary transition">
                    ← Back to Login
                </a>
            </div>

        </div>
    </div>
</x-guest-layout>
