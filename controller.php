<?php
/**
 * FrontAccounting Amortization Module Controller
 * 
 * Routes requests to appropriate views/actions using Ksfraser\HTML library
 * - Default: List loans
 * - ?action=admin: Admin settings
 * - ?action=create: Create new loan
 * - ?action=report: Generate reports
 * 
 * @package AmortizationModule
 */

// FrontAccounting security and setup
$page_security = 'SA_CUSTOMER'; // TODO: Create specific amortization security roles
$path_to_root = "../..";

// Include FrontAccounting core files - this provides $db, user session, etc.
error_log('AMORT: Including session.inc');
include($path_to_root . "/includes/session.inc");
error_log('AMORT: Included session.inc');
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

// Load Composer autoloaders
// Load both main project autoloader AND module autoloader for FA-specific classes
$mainAutoload = __DIR__ . '/../../vendor/autoload.php';
$moduleAutoload = __DIR__ . '/vendor/autoload.php';

// Load main project autoloader (required for core classes)
if (file_exists($mainAutoload)) {
    error_log('AMORT: Loading main autoload');
    require_once $mainAutoload;
}

// Load module autoloader (required for FA-specific classes like FADataProvider)
if (file_exists($moduleAutoload)) {
    error_log('AMORT: Loading module autoload');
    require_once $moduleAutoload;
}

use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\HtmlA;
use Ksfraser\HTML\Elements\HtmlParagraph;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\Amortizations\FA\AmortizationMenuBuilder;

// Get FrontAccounting table prefix (TB_PREF is defined by FA, typically '0_')
$dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';

// Get action from query parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'default';

// Load local menu builder class
require_once __DIR__ . '/MenuBuilder.php';

// Start FA page (includes header, sets up page context)
// Only call if running within FrontAccounting
if (function_exists('page')) {
    page(_("Amortization Module"));
}

// Display navigation menu on all pages
$menuBuilder = new AmortizationMenuBuilder($path_to_root);
echo $menuBuilder->build();

// Route to appropriate view based on action
switch ($action) {
    case 'admin':
        // Admin settings for GL account mappings
        include __DIR__ . '/views/admin_settings.php';
        break;
        
    case 'admin_selectors':
        // Manage selector options
        include __DIR__ . '/views/admin_selectors.php';
        break;
        
    case 'admin_install':
        // Installation status and schema management
        include __DIR__ . '/views/admin_install.php';
        break;
        
    case 'create':
        // Create new loan
        include __DIR__ . '/views/user_loan_setup.php';
        break;
        
    case 'report':
        // Generate reports
        $reportingPath = __DIR__ . '/vendor/ksfraser/amortizations-core/reporting.php';
        if (file_exists($reportingPath)) {
            include $reportingPath;
        } else {
            echo '<p style="color: red;">Error: reporting.php not found. Run composer install.</p>';
        }
        break;
        
    case 'report_details':
        // AJAX endpoint for report details
        header('Content-Type: application/json');
        if (isset($_GET['id'])) {
            global $db;
            if ($db) {
                $dbPrefix = defined('TB_PREF') ? TB_PREF : '';
                $id = intval($_GET['id']);
                try {
                    $stmt = $db->prepare("SELECT * FROM {$dbPrefix}ksf_amortization_staging WHERE id = ?");
                    $stmt->execute([$id]);
                    $report = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($report) {
                        echo json_encode($report);
                    } else {
                        echo json_encode(['error' => 'Report not found']);
                    }
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['error' => 'Database not available']);
            }
        } else {
            echo json_encode(['error' => 'Missing report ID']);
        }
        exit; // Don't render page footer for AJAX
        
    case 'default':
    default:
        // List loans and provide navigation
        echo (new Heading(2))->setText('Amortization Loans')->render();
        
        // Include loan list view
        $viewPath = __DIR__ . '/vendor/ksfraser/amortizations-core/src/Ksfraser/Amortizations/view.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<p style="color: red;">Error: view.php not found at: ' . htmlspecialchars($viewPath) . '</p>';
            echo '<p>Run: composer install</p>';
        }
        break;
}

// End FA page (includes footer, closes page wrapper)
error_log('AMORT: Before end_page');
// Only call if running within FrontAccounting
if (function_exists('end_page')) {
    error_log('AMORT: Calling end_page');
    end_page();
    error_log('AMORT: After end_page');
} else {
    error_log('AMORT: end_page() not defined');
}
?>
