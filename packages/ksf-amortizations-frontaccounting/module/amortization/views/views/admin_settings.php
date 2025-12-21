<?php
/**
 * FrontAccounting Admin Settings: GL Account Mapping Configuration
 * 
 * Allows administrators to configure GL account mappings for amortization:
 * - Liability GL account
 * - Asset GL account
 * - Expenses GL account
 * - Asset value GL account
 * 
 * Uses Ksfraser\HTML builders and GlSelectorHelper for semantic HTML generation.
 * Follows SRP - single responsibility of GL account configuration UI.
 * 
 * @var array $settings Current GL account settings
 */

use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Paragraph;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\Amortizations\FrontAccounting\Helpers\GlSelectorHelper;

// Load external CSS files
if (function_exists('asset_url')) {
    echo '<link rel="stylesheet" href="' . asset_url('css/admin-settings-container.css') . '">';
    echo '<link rel="stylesheet" href="' . asset_url('css/admin-settings-form.css') . '">';
    echo '<link rel="stylesheet" href="' . asset_url('css/admin-settings-buttons.css') . '">';
}

// Get FrontAccounting paths and constants
require_once FA_PATH . '/gl/includes/db/gl_db_accounts.inc';

// Get GL accounts by category
$liabilityGls = get_gl_accounts(CL_LIABILITIES);
$assetGls = get_gl_accounts(CL_ASSETS);
$expenseGls = get_gl_accounts(CL_AMORTIZATION);
$assetValueGls = get_gl_accounts(CL_FIXEDASSETS);

// Load current settings (from config or database)
$currentSettings = isset($settings) ? $settings : [];

// Build the settings form
$container = (new Div())->addClass('admin-settings-container');

$container->append(
    (new Heading(2))->setText('Amortization GL Account Settings'),
    (new Paragraph())->setText('Configure GL accounts for amortization transaction posting.')
);

$form = (new Form())
    ->setMethod('POST')
    ->setAction('?action=admin')
    ->addClass('admin-settings-form');

// Add form groups for each GL account type
$form->append(
    GlSelectorHelper::buildGlFormGroup(
        'liability_gl',
        'Liability GL Account *',
        $liabilityGls,
        $currentSettings['liability_gl'] ?? '',
        'Select the GL account for loan liabilities'
    )
);

$form->append(
    GlSelectorHelper::buildGlFormGroup(
        'asset_gl',
        'Asset GL Account *',
        $assetGls,
        $currentSettings['asset_gl'] ?? '',
        'Select the GL account for loan assets'
    )
);

$form->append(
    GlSelectorHelper::buildGlFormGroup(
        'expense_gl',
        'Expense GL Account *',
        $expenseGls,
        $currentSettings['expense_gl'] ?? '',
        'Select the GL account for interest expense'
    )
);

$form->append(
    GlSelectorHelper::buildGlFormGroup(
        'asset_value_gl',
        'Asset Value GL Account *',
        $assetValueGls,
        $currentSettings['asset_value_gl'] ?? '',
        'Select the GL account for asset valuation'
    )
);

// Add form actions
$actions = (new Div())->addClass('form-actions');
$actions->append(
    (new Button())
        ->setType('submit')
        ->addClass('btn btn-primary')
        ->setText('Save Settings')
);

$form->append($actions);

// Render the form
$container->append($form);
echo $container->render();
?>
