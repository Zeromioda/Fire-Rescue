<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md space-y-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl sm:p-8">

            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-foreground">{{ __('Verify Your Email') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">BFAD Station 178 · Camarin</p>
                </div>
                <button type="button" data-theme-toggle class="rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt"><span class="dark:hidden">Dark</span><span class="hidden dark:inline">Light</span></button>
            </div>

            <p class="text-sm text-muted-foreground">
                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
            </p>

            @if (session('status') == 'verification-link-sent')
                <x-auth-session-status :status="__('A new verification link has been sent to the email address you provided during registration.')" />
            @endif

            <div class="flex items-center justify-between gap-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <x-primary-button>
                        {{ __('Resend Verification Email') }}
                    </x-primary-button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="text-sm font-medium text-primary hover:underline">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
