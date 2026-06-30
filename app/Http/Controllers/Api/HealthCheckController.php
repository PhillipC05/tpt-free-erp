<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'disk_space' => $this->checkDiskSpace(),
        ];

        $unhealthy = array_filter($checks, function ($value, $key) {
            return in_array($key, ['database', 'cache', 'queue']) && $value !== 'ok';
        }, ARRAY_FILTER_USE_BOTH);

        if ($unhealthy) {
            $checks['status'] = 'degraded';
        }

        $statusCode = $checks['status'] === 'healthy' ? 200 : 503;

        return response()->json($checks, $statusCode);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Exception $e) {
            return 'error: '.$e->getMessage();
        }
    }

    private function checkCache(): string
    {
        try {
            $key = 'health_check_'.Str::random(8);
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return $value ? 'ok' : 'error: value mismatch';
        } catch (\Exception $e) {
            return 'error: '.$e->getMessage();
        }
    }

    private function checkQueue(): string
    {
        try {
            $connections = config('queue.connections', []);
            $default = config('queue.default');

            return $default && isset($connections[$default]) ? 'ok' : 'no queue configured';
        } catch (\Exception $e) {
            return 'error: '.$e->getMessage();
        }
    }

    private function checkDiskSpace(): array
    {
        $bytes = disk_free_space(storage_path());
        if ($bytes === false) {
            return ['available' => 'unknown'];
        }

        return [
            'available_bytes' => $bytes,
            'available_human' => round($bytes / 1073741824, 2).' GB',
        ];
    }
}
