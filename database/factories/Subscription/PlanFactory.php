<?php

namespace Database\Factories\Subscription;

use App\Models\Subscription\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PLAN-???-####'),
            'name' => fake()->randomElement(['Starter', 'Professional', 'Business', 'Enterprise', 'Team']),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 9, 199),
            'currency' => 'USD',
            'billing_interval' => fake()->randomElement(['monthly', 'quarterly', 'annually']),
            'trial_days' => fake()->optional(0.4)->numberBetween(7, 30),
            'max_users' => fake()->optional(0.6)->numberBetween(5, 100),
            'included_usage' => fake()->optional()->randomFloat(2, 1000, 100000),
            'usage_overage_rate' => fake()->optional(0.4)->randomFloat(4, 0.001, 0.1),
            'features' => ['basic_reports', 'email_support', 'api_access'],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
