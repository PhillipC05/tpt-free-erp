<?php
/**
 * Security Configuration
 * TPT Open ERP
 */

return [
    'encryption' => [
        'key' => env('ENCRYPTION_KEY'),
        'cipher' => 'AES-256-CBC',
        'enabled' => env('ENCRYPT_SENSITIVE_DATA', true),
    ],

    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'ttl' => 7200, // 2 hours
        'refresh_ttl' => 604800, // 1 week
        'algorithm' => 'HS256',
    ],

    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => false,
        'history_count' => 5,
    ],

    'session' => [
        'timeout' => env('SESSION_TIMEOUT', 7200),
        'regenerate_frequency' => 300,
        'secure_cookie' => env('SESSION_SECURE_COOKIE', false),
        'http_only' => true,
        'same_site' => 'lax',
    ],

    'two_factor' => [
        'enabled' => env('TWO_FACTOR_REQUIRED', false),
        'issuer' => env('APP_NAME', 'TPT Free ERP'),
        'digits' => 6,
        'period' => 30,
    ],

    'rate_limiting' => [
        'enabled' => env('API_THROTTLE_ENABLED', true),
        'max_attempts' => env('API_RATE_LIMIT', 1000),
        'decay_minutes' => 1,
    ],

    'cors' => [
        'enabled' => true,
        'allowed_origins' => ['*'],
        'allowed_headers' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'max_age' => 86400,
    ],

    'content_security_policy' => [
        'enabled' => true,
        'default_src' => ["'self'"],
        'script_src' => ["'self'", "'unsafe-inline'"],
        'style_src' => ["'self'", "'unsafe-inline'"],
        'img_src' => ["'self'", 'data:', 'https:'],
    ],

    'audit' => [
        'enabled' => env('AUDIT_LOG_ENABLED', true),
        'events' => [
            'login',
            'logout',
            'password_change',
            'role_change',
            'data_access',
            'data_modification',
        ],
        'retention_days' => env('DATA_RETENTION_DAYS', 2555),
    ],

    'gdpr' => [
        'enabled' => env('GDPR_ENABLED', true),
        'consent_required' => true,
        'data_portability' => true,
        'right_to_erasure' => true,
        'consent_retention' => 2555,
    ],
];
