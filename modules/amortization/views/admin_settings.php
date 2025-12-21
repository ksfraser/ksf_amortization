<?php
// Admin Settings View - GL Account Configuration
// This view handles GL account selector configuration for amortization module

use Ksfraser\HTML\Elements\HtmlForm;
use Ksfraser\HTML\Elements\HtmlInput;
use Ksfraser\HTML\Builders\SelectBuilder;
use Ksfraser\Amortizations\Repositories\GLAccountRepository;

// Initialize GL account repository
$glRepository = new GLAccountRepository();

// Get GL accounts by category
$liabilityGLs = $glRepository->getAccountsByClass(CL_LIABILITIES);
$assetGLs = $glRepository->getAccountsByClass(CL_ASSETS);
$expenseGLs = $glRepository->getAccountsByClass(CL_AMORTIZATION);
$assetValueGLs = $glRepository->getAccountsByClass(CL_FIXEDASSETS);

// Build form using modern patterns
$form = (new HtmlForm())->setMethod('post');

// Liability GL selector
$form->addChild((new SelectBuilder())
    ->setId('liability_gl')
    ->setName('liability_gl')
    ->addOptionsFromArray($liabilityGLs, 'account_code', 'account_name'));

// Asset GL selector
$form->addChild((new SelectBuilder())
    ->setId('asset_gl')
    ->setName('asset_gl')
    ->addOptionsFromArray($assetGLs, 'account_code', 'account_name'));

// Expense GL selector
$form->addChild((new SelectBuilder())
    ->setId('expenses_gl')
    ->setName('expenses_gl')
    ->addOptionsFromArray($expenseGLs, 'account_code', 'account_name'));

// Asset Value GL selector
$form->addChild((new SelectBuilder())
    ->setId('asset_value_gl')
    ->setName('asset_value_gl')
    ->addOptionsFromArray($assetValueGLs, 'account_code', 'account_name'));

// Submit button
$form->addChild(new HtmlInput()
    ->setType('submit')
    ->setName('submit')
    ->setAttribute('value', 'Save Settings'));

echo $form->toHtml();
