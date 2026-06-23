<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'company' => fake()->company(),
            'source' => fake()->randomElement(['organic', 'referral', 'campaign', 'network', 'manual']),
            'status' => 'new',
            'interest_score' => fake()->numberBetween(0, 100),
        ];
    }
}
