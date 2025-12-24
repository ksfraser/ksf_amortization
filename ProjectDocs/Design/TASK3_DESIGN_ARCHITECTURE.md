# TASK 3: GL Posting Implementation - Design & Architecture

**Status:** In Progress  
**Date:** 2025-12-08  
**Estimated Duration:** 20-24 hours  
**Priority:** Critical for FrontAccounting integration

---

## TASK 3 Scope

### Overview
Implement GL posting functionality for amortization payments in FrontAccounting, including:
- Journal entry generation for individual payments
- Batch posting for multiple payments
- GL account mapping and validation
- Transaction tracking (trans_no, trans_type)
- Reversal handling when schedules change

### Key Requirements (From Functional Specification)

#### UC3: Post Payment to GL
- Create journal entries with proper account structure:
  - **Debit:** Loan liability account (reduces loan balance)
  - **Debit:** Interest expense account (accrues interest)
  - **Credit:** Cash/bank account (payment received)
- Capture and store `trans_no` and `trans_type`
- Update ksf_amortization_staging with posting status
- Handle failures gracefully

#### UC4: Batch Post Payments
- Post multiple unposted payments
- Support filtering by date
- Show preview before posting
- Report success/failure counts

#### UC5: Schedule Recurring Posting (Future)
- Automated cron job support
- Configurable posting rules
- Logging and error notification

---

## Architecture Design

### Core Components

```
┌─────────────────────────────────────────────────────────┐
│              TASK 3 Components                          │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  FAJournalService                                │  │
│  │  - postPaymentToGL()                             │  │
│  │  - batchPostPayments()                           │  │
│  │  - validateGLAccounts()                          │  │
│  │  - createJournalEntry()                          │  │
│  │  - reverseJournalEntry()                         │  │
│  └──────────────────────┬───────────────────────────┘  │
│                         │                               │
│         ┌───────────────┼───────────────┐              │
│         ▼               ▼               ▼              │
│  ┌─────────────┐ ┌────────────┐ ┌──────────────┐      │
│  │ GL Account  │ │ Journal    │ │ Transaction  │      │
│  │ Mapper      │ │ Entry      │ │ Tracker      │      │
│  │             │ │ Builder    │ │              │      │
│  └─────────────┘ └────────────┘ └──────────────┘      │
│         │               │               │              │
│         └───────────────┼───────────────┘              │
│                         ▼                              │
│  ┌─────────────────────────────────────────────────┐  │
│  │  FrontAccounting GL Interface                   │  │
│  │  - FA GL tables (journal entries, GL accounts) │  │
│  │  - trans_no/trans_type capture                 │  │
│  │  - validation & error handling                 │  │
│  └─────────────────────────────────────────────────┘  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Class Hierarchy

#### FAJournalService (Main Service)
```php
class FAJournalService {
    - pdo: PDO
    - logger: Logger
    - glAccounts: array
    
    + __construct(PDO $pdo)
    + postPaymentToGL(int $loanId, array $paymentRow, array $glAccounts): array
    + batchPostPayments(int $loanId, string $upToDate = null): array
    + validateGLAccounts(array $glAccounts): bool
    - createJournalEntry(array $debit, array $credit): array
    - reverseJournalEntry(string $transNo, string $transType): bool
}
```

#### GLAccountMapper
```php
class GLAccountMapper {
    - pdo: PDO
    
    + mapLoanAccounts(int $loanId): array
    + validateAccounts(array $accounts): bool
    + getAccountDetails(string $accountCode): ?array
}
```

#### JournalEntryBuilder
```php
class JournalEntryBuilder {
    - entries: array
    
    + addDebitEntry(string $account, float $amount, string $memo): self
    + addCreditEntry(string $account, float $amount, string $memo): self
    + setReference(int $loanId, string $paymentDate): self
    + build(): array
}
```

---

## Implementation Phases

### Phase 1: Core FAJournalService (Hours 1-6)
**Deliverables:**
1. FAJournalService skeleton with PDO integration
2. postPaymentToGL() basic implementation
3. createJournalEntry() for FA GL tables
4. Transaction capture (trans_no, trans_type)
5. Database update of staging table
6. Error handling & validation

**Key Methods:**
- `postPaymentToGL($loanId, $paymentRow, $glAccounts)`
  - Validates GL accounts exist and are active
  - Creates journal entry in FA GL
  - Updates ksf_amortization_staging with posting info
  - Returns transaction details

**Database Schema Understanding:**
- FA GL tables: `gl_trans` (journal entries)
- GL accounts: `chart_master`, `chart_types`
- Need to understand:
  - trans_type codes (0=system, 10=GL, 20=AP, etc.)
  - trans_no generation
  - Debit/credit structure
  - Reference field for loan tracking

### Phase 2: GL Account Management (Hours 7-12)
**Deliverables:**
1. GLAccountMapper implementation
2. Account validation logic
3. Account balance checking
4. Vendor-specific GL account configuration
5. Default GL account mappings

**Key Methods:**
- `validateGLAccounts(array $accounts)`
- `getAccountBalance(string $accountCode)`
- `mapLoanAccounts(int $loanId)` - Get loan-specific GL mappings

**Configuration Model:**
```php
$glAccounts = [
    'liability_account' => '2100',  // Loan liability
    'interest_expense_account' => '6200',  // Interest expense
    'cash_account' => '1100',  // Cash/bank
    'loan_type' => 'LOAN-AUTO'  // Optional: loan type specific
];
```

### Phase 3: Batch Processing (Hours 13-18)
**Deliverables:**
1. batchPostPayments() implementation
2. Multiple payment posting with rollback on failure
3. Progress tracking and reporting
4. Partial success handling
5. Transaction management (all-or-nothing option)

**Key Methods:**
- `batchPostPayments($loanId, $upToDate = null)`
- `postMultiplePayments(array $paymentRows, array $glAccounts)`
- `getRollbackPlan()` - For reversal if needed

### Phase 4: Reversal & Reconciliation (Hours 19-21)
**Deliverables:**
1. reverseJournalEntry() implementation
2. Automatic reversal when schedule changes
3. Reconciliation checking
4. Audit trail integration

**Key Methods:**
- `reverseJournalEntry($transNo, $transType)`
- `markStagingRowsForReversal($loanId, $fromDate)`

### Phase 5: Testing & Documentation (Hours 22-24)
**Deliverables:**
1. Unit tests for core methods
2. Integration tests with FA GL
3. Error scenario testing
4. Performance testing (batch posting)
5. Complete documentation

---

## Database Schema Details

### FrontAccounting GL Tables

#### `0_gl_trans` (Journal Entries)
```sql
counter: INT PRIMARY KEY AUTO_INCREMENT
type: SMALLINT (10=GL entry, 20=AP, 30=AR, etc)
type_no: INT (document number)
tran_date: DATE
account: VARCHAR (GL account code)
memo_: VARCHAR (description)
amount: DECIMAL(16,4) (positive for debit, negative for credit)
person_id: VARCHAR (vendor/customer/employee if applicable)
person_type_id: SMALLINT (2=vendor, 3=customer, 4=employee)
ref_no: VARCHAR (reference field - store loan ID here)
created_at: TIMESTAMP
```

#### `0_chart_master` (GL Accounts)
```sql
account_code: VARCHAR PRIMARY KEY
account_name: VARCHAR
account_type: INT
parent: VARCHAR
inactive: TINYINT (0=active, 1=inactive)
```

### Our Staging Table Update
```sql
ksf_amortization_staging:
- posted_to_gl: BOOLEAN (0/1)
- trans_no: VARCHAR (captured trans_no from gl_trans)
- trans_type: SMALLINT (captured type from gl_trans)
- posted_at: DATETIME
- posted_by: VARCHAR (username)
- reversal_trans_no: VARCHAR (if reversed)
- reversal_date: DATETIME
```

---

## GL Entry Structure (Example)

For a $1,000 payment with $600 principal, $400 interest:

```
Journal Entry for Loan Payment:

Debit: 2100 (Loan Liability)       $600
Debit: 6200 (Interest Expense)      $400
Credit: 1100 (Cash/Bank)                    $1,000

Reference: LOAN-123-2025-01-01
Memo: Loan Payment - Principal $600, Interest $400
```

---

## Implementation Strategy

### Step 1: Understand FA GL API
- Examine existing FA GL posting code in modules
- Understand trans_no generation
- Understand trans_type codes
- Understand debit/credit convention

### Step 2: Create GLAccountMapper
- Build account validation
- Create configuration structure
- Add default mappings

### Step 3: Implement postPaymentToGL()
- Single payment posting
- Transaction capture
- Staging table update
- Error handling

### Step 4: Extend to batchPostPayments()
- Loop through multiple payments
- Aggregate results
- Transaction rollback on failure

### Step 5: Add Reversals
- Implement reversal logic
- Integrate with TASK 2 recalculation
- Update staging table

### Step 6: Comprehensive Testing
- Unit tests for each method
- Integration tests with FA
- Error scenario coverage

---

## SOLID Principles Applied

### Single Responsibility
- FAJournalService: Orchestrates GL posting
- GLAccountMapper: Manages GL account mappings
- JournalEntryBuilder: Constructs journal entries
- Each class has one reason to change

### Open/Closed
- Extensible for other platforms (WordPress GL posting)
- Configurable GL account mappings
- Plugin-friendly transaction types

### Liskov Substitution
- All posting services implement same interface
- Reversible posting concept
- Consistent error handling

### Interface Segregation
- GLAccountRepository interface (separate from full FADataProvider)
- PostingService interface
- Minimal method signatures

### Dependency Inversion
- Depends on PDO interface, not FA-specific classes
- Configuration injected, not hardcoded
- Logger interface for flexibility

---

## Error Handling Strategy

### Validation Errors
- GL account not found
- GL account inactive
- Insufficient balance (warning, not error)
- Invalid staging row data

### Posting Errors
- Database transaction failure
- GL constraints violated
- Duplicate posting attempt
- Reference field issues

### Recovery
- Rollback on batch failure
- Partial success reporting
- Reversal capability
- Audit trail for investigation

---

## Testing Plan

### Unit Tests (15-20 tests)
1. postPaymentToGL with valid data
2. postPaymentToGL with invalid GL account
3. postPaymentToGL with inactive account
4. createJournalEntry correctness
5. batchPostPayments success
6. batchPostPayments partial failure
7. validateGLAccounts success/failure
8. reverseJournalEntry success
9. Transaction capture accuracy
10. Staging table update
... more

### Integration Tests (5-8 tests)
1. Full FA GL posting with real database
2. Batch posting with rollback
3. Schedule change with reversal
4. Concurrent posting attempts
5. High-volume batch posting

### Performance Tests
- Batch post 100 payments timing
- Database query optimization
- Concurrent posting load testing

---

## Deliverables Checklist

- [ ] FAJournalService.php (400-500 lines)
- [ ] GLAccountMapper.php (150-200 lines)
- [ ] JournalEntryBuilder.php (100-150 lines)
- [ ] PostingInterface.php (interface definition)
- [ ] FAJournalServiceTest.php (200+ lines tests)
- [ ] GL Integration Tests (100+ lines)
- [ ] TASK3_IMPLEMENTATION.md (comprehensive documentation)
- [ ] Database schema updates
- [ ] Configuration examples
- [ ] Error handling documentation

---

## Next Steps

1. ✅ Design architecture (completed this doc)
2. → Examine FrontAccounting GL table structure
3. → Create GLAccountMapper
4. → Implement FAJournalService core methods
5. → Add batch posting support
6. → Implement reversal logic
7. → Write comprehensive tests
8. → Create final documentation

---

*Design Document Version: 1.0*  
*Created: 2025-12-08*  
*Status: Ready for Implementation*
