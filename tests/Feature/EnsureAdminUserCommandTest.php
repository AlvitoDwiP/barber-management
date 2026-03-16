<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnsureAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_default_admin_user_when_missing(): void
    {
        $this->assertDatabaseCount('users', 0);

        $this->artisan('app:ensure-admin-user')
            ->assertSuccessful();

        $user = User::query()->where('email', AdminUserSeeder::DEFAULT_EMAIL)->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check(AdminUserSeeder::DEFAULT_PASSWORD, $user->password));
    }
}
