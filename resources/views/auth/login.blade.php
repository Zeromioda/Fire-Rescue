<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md space-y-6 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl sm:p-8">

            <!-- Card Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-foreground">Dispatch Terminal</h2>
                    <p class="mt-1 text-sm text-muted-foreground">BFAD Station 178 · Camarin</p>
                </div>
                <button type="button" data-theme-toggle class="rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt"><span class="dark:hidden">Dark</span><span class="hidden dark:inline">Light</span></button>
            </div>

            <!-- Session Status -->
            <x-auth-session-status :status="session('status')" />

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <!-- Email / Service ID -->
                <div>
                    <x-input-label for="email" value="Email / Service ID" />
                    <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus
                                  placeholder="responder@caloocan.gov.ph" />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-start justify-between gap-4">
                        <x-input-label for="password" value="Password" />
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary hover:underline">
                                Forgot password?
                            </a>
                        @endif
                    </div>
                    <x-text-input id="password" type="password" name="password" required autocomplete="current-password"
                                  placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <!-- Remember Device -->
                <div class="flex items-center">
                    <label for="remember_me" class="inline-flex cursor-pointer items-center">
                        <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                        <span class="ms-2 text-sm text-muted-foreground">Remember this terminal</span>
                    </label>
                </div>

                <!-- Submit -->
                <x-primary-button class="w-full">
                    Access Terminal
                </x-primary-button>
            </form>

            <!-- Card Footer -->
            <div class="border-t border-border pt-4 text-center text-xs text-muted-foreground">
                Zone 15, District III, Caloocan City · Fire &amp; Rescue System
            </div>
        </div>
    </div>
</x-guest-layout>
