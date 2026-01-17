<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user' => $this->faker->userName,
            'link' => $this->faker->url,
            'start_count' => $this->faker->numberBetween(0, 1000),
            'count' => $this->faker->numberBetween(100, 5000),
            'service_id' => 1,
            'status' => OrderStatus::PENDING,
            'remains' => $this->faker->numberBetween(0, 5000),
            'order_created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'order_cancel_reason' => null,
            'order_fail_reason' => null,
            'charge' => $this->faker->randomFloat(2, 1, 100),
        ];
    }
}
