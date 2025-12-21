<?php
/**
 * FrontAccounting Loan Borrower Selector
 * 
 * Provides AJAX-powered borrower selection interface with type filtering.
 * Allows users to select borrowers (Customers, Suppliers, Employees) for loans.
 * Uses HTML builder pattern for semantic HTML generation.
 * SRP: Single responsibility of FA borrower selection UI.
 * 
 * @var string $selectedType Currently selected borrower type
 * @var string $selectedBorrower Currently selected borrower ID
 */

use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\Paragraph;

// Get selected values if available
$selectedType = $selectedType ?? '';
$selectedBorrower = $selectedBorrower ?? '';

// Build container for accessibility
$container = (new Div())->addClass('fa-borrower-selector');

// Type selector group
$typeGroup = (new Div())->addClass('form-group');
$typeLabel = (new Label())
    ->setFor('borrower_type')
    ->setText('Borrower Type *');
$typeGroup->append($typeLabel);

$typeSelect = (new Select())
    ->setId('borrower_type')
    ->setName('borrower_type')
    ->setRequired(true)
    ->setAttribute('onchange', 'window.faFetchBorrowers ? faFetchBorrowers() : console.log("Handler not loaded")');

$typeSelect->append((new Option())
    ->setValue('')
    ->setText('Select Type')
);
$typeSelect->append((new Option())
    ->setValue('customer')
    ->setText('Customer')
    ->setSelected($selectedType === 'customer')
);
$typeSelect->append((new Option())
    ->setValue('supplier')
    ->setText('Supplier')
    ->setSelected($selectedType === 'supplier')
);
$typeSelect->append((new Option())
    ->setValue('employee')
    ->setText('Employee')
    ->setSelected($selectedType === 'employee')
);

$typeGroup->append($typeSelect);
$container->append($typeGroup);

// Borrower selector group
$borrowerGroup = (new Div())->addClass('form-group');
$borrowerLabel = (new Label())
    ->setFor('borrower_id')
    ->setText('Borrower *');
$borrowerGroup->append($borrowerLabel);

$borrowerSelect = (new Select())
    ->setId('borrower_id')
    ->setName('borrower_id')
    ->setRequired(true);

$borrowerSelect->append((new Option())
    ->setValue('')
    ->setText('Select Borrower')
);

// Add placeholder option if type is selected
if ($selectedType) {
    // TODO: Load borrowers based on selectedType
    // This would be populated via AJAX in production
    $borrowerSelect->append((new Option())
        ->setValue($selectedBorrower)
        ->setText('Loading...')
        ->setSelected(!empty($selectedBorrower))
    );
}

$borrowerGroup->append($borrowerSelect);
$container->append($borrowerGroup);

echo $container->render();
?>

<style>
    .fa-borrower-selector {
        margin: 15px 0;
    }
    
    .fa-borrower-selector .form-group {
        margin-bottom: 15px;
    }
    
    .fa-borrower-selector label {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
        color: #333;
        font-size: 14px;
    }
    
    .fa-borrower-selector select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
        font-family: inherit;
    }
    
    .fa-borrower-selector select:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 5px rgba(25, 118, 210, 0.2);
    }
    
    .fa-borrower-selector select:disabled {
        background-color: #f5f5f5;
        color: #999;
    }
</style>

<script>
/**
 * Fetch borrowers for selected type via AJAX
 * TODO: Implement actual AJAX call to borrower_ajax.php
 * Expected endpoint: borrower_ajax.php?type=customer/supplier/employee
 */
function faFetchBorrowers() {
    const typeSelect = document.getElementById('borrower_type');
    const borrowerSelect = document.getElementById('borrower_id');
    const selectedType = typeSelect.value;
    
    if (!selectedType) {
        borrowerSelect.innerHTML = '<option value="">Select Borrower</option>';
        borrowerSelect.disabled = true;
        return;
    }
    
    borrowerSelect.disabled = true;
    borrowerSelect.innerHTML = '<option value="">Loading...</option>';
    
    // TODO: Replace with actual AJAX call
    // fetch('borrower_ajax.php?type=' + encodeURIComponent(selectedType))
    //     .then(response => response.json())
    //     .then(data => {
    //         borrowerSelect.innerHTML = '<option value="">Select Borrower</option>';
    //         data.forEach(borrower => {
    //             const option = document.createElement('option');
    //             option.value = borrower.id;
    //             option.textContent = borrower.name;
    //             borrowerSelect.appendChild(option);
    //         });
    //         borrowerSelect.disabled = false;
    //     })
    //     .catch(error => {
    //         console.error('Error fetching borrowers:', error);
    //         borrowerSelect.innerHTML = '<option value="">Error loading borrowers</option>';
    //         borrowerSelect.disabled = true;
    //     });
    
    console.log('faFetchBorrowers: Implement AJAX handler for type:', selectedType);
}
</script>
