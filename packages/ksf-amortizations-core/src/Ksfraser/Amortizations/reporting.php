<?php
/**
 * Reporting View
 * Displays paydown schedule for selected loan
 * @package AmortizationModule
 */
?>
<h2>Loan Paydown Report</h2>
<table border="1">
    <tr>
        <th>Payment Date</th>
        <th>Payment Amount</th>
        <th>Principal Portion</th>
        <th>Interest Portion</th>
        <th>Remaining Balance</th>
        <th>Interest Calc Freq</th>
    </tr>
use Ksfraser\Amortizations\Views\ReportingTable;
// Assume $db is available
$reportProvider = new ReportProvider($db);
$reports = $reportProvider->getAllReports();

<h2>Reports</h2>
<?= ReportingTable::render($reports) ?>
</table>
