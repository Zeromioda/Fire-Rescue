<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md space-y-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl sm:p-8">

            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-foreground">Reset Your Password</h2>
                    <p class="mt-1 text-sm text-muted-foreground">BFAD Station 178 · Camarin</p>
                </div>
                <button type="button" data-theme-toggle class="rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt"><span class="dark:hidden">Dark</span><span class="hidden dark:inline">Light</span></button>
            </div>

            <p class="text-sm text-muted-foreground">
                Enter the 6-digit code we sent to your email, then choose a new password.
            </p>

            <!-- Session Status -->
            <x-auth-session-status :status="session('status')" />

            <form method="POST" action="{{ route('password.otp.reset') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <x-input-label for="email" value="Registered Personnel Email" />
                    <x-text-input id="email" type="email" name="email" value="{{ $email }}" required readonly
                                  class="cursor-not-allowed opacity-70" />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <!-- OTP -->
                <div>
                    <x-input-label for="otp" value="6-Digit OTP Code" />
                    <x-text-input id="otp" type="text" name="otp" required autofocus maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                                  placeholder="123456"
                                  class="text-center text-lg tabular-nums tracking-widest" />
                    <x-input-error :messages="$errors->get('otp')" />
                </div>

                <!-- New Password -->
                <div>
                    <x-input-label for="password" value="New Password" />
                    <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-input-label for="password_confirmation" value="Confirm New Password" />
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>

                <x-primary-button class="w-full">
                    Reset Password
                </x-primary-button>
            </form>

            <!-- Expiry countdown & Resend -->
            <x-otp-resend :action="route('password.otp.resend')" :expires-at="$expiresAt" :resend-in="$resendIn" :resends-left="$resendsLeft" />

            <!-- Back to Login -->
            <div class="border-t border-border pt-4 text-center text-xs text-muted-foreground">
                <a href="{{ route('login') }}" class="text-sm font-medium text-primary hover:underline">Back to login</a>
            </div>
        </div>
    </div>
</x-guest-layout>
