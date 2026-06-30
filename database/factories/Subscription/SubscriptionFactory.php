<?php

namespace Database\Factories\Subscription;

use App\Models\Sales\Customer;
use App\Models\Subscription\Plan;
use App\Models\Subscription\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $plan = Plan::factory()->create();
        $periodEnd = now()->addMonth();

        return [
            'subscription_number' => 'SUB-'.date('Ymd').'-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'plan_id' => $plan->id,
            'status' => 'active',
            'trial_ends_at' => null,
            'current_period_start' => now()->toDateString(),
            'current_period_end' => $periodEnd->toDateString(),
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'billing_anchor_day' => now()->day,
            'quantity' => 1,
            'discount_percent' => 0,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function trialing(): static
    {
        return $this->state([
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
            'cancelled_at' => now()->subDays(5),
            'cancellation_reason' => 'No longer needed',
        ]);
    }
}
