<?php
/**
 * Admin Settings View
 * Allows selection of GL accounts and management of loans/mortgages
 * @package AmortizationModule
 */
?>
<h2>Amortization Module - Admin Settings</h2>
<form method="post" action="">
    <label for="asset_gl">Asset GL Account:</label>
    <select name="asset_gl" id="asset_gl">
        <!-- Populate with GL accounts -->
    </select>
    <button type="button" onclick="addGL('asset')">Add GL</button><br><br>

    <label for="liability_gl">Liability (Loan) GL Account:</label>
    <select name="liability_gl" id="liability_gl">
        <!-- Populate with GL accounts -->
    </select>
    <button type="button" onclick="addGL('liability')">Add GL</button><br><br>

    <label for="expense_gl">Expense GL Account:</label>
    <select name="expense_gl" id="expense_gl">
        <!-- Populate with GL accounts -->
    </select>
    <button type="button" onclick="addGL('expense')">Add GL</button><br><br>

    <label for="asset_value_gl">Asset Value GL Account:</label>
    <select name="asset_value_gl" id="asset_value_gl">
        <!-- Populate with GL accounts -->
    </select>
    <button type="button" onclick="addGL('asset_value')">Add GL</button><br><br>

    <input type="submit" value="Save Settings">
</form>
<hr>
<h3>Manage Loans/Mortgages</h3>
<form method="post" action="">
    <input type="hidden" name="edit_loan_id" value="">
    <label for="loan_type">Loan Type:</label>
    <select name="loan_type" id="loan_type">
        <option value="Auto">Auto</option>
        <option value="Mortgage">Mortgage</option>
        <option value="Other">Other</option>
    </select>
    <label for="description">Description:</label>
    <input type="text" name="description" id="description">
    <label for="amount_financed">Amount Financed:</label>
    <input type="number" name="amount_financed" id="amount_financed" step="0.01">
    <label for="interest_rate">Interest Rate (%):</label>
    <input type="number" name="interest_rate" id="interest_rate" step="0.01">
    <label for="payment_frequency">Payment Frequency:</label>
    <select name="payment_frequency" id="payment_frequency">
        <option value="monthly">Monthly</option>
        <option value="bi-weekly">Bi-Weekly</option>
        <option value="weekly">Weekly</option>
        <option value="custom">Custom</option>
    </select>
    <label for="interest_calc_frequency">Interest Calculation Frequency:</label>
    <select name="interest_calc_frequency" id="interest_calc_frequency">
        <option value="monthly">Monthly</option>
        <option value="bi-weekly">Bi-Weekly</option>
        <option value="weekly">Weekly</option>
        <option value="custom">Custom</option>
    </select>
    <label for="num_payments">Number of Payments:</label>
    <input type="number" name="num_payments" id="num_payments">
    <label for="regular_payment">Regular Payment Amount:</label>
    <input type="number" name="regular_payment" id="regular_payment" step="0.01">
    <input type="checkbox" name="override_payment" id="override_payment"> <label for="override_payment">Override Calculated Payment</label>
    <label for="first_payment_date">First Payment Date:</label>
    <input type="date" name="first_payment_date" id="first_payment_date">
    <label for="last_payment_date">Last Payment Date:</label>
    <input type="date" name="last_payment_date" id="last_payment_date">
    <input type="submit" value="Save Loan">
</form>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Description</th>
        <th>Amount Financed</th>
        <th>Interest Rate</th>
        <th>Payment Frequency</th>
        <th>Interest Calc Frequency</th>
        <th>Number of Payments</th>
        <th>Regular Payment</th>
        <th>First Payment Date</th>
        <th>Last Payment Date</th>
        <th>Actions</th>
    </tr>
    <!-- Loop through loans and display rows with Edit button -->
    <?php /* Example row:
    <tr>
        <td>1</td>
        <td>Auto</td>
        <td>Test Loan</td>
        <td>10000.00</td>
        <td>5.00</td>
        <td>Monthly</td>
        <td>Monthly</td>
        <td>12</td>
        <td>856.07</td>
        <td>2025-01-01</td>
        <td>2025-12-01</td>
        <td><button>Edit</button></td>
    </tr>
    */ ?>
</table>
<script>
function addGL(type) {
    alert('Add GL for ' + type + ' (mock)');
}
</script>
