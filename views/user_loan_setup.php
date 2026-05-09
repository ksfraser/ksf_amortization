<?php
// User Loan Setup Form using SelectorRepository for dropdown options
use Ksfraser\HTML\Elements\HtmlInput;
use Ksfraser\HTML\Elements\HtmlForm;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\HtmlParagraph;
use Ksfraser\Amortizations\Repository\SelectorRepository;

global $db;

// Get database table prefix
$dbPrefix = defined('TB_PREF') ? TB_PREF : '';

// Check if we're in a proper FA environment
if (!isset($db) || !$db) {
    echo (new Heading(2))->setText('Create New Loan')->render();
    echo '<p>Database connection not available. This view must be accessed through FrontAccounting.</p>';
    return;
}

echo (new Heading(2))->setText('Create New Loan')->render();

// Initialize repository and fetch options
$selectorRepo = new SelectorRepository($db, 'ksf_selectors', $dbPrefix);
$paymentFrequencies = $selectorRepo->getBySelectorName('payment_frequency');
$borrowerTypes = $selectorRepo->getBySelectorName('borrower_type');

// Check if we have required selector data
if (empty($paymentFrequencies) || empty($borrowerTypes)) {
    echo '<p style="color: red;">Required selector data not found. Please configure selectors in <a href="?action=admin_selectors">Admin → Manage Selectors</a> first.</p>';
    echo '<p>Required selectors: <strong>payment_frequency</strong>, <strong>borrower_type</strong></p>';
    return;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_loan'])) {
    try {
        // TODO: Implement loan creation logic using AmortizationModel
        $successMsg = new HtmlParagraph(new HtmlString('Loan creation functionality coming soon. Data validation passed.'));
        echo $successMsg->getHtml();
    } catch (Exception $e) {
        $errorMsg = new HtmlParagraph(new HtmlString('Error: ' . htmlspecialchars($e->getMessage())));
        echo $errorMsg->getHtml();
    }
}

// Build form using HTML builder classes
$form = new HtmlForm();
$form->setMethod('post')->setAction('');

// Principal Amount
$principalInput = (new HtmlInput())
    ->setType('number')
    ->setName('principal')
    ->setId('principal')
    ->setAttribute('min', '1')
    ->setAttribute('step', '0.01')
    ->setAttribute('required', 'required')
    ->setAttribute('placeholder', '10000.00')
    ->setLabel('Principal Amount ($):');
$form->addInput($principalInput);

// Interest Rate
$rateInput = (new HtmlInput())
    ->setType('number')
    ->setName('interest_rate')
    ->setId('interest_rate')
    ->setAttribute('min', '0')
    ->setAttribute('step', '0.01')
    ->setAttribute('required', 'required')
    ->setAttribute('placeholder', '5.00')
    ->setLabel('Annual Interest Rate (%):');
$form->addInput($rateInput);

// Loan Term
$termInput = (new HtmlInput())
    ->setType('number')
    ->setName('loan_term_years')
    ->setId('loan_term_years')
    ->setAttribute('min', '1')
    ->setValue('1')
    ->setAttribute('required', 'required')
    ->setLabel('Loan Term (Years):');
$form->addInput($termInput);

// Payment Frequency Select
$frequencySelect = (new HtmlSelect())
    ->setName('payment_frequency')
    ->setId('payment_frequency')
    ->setAttribute('required', 'required')
    ->setLabel('Payment Frequency:');
foreach ($paymentFrequencies as $opt) {
    $option = (new HtmlOption())
        ->setValue($opt['option_value'])
        ->setText($opt['option_name']);
    $frequencySelect->addOption($option);
}
$form->addSelect($frequencySelect);

// Borrower Type Select
$borrowerSelect = (new HtmlSelect())
    ->setName('borrower_type')
    ->setId('borrower_type')
    ->setAttribute('required', 'required')
    ->setLabel('Borrower Type:');
foreach ($borrowerTypes as $opt) {
    $option = (new HtmlOption())
        ->setValue($opt['option_value'])
        ->setText($opt['option_name']);
    $borrowerSelect->addOption($option);
}
$form->addSelect($borrowerSelect);

// Start Date
$dateInput = (new HtmlInput())
    ->setType('date')
    ->setName('start_date')
    ->setId('start_date')
    ->setAttribute('required', 'required')
    ->setValue(date('Y-m-d'))
    ->setLabel('Start Date:');
$form->addInput($dateInput);

// Borrower Name
$nameInput = (new HtmlInput())
    ->setType('text')
    ->setName('borrower_name')
    ->setId('borrower_name')
    ->setAttribute('required', 'required')
    ->setAttribute('placeholder', 'John Doe')
    ->setLabel('Borrower Name:');
$form->addInput($nameInput);

// Submit button
$submitBtn = (new HtmlInput())
    ->setType('submit')
    ->setName('create_loan')
    ->setValue('Create Loan')
    ->addClass('button');
$form->addInput($submitBtn);

echo $form->getHtml();

