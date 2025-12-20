<?php

exit;
/***
 * FA Admin Settings: GL account mapping configuration
 * Uses Ksfraser\HTML builders for semantic HTML generation
 */

use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\Button;

require_once FA_PATH . '/gl/includes/db/gl_db_accounts.inc';

// Get GL accounts by category
$liability_gls = get_gl_accounts(CL_LIABILITIES);
$asset_gls = get_gl_accounts(CL_ASSETS);
$expense_gls = get_gl_accounts(CL_AMORTIZATION);
$asset_value_gls = get_gl_accounts(CL_FIXEDASSETS);

// Helper function to build select for GL accounts
function buildGlSelector($name, $accounts, $selected = '') {
    $selectElement = (new Select())->setName($name)->setId($name);
    
    foreach ($accounts as $acc) {
        $option = (new Option())
            ->setValue($acc['account_code'])
            ->setText($acc['account_name']);
        if ($acc['account_code'] == $selected) {
            $option->addAttribute('selected', 'selected');
        }
        $selectElement->appendChild($option);
    }
    return $selectElement;
}

// Build form content
echo "<form method='post'>\n";
echo "<label for='liability_gl'>" . ucfirst(str_replace('_', ' ', 'liability_gl')) . ":</label>\n";
buildGlSelector('liability_gl', $liability_gls)->toHtml();
echo "\n";
echo "<label for='asset_gl'>" . ucfirst(str_replace('_', ' ', 'asset_gl')) . ":</label>\n";
buildGlSelector('asset_gl', $asset_gls)->toHtml();
echo "\n";
echo "<label for='expenses_gl'>" . ucfirst(str_replace('_', ' ', 'expenses_gl')) . ":</label>\n";
buildGlSelector('expenses_gl', $expense_gls)->toHtml();
echo "\n";
echo "<label for='asset_value_gl'>" . ucfirst(str_replace('_', ' ', 'asset_value_gl')) . ":</label>\n";
buildGlSelector('asset_value_gl', $asset_value_gls)->toHtml();
echo "\n";
(new Button())->setType('submit')->setText('Save Settings')->toHtml();
echo "\n</form>\n";
