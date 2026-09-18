<x-admin-layout>
    <div class="mb-[22px]">
        <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Klant toevoegen</h1>
    </div>

    <div class="max-w-2xl rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <form method="POST" action="{{ route('admin.clients.store') }}" class="px-[22px] py-5">
            @csrf

            @include('admin.clients._form')

            <div class="flex gap-[10px] border-t border-ink-100 pt-[18px] dark:border-dark-border">
                <x-primary-button>Opslaan</x-primary-button>
                <a href="{{ route('admin.clients.index') }}" class="inline-flex items-center justify-center rounded-[3px] border border-ink-300 bg-white px-[18px] py-[9px] text-sm font-semibold text-brand-800 hover:bg-ink-50 dark:border-dark-borderfield dark:bg-transparent dark:text-dark-text1 dark:hover:bg-dark-surface2">Annuleren</a>
            </div>
        </form>
    </div>
</x-admin-layout>
