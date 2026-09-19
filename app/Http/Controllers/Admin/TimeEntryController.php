<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTimeEntryDayRequest;
use App\Http\Requests\Admin\StoreTimeEntryRequest;
use App\Http\Requests\Admin\UpdateTimeEntryRequest;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
            ->orderBy('date')
            ->get();

        $weeks = $entries->groupBy(fn (TimeEntry $entry) => $entry->date->isoWeek());

        $approval = $project->monthlyApprovals()
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfDay();

        return view('admin.projects.time-entries.index', [
            'project' => $project,
            'projects' => Project::query()->with('client')->orderBy('name')->get(),
            'view' => $request->query('view') === 'list' ? 'list' : 'calendar',
            'weeks' => $weeks,
            'calendarWeeks' => $this->calendarWeeks($periodStart, $entries),
            'totalHours' => $entries->sum('hours'),
            'totalAmount' => $entries->sum(fn (TimeEntry $entry) => $entry->amount()),
            'year' => $year,
            'month' => $month,
            'previousPeriod' => $periodStart->copy()->subMonth(),
            'nextPeriod' => $periodStart->copy()->addMonth(),
            'approval' => $approval,
        ]);
    }

    /**
     * Create, update or clear the single entry of one day from the calendar.
     */
    public function saveDay(SaveTimeEntryDayRequest $request, Project $project, string $date): JsonResponse
    {
        [$year, $month, $dayOfMonth] = array_map('intval', explode('-', $date));
        abort_unless(checkdate($month, $dayOfMonth, $year), 404);

        $day = Carbon::createFromDate($year, $month, $dayOfMonth)->startOfDay();

        $dayEntries = $project->timeEntries()->whereDate('date', $day)->get();

        abort_if($dayEntries->count() > 1, 409, 'Deze dag heeft meerdere regels. Bewerk ze in de lijstweergave.');

        $entry = $dayEntries->first();

        abort_if($entry?->isLocked(), 403, 'Deze uren zijn al gefactureerd en kunnen niet meer worden gewijzigd.');

        if (! $request->hasHours()) {
            $entry?->delete();
            $entry = null;
        } elseif ($entry) {
            $entry->update($request->safe()->only(['hours', 'description']));
        } else {
            $entry = $project->timeEntries()->create([
                ...$request->safe()->only(['hours', 'description']),
                'date' => $day->toDateString(),
                'rate' => $project->rate,
            ]);
        }

        $monthEntries = $project->timeEntries()
            ->whereYear('date', $day->year)
            ->whereMonth('date', $day->month)
            ->get();

        $approval = $project->monthlyApprovals()
            ->where('year', $day->year)
            ->where('month', $day->month)
            ->first();

        return response()->json([
            'entry' => $entry ? [
                'id' => $entry->id,
                'hours' => (float) $entry->hours,
                'description' => $entry->description,
            ] : null,
            'weekHours' => (float) $monthEntries
                ->filter(fn (TimeEntry $monthEntry) => $monthEntry->date->isSameWeek($day))
                ->sum('hours'),
            'monthHours' => (float) $monthEntries->sum('hours'),
            'monthAmount' => (float) $monthEntries->sum(fn (TimeEntry $monthEntry) => $monthEntry->amount()),
            'approvalStatus' => $approval->status ?? 'draft',
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
                'view' => 'list',
            ])
            ->with('status', 'Uren toegevoegd.');
    }

    public function edit(Project $project, TimeEntry $timeEntry): View
    {
        abort_if($timeEntry->isLocked(), 403, 'Deze uren zijn al gefactureerd en kunnen niet meer worden gewijzigd.');

        return view('admin.projects.time-entries.edit', [
            'project' => $project,
            'timeEntry' => $timeEntry,
        ]);
    }

    public function update(UpdateTimeEntryRequest $request, Project $project, TimeEntry $timeEntry): RedirectResponse
    {
        abort_if($timeEntry->isLocked(), 403, 'Deze uren zijn al gefactureerd en kunnen niet meer worden gewijzigd.');

        $timeEntry->update($request->validated());

        return redirect()
            ->route('admin.projects.time-entries.index', [
                $project,
                'year' => $timeEntry->date->year,
                'month' => $timeEntry->date->month,
                'view' => 'list',
            ])
            ->with('status', 'Uren bijgewerkt.');
    }

    public function destroy(Project $project, TimeEntry $timeEntry): RedirectResponse
    {
        abort_if($timeEntry->isLocked(), 403, 'Deze uren zijn al gefactureerd en kunnen niet meer worden gewijzigd.');

        $year = $timeEntry->date->year;
        $month = $timeEntry->date->month;

        $timeEntry->delete();

        return redirect()
            ->route('admin.projects.time-entries.index', [$project, 'year' => $year, 'month' => $month, 'view' => 'list'])
            ->with('status', 'Uren verwijderd.');
    }

    /**
     * Build the Monday-to-Sunday grid for a month, including the padding days of adjacent months.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @return array<int, array{number: int, hours: float, days: array<int, array{date: Carbon, inMonth: bool, isWeekend: bool, entries: Collection<int, TimeEntry>}>}>
     */
    private function calendarWeeks(Carbon $periodStart, Collection $entries): array
    {
        $entriesByDate = $entries->groupBy(fn (TimeEntry $entry) => $entry->date->toDateString());

        $weeks = [];
        $weekStart = $periodStart->copy()->startOfWeek(Carbon::MONDAY);
        $periodEnd = $periodStart->copy()->endOfMonth();

        while ($weekStart->lte($periodEnd)) {
            $days = [];

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $weekStart->copy()->addDays($offset);

                $days[] = [
                    'date' => $date,
                    'inMonth' => $date->isSameMonth($periodStart),
                    'isWeekend' => $date->isWeekend(),
                    'entries' => $entriesByDate->get($date->toDateString(), collect()),
                ];
            }

            $weeks[] = [
                'number' => $weekStart->isoWeek(),
                'hours' => (float) collect($days)->sum(fn (array $day) => $day['entries']->sum('hours')),
                'days' => $days,
            ];

            $weekStart->addWeek();
        }

        return $weeks;
    }
}
