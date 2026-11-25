<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceStatusRequest;
use App\Models\Invoice;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Services\InvoiceNumberService;
use App\Services\InvoicePdfService;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceNumberService $invoiceNumbers,
        private readonly InvoicePdfService $invoicePdfs,
    ) {}

    public function index(Request $request): View
    {
        $periodDirection = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        return view('admin.invoices.index', [
            'periodDirection' => $periodDirection,
            'invoices' => Invoice::query()
                ->with(['client', 'project'])
                ->orderBy('period_end', $periodDirection)
                ->orderBy('period_start', $periodDirection)
                ->orderByDesc('invoice_date')
                ->orderByDesc('sequence_number')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $projects = Project::query()->with('client')->orderBy('name')->get();

        $project = $request->query('project_id')
            ? $projects->firstWhere('id', (int) $request->query('project_id'))
            : null;

        [$periodStart, $periodEnd] = $this->requestedPeriod($request);

        $proposal = null;

        if ($project) {
            $settings = Setting::current();
            $subtotal = $this->uninvoicedAmount($project, $periodStart, $periodEnd);
            $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);
            $invoiceDate = now()->toDateString();
            $spansMultipleVatPeriods = $settings->spansMultipleVatPeriods($periodStart, $periodEnd);

            $proposal = [
                ...$this->invoiceNumbers->generate($project->client, $invoiceDate),
                'subtotal' => $subtotal,
                'vat_percentage' => $settings->default_vat_percentage,
                'vat_amount' => $vatAmount,
                'total' => $subtotal + $vatAmount,
                'invoice_date' => $invoiceDate,
                'due_date' => now()->addDays($settings->default_payment_term_days)->toDateString(),
                'spans_multiple_vat_periods' => $spansMultipleVatPeriods,
                'line_description' => $spansMultipleVatPeriods ? '' : Invoice::defaultLineDescriptionFor($project, $periodStart, $periodEnd),
                'months' => $this->monthSummaries($project, $periodStart, $periodEnd),
            ];
        }

        return view('admin.invoices.create', [
            'projects' => $projects,
            'project' => $project,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'monthOptions' => $this->monthOptions(),
            'proposal' => $proposal,
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $project = Project::query()->with('client')->findOrFail($validated['project_id']);
        $settings = Setting::current();
        $periodStart = $request->periodStart();
        $periodEnd = $request->periodEnd();

        $subtotal = $this->uninvoicedAmount($project, $periodStart, $periodEnd);

        if ($subtotal <= 0) {
            throw ValidationException::withMessages([
                'period_start' => 'Er zijn geen ongefactureerde uren in deze periode.',
            ]);
        }

        $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);
        $sequenceNumber = $this->invoiceNumbers->sequenceNumberFor($validated['invoice_number'], $validated['invoice_date']);

        $invoice = Invoice::query()->create([
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'line_description' => $validated['line_description'] ?? Invoice::defaultLineDescriptionFor($project, $periodStart, $periodEnd),
            'sequence_number' => $sequenceNumber,
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $subtotal,
            'vat_percentage' => $settings->default_vat_percentage,
            'vat_amount' => $vatAmount,
            'total' => $subtotal + $vatAmount,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('admin.invoices.edit', $invoice)
            ->with('status', 'Conceptfactuur aangemaakt.');
    }

    public function edit(Invoice $invoice): View
    {
        abort_unless($invoice->isDraft(), 404);

        $invoice->loadMissing(['client', 'project']);

        $settings = Setting::current();
        $subtotal = $this->uninvoicedAmount($invoice->project, $invoice->period_start, $invoice->period_end);
        $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);

        return view('admin.invoices.edit', [
            'invoice' => $invoice,
            'proposal' => [
                'subtotal' => $subtotal,
                'vat_percentage' => $settings->default_vat_percentage,
                'vat_amount' => $vatAmount,
                'total' => $subtotal + $vatAmount,
                'spans_multiple_vat_periods' => $settings->spansMultipleVatPeriods($invoice->period_start, $invoice->period_end),
                'months' => $this->monthSummaries($invoice->project, $invoice->period_start, $invoice->period_end),
            ],
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isDraft(), 404);

        $validated = $request->validated();
        $settings = Setting::current();

        $subtotal = $this->uninvoicedAmount($invoice->project, $invoice->period_start, $invoice->period_end);
        $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);
        $sequenceNumber = $this->invoiceNumbers->sequenceNumberFor($validated['invoice_number'], $validated['invoice_date'], $invoice->id);

        $invoice->update([
            'line_description' => $validated['line_description'] ?? Invoice::defaultLineDescriptionFor($invoice->project, $invoice->period_start, $invoice->period_end),
            'invoice_number' => $validated['invoice_number'],
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'sequence_number' => $sequenceNumber,
            'subtotal' => $subtotal,
            'vat_percentage' => $settings->default_vat_percentage,
            'vat_amount' => $vatAmount,
            'total' => $subtotal + $vatAmount,
        ]);

        return redirect()
            ->route('admin.invoices.edit', $invoice)
            ->with('status', 'Conceptfactuur bijgewerkt.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isDraft(), 404);

        $invoice->delete();

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', 'Conceptfactuur verwijderd.');
    }

    public function finalize(Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isDraft(), 404);

        $this->finalizeDraft($invoice);

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', 'Factuur definitief gemaakt en PDF gegenereerd.');
    }

    public function download(Invoice $invoice): StreamedResponse
    {
        abort_unless($invoice->pdf_path && Storage::disk('invoices')->exists($invoice->pdf_path), 404);

        return Storage::disk('invoices')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }

    public function downloadTimesheet(Invoice $invoice): StreamedResponse
    {
        abort_unless($invoice->timesheet_pdf_path && Storage::disk('invoices')->exists($invoice->timesheet_pdf_path), 404);

        return Storage::disk('invoices')->download($invoice->timesheet_pdf_path, "{$invoice->invoice_number}-urenspecificatie.pdf");
    }

    /**
     * Overwrite the invoice and timesheet PDFs of every invoice that has one with the current layout and settings.
     */
    public function regeneratePdfs(): RedirectResponse
    {
        $invoices = Invoice::query()->whereNotNull('pdf_path')->get();

        foreach ($invoices as $invoice) {
            $invoice->update([
                'pdf_path' => $this->invoicePdfs->generate($invoice),
                'timesheet_pdf_path' => $this->invoicePdfs->generateTimesheet($invoice),
            ]);
        }

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', "{$invoices->count()} PDF's opnieuw gegenereerd.");
    }

    public function updateStatus(UpdateInvoiceStatusRequest $request, Invoice $invoice): RedirectResponse
    {
        $status = $request->validated('status');

        if ($invoice->isDraft() && $status !== 'draft') {
            $this->finalizeDraft($invoice);
        }

        if (! $invoice->isDraft() && $status === 'draft') {
            $this->releaseFinalizedInvoice($invoice);
        }

        $invoice->update(match ($status) {
            'draft' => ['status' => 'draft', 'payment_status' => 'open', 'paid_at' => null],
            'open' => ['status' => 'final', 'payment_status' => 'open', 'paid_at' => null],
            'paid' => ['status' => 'final', 'payment_status' => 'paid', 'paid_at' => $invoice->paid_at ?? now()],
            'cancelled' => ['status' => 'cancelled', 'payment_status' => 'open', 'paid_at' => null],
        });

        return back()->with('status', 'Factuurstatus bijgewerkt.');
    }

    /**
     * Freeze the amounts, lock the period's time entries and generate the invoice and timesheet PDFs.
     */
    private function finalizeDraft(Invoice $invoice): void
    {
        $invoice->loadMissing(['client', 'project']);
        $settings = Setting::current();

        $entries = $this->uninvoicedEntries($invoice->project, $invoice->period_start, $invoice->period_end);

        $subtotal = $entries->sum(fn (TimeEntry $entry) => $entry->amount());
        $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);

        DB::transaction(function () use ($invoice, $entries, $subtotal, $settings, $vatAmount) {
            $invoice->update([
                'subtotal' => $subtotal,
                'vat_percentage' => $settings->default_vat_percentage,
                'vat_amount' => $vatAmount,
                'total' => $subtotal + $vatAmount,
                'status' => 'final',
            ]);

            TimeEntry::query()
                ->whereIn('id', $entries->pluck('id'))
                ->update(['invoice_id' => $invoice->id]);
        });

        $invoice->update([
            'pdf_path' => $this->invoicePdfs->generate($invoice),
            'timesheet_pdf_path' => $this->invoicePdfs->generateTimesheet($invoice),
        ]);
    }

    /**
     * Undo a finalization: free the locked time entries and remove the PDFs, which a draft must not have.
     */
    private function releaseFinalizedInvoice(Invoice $invoice): void
    {
        TimeEntry::query()
            ->where('invoice_id', $invoice->id)
            ->update(['invoice_id' => null]);

        Storage::disk('invoices')->delete(array_filter([$invoice->pdf_path, $invoice->timesheet_pdf_path]));
        $invoice->update(['pdf_path' => null, 'timesheet_pdf_path' => null]);
    }

    /**
     * @return Collection<int, TimeEntry>
     */
    private function uninvoicedEntries(Project $project, CarbonInterface $periodStart, CarbonInterface $periodEnd): Collection
    {
        return $project->timeEntries()
            ->whereBetween('date', [$periodStart->copy()->startOfMonth()->toDateString(), $periodEnd->copy()->endOfMonth()->toDateString()])
            ->whereNull('invoice_id')
            ->get();
    }

    private function uninvoicedAmount(Project $project, CarbonInterface $periodStart, CarbonInterface $periodEnd): float
    {
        return $this->uninvoicedEntries($project, $periodStart, $periodEnd)
            ->sum(fn (TimeEntry $entry) => $entry->amount());
    }

    /**
     * The uninvoiced hours and the approval status of each month in the period.
     *
     * @return array<int, array{label: string, hours: float, approval_status: ?string}>
     */
    private function monthSummaries(Project $project, CarbonInterface $periodStart, CarbonInterface $periodEnd): array
    {
        $entries = $this->uninvoicedEntries($project, $periodStart, $periodEnd);
        $approvals = $project->monthlyApprovals()->get();

        return collect(CarbonPeriod::create($periodStart->copy()->startOfMonth(), '1 month', $periodEnd->copy()->startOfMonth()))
            ->map(fn (CarbonInterface $month) => [
                'label' => $month->translatedFormat('F Y'),
                'hours' => (float) $entries->filter(fn (TimeEntry $entry) => $entry->date->isSameMonth($month))->sum('hours'),
                'approval_status' => $approvals->first(fn (MonthlyApproval $approval) => (int) $approval->year === $month->year && (int) $approval->month === $month->month)?->status,
            ])
            ->all();
    }

    /**
     * The months chosen on the creation screen: "from" and "to" as Y-m, or the single "year" and "month" that other screens link with.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function requestedPeriod(Request $request): array
    {
        $singleMonth = Carbon::create((int) $request->query('year', now()->year), (int) $request->query('month', now()->month), 1);

        $periodStart = $this->monthFromQuery($request->query('from')) ?? $singleMonth;
        $periodEnd = $this->monthFromQuery($request->query('to')) ?? $periodStart->copy();

        return [$periodStart, $periodEnd->lt($periodStart) ? $periodStart->copy() : $periodEnd];
    }

    private function monthFromQuery(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('!Y-m', $value);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function monthOptions(): array
    {
        return collect(range(0, 11))
            ->map(function (int $offset) {
                $date = now()->startOfMonth()->subMonths($offset);

                return [
                    'value' => $date->format('Y-m'),
                    'label' => $date->translatedFormat('F Y'),
                ];
            })
            ->all();
    }
}
