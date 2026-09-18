@php
    $openTotal = $openInvoices->sum('total');
@endphp
<x-admin-layout>
    <div class="mb-[22px] flex items-end justify-between">
        <div>
            <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Dashboard</h1>
            <div class="text-[13px] text-ink-500 dark:text-dark-text2">{{ ucfirst(now()->translatedFormat('l j F Y')) }}</div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-3 gap-4">
        <div class="rounded-[4px] border border-ink-200 border-l-[3px] border-l-status-amber-fg bg-white p-5 dark:border-dark-border dark:border-l-status-amber-fg-dark dark:bg-dark-surface1">
            <div class="mb-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2">Openstaand</div>
            <div class="font-display text-[30px] font-bold leading-9 text-ink-900 dark:text-dark-text1" style="font-variant-numeric: tabular-nums">&euro; {{ number_format($openTotal, 2, ',', '.') }}</div>
            <div class="mt-[6px] text-[13px] text-ink-700 dark:text-dark-text2">
                {{ $openInvoices->count() }} {{ $openInvoices->count() === 1 ? 'factuur' : 'facturen' }}
                @if ($overdueInvoiceCount > 0)
                    &middot; <span class="text-status-red-fg dark:text-status-red-fg-dark">{{ $overdueInvoiceCount }} vervallen</span>
                @endif
            </div>
        </div>

        <div class="rounded-[4px] border border-ink-200 border-l-[3px] border-l-brand-600 bg-white p-5 dark:border-dark-border dark:border-l-brand-darkfg dark:bg-dark-surface1">
            <div class="mb-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2">Klaar om te factureren</div>
            <div class="font-display text-[30px] font-bold leading-9 text-ink-900 dark:text-dark-text1" style="font-variant-numeric: tabular-nums">{{ $readyToInvoice->count() }} {{ $readyToInvoice->count() === 1 ? 'periode' : 'perioden' }}</div>
            <div class="mt-[6px] text-[13px] text-ink-700 dark:text-dark-text2">&euro; {{ number_format($readyToInvoice->sum('amount'), 2, ',', '.') }} aan geregistreerde uren</div>
        </div>

        <div class="rounded-[4px] border border-ink-200 border-l-[3px] border-l-ink-500 bg-white p-5 dark:border-dark-border dark:border-l-dark-text3 dark:bg-dark-surface1">
            <div class="mb-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:text-dark-text2">Wacht op goedkeuring</div>
            <div class="font-display text-[30px] font-bold leading-9 text-ink-900 dark:text-dark-text1" style="font-variant-numeric: tabular-nums">{{ $pendingApprovals->count() }} {{ $pendingApprovals->count() === 1 ? 'periode' : 'perioden' }}</div>
            <div class="mt-[6px] text-[13px] text-ink-700 dark:text-dark-text2">
                @if ($pendingApprovals->isNotEmpty())
                    {{ $pendingApprovals->first()->project->client->name }} &middot; verstuurd {{ $pendingApprovals->first()->submitted_at?->format('d-m') }}
                @else
                    Niets in afwachting
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-[1.3fr_1fr] items-start gap-4">
        <div class="rounded-[4px] border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
            <div class="flex items-center justify-between border-b border-ink-200 px-5 py-4 dark:border-dark-border">
                <h2 class="font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Openstaande facturen</h2>
                <a href="{{ route('admin.invoices.index') }}" class="text-[13px] text-brand-600 dark:text-brand-darkhover">Alle facturen</a>
            </div>
            <div class="grid grid-cols-[100px_1.3fr_1fr_110px_120px] gap-3 border-b border-ink-200 bg-ink-50 px-5 py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
                <div>Nummer</div>
                <div>Klant</div>
                <div>Periode</div>
                <div class="text-right">Bedrag</div>
                <div>Vervaldatum</div>
            </div>
            @forelse ($openInvoices as $invoice)
                <div class="grid grid-cols-[100px_1.3fr_1fr_110px_120px] items-center gap-3 border-b border-ink-100 px-5 py-3 text-sm last:border-b-0 dark:border-dark-border" style="font-variant-numeric: tabular-nums">
                    <div class="text-ink-900 dark:text-dark-text1">{{ $invoice->invoice_number }}</div>
                    <div class="text-ink-700 dark:text-dark-text2">{{ $invoice->client->name }}</div>
                    <div class="text-ink-700 dark:text-dark-text2">{{ \Illuminate\Support\Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->translatedFormat('M Y') }}</div>
                    <div class="text-right text-ink-900 dark:text-dark-text1">&euro; {{ number_format($invoice->total, 2, ',', '.') }}</div>
                    <div @class(['text-status-red-fg dark:text-status-red-fg-dark' => $invoice->due_date->isPast(), 'text-ink-700 dark:text-dark-text2' => ! $invoice->due_date->isPast()])>{{ $invoice->due_date->format('d-m-Y') }}</div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-ink-500 dark:text-dark-text2">Geen openstaande facturen.</div>
            @endforelse
        </div>

        <div class="grid gap-4">
            <div class="rounded-[4px] border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
                <div class="border-b border-ink-200 px-5 py-4 dark:border-dark-border">
                    <h2 class="font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Klaar om te factureren</h2>
                </div>
                @forelse ($readyToInvoice as $row)
                    <a href="{{ route('admin.projects.time-entries.index', [$row['project'], 'year' => $row['year'], 'month' => $row['month']]) }}" class="flex items-center justify-between border-b border-ink-100 px-5 py-3 text-sm last:border-b-0 hover:bg-ink-50 dark:border-dark-border dark:hover:bg-dark-surface2">
                        <div>
                            <div class="text-ink-900 dark:text-dark-text1">{{ $row['project']->name }}</div>
                            <div class="text-[12px] text-ink-500 dark:text-dark-text2">{{ \Illuminate\Support\Carbon::createFromDate($row['year'], $row['month'], 1)->translatedFormat('F Y') }}</div>
                        </div>
                        <div class="text-right text-ink-900 dark:text-dark-text1" style="font-variant-numeric: tabular-nums">&euro; {{ number_format($row['amount'], 2, ',', '.') }}</div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-ink-500 dark:text-dark-text2">Niets klaar om te factureren.</div>
                @endforelse
            </div>

            <div class="rounded-[4px] border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
                <div class="border-b border-ink-200 px-5 py-4 dark:border-dark-border">
                    <h2 class="font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Wacht op goedkeuring</h2>
                </div>
                @forelse ($pendingApprovals as $approval)
                    <a href="{{ route('admin.projects.time-entries.index', [$approval->project, 'year' => $approval->year, 'month' => $approval->month]) }}" class="flex items-center justify-between border-b border-ink-100 px-5 py-3 text-sm last:border-b-0 hover:bg-ink-50 dark:border-dark-border dark:hover:bg-dark-surface2">
                        <div>
                            <div class="text-ink-900 dark:text-dark-text1">{{ $approval->project->client->name }}</div>
                            <div class="text-[12px] text-ink-500 dark:text-dark-text2">{{ \Illuminate\Support\Carbon::createFromDate($approval->year, $approval->month, 1)->translatedFormat('F Y') }}</div>
                        </div>
                        <div class="text-right text-[12px] text-ink-500 dark:text-dark-text2">verstuurd {{ $approval->submitted_at?->format('d-m') }}</div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-ink-500 dark:text-dark-text2">Niets in afwachting.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
