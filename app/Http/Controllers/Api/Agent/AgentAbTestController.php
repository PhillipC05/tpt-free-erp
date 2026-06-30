<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentAbTest;
use App\Models\Agent\AgentAbTestResult;
use App\Services\Agent\AgentExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentAbTestController extends BaseApiController
{
    protected string $cacheTag = 'agents';

    public function __construct(
        private readonly AgentExecutionService $executionService,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = AgentAbTest::with(['agentProfile:id,name', 'creator:id,name'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('agent_profile_id'), fn ($q, $id) => $q->where('agent_profile_id', $id))
            ->orderByDesc('created_at');

        $perPage = min($request->query('per_page', 15), 100);
        $items = $query->paginate($perPage);

        return $this->respond([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:200',
            'agent_profile_id' => 'required|exists:agent_profiles,id',
            'skill_slug_a' => 'required|string|max:100',
            'skill_slug_b' => 'required|string|max:100|different:skill_slug_a',
            'input_data' => 'nullable|array',
        ]);
        if ($error) {
            return $error;
        }

        $test = AgentAbTest::create([
            'name' => $request->name,
            'agent_profile_id' => $request->agent_profile_id,
            'skill_slug_a' => $request->skill_slug_a,
            'skill_slug_b' => $request->skill_slug_b,
            'input_data' => $request->input_data,
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return $this->respondCreated($test);
    }

    public function show(int $id): JsonResponse
    {
        $test = AgentAbTest::with(['agentProfile:id,name', 'creator:id,name', 'results'])
            ->find($id);

        if (! $test) {
            return $this->respondNotFound();
        }

        return $this->respondSuccess('A/B test retrieved', $test);
    }

    public function run(Request $request, int $id): JsonResponse
    {
        $test = AgentAbTest::find($id);
        if (! $test) {
            return $this->respondNotFound();
        }

        if ($test->status === 'completed') {
            return $this->respondError('This A/B test is already completed', 422);
        }

        $input = $test->input_data ?? [];

        $test->update(['status' => 'running']);

        $results = [];

        foreach ([$test->skill_slug_a, $test->skill_slug_b] as $skillSlug) {
            try {
                $execution = $this->executionService->execute(
                    $test->agent_profile_id,
                    $skillSlug,
                    $input,
                    $request->user()->id,
                    'manual'
                );

                $results[] = AgentAbTestResult::create([
                    'ab_test_id' => $test->id,
                    'skill_slug' => $skillSlug,
                    'execution_id' => $execution->id,
                    'output' => null,
                    'tokens_used' => null,
                    'duration_ms' => null,
                    'quality_score' => null,
                    'created_at' => now(),
                ]);
            } catch (\RuntimeException $e) {
                $results[] = AgentAbTestResult::create([
                    'ab_test_id' => $test->id,
                    'skill_slug' => $skillSlug,
                    'execution_id' => null,
                    'output' => ['error' => $e->getMessage()],
                    'tokens_used' => 0,
                    'duration_ms' => 0,
                    'quality_score' => null,
                    'created_at' => now(),
                ]);
            }
        }

        $test->update(['status' => 'running']);

        return $this->respondCreated([
            'ab_test_id' => $test->id,
            'status' => $test->status,
            'results' => $results,
            'message' => 'A/B test execution queued. Both skills are running against the same input.',
        ]);
    }

    public function declareWinner(Request $request, int $id): JsonResponse
    {
        $test = AgentAbTest::find($id);
        if (! $test) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'winner_skill' => 'required|string|in:'.$test->skill_slug_a.','.$test->skill_slug_b,
        ]);
        if ($error) {
            return $error;
        }

        $test->update([
            'winner_skill' => $request->winner_skill,
            'status' => 'completed',
        ]);

        return $this->respondSuccess('Winner declared', $test);
    }
}
