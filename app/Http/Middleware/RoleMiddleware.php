<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required',
            ], 401);
        }

        // If no roles were provided, just require authentication.
        $roles = array_values(array_filter($roles, fn ($r) => (string) $r !== ''));
        if (count($roles) === 0) {
            return $next($request);
        }

        $userRoles = DB::table('roles as r')
            ->join('user_roles as ur', 'r.id', '=', 'ur.role_id')
            ->where('ur.user_id', $user->id)
            ->whereNull('ur.deleted_at')
            ->where(function ($q) {
                $q->whereNull('ur.expires_at')->orWhere('ur.expires_at', '>', now());
            })
            ->pluck('r.name')
            ->all();

        $hasRequiredRole = false;
        foreach ($roles as $requiredRole) {
            if (in_array($requiredRole, $userRoles, true)) {
                $hasRequiredRole = true;
                break;
            }
        }

        if (!$hasRequiredRole) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Insufficient role permissions',
            ], 403);
        }

        return $next($request);
    }
}
