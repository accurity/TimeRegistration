<x-admin-layout>
    <div class="mb-[22px]">
        <p class="mb-1 text-[13px] text-ink-500 dark:text-dark-text2">
            <a href="{{ route('admin.clients.users.index', $client) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">{{ $client->name }} — Contactpersonen</a>
        </p>
        <h1 class="font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Contactpersoon toevoegen</h1>
    </div>

    <div class="max-w-2xl rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <form method="POST" action="{{ route('admin.clients.users.store', $client) }}" class="px-[22px] py-5">
            @csrf

            <div class="mb-[14px]">
                <x-input-label for="name" value="Naam" />
                <x-text-input id="name" name="name" type="text" class="block w-full" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mb-[22px]">
                <x-input-label for="email" value="E-mail" />
                <x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                <p class="mt-2 text-[13px] text-ink-500 dark:text-dark-text2">Er wordt automatisch een wachtwoord-instellink naar dit e-mailadres gestuurd.</p>
            </div>

            <div class="flex gap-[10px] border-t border-ink-100 pt-[18px] dark:border-dark-border">
                <x-primary-button>Toevoegen</x-primary-button>
                <a href="{{ route('admin.clients.users.index', $client) }}" class="inline-flex items-center justify-center rounded-[3px] border border-ink-300 bg-white px-[18px] py-[9px] text-sm font-semibold text-brand-800 hover:bg-ink-50 dark:border-dark-borderfield dark:bg-transparent dark:text-dark-text1 dark:hover:bg-dark-surface2">Annuleren</a>
            </div>
        </form>
    </div>
</x-admin-layout>
