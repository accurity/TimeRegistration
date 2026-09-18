<x-guest-layout>
    <h3 class="mb-1.5 font-display text-xl font-bold leading-7 text-ink-900 dark:text-dark-text1">Inloggen</h3>
    <p class="mb-6 text-[13px] leading-5 text-ink-500 dark:text-dark-text2">Urenregistratie en facturatie</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <x-input-label for="email" value="E-mailadres" />
            <x-text-input id="email" class="block w-full" type="email" name="email" placeholder="naam@bedrijf.nl" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-[18px]">
            <x-input-label for="password" value="Wachtwoord" />
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mb-[22px] flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-[13px] text-ink-700 dark:text-dark-text1">
                <input id="remember_me" type="checkbox" name="remember" class="h-3.5 w-3.5 rounded-[2px] border-ink-300 text-brand-600 focus:ring-brand-400 dark:border-dark-borderfield dark:bg-dark-bg">
                Ingelogd blijven
            </label>

            @if (Route::has('password.request'))
                <a class="text-[13px] text-brand-600 hover:text-brand-800 dark:text-brand-darkhover" href="{{ route('password.request') }}">
                    Wachtwoord vergeten?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full">
            Inloggen
        </x-primary-button>
    </form>
</x-guest-layout>
