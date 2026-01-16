<?php

namespace App\Providers;

use App\Console\Commands\BJSGetOrders;
use Illuminate\Support\ServiceProvider;

class BJSServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BJSGetOrders::class,
            ]);
        }
    }
}
