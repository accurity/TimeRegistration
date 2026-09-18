<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClientUserTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_the_contact_persons_of_a_client(): void
    {
        $client = Client::factory()->create();
        User::factory()->create(['role' => 'client', 'client_id' => $client->id, 'name' => 'J. Vermeer']);

        $response = $this->actingAs($this->admin())->get("/admin/clients/{$client->id}/users");

        $response->assertOk();
        $response->assertSee('J. Vermeer');
    }

    public function test_client_role_cannot_manage_contact_persons(): void
    {
        $client = Client::factory()->create();
        $clientUser = User::factory()->create(['role' => 'client', 'client_id' => $client->id]);

        $response = $this->actingAs($clientUser)->get("/admin/clients/{$client->id}/users");

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_contact_person_and_a_reset_link_is_sent(): void
    {
        Notification::fake();

        $client = Client::factory()->create();

        $response = $this->actingAs($this->admin())->post("/admin/clients/{$client->id}/users", [
            'name' => 'J. Vermeer',
            'email' => 'j.vermeer@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.clients.users.index', $client));

        $this->assertDatabaseHas('users', [
            'name' => 'J. Vermeer',
            'email' => 'j.vermeer@example.com',
            'role' => 'client',
            'client_id' => $client->id,
        ]);

        $user = User::where('email', 'j.vermeer@example.com')->firstOrFail();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_the_email_must_be_unique(): void
    {
        $client = Client::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->admin())->post("/admin/clients/{$client->id}/users", [
            'name' => 'J. Vermeer',
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_delete_a_contact_person(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create(['role' => 'client', 'client_id' => $client->id]);

        $response = $this->actingAs($this->admin())->delete("/admin/users/{$user->id}");

        $response->assertRedirect(route('admin.clients.users.index', $client));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_users_cannot_be_deleted_through_this_endpoint(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($this->admin())->delete("/admin/users/{$otherAdmin->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_a_newly_created_contact_person_cannot_log_in_before_setting_a_password(): void
    {
        Notification::fake();

        $client = Client::factory()->create();

        $this->actingAs($this->admin())->post("/admin/clients/{$client->id}/users", [
            'name' => 'J. Vermeer',
            'email' => 'j.vermeer@example.com',
        ]);

        $this->app['auth']->forgetGuards();

        $response = $this->post('/login', [
            'email' => 'j.vermeer@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
}
