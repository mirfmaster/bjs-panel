<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_has_correct_fillable_fields(): void
    {
        $order = Order::create([
            'bjs_id' => 'BJS123',
            'user' => 'testuser',
            'link' => 'https://instagram.com/test',
            'start_count' => 100,
            'count' => 500,
            'service_id' => 1,
            'status' => OrderStatus::PENDING,
            'remains' => 500,
            'order_created_at' => now(),
            'order_cancel_reason' => null,
            'order_fail_reason' => null,
            'charge' => 5.50,
        ]);

        $this->assertNotNull($order->id);
        $this->assertEquals('testuser', $order->user);
        $this->assertEquals('https://instagram.com/test', $order->link);
        $this->assertEquals(100, $order->start_count);
        $this->assertEquals(500, $order->count);
        $this->assertEquals(1, $order->service_id);
        $this->assertEquals(OrderStatus::PENDING, $order->status);
        $this->assertEquals(500, $order->remains);
        $this->assertEquals(5.50, $order->charge);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $order = Order::create([
            'bjs_id' => 'BJS123',
            'id' => 123,
            'user' => 'testuser',
            'link' => 'https://instagram.com/test',
            'count' => 500,
            'service_id' => 1,
            'status' => 0,
            'order_created_at' => now(),
        ]);

        $this->assertInstanceOf(OrderStatus::class, $order->status);
        $this->assertEquals(OrderStatus::PENDING, $order->status);
    }

    public function test_order_created_at_is_cast_to_datetime(): void
    {
        $order = Order::create([
            'bjs_id' => 'BJS123',
            'id' => 123,
            'user' => 'testuser',
            'link' => 'https://instagram.com/test',
            'count' => 500,
            'service_id' => 1,
            'status' => 0,
            'order_created_at' => '2026-01-17 12:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $order->order_created_at);
        $this->assertEquals('2026-01-17 12:00:00', $order->order_created_at->format('Y-m-d H:i:s'));
    }

    public function test_charge_is_cast_to_decimal(): void
    {
        $order = Order::create([
            'bjs_id' => 'BJS123',
            'user' => 'testuser',
            'link' => 'https://instagram.com/test',
            'count' => 500,
            'service_id' => 1,
            'status' => 0,
            'order_created_at' => now(),
            'charge' => 10.99,
        ]);

        $this->assertEquals(10.99, $order->charge);
    }

    public function test_bjs_id_fillable(): void
    {
        $order = Order::create([
            'user' => 'testuser',
            'link' => 'https://instagram.com/test',
            'count' => 500,
            'service_id' => 1,
            'status' => 0,
            'order_created_at' => now(),
            'bjs_id' => 'BJS12345',
        ]);

        $this->assertEquals('BJS12345', $order->bjs_id);
    }

    public function test_status_bjs_is_cast_to_enum(): void
    {
        $order = Order::create([
            'bjs_id' => 'BJS123',
            'id' => 123,
            'user' => 'testuser',
            'link' => 'https://instagram.com/test',
            'count' => 500,
            'service_id' => 1,
            'status' => 0,
            'status_bjs' => 2,
            'order_created_at' => now(),
        ]);

        $this->assertInstanceOf(OrderStatus::class, $order->status_bjs);
        $this->assertEquals(OrderStatus::COMPLETED, $order->status_bjs);
    }

    public function test_processed_by_is_fillable(): void
    {
        $order = Order::create([
            'bjs_id' => 'BJS123',
            'user' => 'testuser',
            'link' => 'https://instagram.com/test',
            'count' => 500,
            'service_id' => 1,
            'status' => 0,
            'order_created_at' => now(),
            'processed_by' => null,
            'processed_at' => now(),
        ]);

        $this->assertNull($order->processed_by);
        $this->assertNotNull($order->processed_at);
    }

    public function test_last_synced_at_is_cast_to_datetime(): void
    {
        $order = Order::create([
            'bjs_id' => 'BJS123',
            'user' => 'testuser',
            'link' => 'https://instagram.com/test',
            'count' => 500,
            'service_id' => 1,
            'status' => 0,
            'order_created_at' => now(),
            'last_synced_at' => '2026-01-17 12:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $order->last_synced_at);
        $this->assertEquals('2026-01-17 12:00:00', $order->last_synced_at->format('Y-m-d H:i:s'));
    }
}
