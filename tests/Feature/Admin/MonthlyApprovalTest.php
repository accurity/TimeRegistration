<?php

namespace Tests\Feature\Admin;

use App\Mail\HoursReadyForApprovalMail;
use App\Models\Client;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MonthlyApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_submit_hours_for_approval(): void
    {
        Mail::fake();

        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $contact = User::factory()->create(['role' => 'client', 'client_id' => $client->id]);

        $response = $this->actingAs($this->admin())->post("/admin/projects/{$project->id}/monthly-approval", [
            'year' => 2026,
            'month' => 9,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.projects.time-entries.index', [$project, 'year' => 2026, 'month' => 9]));

        $this->assertDatabaseHas('monthly_approvals', [
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
            'status' => 'pending',
        ]);

        Mail::assertSent(HoursReadyForApprovalMail::class, function (HoursReadyForApprovalMail $mail) use ($contact) {
            return $mail->hasTo($contact->email);
        });
    }

    public function test_client_role_cannot_submit_hours_for_approval(): void
    {
        $project = Project::factory()->create();
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->post("/admin/projects/{$project->id}/monthly-approval", [
            'year' => 2026,
            'month' => 9,
        ]);

        $response->assertForbidden();
    }

    public function test_mail_is_only_sent_to_client_users_of_that_project_client(): void
    {
        Mail::fake();

        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $contact = User::factory()->create(['role' => 'client', 'client_id' => $client->id]);
        $otherContact = User::factory()->create(['role' => 'client', 'client_id' => $otherClient->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post("/admin/projects/{$project->id}/monthly-approval", [
            'year' => 2026,
            'month' => 9,
        ]);

        Mail::assertSent(HoursReadyForApprovalMail::class, 1);
        Mail::assertSent(HoursReadyForApprovalMail::class, fn (HoursReadyForApprovalMail $mail) => $mail->hasTo($contact->email));
        Mail::assertNotSent(HoursReadyForApprovalMail::class, fn (HoursReadyForApprovalMail $mail) => $mail->hasTo($otherContact->email));
    }

    public function test_resubmitting_after_rejection_updates_the_existing_row_instead_of_creating_a_duplicate(): void
    {
        Mail::fake();

        $project = Project::factory()->create();
        MonthlyApproval::factory()->create([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
            'status' => 'rejected',
            'rejection_reason' => 'Onduidelijke omschrijving',
        ]);

        $this->actingAs($this->admin())->post("/admin/projects/{$project->id}/monthly-approval", [
            'year' => 2026,
            'month' => 9,
        ]);

        $this->assertSame(1, MonthlyApproval::where('project_id', $project->id)->where('year', 2026)->where('month', 9)->count());

        $approval = MonthlyApproval::where('project_id', $project->id)->first();
        $this->assertSame('pending', $approval->status);
        $this->assertNull($approval->rejection_reason);
    }

    public function test_the_pending_status_is_visible_on_the_time_entries_page(): void
    {
        $project = Project::factory()->create();
        MonthlyApproval::factory()->create([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin())->get("/admin/projects/{$project->id}/time-entries?year=2026&month=9");

        $response->assertSee('Ter beoordeling');
    }
}
