<?php

require_once __DIR__ . '/vendor/autoload.php';

return [
    'paths' => [
        'migrations' => 'db/migrations',
        'seeds' => 'db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'name' => env('DB_DATABASE', 'tpt_open_erp'),
            'user' => env('DB_USERNAME', 'postgres'),
            'pass' => env('DB_PASSWORD', ''),
            'port' => env('DB_PORT', '5432'),
            'charset' => 'utf8',
        ],
        'production' => [
            'adapter' => 'pgsql',
            'host' => env('DB_HOST', 'localhost'),
            'name' => env('DB_DATABASE', 'tpt_open_erp'),
            'user' => env('DB_USERNAME', 'postgres'),
            'pass' => env('DB_PASSWORD', ''),
            'port' => env('DB_PORT', '5432'),
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation'
];
