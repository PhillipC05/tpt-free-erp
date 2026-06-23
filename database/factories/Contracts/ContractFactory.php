<?php

namespace Database\Factories\Contracts;

use App\Models\Contracts\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'contract_number' => fake()->unique()->bothify('CON-####'),
            'type' => fake()->randomElement(['sale', 'purchase', 'service', 'nda']),
            'status' => 'draft',
            'value' => fake()->randomFloat(2, 1000, 100000),
            'currency' => 'NZD',
            'created_by' => User::factory(),
        ];
    }
}
