<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Exceptions\BJSAuthException;
use App\Exceptions\BJSSessionException;
use App\Exceptions\BJSException;
use App\Exceptions\BJSNetworkException;
use App\Services\BJS;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class BJSGetOrders extends Command
{
    protected $signature = 'bjs:get-orders {--service= : Service ID} {--status= : Order status (0-7)}';

    protected $description = 'Fetch and display orders from BJS';

    public function handle(): int
    {
        $serviceId = $this->option('service');

        if ($serviceId === null) {
            $bjs = app(BJS::class);
            $services = $bjs->getServices();

            if (empty($services)) {
                $this->error('No services configured. Please add service IDs in settings.');

                return Command::FAILURE;
            }

            $this->info('Available services:');
            foreach ($services as $index => $id) {
                $this->line(($index + 1) . ". Service ID: $id");
            }

            $selection = $this->ask('Select service number (1-' . count($services) . ')', '1');
            $index = (int) $selection - 1;

            $serviceId = $bjs->getServiceId($index);

            if ($serviceId === null) {
                $this->error('Invalid selection.');

                return Command::FAILURE;
            }
        } else {
            $serviceId = (int) $serviceId;
        }

        $bjs = app(BJS::class);
        $status = $this->option('status') ?? 0;

        try {
            $orders = $bjs->getOrdersData((int) $serviceId, (int) $status);

            $state = $bjs->getLastAuthState();
            $stateMessages = [
                BJS::AUTH_STATE_VALID => '<info>Using existing session</info>',
                BJS::AUTH_STATE_REAUTHENTICATED => '<comment>Session renewed - re-authenticated</comment>',
                BJS::AUTH_STATE_FAILED => '<error>Session authentication failed</error>',
                BJS::AUTH_STATE_DISABLED => '<comment>Login toggle is false - session disabled</comment>',
            ];

            $this->line($stateMessages[$state] ?? 'Unknown state');
            $this->newLine();

            if (empty($orders)) {
                $this->info("No orders for service {$serviceId} found.");

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
            $this->info('Found ' . count($rows) . " {$statusLabel} orders for service {$serviceId}");

            return Command::SUCCESS;

        } catch (BJSSessionException $e) {
            $this->error("Session expired: {$e->getMessage()}");

            return Command::FAILURE;

        } catch (BJSAuthException $e) {
            $this->error("Authentication failed: {$e->getMessage()}");

            return Command::FAILURE;

        } catch (BJSNetworkException $e) {
            $this->error("Network error: {$e->getMessage()}");

            return Command::FAILURE;

        } catch (BJSException $e) {
            $this->error("BJS error: {$e->getMessage()}");

            return Command::FAILURE;

        } catch (\Throwable $e) {
            $this->error("Unexpected error: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    private function getIGUsername($order): string
    {
        $input = $order->link ?? $order->username ?? '';
        $input = str_replace('@', '', $input);

        if (! filter_var($input, FILTER_VALIDATE_URL)) {
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
