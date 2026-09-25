<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md space-y-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl sm:p-8">

            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-foreground">Enter Verification Code</h2>
                    <p class="mt-1 text-sm text-muted-foreground">BFAD Station 178 · Camarin</p>
                </div>
                <button type="button" data-theme-toggle class="rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt"><span class="dark:hidden">Dark</span><span class="hidden dark:inline">Light</span></button>
            </div>

            <p class="text-sm text-muted-foreground">
                We've sent a 6-digit code to your email address. Please enter it below to access your terminal session.
            </p>

            <!-- Session Status -->
            <x-auth-session-status :status="session('success')" />

            <!-- Verification Form -->
            <form method="POST" action="{{ route('login.otp.verify.store') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="otp" value="6-Digit OTP Code" />
                    <x-text-input id="otp" type="text" name="otp" required autofocus maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                                  placeholder="123456"
                                  class="text-center text-lg tabular-nums tracking-widest" />
                    <x-input-error :messages="$errors->get('otp')" />
                </div>

                <x-primary-button class="w-full">
                    Verify &amp; Access Terminal
                </x-primary-button>
            </form>

            <!-- Expiry countdown & Resend -->
            <x-otp-resend :action="route('login.otp.resend')" :expires-at="$expiresAt" :resend-in="$resendIn" :resends-left="$resendsLeft" />

            <!-- Back to Login -->
            <div class="border-t border-border pt-4 text-center text-xs text-muted-foreground">
                <a href="{{ route('login') }}" class="text-sm font-medium text-primary hover:underline">Back to login</a>
            </div>
        </div>
    </div>
</x-guest-layout>
