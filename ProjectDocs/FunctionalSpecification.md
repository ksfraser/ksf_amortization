# Functional Specification

## Module Scope
The Amortization Module provides comprehensive loan amortization schedule management across multiple platforms (FrontAccounting, WordPress, SuiteCRM). It supports flexible payment and interest calculation frequencies, handles mid-term adjustments via extra payment recording with automatic recalculation, integrates with platform GL systems (FA), and enables batch/scheduled posting.

## Critical Use Cases (High Priority)

### UC1: Create Loan
**Actors:** Finance Admin, AP/AR Staff  
**Preconditions:** User has Create Loan permission  
**Main Flow:**
1. User navigates to "New Loan" screen
2. Selects loan type (Auto, Mortgage, Other, custom)
3. Selects/enters borrower (customer, supplier, employee)
4. Enters amount financed (e.g., $25,000)
5. Enters annual interest rate (e.g., 5.5%)
6. Selects **payment frequency** (monthly, bi-weekly, weekly, daily, custom)
7. Selects **interest calculation frequency** (monthly, daily, weekly, custom)
8. Enters loan term: either years + payments per year, or total payments
9. **CRITICAL:** System calculates regular payment amount
10. User can override payment if needed
11. Enters first payment date and last payment date
12. **CRITICAL:** System generates full amortization schedule
13. User reviews schedule and confirms
14. System validates: last payment date ≥ calculated payoff date
15. User saves loan
**Postconditions:** 
- Loan record created in ksf_loans_summary
- Full amortization schedule generated and stored in ksf_amortization_staging
- Schedule shows payment date, amount, principal, interest, balance for each period

**Critical Notes:**
- Interest calculation must use correct frequency (not hardcoded to monthly)
- Payment date increments must match payment frequency
- Final payment may be adjusted if balance < regular payment

### UC2: Record Extra Payment (CRITICAL FEATURE)
**Actors:** AP/AR Staff, Finance Admin  
**Preconditions:** Loan has active schedule  
**Main Flow:**
1. User opens loan detail screen
2. User clicks "Record Extra Payment" button
3. User enters payment date and extra amount paid
4. User optionally adds notes (e.g., "Bonus applied to loan")
5. System creates LoanEvent record with event_type='extra'
6. **CRITICAL - RECALCULATION PHASE:**
   - System fetches all LoanEvents up to this date
   - System calculates total extra payments applied to date
   - System identifies first schedule row AFTER the extra payment date
   - System applies extra amount to remaining balance
   - System recalculates all subsequent rows:
     * Reduces principal portion based on extra payment
     * Recalculates interest on new balance
     * Updates remaining balance
     * May reduce number of remaining payments
7. System updates ksf_amortization_staging with recalculated values
8. **CRITICAL:** If any rows were posted to GL:
   - System marks them for reversal (voided flag)
   - Notifies user: "GL entries affected - reversal recommended"
9. User reviews recalculated schedule
10. User confirms or cancels extra payment
**Postconditions:** 
- LoanEvent created in ksf_loan_events
- ksf_amortization_staging recalculated from payment date forward
- Posted GL entries marked for reversal
- Audit log shows recalculation

**Example:**
```
Initial Schedule:
Pmt 1 (Jan 1): $1000 payment, $600 principal, $400 interest, $24,400 balance
Pmt 2 (Feb 1): $1000 payment, $610 principal, $390 interest, $23,790 balance
Pmt 3 (Mar 1): $1000 payment, $620 principal, $380 interest, $23,170 balance

User records extra payment on Jan 15: $500

Recalculated:
Pmt 1 (Jan 1): $1000 payment, $600 principal, $400 interest, $24,400 balance
Extra (Jan 15): $500 extra, applied to principal
Pmt 2 (Feb 1): $895 payment (less principal needed), $500+X principal, ~$395 interest
Pmt 3 (Mar 1): $895 payment, recalculated with new balance
... (fewer total payments needed)
```

### UC3: Post Payment to GL (CRITICAL FOR FA)
**Actors:** Finance Admin  
**Preconditions:** 
- Loan has staging records
- GL accounts configured for loan
- User has GL posting permission
**Main Flow:**
1. User navigates to loan detail → staging table
2. User selects one or more payment lines to post (or all unposted)
3. User optionally filters: "Post all" or "Post up to date X"
4. User reviews summary: number of lines, total amount
5. System validates:
   - GL accounts exist in FA GL chart
   - GL accounts are active (not closed)
   - User has permission to post to these accounts
6. **CRITICAL - FOR EACH PAYMENT:**
   - System creates journal entry with:
     * Debit: Loan liability account (reduces loan balance)
     * Debit: Interest expense account (accrues interest)
     * Credit: Cash/bank account (payment received)
   - System captures returned trans_no and trans_type
7. **CRITICAL - UPDATE STAGING:**
   - System updates ksf_amortization_staging row:
     * posted_to_gl = 1
     * trans_no = captured value
     * trans_type = captured value
     * posted_at = current timestamp
     * posted_by = current user
8. System displays results:
   - Successfully posted: 47 payments, $47,000
   - Failed: 0 payments
9. User confirms completion
**Postconditions:** 
- Journal entries created in FA GL
- ksf_amortization_staging updated with trans_no/trans_type
- Audit trail shows posting

**Critical Notes:**
- **MUST capture and store trans_no and trans_type** - needed for reversals if schedule changes
- Journal entry should reference loan ID: "LOAN-123-20250115"
- If any posts fail, report failures and don't mark as posted

### UC4: Batch Post Payments (HIGH PRIORITY)
**Actors:** Finance Admin, Automated Cron Job  
**Preconditions:** Multiple unposted payment lines exist  
**Main Flow:**
1. User navigates to "Batch Posting" screen
2. Optionally selects specific loans (or "all loans")
3. Selects posting option:
   - "Post all unposted payments", OR
   - "Post up to date 2025-03-31"
4. System shows preview:
   - 47 payments from Loan-1
   - 32 payments from Loan-2
   - Total: 79 payments, $79,000
5. User confirms batch post
6. System processes each payment (as in UC3)
7. System displays results:
   - Successfully posted: 79 payments
   - Failed: 0 payments
   - Time: 15 seconds
8. User reviews and closes
**Postconditions:** All selected payments posted to GL

### UC5: Schedule Recurring Posting (HIGH PRIORITY)
**Actors:** System Administrator  
**Preconditions:** Cron job framework configured  
**Main Flow:**
1. Admin navigates to "Cron Configuration"
2. Enables "Auto Post Amortization Payments"
3. Sets schedule: "Daily at 2:00 AM"
4. Selects which loans to auto-post: "All" or specific loans
5. Selects posting rule: "Post all unposted" or "Post up to 7 days ago"
6. **CRON JOB RUNS ON SCHEDULE:**
   - Checks for unposted payments matching criteria
   - Performs batch post (as in UC4)
   - Logs results to cron_log or email
   - On failure, sends alert to admin
7. Admin can manually check cron results anytime
**Postconditions:** Payments posted automatically per schedule

### UC6: Record Skipped Payment
**Actors:** AP/AR Staff, Finance Admin  
**Preconditions:** Loan has active schedule  
**Main Flow:**
1. User opens loan detail
2. Clicks "Record Event" → "Skipped Payment"
3. Enters payment date to skip
4. System creates LoanEvent with event_type='skip'
5. System recalculates schedule from that date forward
6. Recalculated schedules shifts remaining payments forward
7. Loan term may be extended
**Postconditions:** Event recorded, schedule extended

## Standard Use Cases (Normal Priority)

### UC7: View & Approve Schedule
**Actors:** Finance Admin, AP/AR Staff  
**Preconditions:** Loan exists with schedule  
**Main Flow:**
1. User opens loan detail
2. Views full payment schedule table:
   - Payment #, Payment Date, Payment Amount
   - Principal Portion, Interest Portion, Remaining Balance
   - Posted (Y/N), GL Reference (if posted)
3. User can filter by date range or payment status
4. User can mark lines as reviewed
**Postconditions:** User confirms understanding of schedule

### UC8: Generate Paydown Report
**Actors:** Finance Admin, AP/AR Staff  
**Preconditions:** Loan exists  
**Main Flow:**
1. User opens Reporting screen
2. Selects loan and date range
3. System generates report showing:
   - Loan summary (amount, rate, term)
   - Payment schedule table
   - Totals (principal paid, interest paid, balance remaining)
   - Any extra payments/events applied
4. User can view on screen, print, export to PDF/Excel/CSV
**Postconditions:** Report available for use

### UC9: Edit Loan Parameters
**Actors:** Finance Admin  
**Preconditions:** Loan exists, preferably with no posted entries  
**Main Flow:**
1. User opens loan detail
2. Clicks "Edit Loan"
3. Updates parameters (amount, rate, term, frequency)
4. System validates new parameters
5. System recalculates schedule with new parameters
6. User reviews changes
7. User confirms save
8. **If posted entries exist:** System marks them for reversal
**Postconditions:** Loan updated, schedule recalculated

### UC10: Reverse Posted Entry
**Actors:** Finance Admin  
**Preconditions:** Payment posted to GL (trans_no exists)  
**Main Flow:**
1. User identifies payment to reverse
2. Clicks "Reverse Posting"
3. System uses FA's void_journal_entry(trans_type, trans_no)
4. System marks staging row as voided = 1, posted_to_gl = 0
5. System logs reversal in audit trail
**Postconditions:** GL entry reversed, payment marked unposted

## Data Model

### Core Tables

**ksf_loans_summary**
```
id INT PRIMARY KEY
borrower_id INT NOT NULL
borrower_type VARCHAR(32) -- 'Customer', 'Supplier', 'Employee'
amount_financed DECIMAL(15,2)
interest_rate DECIMAL(5,2) -- Annual percentage
loan_term_years INT
payments_per_year INT
first_payment_date DATE
last_payment_date DATE (calculated or estimated)
regular_payment DECIMAL(15,2)
override_payment TINYINT(1)
loan_type VARCHAR(32) -- 'Auto', 'Mortgage', 'Other'
interest_calc_frequency VARCHAR(32) -- 'daily', 'weekly', 'monthly', etc.
status VARCHAR(16) -- 'active', 'closed', 'defaulted'
created_by INT
created_at TIMESTAMP
updated_at TIMESTAMP
description VARCHAR(255)
```

**ksf_amortization_staging**
```
id INT PRIMARY KEY
loan_id INT FOREIGN KEY
payment_date DATE
payment_amount DECIMAL(15,2)
principal_portion DECIMAL(15,2)
interest_portion DECIMAL(15,2)
remaining_balance DECIMAL(15,2)
posted_to_gl TINYINT(1) -- 0=not posted, 1=posted
trans_no INT -- FA journal entry number
trans_type INT -- FA journal type
voided TINYINT(1) -- Mark as void if schedule recalculated
posted_at TIMESTAMP
posted_by INT (user ID)
reviewed_at TIMESTAMP
reviewed_by INT (user ID)
```

**ksf_loan_events**
```
id INT PRIMARY KEY
loan_id INT FOREIGN KEY
event_type VARCHAR(32) -- 'extra', 'skip'
event_date DATE
amount DECIMAL(15,2) -- Principal amount if 'extra', 0 if 'skip'
notes TEXT
created_at TIMESTAMP
created_by INT
```

**ksf_gl_mapping** (FrontAccounting)
```
id INT PRIMARY KEY
loan_id INT FOREIGN KEY
account_type VARCHAR(32) -- 'asset', 'liability', 'expense', 'asset_value'
gl_account_code VARCHAR(16)
created_at TIMESTAMP
updated_at TIMESTAMP
```

**ksf_amort_loan_types**
```
id INT PRIMARY KEY
name VARCHAR(64)
description VARCHAR(255)
```

**ksf_amort_interest_calc_frequencies**
```
id INT PRIMARY KEY
name VARCHAR(64) -- 'daily', 'weekly', 'monthly', etc.
description VARCHAR(255)
```

## User Interface Components

### Primary Screens
1. **Loan List** - Overview of all loans (status, balance, next payment)
2. **Loan Detail** - Create/edit loan, view schedule, manage events
3. **Payment Schedule** - Full amortization table with posting controls
4. **Event Management** - Add/edit extra payments, skipped payments
5. **Batch Posting** - Multi-loan posting with date filtering
6. **Reporting** - Schedule reports with export options
7. **GL Configuration** - Map GL accounts to loans (FA only)
8. **Cron Configuration** - Schedule automated posting

## Integration Points

### FrontAccounting
- **Menu:** "Banking and General Ledger" → "Amortization"
- **GL:** Uses write_journal_entries(), void_journal_entry() APIs
- **Database:** Uses TB_PREF for table prefixing
- **Access:** Uses FA access levels (SA_CUSTOMER, etc.)

### WordPress
- **Menu:** Custom admin submenu
- **Database:** Uses wpdb->prefix
- **Roles:** Integrates with WordPress user roles

### SuiteCRM
- **Module:** Custom amortization module
- **Borrowers:** Links to Accounts/Contacts
- **Database:** Direct SQL with SuiteCRM conventions

## Security & Permissions

### Access Levels
- **Loans Administrator:** Full CRUD, GL posting, configuration, cron management
- **Loans Reader:** View-only access
- **AP/AR User:** Can record payments, view schedules; cannot post to GL

### Controls
- Input validation on all user inputs
- CSRF token validation on forms
- SQL injection prevention via prepared statements
- GL account validation before posting
- Audit trail: all operations logged with user/timestamp
- Transactional integrity for GL postings

---
