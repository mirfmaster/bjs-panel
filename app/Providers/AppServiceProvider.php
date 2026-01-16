<?php

namespace App\Providers;

use App\Services\BJS;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BJS::class, function ($app) {
            return new BJS(
                $app->make(\Illuminate\Cache\CacheManager::class),
                config('bjs'),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
