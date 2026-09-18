<?php

namespace Tests\Feature\Portal;

use App\Mail\HoursApprovalDecisionMail;
use App\Models\Client;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function clientUserFor(Client $client): User
    {
        return User::factory()->create(['role' => 'client', 'client_id' => $client->id]);
    }

    public function test_the_dashboard_only_shows_periods_for_the_logged_in_clients_own_projects(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id, 'name' => 'Eigen project']);
        $otherProject = Project::factory()->create(['client_id' => $otherClient->id, 'name' => 'Andermans project']);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9]);
        MonthlyApproval::factory()->create(['project_id' => $otherProject->id, 'year' => 2026, 'month' => 9]);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal');

        $response->assertSee('Eigen project');
        $response->assertDontSee('Andermans project');
    }

    public function test_the_hours_overview_shows_date_hours_description_and_a_total(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '3.00', 'description' => 'Redactie productpagina']);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-06', 'hours' => '2.00', 'description' => 'Overleg']);

        $response = $this->actingAs($this->clientUserFor($client))->get("/portal/projects/{$project->id}/2026/9");

        $response->assertOk();
        $response->assertSee('Redactie productpagina');
        $response->assertSee('Overleg');
        $response->assertSee('5,00');
    }

    public function test_a_client_user_cannot_view_another_clients_project(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $otherProject = Project::factory()->create(['client_id' => $otherClient->id]);
        MonthlyApproval::factory()->create(['project_id' => $otherProject->id, 'year' => 2026, 'month' => 9]);

        $response = $this->actingAs($this->clientUserFor($client))->get("/portal/projects/{$otherProject->id}/2026/9");

        $response->assertNotFound();
    }

    public function test_a_client_user_can_approve_hours_and_the_admin_is_notified(): void
    {
        Mail::fake();

        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $approval = MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'pending']);
        $admin = User::factory()->create(['role' => 'admin']);
        $contact = $this->clientUserFor($client);

        $response = $this->actingAs($contact)->post("/portal/projects/{$project->id}/2026/9/approve");

        $response->assertSessionHasNoErrors();
        $approval->refresh();
        $this->assertSame('approved', $approval->status);
        $this->assertSame($contact->id, $approval->approved_by);
        $this->assertNotNull($approval->approved_at);

        Mail::assertSent(HoursApprovalDecisionMail::class, fn (HoursApprovalDecisionMail $mail) => $mail->hasTo($admin->email));
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'pending']);

        $response = $this->actingAs($this->clientUserFor($client))->post("/portal/projects/{$project->id}/2026/9/reject", [
            'rejection_reason' => '',
        ]);

        $response->assertSessionHasErrors('rejection_reason');
    }

    public function test_a_client_user_can_reject_hours_with_a_reason_and_the_admin_is_notified(): void
    {
        Mail::fake();

        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $approval = MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'pending']);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($this->clientUserFor($client))->post("/portal/projects/{$project->id}/2026/9/reject", [
            'rejection_reason' => 'Onduidelijke omschrijving',
        ]);

        $response->assertSessionHasNoErrors();
        $approval->refresh();
        $this->assertSame('rejected', $approval->status);
        $this->assertSame('Onduidelijke omschrijving', $approval->rejection_reason);

        Mail::assertSent(HoursApprovalDecisionMail::class, fn (HoursApprovalDecisionMail $mail) => $mail->hasTo($admin->email));
    }

    public function test_any_of_a_second_contact_sees_the_final_status_and_cannot_double_approve(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $firstContact = $this->clientUserFor($client);
        $secondContact = $this->clientUserFor($client);
        $approval = MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'pending']);

        $this->actingAs($firstContact)->post("/portal/projects/{$project->id}/2026/9/approve");

        $response = $this->actingAs($secondContact)->post("/portal/projects/{$project->id}/2026/9/approve");

        $response->assertSessionHas('error');
        $approval->refresh();
        $this->assertSame('approved', $approval->status);
        $this->assertSame($firstContact->id, $approval->approved_by);

        $showResponse = $this->actingAs($secondContact)->get("/portal/projects/{$project->id}/2026/9");
        $showResponse->assertSee('Goedgekeurd');
    }
}
