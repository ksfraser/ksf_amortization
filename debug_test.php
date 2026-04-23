<?php
require 'vendor/autoload.php';
use Ksfraser\Amortizations\Models\Arrears;

$a = new Arrears(1);
$a->addPenalty(0.005);
echo "Penalty: " . $a->getPenaltyAmount() . "\n";
echo "Total: " . $a->getTotalAmount() . "\n";
echo "Cleared: " . ($a->isCleared() ? "true" : "false") . "\n";
