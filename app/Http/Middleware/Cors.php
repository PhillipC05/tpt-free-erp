<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $cors = config('security.cors', []);
        if (!($cors['enabled'] ?? false)) {
            return $response;
        }

        $origin = $request->headers->get('Origin');
        $allowedOrigins = $cors['allowed_origins'] ?? ['*'];

        $isAllowed = in_array('*', $allowedOrigins, true);
        if (!$isAllowed && $origin) {
            $isAllowed = in_array($origin, $allowedOrigins, true);
        }

        if ($isAllowed && $origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
        } elseif ($isAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $cors['allowed_headers'] ?? ['*']));
        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $cors['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']));
        $response->headers->set('Access-Control-Max-Age', (string)($cors['max_age'] ?? 86400));

        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(204);
        }

        return $response;
    }
}
