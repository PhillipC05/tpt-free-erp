<?php

namespace Database\Factories\Projects;

use App\Models\Projects\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PROJ-####'),
            'name' => fake()->words(4, true),
            'description' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'status' => fake()->randomElement(['planning', 'active', 'on_hold', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'project_manager_id' => null,
            'budget' => fake()->randomFloat(2, 5000, 500000),
            'actual_cost' => fake()->randomFloat(2, 0, 5000),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }
}
