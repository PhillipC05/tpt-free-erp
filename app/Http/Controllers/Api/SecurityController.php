<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class SecurityController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function events(Request $request): JsonResponse
    {
        return $this->respondSuccess('Security events', [
            'events' => [],
            'total' => 0,
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        $activeSessions = PersonalAccessToken::where('tokenable_id', $user->id)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now())
            ->count();

        return $this->respondSuccess('Security dashboard', [
            'active_sessions' => $activeSessions,
            'last_login' => $user->updated_at,
            'two_factor_enabled' => false,
        ]);
    }

    public function sessions(): JsonResponse
    {
        $user = auth()->user();
        $tokens = PersonalAccessToken::where('tokenable_id', $user->id)
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
            ]);

        return $this->respondSuccess('Active sessions', $tokens);
    }

    public function terminateSession(int $session): JsonResponse
    {
        $token = PersonalAccessToken::where('id', $session)
            ->where('tokenable_id', auth()->id())
            ->first();

        if (! $token) {
            return $this->respondNotFound();
        }

        $token->delete();

        return $this->respondSuccess('Session terminated');
    }
}
