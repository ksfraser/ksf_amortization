<?php
/**
 * Admin Selector Options Management
 * 
 * Allows administrators to manage selector options for loan types, terms, etc.
 * Provides CRUD interface for selector options with HTML builder pattern.
 * Follows SRP - single responsibility of selector option management UI.
 * 
 * @var array $options List of selector options to display
 */

use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\Paragraph;

// Get FrontAccounting constants
$dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';

// Handle POST actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // TODO: Implement selector repository for database operations
        // $selectorRepo->save($_POST) or similar
    }
}

// TODO: Load options from database
$options = isset($options) ? $options : [];

// Build the page heading
echo (new Heading(2))->setText('Selector Options Management')->render();

// Build the add/edit form
$form = (new Form())
    ->setId('selectorForm')
    ->setMethod('POST');

// Add form sections
$formSection = (new Div())->addClass('form-section');

// Selector Name
$nameGroup = (new Div())->addClass('form-group');
$nameGroup->append((new Label())->setFor('selector_name')->setText('Selector Name *'));
$nameGroup->append(
    (new Input())
        ->setType('text')
        ->setId('selector_name')
        ->setName('selector_name')
        ->setAttribute('placeholder', 'e.g., Loan Type, Payment Frequency')
        ->setRequired(true)
);
$formSection->append($nameGroup);

// Option Name
$optionGroup = (new Div())->addClass('form-group');
$optionGroup->append((new Label())->setFor('option_name')->setText('Option Name *'));
$optionGroup->append(
    (new Input())
        ->setType('text')
        ->setId('option_name')
        ->setName('option_name')
        ->setAttribute('placeholder', 'e.g., Personal Loan, Monthly')
        ->setRequired(true)
);
$formSection->append($optionGroup);

// Option Value
$valueGroup = (new Div())->addClass('form-group');
$valueGroup->append((new Label())->setFor('option_value')->setText('Option Value *'));
$valueGroup->append(
    (new Input())
        ->setType('text')
        ->setId('option_value')
        ->setName('option_value')
        ->setAttribute('placeholder', 'Internal value for this option')
        ->setRequired(true)
);
$formSection->append($valueGroup);

$form->append($formSection);

// Form actions
$actions = (new Div())->addClass('form-actions');
$actions->append(
    (new Button())
        ->setType('submit')
        ->setName('action')
        ->setAttribute('value', 'add')
        ->addClass('btn btn-primary')
        ->setText('Add Option')
);
$actions->append(
    (new Button())
        ->setType('reset')
        ->addClass('btn btn-secondary')
        ->setText('Clear Form')
);

$form->append($actions);

echo $form->render();

// Build the options table
if (!empty($options)) {
    $table = (new Table())->addClass('selectors-table');
    
    // Header row
    $headerRow = (new TableRow());
    $headerRow->append((new TableData())->setText('<strong>ID</strong>'));
    $headerRow->append((new TableData())->setText('<strong>Selector Name</strong>'));
    $headerRow->append((new TableData())->setText('<strong>Option Name</strong>'));
    $headerRow->append((new TableData())->setText('<strong>Option Value</strong>'));
    $headerRow->append((new TableData())->setText('<strong>Actions</strong>'));
    $table->append($headerRow);
    
    // Data rows
    foreach ($options as $option) {
        $row = (new TableRow());
        $row->append((new TableData())->setText((string)$option['id']));
        $row->append((new TableData())->setText(htmlspecialchars($option['selector_name'])));
        $row->append((new TableData())->setText(htmlspecialchars($option['option_name'])));
        $row->append((new TableData())->setText(htmlspecialchars($option['option_value'])));
        
        // Actions cell
        $actionsCell = (new TableData());
        $actionsDiv = (new Div());
        
        $actionsDiv->append(
            (new Button())
                ->addClass('btn-small')
                ->setType('button')
                ->setAttribute('onclick', "editOption(" . intval($option['id']) . ")")
                ->setText('Edit')
        );
        
        $actionsDiv->append(
            (new Button())
                ->addClass('btn-small')
                ->setType('button')
                ->setAttribute('onclick', "deleteOption(" . intval($option['id']) . ")")
                ->setText('Delete')
        );
        
        $actionsCell->append($actionsDiv);
        $row->append($actionsCell);
        
        $table->append($row);
    }
    
    echo $table->render();
} else {
    echo (new Paragraph())->addClass('no-data')->setText('No selector options found. Create your first option above.')->render();
}
?>

<style>
    .form-section {
        background: white;
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
        color: #333;
    }
    
    .form-group input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 5px rgba(25, 118, 210, 0.2);
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
    }
    
    .btn {
        padding: 10px 20px;
        background-color: #1976d2;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }
    
    .btn:hover {
        background-color: #1565c0;
    }
    
    .btn-secondary {
        background-color: #757575;
    }
    
    .btn-secondary:hover {
        background-color: #616161;
    }
    
    .btn-small {
        padding: 6px 12px;
        font-size: 12px;
        margin: 0 2px;
    }
    
    .selectors-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .selectors-table tbody tr:first-child {
        background-color: #f5f5f5;
        font-weight: bold;
    }
    
    .selectors-table tr {
        border-bottom: 1px solid #eee;
    }
    
    .selectors-table td {
        padding: 12px;
        font-size: 14px;
    }
    
    .selectors-table tbody tr:hover {
        background-color: #f9f9f9;
    }
    
    .no-data {
        text-align: center;
        color: #999;
        padding: 40px;
        background-color: #f9f9f9;
        border-radius: 4px;
        margin-top: 20px;
    }
</style>

<script>
function editOption(id) {
    // TODO: Implement edit functionality
    console.log('Edit option:', id);
}

function deleteOption(id) {
    if (confirm('Delete this selector option?')) {
        // TODO: Implement delete functionality
        console.log('Delete option:', id);
    }
}
</script>
