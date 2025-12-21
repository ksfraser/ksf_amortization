<?php
use Ksfraser\HTML\Elements\HtmlInput;
use Ksfraser\HTML\Elements\HtmlForm;
use Ksfraser\HTML\Builders\SelectBuilder;
use Ksfraser\Amortizations\SelectorModel;

// User Loan Setup Form with modern patterns
$selectorModel = new SelectorModel($db);
$paymentFrequencies = $selectorModel->getOptions('payment_frequency');
$borrowerTypes = $selectorModel->getOptions('borrower_type');

$form = (new HtmlForm())->setMethod('post');

// Loan term field
$form->addChild(new HtmlInput()
    ->setType('number')
    ->setName('loan_term_years')
    ->setId('loan_term_years')
    ->setAttributes(['min' => '1', 'value' => '1', 'required' => 'required']));

// Payment frequency select
$freqSelect = (new SelectBuilder())
    ->setId('payment_frequency')
    ->setName('payment_frequency')
    ->addOptionsFromArray($paymentFrequencies, 'option_value', 'option_name');
$form->addChild($freqSelect);

// Borrower type select
$borrowerSelect = (new SelectBuilder())
    ->setId('borrower_type')
    ->setName('borrower_type')
    ->addOptionsFromArray($borrowerTypes, 'option_value', 'option_name');
$form->addChild($borrowerSelect);

// Submit button
$form->addChild(new HtmlInput()
    ->setType('submit')
    ->setName('submit')
    ->setAttribute('value', 'Submit'));

echo $form->toHtml();
