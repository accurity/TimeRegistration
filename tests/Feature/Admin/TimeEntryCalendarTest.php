<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function dayUrl(Project $project, string $date): string
    {
        return "/admin/projects/{$project->id}/time-entries/days/{$date}";
    }

    public function test_the_calendar_is_the_default_view_and_shows_every_day_of_the_month(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'date' => '2026-09-09',
            'hours' => '4.00',
            'description' => 'Contentmigratie blog',
        ]);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries?year=2026&month=9");

        $response->assertOk();
        $response->assertSee('data-day="2026-09-01"', false);
        $response->assertSee('data-day="2026-09-30"', false);
        $response->assertDontSee('data-day="2026-10-01"', false);
        $response->assertSee('Contentmigratie blog');
        $response->assertSee('Week 37');
    }

    public function test_the_list_view_is_available_through_the_toggle(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-09', 'description' => 'Contentmigratie blog']);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries?year=2026&month=9&view=list");

        $response->assertOk();
        $response->assertSee('Toevoegen');
        $response->assertDontSee('data-day="2026-09-01"', false);
        $response->assertSee('Contentmigratie blog');
    }

    public function test_a_day_with_several_entries_is_read_only_and_links_to_the_list(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->count(2)->create(['project_id' => $project->id, 'date' => '2026-09-09', 'hours' => '2.00']);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries?year=2026&month=9");

        $response->assertSee('2 regels');
        $response->assertSee(route('admin.projects.time-entries.index', [$project, 'year' => 2026, 'month' => 9, 'view' => 'list']));
    }

    public function test_saving_a_day_without_entries_creates_one_with_the_project_rate(): void
    {
        $project = Project::factory()->create(['rate' => '90.00']);

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '4',
            'description' => 'Contentmigratie blog',
        ]);

        $response->assertOk();
        $response->assertJson([
            'entry' => ['hours' => 4, 'description' => 'Contentmigratie blog'],
            'weekHours' => 4,
            'monthHours' => 4,
            'monthAmount' => 360,
            'approvalStatus' => 'draft',
        ]);
        $this->assertDatabaseHas('time_entries', [
            'project_id' => $project->id,
            'hours' => '4.00',
            'rate' => '90.00',
        ]);
        $this->assertSame('2026-09-09', TimeEntry::sole()->date->toDateString());
    }

    public function test_hours_may_be_entered_with_a_decimal_comma(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '1,5',
            'description' => 'Overleg',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('time_entries', ['project_id' => $project->id, 'hours' => '1.50']);
    }

    public function test_saving_a_day_with_one_entry_updates_that_entry(): void
    {
        $project = Project::factory()->create();
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-09', 'hours' => '2.00', 'rate' => '80.00']);

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '3.25',
            'description' => 'Aangepaste omschrijving',
        ]);

        $response->assertOk();
        $response->assertJsonPath('entry.id', $entry->id);
        $this->assertDatabaseCount('time_entries', 1);
        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'hours' => '3.25',
            'description' => 'Aangepaste omschrijving',
            'rate' => '80.00',
        ]);
    }

    public function test_clearing_the_hours_deletes_the_entry_of_that_day(): void
    {
        $project = Project::factory()->create();
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-09']);

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '',
            'description' => $entry->description,
        ]);

        $response->assertOk();
        $response->assertJsonPath('entry', null);
        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_saving_an_empty_day_without_hours_changes_nothing(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '',
            'description' => '',
        ]);

        $response->assertOk();
        $response->assertJsonPath('entry', null);
        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_a_description_is_required_when_hours_are_entered(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '2',
            'description' => '',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('description');
        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_hours_above_twenty_four_are_rejected(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '25',
            'description' => 'Te veel',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('hours');
    }

    public function test_a_day_with_several_entries_cannot_be_saved_from_the_calendar(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->count(2)->create(['project_id' => $project->id, 'date' => '2026-09-09', 'hours' => '2.00']);

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '1',
            'description' => 'Overschrijven',
        ]);

        $response->assertConflict();
        $this->assertDatabaseMissing('time_entries', ['description' => 'Overschrijven']);
        $this->assertDatabaseCount('time_entries', 2);
    }

    public function test_an_invoiced_day_cannot_be_changed(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->create(['project_id' => $project->id]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-09', 'invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '',
            'description' => '',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }

    public function test_the_week_total_only_counts_days_of_the_same_week_and_month(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-08-31', 'hours' => '5.00']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-02', 'hours' => '1.00']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-08', 'hours' => '7.00']);

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-01'), [
            'hours' => '2',
            'description' => 'Nieuw',
        ]);

        $response->assertJsonPath('weekHours', 3);
        $response->assertJsonPath('monthHours', 10);
    }

    public function test_changing_hours_in_an_approved_month_reports_the_revoked_approval(): void
    {
        $project = Project::factory()->create();
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'approved']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-09']);

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '6',
            'description' => 'Gewijzigd na goedkeuring',
        ]);

        $response->assertJsonPath('approvalStatus', 'pending');
    }

    public function test_an_invalid_date_is_not_found(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin())->putJson($this->dayUrl($project, '2026-02-30'), [
            'hours' => '1',
            'description' => 'Bestaat niet',
        ]);

        $response->assertNotFound();
    }

    public function test_client_role_cannot_save_a_day(): void
    {
        $project = Project::factory()->create();
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->putJson($this->dayUrl($project, '2026-09-09'), [
            'hours' => '1',
            'description' => 'Poging',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('time_entries', 0);
    }
}
