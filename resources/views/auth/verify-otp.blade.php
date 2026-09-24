<x-guest-layout>
    <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 bg-background transition-colors duration-200">
        <div class="w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl p-6 sm:p-8 space-y-6 relative overflow-hidden">
            
            <!-- Background Accent Glow -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-primary/10 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center space-y-2">
                <div class="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-2xl shadow-sm mx-auto">
                    🔐
                </div>
                <h2 class="font-display font-black text-xl text-foreground tracking-tight">
                    Enter Verification Code
                </h2>
                <p class="text-xs text-muted">
                    We've sent a 6-digit code to your email address. Please enter it below to access your terminal session.
                </p>
            </div>

            <!-- Session Status / Errors -->
            <x-auth-session-status class="text-xs font-bold text-emerald-600 bg-emerald-500/10 p-3 rounded-xl border border-emerald-500/20" :status="session('success')" />

            <!-- Verification Form -->
            <form method="POST" action="{{ route('login.otp.verify.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="otp" class="block text-[10px] font-display font-black uppercase text-muted tracking-wider mb-1.5 text-center">
                        6-Digit OTP Code
                    </label>
                    <input id="otp" type="text" name="otp" required autofocus maxlength="6"
                           placeholder="123456"
                           style="color: inherit;"
                           class="w-full bg-slate-100 dark:bg-zinc-800 text-slate-900 dark:text-white border border-slate-300 dark:border-zinc-600 rounded-xl p-3.5 text-center text-lg font-mono font-bold tracking-widest focus:ring-2 focus:ring-primary focus:border-primary transition shadow-sm placeholder:text-slate-400 dark:placeholder:text-zinc-400" />
                    <x-input-error :messages="$errors->get('otp')" class="mt-1.5 text-xs text-rose-500 text-center" />
                </div>

                <button type="submit" class="w-full py-3.5 bg-primary hover:opacity-90 text-primary-foreground font-display font-black text-xs rounded-xl uppercase tracking-wider transition-all shadow-md cursor-pointer flex items-center justify-center gap-2 mt-2">
                    <span>Verify & Access Terminal</span>
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