<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\BJS;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BJSSyncProgress extends Command
{
    protected $signature = 'bjs:sync-progress {--force : Force sync even for recently synced orders}';

    protected $description = 'Sync order status from BJS API for non-inprogress orders';

    public function handle(BJS $bjs): int
    {
        $this->info('Starting progress sync...');

        $force = $this->option('force');
        $syncedAtThreshold = $force ? null : now()->subMinutes(2);

        $orders = Order::where('status', '!=', OrderStatus::INPROGRESS)
            ->when($syncedAtThreshold, function ($query) use ($syncedAtThreshold) {
                return $query->where(function ($q) use ($syncedAtThreshold) {
                    $q->whereNull('last_synced_at')
                      ->orWhere('last_synced_at', '<', $syncedAtThreshold);
                });
            })
            ->orderBy('service_id')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders to sync.');

            return Command::SUCCESS;
        }

        $this->info('Found ' . $orders->count() . ' orders to sync');

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;
        $statusChangeCount = 0;

        $ordersByService = $orders->groupBy('service_id');

        foreach ($ordersByService as $serviceId => $serviceOrders) {
            $this->line("Syncing " . count($serviceOrders) . " orders for service {$serviceId}...");

            foreach ($serviceOrders as $order) {
                try {
                    $result = DB::transaction(function () use ($bjs, $order) {
                        $bjsStatus = null;

                        if ($order->bjs_id) {
                            $bjsOrder = $bjs->getOrderByBjsId($order->bjs_id);
                            if ($bjsOrder) {
                                $bjsStatus = $bjsOrder->status ?? null;
                            }
                        }

                        if (!$bjsStatus && $order->id) {
                            $bjsStatus = $bjs->getOrderStatusByLocalId($order->id);
                        }

                        if ($bjsStatus === null) {
                            return ['action' => 'skipped', 'reason' => 'not_found'];
                        }

                        $order->status_bjs = $bjsStatus;
                        $order->last_synced_at = now();

                        if ($order->status->value !== $bjsStatus->value) {
                            $order->status = $bjsStatus;

                            if ($bjsStatus === OrderStatus::COMPLETED) {
                                $order->remains = 0;
                            }
                        }

                        $order->save();

                        return ['action' => 'synced', 'bjs_status' => $bjsStatus->label()];
                    });

                    if ($result['action'] === 'skipped') {
                        $skippedCount++;
                        $this->line("  Order #{$order->id}: Skipped - not found on BJS");
                    } else {
                        $successCount++;
                        if ($order->status->value !== $bjsStatus?->value) {
                            $statusChangeCount++;
                        }
                        $this->line("  Order #{$order->id}: Synced (BJS status: {$result['bjs_status']})");
                    }

                } catch (\Throwable $e) {
                    $failCount++;
                    $this->error("  Order #{$order->id}: Failed - {$e->getMessage()}");
                    Log::error("BJS Sync Progress failed for order #{$order->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        $this->newLine();
        $this->info('Sync completed.');
        $this->info("  Success: {$successCount}");
        $this->info("  Skipped: {$skippedCount}");
        $this->info("  Failed: {$failCount}");
        $this->info("  Status changes: {$statusChangeCount}");

        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
