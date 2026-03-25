<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="flex min-h-screen flex-col bg-gray-100">
            <main class="flex flex-1 flex-col items-center justify-center px-4 py-6 sm:px-6 sm:py-10">
                <div>
                    <a href="/">
                        <x-application-logo class="h-20 w-20 fill-current text-gray-500" />
                    </a>
                </div>

                <div class="mt-6 w-full sm:max-w-md bg-white px-6 py-4 shadow-md overflow-hidden sm:rounded-lg">
                    {{ $slot }}
                </div>
            </main>

            @include('layouts.partials.footer', ['guest' => true])
        </div>
    </body>
</html>
