<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CsrfProtection
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldCheck($request)) {
            return $next($request);
        }

        $sessionToken = (string) $request->session()->token();

        $headerToken = $request->header('X-CSRF-TOKEN');
        $cookieToken = (string) $request->cookie('XSRF-TOKEN');

        $token = is_string($headerToken) && $headerToken !== ''
            ? $headerToken
            : ($cookieToken !== '' ? $cookieToken : null);

        if (! is_string($token) || $token === '' || ! hash_equals($sessionToken, $token)) {
            abort(419, 'CSRF token mismatch');
        }

        return $next($request);
    }

    private function shouldCheck(Request $request): bool
    {
        return ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }
}

?>
