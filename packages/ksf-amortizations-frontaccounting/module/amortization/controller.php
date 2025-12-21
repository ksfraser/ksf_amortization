<?php

/**
 * FrontAccounting Amortization Module Controller
 * 
 * Routes requests to appropriate views/actions using Ksfraser\HTML builder:
 * - Default: List loans
 * - ?action=admin: Admin settings
 * - ?action=create: Create new loan
 * - ?action=report: Generate reports
 * 
 * @package AmortizationModule
 */

use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Link;
use Ksfraser\HTML\Elements\Paragraph;

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
            (new Heading(3))->setText('Amortization Reports')->toHtml();
            (new Paragraph())->setText('Reports feature coming soon...')->toHtml();
            // TODO: Implement reporting interface
        }
        break;
        
    case 'default':
    default:
        // List loans and provide navigation
        (new Heading(2))->setText('Amortization Loans')->toHtml();
        
        $nav = (new Div())
            ->addAttribute('class', 'module-nav')
            ->addAttribute('style', 'margin-bottom: 20px;');
        
        $nav->appendChild(
            (new Link())
                ->setHref($path_to_root . '/modules/amortization/modules/amortization/controller.php?action=create')
                ->setText('Add New Loan')
                ->addAttribute('class', 'button')
        );
        
        $nav->appendChild(
            (new Link())
                ->setHref($path_to_root . '/modules/amortization/modules/amortization/controller.php?action=admin')
                ->setText('Admin Settings')
                ->addAttribute('class', 'button')
        );
        
        $nav->appendChild(
            (new Link())
                ->setHref($path_to_root . '/modules/amortization/modules/amortization/controller.php?action=admin_selectors')
                ->setText('Manage Selectors')
                ->addAttribute('class', 'button')
        );
        
        $nav->appendChild(
            (new Link())
                ->setHref($path_to_root . '/modules/amortization/modules/amortization/controller.php?action=report')
                ->setText('Reports')
                ->addAttribute('class', 'button')
        );
        
        $nav->toHtml();
        
        // TODO: Implement loan list view
        (new Paragraph())->setText('Loan list view coming soon...')->toHtml();
        break;
}
