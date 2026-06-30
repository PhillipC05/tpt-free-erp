<?php

namespace Database\Factories\Donors;

use App\Models\Donors\Grant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GrantFactory extends Factory
{
    protected $model = Grant::class;

    public function definition(): array
    {
        return [
            'donor_id' => null,
            'title' => fake()->sentence(3),
            'grant_number' => fake()->unique()->numerify('GRN-#####'),
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'status' => 'draft',
            'start_date' => fake()->optional()->dateTimeBetween('-1 year', '+6 months'),
            'end_date' => fake()->optional()->dateTimeBetween('+6 months', '+2 years'),
            'purpose' => fake()->sentence(),
            'requirements' => fake()->optional()->paragraph(),
            'funded_amount' => 0,
            'spent_amount' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed']);
    }
}
