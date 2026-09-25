<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold tracking-tight text-foreground">
            {{ __('Account Settings & Security') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 p-6 md:p-8">

        <!-- Profile Information -->
        <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
            <div class="mb-6 border-b border-border pb-4">
                <h3 class="text-lg font-semibold tracking-tight text-foreground">Profile Details</h3>
                <p class="mt-1 text-sm text-muted-foreground">Update your display name and station contact email address.</p>
            </div>
            @include('profile.partials.update-profile-information-form')
        </div>

        <!-- Password & Security -->
        <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
            <div class="mb-6 border-b border-border pb-4">
                <h3 class="text-lg font-semibold tracking-tight text-foreground">Password &amp; Security</h3>
                <p class="mt-1 text-sm text-muted-foreground">Ensure your account is using a secure password to protect dispatch operations.</p>
            </div>
            @include('profile.partials.update-password-form')
        </div>

    </div>
</x-app-layout>
