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

---
