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
    public function listExecutions(Request $request, int $agentId): JsonResponse
    {
        $agent = AgentProfile::find($agentId);
        if (! $agent) {
            return $this->respondNotFound();
        }

        $executions = AgentExecution::where('agent_profile_id', $agentId)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('skill'), fn ($q, $s) => $q->where('skill_slug', $s))
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->respond([
            'success' => true,
            'data' => $executions->items(),
            'meta' => ['total' => $executions->total(), 'current_page' => $executions->currentPage()],
        ]);
    }

    // GET /agents/{id}/executions/{execId}
    public function getExecution(int $agentId, int $execId): JsonResponse
    {
        $execution = AgentExecution::where('agent_profile_id', $agentId)
            ->where('id', $execId)
            ->first();

        if (! $execution) {
            return $this->respondNotFound();
        }

        return $this->respondSuccess('Execution retrieved', $execution);
    }

    // GET /agents/executions/export
    public function exportCsv(Request $request)
    {
        $query = AgentExecution::query()
            ->with('agentProfile:name')
            ->when($request->query('agent_profile_id'), fn ($q, $id) => $q->where('agent_profile_id', $id))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('from'), fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->where('created_at', '<=', $d))
            ->orderByDesc('created_at');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment;filename=agent_executions_'.date('Y-m-d_His').'.csv',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID', 'Agent', 'Skill', 'Status', 'Trigger Type',
                'Tokens Used', 'Model', 'Duration (ms)', 'Error',
                'Created At',
            ]);

            $query->chunk(500, function ($executions) use ($handle) {
                foreach ($executions as $e) {
                    fputcsv($handle, [
                        $e->id,
                        $e->agentProfile->name ?? 'N/A',
                        $e->skill_slug,
                        $e->status,
                        $e->trigger_type,
                        $e->tokens_used,
                        $e->model_used,
                        $e->duration_ms,
                        $e->error_message,
                        $e->created_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
