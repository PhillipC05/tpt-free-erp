<?php

namespace Database\Factories\Pos;

use App\Models\Pos\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'transaction_number' => 'TXN-' . date('Ymd') . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'terminal_id' => null,
            'customer_id' => null,
            'employee_id' => null,
            'status' => 'open',
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'currency' => 'USD',
            'notes' => null,
            'completed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function completed(): static
    {
        return $this->state(function () {
            $subtotal = fake()->randomFloat(2, 10, 500);
            return [
                'status' => 'completed',
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'completed_at' => now(),
            ];
        });
    }

    public function voided(): static
    {
        return $this->state(['status' => 'voided']);
    }
}
