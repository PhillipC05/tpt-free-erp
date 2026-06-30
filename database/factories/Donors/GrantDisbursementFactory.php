<?php

namespace Database\Factories\Donors;

use App\Models\Donors\GrantDisbursement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrantDisbursementFactory extends Factory
{
    protected $model = GrantDisbursement::class;

    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 100, 50000),
            'description' => fake()->sentence(),
            'disbursement_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'created_by' => User::factory(),
        ];
    }
}
