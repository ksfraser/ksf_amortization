<?php
/**
 * PHPUnit Bootstrap Configuration
 * 
 * Initializes PHPUnit test environment, loads autoloader, and sets up global fixtures.
 */

// Define project root
define('PROJECT_ROOT', dirname(__DIR__));

// Load Composer autoloader
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Define mock asset_url function for testing
if (!function_exists('asset_url')) {
    /**
     * Mock asset_url function for testing
     * In production, this would load actual asset URLs
     */
    function asset_url(string $path): string {
        return '/assets/' . $path;
    }
}

// Set test environment
putenv('APP_ENV=testing');
