<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases used by routes
        $middleware->alias([
            'cors.tpt' => \App\Http\Middleware\Cors::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'csrf.protect' => \App\Http\Middleware\CsrfProtection::class,
        ]);

        // Note: Rate limiting is handled via Route::middleware('throttle:api') in routes/api.php.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
