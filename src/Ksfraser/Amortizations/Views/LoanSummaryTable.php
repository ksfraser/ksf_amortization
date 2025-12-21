<?php
namespace Ksfraser\Amortizations\Views;

use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\TableHeader;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\ScriptHandlers\LoanScriptHandler;

/**
 * LoanSummaryTable - Displays loan summary information
 * 
 * Provides table view of all loans with ID, borrower, amount, and status.
 * Includes view and edit actions for each loan.
 * Uses HTML builder pattern for clean, maintainable code.
 * SRP: Single responsibility of loan summary table presentation.
 * 
 * @package Ksfraser\Amortizations\Views
 */
class LoanSummaryTable {
    /**
     * Render loan summary table
     * 
     * @param array $loans Array of loan objects
     * @return string HTML rendering of the table
     */
    public static function render(array $loans = []): string {
        $output = '';
                // Load stylesheets
        $output .= self::getStylesheets();
                // Build heading
        $heading = (new Heading(3))->setText('Loan Summary');
        $output .= $heading->render();
        
        // Build table
        $table = (new Table())->addClass('loan-summary-table');
        
        // Header row
        $headerRow = (new TableRow())->addClass('header-row');
        $headerRow->append(
            (new TableHeader())->setText('ID'),
            (new TableHeader())->setText('Borrower'),
            (new TableHeader())->setText('Amount'),
            (new TableHeader())->setText('Status'),
            (new TableHeader())->setText('Actions')
        );
        $table->append($headerRow);
        
        // Data rows
        foreach ($loans as $loan) {
            $row = (new TableRow())->addClass('data-row');
            
            $row->append((new TableData())
                ->addClass('id-cell')
                ->setText((string)($loan->id ?? 'N/A'))
            );
            
            $row->append((new TableData())
                ->addClass('borrower-cell')
                ->setText(htmlspecialchars($loan->borrower ?? ''))
            );
            
            $amountText = isset($loan->amount) 
                ? '$' . number_format((float)$loan->amount, 2)
                : 'N/A';
            $row->append((new TableData())
                ->addClass('amount-cell')
                ->setText($amountText)
            );
            
            // Status with color coding
            $status = htmlspecialchars($loan->status ?? 'Unknown');
            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $status));
            $row->append((new TableData())
                ->addClass('status-cell ' . $statusClass)
                ->setText($status)
            );
            
            // Actions
            $actionsCell = (new TableData())->addClass('actions-cell');
            $actionsDiv = (new Div())->addClass('action-buttons');
            
            $viewBtn = (new Button())
                ->setType('button')
                ->addClass('btn-small btn-view')
                ->setText('View')
                ->setAttribute('onclick', 'window.loanHandler && window.loanHandler.view(' . intval($loan->id ?? 0) . ')');
            $actionsDiv->append($viewBtn);
            
            $editBtn = (new Button())
                ->setType('button')
                ->addClass('btn-small btn-edit')
                ->setText('Edit')
                ->setAttribute('onclick', 'window.loanHandler && window.loanHandler.edit(' . intval($loan->id ?? 0) . ')');
            $actionsDiv->append($editBtn);
            
            $actionsCell->append($actionsDiv);
            $row->append($actionsCell);
            $table->append($row);
        }
        
        $output .= $table->render();
        
        // Add handler scripts
        $scriptHandler = new LoanScriptHandler();
        $output .= $scriptHandler->render();
        
        return $output;
    }
    
    /**
     * Get stylesheets for this view
     */
    private static function getStylesheets(): string {
        return StylesheetManager::getStylesheets('loan-summary');
    }
}

