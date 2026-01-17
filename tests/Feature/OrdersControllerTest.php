<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrdersControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_index_returns_orders(): void
    {
        Order::factory()->create(['user' => 'testuser']);

        $response = $this->get('/orders');

        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
        $response->assertViewHas('orders');
    }

    public function test_index_filters_by_service(): void
    {
        Order::factory()->create(['service_id' => 1, 'user' => 'user1']);
        Order::factory()->create(['service_id' => 2, 'user' => 'user2']);

        $response = $this->get('/orders?service_id=1');

        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
        $response->assertViewHas('orders');
        $orders = $response->viewData('orders');
        $this->assertCount(1, $orders);
        $this->assertEquals(1, $orders->first()->service_id);
    }

    public function test_index_filters_by_status(): void
    {
        Order::factory()->create(['status' => OrderStatus::PENDING, 'user' => 'user1']);
        Order::factory()->create(['status' => OrderStatus::COMPLETED, 'user' => 'user2']);

        $response = $this->get('/orders?status=0');

        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
        $orders = $response->viewData('orders');
        $this->assertCount(1, $orders);
        $this->assertEquals(OrderStatus::PENDING, $orders->first()->status);
    }

    public function test_index_searches_by_user(): void
    {
        Order::factory()->create(['user' => 'testuser1']);
        Order::factory()->create(['user' => 'otheruser']);

        $response = $this->get('/orders?search=test');

        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
        $orders = $response->viewData('orders');
        $this->assertCount(1, $orders);
        $this->assertEquals('testuser1', $orders->first()->user);
    }

    public function test_stats_returns_json(): void
    {
        Order::factory()->create(['status' => OrderStatus::PENDING]);
        Order::factory()->create(['status' => OrderStatus::COMPLETED]);

        $response = $this->get('/orders/stats');

        $response->assertStatus(200);
        $response->assertJson([
            'pending' => 1,
            'completed' => 1,
        ]);
    }

    public function test_show_returns_order_details(): void
    {
        $order = Order::factory()->create(['user' => 'testuser']);

        $response = $this->get("/orders/{$order->id}");

        $response->assertStatus(200);
        $response->assertViewIs('orders.show');
        $response->assertViewHas('order');
    }

    public function test_unauthenticated_cannot_access_orders(): void
    {
        \Illuminate\Support\Facades\Auth::logout();

        $response = $this->get('/orders');

        $response->assertRedirect('/login');
    }
}
