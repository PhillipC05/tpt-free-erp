<?php

/**
 * Logging Configuration
 *
 * Configuration for Monolog logging system.
 */

return [
    'default' => env('LOG_CHANNEL', 'single'),
    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => 'debug',
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => 'debug',
            'days' => 14,
        ],
    ],
];
