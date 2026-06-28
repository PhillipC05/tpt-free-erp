<?php

namespace Database\Factories\Training;

use App\Models\Training\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('TRN-####'),
            'name' => fake()->randomElement(['Safety Induction', 'Fire Safety', 'Leadership Skills', 'Customer Service', 'OSHA Compliance']),
            'description' => fake()->optional()->paragraph(),
            'course_id' => null,
            'type' => fake()->randomElement(['onboarding', 'compliance', 'skill', 'safety', 'leadership', 'other']),
            'duration_hours' => fake()->optional()->numberBetween(1, 40),
            'cost' => fake()->optional()->randomFloat(2, 0, 500),
            'is_mandatory' => fake()->boolean(30),
            'is_active' => true,
        ];
    }
}
