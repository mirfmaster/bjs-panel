<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class BJSServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            195, // roblox followers
            190, // thread followers
            91,  // instagram comment indonesia
            163, // ulasan google map random
            187, // ulasan google map no review
            191, // thread likes
            192, // thread repost
            188, // ig follow
            189, // ig likes
        ];

        Cache::put(config('bjs.cache_keys.services'), $services);
    }
}
