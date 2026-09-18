<?php

namespace App\Observers;

use App\Models\MonthlyApproval;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;

class TimeEntryObserver
{
    public function updating(TimeEntry $timeEntry): void
    {
        $this->revokeApprovalIfApproved($timeEntry, $timeEntry->getOriginal('date'));
    }

    public function deleting(TimeEntry $timeEntry): void
    {
        $this->revokeApprovalIfApproved($timeEntry, $timeEntry->date);
    }

    private function revokeApprovalIfApproved(TimeEntry $timeEntry, string|Carbon $date): void
    {
        $date = Carbon::parse($date);

        MonthlyApproval::query()
            ->where('project_id', $timeEntry->project_id)
            ->where('year', $date->year)
            ->where('month', $date->month)
            ->where('status', 'approved')
            ->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ]);
    }
}
