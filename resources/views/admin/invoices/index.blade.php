<x-admin-layout>
    <div class="mb-[22px] flex items-end justify-between">
        <div>
            <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Facturen</h1>
            <p class="text-[13px] text-ink-500 dark:text-dark-text2">{{ $invoices->count() }} {{ $invoices->count() === 1 ? 'factuur' : 'facturen' }}</p>
        </div>
        <a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center justify-center rounded-[3px] border border-brand-600 bg-brand-600 px-[18px] py-[9px] text-sm font-semibold text-white hover:border-brand-800 hover:bg-brand-800">
            Nieuwe factuur
        </a>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-status-red-fg dark:text-status-red-fg-dark">{{ session('error') }}</div>
    @endif

    <div class="overflow-hidden rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <div class="grid grid-cols-[140px_1.2fr_1fr_110px_120px_120px_100px] gap-3 border-b border-ink-200 bg-ink-50 px-[22px] py-[10px] text-[11px] font-semibold uppercase tracking-[.1em] text-ink-500 dark:border-dark-border dark:bg-dark-surface2 dark:text-dark-text2">
            <div>Factuur</div>
            <div>Klant</div>
            <div>Project</div>
            <div>Periode</div>
            <div class="text-right">Bedrag</div>
            <div>Status</div>
            <div></div>
        </div>

        @forelse ($invoices as $invoice)
            <div @class([
                'grid grid-cols-[140px_1.2fr_1fr_110px_120px_120px_100px] items-center gap-3 border-b border-ink-100 px-[22px] py-3 text-sm dark:border-dark-border',
                'bg-ink-50 dark:bg-dark-surface2' => $loop->even,
                'last:border-b-0' => true,
            ]) style="font-variant-numeric: tabular-nums">
                <div class="text-ink-900 dark:text-dark-text1">{{ $invoice->invoice_number }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $invoice->client->name }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ $invoice->project->name }}</div>
                <div class="text-ink-700 dark:text-dark-text2">{{ \Illuminate\Support\Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->translatedFormat('M Y') }}</div>
                <div class="text-right text-ink-900 dark:text-dark-text1">&euro; {{ number_format($invoice->total, 2, ',', '.') }}</div>
                <div><x-invoice-status-badge :status="$invoice->status" /></div>
                <div class="text-right">
                    @if ($invoice->isDraft())
                        <a href="{{ route('admin.invoices.edit', $invoice) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Bewerken</a>
                    @elseif ($invoice->pdf_path)
                        <a href="{{ route('admin.invoices.download', $invoice) }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">PDF</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-[22px] py-8 text-center text-sm text-ink-500 dark:text-dark-text2">
                Nog geen facturen aangemaakt.
            </div>
        @endforelse
    </div>
</x-admin-layout>
