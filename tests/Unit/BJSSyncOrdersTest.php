<?php

namespace Tests\Unit;

use App\Console\Commands\BJSSyncOrders;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BJSSyncOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_new_orders(): void
    {
        $mockBjs = Mockery::mock(\App\Services\BJS::class);
        $mockBjs->shouldReceive('getServices')->andReturn([1]);
        $mockBjs->shouldReceive('getOrdersData')->with(1, 0)->andReturn([
            (object)[
                'id' => 125,
                'username' => 'newuser',
                'link' => 'https://instagram.com/new',
                'count' => 1000,
                'status' => 0,
                'created_at' => now(),
                'start_count' => 500,
                'remains' => 1000,
            ],
        ]);

        $this->app->instance(\App\Services\BJS::class, $mockBjs);

        $this->artisan('bjs:sync-orders');

        $this->assertDatabaseHas('orders', [
            'user' => 'newuser',
            'link' => 'https://instagram.com/new',
            'count' => 1000,
            'service_id' => 1,
            'start_count' => 500,
            'remains' => 1000,
        ]);
    }

    public function test_sync_handles_empty_services(): void
    {
        $mockBjs = Mockery::mock(\App\Services\BJS::class);
        $mockBjs->shouldReceive('getServices')->andReturn([]);

        $this->app->instance(\App\Services\BJS::class, $mockBjs);

        $this->artisan('bjs:sync-orders')
            ->assertExitCode(\Symfony\Component\Console\Command\Command::FAILURE);
    }
}
