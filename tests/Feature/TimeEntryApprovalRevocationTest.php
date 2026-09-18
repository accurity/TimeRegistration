<?php

namespace Tests\Feature;

use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryApprovalRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_time_entry_in_an_approved_month_reverts_the_approval_to_pending(): void
    {
        $project = Project::factory()->create();
        $approver = User::factory()->create(['role' => 'client']);
        $approval = MonthlyApproval::factory()->create([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15']);

        $entry->update(['hours' => '4.00']);

        $approval->refresh();
        $this->assertSame('pending', $approval->status);
        $this->assertNull($approval->approved_by);
        $this->assertNull($approval->approved_at);
        $this->assertNull($approval->rejection_reason);
    }

    public function test_deleting_a_time_entry_in_an_approved_month_reverts_the_approval_to_pending(): void
    {
        $project = Project::factory()->create();
        $approval = MonthlyApproval::factory()->create([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
            'status' => 'approved',
            'approved_by' => User::factory()->create(['role' => 'client'])->id,
            'approved_at' => now(),
        ]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15']);

        $entry->delete();

        $this->assertSame('pending', $approval->fresh()->status);
    }

    public function test_editing_a_time_entry_in_a_pending_month_does_not_change_the_approval_status(): void
    {
        $project = Project::factory()->create();
        $approval = MonthlyApproval::factory()->create([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
            'status' => 'pending',
        ]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15']);

        $entry->update(['hours' => '4.00']);

        $this->assertSame('pending', $approval->fresh()->status);
    }

    public function test_editing_a_time_entry_in_a_rejected_month_does_not_change_the_approval_status(): void
    {
        $project = Project::factory()->create();
        $approval = MonthlyApproval::factory()->create([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
            'status' => 'rejected',
            'rejection_reason' => 'Onduidelijk',
        ]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15']);

        $entry->update(['hours' => '4.00']);

        $approval->refresh();
        $this->assertSame('rejected', $approval->status);
        $this->assertSame('Onduidelijk', $approval->rejection_reason);
    }

    public function test_editing_a_time_entry_without_any_monthly_approval_does_not_error(): void
    {
        $project = Project::factory()->create();
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15']);

        $entry->update(['hours' => '4.00']);

        $this->assertSame('4.00', $entry->fresh()->hours);
        $this->assertDatabaseCount('monthly_approvals', 0);
    }

    public function test_editing_a_time_entry_only_reverts_the_approval_for_its_own_month(): void
    {
        $project = Project::factory()->create();
        $septemberApproval = MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'approved']);
        $augustApproval = MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 8, 'status' => 'approved']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15']);

        $entry->update(['hours' => '4.00']);

        $this->assertSame('pending', $septemberApproval->fresh()->status);
        $this->assertSame('approved', $augustApproval->fresh()->status);
    }

    public function test_editing_a_time_entry_through_the_admin_endpoint_also_reverts_an_approved_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        $approval = MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'approved']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15']);

        $this->actingAs($admin)->put("/admin/projects/{$project->id}/time-entries/{$entry->id}", [
            'date' => '2026-09-15',
            'hours' => '5',
            'description' => 'Bijgewerkt',
        ]);

        $this->assertSame('pending', $approval->fresh()->status);
    }

    public function test_deleting_a_time_entry_through_the_admin_endpoint_also_reverts_an_approved_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        $approval = MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'approved']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15']);

        $this->actingAs($admin)->delete("/admin/projects/{$project->id}/time-entries/{$entry->id}");

        $this->assertSame('pending', $approval->fresh()->status);
    }
}
