<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Services\BJS;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class BJSGetOrders extends Command
{
    protected $signature = 'bjs:get-orders {--service= : Service ID} {--status= : Order status (0-7)}';
    protected $description = 'Fetch and display orders from BJS';

    public function handle(): int
    {
        $serviceId = $this->option('service') ?? $this->ask('Service ID', '162');
        $status = $this->option('status') ?? 0;

        $bjs = new BJS();
        $orders = $bjs->getOrdersData((int) $serviceId, (int) $status);

        $state = $bjs->getLastAuthState();
        $stateMessages = [
            BJS::AUTH_STATE_VALID => '<info>Using existing session</info>',
            BJS::AUTH_STATE_REAUTHENTICATED => '<comment>Session renewed - re-authenticated</comment>',
            BJS::AUTH_STATE_FAILED => '<error>Session authentication failed</error>',
            BJS::AUTH_STATE_DISABLED => '<comment>Session is disabled</comment>',
        ];

        $this->line($stateMessages[$state] ?? 'Unknown state');
        $this->newLine();

        if (empty($orders)) {
            $this->info('No orders found.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($orders as $order) {
            $rows[] = [
                $order->id,
                $this->getIGUsername($order),
                $this->getStatusLabel($order),
                $order->start_count ?? '-',
                $order->remains ?? '-',
                $this->formatDate($order),
            ];
        }

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Username', 'Status', 'Start', 'Remains', 'Date'])
            ->setRows($rows);
        $table->render();

        $statusLabel = OrderStatus::from($status)->label();
        $this->info("Found " . count($rows) . " {$statusLabel} orders for service {$serviceId}");

        return Command::SUCCESS;
    }

    private function getIGUsername($order): string
    {
        $input = $order->link ?? $order->username ?? '';
        $input = str_replace('@', '', $input);

        if (!filter_var($input, FILTER_VALIDATE_URL)) {
            return '@' . $input;
        }

        $path = parse_url($input, PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));

        return '@' . ($pathParts[0] ?? $input);
    }

    private function getStatusLabel($order): string
    {
        $status = $order->status ?? 0;

        return match ((int) $status) {
            0 => '<fg=yellow>pending</>',
            1 => '<fg=blue>inprogress</>',
            2 => '<fg=green>completed</>',
            3 => '<fg=yellow>partial</>',
            4 => '<fg=red>canceled</>',
            5 => '<fg=magenta>processing</>',
            6 => '<fg=red>fail</>',
            7 => '<fg=red>error</>',
            default => 'unknown',
        };
    }

    private function formatDate($order): string
    {
        $date = $order->created_at ?? $order->date ?? now();

        if (is_string($date)) {
            try {
                $date = \Carbon\Carbon::parse($date);
            } catch (\Throwable) {
                return '-';
            }
        }

        return $date->format('M d, H:i');
    }
}
