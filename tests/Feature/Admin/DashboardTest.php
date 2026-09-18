<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\MonthlyApproval;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_the_dashboard_shows_no_errors_and_empty_states_for_a_fresh_account(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('Geen openstaande facturen.');
        $response->assertSee('Niets klaar om te factureren.');
        $response->assertSee('Niets in afwachting.');
    }

    public function test_the_dashboard_lists_open_invoices_and_flags_overdue_ones(): void
    {
        Invoice::factory()->final()->create(['invoice_number' => 'OPEN-1', 'total' => 500, 'due_date' => now()->addDays(5), 'payment_status' => 'open']);
        Invoice::factory()->final()->create(['invoice_number' => 'OVERDUE-1', 'total' => 250, 'due_date' => now()->subDays(3), 'payment_status' => 'open']);
        Invoice::factory()->final()->create(['invoice_number' => 'PAID-1', 'total' => 300, 'payment_status' => 'paid', 'paid_at' => now()]);
        Invoice::factory()->create(['invoice_number' => 'DRAFT-1', 'status' => 'draft']);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('OPEN-1');
        $response->assertSee('OVERDUE-1');
        $response->assertDontSee('PAID-1');
        $response->assertDontSee('DRAFT-1');
        $response->assertSee('1 vervallen');
        $response->assertSee('750,00');
    }

    public function test_the_dashboard_lists_periods_ready_to_invoice_with_a_link_to_the_project(): void
    {
        $project = Project::factory()->create(['name' => 'Website migratie', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '3.00', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-06', 'hours' => '2.00', 'rate' => 100]);
        $invoicedEntry = TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-08-05', 'hours' => '4.00', 'rate' => 100]);
        $invoice = Invoice::factory()->final()->create(['project_id' => $project->id]);
        TimeEntry::query()->whereKey($invoicedEntry->id)->update(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('Website migratie');
        $response->assertSee('500,00');
        $response->assertSee(e(route('admin.projects.time-entries.index', [$project, 'year' => 2026, 'month' => 9])), false);
        $response->assertDontSee(e(route('admin.projects.time-entries.index', [$project, 'year' => 2026, 'month' => 8])), false);
    }

    public function test_the_dashboard_lists_pending_approvals_with_a_link_to_the_project(): void
    {
        $client = Client::factory()->create(['name' => 'Bouwgroep Vermeer']);
        $project = Project::factory()->create(['client_id' => $client->id]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'pending', 'submitted_at' => now()]);
        $approvedProject = Project::factory()->create();
        MonthlyApproval::factory()->create(['project_id' => $approvedProject->id, 'year' => 2026, 'month' => 9, 'status' => 'approved']);

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('Bouwgroep Vermeer');
        $response->assertSee('1 periode');
    }
}
