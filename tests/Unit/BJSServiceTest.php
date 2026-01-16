<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Services\BJS;
use GuzzleHttp\Psr7\Response;
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
            'retry_delay_ms' => 5000,
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

        return $cache;
    }

    private function createBJS(?CacheManager $cache = null): BJS
    {
        return new BJS(
            $cache ?? $this->createMockCache(),
            null,
            null,
            '/tmp/bjs-cookies.json',
            'https://belanjasosmed.com',
            $this->config
        );
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

    public function test_get_orders_returns_null_when_not_authenticated(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->getOrders(1, 0);

        $this->assertNull($result);
    }

    public function test_get_orders_data_returns_empty_array_when_not_authenticated(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->getOrdersData(1, 0);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_set_start_count_returns_false_when_not_authenticated(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->setStartCount(123, 100);

        $this->assertFalse($result);
    }

    public function test_set_partial_returns_false_when_not_authenticated(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->setPartial(123, 50);

        $this->assertFalse($result);
    }

    public function test_cancel_order_returns_false_when_not_authenticated(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->cancelOrder(123);

        $this->assertFalse($result);
    }

    public function test_change_status_returns_false_when_not_authenticated(): void
    {
        $bjs = $this->createBJS();
        $result = $bjs->changeStatus(123, OrderStatus::PENDING);

        $this->assertFalse($result);
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

    public function test_get_data_parses_json_response(): void
    {
        $bjs = $this->createBJS();
        $response = new Response(200, [], '{"data": "test"}');
        $result = $bjs->getData($response);

        $this->assertEquals('test', $result->data);
    }

    public function test_auth_state_disabled_in_production_when_login_toggle_is_false(): void
    {
        $this->app->config['app.debug'] = false;

        $this->cacheData['bjs.session.login_toggle'] = false;

        $bjs = new BJS(
            $this->createMockCache(),
            null,
            null,
            '/tmp/bjs-cookies.json',
            'https://belanjasosmed.com',
            $this->config
        );

        $this->assertEquals(BJS::AUTH_STATE_DISABLED, $bjs->getLastAuthState());
    }

    public function test_auth_state_enabled_in_dev_environment(): void
    {
        $this->app->config['app.debug'] = true;

        $bjs = new BJS(
            $this->createMockCache(),
            null,
            null,
            '/tmp/bjs-cookies.json',
            'https://belanjasosmed.com',
            $this->config
        );

        $this->assertEquals(BJS::AUTH_STATE_VALID, $bjs->getLastAuthState());
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
        $this->config['max_retries'] = null;
        $this->config['retry_delay_ms'] = null;

        $bjs = new BJS(
            $this->createMockCache(),
            null,
            null,
            '/tmp/bjs-cookies.json',
            'https://belanjasosmed.com',
            $this->config
        );

        $reflection = new \ReflectionClass($bjs);
        $maxRetries = $reflection->getProperty('maxRetries');
        $retryDelayMs = $reflection->getProperty('retryDelayMs');
        $maxRetries->setAccessible(true);
        $retryDelayMs->setAccessible(true);

        $this->assertEquals(3, $maxRetries->getValue($bjs));
        $this->assertEquals(5000, $retryDelayMs->getValue($bjs));
    }

    public function test_retry_config_custom(): void
    {
        $this->config['max_retries'] = 5;
        $this->config['retry_delay_ms'] = 2000;

        $bjs = new BJS(
            $this->createMockCache(),
            null,
            null,
            '/tmp/bjs-cookies.json',
            'https://belanjasosmed.com',
            $this->config
        );

        $reflection = new \ReflectionClass($bjs);
        $maxRetries = $reflection->getProperty('maxRetries');
        $retryDelayMs = $reflection->getProperty('retryDelayMs');
        $maxRetries->setAccessible(true);
        $retryDelayMs->setAccessible(true);

        $this->assertEquals(5, $maxRetries->getValue($bjs));
        $this->assertEquals(2000, $retryDelayMs->getValue($bjs));
    }
}
