<?php

/**
 * TPT Open ERP - Public Entry Point
 *
 * This file serves as the main entry point for all HTTP requests.
 * It initializes the application and handles routing.
 */

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/bootstrap.php';

$app = new TPT\ERP\Core\Application();

$app->run();
