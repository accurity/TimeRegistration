@php
    $currentPeriodLabel = ucfirst(now()->translatedFormat('F Y'));
@endphp
<x-portal-layout>
    <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Uren</h1>
    <p class="mb-6 text-[13px] text-ink-500 dark:text-dark-text2">{{ ucfirst(now()->translatedFormat('l j F Y')) }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4 flex items-center justify-between">
        <h2 class="font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">{{ $currentPeriodLabel }}</h2>
    </div>

    <div class="mb-6 overflow-hidden rounded-md border border-ink-200 dark:border-dark-border">
        <div class="grid grid-cols-[1.6fr_1fr_100px] gap-3 border-b border-ink-200 bg-ink-50 px-5 py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Project</div>
            <div>Status</div>
            <div></div>
        </div>

        @forelse ($projectStatuses as $row)
            <div @class([
                'grid grid-cols-[1.6fr_1fr_100px] items-center gap-3 border-b border-ink-100 px-5 py-3 text-sm dark:border-dark-border',
                'bg-ink-50 dark:bg-dark-surface2' => $loop->even,
                'last:border-b-0' => true,
            ])>
                <div class="text-ink-900 dark:text-dark-text1">{{ $row['project']->name }}</div>
                <div>
                    @if (! $row['approval'])
                        <span class="inline-block rounded-[3px] border border-status-gray-border bg-status-gray-bg px-[9px] py-[3px] text-[12px] font-semibold text-status-gray-fg dark:border-status-gray-border-dark dark:bg-status-gray-bg-dark dark:text-status-gray-fg-dark">Nog geen uren ingediend</span>
                    @else
                        <x-approval-status-badge :status="$row['approval']->status" />
                    @endif
                </div>
                <div class="text-right">
                    @if ($row['approval'])
                        <a href="{{ route('portal.approvals.show', [$row['project'], $row['year'], $row['month']]) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Bekijken</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-5 py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                Er zijn nog geen projecten aan u gekoppeld.
            </div>
        @endforelse
    </div>

    <h2 class="mb-4 font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Openstaande facturen</h2>

    <div class="overflow-hidden rounded-md border border-ink-200 dark:border-dark-border">
        <div class="grid grid-cols-[110px_1.2fr_130px_120px] gap-3 border-b border-ink-200 bg-ink-50 px-5 py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Factuur</div>
            <div>Project</div>
            <div>Vervaldatum</div>
            <div class="text-right">Bedrag</div>
        </div>

        @forelse ($openInvoices as $invoice)
            <div @class([
                'grid grid-cols-[110px_1.2fr_130px_120px] items-center gap-3 border-b border-ink-100 px-5 py-3 text-sm dark:border-dark-border',
                'bg-ink-50 dark:bg-dark-surface2' => $loop->even,
                'last:border-b-0' => true,
            ]) style="font-variant-numeric: tabular-nums">
                <div class="text-ink-900 dark:text-dark-text1">{{ $invoice->invoice_number }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $invoice->project->name }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $invoice->due_date->format('d-m-Y') }}</div>
                <div class="text-right text-ink-900 dark:text-dark-text1">&euro; {{ number_format($invoice->total, 2, ',', '.') }}</div>
            </div>
        @empty
            <div class="px-5 py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                Geen openstaande facturen.
            </div>
        @endforelse
    </div>
</x-portal-layout>
