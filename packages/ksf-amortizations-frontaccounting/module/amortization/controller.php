<?php

/**
 * FrontAccounting Amortization Module Controller
 * 
 * Routes requests to appropriate views/actions:
 * - Default: List loans
 * - ?action=admin: Admin settings
 * - ?action=create: Create new loan
 * - ?action=report: Generate reports
 * 
 * @package AmortizationModule
 */

global $path_to_root, $db;

// Get action from query parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'default';

// Get FrontAccounting table prefix (TB_PREF is defined by FA, typically '0_')
$dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';

// Require Composer autoloader if available
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Route to appropriate view based on action
switch ($action) {
    case 'admin':
        // Admin settings for GL account mappings
        include __DIR__ . '/views/views/admin_settings.php';
        break;
        
    case 'admin_selectors':
        // Manage selector options
        include __DIR__ . '/views/views/admin_selectors.php';
        break;
        
    case 'create':
        // Create new loan
        include __DIR__ . '/views/views/user_loan_setup.php';
        break;
        
    case 'report':
        // Generate reports
        if (file_exists(__DIR__ . '/reporting.php')) {
            include __DIR__ . '/reporting.php';
        } else {
            echo '<h3>Amortization Reports</h3>';
            echo '<p>Reports feature coming soon...</p>';
            // TODO: Implement reporting interface
        }
        break;
        
    case 'default':
    default:
        // List loans and provide navigation
        echo '<h2>Amortization Loans</h2>';
        echo '<div style="margin-bottom: 20px;">';
        echo '<a href="' . $path_to_root . '/modules/amortization/modules/amortization/controller.php?action=create" class="button">Add New Loan</a> ';
        echo '<a href="' . $path_to_root . '/modules/amortization/modules/amortization/controller.php?action=admin" class="button">Admin Settings</a> ';
        echo '<a href="' . $path_to_root . '/modules/amortization/modules/amortization/controller.php?action=admin_selectors" class="button">Manage Selectors</a> ';
        echo '<a href="' . $path_to_root . '/modules/amortization/modules/amortization/controller.php?action=report" class="button">Reports</a>';
        echo '</div>';
        
        // TODO: Implement loan list view
        echo '<p>Loan list view coming soon...</p>';
        break;
}
