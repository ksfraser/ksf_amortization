<?php
/**
 * Main View
 * Lists loans and provides navigation
 * @package AmortizationModule
 */

// Access level checks (replace with actual FA permission logic)
global $user;
$isAdmin = isset($user->access) && in_array('LOANS_ADMIN', $user->access);
$isReader = isset($user->access) && in_array('LOANS_READER', $user->access);

?>
<h2>Amortization Loans</h2>
<?php if ($isAdmin): ?>
    <a href="?action=create">Add New Loan</a> | <a href="?action=admin">Admin Settings</a> | <a href="?action=report">View Report</a>
<?php elseif ($isReader): ?>
    <a href="?action=report">View Report</a>
<?php endif; ?>
</table>
use Ksfraser\Amortizations\Views\LoanSummaryTable;
// Assume $db is available
$loanProvider = new LoanProvider($db);
$loans = $loanProvider->getAllLoans();

<h2>Loan Summary</h2>
<?= LoanSummaryTable::render($loans) ?>
</table>
