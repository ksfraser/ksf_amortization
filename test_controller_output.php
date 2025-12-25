<?php

/**
 * Test controller output
 */

$path_to_root = '';
$db = null;

// Capture output
ob_start();
include 'modules/amortization/controller.php';
$output = ob_get_clean();

// Simple checks
$checks = [
    'Has h2 heading' => strpos($output, '<h2') !== false && strpos($output, 'Amortization Loans') !== false,
    'Has module-nav div' => strpos($output, 'module-nav') !== false,
    'Has 4 links' => substr_count($output, '<a ') >= 4,
    'Has "Add New Loan" link' => strpos($output, 'Add New Loan') !== false,
    'Has "Admin Settings" link' => strpos($output, 'Admin Settings') !== false,
    'Has "Manage Selectors" link' => strpos($output, 'Manage Selectors') !== false,
    'Has "Reports" link' => strpos($output, 'Reports') !== false,
    'Has paragraph text' => strpos($output, '<p') !== false && strpos($output, 'coming soon') !== false,
];

echo "=== Controller Output Tests ===\n";
$passed = 0;
$failed = 0;

foreach ($checks as $test => $result) {
    $status = $result ? '✓ PASS' : '✗ FAIL';
    echo "$status: $test\n";
    if ($result) $passed++; else $failed++;
}

echo "\n=== Results ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
} else {
    echo "\n✗ Some tests failed\n";
    echo "\n=== Output ===\n";
    echo htmlspecialchars($output);
}
?>
