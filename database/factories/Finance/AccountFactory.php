<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']),
            'category' => fake()->randomElement(['current', 'non_current', 'operating', 'capital']),
            'description' => fake()->sentence(),
            'parent_id' => null,
            'is_active' => true,
            'currency' => 'USD',
            'opening_balance' => fake()->randomFloat(2, 0, 10000),
            'current_balance' => fake()->randomFloat(2, 0, 10000),
        ];
    }

    public function asset(): static
    {
        return $this->state(['type' => 'asset']);
    }

    public function liability(): static
    {
        return $this->state(['type' => 'liability']);
    }

    public function revenue(): static
    {
        return $this->state(['type' => 'revenue']);
    }

    public function expense(): static
    {
        return $this->state(['type' => 'expense']);
    }
}
