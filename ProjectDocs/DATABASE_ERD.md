# Database Entity Relationship Diagram (ERD)

## Core Tables

### ksf_loans_summary
Primary loan information table storing all loan details.

```
ksf_loans_summary
├── id (INT, PRIMARY KEY, AUTO_INCREMENT)
├── borrower_id (INT, NOT NULL) - Links to Customer/Supplier/Employee
├── borrower_type (VARCHAR(32)) - 'Customer', 'Supplier', 'Employee'
├── amount_financed (DECIMAL(15,2)) - Original principal amount
├── interest_rate (DECIMAL(5,2)) - Annual percentage rate
├── loan_term_years (INT) - Term in years
├── payments_per_year (INT) - Payment frequency (12=monthly, 26=bi-weekly, etc.)
├── first_payment_date (DATE) - Date of first payment
├── last_payment_date (DATE) - Calculated/estimated last payment date
├── regular_payment (DECIMAL(15,2)) - Standard payment amount
├── override_payment (TINYINT(1)) - Flag for custom payment amount
├── loan_type (VARCHAR(32)) - 'Auto', 'Mortgage', 'Other' (from selectors)
├── interest_calc_frequency (VARCHAR(32)) - 'daily', 'weekly', 'monthly' (from selectors)
├── status (VARCHAR(16)) - 'active', 'closed', 'defaulted'
├── created_by (INT) - User ID who created the loan
├── created_at (TIMESTAMP) - Creation timestamp
├── updated_at (TIMESTAMP) - Last update timestamp
└── description (VARCHAR(255)) - Loan description/notes

INDEXES:
- PRIMARY KEY (id)
- INDEX idx_borrower (borrower_id, borrower_type)
- INDEX idx_status (status)
- INDEX idx_dates (first_payment_date, last_payment_date)
```

**Relationships:**
- `borrower_id` → Foreign key to FA tables (debtors_master, suppliers, employees) based on `borrower_type`
- `created_by` → Foreign key to FA users table
- `loan_type` → Lookup value in ksf_selectors (selector_name='loan_type')
- `interest_calc_frequency` → Lookup value in ksf_selectors (selector_name='interest_calc_frequency')

---

### ksf_amortization_staging
Staging table for calculated payment schedules before posting to GL.

```
ksf_amortization_staging
├── id (INT, PRIMARY KEY, AUTO_INCREMENT)
├── loan_id (INT, NOT NULL, FOREIGN KEY → ksf_loans_summary.id)
├── payment_date (DATE, NOT NULL) - Scheduled payment date
├── payment_amount (DECIMAL(15,2)) - Total payment amount
├── principal_portion (DECIMAL(15,2)) - Principal component
├── interest_portion (DECIMAL(15,2)) - Interest component
├── remaining_balance (DECIMAL(15,2)) - Balance after payment
├── posted (TINYINT(1), DEFAULT 0) - Flag: has been posted to GL
├── posted_at (TIMESTAMP, NULL) - When posted to GL
├── posted_by (INT, NULL) - User ID who posted
├── gl_trans_id (INT, NULL) - FA GL transaction reference
└── notes (TEXT, NULL) - Payment notes/adjustments

INDEXES:
- PRIMARY KEY (id)
- FOREIGN KEY fk_staging_loan (loan_id) REFERENCES ksf_loans_summary(id) ON DELETE CASCADE
- INDEX idx_posted (posted)
- INDEX idx_payment_date (payment_date)
- INDEX idx_loan_date (loan_id, payment_date)
```

**Relationships:**
- `loan_id` → ksf_loans_summary.id (CASCADE DELETE)
- `posted_by` → FA users table
- `gl_trans_id` → FA gl_trans table (if posted)

---

### ksf_selectors
Lookup/selector values for dropdowns and configuration options.

```
ksf_selectors
├── id (INT, PRIMARY KEY, AUTO_INCREMENT)
├── selector_name (VARCHAR(64), NOT NULL) - Category name
├── option_name (VARCHAR(128), NOT NULL) - Display name
├── option_value (VARCHAR(128), NOT NULL) - Internal value
└── created_at (TIMESTAMP) - When option was added

INDEXES:
- PRIMARY KEY (id)
- UNIQUE KEY unique_selector_option (selector_name, option_value)
- INDEX idx_selector_name (selector_name)

COMMON VALUES:
- selector_name='loan_type': Auto, Mortgage, Personal, Business, Other
- selector_name='interest_calc_frequency': daily, weekly, monthly, annual
- selector_name='payment_frequency': monthly, bi-weekly, weekly, quarterly
- selector_name='borrower_type': Customer, Supplier, Employee
```

**Relationships:**
- Used as lookup table by:
  - ksf_loans_summary.loan_type
  - ksf_loans_summary.interest_calc_frequency
  - Controller/View selectors

---

### ksf_gl_mappings (FrontAccounting Only)
GL account mappings for loan transactions.

```
ksf_gl_mappings
├── id (INT, PRIMARY KEY, AUTO_INCREMENT)
├── mapping_type (VARCHAR(32), NOT NULL) - 'liability', 'asset', 'expense', 'asset_value'
├── gl_account_code (VARCHAR(16), NOT NULL) - FA account code
├── description (VARCHAR(255)) - Mapping description
└── created_at (TIMESTAMP)

INDEXES:
- PRIMARY KEY (id)
- UNIQUE KEY unique_mapping_type (mapping_type)
```

**Relationships:**
- `gl_account_code` → FA chart_master.account_code
- Used by FAJournalService for GL posting

---

## Entity Relationship Visualization

```
┌─────────────────────────┐
│   FA Users Table        │
│   (users)               │
└──────────┬──────────────┘
           │ created_by, posted_by
           │
           ↓
┌──────────────────────────────┐         ┌───────────────────────┐
│   ksf_loans_summary          │←────────│  ksf_selectors        │
│   - id (PK)                  │ lookup  │  - id (PK)            │
│   - borrower_id              │         │  - selector_name      │
│   - borrower_type            │         │  - option_name        │
│   - amount_financed          │         │  - option_value       │
│   - interest_rate            │         └───────────────────────┘
│   - loan_type ──────────────→│ (loan_type, interest_calc_frequency)
│   - interest_calc_frequency ─│
│   - status                   │
│   - created_by (FK) ─────────┘
└──────────┬───────────────────┘
           │ loan_id (FK)
           │ CASCADE DELETE
           ↓
┌──────────────────────────────┐         ┌───────────────────────┐
│   ksf_amortization_staging   │         │  FA GL Tables         │
│   - id (PK)                  │         │  (chart_master,       │
│   - loan_id (FK) ────────────┘         │   gl_trans)           │
│   - payment_date             │         └──────────┬────────────┘
│   - payment_amount           │                    │
│   - principal_portion        │                    │ gl_account_code
│   - interest_portion         │                    │
│   - posted                   │         ┌──────────↓────────────┐
│   - gl_trans_id ─────────────┼────────→│  ksf_gl_mappings      │
│   - posted_by (FK) ──────────┘         │  - id (PK)            │
└──────────────────────────────┘         │  - mapping_type       │
                                          │  - gl_account_code    │
                                          └───────────────────────┘

┌──────────────────────────────┐
│   FA Borrower Tables         │
│   - debtors_master           │
│   - suppliers                │
│   - employees                │
└──────────┬───────────────────┘
           │ borrower_id
           │ (based on borrower_type)
           │
           ↓
     (referenced by ksf_loans_summary)
```

## Data Flow

### 1. Loan Creation Flow
```
User → Controller → View (user_loan_setup.php)
  ↓
Form Submit → Controller
  ↓
Validation → AmortizationModel
  ↓
FADataProvider.createLoan()
  ↓
INSERT into ksf_loans_summary
  ↓
Generate Schedule → AmortizationCalculator
  ↓
INSERT batch into ksf_amortization_staging
```

### 2. Payment Posting Flow (FA Only)
```
User → Reports View
  ↓
Select Payment(s) → Controller
  ↓
FAJournalService.postPayment()
  ↓
Read ksf_gl_mappings
  ↓
Create GL Journal Entries
  ↓
INSERT into FA gl_trans
  ↓
UPDATE ksf_amortization_staging SET posted=1, gl_trans_id=X
```

### 3. Selector Management Flow
```
Admin → admin_selectors.php View
  ↓
CRUD Operations → SelectorRepository
  ↓
ksf_selectors (INSERT/UPDATE/DELETE)
  ↓
Used by loan forms (loan_type, interest_calc_frequency selectors)
```

## Database Constraints & Business Rules

### Referential Integrity
1. `ksf_amortization_staging.loan_id` → CASCADE DELETE when loan deleted
2. `borrower_id` references validated against borrower_type
3. Posted staging records cannot be deleted (posted=1)

### Data Validation
1. `interest_rate` must be >= 0 and <= 100
2. `amount_financed` must be > 0
3. `payments_per_year` must be > 0
4. `first_payment_date` must be > loan creation date
5. `payment_amount` = principal_portion + interest_portion

### Status Transitions
```
NULL → 'active' (on creation)
'active' → 'closed' (when fully paid)
'active' → 'defaulted' (manual status change)
'closed' ↔ 'active' (reopen if needed)
```

## Indexes & Performance Optimization

### Query Patterns & Indexes

**Most Common Queries:**
1. Get active loans by borrower:
   - Index: `idx_borrower` (borrower_id, borrower_type)
   
2. Get unpaid staging records for loan:
   - Index: `idx_posted` (posted) + `idx_loan_date` (loan_id, payment_date)
   
3. Get loans by status:
   - Index: `idx_status` (status)

4. Selector lookups:
   - Index: `idx_selector_name` (selector_name)

### Denormalization Opportunities
- Consider caching `total_interest_paid`, `total_principal_paid` in ksf_loans_summary
- Consider materialized view for dashboard metrics

---

**Last Updated:** 2025-12-25
**Schema Version:** 1.2 (Phase 17 - FA Controller Integration)
