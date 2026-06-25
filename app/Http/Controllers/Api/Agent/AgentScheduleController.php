<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentProfile;
use App\Models\Agent\AgentSchedule;
use App\Services\Agent\SkillRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentScheduleController extends BaseApiController
{
    public function __construct(private readonly SkillRegistry $registry)
    {
        parent::__construct();
    }

    // GET /agents/{id}/schedules
    public function listSchedules(int $agentId): JsonResponse
    {
        $agent = AgentProfile::find($agentId);
        if (!$agent) return $this->respondNotFound();

        $schedules = AgentSchedule::where('agent_profile_id', $agentId)
            ->with('lastExecution')
            ->orderByDesc('created_at')
            ->get();

        return $this->respondSuccess('Agent schedules', $schedules);
    }

    // POST /agents/{id}/schedules
    public function createSchedule(Request $request, int $agentId): JsonResponse
    {
        $agent = AgentProfile::find($agentId);
        if (!$agent) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'skill_slug'      => 'required|string',
            'cron_expression' => 'nullable|string|max:100',
            'input_template'  => 'nullable|array',
            'is_active'       => 'nullable|boolean',
        ]);
        if ($error) return $error;

        $skill = $this->registry->find($request->skill_slug);
        if (!$skill) return $this->respondError('Skill not found', 404);

        $schedule = AgentSchedule::create([
            'agent_profile_id' => $agentId,
            'skill_slug'       => $request->skill_slug,
            'cron_expression'  => $request->cron_expression ?? '0 * * * *',
            'input_template'   => $request->input_template,
            'is_active'        => $request->is_active ?? true,
            'next_run_at'      => now()->addHour(),
        ]);

        return $this->respondCreated($schedule);
    }

    // DELETE /agents/{id}/schedules/{schedId}
    public function deleteSchedule(int $agentId, int $schedId): JsonResponse
    {
        $schedule = AgentSchedule::where('agent_profile_id', $agentId)->where('id', $schedId)->first();
        if (!$schedule) return $this->respondNotFound();

        $schedule->delete();
        return $this->respondSuccess('Schedule deleted');
    }
}
