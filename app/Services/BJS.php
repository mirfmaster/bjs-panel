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
use GuzzleHttp\Exception\RequestException;
use Illuminate\Cache\CacheManager;
use Psr\Http\Message\ResponseInterface;

class BJS
{
    private CacheManager $cache;

    private Client $client;

    private string $baseUri;

    private string $usernameKey;

    private string $passwordKey;

    private string $servicesKey;

    private string $accessTokenKey;

    private string $cookiePath;

    private FileCookieJar $cookieJar;

    private string $loginToggleKey;

    public function __construct(
        CacheManager $cache,
        array $config
    ) {
        $this->cache = $cache;
        $this->baseUri = $config['base_uri'] ?? 'https://belanjasosmed.com';

        $keys = $config['cache_keys'] ?? [];
        $this->usernameKey = $keys['credentials']['username'] ?? 'bjs.credentials.username';
        $this->passwordKey = $keys['credentials']['password'] ?? 'bjs.credentials.password';
        $this->servicesKey = $keys['services'] ?? 'bjs.services';
        $this->accessTokenKey = $keys['api']['access_token'] ?? 'bjs.api.access_token';
        $this->loginToggleKey = $keys['session']['login_toggle'] ?? 'bjs.session.login_toggle';
        $this->cookiePath = $config['cookie_path'] ?? storage_path('app/bjs-cookies.json');
        $this->cookieJar = new FileCookieJar($this->cookiePath, true);

        $this->initializeHttpClient();
    }

    private function initializeHttpClient(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'cookies' => $this->cookieJar,
            'headers' => [
                'Accept' => 'application/json, text/plain, */*',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'Origin' => $this->baseUri,
                'Referer' => $this->baseUri . '/admin/orders?status=0&service=11',
            ],
        ]);
    }

    private function getCsrfToken(string $html): ?string
    {
        preg_match('/<meta name="csrf-token" content="([^"]+)">/', $html, $matches);
        return $matches[1] ?? null;
    }

    private function isSessionValid(): bool
    {
        try {
            $response = $this->client->get('/admin/account');
            $body = (string) $response->getBody();
            return !str_contains($body, 'SignInForm') && str_contains($body, 'current_password');
        } catch (\Throwable) {
            return false;
        }
    }

    private function performFormLogin(): void
    {
        $loginPage = $this->client->get('/admin');
        $csrfToken = $this->getCsrfToken((string) $loginPage->getBody());

        if (!$csrfToken) {
            throw new BJSAuthException('Failed to extract CSRF token from login page');
        }

        $username = $this->cache->get($this->usernameKey);
        $password = $this->cache->get($this->passwordKey);

        if (empty($username) || empty($password)) {
            throw new BJSAuthException('BJS credentials not found in cache');
        }

        $this->client->post('/admin', [
            'form_params' => [
                '_csrf_admin' => $csrfToken,
                'SignInForm[login]' => $username,
                'SignInForm[password]' => $password,
                'SignInForm[remember]' => 1,
            ],
        ]);

        if (!$this->isSessionValid()) {
            throw new BJSAuthException('Form login failed - session not established');
        }
    }

    private function getAccessToken(): string
    {
        $token = $this->cache->get($this->accessTokenKey);

        if ($token) {
            return $token;
        }

        return $this->refreshAccessToken();
    }

    private function refreshAccessToken(): string
    {
        $username = $this->cache->get($this->usernameKey);
        $password = $this->cache->get($this->passwordKey);

        if (empty($username) || empty($password)) {
            throw new BJSAuthException('BJS credentials not found in cache');
        }

        $response = $this->client->post('/admin/api/auth', [
            'json' => [
                'login' => $username,
                'password' => $password,
                're_captcha' => '',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (! ($data['success'] ?? false)) {
            $errorMessage = $data['error_message'] ?? 'Unknown error';
            throw new BJSAuthException('API auth failed: ' . $errorMessage);
        }

        $this->cache->put($this->accessTokenKey, $data['data']['access_token'], now()->addMinutes(20));

        return $data['data']['access_token'];
    }

    private function ensureAuthenticated(): void
    {
        $loginToggle = $this->cache->get($this->loginToggleKey, false);

        if (!$loginToggle) {
            return;
        }

        if (!$this->isSessionValid()) {
            $this->performFormLogin();
        }
    }

    private function requestApi(string $method, string $uri, array $options = []): ResponseInterface
    {
        $this->ensureAuthenticated();

        $token = $this->getAccessToken();

        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        try {
            return $this->client->$method($uri, $options);
        } catch (GuzzleException $e) {
            if ($e instanceof RequestException && $e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                $this->cache->forget($this->accessTokenKey);
                $this->performFormLogin();
                $token = $this->refreshAccessToken();

                $options['headers']['Authorization'] = 'Bearer ' . $token;

                return $this->client->$method($uri, $options);
            }

            throw new BJSNetworkException('API request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getOrders(int $serviceId, int $status, int $pageSize = 100): ResponseInterface
    {
        return $this->requestApi('get', "/admin/api/orders/list?status=$status&service=$serviceId&page_size=$pageSize");
    }

    public function getOrdersData(int $serviceId, int $status, int $pageSize = 100): array
    {
        $response = $this->requestApi('get', "/admin/api/orders/list?status=$status&service=$serviceId&page_size=$pageSize");
        $data = json_decode($response->getBody(), false);

        return $data->data->orders ?? [];
    }

    public function setStartCount(int $id, int $start): void
    {
        $this->requestApi('post', '/admin/api/orders/set-start-count/' . $id, [
            'json' => ['start_count' => $start],
        ]);
    }

    public function setPartial(int $id, int $remains): void
    {
        $this->requestApi('post', '/admin/api/orders/set-partial/' . $id, [
            'json' => ['remains' => $remains],
        ]);
    }

    public function cancelOrder(int $id): void
    {
        $this->requestApi('post', '/admin/api/orders/cancel/' . $id);
    }

    public function changeStatus(int $id, OrderStatus|int $status): void
    {
        $statusValue = $status instanceof OrderStatus ? $status->value : $status;

        $this->requestApi('post', '/admin/api/orders/change-status/' . $id, [
            'json' => ['status' => $statusValue],
        ]);
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
