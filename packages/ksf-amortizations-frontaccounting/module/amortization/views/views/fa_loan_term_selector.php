<?php
// FA Loan Term and Payment Frequency Selector UI
// Uses Ksfraser\HTML builders for semantic HTML generation

use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Hidden;
use Ksfraser\HTML\Elements\PaymentFrequencyHandler;

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
$freqSelect = (new Select('payment_frequency'))
    ->setId('payment_frequency')
    ->addAttribute('onchange', 'updatePaymentsPerYear()')
    ->addOptionsFromArray([
        'annual' => 'Annual',
        'semi-annual' => 'Semi-Annual',
        'monthly' => 'Monthly',
        'semi-monthly' => 'Semi-Monthly',
        'bi-weekly' => 'Bi-Weekly',
        'weekly' => 'Weekly'
    ]);

// Build hidden input
$hiddenInput = (new Hidden())
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

// Output JavaScript using specialized handler class
$handler = new PaymentFrequencyHandler();
$handler->toHtml();
