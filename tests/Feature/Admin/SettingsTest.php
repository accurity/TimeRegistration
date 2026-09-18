<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_settings_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/settings');

        $response->assertOk();
        $response->assertSee('Instellingen');
    }

    public function test_client_cannot_view_the_settings_page(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($client)->get('/admin/settings');

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/settings');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_update_the_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/settings', [
            'company_name' => 'Accurity',
            'address' => 'Havenweg 44',
            'postal_code' => '3421 AB',
            'city' => 'Nieuwegein',
            'kvk_number' => '12345678',
            'btw_number' => 'NL123456789B01',
            'iban' => 'NL91ABNA0417164300',
            'default_payment_term_days' => 30,
            'default_vat_percentage' => 9,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.settings.edit'));

        $setting = Setting::current();
        $this->assertSame('Accurity', $setting->company_name);
        $this->assertSame('Nieuwegein', $setting->city);
        $this->assertSame(30, $setting->default_payment_term_days);
        $this->assertSame('9.00', (string) $setting->default_vat_percentage);
    }

    public function test_settings_require_company_name_and_address(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/settings', [
            'company_name' => '',
            'address' => '',
            'postal_code' => '3421 AB',
            'city' => 'Nieuwegein',
            'default_payment_term_days' => 14,
            'default_vat_percentage' => 21,
        ]);

        $response->assertSessionHasErrors(['company_name', 'address']);
    }

    public function test_vat_percentage_must_be_within_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put('/admin/settings', [
            'company_name' => 'Accurity',
            'address' => 'Havenweg 44',
            'postal_code' => '3421 AB',
            'city' => 'Nieuwegein',
            'default_payment_term_days' => 14,
            'default_vat_percentage' => 150,
        ]);

        $response->assertSessionHasErrors(['default_vat_percentage']);
    }

    public function test_settings_form_shows_current_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::current()->update(['company_name' => 'Accurity']);

        $response = $this->actingAs($admin)->get('/admin/settings');

        $response->assertSee('Accurity');
    }
}
