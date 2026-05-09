<?php
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Installation and Status Check View
 * 
 * Displays database schema status and provides install/update functionality
 * @package AmortizationModule
 */

use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\HtmlParagraph;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\TableHeader;
use Ksfraser\HTML\Elements\HtmlA;
use Ksfraser\HTML\Elements\Div;

global $db;

// Handle install action
if (isset($_POST['action']) && $_POST['action'] === 'install_schema') {
    $schemaDir = __DIR__ . '/../vendor/ksfraser/amortizations-core/';
    $schemaFiles = [
        $schemaDir . 'schema.sql',
        $schemaDir . 'schema_selectors.sql',
        $schemaDir . 'schema_events.sql',
        $schemaDir . 'schema_delinquency.sql',
    ];
    $dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';
    $allSuccess = true;
    foreach ($schemaFiles as $schemaPath) {
        if (file_exists($schemaPath)) {
            try {
                $sql = file_get_contents($schemaPath);
                $sql = str_replace(['{PREFIX}', '&TB_PREF&'], [$dbPrefix, $dbPrefix], $sql);
                // Execute SQL - use multi_query for multiple statements
                if (method_exists($db, 'multi_query')) {
                    if ($db->multi_query($sql)) {
                        do {
                            if ($result = $db->store_result()) {
                                $result->free();
                            }
                        } while ($db->more_results() && $db->next_result());
                    } else {
                        // Always suppress 'already exists' errors for CREATE TABLE IF NOT EXISTS
                        if (strpos($db->error, 'already exists') !== false && strpos($sql, 'CREATE TABLE IF NOT EXISTS') !== false) {
                            // Do not mark as failure, do not show error
                        } else {
                            $allSuccess = false;
                            echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #721c24;">';
                            echo '<strong>Error in ' . htmlspecialchars(basename($schemaPath)) . ':</strong> ' . htmlspecialchars($db->error);
                            echo '</div>';
                        }
                    }
                } else {
                    // Fallback for PDO or other DB drivers
                    $statements = explode(';', $sql);
                    foreach ($statements as $stmt) {
                        $stmt = trim($stmt);
                        if ($stmt) {
                            try {
                                $db->query($stmt);
                            } catch (Exception $e) {
                                $msg = $e->getMessage();
                                if (strpos($msg, 'already exists') !== false && strpos($stmt, 'IF NOT EXISTS') !== false) {
                                    // Suppress harmless error
                                    continue;
                                }
                                $allSuccess = false;
                                echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #721c24;">';
                                echo '<strong>Error in ' . htmlspecialchars(basename($schemaPath)) . ':</strong> ' . htmlspecialchars($msg);
                                echo '</div>';
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $allSuccess = false;
                echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #721c24;">';
                echo '<strong>Error in ' . htmlspecialchars(basename($schemaPath)) . ':</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        } else {
            $allSuccess = false;
            echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; color: #856404;">';
            echo '<strong>Warning:</strong> Schema file not found at: ' . htmlspecialchars($schemaPath);
            echo '</div>';
        }
    }
    if ($allSuccess) {
        echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; color: #155724;">';
        echo '<strong>Success:</strong> All database schemas installed successfully.';
        echo '</div>';
    }
}

echo (new Heading(2))->setText('Installation Status')->render();

// Debug info - use direct HTML with styling
echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 10px 0; border-radius: 5px; font-family: monospace;">';

$debugInfo = '<strong>System Information:</strong><br>';
$debugInfo .= 'PHP Version: ' . PHP_VERSION . '<br>';
$debugInfo .= 'Composer Vendor Path: ' . realpath(__DIR__ . '/../vendor') . '<br>';
$debugInfo .= 'DB Connection: ' . (isset($db) && $db ? 'Available' : 'Not Available') . '<br>';
$debugInfo .= 'DB Prefix: ' . (defined('TB_PREF') ? TB_PREF : '0_') . '<br>';
$debugInfo .= 'Module Path: ' . __DIR__ . '<br><br>';

if (isset($db) && $db) {
    try {
        // Check which tables exist
        $dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';
        $requiredTables = [
            'ksf_amortizations',
            'ksf_amortization_schedules',
            'ksf_loan_types',
            'ksf_selectors',
            'ksf_gl_mappings',
            'ksf_amortization_staging'
        ];
        
        $debugInfo .= '<strong>Database Tables:</strong><br>';
        $tableStatus = [];
        
        foreach ($requiredTables as $table) {
            $fullTableName = $dbPrefix . $table;
            $result = $db->query("SHOW TABLES LIKE '$fullTableName'");
            $exists = $result && $result->num_rows > 0;
            $tableStatus[$table] = $exists;
            
            $statusIcon = $exists ? '✓' : '✗';
            $statusColor = $exists ? 'green' : 'red';
            $debugInfo .= sprintf(
                '<span style="color: %s;">%s</span> %s<br>',
                $statusColor,
                $statusIcon,
                htmlspecialchars($fullTableName)
            );
        }
        
        $allTablesExist = !in_array(false, $tableStatus, true);
        
        $debugInfo .= '<br><strong>Installation Status: </strong>';
        if ($allTablesExist) {
            $debugInfo .= '<span style="color: green; font-weight: bold;">✓ INSTALLED</span><br>';
        } else {
            $debugInfo .= '<span style="color: red; font-weight: bold;">✗ NOT INSTALLED</span><br>';
            $debugInfo .= '<br>';
            
            // Show install button
            $debugInfo .= '<form method="post" style="margin-top: 10px;">';
            $debugInfo .= '<input type="hidden" name="action" value="install_schema">';
            $debugInfo .= '<button type="submit" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">';
            $debugInfo .= 'Install Database Schema';
            $debugInfo .= '</button>';
            $debugInfo .= '</form>';
        }
        
        // Check for data
        if ($allTablesExist) {
            $debugInfo .= '<br><strong>Data Check:</strong><br>';
            
            // Check loans
            $result = $db->query("SELECT COUNT(*) as count FROM {$dbPrefix}ksf_amortizations");
            $loanCount = $result ? $result->fetch_assoc()['count'] : 0;
            $debugInfo .= "Loans: $loanCount<br>";
            
            // Check selectors
            $result = $db->query("SELECT COUNT(*) as count FROM {$dbPrefix}ksf_selectors");
            $selectorCount = $result ? $result->fetch_assoc()['count'] : 0;
            $debugInfo .= "Selectors: $selectorCount<br>";
            
            if ($selectorCount == 0) {
                $debugInfo .= '<span style="color: orange;">⚠ No selector data found. Visit Admin → Manage Selectors to add data.</span><br>';
            }
        }
        
    } catch (Exception $e) {
        $debugInfo .= '<br><span style="color: red;">Error checking database: ' . htmlspecialchars($e->getMessage()) . '</span><br>';
    }
} else {
    $debugInfo .= '<span style="color: red;">Database connection not available!</span><br>';
}

echo $debugInfo;
echo '</div>'; // Close debug div

// Schema file check
echo '<div style="background: #fff; border: 1px solid #dee2e6; padding: 15px; margin: 10px 0; border-radius: 5px;">';

$schemaInfo = '<strong>Schema Files:</strong><br>';
$schemaFiles = [
    'schema.sql' => 'Main schema (tables)',
    'schema_selectors.sql' => 'Selector data',
    'schema_events.sql' => 'Event system',
    'schema_delinquency.sql' => 'Delinquency tracking'
];

foreach ($schemaFiles as $file => $description) {
    $path = __DIR__ . '/../vendor/ksfraser/amortizations-core/' . $file;
    $exists = file_exists($path);
    $statusIcon = $exists ? '✓' : '✗';
    $statusColor = $exists ? 'green' : 'red';
    $schemaInfo .= sprintf(
        '<span style="color: %s;">%s</span> %s - %s<br>',
        $statusColor,
        $statusIcon,
        htmlspecialchars($file),
        htmlspecialchars($description)
    );
}

echo $schemaInfo;
echo '</div>'; // Close schema div

// Navigation
echo '<div style="margin-top: 20px;">';
echo '<a href="?action=default">← Back to Loans</a>';
echo '</div>';
