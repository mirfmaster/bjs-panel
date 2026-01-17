<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\BJS;
use Illuminate\Console\Command;

class BJSSyncOrders extends Command
{
    protected $signature = 'bjs:sync-orders';

    protected $description = 'Sync orders from BJS API - fetch pending orders only';

    public function handle(BJS $bjs): int
    {
        $services = $bjs->getServices();

        if (empty($services)) {
            $this->warn('No services configured. Skipping sync.');

            return Command::FAILURE;
        }

        $this->info('Starting order sync...');
        $this->info('Fetching services: ' . implode(', ', $services));

        $totalNew = 0;
        $totalSkipped = 0;

        foreach ($services as $serviceId) {
            $this->line("Fetching pending orders for service {$serviceId}...");

            try {
                $orders = $bjs->getOrdersData((int) $serviceId, 0);
                $this->line('Found ' . count($orders) . ' pending orders');

                $newOrders = 0;
                $skippedOrders = 0;

                foreach ($orders as $order) {
                    $existing = Order::find($order->id);

                    if ($existing) {
                        $skippedOrders++;
                        continue;
                    }

                    Order::create([
                        'id' => $order->id,
                        'bjs_id' => (string) $order->id,
                        'user' => $order->user ?? '',
                        'link' => $order->link ?? '',
                        'start_count' => $order->start_count ?? null,
                        'count' => $order->count ?? 0,
                        'service_id' => $serviceId,
                        'status' => $order->status ?? 0,
                        'remains' => $order->remains ?? null,
                        'order_created_at' => $order->created_at ?? $order->date ?? now(),
                        'order_cancel_reason' => $order->order_cancel_reason ?? null,
                        'order_fail_reason' => $order->order_fail_reason ?? null,
                        'charge' => $order->charge ?? null,
                    ]);

                    $newOrders++;
                }

                $this->info("Service {$serviceId}: {$newOrders} new, {$skippedOrders} skipped");

                $totalNew += $newOrders;
                $totalSkipped += $skippedOrders;

            } catch (\Throwable $e) {
                $this->error("Error fetching orders for service {$serviceId}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Sync completed.');
        $this->info("Total new orders: {$totalNew}");
        $this->info("Total skipped orders: {$totalSkipped}");

        return Command::SUCCESS;
    }
}
