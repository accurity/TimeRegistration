@php
    $cellClasses = 'flex min-h-[106px] flex-col gap-1.5 rounded border px-2.5 pb-2.5 pt-2';
    $filledCellClasses = 'border-status-blue-border bg-brand-50 dark:border-status-blue-border-dark dark:bg-brand-darktint';
    $errorCellClasses = 'border-status-red-border bg-status-red-bg dark:border-status-red-border-dark dark:bg-status-red-bg-dark';
    $focusCellClasses = 'focus-within:border-brand-600 focus-within:bg-white focus-within:ring-[3px] focus-within:ring-brand-400/20 dark:focus-within:border-brand-darkfg dark:focus-within:bg-dark-bg dark:focus-within:ring-brand-darkfg/25';
    $fieldClasses = 'w-full min-w-0 rounded-[3px] border px-2 py-[5px] text-ink-900 placeholder:text-ink-400 focus:border-brand-600 focus:outline-none focus:ring-[3px] focus:ring-brand-400/30 dark:text-dark-text1 dark:placeholder:text-dark-text3 dark:focus:border-brand-darkfg dark:focus:ring-brand-darkfg/30';
    $emptyFieldClasses = 'border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1';
    $filledFieldClasses = 'border-ink-300 bg-white dark:border-status-blue-border-dark dark:bg-dark-bg';
    $dayNumberClasses = 'font-display text-[15px] font-bold leading-5';
    $weekHoursMap = collect($calendarWeeks)->mapWithKeys(fn (array $week) => [$week['number'] => $week['hours']]);
@endphp

@if ($approval && $approval->isRejected() && $approval->rejection_reason)
    <div class="mb-4 rounded-[3px] border border-status-red-border bg-status-red-bg px-4 py-3 text-sm text-status-red-fg dark:border-status-red-border-dark dark:bg-status-red-bg-dark dark:text-status-red-fg-dark">Afgekeurd: {{ $approval->rejection_reason }}</div>
@endif

<div
    x-data="timeEntryCalendar(@js([
        'monthHours' => (float) $totalHours,
        'monthAmount' => (float) $totalAmount,
        'weekHours' => $weekHoursMap,
        'approvalStatus' => $approval->status ?? 'draft',
    ]))"
    class="overflow-hidden rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1"
>
    <div class="grid gap-2 px-5 pb-5 pt-[18px]" style="font-variant-numeric: tabular-nums">
        <div class="grid grid-cols-[70px_repeat(7,minmax(0,1fr))] gap-2 text-[11px] font-semibold uppercase tracking-[.1em]">
            <div></div>
            @foreach (['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'] as $index => $weekdayLabel)
                <div @class([
                    'pl-2.5',
                    'text-ink-500 dark:text-dark-text2' => $index < 5,
                    'text-ink-400 dark:text-dark-text3' => $index >= 5,
                ])>{{ $weekdayLabel }}</div>
            @endforeach
        </div>

        @foreach ($calendarWeeks as $week)
            <div class="grid grid-cols-[70px_repeat(7,minmax(0,1fr))] gap-2">
                <div class="flex flex-col justify-center gap-[3px] pr-1.5">
                    <span class="text-[11px] font-semibold uppercase tracking-[.08em] text-ink-400 dark:text-dark-text3">Week {{ $week['number'] }}</span>
                    <span
                        class="text-[15px] font-semibold"
                        :class="weekHours[{{ $week['number'] }}] > 0 ? 'text-ink-900 dark:text-dark-text1' : 'text-ink-400 dark:text-dark-text3'"
                        x-text="formatWeekHours({{ $week['number'] }})"
                    >{{ $week['hours'] > 0 ? number_format($week['hours'], 2, ',', '.') : '—' }}</span>
                </div>

                @foreach ($week['days'] as $day)
                    @php
                        $dayEntries = $day['entries'];
                        $dayEntry = $dayEntries->first();
                        $isEditable = $dayEntries->count() <= 1 && ! $dayEntry?->isLocked();
                        $emptyCellClasses = $day['isWeekend']
                            ? 'border-ink-200 bg-ink-50 dark:border-dark-subtleborder dark:bg-dark-subtle'
                            : 'border-ink-200 bg-white dark:border-dark-border dark:bg-dark-bg';
                    @endphp

                    @if (! $day['inMonth'])
                        <div class="min-h-[106px] rounded border border-dashed border-ink-200 bg-ink-50 px-2.5 py-2 dark:border-dark-mutedborder dark:bg-dark-muted">
                            <div class="{{ $dayNumberClasses }} text-ink-400 dark:text-dark-text4">{{ $day['date']->day }}</div>
                            <div class="mt-0.5 text-[11px] text-ink-400 dark:text-dark-text4">{{ $day['date']->translatedFormat('M') }}</div>
                        </div>
                    @elseif ($isEditable)
                        <div
                            data-day="{{ $day['date']->toDateString() }}"
                            x-data="timeEntryDay(@js([
                                'url' => route('admin.projects.time-entries.days.update', [$project, $day['date']->toDateString()]),
                                'week' => $week['number'],
                                'hours' => $dayEntry ? (float) $dayEntry->hours : null,
                                'description' => $dayEntry?->description,
                            ]))"
                            x-on:focusout="leave($event)"
                            :aria-busy="isSaving"
                            class="{{ $cellClasses }} {{ $focusCellClasses }}"
                            :class="error ? '{{ $errorCellClasses }}' : (isFilled ? '{{ $filledCellClasses }}' : '{{ $emptyCellClasses }}')"
                        >
                            <div class="{{ $dayNumberClasses }} text-ink-900 dark:text-dark-text1">{{ $day['date']->day }}</div>
                            <div class="flex items-center gap-1.5">
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    placeholder="0,00"
                                    aria-label="Uren op {{ $day['date']->translatedFormat('j F') }}"
                                    value="{{ $dayEntry ? number_format($dayEntry->hours, 2, ',', '.') : '' }}"
                                    x-model="hours"
                                    x-on:keydown.enter.prevent="$event.target.blur()"
                                    class="{{ $fieldClasses }} text-right text-sm"
                                    :class="isFilled ? '{{ $filledFieldClasses }} font-semibold' : '{{ $emptyFieldClasses }}'"
                                >
                                <span class="text-[11px] text-ink-500 dark:text-dark-text2">uur</span>
                            </div>
                            <textarea
                                rows="2"
                                placeholder="Omschrijving"
                                aria-label="Omschrijving op {{ $day['date']->translatedFormat('j F') }}"
                                x-model="description"
                                x-on:keydown.enter.prevent="$event.target.blur()"
                                class="{{ $fieldClasses }} flex-1 resize-none text-[12px] leading-4"
                                :class="isFilled ? '{{ $filledFieldClasses }}' : '{{ $emptyFieldClasses }}'"
                            >{{ $dayEntry?->description }}</textarea>
                            <p x-show="error" x-text="error" style="display: none" class="text-[11px] leading-4 text-status-red-fg dark:text-status-red-fg-dark"></p>
                        </div>
                    @else
                        <div data-day="{{ $day['date']->toDateString() }}" class="{{ $cellClasses }} {{ $filledCellClasses }}">
                            <div class="{{ $dayNumberClasses }} text-ink-900 dark:text-dark-text1">{{ $day['date']->day }}</div>
                            <div class="text-sm font-semibold text-ink-900 dark:text-dark-text1">
                                {{ number_format($dayEntries->sum('hours'), 2, ',', '.') }}
                                <span class="text-[11px] font-normal text-ink-500 dark:text-dark-text2">uur</span>
                            </div>
                            @if ($dayEntries->count() > 1)
                                <a href="{{ $periodRoute($project, $periodStart, 'list') }}" class="text-[12px] leading-4 text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">{{ $dayEntries->count() }} regels — bekijk in lijst</a>
                            @else
                                <div class="line-clamp-2 text-[12px] leading-4 text-ink-700 dark:text-dark-text2">{{ $dayEntry->description }}</div>
                                <div class="text-[11px] text-ink-400 dark:text-dark-text3">Gefactureerd</div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between gap-6 border-t border-ink-200 bg-ink-50 px-5 py-3.5 dark:border-dark-border dark:bg-dark-surface2">
        <div class="flex items-center gap-[26px] text-sm text-ink-700 dark:text-dark-text2" style="font-variant-numeric: tabular-nums">
            <x-approval-status-badge :status="$approval->status ?? 'draft'" />
            <span>Geregistreerd <strong class="font-semibold text-ink-900 dark:text-dark-text1" x-text="formatHours(monthHours) + ' uur'">{{ number_format($totalHours, 2, ',', '.') }} uur</strong></span>
            <span>Huidig uurtarief <strong class="font-semibold text-ink-900 dark:text-dark-text1">&euro; {{ number_format($project->rate, 2, ',', '.') }}</strong></span>
            <span>Excl. btw <strong class="font-semibold text-ink-900 dark:text-dark-text1" x-text="formatAmount(monthAmount)">&euro; {{ number_format($totalAmount, 2, ',', '.') }}</strong></span>
        </div>
        <p class="max-w-[420px] text-[12px] leading-[18px] text-ink-500 dark:text-dark-text2">Decimaal invoeren, bijvoorbeeld 1,5 voor anderhalf uur. Tab springt naar de omschrijving, daarna naar de volgende dag.</p>
    </div>
</div>
