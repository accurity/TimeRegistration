<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceStatusRequest;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Services\InvoiceNumberService;
use App\Services\InvoicePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                ->orderBy('period_year', $periodDirection)
                ->orderBy('period_month', $periodDirection)
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

        if ($period = $request->query('period')) {
            [$year, $month] = array_map('intval', explode('-', $period));
        } else {
            $year = (int) $request->query('year', now()->year);
            $month = (int) $request->query('month', now()->month);
        }

        $proposal = null;

        if ($project) {
            $settings = Setting::current();
            $subtotal = $this->uninvoicedAmount($project, $year, $month);
            $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);
            $invoiceDate = now()->toDateString();

            $proposal = [
                ...$this->invoiceNumbers->generate($project->client, $invoiceDate),
                'subtotal' => $subtotal,
                'vat_percentage' => $settings->default_vat_percentage,
                'vat_amount' => $vatAmount,
                'total' => $subtotal + $vatAmount,
                'invoice_date' => $invoiceDate,
                'due_date' => now()->addDays($settings->default_payment_term_days)->toDateString(),
                'approval' => $project->monthlyApprovals()->where('year', $year)->where('month', $month)->first(),
            ];
        }

        return view('admin.invoices.create', [
            'projects' => $projects,
            'project' => $project,
            'year' => $year,
            'month' => $month,
            'monthOptions' => $this->monthOptions(),
            'proposal' => $proposal,
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $project = Project::query()->with('client')->findOrFail($validated['project_id']);
        $settings = Setting::current();

        $subtotal = $this->uninvoicedAmount($project, $validated['period_year'], $validated['period_month']);
        $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);
        $sequenceNumber = $this->invoiceNumbers->nextSequenceNumber($project->client_id, $validated['invoice_date']);

        $invoice = Invoice::query()->create([
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'period_year' => $validated['period_year'],
            'period_month' => $validated['period_month'],
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
        $subtotal = $this->uninvoicedAmount($invoice->project, $invoice->period_year, $invoice->period_month);
        $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);

        return view('admin.invoices.edit', [
            'invoice' => $invoice,
            'proposal' => [
                'subtotal' => $subtotal,
                'vat_percentage' => $settings->default_vat_percentage,
                'vat_amount' => $vatAmount,
                'total' => $subtotal + $vatAmount,
                'approval' => $invoice->project->monthlyApprovals()
                    ->where('year', $invoice->period_year)
                    ->where('month', $invoice->period_month)
                    ->first(),
            ],
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isDraft(), 404);

        $validated = $request->validated();
        $settings = Setting::current();

        $subtotal = $this->uninvoicedAmount($invoice->project, $invoice->period_year, $invoice->period_month);
        $vatAmount = round($subtotal * (float) $settings->default_vat_percentage / 100, 2);
        $sequenceNumber = $this->invoiceNumbers->nextSequenceNumber($invoice->client_id, $validated['invoice_date'], $invoice->id);

        $invoice->update([
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

    /**
     * Overwrite the PDF of every invoice that has one with the current layout and settings.
     */
    public function regeneratePdfs(): RedirectResponse
    {
        $invoices = Invoice::query()->whereNotNull('pdf_path')->get();

        foreach ($invoices as $invoice) {
            $invoice->update(['pdf_path' => $this->invoicePdfs->generate($invoice)]);
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
     * Freeze the amounts, lock the period's time entries and generate the PDF.
     */
    private function finalizeDraft(Invoice $invoice): void
    {
        $invoice->loadMissing(['client', 'project']);
        $settings = Setting::current();

        $entries = $invoice->project->timeEntries()
            ->whereYear('date', $invoice->period_year)
            ->whereMonth('date', $invoice->period_month)
            ->whereNull('invoice_id')
            ->get();

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

        $pdfPath = $this->invoicePdfs->generate($invoice);
        $invoice->update(['pdf_path' => $pdfPath]);
    }

    /**
     * Undo a finalization: free the locked time entries and remove the PDF, which a draft must not have.
     */
    private function releaseFinalizedInvoice(Invoice $invoice): void
    {
        TimeEntry::query()
            ->where('invoice_id', $invoice->id)
            ->update(['invoice_id' => null]);

        if ($invoice->pdf_path) {
            Storage::disk('invoices')->delete($invoice->pdf_path);
            $invoice->update(['pdf_path' => null]);
        }
    }

    private function uninvoicedAmount(Project $project, int $year, int $month): float
    {
        return $project->timeEntries()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNull('invoice_id')
            ->get()
            ->sum(fn (TimeEntry $entry) => $entry->amount());
    }

    /**
     * @return array<int, array{year: int, month: int, label: string}>
     */
    private function monthOptions(): array
    {
        return collect(range(0, 11))
            ->map(function (int $offset) {
                $date = now()->subMonths($offset);

                return [
                    'year' => $date->year,
                    'month' => $date->month,
                    'label' => $date->translatedFormat('F Y'),
                ];
            })
            ->all();
    }
}
