<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MonthlyApproval;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $clientId = $request->user()->client_id;

        $projects = Project::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->orderBy('name')
            ->with('monthlyApprovals')
            ->get();

        $submittedPeriods = $projects
            ->flatMap(fn (Project $project) => $project->monthlyApprovals->map(fn (MonthlyApproval $approval) => [
                'project' => $project,
                'year' => $approval->year,
                'month' => $approval->month,
                'approval' => $approval,
            ]))
            ->sortBy([
                fn (array $a, array $b) => $b['approval']->isPending() <=> $a['approval']->isPending(),
                fn (array $a, array $b) => [$b['year'], $b['month']] <=> [$a['year'], $a['month']],
            ]);

        $projectsWithoutSubmissions = $projects
            ->filter(fn (Project $project) => $project->monthlyApprovals->isEmpty())
            ->map(fn (Project $project) => [
                'project' => $project,
                'year' => null,
                'month' => null,
                'approval' => null,
            ]);

        $projectStatuses = $submittedPeriods->concat($projectsWithoutSubmissions)->values();

        $openInvoices = Invoice::query()
            ->where('client_id', $clientId)
            ->where('status', 'final')
            ->where('payment_status', 'open')
            ->with('project')
            ->orderBy('due_date')
            ->get();

        return view('portal.dashboard', [
            'projectStatuses' => $projectStatuses,
            'openInvoices' => $openInvoices,
        ]);
    }
}
