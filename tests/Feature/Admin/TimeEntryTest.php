<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_time_entries_for_a_project_and_month(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create([
            'project_id' => $project->id,
            'date' => '2026-09-15',
            'description' => 'Redactie productpaginas',
        ]);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries?year=2026&month=9");

        $response->assertOk();
        $response->assertSee('Redactie productpaginas');
    }

    public function test_the_list_only_shows_entries_within_the_selected_month(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-15', 'description' => 'September werk']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-08-15', 'description' => 'Augustus werk']);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries?year=2026&month=9");

        $response->assertSee('September werk');
        $response->assertDontSee('Augustus werk');
    }

    public function test_entries_are_shown_chronologically_regardless_of_the_order_they_were_entered_in(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-16', 'description' => 'Later in de maand']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-11', 'description' => 'Eerder in de maand, later ingevoerd']);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries?year=2026&month=9");

        $response->assertSeeInOrder(['Eerder in de maand, later ingevoerd', 'Later in de maand']);
    }

    public function test_client_role_cannot_access_time_entries(): void
    {
        $project = Project::factory()->create();
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->get("/admin/projects/{$project->id}/time-entries");

        $response->assertForbidden();
    }

    public function test_admin_can_add_time_entry_and_the_rate_is_copied_from_the_project(): void
    {
        $project = Project::factory()->create(['rate' => '95.00']);

        $response = $this->actingAs($this->admin())->post("/admin/projects/{$project->id}/time-entries", [
            'date' => '2026-09-15',
            'hours' => '3.5',
            'description' => 'Contentmigratie blog',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.projects.time-entries.index', [$project, 'year' => 2026, 'month' => 9, 'view' => 'list']));

        $this->assertDatabaseHas('time_entries', [
            'project_id' => $project->id,
            'hours' => '3.50',
            'rate' => '95.00',
            'description' => 'Contentmigratie blog',
        ]);
    }

    public function test_the_rate_on_existing_entries_does_not_change_when_the_project_rate_changes(): void
    {
        $project = Project::factory()->create(['rate' => '95.00']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'rate' => '95.00']);

        $project->update(['rate' => '120.00']);

        $this->assertSame('95.00', $entry->fresh()->rate);
    }

    public function test_updating_a_time_entry_does_not_change_its_rate(): void
    {
        $project = Project::factory()->create(['rate' => '95.00']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'rate' => '95.00', 'date' => '2026-09-15']);

        $project->update(['rate' => '150.00']);

        $response = $this->actingAs($this->admin())->put("/admin/projects/{$project->id}/time-entries/{$entry->id}", [
            'date' => '2026-09-16',
            'hours' => '5',
            'description' => 'Bijgewerkte omschrijving',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('95.00', $entry->fresh()->rate);
        $this->assertSame('Bijgewerkte omschrijving', $entry->fresh()->description);
    }

    public function test_hours_must_be_greater_than_zero(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin())->post("/admin/projects/{$project->id}/time-entries", [
            'date' => '2026-09-15',
            'hours' => '0',
            'description' => 'Iets',
        ]);

        $response->assertSessionHasErrors('hours');
    }

    public function test_description_is_required(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin())->post("/admin/projects/{$project->id}/time-entries", [
            'date' => '2026-09-15',
            'hours' => '2',
            'description' => '',
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function test_the_period_totals_are_correct(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-01', 'hours' => '2.00', 'rate' => '100.00']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-02', 'hours' => '3.00', 'rate' => '100.00']);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries?year=2026&month=9");

        $response->assertSee('5,00');
        $response->assertSee('500,00');
    }

    public function test_a_time_entry_cannot_be_accessed_through_a_different_project(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $entry = TimeEntry::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$otherProject->id}/time-entries/{$entry->id}/edit");

        $response->assertNotFound();
    }

    public function test_admin_can_delete_a_time_entry(): void
    {
        $project = Project::factory()->create();
        $entry = TimeEntry::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->admin())->delete("/admin/projects/{$project->id}/time-entries/{$entry->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_a_locked_time_entry_cannot_be_edited(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->create(['project_id' => $project->id]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'invoice_id' => $invoice->id]);

        $editResponse = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries/{$entry->id}/edit");
        $editResponse->assertForbidden();

        $updateResponse = $this->actingAs($this->admin())->put("/admin/projects/{$project->id}/time-entries/{$entry->id}", [
            'date' => '2026-09-15',
            'hours' => '5',
            'description' => 'Poging tot wijzigen',
        ]);
        $updateResponse->assertForbidden();
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id, 'description' => $entry->description]);
    }

    public function test_a_locked_time_entry_cannot_be_deleted(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->create(['project_id' => $project->id]);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id, 'invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->admin())->delete("/admin/projects/{$project->id}/time-entries/{$entry->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }
}
