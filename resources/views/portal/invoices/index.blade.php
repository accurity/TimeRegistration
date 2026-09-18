<x-portal-layout>
    <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Mijn facturen</h1>
    <p class="mb-6 text-[13px] text-ink-500 dark:text-dark-text2">{{ $invoices->count() }} {{ $invoices->count() === 1 ? 'factuur' : 'facturen' }}</p>

    <div class="overflow-hidden rounded-md border border-ink-200 dark:border-dark-border">
        <div class="grid grid-cols-[110px_1.1fr_130px_130px_140px_110px] gap-4 border-b border-ink-200 bg-ink-50 px-6 py-[11px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Factuur</div>
            <div>Periode</div>
            <div>Vervaldatum</div>
            <div class="text-right">Bedrag</div>
            <div>Status</div>
            <div class="text-right">Download</div>
        </div>

        @forelse ($invoices as $invoice)
            <div @class([
                'grid grid-cols-[110px_1.1fr_130px_130px_140px_110px] items-center gap-4 border-b border-ink-100 px-6 py-[13px] text-sm dark:border-dark-border',
                'bg-ink-50 dark:bg-dark-surface2' => $loop->even,
                'last:border-b-0' => true,
            ]) style="font-variant-numeric: tabular-nums">
                <div class="text-ink-900 dark:text-dark-text1">{{ $invoice->invoice_number }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $invoice->project->name }} &middot; {{ \Illuminate\Support\Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->translatedFormat('M Y') }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $invoice->due_date->format('d-m-Y') }}</div>
                <div class="text-right text-ink-900 dark:text-dark-text1">&euro; {{ number_format($invoice->total, 2, ',', '.') }}</div>
                <div><x-invoice-status-badge :status="$invoice->status" :payment-status="$invoice->payment_status" /></div>
                <div class="text-right">
                    <a href="{{ route('portal.invoices.download', $invoice) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">PDF</a>
                </div>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                Er staan nog geen facturen voor u klaar.
            </div>
        @endforelse
    </div>
</x-portal-layout>
