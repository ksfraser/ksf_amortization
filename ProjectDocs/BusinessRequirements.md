# Business Requirements Document (BRD)

## Project Overview
This document outlines the business requirements for the Amortization Module, supporting FrontAccounting, WordPress, and SuiteCRM platforms. The module automates loan amortization schedule management, enabling users to calculate payment schedules, handle extra payments with automatic recalculation, post payments to the GL, and schedule recurring batch posting.

## Business Objectives
- Automate amortization schedule calculation and management for all loan types
- Eliminate manual schedule adjustments by supporting dynamic recalculation when extra payments are recorded
- Integrate seamlessly with platform accounting systems (GL for FA)
- Enable flexible reporting with export/print capabilities
- Support multiple payment and interest calculation frequencies
- Provide scheduled/automated posting to reduce manual data entry

## Stakeholders
- **Finance/Admin Users:** Loan management, GL posting, configuration, reporting
- **Accounts Payable/Receivable:** Entry of loan payments and review of schedules
- **Auditors:** GL reconciliation, posted entry traceability
- **IT/Developers:** Module installation, configuration, maintenance, integration
- **End Users:** Loan data entry, schedule review, payment tracking

## Core Functional Requirements

### Loan Management
- Create new loans with comprehensive parameters (amount, rate, term, frequency)
- Edit existing loans with automatic schedule recalculation
- Delete loans with cascade integrity validation
- Support multiple loan types (Auto, Mortgage, Other, user-defined)

### Amortization Schedule Calculation
- Calculate regular payment using standard amortization formula
- Support flexible payment frequencies: annual, semi-annual, monthly, semi-monthly, bi-weekly, weekly, daily, custom
- Support flexible interest calculation frequencies: annual, semi-annual, monthly, semi-monthly, bi-weekly, weekly, daily, custom
- Generate accurate payment schedules with principal/interest breakdown
- Allow override of calculated payment for special arrangements

### Extra Payment & Event Handling (CRITICAL)
- Record out-of-schedule events (extra payments, skipped payments)
- **AUTOMATICALLY recalculate schedule when events occur**
- Adjust subsequent payment principals, amounts, and remaining balances
- Reduce loan term when extra payments are applied
- Maintain historical event audit trail for compliance

### Staging & Review
- Store calculated schedules in staging table for review before posting
- Display full payment schedule with dates, amounts, principal/interest, balance
- Allow line-item review and approval before GL posting
- Support marking individual payments as reviewed

### GL Integration (FrontAccounting)
- Post individual payment lines to GL accounts with proper journal entries
- Support GL account mapping: Asset, Liability, Expense, Asset Value
- Create journal entries with double-entry bookkeeping
- **Track and store journal entry references (trans_no, trans_type) for later updates**
- Support reversal/adjustment of posted entries when schedules change
- Validate GL accounts and user permissions before posting

### Batch & Scheduled Posting (CRITICAL)
- Post multiple payments in single batch operation
- Support selective posting: "post all" or "post up to date X"
- Enable recurring/automated posting via cron job
- Configure posting schedule (daily, weekly, monthly)
- Support date-based filtering for scheduled posts

### Reporting & Export
- Generate paydown schedule reports (dates, payments, principal, interest, balance)
- Export reports to PDF, Excel, CSV
- Print schedule reports
- Support filtering by loan, date range, status

### User Interface & Configuration
- Admin screens for module settings and GL account mapping
- User screens for loan creation and schedule review
- Out-of-schedule event management interface
- Batch posting control panel with progress tracking
- Input validation with user-friendly error messages

## Non-Functional Requirements
- **Performance:** Schedule calculation for 360+ payments completes in <2 seconds
- **Accuracy:** Calculations accurate to 2 decimal places (cents); auditable
- **Reliability:** All posted entries are atomic and recoverable
- **Maintainability:** Extensible, maintainable codebase following SOLID/DRY principles
- **Multi-platform:** Single business logic codebase for FA, WP, SuiteCRM
- **Backward Compatibility:** Support existing loan data and schedules
- **Data Integrity:** Referential integrity maintained across all tables
- **Auditability:** Complete audit trail of all operations with user/timestamp

## Constraints
- Must follow MVC, SOLID, DRY principles
- Use phpdoc, UML diagrams, and comprehensive UAT scripts
- PSR-4 autoloading via Composer
- Platform-specific code isolated in adaptors
- All table names prefixed with platform-specific prefix (TB_PREF, wpdb->prefix, etc.)
- Schedule recalculation must maintain GL posting integrity
- **PHP 7.3+ compatibility required** (no PHP 8.x only features)

---

## Future Enhancement Requirements (Identified Gaps in Industry Standards)

### PHASE 2: MUST ADD (Critical for Production Completeness)

#### FE-001: Balloon Payments / Graduated Repayment
**Business Case:** Many loan types (auto leases, commercial real estate, construction loans) require balloon structures where a large final payment completes the amortization instead of uniform monthly payments.

**Functional Requirements:**
- Support loan setup with balloon amount or percentage
- Calculate periodic payments based on amortization over shorter period with final balloon
- Display balloon payment in schedule as final line item
- Mark final payment as "Balloon" type for GL posting distinction
- Support "Partially Amortized Loans" (amortize over 30 years but balloon due in 10)

**Implementation Notes:**
- New loan parameter: `balloon_amount` or `balloon_percentage`
- Alternative final payment calculation logic in `calculateSchedule()`
- Schema addition: `balloon_amount` column to loans table
- TDD: Test cases for balloon calculation vs. standard amortization

**Estimated Effort:** 16-20 hours
**Priority:** HIGH - Common loan structure

---

#### FE-002: Variable Interest Rates (ARM - Adjustable Rate Mortgages)
**Business Case:** Realistic mortgage modeling, commercial loans with rate resets, compliance with market conditions.

**Functional Requirements:**
- Support multiple rate periods within single loan
- Define rate periods: start date, end date, rate, reset trigger (date or index-based)
- Recalculate schedule when rate period changes or index updates
- Display rate periods in schedule view with effective rate per period
- Historical rate tracking for audit/compliance

**Implementation Notes:**
- New table: `loan_rate_periods` with (loan_id, effective_date, rate, reset_type, reset_index, reset_trigger)
- Modify `calculateSchedule()` to iterate through rate periods
- New method: `addRatePeriod($loan_id, $effective_date, $rate, $reset_type)`
- New method: `updateRatePeriod($loan_id, $effective_date, $new_rate)`
- Recalculation triggered on rate period expiry or manual update
- TDD: Test cases for multi-period calculations, rate changes mid-loan

**Estimated Effort:** 24-32 hours
**Priority:** HIGH - Essential for mortgage/commercial lending

---

#### FE-003: Partial Payment & Arrears Handling
**Business Case:** Collections scenarios, payment-in-default situations, catch-up payment strategies.

**Functional Requirements:**
- Record partial payments (less than due amount)
- Track arrears/delinquency state
- Calculate late fees or increased interest on overdue principal
- Support catch-up payments (lump sum to bring current)
- Display payment status: current, past-due, delinquent

**Implementation Notes:**
- Extend `recordExtraPayment()` to support partial/short payments
- New table: `loan_payment_status` tracking current/past-due state
- New method: `recordPartialPayment($loan_id, $payment_date, $amount)`
- New method: `applyLateFees($loan_id, $payment_date, $fee_amount)`
- TDD: Test cases for various partial payment scenarios, arrears calculation

**Estimated Effort:** 20-24 hours
**Priority:** HIGH - Business/collections requirement

---

### PHASE 3: SHOULD ADD (Important for Feature Completeness)

#### FE-004: Prepayment Penalties
**Business Case:** Revenue protection for lenders, especially in mortgages and commercial loans.

**Functional Requirements:**
- Define prepayment penalty type: percentage of remaining balance, fixed fee, declining scale
- Calculate penalty based on prepayment amount and remaining term
- Display penalty impact in what-if analysis
- Allow penalty waiver per user role/permission
- Track penalty amounts for revenue reporting

**Implementation Notes:**
- New table: `loan_prepayment_penalties` with (loan_id, penalty_type, penalty_amount/percent, decline_schedule)
- Modify `recordExtraPayment()` to calculate and deduct penalties
- New method: `calculatePrepaymentPenalty($loan_id, $prepayment_amount)`
- New method: `setPrepaymentPenalty($loan_id, $type, $amount_or_percent)`
- Permission check: `can_waive_prepayment_penalty`

**Estimated Effort:** 12-16 hours
**Priority:** MEDIUM - Revenue/compliance significant

---

#### FE-005: Grace Periods and Accrued Interest
**Business Case:** Construction loans, student loans, commercial facilities commonly have grace periods where interest accrues but no payments are due.

**Functional Requirements:**
- Support interest-only periods (no principal payments)
- Support grace periods (no payments or interest accrual, deferred to end)
- Support grace periods with accruing interest
- Calculate accrued interest amount and application point
- Generate separate schedule view showing grace period

**Implementation Notes:**
- New loan parameters: `grace_period_months`, `grace_type` (none/accrual/defer/interest_only)
- New calculation method: `calculateScheduleWithGracePeriod()`
- Modify schedule generation to skip/defer payments during grace
- New table: `loan_grace_periods` tracking accrued amounts
- TDD: Test cases for grace period calculations, interest accrual, application

**Estimated Effort:** 24-28 hours
**Priority:** MEDIUM - Important for construction/student loan products

---

#### FE-006: Loan Refinancing & Mid-Term Modifications
**Business Case:** Rate buydowns, term extensions, balance modifications are common loan lifecycle events.

**Functional Requirements:**
- Support rate modification mid-loan
- Support term extension with recalculation
- Support principal reduction (principal paydown)
- Recalculate remaining schedule from modification date
- Generate refinancing summary with interest savings/costs
- Historical tracking of all refinancing events

**Implementation Notes:**
- New event type: `refinance` (extends LoanEvent table)
- New methods: `refinanceLoan($loan_id, $new_rate, $new_term, $effective_date)`
- New method: `modifyPrincipal($loan_id, $reduction_amount, $effective_date)`
- Recalculation logic: regenerate schedule from mod date forward
- TDD: Test cases for various refinancing scenarios, savings calculation

**Estimated Effort:** 28-32 hours
**Priority:** MEDIUM - Common mortgage/commercial feature

---

#### FE-007: Fee & Charge Amortization (Origination Fees, Servicing Fees, etc.)
**Business Case:** Lenders charge origination fees, servicing fees, insurance; these must integrate into amortization for accurate GL posting and borrower cost.

**Functional Requirements:**
- Define one-time fees (origination, documentation, closing)
- Define recurring fees (servicing, insurance, misc.)
- Integrate fees into total payment or GL posting
- Distinguish fee revenue from interest for accounting
- Display total cost of borrowing including fees

**Implementation Notes:**
- New table: `loan_fees` with (loan_id, fee_type, fee_amount, frequency, start_date)
- Modify GL posting to handle fee accounts separately
- New method: `addFee($loan_id, $fee_type, $amount, $frequency)`
- New method: `calculateTotalBorrowingCost($loan_id)` including all fees
- TDD: Test cases for one-time and recurring fees in schedule

**Estimated Effort:** 16-20 hours
**Priority:** MEDIUM - Commercial lending requirement

---

#### FE-008: Payment Holidays / Forbearance
**Business Case:** Borrower hardship, commercial accommodation, natural disasters commonly trigger payment deferrals.

**Functional Requirements:**
- Define payment holiday periods (dates when no payment due)
- Support interest accrual vs. deferral during holiday
- Recalculate schedule to defer/extend beyond holiday
- Track holiday reason/authorization
- Display holiday periods in schedule timeline

**Implementation Notes:**
- New table: `loan_payment_holidays` with (loan_id, start_date, end_date, accrual_type, reason)
- Extend `recordSkipPayment()` logic or create separate `recordPaymentHoliday()`
- Recalculation: regenerate schedule starting from holiday end date
- TDD: Test cases for various holiday scenarios with/without accrual

**Estimated Effort:** 20-24 hours
**Priority:** MEDIUM - Collections/hardship scenarios

---

### PHASE 4: NICE-TO-HAVE (Enhance User Experience & Compliance)

#### FE-009: What-If Analysis / Scenario Modeling
**Business Case:** Help users understand payment impacts without modifying actual loan.

**Functional Requirements:**
- Create temporary loan scenarios
- "What if I pay $X extra per month?" calculator
- Compare scenarios side-by-side (total interest, payoff date, final payment)
- Save favorite scenarios
- No persistence to database (ephemeral)

**Implementation Notes:**
- New method: `generateScenario($loan_data, $modifications)` - returns calculated schedule without saving
- Frontend: temporary storage in session/cache
- TDD: Test cases for scenario calculations

**Estimated Effort:** 12-16 hours
**Priority:** LOW - Nice-to-have for UX

---

#### FE-010: Comparative Analysis / Payment Strategy Recommendations
**Business Case:** Engage users with payment strategy analysis (accelerated payment vs. standard, extra principal strategies).

**Functional Requirements:**
- Compare multiple payment strategies for same loan
- Show interest savings/costs for each strategy
- Recommend optimal strategy based on user goals
- Timeline visualization of payoff strategies

**Implementation Notes:**
- New method: `compareStrategies($loan_id, array $strategies)`
- Strategies: standard, accelerated_weekly, extra_principal_$X, double_payment
- TDD: Test cases for strategy comparisons

**Estimated Effort:** 16-20 hours
**Priority:** LOW - Enhancement only

---

#### FE-011: Regulatory Compliance Reporting (RESPA, Truth in Lending)
**Business Case:** FrontAccounting deployments to financial institutions require regulatory compliance disclosures.

**Functional Requirements:**
- Generate Loan Estimate (TRID - Loan Estimate under RESPA/TILA)
- Generate Closing Disclosure with final numbers
- Calculate APR vs. stated interest rate
- Disclose total interest, total payments, payment schedule
- Export in compliant format

**Implementation Notes:**
- New report class: `RegulatoryReportGenerator`
- Methods: `generateLoanEstimate()`, `generateClosingDisclosure()`
- Calculations: APR, APRM (annual percentage rate including fees)
- TDD: Test cases for compliance calculations

**Estimated Effort:** 24-28 hours
**Priority:** LOW - Compliance nice-to-have

---

#### FE-012: Loan Insurance & PMI (Private Mortgage Insurance)
**Business Case:** Mortgages typically include PMI when down payment < 20%; auto loans may have credit insurance.

**Functional Requirements:**
- Track PMI/insurance monthly amount
- Calculate when PMI can be removed (typically 80% LTV)
- Include insurance in total monthly payment
- Separate insurance GL posting from payment
- Cancellation date calculation

**Implementation Notes:**
- New table: `loan_insurance` with (loan_id, insurance_type, monthly_premium, cancellation_trigger)
- New method: `calculatePMICancellationDate($loan_id)` based on LTV
- New method: `applyCancellationTrigger($loan_id)` to terminate PMI
- TDD: Test cases for PMI calculations and cancellation

**Estimated Effort:** 16-20 hours
**Priority:** LOW - Mortgage product enhancement

---

#### FE-013: Tax Deduction Reporting & Interest Summaries
**Business Case:** Business loans allow interest deductions; customers need tax-ready reports.

**Functional Requirements:**
- Generate annual interest summary (total paid year-by-year)
- Segregate interest by tax category if multiple
- Export for tax reporting
- Historical interest accumulation tracking

**Implementation Notes:**
- New report: `TaxReportGenerator`
- Method: `generateAnnualInterestSummary($loan_id, $year)`
- TDD: Test cases for interest aggregation by period

**Estimated Effort:** 8-12 hours
**Priority:** LOW - Reporting enhancement

---

## Summary: Current Scope vs. Future Scope

### Phase 1 (CURRENT - COMPLETE ✅)
- ✅ Flexible payment/interest frequencies
- ✅ Extra payment handling with auto-recalculation
- ✅ GL posting with journal tracking
- ✅ Batch & scheduled posting
- ✅ Standard amortization calculations
- ✅ Staging & review workflow

### Phase 2 (MUST ADD - PRODUCTION COMPLETE)
- 🔄 Balloon payments (16-20 hrs)
- 🔄 Variable interest rates (24-32 hrs)
- 🔄 Partial payments & arrears (20-24 hrs)
- **Phase 2 Subtotal: 60-76 hours**

### Phase 3 (SHOULD ADD - FEATURE COMPLETE)
- 🔄 Prepayment penalties (12-16 hrs)
- 🔄 Grace periods (24-28 hrs)
- 🔄 Refinancing support (28-32 hrs)
- 🔄 Fee amortization (16-20 hrs)
- 🔄 Payment holidays (20-24 hrs)
- **Phase 3 Subtotal: 100-120 hours**

### Phase 4 (NICE-TO-HAVE - NICE-TO-HAVE)
- 🔄 What-if analysis (12-16 hrs)
- 🔄 Comparative analysis (16-20 hrs)
- 🔄 Regulatory reporting (24-28 hrs)
- 🔄 Loan insurance/PMI (16-20 hrs)
- 🔄 Tax deduction reports (8-12 hrs)
- **Phase 4 Subtotal: 76-96 hours**

**TOTAL FUTURE WORK: 236-292 hours (~6-7 weeks at 40 hrs/week)**

---
