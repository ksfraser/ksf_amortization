<?php
/**
 * Scenario Builder/Management View
 * 
 * Allows users to:
 * - Create new what-if scenarios
 * - Configure scenario modifications
 * - Compare multiple scenarios
 * - View and manage scenario list
 * 
 * Scenarios include:
 * - Extra monthly payments
 * - Lump sum payments at specific periods
 * - Skip payment scenarios
 * - Accelerated payoff scenarios
 * - Custom modifications
 * 
 * @var Loan $loan Current loan being analyzed
 * @var array $scenarios List of saved scenarios
 * @var ScenarioAnalysisService $scenarioService Scenario service
 */

use Ksfraser\HTML\Elements\Form;
use Ksfraser\HTML\Elements\Input;
use Ksfraser\HTML\Elements\Label;
use Ksfraser\HTML\Elements\Select;
use Ksfraser\HTML\Elements\Option;
use Ksfraser\HTML\Elements\Button;
use Ksfraser\HTML\Elements\Div;
use Ksfraser\HTML\Elements\Heading;
use Ksfraser\HTML\Elements\Paragraph;
use Ksfraser\HTML\Elements\Table;
use Ksfraser\HTML\Elements\TableRow;
use Ksfraser\HTML\Elements\TableData;

$loanId = htmlspecialchars($_GET['loan_id'] ?? '');
$currentBalance = number_format($loan->getCurrentBalance() ?? 0, 2);
$annualRate = number_format(($loan->getAnnualRate() ?? 0) * 100, 2);
$monthlyPayment = number_format($loan->getMonthlyPayment() ?? 0, 2);
$remainingMonths = $loan->getRemainingMonths() ?? 0;
$currentPayoffDate = date('Y-m-d', strtotime("+{$remainingMonths} months"));

echo (new Div())
    ->addClass('scenario-builder-container')
    ->append(
        (new Heading('h2'))->setText('What-If Scenario Analysis'),
        (new Paragraph())
            ->addClass('subtitle')
            ->setText('Create temporary loan scenarios to explore payment strategies and savings opportunities'),
        
        // Tabs
        (new Div())
            ->addClass('scenario-tabs')
            ->append(
                (new Button())
                    ->addClass('tab-button active')
                    ->setAttribute('data-tab', 'create')
                    ->setText('Create Scenario'),
                (new Button())
                    ->addClass('tab-button')
                    ->setAttribute('data-tab', 'manage')
                    ->setText('My Scenarios'),
                (new Button())
                    ->addClass('tab-button')
                    ->setAttribute('data-tab', 'compare')
                    ->setText('Compare')
            ),
        
        // CREATE SCENARIO TAB
        (new Div())
            ->setId('create-tab')
            ->addClass('tab-content active')
            ->append(
                (new Div())
                    ->addClass('scenario-form-wrapper')
                    ->append(
                        (new Form())
                            ->setId('scenarioForm')
                            ->setMethod('POST')
                            ->setAction('?action=scenario&mode=create')
                            ->append(
                                (new Input())
                                    ->setType('hidden')
                                    ->setName('loan_id')
                                    ->setValue($loanId),
                                
                                // Loan Information Section
                                (new Div())
                                    ->addClass('form-section')
                                    ->append(
                                        (new Heading('h3'))->setText('Loan Information'),
                                        (new Table())
                                            ->addClass('loan-info-table')
                                            ->append(
                                                (new TableRow())->append(
                                                    (new TableData())->setText('<strong>Loan Number:</strong>'),
                                                    (new TableData())->setText(htmlspecialchars($loan->getLoanNumber() ?? ''))
                                                ),
                                                (new TableRow())->append(
                                                    (new TableData())->setText('<strong>Current Balance:</strong>'),
                                                    (new TableData())->setText("\${$currentBalance}")
                                                ),
                                                (new TableRow())->append(
                                                    (new TableData())->setText('<strong>Annual Interest Rate:</strong>'),
                                                    (new TableData())->setText("{$annualRate}%")
                                                ),
                                                (new TableRow())->append(
                                                    (new TableData())->setText('<strong>Original Monthly Payment:</strong>'),
                                                    (new TableData())->setText("\${$monthlyPayment}")
                                                ),
                                                (new TableRow())->append(
                                                    (new TableData())->setText('<strong>Remaining Term:</strong>'),
                                                    (new TableData())->setText("{$remainingMonths} months")
                                                )
                                            )
                                    ),
                                
                                // Scenario Details Section
                                (new Div())
                                    ->addClass('form-section')
                                    ->append(
                                        (new Heading('h3'))->setText('Scenario Details'),
                                        
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->setFor('scenarioName')
                                                    ->setText('Scenario Name *'),
                                                (new Input())
                                                    ->setId('scenarioName')
                                                    ->setName('scenario_name')
                                                    ->setType('text')
                                                    ->setAttribute('placeholder', 'e.g., Extra $200/month')
                                                    ->setRequired(true),
                                                (new Paragraph())
                                                    ->addClass('help-text')
                                                    ->setText('Descriptive name for this scenario')
                                            ),
                                        
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->setFor('scenarioType')
                                                    ->setText('Scenario Type *'),
                                                (new Select())
                                                    ->setId('scenarioType')
                                                    ->setName('scenario_type')
                                                    ->setRequired(true)
                                                    ->setAttribute('onchange', 'updateScenarioFields()')
                                                    ->append(
                                                        (new Option())->setValue('')->setText('-- Select Scenario Type --'),
                                                        (new Option())->setValue('extra_monthly')->setText('Extra Monthly Payment'),
                                                        (new Option())->setValue('lump_sum')->setText('Lump Sum Payment'),
                                                        (new Option())->setValue('skip_payment')->setText('Skip Payment'),
                                                        (new Option())->setValue('acceleration')->setText('Accelerated Payoff'),
                                                        (new Option())->setValue('custom')->setText('Custom Modifications')
                                                    ),
                                                (new Paragraph())
                                                    ->addClass('help-text')
                                                    ->setText('Choose the modification type to analyze')
                                            )
                                    ),
                                
                                // Extra Monthly Payment Section
                                (new Div())
                                    ->setId('extra-monthly-section')
                                    ->addClass('scenario-config-section')
                                    ->setAttribute('style', 'display:none;')
                                    ->append(
                                        (new Heading('h3'))->setText('Extra Monthly Payment Configuration'),
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->setFor('extraMonthly')
                                                    ->setText('Additional Monthly Payment $'),
                                                (new Input())
                                                    ->setId('extraMonthly')
                                                    ->setName('extra_monthly_payment')
                                                    ->setType('number')
                                                    ->setAttribute('step', '0.01')
                                                    ->setAttribute('min', '0')
                                                    ->setAttribute('placeholder', '0.00'),
                                                (new Paragraph())
                                                    ->addClass('help-text')
                                                    ->setText('Amount to add to each regular payment')
                                            ),
                                        (new Div())
                                            ->addClass('calculation-preview')
                                            ->append(
                                                (new Paragraph())->setText('<strong>Original Payment:</strong> $' . $monthlyPayment),
                                                (new Paragraph())->setText('<strong>New Payment:</strong> $<span id="newPaymentExtra">--</span>'),
                                                (new Paragraph())->setText('<strong>Estimated Savings:</strong> $<span id="estimatedSavingsExtra">--</span>')
                                            )
                                    ),
                                
                                // Lump Sum Payment Section
                                (new Div())
                                    ->setId('lump-sum-section')
                                    ->addClass('scenario-config-section')
                                    ->setAttribute('style', 'display:none;')
                                    ->append(
                                        (new Heading('h3'))->setText('Lump Sum Payment Configuration'),
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->setFor('lumpSumAmount')
                                                    ->setText('Lump Sum Amount $'),
                                                (new Input())
                                                    ->setId('lumpSumAmount')
                                                    ->setName('lump_sum_payment')
                                                    ->setType('number')
                                                    ->setAttribute('step', '0.01')
                                                    ->setAttribute('min', '0')
                                                    ->setAttribute('placeholder', '0.00'),
                                                (new Paragraph())
                                                    ->addClass('help-text')
                                                    ->setText('One-time payment amount')
                                            ),
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->setFor('lumpSumMonth')
                                                    ->setText('Payment Period (Month Number)'),
                                                (new Input())
                                                    ->setId('lumpSumMonth')
                                                    ->setName('lump_sum_month')
                                                    ->setType('number')
                                                    ->setAttribute('step', '1')
                                                    ->setAttribute('min', '1')
                                                    ->setAttribute('placeholder', '1'),
                                                (new Paragraph())
                                                    ->addClass('help-text')
                                                    ->setText("Which payment period to apply this payment (1-{$remainingMonths})")
                                            ),
                                        (new Div())
                                            ->addClass('calculation-preview')
                                            ->append(
                                                (new Paragraph())->setText('<strong>Lump Sum Amount:</strong> $<span id="lumpSumPreview">--</span>'),
                                                (new Paragraph())->setText('<strong>Applied at Period:</strong> <span id="lumpSumPeriodPreview">--</span>'),
                                                (new Paragraph())->setText('<strong>Estimated Payoff Months Saved:</strong> <span id="estimatedMonthsSaved">--</span>')
                                            )
                                    ),
                                
                                // Skip Payment Section
                                (new Div())
                                    ->setId('skip-payment-section')
                                    ->addClass('scenario-config-section')
                                    ->setAttribute('style', 'display:none;')
                                    ->append(
                                        (new Heading('h3'))->setText('Skip Payment Configuration'),
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->setFor('skipPeriod')
                                                    ->setText('Skip Payment at Period (Month Number)'),
                                                (new Input())
                                                    ->setId('skipPeriod')
                                                    ->setName('skip_payment_period')
                                                    ->setType('number')
                                                    ->setAttribute('step', '1')
                                                    ->setAttribute('min', '1')
                                                    ->setAttribute('placeholder', '1'),
                                                (new Paragraph())
                                                    ->addClass('help-text')
                                                    ->setText('Which payment period to skip (interest will still accrue)')
                                            ),
                                        (new Div())
                                            ->addClass('calculation-preview')
                                            ->append(
                                                (new Paragraph())->setText('<strong>Skipped Period:</strong> <span id="skipPeriodPreview">--</span>'),
                                                (new Paragraph())->setText('<strong>Additional Interest Cost:</strong> $<span id="additionalInterestSkip">--</span>'),
                                                (new Paragraph())->setText('<strong>Extended Payoff:</strong> <span id="extendedPayoffSkip">--</span> months')
                                            )
                                    ),
                                
                                // Acceleration Section
                                (new Div())
                                    ->setId('acceleration-section')
                                    ->addClass('scenario-config-section')
                                    ->setAttribute('style', 'display:none;')
                                    ->append(
                                        (new Heading('h3'))->setText('Accelerated Payoff Configuration'),
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->setFor('targetPayoffDate')
                                                    ->setText('Target Payoff Date'),
                                                (new Input())
                                                    ->setId('targetPayoffDate')
                                                    ->setName('target_payoff_date')
                                                    ->setType('date'),
                                                (new Paragraph())
                                                    ->addClass('help-text')
                                                    ->setText('Desired loan payoff date')
                                            ),
                                        (new Div())
                                            ->addClass('calculation-preview')
                                            ->append(
                                                (new Paragraph())->setText("<strong>Current Payoff Date:</strong> {$currentPayoffDate}"),
                                                (new Paragraph())->setText('<strong>Target Payoff Date:</strong> <span id="targetPayoffPreview">--</span>'),
                                                (new Paragraph())->setText('<strong>Required Monthly Payment:</strong> $<span id="requiredPaymentAccel">--</span>')
                                            )
                                    ),
                                
                                // Custom Section
                                (new Div())
                                    ->setId('custom-section')
                                    ->addClass('scenario-config-section')
                                    ->setAttribute('style', 'display:none;')
                                    ->append(
                                        (new Heading('h3'))->setText('Custom Modifications'),
                                        (new Paragraph())
                                            ->addClass('help-text')
                                            ->setText('Configure multiple modifications together:'),
                                        
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->append(
                                                        (new Input())
                                                            ->setType('checkbox')
                                                            ->setName('use_extra_monthly'),
                                                        ' Extra Monthly Payment: $',
                                                        (new Input())
                                                            ->setType('number')
                                                            ->setName('custom_extra_monthly')
                                                            ->setAttribute('step', '0.01')
                                                            ->setAttribute('min', '0')
                                                            ->setAttribute('style', 'width: 100px;')
                                                    )
                                            ),
                                        
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->append(
                                                        (new Input())
                                                            ->setType('checkbox')
                                                            ->setName('use_lump_sum'),
                                                        ' Lump Sum Payment: $',
                                                        (new Input())
                                                            ->setType('number')
                                                            ->setName('custom_lump_sum')
                                                            ->setAttribute('step', '0.01')
                                                            ->setAttribute('min', '0')
                                                            ->setAttribute('style', 'width: 100px;'),
                                                        ' at Period: ',
                                                        (new Input())
                                                            ->setType('number')
                                                            ->setName('custom_lump_period')
                                                            ->setAttribute('step', '1')
                                                            ->setAttribute('min', '1')
                                                            ->setAttribute('style', 'width: 80px;')
                                                    )
                                            ),
                                        
                                        (new Div())
                                            ->addClass('form-group')
                                            ->append(
                                                (new Label())
                                                    ->append(
                                                        (new Input())
                                                            ->setType('checkbox')
                                                            ->setName('use_skip'),
                                                        ' Skip Payment at Period: ',
                                                        (new Input())
                                                            ->setType('number')
                                                            ->setName('custom_skip_period')
                                                            ->setAttribute('step', '1')
                                                            ->setAttribute('min', '1')
                                                            ->setAttribute('style', 'width: 80px;')
                                                    )
                                            )
                                    ),
                                
                                // Form Actions
                                (new Div())
                                    ->addClass('form-actions')
                                    ->append(
                                        (new Button())
                                            ->setType('submit')
                                            ->addClass('btn btn-primary')
                                            ->setText('Analyze Scenario'),
                                        (new Button())
                                            ->setType('reset')
                                            ->addClass('btn btn-secondary')
                                            ->setText('Clear Form')
                                    )
                            )
                    )
            ),
        
        // MANAGE SCENARIOS TAB
        (new Div())
            ->setId('manage-tab')
            ->addClass('tab-content')
            ->append(
                (new Heading('h3'))->setText('Saved Scenarios'),
                !empty($scenarios) 
                    ? (new Table())
                        ->addClass('scenarios-table')
                        ->append(
                            (new TableRow())
                                ->append(
                                    (new TableData())->setText('<strong>Name</strong>'),
                                    (new TableData())->setText('<strong>Type</strong>'),
                                    (new TableData())->setText('<strong>Created</strong>'),
                                    (new TableData())->setText('<strong>Interest Savings</strong>'),
                                    (new TableData())->setText('<strong>Actions</strong>')
                                ),
                            ...array_map(function($scenario) {
                                return (new TableRow())->append(
                                    (new TableData())->setText(htmlspecialchars($scenario['name'])),
                                    (new TableData())->setText(htmlspecialchars($scenario['type'])),
                                    (new TableData())->setText($scenario['created_at']),
                                    (new TableData())->setText('$' . number_format($scenario['estimated_savings'] ?? 0, 2)),
                                    (new TableData())->append(
                                        (new Button())
                                            ->addClass('btn-small')
                                            ->setAttribute('onclick', "viewScenario('" . $scenario['id'] . "')")
                                            ->setText('View'),
                                        ' ',
                                        (new Button())
                                            ->addClass('btn-small')
                                            ->setAttribute('onclick', "deleteScenario('" . $scenario['id'] . "')")
                                            ->setText('Delete')
                                    )
                                );
                            }, $scenarios)
                        )
                    : (new Paragraph())
                        ->addClass('no-data')
                        ->setText('No scenarios created yet. Create your first scenario above!')
            ),
        
        // COMPARE SCENARIOS TAB
        (new Div())
            ->setId('compare-tab')
            ->addClass('tab-content')
            ->append(
                (new Heading('h3'))->setText('Compare Scenarios'),
                (new Form())
                    ->setId('compareForm')
                    ->setMethod('POST')
                    ->setAction('?action=scenario&mode=compare')
                    ->append(
                        (new Input())
                            ->setType('hidden')
                            ->setName('loan_id')
                            ->setValue($loanId),
                        
                        (new Div())
                            ->addClass('form-group')
                            ->append(
                                (new Label())
                                    ->setFor('scenario1')
                                    ->setText('First Scenario'),
                                (new Select())
                                    ->setId('scenario1')
                                    ->setName('scenario1_id')
                                    ->setRequired(true)
                                    ->append(
                                        (new Option())->setValue('')->setText('-- Select Scenario --'),
                                        ...array_map(function($scenario) {
                                            return (new Option())
                                                ->setValue($scenario['id'])
                                                ->setText(htmlspecialchars($scenario['name']));
                                        }, $scenarios)
                                    )
                            ),
                        
                        (new Div())
                            ->addClass('form-group')
                            ->append(
                                (new Label())
                                    ->setFor('scenario2')
                                    ->setText('Second Scenario'),
                                (new Select())
                                    ->setId('scenario2')
                                    ->setName('scenario2_id')
                                    ->setRequired(true)
                                    ->append(
                                        (new Option())->setValue('')->setText('-- Select Scenario --'),
                                        ...array_map(function($scenario) {
                                            return (new Option())
                                                ->setValue($scenario['id'])
                                                ->setText(htmlspecialchars($scenario['name']));
                                        }, $scenarios)
                                    )
                            ),
                        
                        (new Button())
                            ->setType('submit')
                            ->addClass('btn btn-primary')
                            ->setText('Compare Scenarios')
                    )
            )
    )->render();
?>

<!-- CSS Files -->
<link rel="stylesheet" href="<?= asset_url('module/amortization/assets/css/scenario-container.css') ?>">
<link rel="stylesheet" href="<?= asset_url('module/amortization/assets/css/scenario-tabs.css') ?>">
<link rel="stylesheet" href="<?= asset_url('module/amortization/assets/css/scenario-forms.css') ?>">
<link rel="stylesheet" href="<?= asset_url('module/amortization/assets/css/scenario-buttons.css') ?>">
<link rel="stylesheet" href="<?= asset_url('module/amortization/assets/css/scenario-tables.css') ?>">

<!-- JavaScript Files -->
<script src="<?= asset_url('module/amortization/assets/js/ScenarioTabs.js') ?>"></script>
<script src="<?= asset_url('module/amortization/assets/js/ScenarioFormFields.js') ?>"></script>
<script src="<?= asset_url('module/amortization/assets/js/ScenarioCalculator.js') ?>"></script>
<script src="<?= asset_url('module/amortization/assets/js/ScenarioActions.js') ?>"></script>
<script src="<?= asset_url('module/amortization/assets/js/ScenarioBuilder.js') ?>"></script>

<script>
    // Initialize the scenario builder when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const scenarioBuilder = new ScenarioBuilder({
            monthlyPayment: <?= $loan->getMonthlyPayment() ?? 0 ?>,
            remainingMonths: <?= $loan->getRemainingMonths() ?? 0 ?>,
            tabs: {},
            formFields: {},
            calculator: {},
            actions: {}
        });
    });
</script>
