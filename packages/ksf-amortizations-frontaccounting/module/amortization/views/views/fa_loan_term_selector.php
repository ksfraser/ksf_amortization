<?php
// FA Loan Term and Payment Frequency Selector UI
// Uses Ksfraser\HTML builders for semantic HTML generation

use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;

// Build term label and input
$termLabel = (new Label())->setFor('loan_term_years')->setText('Loan Term (Years):');
$termInput = (new Input())
    ->setType('number')
    ->setName('loan_term_years')
    ->setId('loan_term_years')
    ->addAttribute('min', '1')
    ->setValue('1')
    ->addAttribute('required', 'required');

// Build frequency select
$freqLabel = (new Label())->setFor('payment_frequency')->setText('Payment Frequency:');
$freqSelect = (new Select())->setName('payment_frequency')->setId('payment_frequency')->addAttribute('onchange', 'updatePaymentsPerYear()');
$freqSelect->appendChild((new Option())->setValue('annual')->setText('Annual'));
$freqSelect->appendChild((new Option())->setValue('semi-annual')->setText('Semi-Annual'));
$freqSelect->appendChild((new Option())->setValue('monthly')->setText('Monthly'));
$freqSelect->appendChild((new Option())->setValue('semi-monthly')->setText('Semi-Monthly'));
$freqSelect->appendChild((new Option())->setValue('bi-weekly')->setText('Bi-Weekly'));
$freqSelect->appendChild((new Option())->setValue('weekly')->setText('Weekly'));

// Build hidden input
$hiddenInput = (new Input())
    ->setType('hidden')
    ->setName('payments_per_year')
    ->setId('payments_per_year')
    ->setValue('12');

// Render HTML
$termLabel->toHtml();
echo "\n";
$termInput->toHtml();
echo "\n\n";
$freqLabel->toHtml();
echo "\n";
$freqSelect->toHtml();
echo "\n\n";
$hiddenInput->toHtml();
echo "\n";

// Output JavaScript
echo "<script>\n";
echo "function updatePaymentsPerYear() {\n";
echo "  var freq = document.getElementById('payment_frequency').value;\n";
echo "  var val = 12;\n";
echo "  switch (freq) {\n";
echo "    case 'annual': val = 1; break;\n";
echo "    case 'semi-annual': val = 2; break;\n";
echo "    case 'monthly': val = 12; break;\n";
echo "    case 'semi-monthly': val = 24; break;\n";
echo "    case 'bi-weekly': val = 26; break;\n";
echo "    case 'weekly': val = 52; break;\n";
echo "  }\n";
echo "  document.getElementById('payments_per_year').value = val;\n";
echo "}\n";
echo "</script>\n";
