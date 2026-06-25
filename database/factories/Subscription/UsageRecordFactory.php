<?php

namespace Database\Factories\Subscription;

use App\Models\Subscription\UsageRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageRecordFactory extends Factory
{
    protected $model = UsageRecord::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(100, 50000);
        $unitPrice = fake()->randomFloat(4, 0.0001, 0.05);

        return [
            'subscription_id' => null,
            'usage_type' => fake()->randomElement(['api_calls', 'storage_mb', 'users', 'emails_sent']),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_cost' => round($quantity * $unitPrice, 2),
            'recorded_at' => now(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
