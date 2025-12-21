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
                ->setAttribute('onclick', 'window.interestFreqHandler && window.interestFreqHandler.edit(' . intval($freq->id ?? 0) . ')');
            $actionsDiv->append($editBtn);
            
            $deleteBtn = (new Button())
                ->setType('button')
                ->addClass('btn-small btn-delete')
                ->setText('Delete')
                ->setAttribute('onclick', 'window.interestFreqHandler && window.interestFreqHandler.delete(' . intval($freq->id ?? 0) . ')');
            $actionsDiv->append($deleteBtn);
            
            $actionsCell->append($actionsDiv);
            $row->append($actionsCell);
            $table->append($row);
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
<script src="/js/handlers/BaseHandler.js"></script>
<script src="/js/handlers/InterestFreqHandler.js"></script>
<script>
// Event listeners for handler responses
document.addEventListener('interestFreqEdit', (e) => {
    console.log('Frequency edit event:', e.detail);
    // TODO: Open edit modal/form with frequency data
    // window.showEditFrequencyModal(e.detail.data);
});

document.addEventListener('interestFreqDeleted', (e) => {
    console.log('Frequency deleted event:', e.detail);
    // TODO: Reload table or remove row from DOM
    // window.location.reload();
});

document.addEventListener('interestFreqCreated', (e) => {
    console.log('Frequency created event:', e.detail);
    // TODO: Reload table or add new row to DOM
    // window.location.reload();
});

document.addEventListener('interestFreqUpdated', (e) => {
    console.log('Frequency updated event:', e.detail);
    // TODO: Update table row with new data
    // window.location.reload();
});
</script>
HTML;
    }
}

