<?php

namespace Database\Factories\Recruitment;

use App\Models\Recruitment\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    public function definition(): array
    {
        return [
            'application_id' => null,
            'interview_type' => fake()->randomElement(['phone', 'video', 'onsite', 'panel']),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+14 days'),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'location' => fake()->optional()->city(),
            'interviewer_id' => null,
            'status' => 'scheduled',
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'score' => fake()->randomFloat(1, 5, 10), 'feedback' => fake()->paragraph()]);
    }
}
