# What-If Scenario Analysis & Reporting

**Date:** December 20, 2025  
**Feature Status:** In Development  
**Priority:** Medium (Phases 9-10)

## Overview

The What-If Scenario Analysis feature allows users to explore payment strategies and compare outcomes without modifying actual loan records. Users can create temporary scenarios, analyze impacts, and generate professional reports in multiple formats.

## Features

### 1. Scenario Types

#### Extra Monthly Payment
**Question:** "What if I pay an extra $200/month?"

- Add fixed amount to each monthly payment
- Calculate payoff date reduction
- Estimate total interest savings
- **Example:** $500/month payment → $700/month payment

**Implementation:**
```
Extra Monthly Payment: $200
Result: Loan pays off 18 months earlier, saves $8,450 in interest
```

#### Lump Sum Payment
**Question:** "What if I make a $5,000 payment at period 12?"

- One-time payment at specified period
- Reduces principal immediately
- Recalculates remaining schedule
- **Example:** $5,000 at month 12

**Implementation:**
```
Lump Sum: $5,000 at Period 12
Result: Loan pays off 24 months earlier, saves $12,100 in interest
```

#### Skip Payment
**Question:** "What if I skip a payment in month 6?"

- Omit a single payment
- Interest still accrues
- Extends loan term
- **Example:** Skip month 6 payment

**Implementation:**
```
Skip Period 6
Result: Loan extends 1 month, costs additional $425 in interest
```

#### Accelerated Payoff
**Question:** "How much do I need to pay monthly to be debt-free in 3 years?"

- Specify target payoff date
- Calculate required monthly payment
- Determine payment increase needed

**Implementation:**
```
Target Payoff: 2028-01-15 (36 months instead of 60)
Result: Required monthly payment $1,087 (was $800)
```

#### Custom Modifications
Combine multiple modifications:
- Extra monthly payment + lump sum at period 24
- Multiple lump sums + skip payments
- Complex payment strategies

## User Interface

### Scenario Builder
Located at: `?action=scenario`

**Three Main Sections:**

1. **Create Scenario**
   - Loan information display
   - Scenario type selection
   - Configuration fields (dynamic based on type)
   - Real-time calculation preview
   - Submit to analyze

2. **My Scenarios**
   - List of saved scenarios
   - View, delete, compare actions
   - Show estimated savings for each

3. **Compare**
   - Select two scenarios
   - Side-by-side comparison
   - Interest savings analysis

### Scenario Report
Located at: `?action=report&scenario_id=X`

**Report Sections:**
1. Report header with loan info
2. Scenario modifications applied
3. Summary metrics (total interest, payoff date, etc.)
4. Detailed amortization schedule
5. Key performance metrics
6. Savings analysis

**Export Options:**
- PDF (professional formatted)
- CSV (spreadsheet)
- HTML (on-screen view)
- Print-friendly format

## Architecture

### Classes

#### ScenarioAnalysisService
**Location:** `src/Ksfraser/Amortizations/Services/ScenarioAnalysisService.php`

Creates temporary scenarios and generates modified amortization schedules.

```php
// Create a scenario
$scenario = $scenarioService->createScenario(
    $loan,
    'Extra $200/month',
    ['extra_monthly_payment' => 200]
);

// Generate schedule for scenario
$schedule = $scenarioService->generateScenarioSchedule($scenario, $loan);

// Compare two scenarios
$comparison = $scenarioService->compareScenarios(
    $scenario1, $schedule1,
    $scenario2, $schedule2
);
```

**Key Methods:**
- `createScenario($loan, $name, $modifications)` - Create ephemeral scenario
- `generateScenarioSchedule($scenario, $loan)` - Calculate modified schedule
- `compareScenarios($scenario1, $schedule1, $scenario2, $schedule2)` - Compare metrics
- `calculateInterestSavings($schedule1, $schedule2)` - Interest difference
- `saveFavoriteScenario($scenario)` - Store in session/cache

#### ScenarioReportGenerator
**Location:** `src/Ksfraser/Amortizations/Reports/ScenarioReportGenerator.php`

Generates HTML reports for scenarios with detailed tables and metrics.

```php
// Generate HTML report
$html = $reportGenerator->generateScenarioHtmlReport(
    $scenario,
    $schedule,
    $loan
);

// Generate comparison report
$comparison = $reportGenerator->generateComparisonHtmlReport(
    $scenario1, $schedule1,
    $scenario2, $schedule2,
    $loan
);

// Generate CSV
$csv = $reportGenerator->generateScenarioCsv($scenario, $schedule);
```

**Key Methods:**
- `generateScenarioHtmlReport()` - Single scenario HTML
- `generateComparisonHtmlReport()` - Side-by-side HTML
- `generateScenarioPdf()` - PDF format
- `generateScenarioCsv()` - CSV export

#### ScenarioPdfReportGenerator
**Location:** `src/Ksfraser/Amortizations/Reports/ScenarioPdfReportGenerator.php`

Generates professional PDF reports with styling and formatting.

```php
// Generate PDF
$pdf = $pdfGenerator->generateScenarioPdf(
    $scenario,
    $schedule,
    $loan,
    'scenario-extra-200.pdf'
);

// Generate comparison PDF
$pdf = $pdfGenerator->generateComparisonPdf(
    $scenario1, $schedule1,
    $scenario2, $schedule2,
    $loan
);
```

**Features:**
- Professional headers/footers
- Page breaks for large schedules
- Watermarks (draft, confidential)
- Print-friendly CSS
- Charts and visualizations (optional)

### Views

#### scenario_builder.php
**Location:** `views/views/scenario_builder.php`

Interactive form for creating and comparing scenarios.

**Features:**
- Tabbed interface (Create/Manage/Compare)
- Dynamic form fields based on scenario type
- Real-time calculation previews
- Scenario list management
- Comparison selector

#### scenario_report.php
**Location:** `views/views/scenario_report.php`

Display reports and provide export options.

**Features:**
- Full HTML report rendering
- PDF/CSV export buttons
- Print functionality
- Navigation to create/compare
- Responsive design

## Implementation Timeline

### Phase 9: Core Scenarios (Current)
- ✅ ScenarioAnalysisService - already implemented
- ✅ ScenarioReportGenerator - HTML report generation
- ✅ ScenarioPdfReportGenerator - PDF structure
- ✅ scenario_builder.php - UI for creating scenarios
- ✅ scenario_report.php - Report display

### Phase 10: Integration & Export
**To Do:**
- [ ] Controller routing for scenario actions
- [ ] Session/cache management for scenarios
- [ ] PDF library integration (DomPDF or wkhtmltopdf)
- [ ] CSV export functionality
- [ ] Email report delivery
- [ ] Report archival/storage

### Phase 11: Advanced Features
**Optional (Future):**
- [ ] Saved scenario persistence
- [ ] Scenario favorites/templates
- [ ] Graph/chart visualization
- [ ] Side-by-side PDF comparison
- [ ] Scheduled report generation
- [ ] Mobile-optimized reports

## Testing Scenarios

### Test Case 1: Extra Monthly Payment
**Input:** Loan $100,000, 5% rate, 60 months, extra $200/month
**Expected Output:**
- Payoff: ~48 months (12 months saved)
- Interest savings: ~$4,200
- Final payment: ~$3,200

### Test Case 2: Lump Sum at Period 12
**Input:** $50,000 lump sum payment at month 12
**Expected Output:**
- Payoff: ~36 months (24 months saved)
- Interest savings: ~$18,500

### Test Case 3: Skip Payment
**Input:** Skip period 6 payment
**Expected Output:**
- Payoff: 61 months (extends by 1 month)
- Additional interest: ~$425

### Test Case 4: Comparison
**Input:** Scenario A (extra $200/month) vs Scenario B (lump sum $50k at month 12)
**Expected Output:**
- Side-by-side comparison table
- Interest savings comparison
- Payoff date comparison
- Recommendation based on cash flow

## Database Considerations

**No Persistence Required**
- Scenarios are ephemeral (temporary)
- No changes to actual loan data
- Optional: Save to session for user convenience

**Optional Future:**
- `scenarios` table - Store user's favorite scenarios
- `scenario_comparisons` table - Track analysis history
- `scenario_reports` table - Archive generated reports

## API Examples

### Create and Analyze Scenario
```php
$scenarioService = new ScenarioAnalysisService();
$reportGenerator = new ScenarioReportGenerator($scenarioService);

// Create scenario
$scenario = $scenarioService->createScenario(
    $loan,
    'Extra $200/month',
    ['extra_monthly_payment' => 200]
);

// Generate schedule
$schedule = $scenarioService->generateScenarioSchedule($scenario, $loan);

// Generate HTML report
$html = $reportGenerator->generateScenarioHtmlReport($scenario, $schedule, $loan);

// Output to user
echo $html;
```

### Compare Two Scenarios
```php
$scenario1 = $scenarioService->createScenario($loan, 'Extra $200', ['extra_monthly_payment' => 200]);
$schedule1 = $scenarioService->generateScenarioSchedule($scenario1, $loan);

$scenario2 = $scenarioService->createScenario($loan, 'Lump $50k', ['lump_sum_payment' => 50000, 'lump_sum_month' => 12]);
$schedule2 = $scenarioService->generateScenarioSchedule($scenario2, $loan);

$comparison = $reportGenerator->generateComparisonHtmlReport(
    $scenario1, $schedule1,
    $scenario2, $schedule2,
    $loan
);

echo $comparison;
```

### Export to PDF
```php
$pdfGenerator = new ScenarioPdfReportGenerator($reportGenerator);

$pdf = $pdfGenerator->generateScenarioPdf(
    $scenario,
    $schedule,
    $loan,
    'scenario-analysis.pdf'
);

// Stream to browser or save to file
```

## Report Structure

### Single Scenario Report
```
1. Header
   - Report title
   - Loan information
   - Scenario name
   - Generated date

2. Scenario Summary
   - Total periods
   - Total payments
   - Total principal
   - Total interest
   - Final payment date

3. Amortization Schedule
   - Period-by-period table
   - Date, payment, principal, interest, balance

4. Calculations Summary
   - Total payments
   - Total interest
   - Time to payoff

5. Key Metrics
   - Payoff time reduction
   - Interest savings
   - Effective payoff date
```

### Comparison Report
```
1. Header
   - Report title
   - Both scenario names
   - Generated date

2. Scenario Summaries (Side-by-Side)
   - Same info as single report
   - For both scenarios

3. Comparison Metrics Table
   - Total periods comparison
   - Total interest comparison
   - Total payments comparison
   - Payoff date comparison

4. Savings Analysis
   - Interest savings/costs
   - Payoff acceleration
   - Recommendation
```

## User Stories

### Story 1: Extra Payment Impact
"As a borrower, I want to see how much money I save if I pay an extra $200 per month, so I can decide if I can afford it."

**Acceptance Criteria:**
- ✅ User enters extra monthly payment amount
- ✅ System calculates new payoff date
- ✅ System calculates interest savings
- ✅ Report shows detailed schedule
- ✅ Can export to PDF

### Story 2: Lump Sum Planning
"As a borrower, I want to know how much to pay at tax refund time to maximize interest savings."

**Acceptance Criteria:**
- ✅ User specifies lump sum amount and period
- ✅ System calculates payoff acceleration
- ✅ System calculates exact interest savings
- ✅ Report shows month-by-month impact
- ✅ Can compare multiple lump sum amounts

### Story 3: Skip Payment Cost
"As a borrower, I want to understand the cost of skipping a payment during hardship."

**Acceptance Criteria:**
- ✅ User selects period to skip
- ✅ System calculates additional interest
- ✅ System shows loan extension
- ✅ Report clearly shows costs
- ✅ Can see alternative strategies

### Story 4: Strategy Comparison
"As a borrower, I want to compare multiple payment strategies to find the best approach."

**Acceptance Criteria:**
- ✅ User creates multiple scenarios
- ✅ System allows side-by-side comparison
- ✅ Report shows all metrics clearly
- ✅ Easy to identify best option
- ✅ Can save favorite strategy

## Notes

- All scenarios are **ephemeral** (temporary, no database changes)
- **No modifications** to actual loan records
- **Safe exploration** - users can test multiple strategies
- **Educational** - helps users understand loan mechanics
- **Professional reports** - suitable for financial planning

## Related Features

- Loan Analysis Service
- Payment History Tracking
- Comparative Analysis / Payment Strategy Recommendations (FE-010)
- Refinancing Analysis
- Portfolio Analysis

## References

- Business Requirements: FE-009 (What-If Analysis)
- FunctionalSpecification: UC8 (Generate Paydown Report)
- ScenarioAnalysisService: Fully implemented
