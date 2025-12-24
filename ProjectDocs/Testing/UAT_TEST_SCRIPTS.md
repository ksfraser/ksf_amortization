# UAT - User Acceptance Testing Documentation

**Document Version:** 1.0.0  
**Status:** Test Scripts Ready for Execution  
**Target Audience:** Finance/Admin Users, QA Team  
**Date:** December 8, 2025  
**Platform:** FrontAccounting (Primary), WordPress/SuiteCRM (Secondary)

---

## Table of Contents

1. [UAT Overview](#uat-overview)
2. [Pre-UAT Setup](#pre-uat-setup)
3. [Test Scenarios by Feature](#test-scenarios-by-feature)
4. [User Stories & Test Cases](#user-stories--test-cases)
5. [Pass/Fail Criteria](#passfail-criteria)
6. [UAT Sign-Off](#uat-sign-off)

---

## UAT Overview

### Purpose
User Acceptance Testing verifies that the amortization module meets all business requirements from user perspective. Tests real-world scenarios with actual data and business processes.

### Testing Approach
- **Black-box testing:** Focus on inputs and outputs, not implementation
- **End-to-end workflows:** Test complete processes from start to finish
- **Real data:** Use realistic loan amounts, rates, and frequencies
- **Multiple platforms:** Test on FA, WP, SuiteCRM where applicable
- **User perspective:** Test as finance staff would actually use the system

### Success Criteria
✓ All test scenarios pass without errors  
✓ Calculations match external reference calculators (within $0.02)  
✓ UI is intuitive and clear  
✓ Performance acceptable (<2 sec for calculations, <30 sec for batch)  
✓ Error messages helpful and actionable  
✓ Data persists correctly in database  

---

## Pre-UAT Setup

### Database Backup
Before starting UAT, backup production database:

```bash
# FrontAccounting
mysqldump -u root -p frontaccounting > backup_uat_$(date +%Y%m%d).sql

# Create UAT database
mysql -u root -p -e "CREATE DATABASE frontaccounting_uat;"
mysql -u root -p frontaccounting_uat < backup_uat_$(date +%Y%m%d).sql
```

### Test Data Preparation

Create standardized test loans:

| Loan ID | Amount | Rate | Term | Frequency | Created For |
|---------|--------|------|------|-----------|-------------|
| TEST-001 | $10,000 | 5.0% | 60 mo | Monthly | Basic calculation |
| TEST-002 | $50,000 | 7.5% | 180 mo | Monthly | Standard mortgage |
| TEST-003 | $25,000 | 4.5% | 260 pay | Bi-weekly | Bi-weekly validation |
| TEST-004 | $5,000 | 8.0% | 365 pay | Weekly | Weekly validation |
| TEST-005 | $100,000 | 6.0% | 180 mo | Monthly | Batch posting |
| TEST-006 | $15,000 | 5.5% | 120 mo | Monthly | Extra payment |

### Browser & Environment

- **Browser:** Chrome, Firefox (both latest)
- **Resolution:** 1920x1080 (desktop), 1024x768 (tablet)
- **JavaScript:** Enabled
- **Cookies:** Enabled
- **Developer Console:** Open to catch JS errors

---

## Test Scenarios by Feature

### Feature 1: Basic Amortization Calculation

#### UAT-001: Calculate Monthly Payment
**User Story:** "As a Finance Administrator, I want to create a loan and have the system automatically calculate the correct monthly payment amount"

**Test Steps:**
1. Log in to system
2. Navigate to Loans → New Loan
3. Fill in loan details:
   - Loan ID: TEST-001
   - Principal: $10,000
   - Annual Rate: 5.0%
   - Term: 60 months
   - Payment Frequency: Monthly
   - Interest Calc: Monthly
   - Start Date: 2025-01-01
4. Click "Calculate Payment" button
5. Review calculated payment amount

**Expected Result:**
- System calculates payment ≈ $188.71 per month
- Formula displayed: `Payment = P * [r(1+r)^n] / [(1+r)^n - 1]`
- Calculation shown in clear modal with breakdown
- "Save & Continue" button enabled

**Acceptance Criteria:**
- ✓ Payment amount is correct (within $0.02)
- ✓ Formula is displayed transparently
- ✓ Calculation takes <1 second
- ✓ No JavaScript errors in console

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-002: Generate Payment Schedule
**User Story:** "As a Finance Admin, I want to generate a complete amortization schedule showing each payment broken down by principal and interest"

**Test Steps:**
1. From loan detail page (after creating TEST-001)
2. Click "Generate Schedule" button
3. Review schedule table showing:
   - Payment # (1-60)
   - Payment Date
   - Beginning Balance
   - Payment Amount
   - Principal
   - Interest
   - Ending Balance
4. Scroll to verify all 60 rows present
5. Verify first payment details
6. Verify final payment (balance = $0)

**Expected Result:**
- Schedule displays 60 rows (one per payment)
- Payment dates increment by ~30 days
- First interest payment ≈ $41.67 (10000 * 0.05 / 12)
- First principal payment ≈ $147.04 (188.71 - 41.67)
- Final balance ≈ $0.00 (within $0.02)
- Table sortable by all columns
- Export to CSV/Excel available

**Acceptance Criteria:**
- ✓ All 60 rows present and correct
- ✓ Principal + Interest = Payment for each row
- ✓ Final balance is zero
- ✓ Schedule loads in <2 seconds
- ✓ Export functionality works

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-003: Verify Schedule Accuracy
**User Story:** "As an Auditor, I want to verify that the calculated schedule matches independent verification"

**Test Steps:**
1. Export schedule to Excel from TEST-001
2. Open in Excel and verify calculations:
   - Sum of all principal payments = $10,000
   - Sum of all interest payments = total_paid - principal
   - Row-by-row: Principal + Interest = Payment Amount
   - Row-by-row: Beginning Balance - Principal Payment = Ending Balance
3. Use external calculator (example: https://calculator.com/amortization)
4. Input same parameters and compare results

**Expected Result:**
- Sum of principal = $10,000 (exact)
- Each row formula correct (all columns reconcile)
- Matches external calculator result (within $0.02)

**Acceptance Criteria:**
- ✓ All formulas reconcile correctly
- ✓ Matches external reference calculator
- ✓ Audit-ready report available

**Pass/Fail:** _____ (Tester initials)

---

### Feature 2: Flexible Payment Frequencies

#### UAT-004: Bi-Weekly Payment Calculation
**User Story:** "As a Finance Manager, I want to support bi-weekly payment loans (every 2 weeks) for employees paid bi-weekly"

**Test Steps:**
1. Create TEST-003 loan:
   - Principal: $25,000
   - Rate: 4.5%
   - Term: 260 payments (bi-weekly, ~5 years)
   - Payment Frequency: Bi-Weekly
   - Interest Calc: Bi-Weekly
2. Calculate payment
3. Review schedule (first 5 rows)

**Expected Result:**
- Payment calculated correctly with bi-weekly compounding
- Payment dates increment by exactly 14 days
- Each payment ≈ $102.75
- Schedule generates 260 rows
- Final balance ≈ $0.00

**Acceptance Criteria:**
- ✓ Payment calculated for bi-weekly frequency
- ✓ Dates increment by 14 days
- ✓ Schedule accuracy verified
- ✓ Different from monthly calculation (not simply monthly ÷ 2)

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-005: Weekly Payment Calculation
**User Story:** "As a Finance Manager, I want to support weekly payment loans for flexible paycheck schedules"

**Test Steps:**
1. Create TEST-004 loan:
   - Principal: $5,000
   - Rate: 8.0%
   - Term: 365 payments (weekly, ~7 years)
   - Payment Frequency: Weekly
   - Interest Calc: Weekly
2. Calculate and verify

**Expected Result:**
- Weekly payment calculated (≈ $13.70 per week)
- Dates increment by 7 days
- Schedule has 365 rows
- Different calculation from monthly/bi-weekly

**Acceptance Criteria:**
- ✓ Weekly frequency calculation works
- ✓ Dates increment by 7 days exactly
- ✓ Performance acceptable for 365-payment schedule

**Pass/Fail:** _____ (Tester initials)

---

### Feature 3: Extra Payment Handling

#### UAT-006: Record Extra Payment
**User Story:** "As a Finance Admin, I want to record when a customer makes an extra payment toward principal"

**Test Steps:**
1. Select TEST-006 loan (already has schedule)
2. Navigate to "Loan Events" tab
3. Click "Record Event" button
4. Fill in:
   - Event Type: Extra Payment
   - Event Date: 2025-03-15
   - Amount: $500.00
   - Reason: Customer extra principal payment
5. Click "Save"
6. Verify event appears in Events list

**Expected Result:**
- Event recorded in database
- Event date and amount captured
- Reason field populated
- Can edit/delete event
- Shows in event list with timestamp

**Acceptance Criteria:**
- ✓ Event records successfully
- ✓ Data persists (refresh page, data still there)
- ✓ Can view all events for loan
- ✓ Error message if required fields missing

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-007: Extra Payment Recalculates Schedule
**User Story:** "As a Finance Admin, I want the system to automatically recalculate the schedule when an extra payment is recorded, adjusting future payments"

**Test Steps:**
1. After recording extra payment in UAT-006:
2. Verify system displays message: "Schedule will be recalculated"
3. Navigate to Schedule tab
4. Compare new schedule with original:
   - Payment #7 and after should have lower ending balances
   - Final payment date should be earlier
   - Final balance still $0.00
5. Export both schedules (before/after) and compare in Excel

**Expected Result:**
- Schedule automatically recalculated
- Payments after extra payment event have adjusted balances
- Loan paid off sooner (fewer payments)
- All balances reconcile correctly
- No manual action needed by user

**Acceptance Criteria:**
- ✓ Schedule recalculates automatically
- ✓ Balances adjust correctly
- ✓ Remaining term reduced
- ✓ Audit trail shows recalculation
- ✓ Performance acceptable

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-008: Multiple Extra Payments
**User Story:** "As a Finance Admin, I want to record multiple extra payments over the loan term and have each one adjust the schedule correctly"

**Test Steps:**
1. Create new test loan: TEST-007
2. Record three extra payments at different dates:
   - Payment 1: $300 on 2025-02-15
   - Payment 2: $400 on 2025-05-15
   - Payment 3: $250 on 2025-08-15
3. After each payment, verify schedule recalculates
4. Verify final schedule:
   - Has fewer payments (not 60, but maybe 55)
   - All balances correct
   - Final balance = $0

**Expected Result:**
- All three events recorded
- Schedule recalculates after each event
- Cumulative effect of extra payments captured ($950 total)
- Schedule cascades correctly with each adjustment

**Acceptance Criteria:**
- ✓ Multiple events handled correctly
- ✓ Each recalculation cascades properly
- ✓ Final schedule accurate with all adjustments
- ✓ Event history preserved

**Pass/Fail:** _____ (Tester initials)

---

### Feature 4: GL Posting (FrontAccounting)

#### UAT-009: Post Single Payment to GL
**User Story:** "As a Finance Admin, I want to post a scheduled payment to the General Ledger, creating journal entries that track cash received, principal payment, and interest income"

**Test Steps:**
1. Select TEST-002 loan with payment schedule
2. Navigate to "Post to GL" tab
3. Select payment #1 from schedule
4. Click "Post Payment" button
5. Review confirmation showing:
   - Journal Entry Details
   - Debit: Cash Received account (e.g., 1010)
   - Credit: Loan Payable account (e.g., 2050) for principal
   - Credit: Interest Income account (e.g., 4010) for interest
6. Click "Confirm Posting"
7. Verify success message

**Expected Result:**
- Journal entry created in FA
- Trans_No captured (e.g., "FA-2025-001")
- Trans_Type captured (e.g., "JD" for journal entry)
- Payment marked as "Posted" in schedule
- Amounts match schedule payment breakdown
- No duplicate posting possible

**Acceptance Criteria:**
- ✓ Journal entry created in FA
- ✓ Trans_No and Trans_Type captured
- ✓ Amounts correct (principal + interest)
- ✓ Accounts correct
- ✓ Payment marked as posted
- ✓ Error if duplicate posting attempted

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-010: Batch Post Multiple Payments
**User Story:** "As a Finance Manager, I want to post multiple months of scheduled payments in batch, reducing manual work"

**Test Steps:**
1. Select TEST-005 loan ($100k)
2. Navigate to "Post to GL" tab
3. Select date range: 2025-01-01 to 2025-12-31
4. Click "Batch Post Payments"
5. System shows:
   - Number of payments to post: 12
   - Total amount: $6,647.18 (12 × ~$553.93)
   - Journal entries to create: 24 (2 per payment)
6. Click "Confirm Batch Post"
7. Monitor progress bar
8. Verify completion

**Expected Result:**
- All 12 payments posted in single operation
- Each payment has journal entry in GL
- Batch posting completes in <30 seconds
- All payments marked as "Posted"
- Can download posting report

**Acceptance Criteria:**
- ✓ All payments posted successfully
- ✓ Performance acceptable (<30 sec for 12 payments)
- ✓ No posting errors
- ✓ All entries in GL
- ✓ Report available for download

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-011: Handle Extra Payment GL Posting
**User Story:** "As a Finance Admin, when an extra payment triggers schedule recalculation, I want the previously posted payments to remain posted while adjusted future payments are handled correctly"

**Test Steps:**
1. Create loan TEST-008, generate schedule, post first 3 payments
2. Record extra payment on payment date 4
3. Verify schedule recalculated
4. Post payment 4 and beyond
5. In FrontAccounting, verify all journal entries:
   - Original postings 1-3 unchanged
   - New postings for adjusted schedule
   - Correct accounts and amounts

**Expected Result:**
- Previous postings unchanged
- Adjusted future payments post correctly
- No duplicate entries
- GL reconciles correctly

**Acceptance Criteria:**
- ✓ Previous postings preserved
- ✓ Extra payment triggers only needed adjustments
- ✓ GL remains in balance
- ✓ Audit trail shows recalculation

**Pass/Fail:** _____ (Tester initials)

---

### Feature 5: User Interface & Data Entry

#### UAT-012: Form Validation
**User Story:** "As a user, I want helpful error messages if I enter invalid data"

**Test Steps:**
1. Try creating loan with missing fields (should fail)
2. Try negative principal (should fail)
3. Try zero/negative interest rate (should fail)
4. Try invalid date (should fail)
5. Try extra payment amount > balance (should warn)

**Expected Result:**
- Clear error messages for each validation error
- Field highlighted in red
- Submit button disabled until valid
- Messages in user-friendly language (not code)

**Acceptance Criteria:**
- ✓ All validation errors caught
- ✓ Messages are helpful
- ✓ No confusing error codes
- ✓ User guided to fix issues

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-013: Search & Filter Loans
**User Story:** "As a Finance Manager, I want to search/filter loans to quickly find specific loans"

**Test Steps:**
1. Navigate to Loans list
2. Test filtering by:
   - Loan ID (search "TEST-001")
   - Status (Active, Posted, Complete)
   - Date range
   - Interest rate range
3. Test sorting by:
   - Loan amount (ascending/descending)
   - Interest rate
   - Payment date

**Expected Result:**
- Search finds correct loans
- Filters work correctly (can apply multiple)
- Sort order correct
- Results update instantly
- Can clear filters

**Acceptance Criteria:**
- ✓ Search works
- ✓ Filters accurate
- ✓ Sorting correct
- ✓ Performance acceptable

**Pass/Fail:** _____ (Tester initials)

---

### Feature 6: Reporting & Export

#### UAT-014: Export Schedule to Excel
**User Story:** "As a Finance Manager, I want to export amortization schedules to Excel for analysis and sharing"

**Test Steps:**
1. Select loan with schedule
2. Click "Export" button
3. Choose "Excel (.xlsx)"
4. Save file to desktop
5. Open in Excel
6. Verify:
   - All rows and columns present
   - Formatting readable
   - Formulas not included (values only)
   - Can perform calculations in Excel

**Expected Result:**
- Excel file downloads
- All schedule data included
- Well-formatted (headers, borders, colors)
- Values only (no circular references)
- Can be opened in Excel, Google Sheets, etc.

**Acceptance Criteria:**
- ✓ Excel file created
- ✓ All data present
- ✓ Formatting good
- ✓ Compatible with multiple tools

**Pass/Fail:** _____ (Tester initials)

---

#### UAT-015: Generate Posting Report
**User Story:** "As an Auditor, I want a detailed report showing all payments posted to GL with reconciliation to loan schedule"

**Test Steps:**
1. After posting multiple payments (UAT-010)
2. Navigate to Reports section
3. Select "GL Posting Report"
4. Choose date range: 2025-01-01 to 2025-12-31
5. Generate report
6. Review report contents:
   - List of all posted payments
   - Trans_No and Trans_Type
   - GL accounts used
   - Amounts (principal + interest)
   - Reconciliation to GL balance

**Expected Result:**
- Report generated (PDF or Excel)
- All postings listed
- Reconciliation to GL
- Can be printed
- Can be archived

**Acceptance Criteria:**
- ✓ Report complete and accurate
- ✓ Reconciliation correct
- ✓ Suitable for audit
- ✓ Can print and export

**Pass/Fail:** _____ (Tester initials)

---

## User Stories & Test Cases Summary

| User Story ID | Feature | Test Cases | Priority |
|:---:|:---:|:---:|:---:|
| US-001 | Basic Calculation | UAT-001, 002, 003 | Critical |
| US-002 | Flexible Frequencies | UAT-004, 005 | Critical |
| US-003 | Extra Payments | UAT-006, 007, 008 | Critical |
| US-004 | GL Posting | UAT-009, 010, 011 | High |
| US-005 | UI & Validation | UAT-012, 013 | High |
| US-006 | Reporting | UAT-014, 015 | Medium |

---

## Pass/Fail Criteria

### Critical Path (Must Pass)
- ✓ UAT-001: Monthly payment calculation
- ✓ UAT-002: Schedule generation
- ✓ UAT-003: Accuracy verification
- ✓ UAT-004: Bi-weekly calculation
- ✓ UAT-006: Record extra payment
- ✓ UAT-007: Recalculate schedule

**Release Blocked If:** Any critical test fails

### High Priority (Should Pass)
- ✓ UAT-005: Weekly calculation
- ✓ UAT-008: Multiple extra payments
- ✓ UAT-009: GL posting
- ✓ UAT-010: Batch posting
- ✓ UAT-012: Validation
- ✓ UAT-013: Search/filter

**Release Allowed If:** ≤1 issue (with documented workaround)

### Medium Priority (Nice to Have)
- ✓ UAT-014: Export to Excel
- ✓ UAT-015: Reporting

**Release Allowed With:** These failing (addressed in Phase 2)

---

## UAT Sign-Off

### Tester Information
**Tester Name:** _________________________  
**Title:** _________________________  
**Date:** _________________________  
**Environment:** [ ] Dev [ ] Staging [ ] Production

### Results Summary

| Category | Count | Status |
|:---:|:---:|:---:|
| Total Tests | 15 | PASS / FAIL |
| Critical | 6 | PASS / FAIL |
| High | 7 | PASS / FAIL |
| Medium | 2 | PASS / FAIL |
| **Overall** | **15** | **PASS / FAIL** |

### Issues Found

| Issue # | Test | Severity | Description | Status |
|:---:|:---:|:---:|:---:|:---:|
| 1 | UAT-## | [ ] Critical [ ] High [ ] Med | | [ ] Resolved [ ] Deferred |
| 2 | UAT-## | [ ] Critical [ ] High [ ] Med | | [ ] Resolved [ ] Deferred |

### Sign-Off

**Test Execution Complete:** ☐ Yes  
**All Critical Tests Passed:** ☐ Yes  
**Ready for Production:** ☐ Yes  

**Tester Signature:** _________________________ Date: _______  
**QA Manager Signature:** _________________________ Date: _______  
**Project Manager Signature:** _________________________ Date: _______  

### Notes

_Any additional comments, observations, or recommendations:_

_______________________________________________________________________________
_______________________________________________________________________________
_______________________________________________________________________________

