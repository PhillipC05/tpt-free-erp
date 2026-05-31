<?php

namespace Database\Factories\Lms;

use App\Models\HR\Employee;
use App\Models\Lms\Course;
use App\Models\Lms\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'employee_id' => Employee::factory(),
            'enrollment_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'completion_date' => null,
            'status' => fake()->randomElement(['enrolled', 'in_progress', 'completed', 'dropped']),
            'score' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completion_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'score' => fake()->randomFloat(1, 60, 100),
        ]);
    }
}
