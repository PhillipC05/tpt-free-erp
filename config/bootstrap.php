<?php

/**
 * Bootstrap Configuration for TPT ERP
 *
 * Initializes the application environment and core settings.
 */

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Set error reporting based on environment
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') ?: '0');

// Set default timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');

// Set session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', getenv('APP_ENV') === 'production' ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
