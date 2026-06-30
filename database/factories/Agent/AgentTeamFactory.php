<?php

namespace Database\Factories\Agent;

use App\Models\Agent\AgentTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentTeamFactory extends Factory
{
    protected $model = AgentTeam::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
