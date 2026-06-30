<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' Budget',
            'period_type' => $this->faker->randomElement(['annual', 'quarterly', 'monthly']),
            'year' => $this->faker->numberBetween(2024, 2027),
            'period' => $this->faker->optional()->numberBetween(1, 12),
            'department_id' => null,
            'account_id' => null,
            'budgeted_amount' => $this->faker->randomFloat(2, 1000, 500000),
            'actual_amount' => $this->faker->randomFloat(2, 0, 500000),
            'status' => $this->faker->randomElement(['draft', 'active', 'closed']),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
