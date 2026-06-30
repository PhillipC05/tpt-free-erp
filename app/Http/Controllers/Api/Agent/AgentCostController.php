<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentCostRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentCostController extends BaseApiController
{
    protected string $cacheTag = 'agents';

    public function __construct()
    {
        parent::__construct();
    }

    public function summary(Request $request): JsonResponse
    {
        $query = AgentCostRecord::query()
            ->select(
                DB::raw('SUM(tokens_input + tokens_output) as total_tokens'),
                DB::raw('SUM(estimated_cost) as total_cost'),
                DB::raw('COUNT(*) as execution_count'),
                DB::raw('CASE WHEN COUNT(*) > 0 THEN SUM(estimated_cost) / COUNT(*) ELSE 0 END as avg_cost_per_execution')
            );

        $query = $this->applyDateRange($query, $request);

        $result = $query->first();

        return $this->respondSuccess('Cost summary', [
            'total_tokens' => (int) ($result->total_tokens ?? 0),
            'total_cost' => (float) ($result->total_cost ?? 0),
            'execution_count' => (int) ($result->execution_count ?? 0),
            'avg_cost_per_execution' => round((float) ($result->avg_cost_per_execution ?? 0), 6),
        ]);
    }

    public function byAgent(Request $request): JsonResponse
    {
        $query = AgentCostRecord::query()
            ->select(
                'agent_profile_id',
                DB::raw('SUM(tokens_input + tokens_output) as total_tokens'),
                DB::raw('SUM(estimated_cost) as total_cost'),
                DB::raw('COUNT(*) as execution_count'),
                DB::raw('CASE WHEN COUNT(*) > 0 THEN SUM(estimated_cost) / COUNT(*) ELSE 0 END as avg_cost_per_execution')
            )
            ->groupBy('agent_profile_id');

        $query = $this->applyDateRange($query, $request);

        $results = $query->with('agentProfile:id,name')->get();

        return $this->respondSuccess('Cost by agent', $results);
    }

    public function bySkill(Request $request): JsonResponse
    {
        $query = AgentCostRecord::query()
            ->select(
                'skill_slug',
                DB::raw('SUM(tokens_input + tokens_output) as total_tokens'),
                DB::raw('SUM(estimated_cost) as total_cost'),
                DB::raw('COUNT(*) as execution_count'),
                DB::raw('CASE WHEN COUNT(*) > 0 THEN SUM(estimated_cost) / COUNT(*) ELSE 0 END as avg_cost_per_execution')
            )
            ->groupBy('skill_slug');

        $query = $this->applyDateRange($query, $request);

        $results = $query->get();

        return $this->respondSuccess('Cost by skill', $results);
    }

    public function daily(Request $request): JsonResponse
    {
        $query = AgentCostRecord::query()
            ->select(
                'date_bucket',
                DB::raw('SUM(tokens_input + tokens_output) as total_tokens'),
                DB::raw('SUM(estimated_cost) as total_cost'),
                DB::raw('COUNT(*) as execution_count')
            )
            ->groupBy('date_bucket')
            ->orderBy('date_bucket');

        $query = $this->applyDateRange($query, $request);

        $results = $query->get();

        return $this->respondSuccess('Daily cost trend', $results);
    }

    private function applyDateRange($query, Request $request)
    {
        if ($request->query('from')) {
            $query->where('date_bucket', '>=', $request->query('from'));
        }
        if ($request->query('to')) {
            $query->where('date_bucket', '<=', $request->query('to'));
        }
        if ($request->query('agent_profile_id')) {
            $query->where('agent_profile_id', $request->query('agent_profile_id'));
        }
        if ($request->query('skill_slug')) {
            $query->where('skill_slug', $request->query('skill_slug'));
        }

        return $query;
    }
}
