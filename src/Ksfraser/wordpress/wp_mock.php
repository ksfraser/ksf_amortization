<?php
// Mock definitions for WordPress functions/constants to prevent lint errors in standalone development and testing.

if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/'); // Example path, adjust as needed
}

if (!function_exists('dbDelta')) {
    function dbDelta($queries) {
        // Mock implementation: do nothing or log queries
        return true;
    }
}

// You can add more WordPress mocks here as needed for linting or testing.
