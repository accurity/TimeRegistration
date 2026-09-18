<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function clientUserFor(Client $client): User
    {
        return User::factory()->create(['role' => 'client', 'client_id' => $client->id]);
    }

    public function test_a_client_user_only_sees_invoices_for_their_own_client(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $otherProject = Project::factory()->create(['client_id' => $otherClient->id]);
        Invoice::factory()->final()->create(['client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => 'OWN-1']);
        Invoice::factory()->final()->create(['client_id' => $otherClient->id, 'project_id' => $otherProject->id, 'invoice_number' => 'OTHER-1']);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal/invoices');

        $response->assertOk();
        $response->assertSee('OWN-1');
        $response->assertDontSee('OTHER-1');
    }

    public function test_draft_invoices_are_never_shown_in_the_portal(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        Invoice::factory()->create(['client_id' => $client->id, 'project_id' => $project->id, 'status' => 'draft', 'invoice_number' => 'DRAFT-1']);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal/invoices');

        $response->assertDontSee('DRAFT-1');
    }

    public function test_cancelled_invoices_are_shown_with_a_clear_label(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        Invoice::factory()->cancelled()->create(['client_id' => $client->id, 'project_id' => $project->id, 'invoice_number' => 'CANCELLED-1']);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal/invoices');

        $response->assertSee('CANCELLED-1');
        $response->assertSee('Geannuleerd');
    }

    public function test_the_list_shows_number_period_amount_and_payment_status(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id, 'name' => 'Website migratie']);
        Invoice::factory()->final()->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'invoice_number' => '1012620-ABC',
            'period_year' => 2026,
            'period_month' => 9,
            'total' => 1234.56,
            'payment_status' => 'open',
        ]);

        $response = $this->actingAs($this->clientUserFor($client))->get('/portal/invoices');

        $response->assertSee('1012620-ABC');
        $response->assertSee('Website migratie');
        $response->assertSee('1.234,56');
        $response->assertSee('Openstaand');
    }

    public function test_a_client_user_can_download_their_own_invoice_pdf(): void
    {
        Storage::fake('invoices');
        Storage::disk('invoices')->put('2026/OWN-1.pdf', '%PDF-1.4 fake content');
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $invoice = Invoice::factory()->final()->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'invoice_number' => 'OWN-1',
            'pdf_path' => '2026/OWN-1.pdf',
        ]);

        $response = $this->actingAs($this->clientUserFor($client))->get("/portal/invoices/{$invoice->id}/download");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_a_client_user_cannot_download_another_clients_invoice(): void
    {
        Storage::fake('invoices');
        Storage::disk('invoices')->put('2026/OTHER-1.pdf', '%PDF-1.4 fake content');
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $otherProject = Project::factory()->create(['client_id' => $otherClient->id]);
        $invoice = Invoice::factory()->final()->create([
            'client_id' => $otherClient->id,
            'project_id' => $otherProject->id,
            'invoice_number' => 'OTHER-1',
            'pdf_path' => '2026/OTHER-1.pdf',
        ]);

        $response = $this->actingAs($this->clientUserFor($client))->get("/portal/invoices/{$invoice->id}/download");

        $response->assertNotFound();
    }

    public function test_a_client_user_cannot_download_a_draft_invoice(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->clientUserFor($client))->get("/portal/invoices/{$invoice->id}/download");

        $response->assertNotFound();
    }
}
