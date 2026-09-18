<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_the_clients_list(): void
    {
        Client::factory()->create(['name' => 'Bouwgroep Vermeer']);

        $response = $this->actingAs($this->admin())->get('/admin/clients');

        $response->assertOk();
        $response->assertSee('Bouwgroep Vermeer');
    }

    public function test_client_role_cannot_access_the_clients_list(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->get('/admin/clients');

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_client(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/clients', [
            'name' => 'Bouwgroep Vermeer',
            'invoice_abbreviation' => 'VER',
            'address' => 'Havenweg 44',
            'postal_code' => '3421 AB',
            'city' => 'Nieuwegein',
            'kvk_number' => '12345678',
            'btw_number' => 'NL123456789B01',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.clients.index'));

        $this->assertDatabaseHas('clients', [
            'name' => 'Bouwgroep Vermeer',
            'invoice_abbreviation' => 'VER',
        ]);
    }

    public function test_invoice_abbreviation_is_required_and_unique(): void
    {
        Client::factory()->create(['invoice_abbreviation' => 'VER']);

        $response = $this->actingAs($this->admin())->post('/admin/clients', [
            'name' => 'De Waardse Groep',
            'invoice_abbreviation' => 'VER',
            'address' => 'Kerkstraat 1',
            'postal_code' => '1234 AB',
            'city' => 'Utrecht',
        ]);

        $response->assertSessionHasErrors('invoice_abbreviation');
    }

    public function test_admin_can_update_a_client(): void
    {
        $client = Client::factory()->create(['name' => 'Bouwgroep Vermeer']);

        $response = $this->actingAs($this->admin())->put("/admin/clients/{$client->id}", [
            'name' => 'Bouwgroep Vermeer B.V.',
            'invoice_abbreviation' => $client->invoice_abbreviation,
            'address' => $client->address,
            'postal_code' => $client->postal_code,
            'city' => $client->city,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.clients.index'));

        $this->assertSame('Bouwgroep Vermeer B.V.', $client->fresh()->name);
    }

    public function test_a_client_keeps_its_own_abbreviation_unique_check_passing_on_update(): void
    {
        $client = Client::factory()->create(['invoice_abbreviation' => 'VER']);

        $response = $this->actingAs($this->admin())->put("/admin/clients/{$client->id}", [
            'name' => $client->name,
            'invoice_abbreviation' => 'VER',
            'address' => $client->address,
            'postal_code' => $client->postal_code,
            'city' => $client->city,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_admin_can_delete_a_client_without_linked_users(): void
    {
        $client = Client::factory()->create();

        $response = $this->actingAs($this->admin())->delete("/admin/clients/{$client->id}");

        $response->assertRedirect(route('admin.clients.index'));
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_client_with_linked_users_cannot_be_deleted(): void
    {
        $client = Client::factory()->create();
        User::factory()->create(['role' => 'client', 'client_id' => $client->id]);

        $response = $this->actingAs($this->admin())->delete("/admin/clients/{$client->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_client_with_linked_projects_cannot_be_deleted(): void
    {
        $client = Client::factory()->create();
        Project::factory()->create(['client_id' => $client->id]);

        $response = $this->actingAs($this->admin())->delete("/admin/clients/{$client->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }
}
