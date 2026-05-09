<?php
use Ksfraser\HTML\Elements\HtmlInput;
use Ksfraser\HTML\Builders\SelectBuilder;
use Ksfraser\HTML\ScriptHandlers\PaymentFrequencyHandler;

// WordPress Loan Term and Payment Frequency Selector
// Uses PaymentFrequencyHandler for clean frequency management

$freqHandler = (new PaymentFrequencyHandler())->setSelectedFrequency('monthly');

echo '<label for="loan_term_years">Loan Term (Years):</label>';
echo (new HtmlInput())->setType('number')->setName('loan_term_years')
    ->setId('loan_term_years')->setAttributes(['min' => '1', 'value' => '1', 'required' => 'required'])->toHtml();

echo '<label for="payment_frequency">Payment Frequency:</label>';
echo (new SelectBuilder())
    ->setId('payment_frequency')
    ->setName('payment_frequency')
    ->addOptionsFromArray($freqHandler->getFrequencyOptions())
    ->toHtml();

echo (new HtmlInput())->setType('hidden')->setName('payments_per_year')
    ->setId('payments_per_year')->setAttribute('value', '12')->toHtml();

echo $freqHandler->toHtml();
