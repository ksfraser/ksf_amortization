<?php
// User Loan Setup Form using SelectorModel for choices
// Uses Ksfraser\HTML builders for semantic HTML generation

use Ksfraser\Amortizations\SelectorModel;
use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\Button;

$selectorModel = new SelectorModel($db);
$paymentFrequencies = $selectorModel->getOptions('payment_frequency');
$borrowerTypes = $selectorModel->getOptions('borrower_type');

// Build form
$form = (new Form())->setMethod('post');

// Loan term field
$form->appendChild((new Label())->setFor('loan_term_years')->setText('Loan Term (Years):'));
$form->appendChild((new Input())
    ->setType('number')
    ->setName('loan_term_years')
    ->setId('loan_term_years')
    ->addAttribute('min', '1')
    ->setValue('1')
    ->addAttribute('required', 'required')
);

// Payment frequency select
$form->appendChild((new Label())->setFor('payment_frequency')->setText('Payment Frequency:'));
$freqSelect = (new Select())->setName('payment_frequency')->setId('payment_frequency');
foreach ($paymentFrequencies as $opt) {
    $freqSelect->appendChild((new Option())
        ->setValue(htmlspecialchars($opt['option_value']))
        ->setText(htmlspecialchars($opt['option_name']))
    );
}
$form->appendChild($freqSelect);

// Borrower type select
$form->appendChild((new Label())->setFor('borrower_type')->setText('Borrower Type:'));
$borrowerSelect = (new Select())->setName('borrower_type')->setId('borrower_type');
foreach ($borrowerTypes as $opt) {
    $borrowerSelect->appendChild((new Option())
        ->setValue(htmlspecialchars($opt['option_value']))
        ->setText(htmlspecialchars($opt['option_name']))
    );
}
$form->appendChild($borrowerSelect);

// Submit button
$form->appendChild((new Button())->setType('submit')->setText('Submit'));

$form->toHtml();
