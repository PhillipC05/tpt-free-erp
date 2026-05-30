<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'type' => fake()->randomElement(['debit', 'credit']),
            'amount' => fake()->randomFloat(2, 10, 50000),
            'description' => fake()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
            'transaction_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'status' => 'pending',
            'created_by' => null,
            'approved_by' => null,
        ];
    }

    public function posted(): static
    {
        return $this->state(['status' => 'posted']);
    }

    public function voided(): static
    {
        return $this->state(['status' => 'void']);
    }
}
