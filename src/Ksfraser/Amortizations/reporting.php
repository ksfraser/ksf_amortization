<?php
/**
 * Reporting View
 * Displays available reports
 * @package AmortizationModule
 */

use Ksfraser\Amortizations\Views\ReportingTable;
use Ksfraser\Amortizations\FA\FADataProvider;

// Note: This view is included by controller.php
// $db should be available from controller scope
global $db;

// Use FADataProvider to get reports (follows Repository pattern)
try {
    $dataProvider = new FADataProvider($db);
    $reports = $dataProvider->getAllReports();
    
    // Use ReportingTable SRP class to render
    echo ReportingTable::render($reports);
    
} catch (Exception $e) {
    // Use FrontAccounting's error handling system
    if (function_exists('display_error')) {
        display_error('Error loading reports: ' . $e->getMessage());
    } else {
        echo '<p class="error">Error loading reports: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
