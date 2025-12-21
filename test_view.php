<?php
require 'vendor/autoload.php';

try {
    $output = \Ksfraser\Amortizations\Views\ReportingTable::render([]);
    echo "Success! Output length: " . strlen($output) . "\n";
    echo "First 200 chars:\n";
    echo substr($output, 0, 200) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
