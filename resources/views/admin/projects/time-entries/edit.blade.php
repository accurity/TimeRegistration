<x-admin-layout>
    <div class="mb-[22px]">
        <p class="mb-1 text-[13px] text-ink-500 dark:text-dark-text2">
            <a href="{{ route('admin.projects.time-entries.index', [$project, 'year' => $timeEntry->date->year, 'month' => $timeEntry->date->month]) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">{{ $project->client->name }} — {{ $project->name }}</a>
        </p>
        <h1 class="font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Uren bewerken</h1>
    </div>

    <div class="max-w-2xl rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <form method="POST" action="{{ route('admin.projects.time-entries.update', [$project, $timeEntry]) }}" class="px-[22px] py-5">
            @csrf
            @method('PUT')

            <div class="mb-[14px] grid grid-cols-2 gap-[14px]">
                <div>
                    <x-input-label for="date" value="Datum" />
                    <x-text-input id="date" name="date" type="date" class="block w-full" :value="old('date', $timeEntry->date->toDateString())" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="hours" value="Uren" />
                    <x-text-input id="hours" name="hours" type="number" step="0.25" min="0" max="24" class="block w-full" :value="old('hours', $timeEntry->hours)" required />
                    <x-input-error :messages="$errors->get('hours')" class="mt-2" />
                </div>
            </div>

            <div class="mb-[14px]">
                <x-input-label for="description" value="Omschrijving" />
                <x-text-input id="description" name="description" type="text" class="block w-full" :value="old('description', $timeEntry->description)" required />
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="mb-[22px]">
                <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2">Uurtarief</div>
                <div class="rounded-[3px] border border-ink-200 bg-ink-50 px-3 py-[9px] text-sm text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">&euro; {{ number_format($timeEntry->rate, 2, ',', '.') }}</div>
                <p class="mt-2 text-[13px] text-ink-500 dark:text-dark-text2">Vastgezet op het moment van invoeren; wijzigt niet mee met het huidige projecttarief.</p>
            </div>

            <div class="flex gap-[10px] border-t border-ink-100 pt-[18px] dark:border-dark-border">
                <x-primary-button>Opslaan</x-primary-button>
                <a href="{{ route('admin.projects.time-entries.index', [$project, 'year' => $timeEntry->date->year, 'month' => $timeEntry->date->month]) }}" class="inline-flex items-center justify-center rounded-[3px] border border-ink-300 bg-white px-[18px] py-[9px] text-sm font-semibold text-brand-800 hover:bg-ink-50 dark:border-dark-borderfield dark:bg-transparent dark:text-dark-text1 dark:hover:bg-dark-surface2">Annuleren</a>
                <button
                    type="submit"
                    form="delete-entry-form"
                    class="ml-auto inline-flex items-center justify-center rounded-[3px] border border-status-red-border bg-white px-[18px] py-[9px] text-sm font-semibold text-status-red-fg hover:bg-status-red-bg dark:border-status-red-border-dark dark:bg-transparent dark:text-status-red-fg-dark dark:hover:bg-status-red-bg-dark"
                    onclick="return confirm('Deze uren verwijderen?')"
                >
                    Verwijderen
                </button>
            </div>
        </form>

        <form id="delete-entry-form" method="POST" action="{{ route('admin.projects.time-entries.destroy', [$project, $timeEntry]) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</x-admin-layout>
