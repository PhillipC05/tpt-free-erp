<?php

namespace Database\Factories\Recruitment;

use App\Models\Recruitment\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'job_code' => fake()->unique()->bothify('JOB-####'),
            'title' => fake()->randomElement(['Software Engineer', 'Project Manager', 'Sales Representative', 'Accountant', 'Marketing Specialist']),
            'description' => fake()->paragraph(),
            'requirements' => fake()->optional()->paragraph(),
            'department_id' => null,
            'location' => fake()->city(),
            'employment_type' => fake()->randomElement(['full_time', 'part_time', 'contract']),
            'salary_min' => fake()->randomFloat(2, 30000, 60000),
            'salary_max' => fake()->randomFloat(2, 60000, 120000),
            'currency' => 'USD',
            'positions' => fake()->numberBetween(1, 5),
            'status' => 'draft',
            'posted_date' => null,
            'closing_date' => fake()->dateTimeBetween('+30 days', '+60 days'),
            'created_by' => User::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => 'open', 'posted_date' => now()->toDateString()]);
    }
}
