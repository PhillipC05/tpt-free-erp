<?php

namespace Database\Factories\Recruitment;

use App\Models\Recruitment\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'application_number' => Application::generateNumber(),
            'job_id' => null,
            'candidate_name' => fake()->name(),
            'candidate_email' => fake()->unique()->safeEmail(),
            'candidate_phone' => fake()->optional()->phoneNumber(),
            'cover_letter' => fake()->optional()->paragraph(),
            'expected_salary' => fake()->optional()->randomFloat(2, 30000, 100000),
            'status' => 'new',
        ];
    }

    public function interviewed(): static
    {
        return $this->state(['status' => 'interview']);
    }

    public function hired(): static
    {
        return $this->state(['status' => 'hired']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected', 'rejection_reason' => fake()->sentence()]);
    }
}
