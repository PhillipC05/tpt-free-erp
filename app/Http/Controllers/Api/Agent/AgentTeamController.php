<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentTeam;
use App\Models\Agent\AgentTeamExecution;
use App\Models\Agent\AgentTeamMember;
use App\Services\Agent\AgentTeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentTeamController extends BaseApiController
{
    public function __construct(
        private readonly AgentTeamService $teamService,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $teams = AgentTeam::withCount(['members', 'executions'])
            ->when($request->query('active'), fn ($q, $a) => $q->where('is_active', filter_var($a, FILTER_VALIDATE_BOOLEAN)))
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $teams->items(),
            'meta' => [
                'total' => $teams->total(),
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        if ($error) {
            return $error;
        }

        $team = AgentTeam::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
            'created_by' => $request->user()->id,
        ]);

        return $this->respondCreated($team);
    }

    public function show(int $id): JsonResponse
    {
        $team = AgentTeam::with(['members.agentProfile', 'creator:id,name,email'])
            ->withCount(['executions', 'members'])
            ->find($id);

        if (! $team) {
            return $this->respondNotFound();
        }

        return $this->respondSuccess('Team retrieved', $team);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $team = AgentTeam::find($id);
        if (! $team) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        if ($error) {
            return $error;
        }

        $team->update($request->only('name', 'description', 'is_active'));

        return $this->respondSuccess('Team updated', $team);
    }

    public function destroy(int $id): JsonResponse
    {
        $team = AgentTeam::find($id);
        if (! $team) {
            return $this->respondNotFound();
        }

        $team->delete();

        return $this->respondSuccess('Team deleted');
    }

    public function manageMembers(Request $request, int $id): JsonResponse
    {
        $team = AgentTeam::find($id);
        if (! $team) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'members' => 'required|array',
            'members.*.agent_profile_id' => 'required|integer|exists:agent_profiles,id',
            'members.*.skill_slug' => 'required|string|max:100',
            'members.*.execution_order' => 'nullable|integer|min:0',
            'members.*.input_mapping' => 'nullable|array',
        ]);
        if ($error) {
            return $error;
        }

        // Sync members: remove existing, add new
        $team->members()->delete();

        foreach ($request->members as $index => $memberData) {
            AgentTeamMember::create([
                'team_id' => $team->id,
                'agent_profile_id' => $memberData['agent_profile_id'],
                'skill_slug' => $memberData['skill_slug'],
                'execution_order' => $memberData['execution_order'] ?? $index,
                'input_mapping' => $memberData['input_mapping'] ?? null,
            ]);
        }

        $team->load('members.agentProfile');

        return $this->respondSuccess('Team members updated', $team->members);
    }

    public function execute(Request $request, int $id): JsonResponse
    {
        $team = AgentTeam::find($id);
        if (! $team) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'input' => 'nullable|array',
        ]);
        if ($error) {
            return $error;
        }

        try {
            $execution = $this->teamService->execute(
                $id,
                $request->input('input', []),
                $request->user()->id
            );
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), 422);
        }

        return $this->respondCreated([
            'execution_id' => $execution->id,
            'status' => $execution->status,
        ]);
    }

    public function getExecution(int $id, int $execId): JsonResponse
    {
        $team = AgentTeam::find($id);
        if (! $team) {
            return $this->respondNotFound();
        }

        $execution = AgentTeamExecution::with(['stepResults.agentProfile', 'triggeredBy:id,name,email'])
            ->where('team_id', $id)
            ->find($execId);

        if (! $execution) {
            return $this->respondNotFound();
        }

        return $this->respondSuccess('Execution retrieved', $execution);
    }

    public function listExecutions(int $id): JsonResponse
    {
        $team = AgentTeam::find($id);
        if (! $team) {
            return $this->respondNotFound();
        }

        $executions = AgentTeamExecution::with('triggeredBy:id,name,email')
            ->where('team_id', $id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $executions->items(),
            'meta' => [
                'total' => $executions->total(),
                'current_page' => $executions->currentPage(),
                'last_page' => $executions->lastPage(),
                'per_page' => $executions->perPage(),
            ],
        ]);
    }
}
