<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function clientUserFor(Client $client): User
    {
        return User::factory()->create(['role' => 'client', 'client_id' => $client->id]);
    }

    public function test_the_dashboard_shows_no_errors_and_empty_states_for_a_fresh_account(): void
    {
        $client = Client::factory()->create();

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal');

        $response->assertOk();
        $response->assertSee('Er zijn nog geen projecten aan u gekoppeld.');
        $response->assertSee('Geen openstaande facturen.');
    }

    public function test_a_project_without_a_submission_this_month_shows_as_not_yet_submitted(): void
    {
        $client = Client::factory()->create();
        Project::factory()->create(['client_id' => $client->id, 'name' => 'Website migratie']);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal');

        $response->assertOk();
        $response->assertSee('Website migratie');
        $response->assertSee('Nog geen uren ingediend');
    }

    public function test_a_project_with_a_pending_approval_this_month_shows_as_waiting_with_a_link(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'pending', 'submitted_at' => now()]);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal');

        $response->assertOk();
        $response->assertSee('Ter beoordeling');
        $response->assertSee(e(route('portal.approvals.show', [$project, 2026, 9])), false);
    }

    public function test_a_project_with_an_approved_month_shows_as_approved(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'approved']);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal');

        $response->assertOk();
        $response->assertSee('Goedgekeurd');
    }

    public function test_submissions_for_earlier_months_are_listed_with_a_link(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        foreach (range(1, 6) as $month) {
            MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => $month, 'status' => 'pending', 'submitted_at' => now()]);
        }

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal');

        $response->assertOk();
        $response->assertDontSee('Nog geen uren ingediend');

        foreach (range(1, 6) as $month) {
            $response->assertSee(e(route('portal.approvals.show', [$project, 2026, $month])), false);
        }
    }

    public function test_pending_submissions_are_listed_before_decided_ones_newest_first(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 5, 'status' => 'approved']);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 1, 'status' => 'pending', 'submitted_at' => now()]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 3, 'status' => 'pending', 'submitted_at' => now()]);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal');

        $response->assertOk();
        $response->assertSeeInOrder([
            e(route('portal.approvals.show', [$project, 2026, 3])),
            e(route('portal.approvals.show', [$project, 2026, 1])),
            e(route('portal.approvals.show', [$project, 2026, 5])),
        ], false);
    }

    public function test_only_the_clients_own_open_invoices_are_shown(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $otherProject = Project::factory()->create(['client_id' => $otherClient->id]);
        Invoice::factory()->final()->create(['client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => 'OWN-1', 'payment_status' => 'open']);
        Invoice::factory()->final()->create(['client_id' => $otherClient->id, 'project_id' => $otherProject->id, 'invoice_number' => 'OTHER-1', 'payment_status' => 'open']);
        Invoice::factory()->final()->create(['client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => 'PAID-1', 'payment_status' => 'paid', 'paid_at' => now()]);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal');

        $response->assertOk();
        $response->assertSee('OWN-1');
        $response->assertDontSee('OTHER-1');
        $response->assertDontSee('PAID-1');
    }
}
