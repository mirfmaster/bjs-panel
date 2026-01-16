<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Exceptions\BJSAuthException;
use App\Exceptions\BJSSessionException;
use App\Exceptions\BJSNetworkException;
use App\Exceptions\BJSException;
use App\Services\BJS;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\TestCase;

class BJSServiceTest extends TestCase
{
    private array $cacheData = [];

    private array $config = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheData = [
            'bjs.credentials.username' => 'testuser',
            'bjs.credentials.password' => 'testpass',
            'bjs.session.login_toggle' => false,
        ];

        $this->config = [
            'max_retries' => 3,
            'retry_delay_ms' => 100,
            'session_cache_ttl' => 600,
            'cookie_path' => '/tmp/bjs-cookies.json',
            'base_uri' => 'https://belanjasosmed.com',
            'cache_keys' => [
                'credentials' => [
                    'username' => 'bjs.credentials.username',
                    'password' => 'bjs.credentials.password',
                ],
                'session' => [
                    'login_toggle' => 'bjs.session.login_toggle',
                ],
                'services' => 'bjs.services',
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function createMockCache(): CacheManager
    {
        $cache = \Mockery::mock(CacheManager::class);
        $cache->shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
            return $this->cacheData[$key] ?? $default;
        });
        $cache->shouldReceive('put')->andReturnUsing(function ($key, $value) {
            $this->cacheData[$key] = $value;
        });
        $cache->shouldReceive('forget')->andReturnUsing(function ($key) {
            unset($this->cacheData[$key]);
        });
        $cache->shouldReceive('has')->andReturnUsing(function ($key) {
            return isset($this->cacheData[$key]);
        });

        return $cache;
    }

    private function createBJS(): BJS
    {
        return new BJS($this->createMockCache(), $this->config);
    }

    public function test_enum_returns_correct_label_for_pending(): void
    {
        $this->assertEquals('pending', OrderStatus::PENDING->label());
    }

    public function test_enum_returns_correct_label_for_inprogress(): void
    {
        $this->assertEquals('inprogress', OrderStatus::INPROGRESS->label());
    }

    public function test_enum_returns_correct_label_for_completed(): void
    {
        $this->assertEquals('completed', OrderStatus::COMPLETED->label());
    }

    public function test_enum_returns_correct_label_for_partial(): void
    {
        $this->assertEquals('partial', OrderStatus::PARTIAL->label());
    }

    public function test_enum_returns_correct_label_for_canceled(): void
    {
        $this->assertEquals('canceled', OrderStatus::CANCELED->label());
    }

    public function test_enum_returns_correct_label_for_processing(): void
    {
        $this->assertEquals('processing', OrderStatus::PROCESSING->label());
    }

    public function test_enum_returns_correct_label_for_fail(): void
    {
        $this->assertEquals('fail', OrderStatus::FAIL->label());
    }

    public function test_enum_returns_correct_label_for_error(): void
    {
        $this->assertEquals('error', OrderStatus::ERROR->label());
    }

    public function test_enum_from_label_returns_correct_enum(): void
    {
        $this->assertEquals(OrderStatus::PENDING, OrderStatus::fromLabel('pending'));
        $this->assertEquals(OrderStatus::INPROGRESS, OrderStatus::fromLabel('inprogress'));
        $this->assertEquals(OrderStatus::COMPLETED, OrderStatus::fromLabel('completed'));
        $this->assertEquals(OrderStatus::PARTIAL, OrderStatus::fromLabel('partial'));
        $this->assertEquals(OrderStatus::CANCELED, OrderStatus::fromLabel('canceled'));
        $this->assertEquals(OrderStatus::PROCESSING, OrderStatus::fromLabel('processing'));
        $this->assertEquals(OrderStatus::FAIL, OrderStatus::fromLabel('fail'));
        $this->assertEquals(OrderStatus::ERROR, OrderStatus::fromLabel('error'));
    }

    public function test_enum_from_label_throws_on_invalid_label(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        OrderStatus::fromLabel('invalid_status');
    }

    public function test_enum_values_match_original_constants(): void
    {
        $this->assertEquals(0, OrderStatus::PENDING->value);
        $this->assertEquals(1, OrderStatus::INPROGRESS->value);
        $this->assertEquals(2, OrderStatus::COMPLETED->value);
        $this->assertEquals(3, OrderStatus::PARTIAL->value);
        $this->assertEquals(4, OrderStatus::CANCELED->value);
        $this->assertEquals(5, OrderStatus::PROCESSING->value);
        $this->assertEquals(6, OrderStatus::FAIL->value);
        $this->assertEquals(7, OrderStatus::ERROR->value);
    }

    public function test_get_orders_throws_auth_exception_when_login_disabled(): void
    {
        $this->app->config['app.debug'] = false;

        $this->expectException(BJSAuthException::class);
        $bjs = $this->createBJS();
        $bjs->getOrders(1, 0);
    }

    public function test_get_orders_data_throws_auth_exception_when_login_disabled(): void
    {
        $this->app->config['app.debug'] = false;

        $this->expectException(BJSAuthException::class);
        $bjs = $this->createBJS();
        $bjs->getOrdersData(1, 0);
    }

    public function test_set_start_count_throws_auth_exception_when_login_disabled(): void
    {
        $this->app->config['app.debug'] = false;

        $this->expectException(BJSAuthException::class);
        $bjs = $this->createBJS();
        $bjs->setStartCount(123, 100);
    }

    public function test_set_partial_throws_auth_exception_when_login_disabled(): void
    {
        $this->app->config['app.debug'] = false;

        $this->expectException(BJSAuthException::class);
        $bjs = $this->createBJS();
        $bjs->setPartial(123, 50);
    }

    public function test_cancel_order_throws_auth_exception_when_login_disabled(): void
    {
        $this->app->config['app.debug'] = false;

        $this->expectException(BJSAuthException::class);
        $bjs = $this->createBJS();
        $bjs->cancelOrder(123);
    }

    public function test_change_status_throws_auth_exception_when_login_disabled(): void
    {
        $this->app->config['app.debug'] = false;

        $this->expectException(BJSAuthException::class);
        $bjs = $this->createBJS();
        $bjs->changeStatus(123, OrderStatus::PENDING);
    }

    public function test_get_ig_username_strips_at_symbol(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->getIGUsername('@testuser');

        $this->assertEquals('testuser', $result);
    }

    public function test_get_ig_username_returns_username_from_url(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->getIGUsername('https://instagram.com/testuser');

        $this->assertEquals('testuser', $result);
    }

    public function test_get_ig_username_returns_input_when_not_url(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->getIGUsername('testuser');

        $this->assertEquals('testuser', $result);
    }

    public function test_auth_state_disabled_in_production_when_login_toggle_is_false(): void
    {
        $this->app->config['app.debug'] = false;

        $this->cacheData['bjs.session.login_toggle'] = false;

        $bjs = new BJS($this->createMockCache(), $this->config);

        $this->assertEquals(BJS::AUTH_STATE_DISABLED, $bjs->getLastAuthState());
    }

    public function test_auth_state_constants_exist(): void
    {
        $this->assertEquals('valid', BJS::AUTH_STATE_VALID);
        $this->assertEquals('reauthenticated', BJS::AUTH_STATE_REAUTHENTICATED);
        $this->assertEquals('failed', BJS::AUTH_STATE_FAILED);
        $this->assertEquals('disabled', BJS::AUTH_STATE_DISABLED);
    }

    public function test_retry_config_defaults(): void
    {
        $config = [
            'max_retries' => null,
            'retry_delay_ms' => null,
            'session_cache_ttl' => null,
            'cookie_path' => '/tmp/bjs-cookies.json',
            'base_uri' => 'https://belanjasosmed.com',
            'cache_keys' => $this->config['cache_keys'],
        ];

        $bjs = new BJS($this->createMockCache(), $config);

        $reflection = new \ReflectionClass($bjs);
        $maxRetries = $reflection->getProperty('maxRetries');
        $retryDelayMs = $reflection->getProperty('retryDelayMs');
        $sessionCacheTtl = $reflection->getProperty('sessionCacheTtl');
        $maxRetries->setAccessible(true);
        $retryDelayMs->setAccessible(true);
        $sessionCacheTtl->setAccessible(true);

        $this->assertEquals(3, $maxRetries->getValue($bjs));
        $this->assertEquals(5000, $retryDelayMs->getValue($bjs));
        $this->assertEquals(600, $sessionCacheTtl->getValue($bjs));
    }

    public function test_retry_config_custom(): void
    {
        $config = $this->config;
        $config['max_retries'] = 5;
        $config['retry_delay_ms'] = 2000;
        $config['session_cache_ttl'] = 300;

        $bjs = new BJS($this->createMockCache(), $config);

        $reflection = new \ReflectionClass($bjs);
        $maxRetries = $reflection->getProperty('maxRetries');
        $retryDelayMs = $reflection->getProperty('retryDelayMs');
        $sessionCacheTtl = $reflection->getProperty('sessionCacheTtl');
        $maxRetries->setAccessible(true);
        $retryDelayMs->setAccessible(true);
        $sessionCacheTtl->setAccessible(true);

        $this->assertEquals(5, $maxRetries->getValue($bjs));
        $this->assertEquals(2000, $retryDelayMs->getValue($bjs));
        $this->assertEquals(300, $sessionCacheTtl->getValue($bjs));
    }

    public function test_exception_hierarchy(): void
    {
        $authException = new BJSAuthException('Auth failed');
        $sessionException = new BJSSessionException('Session expired');
        $networkException = new BJSNetworkException('Network error');

        $this->assertInstanceOf(BJSException::class, $authException);
        $this->assertInstanceOf(BJSException::class, $sessionException);
        $this->assertInstanceOf(BJSException::class, $networkException);

        $this->assertEquals(BJSException::AUTH_FAILED, $authException->getCode());
        $this->assertEquals(BJSException::SESSION_EXPIRED, $sessionException->getCode());
        $this->assertEquals(BJSException::NETWORK_ERROR, $networkException->getCode());
    }

    public function test_get_services_returns_empty_when_not_set(): void
    {
        $bjs = $this->createBJS();
        $services = $bjs->getServices();

        $this->assertIsArray($services);
        $this->assertEmpty($services);
    }

    public function test_get_service_id_returns_null_for_invalid_index(): void
    {
        $bjs = $this->createBJS();
        $serviceId = $bjs->getServiceId(999);

        $this->assertNull($serviceId);
    }

    public function test_get_services_returns_cached_services(): void
    {
        $this->cacheData['bjs.services'] = [11, 22, 33];

        $bjs = $this->createBJS();
        $services = $bjs->getServices();

        $this->assertEquals([11, 22, 33], $services);
    }

    public function test_get_service_id_returns_correct_id(): void
    {
        $this->cacheData['bjs.services'] = [11, 22, 33];

        $bjs = $this->createBJS();

        $this->assertEquals(11, $bjs->getServiceId(0));
        $this->assertEquals(22, $bjs->getServiceId(1));
        $this->assertEquals(33, $bjs->getServiceId(2));
    }

    public function test_exception_thrown_on_empty_credentials_in_dev(): void
    {
        $this->app->config['app.debug'] = true;
        $this->cacheData['bjs.credentials.username'] = '';
        $this->cacheData['bjs.credentials.password'] = '';

        $bjs = new BJS($this->createMockCache(), $this->config);

        $this->expectException(BJSAuthException::class);
        $bjs->getOrders(1, 0);
    }
}
