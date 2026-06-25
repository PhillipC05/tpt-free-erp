<?php

namespace Database\Factories\Pos;

use App\Models\Pos\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'transaction_id' => null,
            'method' => fake()->randomElement(['cash', 'card', 'bank_transfer', 'digital_wallet']),
            'amount' => fake()->randomFloat(2, 5, 500),
            'reference' => fake()->optional()->bothify('REF-####'),
            'notes' => null,
            'received_by' => User::factory(),
        ];
    }
}
