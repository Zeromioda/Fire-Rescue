<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md space-y-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl sm:p-8">

            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-foreground">{{ __('Confirm Password') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">BFAD Station 178 · Camarin</p>
                </div>
                <button type="button" data-theme-toggle class="rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt"><span class="dark:hidden">Dark</span><span class="hidden dark:inline">Light</span></button>
            </div>

            <p class="text-sm text-muted-foreground">
                {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
            </p>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf

                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password"
                                  type="password"
                                  name="password"
                                  required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <x-primary-button class="w-full">
                    {{ __('Confirm') }}
                </x-primary-button>
            </form>
        </div>
    </div>
</x-guest-layout>
