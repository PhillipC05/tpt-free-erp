<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Agent\AgentProfile;
use App\Models\Agent\AgentToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentTokenController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createToken(Request $request, int $agentId): JsonResponse
    {
        $agent = AgentProfile::find($agentId);
        if (! $agent) {
            return $this->respondNotFound();
        }

        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:200',
            'abilities' => 'nullable|array',
            'allowed_skill_slugs' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:600',
            'expires_at' => 'nullable|date|after:now',
        ]);
        if ($error) {
            return $error;
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        $agentToken = AgentToken::create([
            'agent_profile_id' => $agentId,
            'user_id' => $request->user()->id,
            'token_hash' => $tokenHash,
            'name' => $request->name,
            'abilities' => $request->abilities ?? ['*'],
            'allowed_skill_slugs' => $request->allowed_skill_slugs,
            'rate_limit_per_minute' => $request->rate_limit_per_minute ?? 60,
            'expires_at' => $request->expires_at,
        ]);

        // Return plain token once — never shown again
        return $this->respondCreated(array_merge($agentToken->toArray(), [
            'plain_token' => $plainToken,
            'warning' => 'Save this token now — it will not be shown again.',
        ]));
    }

    public function deleteToken(int $agentId, int $tokenId): JsonResponse
    {
        $token = AgentToken::where('agent_profile_id', $agentId)->where('id', $tokenId)->first();
        if (! $token) {
            return $this->respondNotFound();
        }

        $token->delete(); // soft delete

        return $this->respondSuccess('Token revoked');
    }
}
