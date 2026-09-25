<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md space-y-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl sm:p-8">

            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-foreground">Forgot Your Password?</h2>
                    <p class="mt-1 text-sm text-muted-foreground">BFAD Station 178 · Camarin</p>
                </div>
                <button type="button" data-theme-toggle class="rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt"><span class="dark:hidden">Dark</span><span class="hidden dark:inline">Light</span></button>
            </div>

            <p class="text-sm text-muted-foreground">
                Enter your registered personnel email below. We will send a <span class="font-medium text-foreground">6-digit OTP code</span> to reset your dispatch credentials.
            </p>

            <!-- Session Status -->
            <x-auth-session-status :status="session('status')" />

            <form method="POST" action="{{ route('password.otp.send') }}" class="space-y-5">
                @csrf

                <!-- Registered Email -->
                <div>
                    <x-input-label for="email" value="Registered Personnel Email" />
                    <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="officer@bfad178.gov.ph" />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <x-primary-button class="w-full">
                    Send Verification OTP
                </x-primary-button>
            </form>

            <div class="border-t border-border pt-4 text-center text-xs text-muted-foreground">
                <a href="{{ route('login') }}" class="text-sm font-medium text-primary hover:underline">Back to login</a>
            </div>
        </div>
    </div>
</x-guest-layout>
