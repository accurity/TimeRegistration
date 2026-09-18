<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $openInvoices = Invoice::query()
            ->where('status', 'final')
            ->where('payment_status', 'open')
            ->with(['client', 'project'])
            ->orderBy('due_date')
            ->get();

        $pendingApprovals = MonthlyApproval::query()
            ->where('status', 'pending')
            ->with('project.client')
            ->orderBy('submitted_at')
            ->get();

        return view('admin.dashboard', [
            'openInvoices' => $openInvoices,
            'overdueInvoiceCount' => $openInvoices->filter(fn (Invoice $invoice) => $invoice->due_date->isPast())->count(),
            'readyToInvoice' => $this->readyToInvoice(),
            'pendingApprovals' => $pendingApprovals,
        ]);
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
