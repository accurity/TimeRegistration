<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $invoicedPerFiscalYear = $this->invoicedPerFiscalYear();

        $openInvoices = Invoice::query()
            ->where('status', 'final')
            ->where('payment_status', 'open')
            ->with(['client', 'project'])
            ->orderBy('due_date')
            ->get();

        $pendingApprovals = MonthlyApproval::query()
            ->where('status', 'pending')
            ->whereHas('project.client', fn ($query) => $query->where('requires_approval', true))
            ->with('project.client')
            ->orderBy('submitted_at')
            ->get();

        return view('admin.dashboard', [
            'openInvoices' => $openInvoices,
            'overdueInvoiceCount' => $openInvoices->filter(fn (Invoice $invoice) => $invoice->due_date->isPast())->count(),
            'readyToInvoice' => $this->readyToInvoice(),
            'pendingApprovals' => $pendingApprovals,
            'invoicedPerFiscalYear' => $invoicedPerFiscalYear,
            'selectedFiscalYear' => $invoicedPerFiscalYear->firstWhere('year', $request->integer('year')) ?? $invoicedPerFiscalYear->first(),
        ]);
    }

    /**
     * Invoiced totals of final invoices (open and paid) per calendar year of the invoice date, newest first.
     *
     * @return Collection<int, array{year: int, subtotal: float, total: float, count: int}>
     */
    private function invoicedPerFiscalYear(): Collection
    {
        return Invoice::query()
            ->where('status', 'final')
            ->selectRaw('YEAR(invoice_date) as year, SUM(subtotal) as subtotal, SUM(total) as total, COUNT(*) as invoice_count')
            ->groupBy('year')
            ->orderByDesc('year')
            ->get()
            ->map(fn ($row) => [
                'year' => (int) $row->year,
                'subtotal' => (float) $row->subtotal,
                'total' => (float) $row->total,
                'count' => (int) $row->invoice_count,
            ])
            ->toBase();
    }

    /**
     * @return Collection<int, array{project: Project, year: int, month: int, hours: float, amount: float}>
     */
    private function readyToInvoice(): Collection
    {
        $rows = TimeEntry::query()
            ->whereNull('invoice_id')
            ->selectRaw('project_id, YEAR(date) as year, MONTH(date) as month, SUM(hours) as hours, SUM(hours * rate) as amount')
            ->groupBy('project_id', 'year', 'month')
            ->get();

        $projects = Project::query()
            ->with('client')
            ->whereIn('id', $rows->pluck('project_id')->unique())
            ->get()
            ->keyBy('id');

        return $rows
            ->map(fn ($row) => [
                'project' => $projects->get($row->project_id),
                'year' => (int) $row->year,
                'month' => (int) $row->month,
                'hours' => (float) $row->hours,
                'amount' => (float) $row->amount,
            ])
            ->sortByDesc(fn (array $row) => sprintf('%04d%02d', $row['year'], $row['month']))
            ->values();
    }
}
