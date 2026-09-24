<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-extrabold text-xl text-foreground flex items-center gap-2">
            <span>👤</span> {{ __('Account Settings & Security') }}
        </h2>
    </x-slot>

    <!-- Main Content Container with Sidebar Offset -->
    <div class="p-6 md:p-8 space-y-6 max-w-5xl mx-auto">

        <!-- Profile Information Card -->
        <div class="bg-card border border-border rounded-xl p-6 shadow-sm">
            <div class="mb-6 pb-4 border-b border-border">
                <h3 class="text-base font-display font-bold text-foreground flex items-center gap-2">
                    <span>📋</span> Profile Details
                </h3>
                <p class="text-xs text-muted mt-1">Update your display name and station contact email address.</p>
            </div>
            @include('profile.partials.update-profile-information-form')
        </div>

        <!-- Password Security Card -->
        <div class="bg-card border border-border rounded-xl p-6 shadow-sm">
            <div class="mb-6 pb-4 border-b border-border">
                <h3 class="text-base font-display font-bold text-foreground flex items-center gap-2">
                    <span>🔒</span> Password & Security
                </h3>
                <p class="text-xs text-muted mt-1">Ensure your account is using a secure password to protect dispatch operations.</p>
            </div>
            @include('profile.partials.update-password-form')
        </div>

    </div>
</x-app-layout>