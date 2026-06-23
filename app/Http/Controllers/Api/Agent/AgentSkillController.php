<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentProfile;
use App\Models\Agent\AgentSkillAssignment;
use App\Services\Agent\AgentExecutionService;
use App\Services\Agent\SkillRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentSkillController extends BaseApiController
{
    public function __construct(
        private readonly SkillRegistry $registry,
        private readonly AgentExecutionService $executionService,
    ) {
        parent::__construct();
    }

    // GET /agents/skills/available — full catalog
    public function catalog(): JsonResponse
    {
        $skills = $this->registry->all();
        return $this->respondSuccess('Skill catalog', $skills);
    }

    // GET /agents/{id}/skills
    public function index(int $agentId): JsonResponse
    {
        $agent = AgentProfile::find($agentId);
        if (!$agent) return $this->respondNotFound();

        $assignments = AgentSkillAssignment::where('agent_profile_id', $agentId)->get();
        $allSkills   = $this->registry->all();

        $result = $assignments->map(function ($assignment) use ($allSkills) {
            $meta = collect($allSkills)->firstWhere('slug', $assignment->skill_slug);
            return array_merge($assignment->toArray(), ['skill_meta' => $meta]);
        });

        return $this->respondSuccess('Agent skills', $result);
    }

    // PUT /agents/{id}/skills/{slug}
    public function update(Request $request, int $agentId, string $slug): JsonResponse
    {
        $agent = AgentProfile::find($agentId);
        if (!$agent) return $this->respondNotFound();

        $skill = $this->registry->find($slug);
        if (!$skill) return $this->respondError('Skill not found in registry', 404);

        $error = $this->validate($request->all(), [
            'is_enabled'       => 'nullable|boolean',
            'config_overrides' => 'nullable|array',
        ]);
        if ($error) return $error;

        $assignment = AgentSkillAssignment::updateOrCreate(
            ['agent_profile_id' => $agentId, 'skill_slug' => $slug],
            [
                'is_enabled'       => $request->is_enabled ?? true,
                'config_overrides' => $request->config_overrides,
            ]
        );

        return $this->respondSuccess('Skill assignment updated', array_merge($assignment->toArray(), ['skill_meta' => $skill]));
    }

    // POST /agents/{id}/skills/{slug}/run
    public function run(Request $request, int $agentId, string $slug): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'input' => 'nullable|array',
        ]);
        if ($error) return $error;

        try {
            $execution = $this->executionService->execute(
                $agentId,
                $slug,
                $request->input('input', []),
                $request->user()->id,
                'manual'
            );
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), 422);
        }

        return $this->respondCreated([
            'execution_id' => $execution->id,
            'status'       => $execution->status,
            'message'      => 'Execution queued. Poll GET /api/v1/agents/' . $agentId . '/executions/' . $execution->id,
        ]);
    }
}
