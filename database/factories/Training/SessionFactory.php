<?php

namespace Database\Factories\Training;

use App\Models\Training\Program;
use App\Models\Training\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'starts_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'ends_at' => fake()->optional()->dateTimeBetween('+1 day', '+31 days'),
            'location' => fake()->optional()->city(),
            'instructor_id' => null,
            'max_participants' => fake()->optional()->numberBetween(5, 30),
            'status' => 'scheduled',
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
