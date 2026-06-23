<?php

namespace Database\Factories\Expenses;

use App\Models\Expenses\ExpenseReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseReport>
 */
class ExpenseReportFactory extends Factory
{
    protected $model = ExpenseReport::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'user_id' => User::factory(),
            'status' => 'draft',
            'total_amount' => 0,
        ];
    }
}
