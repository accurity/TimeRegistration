@php
    $periodStart = \Illuminate\Support\Carbon::createFromDate($year, $month, 1);
    $periodLabel = $periodStart->translatedFormat('F Y');
    $defaultDate = $periodStart->isCurrentMonth() ? now()->toDateString() : $periodStart->toDateString();
@endphp
<x-admin-layout>
    <div class="mb-[22px] flex items-end justify-between">
        <div>
            <p class="mb-1 text-[13px] text-ink-500 dark:text-dark-text2">
                <a href="{{ route('admin.projects.index') }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">{{ $project->client->name }} — {{ $project->name }}</a>
            </p>
            <h1 class="font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Uren registreren</h1>
        </div>
        <form method="GET" action="{{ route('admin.projects.time-entries.index', $project) }}">
            <x-select-input name="period" class="w-44" onchange="this.form.submit()">
                @foreach ($monthOptions as $option)
                    <option value="{{ $option['year'] }}-{{ $option['month'] }}" @selected($option['year'] === $year && $option['month'] === $month)>{{ ucfirst($option['label']) }}</option>
                @endforeach
            </x-select-input>
        </form>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-status-red-fg dark:text-status-red-fg-dark">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-[2.4fr_1fr] items-start gap-4">
        <div class="overflow-hidden rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
            <form method="POST" action="{{ route('admin.projects.time-entries.store', $project) }}" class="border-b border-ink-200 bg-brand-50 px-5 py-4 dark:border-dark-border dark:bg-dark-surface2">
                @csrf
                <div class="grid grid-cols-[130px_100px_1fr_120px] items-end gap-3">
                    <div>
                        <x-input-label for="date" value="Datum" />
                        <x-text-input id="date" name="date" type="date" class="block w-full" value="{{ old('date', $defaultDate) }}" required />
                    </div>
                    <div>
                        <x-input-label for="hours" value="Uren" />
                        <x-text-input id="hours" name="hours" type="number" step="0.25" min="0.01" max="24" class="block w-full" value="{{ old('hours') }}" required />
                    </div>
                    <div>
                        <x-input-label for="description" value="Omschrijving" />
                        <x-text-input id="description" name="description" type="text" class="block w-full" value="{{ old('description') }}" placeholder="Waaraan is gewerkt?" required />
                    </div>
                    <x-primary-button class="justify-center">Toevoegen</x-primary-button>
                </div>
                <x-input-error :messages="$errors->get('date')" class="mt-2" />
                <x-input-error :messages="$errors->get('hours')" class="mt-2" />
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                <p class="mt-2 text-[12px] text-ink-500 dark:text-dark-text2">Decimaal invoeren, bijvoorbeeld 1,5 voor anderhalf uur.</p>
            </form>

            <div class="grid grid-cols-[130px_100px_1fr_130px] gap-3 border-b border-ink-200 bg-ink-50 px-5 py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
                <div>Datum</div>
                <div class="text-right">Uren</div>
                <div>Omschrijving</div>
                <div></div>
            </div>

            @forelse ($weeks as $week => $entries)
                <div class="border-b border-ink-100 bg-ink-50/60 px-5 py-[7px] text-[11px] font-semibold uppercase tracking-[.08em] text-ink-400 dark:border-dark-border dark:bg-dark-surface2/60 dark:text-dark-text3">Week {{ $week }}</div>
                @foreach ($entries as $entry)
                    <div class="grid grid-cols-[130px_100px_1fr_130px] items-center gap-3 border-b border-ink-100 px-5 py-[11px] text-sm dark:border-dark-border" style="font-variant-numeric: tabular-nums">
                        <div class="text-ink-900 dark:text-dark-text1">{{ $entry->date->format('d-m-Y') }}</div>
                        <div class="text-right text-ink-900 dark:text-dark-text1">{{ number_format($entry->hours, 2, ',', '.') }}</div>
                        <div class="text-ink-700 dark:text-dark-text2">{{ $entry->description }}</div>
                        <div class="text-right">
                            <a href="{{ route('admin.projects.time-entries.edit', [$project, $entry]) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Bewerken</a>
                            ·
                            <button
                                type="submit"
                                form="delete-entry-{{ $entry->id }}"
                                class="text-status-red-fg hover:underline dark:text-status-red-fg-dark"
                                onclick="return confirm('Deze uren verwijderen?')"
                            >Verwijderen</button>
                            <form id="delete-entry-{{ $entry->id }}" method="POST" action="{{ route('admin.projects.time-entries.destroy', [$project, $entry]) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    </div>
                @endforeach
            @empty
                <div class="px-5 py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                    Nog geen uren geregistreerd voor {{ $periodLabel }}.
                </div>
            @endforelse

            <div class="grid grid-cols-[130px_100px_1fr_130px] gap-3 bg-brand-100 px-5 py-[13px] text-[15px] font-semibold dark:bg-brand-darktint dark:text-dark-text1" style="font-variant-numeric: tabular-nums">
                <div>Totaal {{ $periodLabel }}</div>
                <div class="text-right">{{ number_format($totalHours, 2, ',', '.') }}</div>
                <div></div>
                <div class="text-right">&euro; {{ number_format($totalAmount, 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="grid gap-4">
            <div class="rounded-md border border-ink-200 bg-white p-5 dark:border-dark-border dark:bg-dark-surface1">
                <div class="mb-[14px] flex items-center justify-between">
                    <h3 class="font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">{{ $periodLabel }}</h3>
                    <x-approval-status-badge :status="$approval->status ?? 'draft'" />
                </div>
                <div class="grid gap-[9px] text-sm" style="font-variant-numeric: tabular-nums">
                    <div class="flex justify-between"><span class="text-ink-700 dark:text-dark-text2">Geregistreerd</span><span class="text-ink-900 dark:text-dark-text1">{{ number_format($totalHours, 2, ',', '.') }} uur</span></div>
                    <div class="flex justify-between"><span class="text-ink-700 dark:text-dark-text2">Huidig uurtarief</span><span class="text-ink-900 dark:text-dark-text1">&euro; {{ number_format($project->rate, 2, ',', '.') }}</span></div>
                    <div class="flex justify-between border-t border-ink-100 pt-[9px] font-semibold text-ink-900 dark:border-dark-border dark:text-dark-text1"><span>Excl. btw</span><span>&euro; {{ number_format($totalAmount, 2, ',', '.') }}</span></div>
                </div>
            </div>

            <div class="rounded-md border border-ink-200 bg-white p-5 dark:border-dark-border dark:bg-dark-surface1">
                <h3 class="mb-[10px] font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Volgende stap</h3>

                @if ($approval && $approval->isRejected() && $approval->rejection_reason)
                    <p class="mb-[14px] text-[13px] leading-5 text-status-red-fg dark:text-status-red-fg-dark">Afgekeurd: {{ $approval->rejection_reason }}</p>
                @endif

                @if (! $approval || $approval->isRejected())
                    <p class="mb-[14px] text-[13px] leading-5 text-ink-700 dark:text-dark-text2">Bij versturen krijgt de klant een e-mail met een link naar het portaal.</p>
                    <form method="POST" action="{{ route('admin.projects.monthly-approval.submit', $project) }}">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <x-primary-button class="w-full justify-center">
                            {{ $approval && $approval->isRejected() ? 'Opnieuw indienen ter goedkeuring' : 'Ter goedkeuring versturen' }}
                        </x-primary-button>
                    </form>
                @elseif ($approval->isPending())
                    <p class="text-[13px] leading-5 text-ink-700 dark:text-dark-text2">Ingediend op {{ $approval->submitted_at?->translatedFormat('j F Y, H:i') }}. In afwachting van de klant.</p>
                @else
                    <p class="mb-[14px] text-[13px] leading-5 text-ink-700 dark:text-dark-text2">
                        Goedgekeurd op {{ $approval->approved_at?->translatedFormat('j F Y') }}
                        @if ($approval->approvedBy)
                            door {{ $approval->approvedBy->name }}
                        @endif
                    </p>
                    <a href="{{ route('admin.invoices.create', ['project_id' => $project->id, 'year' => $year, 'month' => $month]) }}" class="inline-flex w-full items-center justify-center rounded-[3px] border border-brand-600 bg-brand-600 px-[18px] py-[9px] text-sm font-semibold text-white hover:border-brand-800 hover:bg-brand-800">Factuur opstellen</a>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
