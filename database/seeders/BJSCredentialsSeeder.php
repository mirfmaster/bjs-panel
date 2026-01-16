<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class BJSCredentialsSeeder extends Seeder
{
    public function run(): void
    {
        Cache::put('bjs.credentials.username', config('bjs.username'));
        Cache::put('bjs.credentials.password', config('bjs.password'));
        Cache::put('bjs.session.login_toggle', true);
        Cache::put('bjs.session.failed_attempts', 0);
    }
}
