<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentExecution;
use App\Models\Agent\AgentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentExecutionController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    // GET /agents/{id}/executions
    public function index(Request $request, int $agentId): JsonResponse
    {
        $agent = AgentProfile::find($agentId);
        if (!$agent) return $this->respondNotFound();

        $executions = AgentExecution::where('agent_profile_id', $agentId)
            ->when($request->query('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->query('skill'), fn($q, $s) => $q->where('skill_slug', $s))
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->respond([
            'success' => true,
            'data'    => $executions->items(),
            'meta'    => ['total' => $executions->total(), 'current_page' => $executions->currentPage()],
        ]);
    }

    // GET /agents/{id}/executions/{execId}
    public function show(int $agentId, int $execId): JsonResponse
    {
        $execution = AgentExecution::where('agent_profile_id', $agentId)
            ->where('id', $execId)
            ->first();

        if (!$execution) return $this->respondNotFound();
        return $this->respondSuccess('Execution retrieved', $execution);
    }
}
