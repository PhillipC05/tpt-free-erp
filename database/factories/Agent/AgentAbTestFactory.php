<?php

namespace Database\Factories\Agent;

use App\Models\Agent\AgentAbTest;
use App\Models\Agent\AgentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentAbTestFactory extends Factory
{
    protected $model = AgentAbTest::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'agent_profile_id' => AgentProfile::factory(),
            'skill_slug_a' => 'test.skill_a',
            'skill_slug_b' => 'test.skill_b',
            'input_data' => ['prompt' => fake()->sentence()],
            'status' => 'draft',
            'winner_skill' => null,
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function running(): static
    {
        return $this->state(['status' => 'running']);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'completed',
            'winner_skill' => $attrs['skill_slug_a'],
        ]);
    }
}
