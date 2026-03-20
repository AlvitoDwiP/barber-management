<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstOwnerSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_screen_can_be_rendered_when_no_users_exist(): void
    {
        $response = $this->get(route('owner.setup.create'));

        $response->assertOk();
        $response->assertSee('Buat akun owner pertama');
    }

    public function test_first_owner_can_be_created_once_and_is_logged_in(): void
    {
        $response = $this->post(route('owner.setup.store'), [
            'name' => 'Owner Utama',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'name' => 'Owner Utama',
        ]);
    }

    public function test_setup_screen_redirects_to_login_after_owner_exists(): void
    {
        User::factory()->create();

        $response = $this->get(route('owner.setup.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_login_redirects_to_owner_setup_when_no_users_exist(): void
    {
        $response = $this->get(route('login'));

        $response->assertRedirect(route('owner.setup.create'));
    }

    public function test_register_route_is_not_available(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_root_redirects_to_owner_setup_when_no_users_exist(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('owner.setup.create'));
    }

    public function test_root_redirects_to_login_when_owner_exists_and_guest_visits(): void
    {
        User::factory()->create();

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
