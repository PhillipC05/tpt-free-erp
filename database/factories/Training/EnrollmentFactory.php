<?php

namespace Database\Factories\Training;

use App\Models\HR\Employee;
use App\Models\Training\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'session_id' => null,
            'employee_id' => Employee::factory(),
            'status' => 'enrolled',
            'score' => null,
            'feedback' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'score' => fake()->randomFloat(1, 5, 10), 'completed_at' => now()]);
    }
}
