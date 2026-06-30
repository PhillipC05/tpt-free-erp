<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiKeyUsage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'API key required.',
            ], 401);
        }

        $prefix = substr($token, 0, 8);
        $hash = ApiKey::hashKey($token);

        $apiKey = ApiKey::where('key_prefix', $prefix)
            ->where('key_hash', $hash)
            ->where('is_active', true)
            ->first();

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key.',
            ], 401);
        }

        if ($apiKey->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'API key has expired.',
            ], 401);
        }

        $apiKey->update(['last_used_at' => now()]);

        $request->attributes->set('api_key', $apiKey);

        $startTime = microtime(true);

        $response = $next($request);

        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        ApiKeyUsage::create([
            'api_key_id' => $apiKey->id,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'response_time_ms' => $responseTimeMs,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return $response;
    }
}
