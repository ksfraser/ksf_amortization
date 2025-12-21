<?php
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Builders\SelectBuilder;
use Ksfraser\HTML\ScriptHandlers\AjaxSelectPopulator;

// SuiteCRM Loan Borrower Selector
// Uses AjaxSelectPopulator for clean AJAX integration

$borrowerPopulator = (new AjaxSelectPopulator())
    ->setTriggerSelectId('borrower_type')
    ->setTargetSelectId('borrower_id')
    ->setAjaxEndpoint('borrower_ajax.php?crm=suitecrm')
    ->setParameterName('type');

echo '<label for="borrower_id">Borrower (Contact):</label>';
echo (new SelectBuilder())
    ->setId('borrower_id')
    ->setName('borrower_id')
    ->addOption('', 'Select Contact')
    ->toHtml();
echo $borrowerPopulator->toHtml();
