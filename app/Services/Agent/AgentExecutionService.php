<?php

namespace App\Services\Agent;

use App\Jobs\AgentSkillJob;
use App\Models\Agent\AgentExecution;
use App\Models\Agent\AgentProfile;
use App\Models\Agent\AgentSkillAssignment;
use Illuminate\Support\Facades\Log;

class AgentExecutionService
{
    public function __construct(
        private readonly SkillRegistry $registry,
    ) {}

    public function execute(
        int $agentProfileId,
        string $skillSlug,
        array $input,
        ?int $triggeredBy = null,
        string $triggerType = 'manual'
    ): AgentExecution {
        $agent = AgentProfile::findOrFail($agentProfileId);

        if (! $agent->is_active) {
            throw new \RuntimeException("Agent profile '{$agent->name}' is not active.");
        }

        $skill = $this->registry->find($skillSlug);
        if (! $skill) {
            throw new \RuntimeException("Skill '{$skillSlug}' not found in registry.");
        }

        // Check skill is enabled for this agent
        $assignment = AgentSkillAssignment::where('agent_profile_id', $agentProfileId)
            ->where('skill_slug', $skillSlug)
            ->first();

        if ($assignment && ! $assignment->is_enabled) {
            throw new \RuntimeException("Skill '{$skillSlug}' is disabled for this agent.");
        }

        $execution = AgentExecution::create([
            'agent_profile_id' => $agentProfileId,
            'skill_slug' => $skillSlug,
            'triggered_by' => $triggeredBy,
            'trigger_type' => $triggerType,
            'input' => $input,
            'status' => 'queued',
        ]);

        AgentSkillJob::dispatch($agentProfileId, $skillSlug, $input, $triggeredBy, $triggerType, $execution->id);

        Log::info("Agent execution #{$execution->id} queued: agent={$agentProfileId} skill={$skillSlug}");

        return $execution;
    }
}
