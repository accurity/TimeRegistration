<x-admin-layout>
    <div class="mb-[22px] flex items-end justify-between">
        <div>
            <p class="mb-1 text-[13px] text-ink-500 dark:text-dark-text2">
                <a href="{{ route('admin.clients.edit', $client) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">{{ $client->name }}</a>
            </p>
            <h1 class="font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Contactpersonen</h1>
        </div>
        <a href="{{ route('admin.clients.users.create', $client) }}" class="inline-flex items-center justify-center rounded-[3px] border border-brand-600 bg-brand-600 px-[18px] py-[9px] text-sm font-semibold text-white hover:border-brand-800 hover:bg-brand-800">
            Contactpersoon toevoegen
        </a>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="overflow-hidden rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <div class="grid grid-cols-[1.4fr_1.4fr_140px] gap-3 border-b border-ink-200 bg-ink-50 px-[22px] py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Naam</div>
            <div>E-mail</div>
            <div></div>
        </div>

        @forelse ($users as $user)
            <div @class([
                'grid grid-cols-[1.4fr_1.4fr_140px] items-center gap-3 border-b border-ink-100 px-[22px] py-3 text-sm dark:border-dark-border',
                'bg-ink-50 dark:bg-dark-surface2' => $loop->even,
                'last:border-b-0' => true,
            ])>
                <div class="text-ink-900 dark:text-dark-text1">{{ $user->name }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $user->email }}</div>
                <div class="text-right">
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Deze contactpersoon verwijderen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-status-red-fg hover:underline dark:text-status-red-fg-dark">Verwijderen</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="px-[22px] py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                Nog geen contactpersonen toegevoegd.
            </div>
        @endforelse
    </div>
</x-admin-layout>
