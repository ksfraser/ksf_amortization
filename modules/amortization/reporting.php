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
    </tr>
    <!-- Loop through schedule and display rows -->
    <?php /* Example row:
    <tr>
        <td>2025-01-01</td>
        <td>1000.00</td>
        <td>800.00</td>
        <td>200.00</td>
        <td>9200.00</td>
    </tr>
    */ ?>
</table>
