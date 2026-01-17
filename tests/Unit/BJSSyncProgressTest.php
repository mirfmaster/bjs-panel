<?php

namespace Tests\Unit;

use App\Console\Commands\BJSSyncProgress;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BJSSyncProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_progress_command_exists(): void
    {
        $command = new BJSSyncProgress();
        $this->assertEquals('bjs:sync-progress', $command->getName());
    }

    public function test_sync_progress_handles_empty_services(): void
    {
        $this->artisan('bjs:sync-progress')
            ->expectsOutput('No orders to sync.')
            ->assertExitCode(0);
    }

    public function test_sync_progress_handles_no_orders_to_sync(): void
    {
        $this->artisan('bjs:sync-progress')
            ->expectsOutput('No orders to sync.')
            ->assertExitCode(0);
    }
}
