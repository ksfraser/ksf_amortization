<?php
// Admin Settings View - GL Account Configuration
// This view handles GL account selector configuration for amortization module

use Ksfraser\HTML\Elements\HtmlForm;
use Ksfraser\HTML\Elements\HtmlInput;
use Ksfraser\HTML\Elements\HtmlParagraph;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\Heading;

global $db;
$dbPrefix = defined('TB_PREF') ? TB_PREF : '';

// Check if we're in a proper FA environment
if (!isset($db) || !$db) {
    echo (new Heading(2))->setText('GL Account Mappings')->render();
    echo '<p>Database connection not available. This view must be accessed through FrontAccounting.</p>';
    return;
}

echo (new Heading(2))->setText('GL Account Mappings')->render();
echo '<p>Configure which GL accounts are used for loan-related transactions.</p>';

$tableName = $dbPrefix . 'ksf_gl_mappings';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mappings'])) {
    try {
        foreach ($_POST['mapping'] as $type => $accountCode) {
            // Update or insert mapping
            $stmt = $db->prepare("
                INSERT INTO {$tableName} (mapping_type, gl_account_code, description) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE gl_account_code = ?, description = ?
            ");
            $description = $_POST['description'][$type] ?? '';
            $stmt->execute([$type, $accountCode, $description, $accountCode, $description]);
        }
        echo '<p style="color: green; font-weight: bold;">GL Account mappings saved successfully.</p>';
    } catch (Exception $e) {
        echo '<p style="color: red;">Error saving mappings: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// Fetch existing mappings
$mappings = [];
try {
    $stmt = $db->query("SELECT mapping_type, gl_account_code, description FROM {$tableName}");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mappings[$row['mapping_type']] = $row;
    }
} catch (Exception $e) {
    echo '<p style="color: red;">Error loading mappings: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Fetch available GL accounts from FA
$glAccounts = [];
try {
    $chartTable = $dbPrefix . 'chart_master';
    $stmt = $db->query("SELECT account_code, account_name FROM {$chartTable} ORDER BY account_code");
    $glAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<p style="color: red;">Error loading GL accounts: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Mapping types
$mappingTypes = [
    'liability' => 'Loan Liability Account',
    'asset' => 'Loan Asset Account',
    'expense' => 'Interest Expense Account',
    'asset_value' => 'Asset Value Account'
];

echo '<form method="post" style="max-width: 800px;">';
echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
echo '<tr><th>Mapping Type</th><th>GL Account</th><th>Description</th></tr>';

foreach ($mappingTypes as $type => $label) {
    $currentAccount = $mappings[$type]['gl_account_code'] ?? '';
    $currentDescription = $mappings[$type]['description'] ?? '';
    
    echo '<tr>';
    echo '<td><strong>' . htmlspecialchars($label) . '</strong></td>';
    echo '<td>';
    echo '<select name="mapping[' . htmlspecialchars($type) . ']" required style="width: 100%;">';
    echo '<option value="">-- Select Account --</option>';
    foreach ($glAccounts as $account) {
        $selected = ($account['account_code'] === $currentAccount) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($account['account_code']) . '"' . $selected . '>';
        echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']);
        echo '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '<td>';
    echo '<input type="text" name="description[' . htmlspecialchars($type) . ']" ';
    echo 'value="' . htmlspecialchars($currentDescription) . '" style="width: 100%;" ';
    echo 'placeholder="Optional description">';
    echo '</td>';
    echo '</tr>';
}

echo '</table>';
echo '<div style="margin-top: 20px;">';
echo '<input type="submit" name="save_mappings" value="Save Mappings" class="button">';
echo '</div>';
echo '</form>';
?>
