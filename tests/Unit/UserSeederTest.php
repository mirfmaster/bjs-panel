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

    public function test_seeder_creates_superadmin_user(): void
    {
        $superadmin = User::where('email', 'superadmin@example.com')->first();

        $this->assertNotNull($superadmin, 'Superadmin user should exist');
        $this->assertEquals('Super Admin', $superadmin->name);
        $this->assertTrue((bool)$superadmin->is_superadmin, 'Superadmin should have is_superadmin flag');
        $this->assertTrue(Hash::check('superadmin', $superadmin->password), 'Superadmin password should be superadmin');
    }

    public function test_seeder_creates_5_admin_users(): void
    {
        $users = User::whereNull('is_superadmin')->orWhere('is_superadmin', false)->get();

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

    public function test_admin_users_can_login_with_secret_password(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = User::where('email', "admin$i@example.com")->first();
            $this->assertNotNull($user);
            $this->assertTrue(
                Hash::check('secret', $user->password),
                "User admin$i should have password 'secret'"
            );
        }
    }

    public function test_superadmin_has_correct_password(): void
    {
        $superadmin = User::where('email', 'superadmin@example.com')->first();
        $this->assertNotNull($superadmin);
        $this->assertTrue(Hash::check('superadmin', $superadmin->password));
    }
}
