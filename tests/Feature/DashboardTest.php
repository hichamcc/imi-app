<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create([
            'api_key' => 'test-api-key',
            'api_operator_id' => 'test-operator-id',
            'api_base_url' => 'https://api.postingdeclaration.eu',
            'is_active' => true
        ]);

        $this->actingAs($user);

        $this->get('/dashboard')->assertStatus(200);
    }

    public function test_authenticated_users_without_api_credentials_are_redirected_to_contact_admin(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertRedirect('/contact-admin');
    }
}
