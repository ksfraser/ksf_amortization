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
<table border="1">
    <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Description</th>
        <th>Principal</th>
        <th>Interest Rate</th>
        <th>Term</th>
        <?php if ($isAdmin): ?>
            <th>Actions</th>
        <?php endif; ?>
    </tr>
    <!-- Loop through loans and display rows -->
    <?php /* Example row:
    <tr>
        <td>1</td>
        <td>Auto</td>
        <td>Test Loan</td>
        <td>10000.00</td>
        <td>5.00</td>
        <td>12</td>
        <?php if ($isAdmin): ?>
            <td><a href="?action=edit&id=1">Edit</a> | <a href="?action=delete&id=1">Delete</a></td>
        <?php endif; ?>
    </tr>
    */ ?>
</table>
