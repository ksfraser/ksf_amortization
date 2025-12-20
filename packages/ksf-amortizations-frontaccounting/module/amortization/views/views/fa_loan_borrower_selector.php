<?php
// FA Loan Borrower Selector UI
// Uses Ksfraser\HTML builders for semantic HTML generation

use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\AjaxSelectPopulator;

// Build type selector
$typeLabel = (new Label())->setFor('borrower_type')->setText('Borrower Type:');
$typeSelect = (new Select())->setName('borrower_type')->setId('borrower_type')->addAttribute('onchange', 'faFetchBorrowers()');
$typeSelect->appendChild((new Option())->setValue('')->setText('Select Type'));
$typeSelect->appendChild((new Option())->setValue('Customer')->setText('Customer'));
$typeSelect->appendChild((new Option())->setValue('Supplier')->setText('Supplier'));
$typeSelect->appendChild((new Option())->setValue('Employee')->setText('Employee'));

// Build borrower selector
$borrowerLabel = (new Label())->setFor('borrower_id')->setText('Borrower:');
$borrowerSelect = (new Select())->setName('borrower_id')->setId('borrower_id');
$borrowerSelect->appendChild((new Option())->setValue('')->setText('Select Borrower'));

// Render HTML
$typeLabel->toHtml();
echo "\n";
$typeSelect->toHtml();
echo "\n\n";
$borrowerLabel->toHtml();
echo "\n";
$borrowerSelect->toHtml();
echo "\n";

// Output JavaScript using specialized populator class
$populator = (new AjaxSelectPopulator())
    ->setFunctionName('faFetchBorrowers')
    ->setSourceFieldId('borrower_type')
    ->setTargetFieldId('borrower_id')
    ->setEndpoint('borrower_ajax.php')
    ->setQueryParam('type')
    ->setPlaceholder('Select Borrower');
$populator->toHtml();
