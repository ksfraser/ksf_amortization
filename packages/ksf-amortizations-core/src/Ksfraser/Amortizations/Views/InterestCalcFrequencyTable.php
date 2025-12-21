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
 * InterestCalcFrequencyTable - Displays and manages interest calculation frequencies
 * 
 * Provides table view of all interest calculation frequencies with add/edit/delete.
 * Uses HTML builder pattern for clean, maintainable code.
 * SRP: Single responsibility of interest frequency table presentation.
 * 
 * @package Ksfraser\Amortizations\Views
 */
class InterestCalcFrequencyTable {
    /**
     * Render interest calculation frequencies table
     * 
     * @param array $interestCalcFreqs Array of frequency objects
     * @return string HTML rendering of the table and form
     */
    public static function render(array $interestCalcFreqs = []): string {
        $output = '';
        
        // Load stylesheets
        $output .= self::getStylesheets();
        
        // Build heading
        $heading = (new Heading(3))->setText('Interest Calculation Frequencies');
        $output .= $heading->render();
        
        // Build table
        $table = (new Table())->addClass('interest-freq-table');
        
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
        foreach ($interestCalcFreqs as $freq) {
            $row = (new TableRow())->addClass('data-row');
            
            $row->append((new TableData())
                ->addClass('id-cell')
                ->setText((string)($freq->id ?? 'N/A'))
            );
            
            $row->append((new TableData())
                ->addClass('name-cell')
                ->setText(htmlspecialchars($freq->name ?? ''))
            );
            
            $row->append((new TableData())
                ->addClass('description-cell')
                ->setText(htmlspecialchars($freq->description ?? ''))
            );
            
            $actionsCell = (new TableData())->addClass('actions-cell');
            $actionsDiv = (new Div())->addClass('action-buttons');
            
            $editBtn = (new Button())
                ->setType('button')
                ->addClass('btn-small btn-edit')
                ->setText('Edit')
                ->setAttribute('onclick', 'window.editInterestFreq ? editInterestFreq(' . intval($freq->id ?? 0) . ') : console.log("Handler not loaded")');
            $actionsDiv->append($editBtn);
            
            $deleteBtn = (new Button())
                ->setType('button')
                ->addClass('btn-small btn-delete')
                ->setText('Delete')
                ->setAttribute('onclick', 'window.deleteInterestFreq ? deleteInterestFreq(' . intval($freq->id ?? 0) . ') : console.log("Handler not loaded")');
            $actionsDiv->append($deleteBtn);
            
            $actionsCell->append($actionsDiv);
            $row->append($actionsCell);
            $table->append($row);
        }
        
        // Load CSS
        if (function_exists('asset_url')) {
            $output .= '<link rel="stylesheet" href="' . asset_url('css/interest-freq-table.css') . '">';
            $output .= '<link rel="stylesheet" href="' . asset_url('css/interest-freq-form.css') . '">';
            $output .= '<link rel="stylesheet" href="' . asset_url('css/interest-freq-buttons.css') . '">';
        }
        
        $output .= $table->render();
        
        // Build add form
        $form = (new Form())
            ->setMethod('POST')
            ->addClass('add-interest-freq-form');
        
        $formContainer = (new Div())->addClass('form-container');
        
        $nameGroup = (new Div())->addClass('form-group');
        $nameGroup->append((new Input())
            ->setType('text')
            ->setName('interest_calc_freq_name')
            ->setAttribute('placeholder', 'New Frequency')
            ->setRequired(true)
        );
        $formContainer->append($nameGroup);
        
        $descGroup = (new Div())->addClass('form-group');
        $descGroup->append((new Input())
            ->setType('text')
            ->setName('interest_calc_freq_desc')
            ->setAttribute('placeholder', 'Description')
            ->setRequired(true)
        );
        $formContainer->append($descGroup);
        
        $submitBtn = (new Button())
            ->setType('submit')
            ->addClass('btn btn-primary')
            ->setText('Add Frequency');
        $formContainer->append($submitBtn);
        
        $form->append($formContainer);
        $output .= $form->render();
        
        $output .= self::getScripts();
        
        return $output;
    }
    
    /**
     * Get stylesheets for this view
     */
    private static function getStylesheets(): string {
        return StylesheetManager::getStylesheets('interest-freq');
    }
    
    /**
     * Get JavaScript handlers
     */
    private static function getScripts(): string {
        return <<<HTML
<style>
    .interest-freq-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .interest-freq-table th {
        background-color: #1976d2;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border: 1px solid #1565c0;
    }
    
    .interest-freq-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    
    .interest-freq-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .interest-freq-table .actions-cell {
        text-align: center;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
    }
    
    .btn-small {
        padding: 6px 12px;
        font-size: 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-edit {
        background-color: #ff9800;
        color: white;
    }
    
    .btn-edit:hover {
        background-color: #f57c00;
    }
    
    .btn-delete {
        background-color: #f44336;
        color: white;
    }
    
    .btn-delete:hover {
        background-color: #d32f2f;
    }
    
    .add-interest-freq-form {
        margin: 30px 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
    }
    
    .form-container {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }
    
    .form-group {
        flex: 1;
    }
    
    .form-group input {
        width: 100%;
        padding: 10px;
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
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background-color: #1976d2;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #1565c0;
    }
</style>

<script>
function editInterestFreq(id) {
    console.log('Edit interest frequency:', id);
}

function deleteInterestFreq(id) {
    if (confirm('Delete this frequency?')) {
        console.log('Delete interest frequency:', id);
    }
}
</script>
HTML;
    }
}
