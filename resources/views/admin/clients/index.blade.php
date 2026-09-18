<x-admin-layout>
    <div class="mb-[22px] flex items-end justify-between">
        <div>
            <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Klanten</h1>
            <p class="text-[13px] text-ink-500 dark:text-dark-text2">{{ $clients->count() }} {{ $clients->count() === 1 ? 'klant' : "klanten" }}</p>
        </div>
        <a href="{{ route('admin.clients.create') }}" class="inline-flex items-center justify-center rounded-[3px] border border-brand-600 bg-brand-600 px-[18px] py-[9px] text-sm font-semibold text-white hover:border-brand-800 hover:bg-brand-800">
            Klant toevoegen
        </a>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-status-red-fg dark:text-status-red-fg-dark">{{ session('error') }}</div>
    @endif

    <div class="overflow-hidden rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <div class="grid grid-cols-[1.6fr_140px_1fr_160px_140px] gap-3 border-b border-ink-200 bg-ink-50 px-[22px] py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Klant</div>
            <div>Afkorting</div>
            <div>Plaats</div>
            <div>KvK-nummer</div>
            <div></div>
        </div>

        @forelse ($clients as $client)
            <div @class([
                'grid grid-cols-[1.6fr_140px_1fr_160px_140px] items-center gap-3 border-b border-ink-100 px-[22px] py-3 text-sm dark:border-dark-border',
                'bg-ink-50 dark:bg-dark-surface2' => $loop->even,
                'last:border-b-0' => true,
            ])>
                <div class="text-ink-900 dark:text-dark-text1">{{ $client->name }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $client->invoice_abbreviation }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $client->city }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $client->kvk_number ?? '—' }}</div>
                <div class="text-right">
                    <a href="{{ route('admin.clients.edit', $client) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Bewerken</a>
                </div>
            </div>
        @empty
            <div class="px-[22px] py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                Nog geen klanten toegevoegd.
            </div>
        @endforelse
    </div>
</x-admin-layout>
