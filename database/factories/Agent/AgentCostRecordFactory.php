<?php

namespace Database\Factories\Agent;

use App\Models\Agent\AgentCostRecord;
use App\Models\Agent\AgentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentCostRecordFactory extends Factory
{
    protected $model = AgentCostRecord::class;

    public function definition(): array
    {
        $tokensInput = fake()->numberBetween(100, 5000);
        $tokensOutput = fake()->numberBetween(50, 2000);
        $totalTokens = $tokensInput + $tokensOutput;

        return [
            'agent_profile_id' => AgentProfile::factory(),
            'skill_slug' => 'test.skill',
            'model_used' => 'meta-llama/llama-3.1-70b-instruct',
            'tokens_input' => $tokensInput,
            'tokens_output' => $tokensOutput,
            'estimated_cost' => round($totalTokens * (0.50 / 1_000_000), 6),
            'currency' => 'USD',
            'recorded_at' => now(),
            'date_bucket' => now()->toDateString(),
            'created_at' => now(),
        ];
    }
}
