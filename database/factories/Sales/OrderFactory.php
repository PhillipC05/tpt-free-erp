<?php

namespace Database\Factories\Sales;

use App\Models\Sales\Customer;
use App\Models\Sales\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);

        return [
            'order_number' => fake()->unique()->bothify('ORD-####-??'),
            'customer_id' => Customer::factory(),
            'order_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'expected_delivery_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['draft', 'confirmed', 'shipped', 'delivered', 'cancelled']),
            'subtotal' => $subtotal,
            'tax_amount' => round($subtotal * 0.1, 2),
            'discount_amount' => 0,
            'total_amount' => round($subtotal * 1.1, 2),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => \App\Models\User::factory(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }
}
