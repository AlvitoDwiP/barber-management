<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-body font-sans antialiased">
        <div class="min-h-screen" x-data="{ sidebarOpen: false, userMenuOpen: false }" @keydown.escape.window="sidebarOpen = false; userMenuOpen = false">
            @include('layouts.partials.sidebar')

            <div class="lg:pl-72">
                @include('layouts.partials.topbar')

                <main class="px-4 pb-8 pt-6 sm:px-6 lg:px-8 lg:pt-8">
                    @isset($header)
                        <div class="mb-6">{{ $header }}</div>
                    @endisset

                    <x-flash-message />
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
