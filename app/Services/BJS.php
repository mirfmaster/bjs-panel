<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BJSAuthException;
use App\Exceptions\BJSSessionException;
use App\Exceptions\BJSException;
use App\Exceptions\BJSNetworkException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Cache\CacheManager;
use Psr\Http\Message\ResponseInterface;

class BJS
{
    public const AUTH_STATE_VALID = 'valid';

    public const AUTH_STATE_REAUTHENTICATED = 'reauthenticated';

    public const AUTH_STATE_FAILED = 'failed';

    public const AUTH_STATE_DISABLED = 'disabled';

    private const CACHE_KEY_SESSION_VALID = 'bjs.session.valid_until';

    private CacheManager $cache;

    private Client $client;

    private Client $clientXML;

    private string $cookiePath;

    private string $servicesKey;

    private string $baseUri;

    private string $usernameKey;

    private string $passwordKey;

    private string $loginToggleKey;

    private int $maxRetries;

    private int $retryDelayMs;

    private int $sessionCacheTtl;

    private string $lastAuthState = self::AUTH_STATE_DISABLED;

    public function getLastAuthState(): string
    {
        return $this->lastAuthState;
    }

    public function __construct(
        CacheManager $cache,
        array $config
    ) {
        $this->cache = $cache;
        $this->cookiePath = $config['cookie_path'] ?? $this->getDefaultCookiePath();
        $this->baseUri = $config['base_uri'] ?? 'https://belanjasosmed.com';
        $this->maxRetries = $config['max_retries'] ?? 3;
        $this->retryDelayMs = $config['retry_delay_ms'] ?? 5000;
        $this->sessionCacheTtl = $config['session_cache_ttl'] ?? 600;

        $keys = $config['cache_keys'] ?? [
            'credentials' => [
                'username' => 'bjs.credentials.username',
                'password' => 'bjs.credentials.password',
            ],
            'session' => [
                'login_toggle' => 'bjs.session.login_toggle',
            ],
            'services' => 'bjs.services',
        ];

        $this->usernameKey = $keys['credentials']['username'];
        $this->passwordKey = $keys['credentials']['password'];
        $this->loginToggleKey = $keys['session']['login_toggle'];
        $this->servicesKey = $keys['services'];

        $this->initializeHttpClients();
    }

    private function getDefaultCookiePath(): string
    {
        return storage_path('app/bjs-cookies.json');
    }

    private function initializeHttpClients(): void
    {
        $cookie = new FileCookieJar($this->cookiePath, true);

        $this->client = new Client([
            'cookies' => $cookie,
            'base_uri' => $this->baseUri,
        ]);

        $this->clientXML = new Client([
            'cookies' => $cookie,
            'base_uri' => $this->baseUri,
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => $this->baseUri . '/admin/orders?status=0&service=11',
                'Origin' => $this->baseUri,
            ],
        ]);
    }

    private function isLoginEnabled(): bool
    {
        if (config('app.debug', false)) {
            return ! empty($this->cache->get($this->usernameKey))
                && ! empty($this->cache->get($this->passwordKey));
        }

        return $this->cache->get($this->loginToggleKey, false) === true;
    }

    private function ensureAuthenticated(): void
    {
        if (! $this->isLoginEnabled()) {
            $this->lastAuthState = self::AUTH_STATE_DISABLED;
            throw new BJSAuthException('Login is disabled');
        }

        $validUntil = $this->cache->get(self::CACHE_KEY_SESSION_VALID, 0);

        if (time() < $validUntil) {
            return;
        }

        if (! $this->validateSession()) {
            $this->reauthenticate();
        }

        $this->cache->put(self::CACHE_KEY_SESSION_VALID, time() + $this->sessionCacheTtl, $this->sessionCacheTtl);
    }

    private function validateSession(): bool
    {
        try {
            $testSession = $this->client->get('/admin/account');
            $body = (string) $testSession->getBody();

            return ! str_contains($body, 'SignInForm') && str_contains($body, 'current_password');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function reauthenticate(): void
    {
        $username = $this->cache->get($this->usernameKey);
        $password = $this->cache->get($this->passwordKey);

        if (empty($username) || empty($password)) {
            $this->lastAuthState = self::AUTH_STATE_FAILED;
            throw new BJSAuthException('BJS credentials not found in cache');
        }

        $this->retryWithBackoff(function () use ($username, $password) {
            $response = $this->client->get('/admin');
            $html = (string) $response->getBody();
            $csrfToken = $this->getCsrfToken($html);

            if (! $csrfToken) {
                $this->lastAuthState = self::AUTH_STATE_FAILED;
                throw new BJSAuthException('CSRF token not found');
            }

            $this->client->post('/admin', [
                'form_params' => [
                    '_csrf_admin' => $csrfToken,
                    'SignInForm[login]' => $username,
                    'SignInForm[password]' => $password,
                    'SignInForm[remember]' => 1,
                ],
            ]);

            if (! $this->validateSession()) {
                $this->lastAuthState = self::AUTH_STATE_FAILED;
                throw new BJSSessionException('Re-authentication failed');
            }

            $this->lastAuthState = self::AUTH_STATE_REAUTHENTICATED;
        });
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

    private function executeWithAuthRetry(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (BJSNetworkException $e) {
            throw $e;
        } catch (BJSAuthException | BJSSessionException $e) {
            $this->clearSessionCache();
            $this->reauthenticate();

            return $operation();
        } catch (GuzzleException $e) {
            $response = $e->getResponse();

            if ($response && in_array($response->getStatusCode(), [401, 403])) {
                $this->clearSessionCache();
                $this->reauthenticate();

                return $operation();
            }

            throw new BJSNetworkException('Network error: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Throwable $e) {
            throw new BJSException('Request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function clearSessionCache(): void
    {
        $this->cache->forget(self::CACHE_KEY_SESSION_VALID);
    }

    private function getCsrfToken(string $html): ?string
    {
        $pattern = '/<meta name="csrf-token" content="(.*?)">/';
        preg_match($pattern, $html, $matches);

        return $matches[1] ?? null;
    }

    public function isAuthenticated(): bool
    {
        return $this->isLoginEnabled() && $this->validateSession();
    }

    public function getOrders(int $serviceId, int $status, int $pageSize = 100): ResponseInterface
    {
        $this->ensureAuthenticated();

        return $this->executeWithAuthRetry(fn() =>
            $this->clientXML->get("/admin/api/orders/list?status=$status&service=$serviceId&page_size=$pageSize")
        );
    }

    public function getOrdersData(int $serviceId, int $status, int $pageSize = 100): array
    {
        $this->ensureAuthenticated();

        $response = $this->executeWithAuthRetry(fn() =>
            $this->clientXML->get("/admin/api/orders/list?status=$status&service=$serviceId&page_size=$pageSize")
        );

        $data = json_decode($response->getBody(), false);

        return $data->data->orders ?? [];
    }

    public function setStartCount(int $id, int $start): void
    {
        $this->ensureAuthenticated();

        $this->executeWithAuthRetry(fn() =>
            $this->clientXML->post('/admin/api/orders/set-start-count/' . $id, [
                'form_params' => [
                    'start_count' => $start,
                ],
            ])
        );
    }

    public function setPartial(int $id, int $remains): void
    {
        $this->ensureAuthenticated();

        $this->executeWithAuthRetry(fn() =>
            $this->clientXML->post('/admin/api/orders/set-partial/' . $id, [
                'form_params' => [
                    'remains' => $remains,
                ],
            ])
        );
    }

    public function cancelOrder(int $id): void
    {
        $this->ensureAuthenticated();

        $this->executeWithAuthRetry(fn() =>
            $this->clientXML->post('/admin/api/orders/cancel/' . $id)
        );
    }

    public function changeStatus(int $id, OrderStatus|int $status): void
    {
        $this->ensureAuthenticated();

        $statusValue = $status instanceof OrderStatus ? $status->value : $status;

        $this->executeWithAuthRetry(fn() =>
            $this->clientXML->post('/admin/api/orders/change-status/' . $id, [
                'form_params' => [
                    'status' => $statusValue,
                ],
            ])
        );
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
