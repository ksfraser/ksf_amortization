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
        
        // Check if no reports
        if (empty($reports)) {
            $output .= '<p>No reports available. Reports are generated when you create and calculate loan amortization schedules.</p>';
            $output .= '<p><a href="?action=create">Create a loan</a> to generate your first report.</p>';
            return $output;
        }
        
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
        return <<<'HTML'
<script>
function viewReport(id) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.id = 'report-modal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;';
    
    // Create modal content
    const content = document.createElement('div');
    content.style.cssText = 'background: white; padding: 20px; border-radius: 8px; max-width: 90%; max-height: 90%; overflow: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.3);';
    content.innerHTML = '<h2>Report #' + id + '</h2><p>Loading report details...</p><button onclick="closeReportModal()" style="margin-top: 15px;">Close</button>';
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    // Fetch report details via AJAX
    const controller = window.location.pathname.split('?')[0];
    fetch(controller + '?action=report_details&id=' + id)
        .then(response => response.json())
        .then(data => {
            let html = '<h2>Report #' + id + '</h2>';
            html += '<table border="1" cellpadding="8" style="width: 100%; border-collapse: collapse;">';
            html += '<tr><th>Property</th><th>Value</th></tr>';
            for (const [key, value] of Object.entries(data)) {
                html += '<tr><td><strong>' + key + '</strong></td><td>' + value + '</td></tr>';
            }
            html += '</table>';
            html += '<button onclick="closeReportModal()" style="margin-top: 15px;">Close</button>';
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<h2>Report #' + id + '</h2><p style="color: red;">Error loading report: ' + error.message + '</p><button onclick="closeReportModal()" style="margin-top: 15px;">Close</button>';
        });
}

function closeReportModal() {
    const modal = document.getElementById('report-modal');
    if (modal) {
        modal.remove();
    }
}
</script>
HTML;
    }
}
