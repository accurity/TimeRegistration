<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\HoursApprovalDecisionMail;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function show(Request $request, Project $project, int $year, int $month): View
    {
        $this->authorizeProject($request, $project);

        $approval = $project->monthlyApprovals()
            ->where('year', $year)
            ->where('month', $month)
            ->firstOrFail();

        $entries = $project->timeEntries()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        return view('portal.approvals.show', [
            'project' => $project,
            'approval' => $approval,
            'entries' => $entries,
            'totalHours' => $entries->sum('hours'),
            'totalAmount' => $entries->sum(fn (TimeEntry $entry) => $entry->amount()),
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function approve(Request $request, Project $project, int $year, int $month): RedirectResponse
    {
        $this->authorizeProject($request, $project);

        $approval = $project->monthlyApprovals()
            ->where('year', $year)
            ->where('month', $month)
            ->firstOrFail();

        if (! $approval->isPending()) {
            return redirect()
                ->route('portal.approvals.show', [$project, $year, $month])
                ->with('error', 'Deze periode is al beoordeeld.');
        }

        $approval->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $this->notifyAdmins($project, $approval);

        return redirect()
            ->route('portal.approvals.show', [$project, $year, $month])
            ->with('status', 'Uren goedgekeurd.');
    }

    public function reject(Request $request, Project $project, int $year, int $month): RedirectResponse
    {
        $this->authorizeProject($request, $project);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $approval = $project->monthlyApprovals()
            ->where('year', $year)
            ->where('month', $month)
            ->firstOrFail();

        if (! $approval->isPending()) {
            return redirect()
                ->route('portal.approvals.show', [$project, $year, $month])
                ->with('error', 'Deze periode is al beoordeeld.');
        }

        $approval->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $this->notifyAdmins($project, $approval);

        return redirect()
            ->route('portal.approvals.show', [$project, $year, $month])
            ->with('status', 'Uren afgekeurd.');
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        abort_unless($request->user()->client_id === $project->client_id, 404);
    }

    private function notifyAdmins(Project $project, MonthlyApproval $approval): void
    {
        $admins = User::query()->where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::to($admin)->send(new HoursApprovalDecisionMail($project, $approval));
        }
    }
}
