<?php

namespace Database\Factories\Contracts;

use App\Models\Contracts\Contract;
use App\Models\Contracts\ContractMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractMilestone>
 */
class ContractMilestoneFactory extends Factory
{
    protected $model = ContractMilestone::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'due_date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'payment_amount' => fake()->randomFloat(2, 500, 20000),
            'is_completed' => false,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }
}
