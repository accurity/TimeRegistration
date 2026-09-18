<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $clientId = $request->user()->client_id;
        $year = now()->year;
        $month = now()->month;

        $projectStatuses = Project::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'project' => $project,
                'year' => $year,
                'month' => $month,
                'approval' => $project->monthlyApprovals()->where('year', $year)->where('month', $month)->first(),
            ]);

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
