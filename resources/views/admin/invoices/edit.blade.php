@php
    $periodLabel = \Illuminate\Support\Carbon::createFromDate($invoice->period_year, $invoice->period_month, 1)->translatedFormat('F Y');
@endphp
<x-admin-layout>
    <div class="mb-[22px]">
        <p class="mb-1 text-[13px] text-ink-500 dark:text-dark-text2">
            <a href="{{ route('admin.invoices.index') }}" class="text-brand-600 hover:text-brand-800 dark:text-brand-darkhover">Facturen</a>
        </p>
        <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Conceptfactuur bewerken</h1>
        <p class="text-[13px] text-ink-500 dark:text-dark-text2">{{ $invoice->client->name }} — {{ $invoice->project->name }} — {{ $periodLabel }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($proposal['approval'] && ! $proposal['approval']->isApproved())
        <div class="mb-[14px] max-w-2xl rounded-md border border-status-amber-border bg-status-amber-bg px-4 py-3 text-[13px] text-status-amber-fg dark:border-status-amber-border-dark dark:bg-status-amber-bg-dark dark:text-status-amber-fg-dark">
            Let op: de uren van deze periode zijn nog niet goedgekeurd door de klant.
        </div>
    @elseif (! $proposal['approval'])
        <div class="mb-[14px] max-w-2xl rounded-md border border-status-amber-border bg-status-amber-bg px-4 py-3 text-[13px] text-status-amber-fg dark:border-status-amber-border-dark dark:bg-status-amber-bg-dark dark:text-status-amber-fg-dark">
            Let op: deze periode is nog niet ter goedkeuring aangeboden aan de klant.
        </div>
    @endif

    <div class="max-w-2xl rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
        <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="px-[22px] py-5">
            @csrf
            @method('PUT')

            <div class="mb-[14px] grid gap-[9px] rounded-md bg-ink-50 px-4 py-3 text-sm dark:bg-dark-surface2" style="font-variant-numeric: tabular-nums">
                <div class="flex justify-between"><span class="text-ink-700 dark:text-dark-text2">Subtotaal</span><span class="text-ink-900 dark:text-dark-text1">&euro; {{ number_format($proposal['subtotal'], 2, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-ink-700 dark:text-dark-text2">BTW ({{ number_format($proposal['vat_percentage'], 0) }}%)</span><span class="text-ink-900 dark:text-dark-text1">&euro; {{ number_format($proposal['vat_amount'], 2, ',', '.') }}</span></div>
                <div class="flex justify-between border-t border-ink-200 pt-[9px] font-semibold text-ink-900 dark:border-dark-border dark:text-dark-text1"><span>Totaal</span><span>&euro; {{ number_format($proposal['total'], 2, ',', '.') }}</span></div>
            </div>

            <div class="mb-[14px]">
                <x-input-label for="invoice_number" value="Factuurnummer" />
                <x-text-input id="invoice_number" name="invoice_number" type="text" class="block w-full" value="{{ old('invoice_number', $invoice->invoice_number) }}" required />
                <x-input-error :messages="$errors->get('invoice_number')" class="mt-2" />
            </div>

            <div class="mb-[22px] grid grid-cols-2 gap-[14px]">
                <div>
                    <x-input-label for="invoice_date" value="Factuurdatum" />
                    <x-text-input id="invoice_date" name="invoice_date" type="date" class="block w-full" value="{{ old('invoice_date', $invoice->invoice_date->toDateString()) }}" required />
                    <x-input-error :messages="$errors->get('invoice_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="due_date" value="Vervaldatum" />
                    <x-text-input id="due_date" name="due_date" type="date" class="block w-full" value="{{ old('due_date', $invoice->due_date->toDateString()) }}" required />
                    <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-ink-100 pt-[18px] dark:border-dark-border">
                <div class="flex gap-[10px]">
                    <x-primary-button>Opslaan</x-primary-button>
                    <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center justify-center rounded-[3px] border border-ink-300 bg-white px-[18px] py-[9px] text-sm font-semibold text-brand-800 hover:bg-ink-50 dark:border-dark-borderfield dark:bg-transparent dark:text-dark-text1 dark:hover:bg-dark-surface2">Annuleren</a>
                </div>
                <button
                    type="submit"
                    form="delete-invoice-{{ $invoice->id }}"
                    class="text-sm text-status-red-fg hover:underline dark:text-status-red-fg-dark"
                    onclick="return confirm('Deze conceptfactuur verwijderen?')"
                >Concept verwijderen</button>
                <form id="delete-invoice-{{ $invoice->id }}" method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </form>
    </div>

    <div class="mt-[14px] max-w-2xl rounded-md border border-ink-200 bg-white px-[22px] py-5 dark:border-dark-border dark:bg-dark-surface1">
        <h3 class="mb-1 font-display text-base font-bold leading-6 text-ink-900 dark:text-dark-text1">Definitief maken</h3>
        <p class="mb-[14px] text-[13px] leading-5 text-ink-700 dark:text-dark-text2">De bedragen worden bevroren, de uren van deze periode worden vergrendeld en de PDF wordt gegenereerd. Dit kan daarna niet meer worden teruggedraaid (alleen annuleren).</p>
        <form method="POST" action="{{ route('admin.invoices.finalize', $invoice) }}" onsubmit="return confirm('Deze factuur definitief maken? De uren worden vergrendeld.')">
            @csrf
            <x-primary-button>Definitief maken</x-primary-button>
        </form>
    </div>
</x-admin-layout>
