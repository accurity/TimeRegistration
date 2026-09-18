<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use App\Models\TimeEntry;
use App\Services\InvoiceNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceNumberService $invoiceNumbers) {}

    public function index(): View
    {
        return view('admin.invoices.index', [
            'invoices' => Invoice::query()
                ->with(['client', 'project'])
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
