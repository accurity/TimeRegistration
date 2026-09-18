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
        <div class="min-h-screen bg-white dark:bg-dark-bg">
            <div class="flex h-[58px] items-center justify-between border-b border-ink-300 px-6 dark:border-dark-border">
                <div class="mx-auto flex w-full max-w-[920px] items-center justify-between">
                    <a href="{{ route('portal.dashboard') }}">
                        <img src="{{ asset('images/logo-accurity.png') }}" alt="Accurity" class="block h-[22px] w-auto">
                    </a>

                    <div class="flex items-center gap-5 text-sm text-ink-700 dark:text-dark-text2">
                        <span
                            @class([
                                'font-semibold text-brand-800 dark:text-brand-darkhover' => request()->routeIs('portal.dashboard'),
                            ])
                        >Uren</span>
                        <span>Mijn facturen</span>
                        <x-theme-toggle />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-[13px] text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Uitloggen</button>
                        </form>
                    </div>
                </div>
            </div>

            <main class="mx-auto w-full max-w-[920px] px-6 py-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
