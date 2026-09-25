<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Prevent Theme Flash -->
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-background text-foreground transition-colors duration-200">
        <div class="min-h-screen"
             x-data="{ mobileOpen: false, sidebarOpen: localStorage.getItem('sidebar') !== 'closed' }"
             x-init="$watch('sidebarOpen', value => localStorage.setItem('sidebar', value ? 'open' : 'closed'))">
            @include('layouts.navigation')

            <div class="transition-[padding] duration-300" :class="sidebarOpen ? 'lg:pl-72' : 'lg:pt-14'">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 lg:pt-8">
                        <div class="rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @auth
            <x-idle-timeout />
        @endauth
    </body>
</html>
