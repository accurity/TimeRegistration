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

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_the_draft_creation_screen_shows_a_correctly_computed_proposal(): void
    {
        $client = Client::factory()->create(['invoice_abbreviation' => 'ABC']);
        $project = Project::factory()->create(['client_id' => $client->id, 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '3.00', 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-06', 'hours' => '2.00', 'rate' => 100]);

        $response = $this->actingAs($this->admin())->get('/admin/invoices/create?'.http_build_query([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertOk();
        $response->assertSee('500,00');
        $response->assertSee('105,00');
        $response->assertSee('605,00');
        $response->assertSee('1012620-ABC');
    }

    public function test_a_non_blocking_warning_is_shown_when_the_month_is_not_approved(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05']);

        $response = $this->actingAs($this->admin())->get('/admin/invoices/create?'.http_build_query([
            'project_id' => $project->id,
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertOk();
        $response->assertSee('nog niet ter goedkeuring aangeboden');
    }

    public function test_admin_can_save_a_draft_invoice_with_the_proposed_values(): void
    {
        $client = Client::factory()->create(['invoice_abbreviation' => 'ABC']);
        $project = Project::factory()->create(['client_id' => $client->id, 'rate' => 100]);
        TimeEntry::factory()->create(['project_id' => $project->id, 'date' => '2026-09-05', 'hours' => '4.00', 'rate' => 100]);
        MonthlyApproval::factory()->create(['project_id' => $project->id, 'year' => 2026, 'month' => 9, 'status' => 'approved']);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_year' => 2026,
            'period_month' => 9,
            'invoice_number' => '1012620-ABC',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'project_id' => $project->id,
            'invoice_number' => '1012620-ABC',
            'sequence_number' => 101,
            'subtotal' => 400.00,
            'vat_amount' => 84.00,
            'total' => 484.00,
            'status' => 'draft',
        ]);
    }

    public function test_the_invoice_number_must_be_unique(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        Invoice::factory()->create(['client_id' => $client->id, 'invoice_number' => 'DUPLICATE-1']);

        $response = $this->actingAs($this->admin())->post('/admin/invoices', [
            'project_id' => $project->id,
            'period_year' => 2026,
            'period_month' => 9,
            'invoice_number' => 'DUPLICATE-1',
            'invoice_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ]);

        $response->assertSessionHasErrors('invoice_number');
        $this->assertSame(1, Invoice::query()->where('invoice_number', 'DUPLICATE-1')->count());
    }

    public function test_admin_can_update_number_date_and_due_date_of_a_draft(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft', 'invoice_number' => 'OLD-1']);

        $response = $this->actingAs($this->admin())->put("/admin/invoices/{$invoice->id}", [
            'invoice_number' => 'NEW-1',
            'invoice_date' => '2026-10-02',
            'due_date' => '2026-10-16',
        ]);

        $response->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertSame('NEW-1', $invoice->invoice_number);
        $this->assertSame('2026-10-02', $invoice->invoice_date->toDateString());
        $this->assertSame('2026-10-16', $invoice->due_date->toDateString());
    }

    public function test_a_draft_invoice_can_be_deleted_without_affecting_time_entries(): void
    {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->create(['project_id' => $project->id, 'status' => 'draft']);
        $entry = TimeEntry::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->admin())->delete("/admin/invoices/{$invoice->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);
    }

    public function test_a_final_invoice_cannot_be_edited_or_deleted(): void
    {
        $invoice = Invoice::factory()->final()->create();

        $editResponse = $this->actingAs($this->admin())->get("/admin/invoices/{$invoice->id}/edit");
        $editResponse->assertNotFound();

        $updateResponse = $this->actingAs($this->admin())->put("/admin/invoices/{$invoice->id}", [
            'invoice_number' => 'CHANGED-1',
            'invoice_date' => '2026-10-02',
            'due_date' => '2026-10-16',
        ]);
        $updateResponse->assertNotFound();

        $deleteResponse = $this->actingAs($this->admin())->delete("/admin/invoices/{$invoice->id}");
        $deleteResponse->assertNotFound();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'final']);
    }
}
