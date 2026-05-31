<?php

namespace Database\Factories\Projects;

use App\Models\Projects\Project;
use App\Models\Projects\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('TASK-####'),
            'project_id' => Project::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'assigned_to' => null,
            'start_date' => fake()->dateTimeBetween('now', '+1 week')->format('Y-m-d'),
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'completed_at' => null,
            'status' => fake()->randomElement(['todo', 'in_progress', 'review', 'done']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'estimated_hours' => fake()->randomFloat(1, 1, 40),
            'actual_hours' => 0,
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
