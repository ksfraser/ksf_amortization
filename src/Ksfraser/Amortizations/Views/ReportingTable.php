<?php
namespace Ksfraser\Amortizations\Views;

use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;
use Ksfraser\HTML\Elements\TableHeader;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Div;

/**
 * ReportingTable - Displays available reports
 * 
 * Provides table view of generated and available reports with view action.
 * Uses HTML builder pattern for clean, maintainable code.
 * SRP: Single responsibility of reporting table presentation.
 * 
 * @package Ksfraser\Amortizations\Views
 */
class ReportingTable {
    /**
     * Render reports table
     * 
     * @param array $reports Array of report objects
     * @return string HTML rendering of the table
     */
    public static function render(array $reports = []): string {
        $output = '';
        
        // Load stylesheets
        $output .= self::getStylesheets();
        
        // Build heading
        $heading = (new Heading(3))->setText('Reports');
        $output .= $heading->render();
        
        // Build table
        $table = (new Table())->addClass('reporting-table');
        
        // Header row
        $headerRow = (new TableRow())->addClass('header-row');
        $headerRow->append(
            (new TableHeader())->setText('ID'),
            (new TableHeader())->setText('Type'),
            (new TableHeader())->setText('Date'),
            (new TableHeader())->setText('Actions')
        );
        $table->append($headerRow);
        
        // Data rows
        foreach ($reports as $report) {
            $row = (new TableRow())->addClass('data-row');
            
            $row->append((new TableData())
                ->addClass('id-cell')
                ->setText((string)($report->id ?? 'N/A'))
            );
            
            $row->append((new TableData())
                ->addClass('type-cell')
                ->setText(htmlspecialchars($report->type ?? 'Unknown'))
            );
            
            // Format date if provided
            $dateText = 'N/A';
            if (isset($report->date)) {
                try {
                    $dateObj = is_string($report->date) 
                        ? new \DateTime($report->date)
                        : $report->date;
                    $dateText = $dateObj->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $dateText = htmlspecialchars($report->date);
                }
            }
            $row->append((new TableData())
                ->addClass('date-cell')
                ->setText($dateText)
            );
            
            // Actions
            $actionsCell = (new TableData())->addClass('actions-cell');
            $actionsDiv = (new Div())->addClass('action-buttons');
            
            $viewBtn = (new Button())
                ->setType('button')
                ->addClass('btn-small btn-view')
                ->setText('View')
                ->setAttribute('onclick', 'window.viewReport ? viewReport(' . intval($report->id ?? 0) . ') : console.log("Handler not loaded")');
            $actionsDiv->append($viewBtn);
            
            // Add download button if available
            if (isset($report->download_url)) {
                $downloadBtn = (new Button())
                    ->setType('button')
                    ->addClass('btn-small btn-download')
                    ->setText('Download')
                    ->setAttribute('onclick', 'window.location.href = "' . htmlspecialchars($report->download_url) . '"');
                $actionsDiv->append($downloadBtn);
            }
            
            $actionsCell->append($actionsDiv);
            $row->append($actionsCell);
            $table->append($row);
        }
        
        $output .= $table->render();
        $output .= self::getScripts();
        
        return $output;
    }
    
    /**
     * Get stylesheets for this view
     */
    private static function getStylesheets(): string {
        return StylesheetManager::getStylesheets('reporting');
    }
    
    /**
     * Get JavaScript handlers
     */
    private static function getScripts(): string {
        return <<<HTML
<style>
    .reporting-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .reporting-table th {
        background-color: #1976d2;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border: 1px solid #1565c0;
    }
    
    .reporting-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    
    .reporting-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .reporting-table .actions-cell {
        text-align: center;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-small {
        padding: 6px 12px;
        font-size: 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .btn-view {
        background-color: #1976d2;
        color: white;
    }
    
    .btn-view:hover {
        background-color: #1565c0;
    }
    
    .btn-download {
        background-color: #388e3c;
        color: white;
    }
    
    .btn-download:hover {
        background-color: #2e7d32;
    }
</style>

<script>
function viewReport(id) {
    console.log('View report:', id);
    // TODO: Implement view report details/preview
}
</script>
HTML;
    }
}
