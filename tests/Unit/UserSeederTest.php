<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_seeder_creates_5_admin_users(): void
    {
        $users = User::all();

        $this->assertCount(5, $users);

        for ($i = 1; $i <= 5; $i++) {
            $this->assertTrue(
                $users->contains('name', "admin$i"),
                "User admin$i should exist"
            );
            $this->assertTrue(
                $users->contains('email', "admin$i@example.com"),
                "User admin$i@example.com should exist"
            );
        }
    }

    public function test_all_users_have_password_set(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->assertNotNull($user->password);
            $this->assertNotEmpty($user->password);
        }
    }

    public function test_all_users_can_login_with_secret_password(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->assertTrue(
                Hash::check('secret', $user->password),
                "User {$user->name} should have password 'secret'"
            );
        }
    }
}
