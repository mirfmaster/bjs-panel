<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BJSException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Cache\CacheManager;
use Psr\Http\Message\ResponseInterface;

class BJS
{
    public const AUTH_STATE_VALID = 'valid';

    public const AUTH_STATE_REAUTHENTICATED = 'reauthenticated';

    public const AUTH_STATE_FAILED = 'failed';

    public const AUTH_STATE_DISABLED = 'disabled';

    private CacheManager $cache;

    private Client $client;

    private Client $clientXML;

    private bool $loginState = false;

    private bool $isValidSession = false;

    private string $cookiePath;

    private string $servicesKey;

    private string $baseUri;

    private string $usernameKey;

    private string $passwordKey;

    private string $loginToggleKey;

    private int $maxRetries;

    private int $retryDelayMs;

    private string $lastAuthState = self::AUTH_STATE_DISABLED;

    public function getLastAuthState(): string
    {
        return $this->lastAuthState;
    }

    public function __construct(
        ?CacheManager $cache = null,
        ?Client $client = null,
        ?Client $clientXML = null,
        ?string $cookiePath = null,
        ?string $baseUri = null,
        ?array $config = null
    ) {
        $this->cache = $cache ?? app(CacheManager::class);
        $this->cookiePath = $cookiePath ?? $this->getDefaultCookiePath();
        $this->baseUri = $baseUri ?? config('bjs.base_uri', 'https://belanjasosmed.com');
        $this->maxRetries = $config['max_retries'] ?? config('bjs.max_retries', 3);
        $this->retryDelayMs = $config['retry_delay_ms'] ?? config('bjs.retry_delay_ms', 5000);

        $keys = $config['cache_keys'] ?? config('bjs.cache_keys', [
            'credentials' => [
                'username' => 'bjs.credentials.username',
                'password' => 'bjs.credentials.password',
            ],
            'session' => [
                'login_toggle' => 'bjs.session.login_toggle',
            ],
            'services' => 'bjs.services',
        ]);

        $this->usernameKey = $keys['credentials']['username'];
        $this->passwordKey = $keys['credentials']['password'];
        $this->loginToggleKey = $keys['session']['login_toggle'];
        $this->servicesKey = $keys['services'];

        $this->initializeHttpClients($client, $clientXML);
        $this->checkLoginState();
    }

    private function getDefaultCookiePath(): string
    {
        if (function_exists('storage_path')) {
            return storage_path('app/bjs-cookies.json');
        }

        return '/tmp/bjs-cookies.json';
    }

    private function initializeHttpClients(?Client $client, ?Client $clientXML): void
    {
        $cookie = new FileCookieJar($this->cookiePath, true);

        $this->client = $client ?? new Client([
            'cookies' => $cookie,
            'base_uri' => $this->baseUri,
        ]);

        $this->clientXML = $clientXML ?? new Client([
            'cookies' => $cookie,
            'base_uri' => $this->baseUri,
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => $this->baseUri.'/admin/orders?status=0&service=11',
                'Origin' => $this->baseUri,
            ],
        ]);
    }

    private function checkLoginState(): void
    {
        $isDev = config('app.debug', false);

        if ($isDev) {
            $hasCredentials = ! empty($this->cache->get($this->usernameKey))
                && ! empty($this->cache->get($this->passwordKey));
            $this->loginState = $hasCredentials;

            if ($this->loginState) {
                $this->validateAndRepairSession();
            }

            return;
        }

        $this->loginState = $this->cache->get($this->loginToggleKey, false) === true;

        if (! $this->loginState) {
            $this->lastAuthState = self::AUTH_STATE_DISABLED;

            return;
        }

        $this->validateAndRepairSession();
    }

    private function validateAndRepairSession(): void
    {
        $this->isValidSession = $this->isValidSession();

        if ($this->isValidSession) {
            $this->lastAuthState = self::AUTH_STATE_VALID;
        } else {
            $this->reauthenticate();
        }
    }

    private function retryWithBackoff(callable $operation): void
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $operation();

                return;
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelayMs * 1000);
                }
            }
        }

        throw $lastException;
    }

    private function reauthenticate(): void
    {
        $this->retryWithBackoff(function () {
            $username = $this->cache->get($this->usernameKey);
            $password = $this->cache->get($this->passwordKey);

            if (empty($username) || empty($password)) {
                $this->lastAuthState = self::AUTH_STATE_FAILED;
                throw BJSException::authFailed('BJS credentials not found in cache');
            }

            $response = $this->client->get('/admin');
            $html = (string) $response->getBody();
            $csrfToken = $this->getCsrfToken($html);

            if (! $csrfToken) {
                $this->lastAuthState = self::AUTH_STATE_FAILED;
                throw BJSException::authFailed('CSRF token not found');
            }

            $this->client->post('/admin', [
                'form_params' => [
                    '_csrf_admin' => $csrfToken,
                    'SignInForm[login]' => $username,
                    'SignInForm[password]' => $password,
                    'SignInForm[remember]' => 1,
                ],
            ]);

            $this->isValidSession = $this->isValidSession();

            if (! $this->isValidSession) {
                $this->lastAuthState = self::AUTH_STATE_FAILED;
                throw BJSException::sessionInvalid('Re-authentication failed');
            }

            $this->lastAuthState = self::AUTH_STATE_REAUTHENTICATED;
        });
    }

    private function getCsrfToken(string $html): ?string
    {
        $pattern = '/<meta name="csrf-token" content="(.*?)">/';
        preg_match($pattern, $html, $matches);

        return $matches[1] ?? null;
    }

    private function isValidSession(): bool
    {
        try {
            $testSession = $this->client->get('/admin/account');
            $body = (string) $testSession->getBody();

            return ! str_contains($body, 'SignInForm') && str_contains($body, 'current_password');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isAuthenticated(): bool
    {
        return $this->loginState && $this->isValidSession;
    }

    public function getOrders(int $serviceId, int $status, int $pageSize = 100): ?ResponseInterface
    {
        if (! $this->loginState) {
            return null;
        }

        try {
            return $this->clientXML->get("/admin/api/orders/list?status=$status&service=$serviceId&page_size=$pageSize");
        } catch (\Throwable $e) {
            $this->handleRequestError($e);

            return null;
        }
    }

    public function getOrdersData(int $serviceId, int $status, int $pageSize = 100): array
    {
        if (! $this->loginState) {
            return [];
        }

        try {
            $request = $this->clientXML->get("/admin/api/orders/list?status=$status&service=$serviceId&page_size=$pageSize");
            $data = json_decode($request->getBody(), false);

            return $data->data->orders ?? [];
        } catch (\Throwable $e) {
            $this->handleRequestError($e);

            return [];
        }
    }

    public function setStartCount(int $id, int $start): bool
    {
        if (! $this->loginState) {
            return false;
        }

        try {
            $this->clientXML->post('/admin/api/orders/set-start-count/'.$id, [
                'form_params' => [
                    'start_count' => $start,
                ],
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->handleRequestError($e);

            return false;
        }
    }

    public function setPartial(int $id, int $remains): bool
    {
        if (! $this->loginState) {
            return false;
        }

        try {
            $this->clientXML->post('/admin/api/orders/set-partial/'.$id, [
                'form_params' => [
                    'remains' => $remains,
                ],
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->handleRequestError($e);

            return false;
        }
    }

    public function cancelOrder(int $id): bool
    {
        if (! $this->loginState) {
            return false;
        }

        try {
            $this->clientXML->post('/admin/api/orders/cancel/'.$id);

            return true;
        } catch (\Throwable $e) {
            $this->handleRequestError($e);

            return false;
        }
    }

    public function changeStatus(int $id, OrderStatus|int $status): bool
    {
        if (! $this->loginState) {
            return false;
        }

        $statusValue = $status instanceof OrderStatus ? $status->value : $status;

        try {
            $this->clientXML->post('/admin/api/orders/change-status/'.$id, [
                'form_params' => [
                    'status' => $statusValue,
                ],
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->handleRequestError($e);

            return false;
        }
    }

    private function handleRequestError(\Throwable $e): void
    {
        logger()->error('BJS Request Error: '.$e->getMessage());
    }

    public function getData(ResponseInterface $request): object
    {
        return json_decode($request->getBody(), false);
    }

    public function getIGUsername(string $input): string
    {
        $input = str_replace('@', '', $input);

        if (! filter_var($input, FILTER_VALIDATE_URL)) {
            return $input;
        }

        $path = parse_url($input, PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));

        return $pathParts[0];
    }

    public function getServices(): array
    {
        return $this->cache->get($this->servicesKey, []);
    }

    public function getServiceId(int $index): ?int
    {
        $services = $this->getServices();

        return $services[$index] ?? null;
    }
}
