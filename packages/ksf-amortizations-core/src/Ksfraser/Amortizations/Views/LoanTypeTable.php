<?php
namespace Ksfraser\Amortizations\Views;

use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\TableHeader;
use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Div;

/**
 * LoanTypeTable - Displays and manages loan types
 * 
 * Provides table view of all loan types with add/edit/delete functionality.
 * Uses HTML builder pattern for clean, maintainable code.
 * SRP: Single responsibility of loan type table presentation.
 * 
 * @package Ksfraser\Amortizations\Views
 */
class LoanTypeTable {
    /**
     * Render loan types table with management interface
     * 
     * @param array $loanTypes Array of loan type objects
     * @return string HTML rendering of the table and form
     */
    public static function render(array $loanTypes = []): string {
        $output = '';
        
        // Load stylesheets
        $output .= self::getStylesheets();
        
        // Build heading
        $heading = (new Heading(3))->setText('Loan Types');
        $output .= $heading->render();
        
        // Build table
        $table = (new Table())->addClass('loan-types-table');
        
        // Header row
        $headerRow = (new TableRow())->addClass('header-row');
        $headerRow->append(
            (new TableHeader())->setText('ID'),
            (new TableHeader())->setText('Name'),
            (new TableHeader())->setText('Description'),
            (new TableHeader())->setText('Actions')
        );
        $table->append($headerRow);
        
        // Data rows
        foreach ($loanTypes as $type) {
            $row = (new TableRow())->addClass('data-row');
            
            // ID cell
            $row->append((new TableData())
                ->addClass('id-cell')
                ->setText((string)($type->id ?? 'N/A'))
            );
            
            // Name cell
            $row->append((new TableData())
                ->addClass('name-cell')
                ->setText(htmlspecialchars($type->name ?? ''))
            );
            
            // Description cell
            $row->append((new TableData())
                ->addClass('description-cell')
                ->setText(htmlspecialchars($type->description ?? ''))
            );
            
            // Actions cell
            $actionsCell = (new TableData())->addClass('actions-cell');
            $actionsDiv = (new Div())->addClass('action-buttons');
            
            // Edit button
            $editBtn = (new Button())
                ->setType('button')
                ->addClass('btn-small btn-edit')
                ->setText('Edit')
                ->setAttribute('onclick', 'window.editLoanType ? editLoanType(' . intval($type->id ?? 0) . ') : console.log("Handler not loaded")');
            $actionsDiv->append($editBtn);
            
            // Delete button
            $deleteBtn = (new Button())
                ->setType('button')
                ->addClass('btn-small btn-delete')
                ->setText('Delete')
                ->setAttribute('onclick', 'window.deleteLoanType ? deleteLoanType(' . intval($type->id ?? 0) . ') : console.log("Handler not loaded")');
            $actionsDiv->append($deleteBtn);
            
            $actionsCell->append($actionsDiv);
            $row->append($actionsCell);
            
            $table->append($row);
        }
        
        $output .= $table->render();
        
        // Build add form
        $form = (new Form())
            ->setMethod('POST')
            ->addClass('add-loan-type-form');
        
        $formContainer = (new Div())->addClass('form-container');
        
        // Loan type name input
        $nameGroup = (new Div())->addClass('form-group');
        $nameGroup->append((new Input())
            ->setType('text')
            ->setName('loan_type_name')
            ->setAttribute('placeholder', 'New Loan Type')
            ->setRequired(true)
        );
        $formContainer->append($nameGroup);
        
        // Description input
        $descGroup = (new Div())->addClass('form-group');
        $descGroup->append((new Input())
            ->setType('text')
            ->setName('loan_type_desc')
            ->setAttribute('placeholder', 'Description')
            ->setRequired(true)
        );
        $formContainer->append($descGroup);
        
        // Submit button
        $submitBtn = (new Button())
            ->setType('submit')
            ->addClass('btn btn-primary')
            ->setText('Add Loan Type');
        $formContainer->append($submitBtn);
        
        $form->append($formContainer);
        $output .= $form->render();
        
        // Add JavaScript handlers
        $output .= self::getScripts();
        
        return $output;
    }
    
    /**
     * Get JavaScript for table functionality
     * 
     * @return string HTML with script tag
     */
    private static function getStylesheets(): string {
        return StylesheetManager::getStylesheets('loan-types');
    }
    
    /**
     * Get JavaScript handlers
     */
    private static function getScripts(): string {
        return <<<HTML
<script>
/**
 * Edit loan type - TODO: Implement edit functionality
 */
function editLoanType(id) {
    console.log('Edit loan type:', id);
    // TODO: Implement edit form or modal
}

/**
 * Delete loan type - TODO: Implement delete functionality
 */
function deleteLoanType(id) {
    if (confirm('Delete this loan type?')) {
        console.log('Delete loan type:', id);
        // TODO: Implement delete via AJAX or form submission
    }
}
</script>
HTML;
    }
}

