<?php

use App\Exceptions\ErpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases used by routes
        $middleware->alias([
            'cors.tpt' => \App\Http\Middleware\Cors::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'csrf.protect' => \App\Http\Middleware\CsrfProtection::class,
        ]);

        // Note: Rate limiting is handled via Route::middleware('throttle:api') in routes/api.php.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render all API exceptions as JSON
        $jsonResponse = fn (string $message, int $status, array $extra = []) =>
            response()->json(array_merge(['success' => false, 'message' => $message], $extra), $status);

        $exceptions->render(function (ErpException $e, Request $request) use ($jsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $jsonResponse($e->getMessage(), $e->getStatusCode());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($jsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $jsonResponse('Unauthenticated', 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($jsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $jsonResponse('Validation failed', 422, ['errors' => $e->errors()]);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($jsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $jsonResponse('Resource not found', 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($jsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $jsonResponse('Endpoint not found', 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) use ($jsonResponse) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $jsonResponse($e->getMessage() ?: 'HTTP error', $e->getStatusCode());
            }
        });
    })->create();
