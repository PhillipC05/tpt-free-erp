<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentCompanyAccess;
use App\Models\Agent\AgentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentCompanyAccessController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id ?? null;

        $access = AgentCompanyAccess::with('agentProfile')
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $access->items(),
            'meta' => [
                'total' => $access->total(),
                'current_page' => $access->currentPage(),
                'last_page' => $access->lastPage(),
                'per_page' => $access->perPage(),
            ],
        ]);
    }

    public function grant(Request $request, int $id): JsonResponse
    {
        $agent = AgentProfile::find($id);
        if (! $agent) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'company_id' => 'required|integer',
            'access_level' => 'nullable|string|in:view,use,admin',
            'expires_at' => 'nullable|date',
        ]);
        if ($error) {
            return $error;
        }

        $existing = AgentCompanyAccess::where('agent_profile_id', $id)
            ->where('company_id', $request->company_id)
            ->first();

        if ($existing) {
            return $this->respondError('Access already granted for this company', 409);
        }

        $access = AgentCompanyAccess::create([
            'agent_profile_id' => $id,
            'company_id' => $request->company_id,
            'granted_by' => $request->user()->id,
            'access_level' => $request->access_level ?? 'use',
            'expires_at' => $request->expires_at,
        ]);

        return $this->respondCreated($access);
    }

    public function revoke(int $id, int $accessId): JsonResponse
    {
        $access = AgentCompanyAccess::where('agent_profile_id', $id)
            ->where('id', $accessId)
            ->first();

        if (! $access) {
            return $this->respondNotFound();
        }

        $access->delete();

        return $this->respondSuccess('Access revoked');
    }

    public function updateAccess(Request $request, int $id, int $accessId): JsonResponse
    {
        $access = AgentCompanyAccess::where('agent_profile_id', $id)
            ->where('id', $accessId)
            ->first();

        if (! $access) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'access_level' => 'required|string|in:view,use,admin',
            'expires_at' => 'nullable|date',
        ]);
        if ($error) {
            return $error;
        }

        $access->update([
            'access_level' => $request->access_level,
            'expires_at' => $request->expires_at,
        ]);

        return $this->respondSuccess('Access level updated', $access);
    }
}
