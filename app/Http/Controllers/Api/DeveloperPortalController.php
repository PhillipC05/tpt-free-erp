<?php

namespace App\Http\Controllers\Api;

use App\Models\ApiKey;
use App\Models\ApiKeyUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeveloperPortalController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createApiKey(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'rate_limit_per_minute' => 'sometimes|integer|min:1|max:1000',
            'expires_at' => 'sometimes|nullable|date|after:now',
        ]);
        if ($error) {
            return $error;
        }

        $plainKey = ApiKey::generateKey();
        $hash = ApiKey::hashKey($plainKey);
        $prefix = substr($plainKey, 0, 8);

        $apiKey = ApiKey::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'key_hash' => $hash,
            'key_prefix' => $prefix,
            'abilities' => $request->abilities,
            'rate_limit_per_minute' => $request->input('rate_limit_per_minute', 60),
            'expires_at' => $request->expires_at,
        ]);

        return $this->respondCreated([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key' => $plainKey,
            'key_prefix' => $prefix,
            'abilities' => $apiKey->abilities,
            'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
            'expires_at' => $apiKey->expires_at,
            'message' => 'Save this key now. It will not be shown again.',
        ], 'API key created successfully');
    }

    public function listApiKeys(Request $request): JsonResponse
    {
        $keys = ApiKey::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $keys->items(),
            'meta' => [
                'current_page' => $keys->currentPage(),
                'last_page' => $keys->lastPage(),
                'per_page' => $keys->perPage(),
                'total' => $keys->total(),
            ],
        ]);
    }

    public function revokeApiKey(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $apiKey) {
            return $this->respondNotFound();
        }

        $apiKey->update(['is_active' => false]);

        return $this->respondSuccess('API key revoked successfully');
    }

    public function usageStats(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $apiKey) {
            return $this->respondNotFound();
        }

        $days = (int) $request->query('days', 30);
        $since = now()->subDays($days);

        $dailyUsage = ApiKeyUsage::where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', $since)
            ->select(
                DB::raw('date(created_at) as date'),
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when status_code >= 400 then 1 else 0 end) as error_calls'),
                DB::raw('avg(response_time_ms) as avg_response_ms')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalCalls = ApiKeyUsage::where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', $since)
            ->count();

        $errorCalls = ApiKeyUsage::where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', $since)
            ->where('status_code', '>=', 400)
            ->count();

        $errorRate = $totalCalls > 0 ? round($errorCalls / $totalCalls * 100, 2) : 0;

        return $this->respondSuccess('Usage stats retrieved', [
            'daily_usage' => $dailyUsage,
            'total_calls' => $totalCalls,
            'error_calls' => $errorCalls,
            'error_rate' => $errorRate,
            'period_days' => $days,
        ]);
    }

    public function usageByEndpoint(Request $request, int $id): JsonResponse
    {
        $apiKey = ApiKey::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $apiKey) {
            return $this->respondNotFound();
        }

        $days = (int) $request->query('days', 30);
        $since = now()->subDays($days);

        $endpoints = ApiKeyUsage::where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', $since)
            ->select(
                'endpoint',
                'method',
                DB::raw('count(*) as call_count'),
                DB::raw('avg(response_time_ms) as avg_response_ms'),
                DB::raw('sum(case when status_code >= 400 then 1 else 0 end) as error_count')
            )
            ->groupBy('endpoint', 'method')
            ->orderByDesc('call_count')
            ->limit(50)
            ->get();

        return $this->respondSuccess('Endpoint usage retrieved', [
            'endpoints' => $endpoints,
            'period_days' => $days,
        ]);
    }
}
