<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('superadmin'),
            'is_superadmin' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "admin$i",
                'email' => "admin$i@example.com",
                'password' => Hash::make('secret'),
            ]);
        }

        $this->call(BJSCredentialsSeeder::class);
    }
}
