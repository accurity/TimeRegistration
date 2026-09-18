@php
    $periodLabel = \Illuminate\Support\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
    $periodStart = \Illuminate\Support\Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $periodEnd = \Illuminate\Support\Carbon::createFromDate($year, $month, 1)->endOfMonth();
@endphp
<x-portal-layout>
    <div class="mb-7 flex items-start justify-between gap-6">
        <div>
            <h1 class="mb-[6px] font-display text-[32px] font-bold leading-10 text-ink-900 dark:text-dark-text1">Uren {{ $periodLabel }}</h1>
            <div class="text-[15px] text-ink-700 dark:text-dark-text2">{{ $project->name }} · uitgevoerd door Accurity — online communicatie</div>
        </div>
        <x-approval-status-badge :status="$approval->status" />
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-status-red-fg dark:text-status-red-fg-dark">{{ session('error') }}</div>
    @endif

    <div class="mb-7 grid grid-cols-4 overflow-hidden rounded-md border border-ink-200 bg-ink-50 dark:border-dark-border dark:bg-dark-surface1">
        <div class="border-r border-ink-200 px-5 py-4 dark:border-dark-border">
            <div class="mb-[6px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2">Periode</div>
            <div class="text-[15px] text-ink-900 dark:text-dark-text1">{{ $periodStart->format('j') }} – {{ $periodEnd->format('j F') }}</div>
        </div>
        <div class="border-r border-ink-200 px-5 py-4 dark:border-dark-border">
            <div class="mb-[6px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2">Totaal uren</div>
            <div class="text-[15px] text-ink-900 dark:text-dark-text1" style="font-variant-numeric: tabular-nums">{{ number_format($totalHours, 2, ',', '.') }}</div>
        </div>
        <div class="border-r border-ink-200 px-5 py-4 dark:border-dark-border">
            <div class="mb-[6px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2">Uurtarief</div>
            <div class="text-[15px] text-ink-900 dark:text-dark-text1" style="font-variant-numeric: tabular-nums">&euro; {{ number_format($project->rate, 2, ',', '.') }}</div>
        </div>
        <div class="px-5 py-4">
            <div class="mb-[6px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2">Bedrag excl. btw</div>
            <div class="text-[15px] font-semibold text-ink-900 dark:text-dark-text1" style="font-variant-numeric: tabular-nums">&euro; {{ number_format($totalAmount, 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="mb-7 overflow-hidden rounded-md border border-ink-200 dark:border-dark-border">
        <div class="grid grid-cols-[150px_1fr_110px] gap-4 border-b border-ink-200 bg-ink-50 px-5 py-[11px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Datum</div>
            <div>Omschrijving</div>
            <div class="text-right">Uren</div>
        </div>
        @foreach ($entries as $entry)
            <div class="grid grid-cols-[150px_1fr_110px] gap-4 border-b border-ink-100 px-5 py-[13px] text-[15px] dark:border-dark-border" style="font-variant-numeric: tabular-nums">
                <div class="text-ink-900 dark:text-dark-text1">{{ $entry->date->translatedFormat('j F') }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $entry->description }}</div>
                <div class="text-right text-ink-900 dark:text-dark-text1">{{ number_format($entry->hours, 2, ',', '.') }}</div>
            </div>
        @endforeach
        <div class="grid grid-cols-[150px_1fr_110px] gap-4 bg-brand-100 px-5 py-[15px] text-base font-semibold dark:bg-brand-darktint dark:text-dark-text1" style="font-variant-numeric: tabular-nums">
            <div>Totaal</div>
            <div></div>
            <div class="text-right">{{ number_format($totalHours, 2, ',', '.') }}</div>
        </div>
    </div>

    @if ($approval->isPending())
        <div class="rounded-md border border-ink-300 border-t-[3px] border-t-brand-600 p-7 dark:border-dark-borderfield dark:border-t-brand-darkfg">
            <h2 class="mb-[6px] font-display text-xl font-bold leading-7 text-ink-900 dark:text-dark-text1">Beoordeling</h2>
            <p class="mb-[22px] max-w-xl text-sm leading-[22px] text-ink-700 dark:text-dark-text2">Na goedkeuring wordt de factuur automatisch opgesteld en verstuurd. Bij afkeuren nemen wij contact met u op.</p>

            <form method="POST" action="{{ route('portal.approvals.reject', [$project, $year, $month]) }}" id="reject-form" class="grid grid-cols-[1fr_300px] items-end gap-7">
                @csrf
                <div>
                    <x-input-label for="rejection_reason" value="Reden — alleen verplicht bij afkeuren" />
                    <textarea id="rejection_reason" name="rejection_reason" rows="2" class="block w-full rounded-[3px] border border-ink-300 bg-white px-3 py-[9px] text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-600 focus:outline-none focus:ring-[3px] focus:ring-brand-400/30 dark:border-dark-borderfield dark:bg-dark-bg dark:text-dark-text1 dark:placeholder:text-dark-text3" placeholder="Bijvoorbeeld: het overleg duurde korter.">{{ old('rejection_reason') }}</textarea>
                    <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                </div>
                <div class="grid gap-[10px]">
                    <button type="submit" form="approve-form" class="rounded-[3px] bg-status-green-fg px-3 py-3 text-[15px] font-semibold text-white hover:opacity-90">Uren goedkeuren</button>
                    <button type="submit" class="rounded-[3px] border border-status-red-border bg-white px-3 py-3 text-[15px] font-semibold text-status-red-fg hover:bg-status-red-bg dark:border-status-red-border-dark dark:bg-transparent dark:text-status-red-fg-dark dark:hover:bg-status-red-bg-dark">Afkeuren</button>
                </div>
            </form>
            <form method="POST" action="{{ route('portal.approvals.approve', [$project, $year, $month]) }}" id="approve-form" class="hidden">
                @csrf
            </form>
        </div>
    @elseif ($approval->isApproved())
        <div class="flex items-center justify-between gap-6 rounded-md border border-status-green-border border-l-[3px] bg-status-green-bg px-6 py-5 dark:border-status-green-border-dark dark:bg-status-green-bg-dark">
            <div>
                <div class="mb-1 font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Goedgekeurd op {{ $approval->approved_at?->translatedFormat('j F Y') }}@if ($approval->approvedBy) door {{ $approval->approvedBy->name }}@endif</div>
                <div class="text-sm leading-[22px] text-ink-700 dark:text-dark-text2">De factuur wordt zo spoedig mogelijk opgesteld en per e-mail verstuurd.</div>
            </div>
        </div>
    @else
        <div class="rounded-md border border-status-red-border border-l-[3px] bg-status-red-bg px-6 py-5 dark:border-status-red-border-dark dark:bg-status-red-bg-dark">
            <div class="mb-1 font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Afgekeurd op {{ $approval->approved_at?->translatedFormat('j F Y') }}@if ($approval->approvedBy) door {{ $approval->approvedBy->name }}@endif</div>
            @if ($approval->rejection_reason)
                <div class="text-sm leading-[22px] text-ink-700 dark:text-dark-text2">Reden: {{ $approval->rejection_reason }}</div>
            @endif
        </div>
    @endif
</x-portal-layout>
