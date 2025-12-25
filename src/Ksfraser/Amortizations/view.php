<?php
/**
 * Main View - Loan List Display
 * Lists loans and provides navigation
 * @package AmortizationModule
 */

use Ksfraser\Amortizations\Views\LoanSummaryTable;
use Ksfraser\Amortizations\FA\FADataProvider;

// Note: This view is included by controller.php
// $db should be available from controller scope
global $db;

// Use FADataProvider to get loans (follows Repository pattern)
try {
    $dataProvider = new FADataProvider($db);
    $loans = $dataProvider->getAllLoans();
    
    // Use LoanSummaryTable SRP class to render
    echo LoanSummaryTable::render($loans);
    
} catch (Exception $e) {
    echo '<p>Error loading loan list: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
