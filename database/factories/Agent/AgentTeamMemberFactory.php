<?php

namespace Database\Factories\Agent;

use App\Models\Agent\AgentProfile;
use App\Models\Agent\AgentTeam;
use App\Models\Agent\AgentTeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentTeamMemberFactory extends Factory
{
    protected $model = AgentTeamMember::class;

    public function definition(): array
    {
        return [
            'team_id' => AgentTeam::factory(),
            'agent_profile_id' => AgentProfile::factory(),
            'execution_order' => fake()->numberBetween(0, 10),
            'skill_slug' => 'automation.'.fake()->slug(1, '_'),
            'input_mapping' => null,
        ];
    }
}
