<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentProfile;
use App\Models\Agent\AgentSkillAssignment;
use App\Services\Agent\SkillRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends BaseApiController
{
    public function __construct(private readonly SkillRegistry $registry)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $agents = AgentProfile::withCount(['executions', 'skillAssignments'])
            ->when($request->query('type'), fn($q, $type) => $q->where('agent_type', $type))
            ->when($request->query('active'), fn($q, $active) => $q->where('is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN)))
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data'    => $agents->items(),
            'meta'    => ['total' => $agents->total(), 'current_page' => $agents->currentPage(), 'last_page' => $agents->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name'            => 'required|string|max:200',
            'description'     => 'nullable|string',
            'agent_type'      => 'required|string|in:local,openrouter,api,human_subcontractor',
            'provider_config' => 'nullable|array',
            'is_active'       => 'nullable|boolean',
        ]);
        if ($error) return $error;

        $agent = AgentProfile::create([
            'name'            => $request->name,
            'description'     => $request->description,
            'agent_type'      => $request->agent_type,
            'provider_config' => $request->provider_config,
            'is_active'       => $request->is_active ?? true,
            'created_by'      => $request->user()->id,
        ]);

        return $this->respondCreated($agent);
    }

    public function show(int $id): JsonResponse
    {
        $agent = AgentProfile::with(['skillAssignments', 'creator:id,name,email'])
            ->withCount('executions')
            ->find($id);

        if (!$agent) return $this->respondNotFound();

        // Merge skill registry metadata into assignments
        $allSkills = $this->registry->all();
        $assignments = $agent->skillAssignments->map(function ($assignment) use ($allSkills) {
            $skillMeta = collect($allSkills)->firstWhere('slug', $assignment->skill_slug);
            return array_merge($assignment->toArray(), ['skill_meta' => $skillMeta]);
        });

        return $this->respondSuccess('Agent retrieved', array_merge($agent->toArray(), [
            'skill_assignments' => $assignments,
        ]));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $agent = AgentProfile::find($id);
        if (!$agent) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'name'            => 'sometimes|string|max:200',
            'description'     => 'nullable|string',
            'agent_type'      => 'sometimes|string|in:local,openrouter,api,human_subcontractor',
            'provider_config' => 'nullable|array',
            'is_active'       => 'nullable|boolean',
        ]);
        if ($error) return $error;

        $agent->update($request->only('name', 'description', 'agent_type', 'provider_config', 'is_active'));
        return $this->respondSuccess('Agent updated', $agent);
    }

    public function destroy(int $id): JsonResponse
    {
        $agent = AgentProfile::find($id);
        if (!$agent) return $this->respondNotFound();

        $agent->delete(); // soft delete
        return $this->respondSuccess('Agent deleted');
    }
}
