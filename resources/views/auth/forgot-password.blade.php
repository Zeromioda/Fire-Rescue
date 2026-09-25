<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 bg-background">
        <div class="w-full max-w-md bg-card border border-border rounded-2xl p-8 shadow-2xl space-y-6">
            
            <!-- Header Badge & Text -->
            <div class="space-y-2 text-center">
                <div class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-widest mb-1 border border-primary/20">
                    Terminal Identity Recovery
                </div>
                <h2 class="font-display font-bold text-xl text-foreground tracking-wide">
                    Forgot Your Password?
                </h2>
                <p class="text-xs text-muted leading-relaxed">
                    Enter your registered personnel email below. We will send a <span class="text-foreground font-semibold">password reset link</span> to reset your dispatch credentials.
                </p>
            </div>

            <!-- Session Status / Feedback -->
            @if (session('status'))
                <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-3 text-xs text-emerald-400 font-medium text-center">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                <!-- Registered Email Field -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1.5">Registered Personnel Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="officer@bfad178.gov.ph"
                        class="w-full bg-card-alt border border-border rounded-xl px-4 py-2.5 text-sm text-foreground focus:ring-1 focus:ring-primary focus:border-primary">
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3 bg-primary text-primary-foreground font-display font-bold text-xs rounded-xl shadow-lg hover:opacity-90 transition uppercase tracking-wider mt-2">
                    Send Reset Link →
                </button>
            </form>

            <!-- Footer Meta & Return Link -->
            <div class="flex items-center justify-between pt-4 border-t border-border text-xs">
                <a href="{{ route('login') }}" class="text-muted hover:text-foreground font-semibold transition">
                    ← Return to Login
                </a>
                <span class="text-[10px] font-mono text-muted/60 tracking-wider"></span>
            </div>

        </div>
    </div>
</x-guest-layout>