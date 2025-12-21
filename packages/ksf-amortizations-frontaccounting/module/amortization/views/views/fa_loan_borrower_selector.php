<?php
/**
 * FrontAccounting Loan Borrower Selector
 * 
 * Provides AJAX-powered borrower selection interface with type filtering.
 * Allows users to select borrowers (Customers, Suppliers, Employees) for loans.
 * Uses HTML builder pattern for semantic HTML generation and AjaxSelectPopulator for AJAX logic.
 * SRP: Single responsibility of FA borrower selection UI.
 * 
 * @var string $selectedType Currently selected borrower type
 * @var string $selectedBorrower Currently selected borrower ID
 */

use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\AjaxSelectPopulator;
use Ksfraser\HTML\Elements\HtmlString;

// Get selected values if available
$selectedType = $selectedType ?? '';
$selectedBorrower = $selectedBorrower ?? '';

// Build container for accessibility
$container = (new Div())->addClass('fa-borrower-selector');

// Type selector group
$typeGroup = (new Div())->addClass('form-group');
$typeGroup->append((new Label())
    ->setFor('borrower_type')
    ->setText('Borrower Type *')
);

$typeSelect = (new Select())
    ->setId('borrower_type')
    ->setName('borrower_type')
    ->setRequired(true)
    ->append((new Option())->setValue('')->setText('Select Type'))
    ->append((new Option())->setValue('customer')->setText('Customer')->setSelected($selectedType === 'customer'))
    ->append((new Option())->setValue('supplier')->setText('Supplier')->setSelected($selectedType === 'supplier'))
    ->append((new Option())->setValue('employee')->setText('Employee')->setSelected($selectedType === 'employee'));

$typeGroup->append($typeSelect);
$container->append($typeGroup);

// Borrower selector group
$borrowerGroup = (new Div())->addClass('form-group');
$borrowerGroup->append((new Label())
    ->setFor('borrower_id')
    ->setText('Borrower *')
);

$borrowerSelect = (new Select())
    ->setId('borrower_id')
    ->setName('borrower_id')
    ->setRequired(true)
    ->append((new Option())->setValue('')->setText('Select Borrower'));

if ($selectedType) {
    $borrowerSelect->append((new Option())
        ->setValue($selectedBorrower)
        ->setText('Loading...')
        ->setSelected(!empty($selectedBorrower))
    );
}

$borrowerGroup->append($borrowerSelect);
$container->append($borrowerGroup);

echo $container->toHtml();
?>

<?php
// Generate AJAX populator for borrower selection
$ajaxPopulator = (new AjaxSelectPopulator())
    ->setTriggerSelectId('borrower_type')
    ->setTargetSelectId('borrower_id')
    ->setAjaxEndpoint('borrower_ajax.php')
    ->setParameterName('type');

echo $ajaxPopulator->toHtml();
?>
