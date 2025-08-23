
<?php
class AdminSettings
{
    public static function render($selected = [])
    {
        require_once FA_PATH . '/gl/includes/db/gl_db_accounts.inc';

        // Get GL accounts by category
        $liability_gls = get_gl_accounts(CL_LIABILITIES);
        $asset_gls = get_gl_accounts(CL_ASSETS);
        $expense_gls = get_gl_accounts(CL_AMORTIZATION);
        $asset_value_gls = get_gl_accounts(CL_FIXEDASSETS);

        // Render selectors
        self::gl_selector('liability_gl', $liability_gls, $selected['liability_gl'] ?? '');
        self::gl_selector('asset_gl', $asset_gls, $selected['asset_gl'] ?? '');
        self::gl_selector('expenses_gl', $expense_gls, $selected['expenses_gl'] ?? '');
        self::gl_selector('asset_value_gl', $asset_value_gls, $selected['asset_value_gl'] ?? '');
    }

    private static function gl_selector($name, $accounts, $selected = '')
    {
        echo "<label for='$name'>" . ucfirst(str_replace('_', ' ', $name)) . ":</label>";
        echo "<select name='$name' id='$name'>";
        foreach ($accounts as $acc) {
            $sel = ($acc['account_code'] == $selected) ? 'selected' : '';
            echo "<option value='{$acc['account_code']}' $sel>{$acc['account_name']}</option>";
        }
        echo "</select>";
    }
}
?>
<h2>Amortization Module - Admin Settings</h2>
<form method="post">
  <?php AdminSettings::render(); ?>
  <button type="submit">Save Settings</button>
</form>
