<?php

/**
 * Bootstrap Configuration for TPT ERP
 *
 * Initializes the application environment and core settings.
 */

// Load helper functions first
require_once __DIR__ . '/../core/helpers.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Set error reporting based on environment
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
    ini_set('display_errors', '0');
}

// Set default timezone
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

// Set session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', env('APP_ENV', 'development') === 'production' ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', 7200);
