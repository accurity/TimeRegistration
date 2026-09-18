<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        @include('partials.theme-init')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="relative flex min-h-screen items-center justify-center bg-ink-50 dark:bg-dark-bg">
            <div class="absolute inset-x-0 top-0 h-1.5 bg-brand-600"></div>

            <div class="absolute right-6 top-6">
                <x-theme-toggle />
            </div>

            <div class="w-[380px] rounded-md border border-ink-200 bg-white p-9 px-8 shadow-[0_4px_18px_rgba(15,26,33,0.07)] dark:border-dark-border dark:bg-dark-surface1 dark:shadow-none">
                <img src="{{ asset('images/logo-accurity.png') }}" alt="Accurity — online communicatie" class="mb-7 block h-[30px] w-auto">

                {{ $slot }}
            </div>
        </div>
    </body>
</html>
