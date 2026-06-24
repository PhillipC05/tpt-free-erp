<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\BudgetLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetLineFactory extends Factory
{
    protected $model = BudgetLine::class;

    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'account_id' => Account::factory(),
            'budgeted_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'actual_amount' => $this->faker->randomFloat(2, 0, 100000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
