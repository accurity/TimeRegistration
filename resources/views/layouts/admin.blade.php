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
        <div class="min-h-screen bg-ink-50 dark:bg-dark-bg">
            <div class="flex h-[58px] items-center justify-between border-b border-ink-200 bg-white px-6 dark:border-dark-border dark:bg-dark-surface1">
                <div class="mx-auto flex w-full max-w-[1180px] items-center justify-between">
                    <div class="flex items-center gap-8">
                        <a href="{{ route('admin.dashboard') }}">
                            <img src="{{ asset('images/logo-accurity.png') }}" alt="Accurity" class="block h-[22px] w-auto">
                        </a>

                        <nav class="flex h-[58px] items-center gap-6 text-sm">
                            @foreach ([
                                ['admin.dashboard', 'admin.dashboard', 'Dashboard'],
                                ['admin.clients.index', 'admin.clients.*', 'Klanten'],
                                ['admin.projects.index', 'admin.projects.*', 'Projecten'],
                                [null, null, 'Uren'],
                                ['admin.invoices.index', 'admin.invoices.*', 'Facturen'],
                            ] as [$routeName, $activePattern, $label])
                                @php $isActive = $activePattern && request()->routeIs($activePattern); @endphp
                                @if ($routeName)
                                    <a
                                        href="{{ route($routeName) }}"
                                        @class([
                                            'flex h-[58px] items-center',
                                            'border-b-2 border-brand-600 font-semibold text-brand-800 dark:border-brand-darkfg dark:text-brand-darkhover' => $isActive,
                                            'text-ink-700 dark:text-dark-text2' => ! $isActive,
                                        ])
                                    >{{ $label }}</a>
                                @else
                                    <span class="flex h-[58px] items-center text-ink-700 dark:text-dark-text2">{{ $label }}</span>
                                @endif
                            @endforeach
                        </nav>
                    </div>

                    <div class="flex items-center gap-4 text-[13px] text-ink-700 dark:text-dark-text2">
                        <a href="{{ route('admin.settings.edit') }}" @class(['text-brand-800 font-semibold dark:text-brand-darkhover' => request()->routeIs('admin.settings.*'), 'hover:text-brand-800 dark:hover:text-brand-darkhover' => ! request()->routeIs('admin.settings.*')])>Instellingen</a>
                        <x-theme-toggle />
                        <span>{{ auth()->user()->name }} — Accurity</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Uitloggen</button>
                        </form>
                    </div>
                </div>
            </div>

            <main class="mx-auto w-full max-w-[1180px] px-6 py-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
