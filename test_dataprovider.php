<?php
require 'vendor/autoload.php';

$test = new \Ksfraser\Amortizations\Tests\Unit\PaymentCalculatorTest();
$data = $test->supportedFrequencies();

echo "Data provider returned: \n";
var_dump($data);
echo "\nNumber of items: " . count($data) . "\n";
