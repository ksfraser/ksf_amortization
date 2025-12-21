<?php
use Ksfraser\HTML\Builders\SelectBuilder;
use Ksfraser\HTML\ScriptHandlers\AjaxSelectPopulator;

// WordPress Loan Borrower Selector
// Uses AjaxSelectPopulator for clean AJAX integration

$borrowerPopulator = (new AjaxSelectPopulator())
    ->setTriggerSelectId('borrower_type')
    ->setTargetSelectId('borrower_id')
    ->setAjaxEndpoint('borrower_ajax.php?crm=wordpress')
    ->setParameterName('type');

echo '<label for="borrower_id">Borrower (User):</label>';
echo (new SelectBuilder())
    ->setId('borrower_id')
    ->setName('borrower_id')
    ->addOption('', 'Select User')
    ->toHtml();
echo $borrowerPopulator->toHtml();
