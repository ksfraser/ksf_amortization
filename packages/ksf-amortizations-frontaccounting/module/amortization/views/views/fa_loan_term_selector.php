<?php
/**
 * FrontAccounting Loan Term and Payment Frequency Selector
 * 
 * Provides interface for users to select loan term and payment frequency.
 * Updates hidden field with calculated payments per year based on frequency.
 * Uses HTML builder pattern for semantic HTML generation.
 * SRP: Single responsibility of term/frequency selection UI.
 * 
 * @var int $loanTermYears Current loan term in years
 * @var string $paymentFrequency Currently selected payment frequency
 * @var int $paymentsPerYear Calculated payments per year
 */

use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\Div;

// Get passed values or defaults
$loanTermYears = $loanTermYears ?? 1;
$paymentFrequency = $paymentFrequency ?? 'monthly';
$paymentsPerYear = $paymentsPerYear ?? 12;

// Build container
$container = (new Div())->addClass('fa-term-selector');

// Loan term group
$termGroup = (new Div())->addClass('form-group');
$termLabel = (new Label())
    ->setFor('loan_term_years')
    ->setText('Loan Term (Years) *');
$termGroup->append($termLabel);

$termInput = (new Input())
    ->setType('number')
    ->setId('loan_term_years')
    ->setName('loan_term_years')
    ->setAttribute('min', '0.25')
    ->setAttribute('step', '0.25')
    ->setAttribute('value', (string)$loanTermYears)
    ->setAttribute('placeholder', 'Enter loan term in years')
    ->setRequired(true)
    ->setAttribute('onchange', 'window.updatePaymentsPerYear ? updatePaymentsPerYear() : console.log("Handler not loaded")');

$termGroup->append($termInput);
$container->append($termGroup);

// Payment frequency group
$freqGroup = (new Div())->addClass('form-group');
$freqLabel = (new Label())
    ->setFor('payment_frequency')
    ->setText('Payment Frequency *');
$freqGroup->append($freqLabel);

$freqSelect = (new Select())
    ->setId('payment_frequency')
    ->setName('payment_frequency')
    ->setRequired(true)
    ->setAttribute('onchange', 'window.updatePaymentsPerYear ? updatePaymentsPerYear() : console.log("Handler not loaded")');

$frequencyOptions = [
    'annual' => 'Annual (1x)',
    'semi-annual' => 'Semi-Annual (2x)',
    'quarterly' => 'Quarterly (4x)',
    'monthly' => 'Monthly (12x)',
    'semi-monthly' => 'Semi-Monthly (24x)',
    'bi-weekly' => 'Bi-Weekly (26x)',
    'weekly' => 'Weekly (52x)'
];

foreach ($frequencyOptions as $value => $label) {
    $freqSelect->append((new Option())
        ->setValue($value)
        ->setText($label)
        ->setSelected($paymentFrequency === $value)
    );
}

$freqGroup->append($freqSelect);
$container->append($freqGroup);

echo $container->render();

// Hidden field to store calculated payments per year
echo (new Input())
    ->setType('hidden')
    ->setId('payments_per_year')
    ->setName('payments_per_year')
    ->setAttribute('value', (string)$paymentsPerYear)
    ->render();
?>

<style>
    .fa-term-selector {
        margin: 15px 0;
    }
    
    .fa-term-selector .form-group {
        margin-bottom: 15px;
    }
    
    .fa-term-selector label {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
        color: #333;
        font-size: 14px;
    }
    
    .fa-term-selector input,
    .fa-term-selector select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
        font-family: inherit;
    }
    
    .fa-term-selector input:focus,
    .fa-term-selector select:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 5px rgba(25, 118, 210, 0.2);
    }
</style>

<script>
/**
 * Update payments per year based on selected frequency
 * TODO: Trigger recalculation of amortization schedule
 */
function updatePaymentsPerYear() {
    const freqSelect = document.getElementById('payment_frequency');
    const paymentsField = document.getElementById('payments_per_year');
    
    const frequencyMap = {
        'annual': 1,
        'semi-annual': 2,
        'quarterly': 4,
        'monthly': 12,
        'semi-monthly': 24,
        'bi-weekly': 26,
        'weekly': 52
    };
    
    const frequency = freqSelect.value;
    const paymentsPerYear = frequencyMap[frequency] || 12;
    
    paymentsField.value = paymentsPerYear;
    
    // TODO: Trigger recalculation event
    // const event = new CustomEvent('frequencyChanged', { 
    //     detail: { frequency: frequency, paymentsPerYear: paymentsPerYear }
    // });
    // document.dispatchEvent(event);
    
    console.log('updatePaymentsPerYear:', frequency, '→', paymentsPerYear, 'payments/year');
}

// Initialize payments per year on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePaymentsPerYear();
});
</script>
