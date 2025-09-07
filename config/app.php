<?php
/**
 * Application Configuration
 * TPT Open ERP
 */

return [
    'name' => env('APP_NAME', 'TPT Free ERP'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'providers' => [
        // Core service providers
        TPT\ERP\Providers\AppServiceProvider::class,
        TPT\ERP\Providers\DatabaseServiceProvider::class,
        TPT\ERP\Providers\RouteServiceProvider::class,
        TPT\ERP\Providers\CacheServiceProvider::class,
        TPT\ERP\Providers\LogServiceProvider::class,
    ],

    'aliases' => [
        'App' => TPT\ERP\Facades\App::class,
        'DB' => TPT\ERP\Facades\DB::class,
        'Cache' => TPT\ERP\Facades\Cache::class,
        'Log' => TPT\ERP\Facades\Log::class,
    ],
];
