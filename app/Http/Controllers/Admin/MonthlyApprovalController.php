<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\HoursReadyForApprovalMail;
use App\Models\MonthlyApproval;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MonthlyApprovalController extends Controller
{
    public function submit(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $approval = MonthlyApproval::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'status' => 'pending',
                'submitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ]
        );

        $recipients = $project->client->users()->where('role', 'client')->get();

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new HoursReadyForApprovalMail($project, $approval));
        }

        $status = $recipients->isEmpty()
            ? 'Uren ingediend, maar deze klant heeft nog geen contactpersonen om te informeren.'
            : 'Uren ingediend ter goedkeuring.';

        return redirect()
            ->route('admin.projects.time-entries.index', [$project, 'year' => $validated['year'], 'month' => $validated['month']])
            ->with('status', $status);
    }
}
