<x-admin-layout>
    <div class="mb-[22px]">
        <h1 class="mb-1 font-display text-[28px] font-bold leading-9 text-ink-900 dark:text-dark-text1">Nieuwe factuur</h1>
    </div>

    <x-input-error :messages="$errors->get('invoice_number')" class="mb-4" />
    <x-input-error :messages="$errors->get('invoice_date')" class="mb-4" />
    <x-input-error :messages="$errors->get('due_date')" class="mb-4" />
    <x-input-error :messages="$errors->get('period_start')" class="mb-4" />
    <x-input-error :messages="$errors->get('period_end')" class="mb-4" />

    <form method="GET" action="{{ route('admin.invoices.create') }}" class="mb-[22px] flex items-end gap-[14px]">
        <div>
            <x-input-label for="project_id" value="Project" />
            <x-select-input id="project_id" name="project_id" class="w-64" onchange="this.form.submit()">
                <option value="">Kies een project…</option>
                @foreach ($projects as $option)
                    <option value="{{ $option->id }}" @selected($project && $project->id === $option->id)>{{ $option->client->name }} — {{ $option->name }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="from" value="Van" />
            <x-select-input id="from" name="from" class="w-44" onchange="this.form.submit()">
                @foreach ($monthOptions as $option)
                    <option value="{{ $option['value'] }}" @selected($option['value'] === $periodStart->format('Y-m'))>{{ ucfirst($option['label']) }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="to" value="Tot en met" />
            <x-select-input id="to" name="to" class="w-44" onchange="this.form.submit()">
                @foreach ($monthOptions as $option)
                    <option value="{{ $option['value'] }}" @selected($option['value'] === $periodEnd->format('Y-m'))>{{ ucfirst($option['label']) }}</option>
                @endforeach
            </x-select-input>
        </div>
        <noscript>
            <button type="submit" class="inline-flex items-center justify-center rounded-[3px] border border-ink-300 bg-white px-[18px] py-[9px] text-sm font-semibold text-brand-800 hover:bg-ink-50 dark:border-dark-borderfield dark:bg-transparent dark:text-dark-text1 dark:hover:bg-dark-surface2">Bijwerken</button>
        </noscript>
    </form>

    @if (! $project)
        <div class="max-w-2xl rounded-md border border-ink-200 bg-white p-5 text-sm text-ink-500 dark:border-dark-border dark:bg-dark-surface1 dark:text-dark-text2">
            Kies eerst een project en periode om een conceptfactuur op te stellen.
        </div>
    @else
        <div class="max-w-2xl rounded-md border border-ink-200 bg-white dark:border-dark-border dark:bg-dark-surface1">
            <form method="POST" action="{{ route('admin.invoices.store') }}" class="px-[22px] py-5">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <input type="hidden" name="period_start" value="{{ $periodStart->format('Y-m') }}">
                <input type="hidden" name="period_end" value="{{ $periodEnd->format('Y-m') }}">

                @include('admin.invoices._months', ['months' => $proposal['months'], 'showApprovalStatus' => $project->client->requires_approval])

                <div class="mb-[14px] grid gap-[9px] rounded-md bg-ink-50 px-4 py-3 text-sm dark:bg-dark-surface2" style="font-variant-numeric: tabular-nums">
                    <div class="flex justify-between"><span class="text-ink-700 dark:text-dark-text2">Subtotaal</span><span class="text-ink-900 dark:text-dark-text1">&euro; {{ number_format($proposal['subtotal'], 2, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-ink-700 dark:text-dark-text2">BTW ({{ number_format($proposal['vat_percentage'], 0) }}%)</span><span class="text-ink-900 dark:text-dark-text1">&euro; {{ number_format($proposal['vat_amount'], 2, ',', '.') }}</span></div>
                    <div class="flex justify-between border-t border-ink-200 pt-[9px] font-semibold text-ink-900 dark:border-dark-border dark:text-dark-text1"><span>Totaal</span><span>&euro; {{ number_format($proposal['total'], 2, ',', '.') }}</span></div>
                </div>

                <div class="mb-[14px]">
                    <x-input-label for="line_description" value="Tekst op de factuurregel" />
                    <x-text-input id="line_description" name="line_description" type="text" class="block w-full" value="{{ old('line_description', $proposal['line_description']) }}" :required="$proposal['spans_multiple_vat_periods']" />
                    @if ($proposal['spans_multiple_vat_periods'])
                        <p class="mt-1 text-[12px] text-ink-500 dark:text-dark-text2">De periode loopt over meer dan één btw-periode. Vul in wat er op de factuurregel moet staan.</p>
                    @endif
                    <x-input-error :messages="$errors->get('line_description')" class="mt-2" />
                </div>

                <div class="mb-[14px]">
                    <x-input-label for="invoice_number" value="Factuurnummer" />
                    <x-text-input id="invoice_number" name="invoice_number" type="text" class="block w-full" value="{{ old('invoice_number', $proposal['invoice_number']) }}" required />
                </div>

                <div class="mb-[22px] grid grid-cols-2 gap-[14px]">
                    <div>
                        <x-input-label for="invoice_date" value="Factuurdatum" />
                        <x-text-input id="invoice_date" name="invoice_date" type="date" class="block w-full" value="{{ old('invoice_date', $proposal['invoice_date']) }}" required />
                    </div>
                    <div>
                        <x-input-label for="due_date" value="Vervaldatum" />
                        <x-text-input id="due_date" name="due_date" type="date" class="block w-full" value="{{ old('due_date', $proposal['due_date']) }}" required />
                    </div>
                </div>

                <div class="flex gap-[10px] border-t border-ink-100 pt-[18px] dark:border-dark-border">
                    <x-primary-button>Opslaan als concept</x-primary-button>
                    <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center justify-center rounded-[3px] border border-ink-300 bg-white px-[18px] py-[9px] text-sm font-semibold text-brand-800 hover:bg-ink-50 dark:border-dark-borderfield dark:bg-transparent dark:text-dark-text1 dark:hover:bg-dark-surface2">Annuleren</a>
                </div>
            </form>
        </div>
    @endif
</x-admin-layout>
