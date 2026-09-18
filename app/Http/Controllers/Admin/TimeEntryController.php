<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTimeEntryRequest;
use App\Http\Requests\Admin\UpdateTimeEntryRequest;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        if ($period = $request->query('period')) {
            [$year, $month] = array_map('intval', explode('-', $period));
        } else {
            $year = (int) $request->query('year', now()->year);
            $month = (int) $request->query('month', now()->month);
        }

        $entries = $project->timeEntries()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderByDesc('date')
            ->get();

        $weeks = $entries->groupBy(fn (TimeEntry $entry) => $entry->date->isoWeek());

        return view('admin.projects.time-entries.index', [
            'project' => $project,
            'weeks' => $weeks,
            'totalHours' => $entries->sum('hours'),
            'totalAmount' => $entries->sum(fn (TimeEntry $entry) => $entry->amount()),
            'year' => $year,
            'month' => $month,
            'monthOptions' => $this->monthOptions(),
        ]);
    }

    public function store(StoreTimeEntryRequest $request, Project $project): RedirectResponse
    {
        $date = $request->validated('date');

        $project->timeEntries()->create([
            ...$request->validated(),
            'rate' => $project->rate,
        ]);

        return redirect()
            ->route('admin.projects.time-entries.index', [
                $project,
                'year' => date('Y', strtotime($date)),
                'month' => date('n', strtotime($date)),
            ])
            ->with('status', 'Uren toegevoegd.');
    }

    public function edit(Project $project, TimeEntry $timeEntry): View
    {
        return view('admin.projects.time-entries.edit', [
            'project' => $project,
            'timeEntry' => $timeEntry,
        ]);
    }

    public function update(UpdateTimeEntryRequest $request, Project $project, TimeEntry $timeEntry): RedirectResponse
    {
        $timeEntry->update($request->validated());

        return redirect()
            ->route('admin.projects.time-entries.index', [
                $project,
                'year' => $timeEntry->date->year,
                'month' => $timeEntry->date->month,
            ])
            ->with('status', 'Uren bijgewerkt.');
    }

    public function destroy(Project $project, TimeEntry $timeEntry): RedirectResponse
    {
        $year = $timeEntry->date->year;
        $month = $timeEntry->date->month;

        $timeEntry->delete();

        return redirect()
            ->route('admin.projects.time-entries.index', [$project, 'year' => $year, 'month' => $month])
            ->with('status', 'Uren verwijderd.');
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
