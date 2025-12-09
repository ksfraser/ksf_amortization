<?php

exit;
/***
 * The code in the file should be in admin_settings class
 * 
 * 
 * 
 */
// FA Admin Settings: GL selectors by category
require_once FA_PATH . '/gl/includes/db/gl_db_accounts.inc';

function gl_selector($name, $accounts, $selected = '') {
    echo "<label for='$name'>" . ucfirst(str_replace('_', ' ', $name)) . ":</label>";
    echo "<select name='$name' id='$name'>";
    foreach ($accounts as $acc) {
        $sel = ($acc['account_code'] == $selected) ? 'selected' : '';
        echo "<option value='{$acc['account_code']}' $sel>{$acc['account_name']}</option>";
    }
    echo "</select>";
}

// Get GL accounts by category
$liability_gls = get_gl_accounts(CL_LIABILITIES);
$asset_gls = get_gl_accounts(CL_ASSETS);
$expense_gls = get_gl_accounts(CL_AMORTIZATION);
$asset_value_gls = get_gl_accounts(CL_FIXEDASSETS);

// UI
?>
<form method="post">
  <?php gl_selector('liability_gl', $liability_gls); ?>
  <?php gl_selector('asset_gl', $asset_gls); ?>
  <?php gl_selector('expenses_gl', $expense_gls); ?>
  <?php gl_selector('asset_value_gl', $asset_value_gls); ?>
  <button type="submit">Save Settings</button>
</form>
