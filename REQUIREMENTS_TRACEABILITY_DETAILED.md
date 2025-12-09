# Requirements Traceability Matrix - Detailed

## Overview
This detailed matrix maps business and functional requirements to implementation code, test cases, and UAT scripts. It ensures complete coverage and identifies gaps.

## Critical Path Requirements (Phase 1)

### REQ-001: Flexible Payment & Interest Frequency Calculation
**Business Requirement:** Support flexible payment and interest calculation frequencies  
**Functional Requirement:** UC1 - Create Loan  
**Current Status:** ⚠️ **NEEDS WORK** - Hardcoded to monthly  
**Impact:** HIGH - Affects all loan types  

**Implementation Requirements:**
```
Must Fix in AmortizationModel:
1. calculatePayment($principal, $rate, $num_payments, $payment_frequency)
   - Calculate based on actual frequency, not hardcoded formula
   - Currently: monthly_rate = rate/100/12 (WRONG for weekly/daily)
   - Fix: Convert payment frequency to correct period rate
   
2. calculateSchedule($loan_id)
   - Use interest_calc_frequency from loan record (currently ignored)
   - Date increment must match payment_frequency (currently hardcoded +1 month)
   - Interest calc must use correct period (currently always /12)
   
3. New Methods Needed:
   - getPaymentIntervalDays($payments_per_year)
   - getInterestCalcPeriodRate($frequency, $annual_rate)
   - getNextPaymentDate($current_date, $frequency)
```

**Code Files to Modify:**
- `src/Ksfraser/Amortizations/AmortizationModel.php` - fix calculatePayment(), calculateSchedule()
- `src/Ksfraser/Amortizations/controller.php` - pass frequency to calculations
- `modules/amortization/FADataProvider.php` - ensure all fields captured

**Unit Tests Required:**
- `test_calculatePaymentMonthlyFrequency()` - baseline
- `test_calculatePaymentBiWeeklyFrequency()` - 26 payments/year
- `test_calculatePaymentWeeklyFrequency()` - 52 payments/year
- `test_calculatePaymentDailyFrequency()` - 365 payments/year
- `test_calculateScheduleWithDailyInterest()` - interest compounds daily
- `test_calculateScheduleWithMonthlyInterest()` - interest compounds monthly
- `test_dateIncrementMatchesPaymentFrequency()` - weekly dates increment by 7 days
- `test_calculateScheduleAccuracy()` - final balance = 0 within 2 cents

**UAT Scripts Required:**
- `FA_Create_Loan_Monthly_Payment_Monthly_Interest.md`
- `FA_Create_Loan_BiWeekly_Payment_Monthly_Interest.md`
- `FA_Create_Loan_Weekly_Payment_Daily_Interest.md`
- `FA_Verify_Calculation_Against_External_Tool.md`

**Acceptance Criteria:**
- ✓ Schedule calculations match amortization calculators for multiple frequencies
- ✓ Final payment brings balance to $0.00 (±$0.02)
- ✓ All dates increment correctly per frequency
- ✓ Interest calculation uses correct period

---

### REQ-002: Extra Payment Handling with Automatic Recalculation (CRITICAL)
**Business Requirement:** Record extra payments and automatically recalculate entire schedule  
**Functional Requirement:** UC2 - Record Extra Payment  
**Current Status:** ❌ **NOT IMPLEMENTED**  
**Impact:** CRITICAL - Core feature from requirements  

**Implementation Requirements:**
```
Must Implement in AmortizationModel:
1. recordExtraPayment($loan_id, $event_date, $amount, $notes)
   - Create LoanEvent record via DataProvider
   - Trigger recalculateScheduleAfterEvent()
   - Return updated schedule
   
2. recalculateScheduleAfterEvent($loan_id, $event_date)
   - Get all events up to event_date
   - Sum extra payments applied before event_date
   - Find first staging row AFTER event_date
   - Reduce that row's remaining_balance by extra amount
   - Recalculate all subsequent rows with new balance
   - Update all affected staging rows
   - Mark any posted rows (posted_to_gl=1) for potential reversal
   
3. Helper Methods:
   - getTotalExtraPaymentsBeforeDate($loan_id, $date)
   - getScheduleRowsAfterDate($loan_id, $date)
   - recalculateRowBalance($loan, $row, $new_starting_balance)

DataProvider Methods Needed:
- insertLoanEvent(LoanEvent $event)
- getLoanEvents($loan_id)
- updateScheduleRow($staging_id, array $updates)
- deleteScheduleAfterDate($loan_id, $date)
- getScheduleRowsAfterDate($loan_id, $date)
```

**Code Files to Create/Modify:**
- `src/Ksfraser/Amortizations/AmortizationModel.php` - new methods
- `src/Ksfraser/Amortizations/DataProviderInterface.php` - new method signatures
- `modules/amortization/FADataProvider.php` - implement interface methods
- `src/Ksfraser/Amortizations/GenericLoanEventProvider.php` - already exists, may need updates
- `src/Ksfraser/Amortizations/controller.php` - add UI route for extra payment

**Unit Tests Required (10+ test methods):**
- `test_recordExtraPaymentCreatesLoanEvent()`
- `test_recordExtraPaymentTriggersRecalculation()`
- `test_extraPaymentReducesBalance()`
- `test_extraPaymentReducesRemainingPayments()`
- `test_extraPaymentReducesLoanTerm()`
- `test_multipleExtraPaymentsCompound()`
- `test_extraPaymentOnScheduleDate()`
- `test_extraPaymentBetweenPaymentDates()`
- `test_recalculationMaintainsAccuracy()`
- `test_partialPaymentAdjustment()`
- `test_postedEntriesMarkedForReversal()`

**UAT Scripts Required:**
- `FA_Record_Extra_Payment_On_Schedule_Date.md`
- `FA_Record_Extra_Payment_Between_Dates.md`
- `FA_Verify_Schedule_Recalculated_After_Extra_Payment.md`
- `FA_Extra_Payment_Reduces_Loan_Term.md`
- `FA_Record_Multiple_Extra_Payments.md`
- `FA_Extra_Payment_Triggers_GL_Entry_Reversal_Alert.md`

**Example Scenario for Testing:**
```
Initial 12-month loan, $12,000 at 5% annual, monthly payment = $1000.65
Jan 1: $1000.65 payment, $875 principal, $125.65 interest, balance $11,125
Feb 1: $1000.65 payment, $880.40 principal, $120.25 interest, balance $10,244.60
Mar 1: $1000.65 payment, $885.93 principal, $114.72 interest, balance $9,358.67

User records extra payment Feb 15: $500

Recalculated from Feb 15:
Feb 15: Extra $500 applied to principal, new balance $10,244.60 - $500 = $9,744.60
Mar 1: New balance at this point, recalculate payment/interest/principal
  - If payment stays same: more principal, less interest
  - If extra reduces term: final payment may be different
  - Subsequent payments adjust accordingly
```

**Acceptance Criteria:**
- ✓ Extra payment creates audit trail
- ✓ Schedule recalculated immediately
- ✓ Subsequent payment calculations are accurate
- ✓ Final balance is $0.00 (±$0.02)
- ✓ Posted GL entries identified for reversal

---

### REQ-003: GL Posting with Journal Entry Tracking (CRITICAL FOR FA)
**Business Requirement:** Post payments to GL and track journal references for future updates  
**Functional Requirement:** UC3 - Post Payment to GL  
**Current Status:** ❌ **STUB ONLY** - `postPaymentToGL()` returns true without doing anything  
**Impact:** CRITICAL - FA integration requirement  

**Implementation Requirements:**
```
Must Implement in FAJournalService:
1. postPaymentToGL($loan_id, $payment_row, $gl_accounts)
   Returns: array with success flag, trans_no, trans_type, or error message
   
   Steps:
   - Validate all GL accounts exist in FA GL chart
   - Validate accounts are active (not closed)
   - Check user permission to post to accounts
   - Create journal entry with entries:
     * Debit: Liability account (loan principal reduction)
     * Debit: Expense account (interest expense)
     * Credit: Asset account (cash/bank payment received)
   - Capture returned trans_no and trans_type
   - Update ksf_amortization_staging:
     * posted_to_gl = 1
     * trans_no = captured value
     * trans_type = captured value
     * posted_at = NOW()
     * posted_by = current user
   - Return success with references
   
2. validateGLAccounts(array $accounts)
   - Check each account exists in 0_chart_master
   - Check account is not closed
   - Check account type matches intent (liability, asset, expense)
   - Return validation result
   
3. createJournalEntry($loan_id, $payment_row, $gl_accounts)
   - Use FA's write_journal_entries() function
   - Create 3-line entry (debit principal, debit interest, credit cash)
   - Set date = payment_date
   - Set reference = "LOAN-{loan_id}-{YYYYMMDD}"
   - Set memo = "Loan Payment: LOAN-123-20250115"
   - Return trans_no and trans_type
   
4. reversePostedEntry($staging_id)
   - Get trans_no and trans_type from staging row
   - Use FA's void_journal_entry() function
   - Mark staging row: voided=1, posted_to_gl=0
   - Log reversal in audit trail

New Methods in FADataProvider:
- getScheduleRow($staging_id) - retrieve single staging row
- updateScheduleRow($staging_id, array $updates) - update staging row
- getScheduleRowsByTransNo($trans_no) - find staging rows by journal entry
```

**Code Files to Create/Modify:**
- `modules/amortization/FAJournalService.php` - implement all methods (currently stubbed)
- `modules/amortization/FADataProvider.php` - add new methods
- `src/Ksfraser/Amortizations/DataProviderInterface.php` - may need extensions
- `src/Ksfraser/Amortizations/controller.php` - add posting routes and error handling

**Unit Tests Required (8+ test methods):**
- `test_postPaymentToGLCreatesJournalEntry()`
- `test_postPaymentCapturesTransNo()`
- `test_postPaymentCapturesTransType()`
- `test_postPaymentUpdatesStaging()`
- `test_postPaymentValidatesGLAccounts()`
- `test_postPaymentFailsOnInvalidAccount()`
- `test_reversePostedEntry()`
- `test_reverseUpdatesStaging()`
- `test_postPaymentSetsCorrectAmounts()`

**UAT Scripts Required:**
- `FA_Post_Single_Payment_To_GL.md`
- `FA_Post_Payment_Creates_Journal_Entry.md`
- `FA_Verify_GL_Entry_Accuracy.md`
- `FA_GL_Account_Validation_Prevents_Invalid_Post.md`
- `FA_Post_Multiple_Payments.md`
- `FA_Reverse_Posted_Entry.md`
- `FA_Verify_Trans_No_Stored_In_Staging.md`
- `FA_Post_After_Extra_Payment_Recalculation.md`

**Example Journal Entry:**
```
Loan Payment: LOAN-123-20250115
Date: 2025-01-15
Reference: LOAN-123-20250115

Account                        Type      Amount    Description
------
Loan Liability (2100)         Debit     $600.00   Principal reduction
Interest Expense (5210)       Debit     $125.65   Interest accrual
Cash in Bank (1200)           Credit    $725.65   Payment received

ksf_amortization_staging row updated:
- posted_to_gl = 1
- trans_no = 12345 (returned from FA)
- trans_type = 20 (journal type)
- posted_at = 2025-01-15 14:30:00
- posted_by = 2 (user ID)
```

**Acceptance Criteria:**
- ✓ Journal entry created in FA with correct amounts
- ✓ trans_no and trans_type captured in staging table
- ✓ GL accounts validated before posting
- ✓ Error handling for invalid accounts
- ✓ Posted entries can be reversed/voided
- ✓ Staging row marked as posted with timestamps/user

---

## High Priority Requirements (Phase 2)

### REQ-004: Batch & Scheduled Posting
**Status:** ❌ NOT IMPLEMENTED  
**Implementation:**
- Create `AmortizationPostingService` class for batch operations
- Implement `postPaymentsBatch($loan_ids, $upToDate = null)`
- Implement cron task scheduling
**Estimated Effort:** 20-30 hours

### REQ-009: User Permissions & Access Control
**Status:** ❌ NOT IMPLEMENTED  
**Implementation:**
- Add access checks to all controller actions
- Implement Loans Administrator vs. Loans Reader roles
- Enforce permission checks before GL posting
**Estimated Effort:** 8-12 hours

### REQ-010: GL Account Mapping
**Status:** ❌ PARTIAL - Schema needs updates  
**Implementation:**
- Create `ksf_gl_mapping` table for per-loan GL accounts
- Add UI to configure GL accounts per loan
- Update posting logic to use loan-specific accounts
**Estimated Effort:** 12-16 hours

---

## Medium Priority Requirements (Phase 3)

### REQ-006, REQ-008, REQ-011, REQ-012
- Skipped payment handling
- Reporting functionality
- Audit logging
- Input validation

---

## Testing Strategy

### Unit Test Categories

**1. Calculation Tests (15+ tests)**
- Payment calculation with various frequencies
- Schedule generation with various frequencies
- Interest calculation accuracy
- Final balance validation

**2. Extra Payment Tests (10+ tests)**
- Event recording
- Schedule recalculation
- Balance adjustments
- Term reduction

**3. GL Posting Tests (8+ tests)**
- Journal entry creation
- Trans_no/trans_type capture
- Account validation
- Posting/reversal operations

**4. Data Provider Tests (5+ tests)**
- Insert/update/delete operations
- Query validation
- Data integrity

**5. Integration Tests (5+ tests)**
- Full workflow: Create → Schedule → Extra Payment → Post to GL
- End-to-end accuracy verification

**Total Phase 1 Unit Tests:** ~45 test methods, 150+ assertions

### UAT Test Categories

**1. Create Loan UATs (3 scenarios)**
- Monthly payment, monthly interest
- Bi-weekly payment, monthly interest
- Weekly payment, daily interest

**2. Extra Payment UATs (3 scenarios)**
- Extra payment on schedule date
- Extra payment between dates
- Multiple extra payments

**3. GL Posting UATs (3 scenarios)**
- Single payment post
- Batch post
- Post with reversal

**4. Calculation Accuracy UATs (2 scenarios)**
- Verify against external calculator
- Verify end-to-end accuracy

**Total Phase 1 UATs:** ~11 detailed scripts

---

## Effort Estimation

### Phase 1 (Critical - 8-10 weeks)
| Task | Effort | Status |
|------|--------|--------|
| Fix frequency calculation | 16-20 hrs | Not started |
| Implement extra payment | 24-30 hrs | Not started |
| Implement GL posting | 20-24 hrs | Not started |
| Unit tests for above | 30-40 hrs | Not started |
| UAT script development | 20-24 hrs | Not started |
| **TOTAL PHASE 1** | **110-138 hrs** | **Not started** |

### Phase 2 (High Priority - 4-6 weeks)
| Task | Effort |
|------|--------|
| Batch posting | 20-24 hrs |
| User permissions | 8-12 hrs |
| GL mapping tables | 12-16 hrs |
| Unit tests | 20-24 hrs |
| UAT scripts | 12-16 hrs |
| **TOTAL PHASE 2** | **72-92 hrs** |

### Grand Total: ~200 hours to production readiness

---

## Go/No-Go Checklist

### Before Phase 1 Starts
- [ ] Team agrees on scope and effort estimate
- [ ] Requirements approved by Finance/Admin stakeholder
- [ ] Development environment set up (test DB, Composer dependencies)
- [ ] Code review process defined

### Before Phase 1 Testing
- [ ] All code written and compiles without errors
- [ ] All unit tests pass
- [ ] Code coverage >85% for critical modules
- [ ] Peer code review completed, no blockers

### Before Phase 2 Starts
- [ ] Phase 1 UAT completed and approved
- [ ] All Phase 1 defects fixed
- [ ] Phase 1 documentation complete

### Before Production Release
- [ ] All Phase 1 + 2 requirements complete
- [ ] Code coverage >80% overall
- [ ] Zero critical/high security issues
- [ ] All UAT scripts pass with stakeholder sign-off
- [ ] Deployment tested in staging environment
- [ ] Rollback plan documented
- [ ] Training materials prepared

---
