<?php
/**
 * User Loan Setup Form
 * 
 * Interactive form for users to create and configure new loans.
 * Includes loan term, payment frequency, and borrower type selection.
 * Uses HTML builder pattern for clean, semantic HTML generation.
 * SRP: Single responsibility of loan setup form presentation.
 * 
 * @var array $paymentFrequencies Options for payment frequency
 * @var array $borrowerTypes Options for borrower type
 */

use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Div;

// Assume payment frequencies and borrower types are passed from controller
$paymentFrequencies = $paymentFrequencies ?? [];
$borrowerTypes = $borrowerTypes ?? [];

// Build form
$form = (new Form())
    ->setMethod('POST')
    ->setId('loanSetupForm')
    ->addClass('loan-setup-form');

// Form container
$formContainer = (new Div())->addClass('form-container');

// Loan term group
$termGroup = (new Div())->addClass('form-group');
$termGroup->append((new Label())
    ->setFor('loan_term_years')
    ->setText('Loan Term (Years) *')
);
$termGroup->append((new Input())
    ->setType('number')
    ->setId('loan_term_years')
    ->setName('loan_term_years')
    ->setAttribute('min', '1')
    ->setAttribute('step', '0.25')
    ->setAttribute('placeholder', 'Enter loan term in years')
    ->setRequired(true)
);
$formContainer->append($termGroup);

// Payment frequency group
$freqGroup = (new Div())->addClass('form-group');
$freqGroup->append((new Label())
    ->setFor('payment_frequency')
    ->setText('Payment Frequency *')
);
$freqSelect = (new Select())
    ->setId('payment_frequency')
    ->setName('payment_frequency')
    ->setRequired(true);
$freqSelect->append((new Option())
    ->setValue('')
    ->setText('Select frequency')
);
foreach ($paymentFrequencies as $freq) {
    $freqSelect->append((new Option())
        ->setValue(htmlspecialchars($freq['option_value']))
        ->setText(htmlspecialchars($freq['option_name']))
    );
}
$freqGroup->append($freqSelect);
$formContainer->append($freqGroup);

// Borrower type group
$borrowerGroup = (new Div())->addClass('form-group');
$borrowerGroup->append((new Label())
    ->setFor('borrower_type')
    ->setText('Borrower Type *')
);
$borrowerSelect = (new Select())
    ->setId('borrower_type')
    ->setName('borrower_type')
    ->setRequired(true);
$borrowerSelect->append((new Option())
    ->setValue('')
    ->setText('Select borrower type')
);
foreach ($borrowerTypes as $borrower) {
    $borrowerSelect->append((new Option())
        ->setValue(htmlspecialchars($borrower['option_value']))
        ->setText(htmlspecialchars($borrower['option_name']))
    );
}
$borrowerGroup->append($borrowerSelect);
$formContainer->append($borrowerGroup);

$form->append($formContainer);

// Form actions
$actions = (new Div())->addClass('form-actions');
$actions->append(
    (new Button())
        ->setType('submit')
        ->addClass('btn btn-primary')
        ->setText('Create Loan')
);
$actions->append(
    (new Button())
        ->setType('reset')
        ->addClass('btn btn-secondary')
        ->setText('Clear')
);
$form->append($actions);

echo $form->render();
?>

<style>
    .loan-setup-form {
        max-width: 600px;
        margin: 20px 0;
    }
    
    .form-container {
        background: white;
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
    }
    
    .form-group {
        margin-bottom: 18px;
    }
    
    .form-group label {
        display: block;
        font-weight: 500;
        margin-bottom: 6px;
        color: #333;
        font-size: 14px;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
        font-family: inherit;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 5px rgba(25, 118, 210, 0.2);
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background-color: #1976d2;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #1565c0;
    }
    
    .btn-secondary {
        background-color: #757575;
        color: white;
    }
    
    .btn-secondary:hover {
        background-color: #616161;
    }
</style>
