<?php
// WordPress entry point for Amortization module
if (!defined('AMORTIZATION_PLATFORM')) {
    define('AMORTIZATION_PLATFORM', 'wordpress');
}
require_once __DIR__ . '/../amortization/controller.php';
