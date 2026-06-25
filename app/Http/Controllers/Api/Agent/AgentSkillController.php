<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentProfile;
use App\Models\Agent\AgentSkillAssignment;
use App\Services\Agent\AgentExecutionService;
use App\Services\Agent\SkillRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    public function listSkills(int $agentId): JsonResponse
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
    public function updateSkill(Request $request, int $agentId, string $slug): JsonResponse
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

    // POST /agents/skills/upload — admin only
    public function upload(Request $request): JsonResponse
    {
        if (!$request->hasFile('skill_file') || !$request->file('skill_file')->isValid()) {
            return $this->respondError('A valid skill_file is required', 422);
        }

        $file = $request->file('skill_file');

        if ($file->getClientOriginalExtension() !== 'md') {
            return $this->respondError('Only .md files are accepted', 422);
        }

        $content = file_get_contents($file->getRealPath());

        // Parse to validate required frontmatter fields
        $parsed = $this->registry->parseContent($content);

        $required = ['slug', 'category', 'name'];
        foreach ($required as $field) {
            if (empty($parsed[$field])) {
                return $this->respondError("Missing required frontmatter field: {$field}", 422);
            }
        }

        // Validate slug format: category.name (no spaces, lowercase)
        if (!preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/', $parsed['slug'])) {
            return $this->respondError('Slug must be in format category.name (lowercase, underscores only)', 422);
        }

        $category = $parsed['category'];
        $slug = $parsed['slug'];
        $filename = str_replace($category . '.', '', $slug) . '.md';
        $path = "skills/{$category}/{$filename}";

        Storage::put($path, $content);
        $this->registry->clearCache();

        return $this->respondSuccess('Skill uploaded successfully', ['path' => $path, 'slug' => $slug]);
    }
}
