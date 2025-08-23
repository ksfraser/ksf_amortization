<?php
/**
 * Main View
 * Lists loans and provides navigation
 * @package AmortizationModule
 */

// Access level checks (replace with actual FA permission logic)
global $user;
/*
$isAdmin = isset($user->access) && in_array('LOANS_ADMIN', $user->access);
$isReader = isset($user->access) && in_array('LOANS_READER', $user->access);
*/
$isAdmin = true;

echo "<h2>Amortization Loans</h2>";

if ($isAdmin)
{
	echo '<a href="?action=create">Add New Loan</a>';
	echo ' | ';
	echo '<a href="?action=admin">Admin Settings</a>';
	echo ' | ';
	echo '<a href="?action=report">View Report</a>';
} elseif ($isReader)
{
	echo '<a href="?action=report">View Report</a>';
}
echo "</table>";
use Ksfraser\Amortizations\Views\LoanSummaryTable;
// Assume $db is available
$loanProvider = new LoanProvider($db);
$loans = $loanProvider->getAllLoans();

echo "<h2>Loan Summary</h2>";
LoanSummaryTable::render($loans);
echo "</table>";
