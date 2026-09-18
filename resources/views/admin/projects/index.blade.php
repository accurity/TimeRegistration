<x-admin-layout>
    <div class="mb-[22px] flex items-end justify-between">
        <div>
            <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Projecten</h1>
            <p class="text-[13px] text-ink-500 dark:text-dark-text2">{{ $projects->count() }} {{ $projects->count() === 1 ? 'project' : 'projecten' }}</p>
        </div>
        <a href="{{ route('admin.projects.create') }}" class="inline-flex items-center justify-center rounded-[3px] border border-brand-600 bg-brand-600 px-[18px] py-[9px] text-sm font-semibold text-white hover:border-brand-800 hover:bg-brand-800">
            Project toevoegen
        </a>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-status-red-fg dark:text-status-red-fg-dark">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.projects.index') }}" class="mb-[14px] flex items-end gap-[14px]">
        <div>
            <x-input-label for="client_id" value="Klant" />
            <x-select-input id="client_id" name="client_id" class="w-52" onchange="this.form.submit()">
                <option value="">Alle klanten</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected($clientId == $client->id)>{{ $client->name }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <x-select-input id="status" name="status" class="w-40" onchange="this.form.submit()">
                <option value="active" @selected($status === 'active')>Actief</option>
                <option value="archived" @selected($status === 'archived')>Gearchiveerd</option>
                <option value="all" @selected($status === 'all')>Alles</option>
            </x-select-input>
        </div>
        <noscript>
            <button type="submit" class="inline-flex items-center justify-center rounded-[3px] border border-ink-300 bg-white px-[18px] py-[9px] text-sm font-semibold text-brand-800 hover:bg-ink-50 dark:border-dark-borderfield dark:bg-transparent dark:text-dark-text1 dark:hover:bg-dark-surface2">Filteren</button>
        </noscript>
    </form>

    <div class="overflow-hidden rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <div class="grid grid-cols-[1.6fr_1.2fr_110px_120px_140px] gap-3 border-b border-ink-200 bg-ink-50 px-[22px] py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Project</div>
            <div>Klant</div>
            <div class="text-right">Tarief</div>
            <div>Status</div>
            <div></div>
        </div>

        @forelse ($projects as $project)
            <div @class([
                'grid grid-cols-[1.6fr_1.2fr_110px_120px_140px] items-center gap-3 border-b border-ink-100 px-[22px] py-3 text-sm dark:border-dark-border',
                'bg-ink-50 dark:bg-dark-surface2' => $loop->even,
                'last:border-b-0' => true,
            ])>
                <div class="text-ink-900 dark:text-dark-text1">{{ $project->name }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $project->client->name }}</div>
                <div class="text-right text-ink-700 dark:text-dark-text2" style="font-variant-numeric: tabular-nums">&euro; {{ number_format($project->rate, 2, ',', '.') }}</div>
                <div>
                    @if ($project->isActive())
                        <span class="inline-block rounded-[3px] border border-status-green-border bg-status-green-bg px-[10px] py-1 text-[12px] font-semibold text-status-green-fg dark:border-status-green-border-dark dark:bg-status-green-bg-dark dark:text-status-green-fg-dark">Actief</span>
                    @else
                        <span class="inline-block rounded-[3px] border border-status-gray-border bg-status-gray-bg px-[10px] py-1 text-[12px] font-semibold text-status-gray-fg dark:border-status-gray-border-dark dark:bg-status-gray-bg-dark dark:text-status-gray-fg-dark">Gearchiveerd</span>
                    @endif
                </div>
                <div class="text-right">
                    <a href="{{ route('admin.projects.edit', $project) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Bewerken</a>
                </div>
            </div>
        @empty
            <div class="px-[22px] py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                Geen projecten gevonden.
            </div>
        @endforelse
    </div>
</x-admin-layout>
