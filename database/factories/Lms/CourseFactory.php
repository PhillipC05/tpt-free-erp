<?php

namespace Database\Factories\Lms;

use App\Models\Lms\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CRS-####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['online', 'classroom', 'blended']),
            'duration_hours' => fake()->randomFloat(1, 1, 40),
            'cost' => fake()->randomFloat(2, 0, 2000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
