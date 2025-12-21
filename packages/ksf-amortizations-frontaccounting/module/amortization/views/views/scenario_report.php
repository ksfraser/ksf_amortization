<?php
/**
 * Scenario Analysis Report View
 * 
 * Displays what-if scenario analysis on screen with options to:
 * - View scenario details
 * - Compare scenarios
 * - Export to PDF/CSV
 * - Run new scenarios
 * 
 * @var array $scenario Current scenario data
 * @var array $schedule Calculated schedule
 * @var Loan $loan Associated loan
 * @var ScenarioReportGenerator $reportGenerator Report generator instance
 */

use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Paragraph;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\Link;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\Button;

?>
<div class="scenario-report-container">
    <div class="scenario-report-actions">
        <button class="btn btn-primary" id="exportPdf">Export to PDF</button>
        <button class="btn btn-secondary" id="exportCsv">Export to CSV</button>
        <button class="btn btn-default" id="newScenario">Create New Scenario</button>
        <button class="btn btn-default" id="compareScenario">Compare Scenarios</button>
    </div>

    <div class="scenario-report-content" id="reportContent">
        <!-- Report content will be generated here -->
    </div>
</div>

<style>
    .scenario-report-container {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .scenario-report-actions {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .scenario-report-actions button {
        padding: 10px 20px;
        border: 1px solid #ccc;
        background-color: #f5f5f5;
        cursor: pointer;
        border-radius: 4px;
    }

    .scenario-report-actions button:hover {
        background-color: #e0e0e0;
    }

    .btn-primary {
        background-color: #1976d2;
        color: white;
        border-color: #1976d2;
    }

    .btn-primary:hover {
        background-color: #1565c0;
    }

    .scenario-report-content {
        background-color: white;
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
    }

    .scenario-report-header {
        border-bottom: 2px solid #1976d2;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .scenario-report-header h2 {
        color: #1976d2;
        margin-bottom: 10px;
    }

    .scenario-report-header p {
        margin: 5px 0;
        color: #666;
    }

    .scenario-summary {
        margin-bottom: 30px;
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
    }

    .scenario-summary .summary-table {
        width: 100%;
        border-collapse: collapse;
    }

    .scenario-summary .summary-table tr {
        border-bottom: 1px solid #ddd;
    }

    .scenario-summary .summary-table td {
        padding: 8px 4px;
    }

    .scenario-summary .summary-table td:first-child {
        width: 50%;
        font-weight: bold;
        color: #333;
    }

    .scenario-summary .summary-table td:last-child {
        text-align: right;
        color: #1976d2;
        font-weight: bold;
    }

    .schedule-table-wrapper {
        margin: 30px 0;
        overflow-x: auto;
    }

    .schedule-table-wrapper h3 {
        color: #1976d2;
        margin-bottom: 15px;
    }

    .amortization-schedule {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .amortization-schedule thead {
        background-color: #f5f5f5;
    }

    .amortization-schedule th {
        padding: 10px;
        text-align: right;
        font-weight: bold;
        border-bottom: 2px solid #ddd;
        color: #1976d2;
    }

    .amortization-schedule th:first-child {
        text-align: left;
    }

    .amortization-schedule td {
        padding: 8px 10px;
        border-bottom: 1px solid #eee;
        text-align: right;
    }

    .amortization-schedule td:first-child {
        text-align: left;
        font-weight: bold;
    }

    .amortization-schedule tbody tr:hover {
        background-color: #f9f9f9;
    }

    .amortization-schedule tbody tr:nth-child(even) {
        background-color: #fafafa;
    }

    .calculations-summary,
    .key-metrics {
        background-color: #f0f7ff;
        border: 1px solid #90caf9;
        padding: 15px;
        margin: 20px 0;
        border-radius: 4px;
    }

    .calculations-summary h3,
    .key-metrics h3 {
        color: #1976d2;
        margin-bottom: 12px;
    }

    .calculations-summary table,
    .key-metrics table {
        width: 100%;
        border-collapse: collapse;
    }

    .calculations-summary table tr,
    .key-metrics table tr {
        border-bottom: 1px solid #ddd;
    }

    .calculations-summary td,
    .key-metrics td {
        padding: 8px 4px;
    }

    .calculations-summary td:first-child,
    .key-metrics td:first-child {
        font-weight: bold;
        width: 50%;
    }

    .calculations-summary td:last-child,
    .key-metrics td:last-child {
        text-align: right;
        color: #1976d2;
        font-weight: bold;
    }

    .savings-positive {
        background-color: #e8f5e9;
        border-left: 4px solid #4caf50;
        padding: 12px;
        margin: 15px 0;
        border-radius: 4px;
    }

    .savings-positive p {
        margin: 5px 0;
        color: #2e7d32;
    }

    .savings-negative {
        background-color: #ffebee;
        border-left: 4px solid #f44336;
        padding: 12px;
        margin: 15px 0;
        border-radius: 4px;
    }

    .savings-negative p {
        margin: 5px 0;
        color: #c62828;
    }

    .comparison-metrics {
        margin: 30px 0;
    }

    .comparison-metrics h3 {
        color: #1976d2;
        margin-bottom: 15px;
    }

    .comparison-table {
        width: 100%;
        border-collapse: collapse;
    }

    .comparison-table th,
    .comparison-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: right;
    }

    .comparison-table th {
        background-color: #f5f5f5;
        font-weight: bold;
        color: #1976d2;
    }

    .comparison-table th:first-child,
    .comparison-table td:first-child {
        text-align: left;
    }

    .comparison-table tbody tr:hover {
        background-color: #f9f9f9;
    }

    @media print {
        .scenario-report-actions {
            display: none;
        }

        .scenario-report-content {
            border: none;
            box-shadow: none;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export to PDF
    document.getElementById('exportPdf').addEventListener('click', function() {
        const loanId = '<?= htmlspecialchars($_GET['loan_id'] ?? '') ?>';
        const scenarioId = '<?= htmlspecialchars($_GET['scenario_id'] ?? '') ?>';
        window.location.href = '?action=report&format=pdf&loan_id=' + loanId + '&scenario_id=' + scenarioId;
    });

    // Export to CSV
    document.getElementById('exportCsv').addEventListener('click', function() {
        const loanId = '<?= htmlspecialchars($_GET['loan_id'] ?? '') ?>';
        const scenarioId = '<?= htmlspecialchars($_GET['scenario_id'] ?? '') ?>';
        window.location.href = '?action=report&format=csv&loan_id=' + loanId + '&scenario_id=' + scenarioId;
    });

    // Create new scenario
    document.getElementById('newScenario').addEventListener('click', function() {
        window.location.href = '?action=scenario&mode=create&loan_id=' + '<?= htmlspecialchars($_GET['loan_id'] ?? '') ?>';
    });

    // Compare scenarios
    document.getElementById('compareScenario').addEventListener('click', function() {
        window.location.href = '?action=scenario&mode=compare&loan_id=' + '<?= htmlspecialchars($_GET['loan_id'] ?? '') ?>';
    });

    // Print button (for PDF output on-screen)
    const printButton = document.createElement('button');
    printButton.className = 'btn btn-secondary';
    printButton.textContent = 'Print';
    printButton.addEventListener('click', function() {
        window.print();
    });
    document.querySelector('.scenario-report-actions').insertBefore(printButton, document.getElementById('newScenario'));
});
</script>
