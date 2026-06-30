<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        $response = $next($request);

        $durationMs = (hrtime(true) - $start) / 1e6;

        $response->headers->set('X-Request-Time', round($durationMs, 2).'ms');

        if ($durationMs > 1000) {
            Log::channel('performance')->warning('Slow request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => round($durationMs, 2),
                'route' => $request->route()?->getName() ?? $request->path(),
            ]);
        }

        return $response;
    }
}
