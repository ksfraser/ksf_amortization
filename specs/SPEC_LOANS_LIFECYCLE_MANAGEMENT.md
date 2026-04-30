# SPEC-LOANS: Loan Lifecycle Management Workflow Specification

**Version**: 1.0 | **Date**: April 28, 2026 | **Status**: Ready for Implementation

---

## 1. OVERVIEW

The Loan Lifecycle Management system manages the complete journey of a loan from origination through payoff or charge-off. Includes underwriting, funding, amortization calculation, payment processing, interest accrual, and account management with comprehensive audit trails.

### Key Features
- Loan application & origination workflow
- Automated underwriting with rule engine
- Credit assessment & decision
- Loan funding & disbursement
- Amortization calculation & scheduling
- Interest accrual & posting
- Fee calculation & management
- Payment processing & posting
- Principal/interest distribution
- Account status management
- Rate adjustments & modifications
- Prepayment handling
- Loan payoff calculations
- Charge-off & write-off workflows
- Investor reporting

### Success Metrics
- Origination to funding: < 5 business days
- Interest accuracy: 99.99%
- Payment posting: 100% accuracy
- System uptime: 99.9%
- Calculation precision: To the cent

---

## 2. LOAN ORIGINATION WORKFLOW

### 2.1 Application Stage

```
BORROWER SUBMITS APPLICATION
├─ Personal Information
│  ├─ Name, SSN, DOB
│  ├─ Contact info (phone, email, address)
│  ├─ Employment info
│  └─ Income (annual, source)
│
├─ Loan Request
│  ├─ Desired amount
│  ├─ Loan purpose
│  ├─ Requested term
│  └─ Preferred rate type
│
├─ Financial Information
│  ├─ Assets
│  ├─ Liabilities
│  ├─ Monthly debt payments
│  └─ Housing payment/rent
│
└─ Authorization Forms
   ├─ Credit authorization
   ├─ Electronic signature consent
   └─ Disclosures acknowledgment
```

### 2.2 Underwriting

```
AUTOMATED UNDERWRITING ENGINE
├─ Pulls Credit Report
│  ├─ FICO score
│  ├─ Trade lines (count, avg age)
│  ├─ Delinquencies (30/60/90 days)
│  ├─ Inquiries (last 6/12 months)
│  └─ Collections/judgments
│
├─ Income Verification
│  ├─ Employment verification (3rd party)
│  ├─ Income documentation (pay stubs, tax returns)
│  ├─ Consistency check vs stated income
│  └─ Stability assessment
│
├─ Debt-to-Income Calculation
│  ├─ Total monthly debt payments
│  ├─ Housing payment
│  ├─ Proposed loan payment
│  ├─ DTI ratio = Total obligations / Gross income
│  └─ DTI assessment
│
├─ Risk Scoring
│  ├─ Credit score weight (40%)
│  ├─ DTI weight (30%)
│  ├─ Income stability weight (15%)
│  ├─ Collateral/assets weight (10%)
│  ├─ Employment history weight (5%)
│  └─ COMPOSITE SCORE
│
└─ Automated Decision Rules
   ├─ Auto-Approve: Score > 0.85
   ├─ Auto-Deny: Score < 0.40
   └─ Manual Review: Score 0.40-0.85
```

### 2.3 Pricing

```
LOAN PRICING ALGORITHM

Base Interest Rate Calculation:
├─ Prime Rate (current): 8.00%
├─ Risk Adjustment
│  ├─ Credit score 750+: -0.5%
│  ├─ Credit score 700-749: -0.25%
│  ├─ Credit score 650-699: +0%
│  ├─ Credit score 600-649: +0.75%
│  └─ Credit score < 600: +1.5%
│
├─ Term Adjustment
│  ├─ 12-24 months: -0.25%
│  ├─ 25-36 months: +0%
│  ├─ 37-60 months: +0.5%
│  └─ 60+ months: +1.0%
│
├─ Amount Adjustment
│  ├─ < $10K: +0.5%
│  ├─ $10K-$50K: +0%
│  ├─ $50K-$100K: -0.25%
│  └─ > $100K: -0.5%
│
└─ FINAL RATE = Base Rate + All Adjustments

Origination Fee:
├─ Standard: 2.5% of loan amount
├─ Minimum: $100
└─ Deducted from funds disbursed

Example:
├─ Prime Rate: 8.00%
├─ Credit (680): +0%
├─ Term (36 mo): +0%
├─ Amount ($50K): -0.25%
├─ APR: 7.75%
├─ Fee: $1,250 (2.5% of $50K)
```

### 2.4 Conditional Loan Approval

```
LOAN APPROVAL CONDITIONS

Standard Conditions (must clear before funding):
├─ Final credit report review (no new delinquencies)
├─ Employment verification (still employed)
├─ Income verification delivery
├─ No inquiries since application (fraud check)
├─ Down payment (if applicable) received
├─ Insurance (if required) proof
├─ Loan documents signed electronically
└─ No credit score drop > 50 points

Approval Decision:
├─ All conditions met: FUNDED
├─ Conditions not met: SUSPENDED (30 days max)
├─ Can't meet conditions: DENIED (adverse notice sent)
└─ Additional documentation: PENDING

Adverse Action Notice (if denied):
├─ Specific reason for denial
├─ Right to explanation
├─ Credit bureau info
└─ Mailed within 30 days
```

---

## 3. AMORTIZATION CALCULATION

### 3.1 Amortization Formula

```
FIXED-RATE AMORTIZATION

Inputs:
├─ Principal (P) = $50,000
├─ Annual Interest Rate (r) = 7.75%
├─ Total Payments (n) = 36 months
└─ Loan Origination Date = 04/15/2026

Step 1: Calculate Monthly Interest Rate
Monthly Rate (i) = Annual Rate / 12
i = 7.75% / 12 = 0.6458% (0.006458 decimal)

Step 2: Calculate Monthly Payment
M = P × [i(1+i)^n] / [(1+i)^n - 1]
M = 50,000 × [0.006458(1.006458)^36] / [(1.006458)^36 - 1]
M = 50,000 × [0.006458 × 1.25158] / [0.25158]
M = 50,000 × 0.01020
M = $510.00 (rounded to nearest cent)

Step 3: Build Amortization Schedule
For each period:
├─ Interest = Remaining Balance × Monthly Rate
├─ Principal = Payment - Interest
├─ New Balance = Previous Balance - Principal
└─ Record in amortization table

Example First 3 Payments:
┌────┬──────────┬──────────┬─────────┬────────────┐
│ Mo │ Payment  │ Interest │Principal│  Balance  │
├────┼──────────┼──────────┼─────────┼────────────┤
│ 1  │  $510.00 │  $323.33 │ $186.67 │ $49,813.33 │
│ 2  │  $510.00 │  $321.81 │ $188.19 │ $49,625.14 │
│ 3  │  $510.00 │  $320.28 │ $189.72 │ $49,435.42 │
└────┴──────────┴──────────┴─────────┴────────────┘

Final Payment Adjustment:
Last month payment may differ slightly due to rounding
Adjusted to bring balance to exactly $0
```

### 3.2 Amortization Entity

```php
class Amortization {
    private int $loan_id;
    private float $original_amount;
    private float $interest_rate;
    private int $term_months;
    private \DateTime $start_date;
    private float $monthly_payment;
    
    // Complete schedule stored for quick lookup
    private array $schedule = [
        1 => [
            'payment_number' => 1,
            'payment_date' => '2026-05-15',
            'payment_amount' => 510.00,
            'principal' => 186.67,
            'interest' => 323.33,
            'balance' => 49813.33
        ],
        // ... 35 more rows
    ];
}
```

---

## 4. PAYMENT PROCESSING

### 4.1 Payment Types

```
Regular Payment:
├─ Scheduled monthly payment
├─ Applied to principal + interest
├─ Posted to account on payment date
└─ Balance reduced by principal amount

Partial Payment:
├─ Less than scheduled amount
├─ Applied to interest first
├─ Deferred/held if company policy
└─ May trigger delinquency status

Extra/Principal Payment:
├─ Additional payment beyond scheduled
├─ Applied 100% to principal
├─ Reduces total interest & term (if amortization recalc enabled)
└─ No change to payment schedule

Lump Sum Payment:
├─ Large payment toward principal
├─ May occur at payoff
├─ Applied after all scheduled payments
└─ Terminates loan early

Past Payment:
├─ Payment for missed/delinquent period
├─ Applied to oldest delinquent month first
├─ Late fees/penalties applied
└─ Delinquency cured when current
```

### 4.2 Payment Receipt & Posting

```
Payment Processing Flow:

1. PAYMENT RECEIPT (Various Methods)
   ├─ ACH bank transfer
   ├─ Wire transfer
   ├─ Check (mailed)
   ├─ Credit/debit card (online)
   └─ Mobile app payment
   
2. FUNDING CONFIRMATION
   ├─ Bank confirms receipt
   ├─ Funds available (1-2 business days for ACH)
   ├─ Payment matched to account (account number)
   └─ Transaction ID recorded
   
3. POSTING TRANSACTION (Next business day after settlement)
   ├─ Retrieve payment from processor
   ├─ Match to correct loan account
   ├─ Verify amount & date
   ├─ Lock transaction (no updates)
   └─ Write to transactions table
   
4. DISTRIBUTE TO PRINCIPLES
   ├─ Apply Interest First (current month)
   ├─ Apply Principal Second (reduces balance)
   ├─ Apply Fees Third (late fees, NSF fees)
   ├─ Generate receipt
   └─ Update account status
   
5. UPDATE ACCOUNT STATUS
   ├─ Check if payment current/past-due
   ├─ Update balance
   ├─ Update next payment date
   ├─ Update account stage (current/delinquent/paid-off)
   └─ Generate statement

6. GENERATE RECEIPT & NOTIFICATION
   ├─ Email confirmation to borrower
   ├─ SMS confirmation (if opted in)
   ├─ Downloadable receipt
   └─ Portal access to receipt
```

### 4.3 Payment Validation

```
Validation Rules:

Amount Validation:
├─ Amount > $0.01
├─ Amount < $999,999.99
├─ No partial cents (round to nearest cent)
└─ Amount matches reference

Account Validation:
├─ Account exists in system
├─ Account status allows payments (not closed, not fraud hold)
├─ Account not in dispute
└─ Account active

Timing Validation:
├─ Payment date not in future (except scheduled)
├─ Payment date not more than 5 days past
├─ Payment not duplicate (duplicate detection)
└─ Transaction ID unique

Fraud Detection:
├─ Amount unusually large (> 3x normal payment)
├─ Rapid payments from different accounts
├─ Payment from unauthorized account
└─ Velocity check (payments per day)
```

---

## 5. INTEREST & FEE CALCULATION

### 5.1 Interest Accrual

```
Daily Interest Accrual:

Formula:
Daily Interest = (Balance × Annual Rate) / 365

Example:
├─ Balance: $49,813.33
├─ Annual Rate: 7.75%
├─ Days: 30 (April has 30 days)
├─ Daily Interest = ($49,813.33 × 7.75%) / 365 = $1.055 per day
├─ Monthly Interest = $1.055 × 30 = $31.65
└─ Interest accrued each day, compounded monthly

Accrual Frequency:
├─ Calculated: Daily (every 24 hours)
├─ Posted: Monthly (on payment due date)
├─ Deferred: Until payment received if delinquent
└─ Realized: Only when payment received
```

### 5.2 Fee Schedule

```
Late Payment Fee (triggered when 10+ days late):
├─ Amount: 3% of scheduled payment OR $25 (whichever greater)
├─ When Applied: At 10 days overdue
├─ Applied Once Until: Payment received
├─ Posted To: Separate fee balance
└─ Example: Payment $500 → Fee = $25 (3% = $15)

NSF/Returned Payment Fee:
├─ Amount: $35 per failed payment
├─ When Applied: When payment returned by bank
├─ Applied To: Separate fee balance
└─ Examples: ACH reject, check bounce, card decline

Loan Modification Fee (if allowed):
├─ Amount: $250-$500 (one-time)
├─ When Applied: When modification processed
├─ Applied To: Principal or deducted from account
└─ Used for: Rate changes, term extensions

Prepayment Penalty (if applicable):
├─ Amount: 1-3% of remaining principal (state dependent)
├─ When Applied: When extra principal payment received
├─ Applied To: Reduces benefit of prepayment
└─ Varies by state & loan type
```

### 5.3 Fee Calculations

```php
class FeeCalculator {
    public function calculateLateFee(
        float $scheduled_payment,
        int $days_late
    ): float {
        if ($days_late < 10) {
            return 0;
        }
        
        $percentage_fee = $scheduled_payment * 0.03;  // 3%
        $flat_fee = 25.00;
        
        return max($percentage_fee, $flat_fee);
    }
    
    public function calculatePrepaymentPenalty(
        float $extra_payment,
        float $remaining_balance,
        int $months_remaining,
        string $state
    ): float {
        // Varies by state; this is example for CA (1%)
        if ($state !== 'CA') {
            return 0;  // No penalty
        }
        
        if ($months_remaining <= 6) {
            return 0;  // No penalty last 6 months
        }
        
        return $extra_payment * 0.01;  // 1% penalty
    }
}
```

---

## 6. ACCOUNT MANAGEMENT

### 6.1 Account Stages

```
LOAN LIFECYCLE STAGES

1. ORIGINATION (Application → Approval)
   └─ Duration: 3-5 business days
   └─ Action: Underwriting, pricing, approval

2. PENDING (Approval → Funding)
   └─ Duration: 2-5 business days
   └─ Action: Document prep, signature, docusign

3. ACTIVE (Funded → All payments received)
   └─ Duration: Full loan term
   └─ Action: Payment scheduling, status monitoring

4. DELINQUENT (10+ days late)
   └─ Substages: 30/60/90+ days
   └─ Action: Collections workflow

5. CURRENT (After delinquent, payments resume)
   └─ Duration: Until paid off
   └─ Action: Normal payment processing

6. PAID_OFF (Final payment processed)
   └─ Duration: Ongoing (closed account)
   └─ Action: Statement archival, final reporting

7. CHARGE_OFF (> 180 days past due typically)
   └─ Action: Write-off to loss, investor notification

8. CLOSED (Borrower request, company request)
   └─ Action: No further activity
```

### 6.2 Loan Status Transitions

```
State Machine:

ORIGINATION
   ↓ (Approval granted)
PENDING
   ↓ (Funds disbursed)
ACTIVE
   ├─ (Payment on time)
   │  └─ CURRENT
   │     ├─ (10+ days late)
   │     │  └─ DELINQUENT_30
   │     │     ├─ (60+ days late)
   │     │     │  └─ DELINQUENT_60
   │     │     │     ├─ (90+ days late)
   │     │     │     │  └─ DELINQUENT_90+
   │     │     │     │     ├─ (Payment received, < 180 days late)
   │     │     │     │     │  └─ CURRENT
   │     │     │     │     └─ (180+ days late)
   │     │     │     │        └─ CHARGED_OFF
   │     │     │     └─ (Payment received)
   │     │     │        └─ CURRENT
   │     │     └─ (Payment received)
   │     │        └─ CURRENT
   │     └─ (Payment received)
   │        └─ CURRENT
   │
   ├─ (All payments complete)
   │  └─ PAID_OFF
   │
   └─ (Account closed)
      └─ CLOSED
```

---

## 7. DATA MODEL

### 7.1 Loan Entity

```php
class Loan {
    private int $loan_id;
    private string $loan_number;  // Unique human-readable ID
    private int $borrower_no;
    private string $stage;  // ORIGINATION, PENDING, ACTIVE, etc.
    private string $status;  // CURRENT, DELINQUENT_30, PAID_OFF, etc.
    
    // Loan Details
    private float $original_amount;
    private float $current_balance;
    private float $interest_rate;
    private int $term_months;
    private string $loan_type;  // Personal, Auto, Business, etc.
    private float $monthly_payment;
    
    // Dates
    private \Date $origination_date;
    private \Date $funding_date;
    private \Date $next_due_date;
    private \Date $maturity_date;
    private \Date $first_payment_date;
    
    // Amounts
    private float $total_interest_paid;
    private float $total_principal_paid;
    private float $total_fees_charged;
    private float $total_fees_paid;
    
    // Current Status
    private int $days_past_due;
    private float $past_due_amount;
    private float $interest_accrued;
    private float $late_fees;
    
    // Investor
    private int $investor_id;
    private bool $investor_owned;
}
```

### 7.2 Amortization Schedule Entity

```php
class AmortizationSchedule {
    private int $schedule_id;
    private int $loan_id;
    private int $period;  // Payment number (1, 2, 3, ...)
    private \Date $due_date;
    private \Date $payment_date;  // Actual payment date (null if not yet paid)
    
    private float $beginning_balance;
    private float $scheduled_payment;
    private float $principal;
    private float $interest;
    private float $ending_balance;
    
    private string $status;  // PENDING, PAID, LATE, PAST_DUE
    private float $amount_paid;  // Actual amount paid (if different from scheduled)
}
```

### 7.3 Database Schema

```sql
CREATE TABLE 0_ksf_loans (
    loan_id INT AUTO_INCREMENT PRIMARY KEY,
    loan_number VARCHAR(20) UNIQUE NOT NULL,
    borrower_no INT NOT NULL,
    
    stage VARCHAR(20) NOT NULL DEFAULT 'ORIGINATION',
    status VARCHAR(20) NOT NULL DEFAULT 'CURRENT',
    
    original_amount DECIMAL(15,2) NOT NULL,
    current_balance DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(6,4) NOT NULL,
    term_months INT NOT NULL,
    loan_type VARCHAR(50),
    monthly_payment DECIMAL(15,2),
    
    origination_date DATE,
    funding_date DATE,
    next_due_date DATE,
    maturity_date DATE,
    first_payment_date DATE,
    
    days_past_due INT DEFAULT 0,
    past_due_amount DECIMAL(15,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (borrower_no) REFERENCES 0_debtors(debtor_no),
    INDEX idx_status (status),
    INDEX idx_borrower (borrower_no),
    INDEX idx_next_due (next_due_date)
);

CREATE TABLE 0_ksf_amortization_schedule (
    schedule_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    period INT NOT NULL,
    
    due_date DATE NOT NULL,
    payment_date DATE,
    
    beginning_balance DECIMAL(15,2),
    scheduled_payment DECIMAL(15,2),
    principal DECIMAL(15,2),
    interest DECIMAL(15,2),
    ending_balance DECIMAL(15,2),
    
    status VARCHAR(20) DEFAULT 'PENDING',
    amount_paid DECIMAL(15,2),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES 0_ksf_loans(loan_id),
    UNIQUE KEY uk_loan_period (loan_id, period),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
);

CREATE TABLE 0_ksf_payments (
    payment_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    
    payment_date DATE NOT NULL,
    settlement_date DATE,
    
    payment_amount DECIMAL(15,2) NOT NULL,
    principal_applied DECIMAL(15,2),
    interest_applied DECIMAL(15,2),
    fees_applied DECIMAL(15,2),
    
    payment_method VARCHAR(20),  -- ACH, CHECK, CARD, WIRE
    transaction_id VARCHAR(50),
    processor_reference VARCHAR(100),
    
    status VARCHAR(20),  -- PENDING, SETTLED, FAILED, RETURNED
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES 0_ksf_loans(loan_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_status (status)
);
```

---

## 8. API ENDPOINTS

### 8.1 Loan Management

```
GET /api/v1/loans/:loan_id
───────────────────────────
Response: 200 OK
{
  "loan_id": 100,
  "loan_number": "LN-2026-001",
  "status": "current",
  "borrower_name": "John Doe",
  "original_amount": 50000.00,
  "current_balance": 25000.00,
  "interest_rate": 7.75,
  "term_months": 36,
  "monthly_payment": 510.00,
  "next_due_date": "2026-05-15",
  "days_past_due": 0,
  "paid_to_date": "2026-04-15"
}

─────────────────────────────

POST /api/v1/loans
──────────────────
Request:
{
  "borrower_no": 12345,
  "amount": 50000,
  "term_months": 36,
  "loan_type": "personal",
  "purpose": "debt_consolidation"
}

Response: 201 Created
{
  "loan_id": 100,
  "loan_number": "LN-2026-001",
  "status": "origination",
  "created_at": "2026-04-15T10:00:00Z"
}
```

### 8.2 Amortization

```
GET /api/v1/loans/:loan_id/amortization
────────────────────────────────────────
Response: 200 OK
{
  "loan_id": 100,
  "term_months": 36,
  "schedule": [
    {
      "period": 1,
      "due_date": "2026-05-15",
      "payment": 510.00,
      "principal": 186.67,
      "interest": 323.33,
      "balance": 49813.33
    },
    // ... 35 more rows ...
  ]
}
```

### 8.3 Payments

```
POST /api/v1/loans/:loan_id/payments
────────────────────────────────────
Request:
{
  "amount": 510.00,
  "payment_method": "ach",
  "payment_date": "2026-04-15"
}

Response: 201 Created
{
  "payment_id": 1001,
  "status": "pending",
  "settlement_date": "2026-04-17",
  "confirmation_number": "PAY-20260415-001"
}
```

---

## 9. IMPLEMENTATION CHECKLIST

Phase 1: Foundation (3 weeks)
- [ ] Database schema & migrations
- [ ] Loan entity & repository
- [ ] Origination service structure
- [ ] API gateway setup

Phase 2: Calculation Engine (3 weeks)
- [ ] Amortization calculator
- [ ] Interest accrual system
- [ ] Fee calculation
- [ ] Comprehensive testing

Phase 3: Workflow Management (3 weeks)
- [ ] Loan creation & approval
- [ ] Funding workflow
- [ ] Account status management
- [ ] State machine implementation

Phase 4: Payment Processing (3 weeks)
- [ ] Payment processor integration
- [ ] Payment posting logic
- [ ] Receipt generation
- [ ] Notification service

Phase 5: Testing & Integration (2 weeks)
- [ ] End-to-end workflow testing
- [ ] Integration with bank API
- [ ] Performance optimization
- [ ] Production deployment

---

**Status**: Specification complete, ready for development  
**Estimated Timeline**: 12 weeks (with 4 developers)  
**Next Step**: Database schema implementation

