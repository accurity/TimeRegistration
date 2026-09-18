<div class="flex h-[58px] items-center justify-between border-b border-ink-200 bg-white px-6 dark:border-dark-border dark:bg-dark-surface1">
    <a href="{{ route('dashboard') }}">
        <img src="{{ asset('images/logo-accurity.png') }}" alt="Accurity" class="block h-[22px] w-auto">
    </a>

    <div class="flex items-center gap-4 text-[13px] text-ink-700 dark:text-dark-text2">
        <x-theme-toggle />
        <a href="{{ route('dashboard') }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Terug naar dashboard</a>
        <span>{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Uitloggen</button>
        </form>
    </div>
</div>
