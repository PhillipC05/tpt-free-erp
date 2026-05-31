<?php

namespace Database\Factories\Quality;

use App\Models\Quality\NonConformance;
use App\Models\Quality\QualityCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

class NonConformanceFactory extends Factory
{
    protected $model = NonConformance::class;

    public function definition(): array
    {
        return [
            'nc_number' => fake()->unique()->bothify('NC-####'),
            'check_id' => QualityCheck::factory(),
            'description' => fake()->paragraph(),
            'severity' => fake()->randomElement(['minor', 'major', 'critical']),
            'status' => fake()->randomElement(['open', 'investigating', 'resolved', 'closed']),
            'root_cause' => fake()->optional(0.5)->sentence(),
            'corrective_action' => fake()->optional(0.5)->sentence(),
            'assigned_to' => null,
            'target_resolution_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'resolved_at' => null,
        ];
    }
}
