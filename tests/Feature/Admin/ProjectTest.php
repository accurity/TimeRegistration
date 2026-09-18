<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_the_projects_list(): void
    {
        Project::factory()->create(['name' => 'Website migratie']);

        $response = $this->actingAs($this->admin())->get('/admin/projects');

        $response->assertOk();
        $response->assertSee('Website migratie');
    }

    public function test_client_role_cannot_access_the_projects_list(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->get('/admin/projects');

        $response->assertForbidden();
    }

    public function test_the_projects_list_hides_archived_projects_by_default(): void
    {
        Project::factory()->create(['name' => 'Actief project', 'status' => 'active']);
        Project::factory()->archived()->create(['name' => 'Oud project']);

        $response = $this->actingAs($this->admin())->get('/admin/projects');

        $response->assertSee('Actief project');
        $response->assertDontSee('Oud project');
    }

    public function test_archived_projects_can_still_be_found_via_the_status_filter(): void
    {
        Project::factory()->archived()->create(['name' => 'Oud project']);

        $response = $this->actingAs($this->admin())->get('/admin/projects?status=archived');

        $response->assertSee('Oud project');
    }

    public function test_the_projects_list_can_be_filtered_by_client(): void
    {
        $client = Client::factory()->create();
        Project::factory()->create(['name' => 'Project van klant A', 'client_id' => $client->id]);
        Project::factory()->create(['name' => 'Project van klant B']);

        $response = $this->actingAs($this->admin())->get('/admin/projects?client_id='.$client->id);

        $response->assertSee('Project van klant A');
        $response->assertDontSee('Project van klant B');
    }

    public function test_admin_can_create_a_project(): void
    {
        $client = Client::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/projects', [
            'client_id' => $client->id,
            'name' => 'Website migratie',
            'rate' => '85.00',
            'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.projects.index'));

        $this->assertDatabaseHas('projects', [
            'name' => 'Website migratie',
            'client_id' => $client->id,
            'rate' => '85.00',
        ]);
    }

    public function test_the_rate_must_be_a_positive_amount(): void
    {
        $client = Client::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/projects', [
            'client_id' => $client->id,
            'name' => 'Website migratie',
            'rate' => '0',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('rate');
    }

    public function test_the_client_is_required(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/projects', [
            'name' => 'Website migratie',
            'rate' => '85.00',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('client_id');
    }

    public function test_admin_can_update_a_project(): void
    {
        $project = Project::factory()->create(['name' => 'Website migratie']);

        $response = $this->actingAs($this->admin())->put("/admin/projects/{$project->id}", [
            'client_id' => $project->client_id,
            'name' => 'Website migratie v2',
            'rate' => $project->rate,
            'status' => 'archived',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.projects.index'));

        $project->refresh();
        $this->assertSame('Website migratie v2', $project->name);
        $this->assertSame('archived', $project->status);
    }

    public function test_admin_can_delete_a_project(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->admin())->delete("/admin/projects/{$project->id}");

        $response->assertRedirect(route('admin.projects.index'));
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_a_project_with_time_entries_cannot_be_deleted(): void
    {
        $project = Project::factory()->create();
        TimeEntry::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->admin())->delete("/admin/projects/{$project->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }
}
