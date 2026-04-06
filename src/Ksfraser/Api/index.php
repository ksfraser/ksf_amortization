<?php
/**
 * API Index
 * 
 * Main entry point for the API.
 * This file should be called to route all API requests.
 * 
 * Usage with FrontAccounting:
 * - Place this in modules/amortization/api/index.php
 * - Configure web server to route API requests to this file
 * 
 * @package Ksfraser\Api
 * @author KSF Development Team
 * @version 1.0.0
 */

// Prevent direct file access
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    // Direct access to this file is allowed for API routing
}

// Define API base path for this module
if (!defined('API_BASE_PATH')) {
    define('API_BASE_PATH', dirname(__FILE__));
}

// Load Composer autoloader
$autoloadFile = dirname(__FILE__) . '/../../vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => 'Composer autoloader not found. Run composer install.',
    ]);
    exit(1);
}

require_once $autoloadFile;

// Load API Bootstrap
require_once __DIR__ . '/src/Ksfraser/Api/Bootstrap.php';

use Ksfraser\Api\Application;

// Create and run application
try {
    $config = [
        'debug' => defined('DEBUG') && DEBUG === true,
        'base_path' => '/modules/amortization/api',
        'timezone' => 'UTC',
    ];

    $app = new Application($config);
    $app->run();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    $error = [
        'error' => true,
        'message' => 'Application error',
        'status' => 500,
    ];

    if (defined('DEBUG') && DEBUG === true) {
        $error['exception'] = $e->getMessage();
        $error['file'] = $e->getFile();
        $error['line'] = $e->getLine();
    }

    echo json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit(1);
}
