<?php

namespace App\Services\Agent;

use App\Models\Agent\AgentTeam;
use App\Models\Agent\AgentTeamExecution;
use App\Models\Agent\AgentTeamMember;
use App\Models\Agent\AgentTeamStepResult;
use Illuminate\Support\Facades\Log;

class AgentTeamService
{
    public function __construct(
        private readonly AgentExecutionService $executionService,
    ) {}

    public function execute(int $teamId, array $initialInput, ?int $triggeredBy = null): AgentTeamExecution
    {
        $team = AgentTeam::with('members')->findOrFail($teamId);

        if (! $team->is_active) {
            throw new \RuntimeException("Team '{$team->name}' is not active.");
        }

        $teamExecution = AgentTeamExecution::create([
            'team_id' => $teamId,
            'triggered_by' => $triggeredBy,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $members = $team->members->sortBy('execution_order')->values();
        $currentInput = $initialInput;

        foreach ($members as $member) {
            $stepInput = $this->resolveInput($member, $currentInput, $initialInput);

            $stepResult = AgentTeamStepResult::create([
                'team_execution_id' => $teamExecution->id,
                'agent_profile_id' => $member->agent_profile_id,
                'skill_slug' => $member->skill_slug,
                'input' => $stepInput,
                'status' => 'running',
                'step_order' => $member->execution_order,
            ]);

            try {
                $execution = $this->executionService->execute(
                    $member->agent_profile_id,
                    $member->skill_slug,
                    $stepInput,
                    $triggeredBy,
                    'team'
                );

                $stepResult->update([
                    'execution_id' => $execution->id,
                    'status' => $execution->status,
                ]);

                $output = $execution->output ?? [];
                $currentInput = $output;

            } catch (\Throwable $e) {
                Log::error("Team step failed: team={$teamId} member={$member->agent_profile_id} error={$e->getMessage()}");
                $stepResult->update(['status' => 'failed']);
                $teamExecution->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                ]);
                throw $e;
            }
        }

        $teamExecution->update([
            'status' => 'completed',
            'output' => $currentInput,
            'completed_at' => now(),
        ]);

        return $teamExecution->fresh(['stepResults']);
    }

    private function resolveInput(AgentTeamMember $member, array $previousOutput, array $initialInput): array
    {
        if ($member->execution_order === 0) {
            return $initialInput;
        }

        $mapping = $member->input_mapping;
        if (! $mapping) {
            return $previousOutput;
        }

        $resolved = [];
        foreach ($mapping as $targetField => $sourceField) {
            $resolved[$targetField] = data_get($previousOutput, $sourceField);
        }

        return $resolved;
    }
}
