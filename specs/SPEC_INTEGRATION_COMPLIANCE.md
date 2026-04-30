# SPEC-INTEGRATION: Integration & Compliance Workflow Specification

**Version**: 1.0 | **Date**: April 28, 2026 | **Status**: Ready for Implementation

---

## 1. OVERVIEW

The Integration & Compliance system ensures seamless data exchange with external systems, maintains regulatory compliance, and provides audit trails for all critical operations. Covers CRM synchronization, bank integrations, regulatory reporting, and compliance monitoring.

### Key Features
- CRM synchronization (FrontAccounting, SugarCRM)
- Bank ACH/Wire integration
- Regulatory reporting (CARES Act, ECOA, Fair Lending)
- Audit trail logging
- Document management & retention
- Consent management (electronic signatures)
- Privacy & PII security
- Webhook notifications
- API rate limiting & throttling
- Error handling & retry logic

### Success Metrics
- Data sync accuracy: 99.9%
- Compliance violations: 0 per quarter
- Failed payment processing: < 0.1%
- Audit log completeness: 100%
- Document retention: 100% per regulations

---

## 2. SYSTEMS INTEGRATION

### 2.1 CRM Integration (FrontAccounting)

#### Loan Data Sync

```
DIRECTION: KSF Amortization → FrontAccounting
FREQUENCY: Real-time (on event), batch nightly

Events triggering sync:
├─ Loan originated
├─ Payment received
├─ Delinquency detected
├─ Collection task assigned
└─ Account closed/charged-off

Data mapping:
┌────────────────────────────────────────────────┐
│ KSF System                │ FA System          │
├────────────────────────────────────────────────┤
│ borrower_no              → customer_no        │
│ loan_id                  → invoice_id (ref)   │
│ loan_number              → reference          │
│ disbursement_date        → date_              │
│ original_amount          → amount             │
│ current_balance          → balance_due        │
│ interest_rate            → dim_account_no     │
│ payment_status           → notes              │
│ loan_stage               → status             │
└────────────────────────────────────────────────┘

Payment sync:
├─ Payment date
├─ Payment amount
├─ Payment method (bank, check, wire)
├─ Collection status (on_time, late, arranged)
└─ Applied to principal/interest/fees

Collection task sync:
├─ Task ID
├─ Assigned collector
├─ Task status (open, in_progress, resolved)
├─ Contact attempts
└─ Latest activity type
```

#### Contact & Communication Sync

```
KSF → FA Communication Log:
─────────────────────────────
├─ Call Records
│  ├─ Collector ID
│  ├─ Call date/time
│  ├─ Call duration
│  ├─ Call outcome
│  └─ Notes
│
├─ Email Records
│  ├─ Email sent
│  ├─ Recipient
│  ├─ Subject
│  └─ Bounce/open tracking
│
├─ SMS Records
│  ├─ SMS sent
│  ├─ Message
│  └─ Delivery confirmation
│
└─ Letters
   ├─ Letter type
   ├─ Date sent
   ├─ Delivery method
   └─ Signature/confirmation
```

#### Account Status Sync

```
Bidirectional sync:

FA → KSF:
├─ Customer status updates
├─ Account holds/flags
├─ Credit limit changes
└─ Customer notes

KSF → FA:
├─ Delinquency status
├─ Collection tags
├─ Risk scores
├─ Account restrictions
└─ Payment arrangement status
```

### 2.2 Bank Integration (ACH/Wire Processing)

#### Payment Processor Integration

```
Architecture:
┌─────────────────────────────────────────┐
│ KSF Amortization System                 │
│                                         │
│  Payment Queue                          │
│  └─ Due payments (daily)                │
│  └─ Arrangement payments                │
│  └─ Manual payments                     │
│                                         │
│         ↓                               │
│                                         │
│  Payment Processor Service              │
│  ├─ Batch creation                      │
│  ├─ Validation                          │
│  └─ Submission                          │
│                                         │
│         ↓                               │
└─────────────────────────────────────────┘
           ↓
┌─────────────────────────────────────────┐
│ Payment Gateway                         │
│ (Stripe, Square, PayPal, Bank Direct)  │
│                                         │
│  ├─ Process payment                     │
│  ├─ Handle failures/retries             │
│  └─ Generate confirmation               │
└─────────────────────────────────────────┘
           ↓
      Bank Network
      (ACH/Wire/Card)
           ↓
   Return Notification
   (success/failure)
           ↓
┌─────────────────────────────────────────┐
│ KSF Payment Status Update               │
│ ├─ Record transaction                   │
│ ├─ Update loan balance                  │
│ ├─ Post interest/fees                   │
│ └─ Generate receipt                     │
└─────────────────────────────────────────┘
```

#### Payment Processing Workflow

```
1. DAILY BATCH CREATION (6:00 AM)
   ├─ Identify payments due
   ├─ Identify arrangement payments
   ├─ Identify existing holds
   └─ Create submission batch

2. VALIDATION (6:30 AM)
   ├─ Verify account balances
   ├─ Check duplicate submission
   ├─ Verify amounts match contracts
   └─ Apply NACHA formatting

3. SUBMISSION (7:00 AM)
   ├─ Transmit to processor
   ├─ Log submission details
   ├─ Queue for confirmation
   └─ Set timeout for retry

4. CONFIRMATION (7:30 AM - 5:00 PM)
   ├─ Monitor for acceptance
   ├─ Handle rejection/failure
   ├─ Retry failed payments
   └─ Update status

5. SETTLEMENT (Next business day)
   ├─ Confirm funds received
   ├─ Post to general ledger
   ├─ Reconcile bank
   └─ Generate reports

6. CUSTOMER NOTIFICATION
   ├─ Email confirmation
   ├─ SMS confirmation
   ├─ Portal access to receipt
   └─ Deduction confirmation
```

#### ACH Return Handling

```
Return Types:
├─ R01: Insufficient funds
├─ R02: Account closed
├─ R03: No authorization
├─ R04: Invalid account
├─ R05: Duplicate entry
│
Process:
├─ Automatic retry (1x for R01)
├─ Flag account for follow-up
├─ Notify collector
├─ Reverse transaction if not confirmed
├─ Update payment status
└─ Manual collector contact required
```

### 2.3 CRM Synchronization Service

```php
class CRMSyncService {
    public function __construct(
        private CRMClient $crmClient,
        private EntityManager $em,
        private EventDispatcher $events,
        private LoggerInterface $logger
    ) {}
    
    // Sync loan to CRM
    public function syncLoan(Loan $loan): void {
        $data = [
            'customer_no' => $loan->getBorrower()->getCRMId(),
            'loan_no' => $loan->getLoanNumber(),
            'amount' => $loan->getOriginalAmount(),
            'rate' => $loan->getInterestRate(),
            'term' => $loan->getTermMonths(),
            'status' => $loan->getStatus(),
            'balance' => $loan->getCurrentBalance(),
            'due_date' => $loan->getNextDueDate(),
        ];
        
        $this->crmClient->updateCustomerInvoice(
            $loan->getBorrower()->getCRMId(),
            $data
        );
        
        $this->logger->info('Loan synced to CRM', ['loan_id' => $loan->getId()]);
    }
    
    // Sync payment to CRM
    public function syncPayment(Payment $payment): void {
        $this->crmClient->recordPayment([
            'invoice_id' => $payment->getLoan()->getCRMId(),
            'amount' => $payment->getAmount(),
            'date' => $payment->getPaymentDate(),
            'method' => $payment->getPaymentMethod(),
            'reference' => $payment->getTransactionId()
        ]);
    }
    
    // Sync collection activity
    public function syncCollectionActivity(CollectionActivity $activity): void {
        $this->crmClient->logCommunication([
            'customer_no' => $activity->getTask()->getLoan()->getBorrower()->getCRMId(),
            'type' => 'collection_' . $activity->getActivityType(),
            'date' => $activity->getCreatedAt(),
            'notes' => $activity->getNotes(),
            'collector_id' => $activity->getRecordedBy(),
            'external_ref' => $activity->getId()
        ]);
    }
    
    // Batch sync (nightly)
    public function batchSync(): void {
        $this->syncModifiedLoans();
        $this->syncModifiedPayments();
        $this->syncModifiedCollectionActivities();
    }
}
```

---

## 3. REGULATORY COMPLIANCE

### 3.1 Fair Lending Compliance

#### ECOA (Equal Credit Opportunity Act) Monitoring

```
ECOA Protected Classes:
├─ Race/Color
├─ Religion
├─ National Origin
├─ Sex
├─ Marital Status
├─ Age
└─ Disability Status

Protected Action:
├─ Loan denial
├─ Adverse action (adverse terms)
├─ Different treatment
└─ Credit line reduction

Monitoring Points:
├─ Application stage (no proxy discrimination)
├─ Underwriting stage (consistent standards)
├─ Pricing stage (no disparate impact)
├─ Collections stage (consistent enforcement)
└─ Account management stage

Quarterly Review Process:
1. Extract application & underwriting data
2. Run regression analysis on approval rates
3. Analyze by protected class
4. Identify disparate impact (>80% rule)
5. Review files if impact detected
6. Document findings & remediation
7. Report to management

Success Criteria:
├─ Approval rate differential < 20% by class
├─ Interest rate differential < 100 bps by class
├─ Collections enforcement uniformity
└─ NO pattern-based discrimination
```

#### Adverse Action Notices

```
When Required:
├─ Application denied
├─ Application withdrawn before funding
├─ Different terms offered
├─ Existing relationship negatively affected
└─ Credit limit reduction

What Must Be Included:
├─ Statement that credit decision was made
├─ Specific reasons for decision
├─ Disclosure of right to explanation
├─ Notification of consumer report usage (if used)
├─ Consumer reporting agency contact info
├─ FCRA § 615 notice

Timeline:
├─ Sent within 30 days of decision
├─ Includes specific reason(s)
├─ NO vague language ("credit decision")

System Implementation:
├─ Auto-generate on decision
├─ Mail via certified mail
├─ Track delivery/return
├─ Archive in audit file (7 years)
└─ Log in compliance database
```

### 3.2 FDCPA Compliance (Collections)

```
Covered by: Fair Debt Collection Practices Act (1978)

Rules:
├─ NO false representations
├─ NO harassment/abuse
├─ NO unfair/unconscionable practices
└─ NO violation of TCPA (telemarketing)

Contact Restrictions:
├─ 8 AM - 9 PM debtor's local time (exceptions with consent)
├─ NO contact before 8 AM OR after 9 PM
├─ NO contact at workplace if employer prohibits
├─ NO contact if attorney retained
└─ NO contact if written cease & desist received

Frequency Limits:
├─ NO pattern of calling with intent to harass
├─ Max ~7 attempts per week reasonable
├─ NO multiple calls same day (exception: legitimate callbacks)
└─ NO repetitive contact after promise

Prohibited Practices:
├─ NO threatening language
├─ NO public/embarrassing collection tactics
├─ NO false/misleading statements
├─ NO posting of names (except for court)
├─ NO obscene/profane language
├─ NO false identity
└─ NO third-party disclosure (except to verify debt)

System Controls:
├─ Contact time validation (time zone aware)
├─ Contact frequency tracking
├─ Language audit (flag prohibited terms)
├─ Cease & desist registry
├─ Attorney notification tracking
└─ Comprehensive activity logging
```

### 3.3 TCPA Compliance (SMS/Phone)

```
Telephone Consumer Protection Act (TCPA)

SMS Requirements:
├─ Prior express written consent required
├─ Consent captured at application or explicitly
├─ Consent records maintained (7 years)
├─ Opt-out capability on every message
├─ NO auto-dialers without consent
├─ NO prerecorded calls without consent

Message Format:
├─ [Company name] on behalf of [Creditor Name]
├─ [Brief loan description if applicable]
├─ [Main message]
├─ "Reply STOP to opt-out"
└─ "For help reply HELP"

System Implementation:
├─ Consent verification on SMS send
├─ Carrier-compliant opt-out processing
├─ Timestamp logging of all SMS
├─ STOP response handling (auto opt-out)
└─ Audit trail for each SMS sent
```

### 3.4 CARES Act Compliance (If applicable)

```
Coronavirus Aid, Relief, and Economic Security Act

Relevant Provisions:
├─ Loan forbearance options
├─ Payment deferrals
├─ Interest waiver provisions
├─ Credit reporting obligations
└─ Compliance documentation

Implementation:
├─ Forbearance workflow
├─ Deferral tracking
├─ Credit bureau reporting coordination
└─ Borrower notification
```

### 3.5 Compliance Dashboard

```
Administrator View:
═════════════════════════════════════════

FAIR LENDING COMPLIANCE
─────────────────────────────────────────
Quarter 2 2026 (Jan-Mar) Status: ✓ PASS
├─ Approval Rate White/Hispanic: 89% vs 87% (Δ 2%)
├─ Approval Rate Asian: 91% (vs 89% baseline) Δ 2%
├─ Interest Rate Differential: 12 bps (vs 100 bps limit) ✓
├─ Collections Enforcement Uniformity: 84% (vs 75% avg) ✓
└─ NO disparate impact detected ✓

FDCPA COMPLIANCE (Collections)
─────────────────────────────────────────
YTD Status: ✓ COMPLIANT
├─ Total Collection Calls: 1,240
├─ Compliance Violations: 0
├─ STOP Requests: 3 (all honored ✓)
├─ Attorney Notifications: 2 (all honored ✓)
├─ Contact Time Violations: 0 ✓
├─ Frequency Violations: 0 ✓
└─ Language Violations: 0 ✓

TCPA COMPLIANCE (SMS)
─────────────────────────────────────────
YTD Status: ✓ COMPLIANT
├─ SMS Sent (with consent): 450
├─ SMS Sent (without consent): 0 ✓
├─ Opt-out Requests: 12 (all honored ✓)
├─ HELP Requests: 4 (all responded ✓)
└─ Compliance Violations: 0 ✓

[View Details] [Generate Report] [Audit Trail]
```

---

## 4. AUDIT & COMPLIANCE LOGGING

### 4.1 Audit Trail System

```php
class AuditLogger {
    public function logAction(
        string $entity,
        int $entityId,
        string $action,
        array $oldValues,
        array $newValues,
        string $userId,
        string $reason = null
    ): void {
        // Record before/after on sensitive fields:
        // - Loan amount
        // - Interest rate
        // - Payment terms
        // - Status/stage changes
        // - Delinquency actions
        // - Collections activities
        // - Account restrictions
        
        $log = [
            'timestamp' => now(),
            'entity' => $entity,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $newValues,
            'user_id' => $userId,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent')
        ];
        
        // Store in immutable audit table
        $this->auditRepository->save($log);
    }
}
```

### 4.2 Audit Table Schema

```sql
CREATE TABLE 0_ksf_audit_log (
    audit_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- Entity being audited
    entity_type VARCHAR(50) NOT NULL,  -- Loan, Payment, CollectionTask, etc.
    entity_id INT NOT NULL,
    
    -- Action metadata
    action VARCHAR(50) NOT NULL,  -- created, updated, deleted
    action_timestamp DATETIME NOT NULL,
    
    -- Old and new values (JSON for flexibility)
    old_values JSON,
    new_values JSON,
    changed_fields JSON,  -- Array of field names changed
    
    -- User info
    user_id INT,
    user_name VARCHAR(100),
    user_role VARCHAR(50),
    
    -- Context
    ip_address VARCHAR(45),
    user_agent TEXT,
    reason TEXT,
    
    -- Immutability
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_timestamp (action_timestamp),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action)
);
```

### 4.3 Compliance Event Logging

```
Critical Events Logged:
├─ Loan underwriting decision
├─ Loan approval/denial with reason
├─ Interest rate setting
├─ Payment received
├─ Payment missed/late
├─ Delinquency status change
├─ Collection task assignment
├─ Collection activity
├─ Payment arrangement created/modified
├─ Account restrictions/holds
├─ Write-off/charge-off decision
├─ FDCPA violation detected
├─ TCPA violation (opt-out)
└─ Fair lending analysis review

Archival:
├─ Financial records: 7 years
├─ Credit decisions: 7 years
├─ Collections records: 7 years (post-charge-off)
├─ Consent forms: 7 years
└─ Compliance reviews: Permanent
```

---

## 5. DOCUMENT MANAGEMENT & RETENTION

### 5.1 Document Types

```
Critical Documents:
├─ Credit Application
├─ Credit Report Authorization
├─ Loan Agreement
├─ Truth in Lending Disclosure (TILA)
├─ Anti-predatory Lending Notice
├─ Electronic Signature Consent
├─ Payment Authorization (ACH/Card)
├─ Account Transfer Forms (if applicable)
└─ Collection Communications
   ├─ Collection letters
   ├─ Call recordings
   ├─ SMS transcripts
   └─ Payment arrangement agreements

### 5.2 Retention Policy

```
Document Type              | Retention Period | Archive Location
─────────────────────────────────────────────────────────────
Loan Application           | 7 years          | Cloud Archive
Loan Agreement             | 7 years + life   | Cloud Archive
Payment Records            | 7 years          | Cloud Archive
Collection Records         | 7 years post-CO  | Cloud Archive
Credit Investigation       | 7 years          | Cloud Archive
Consent Forms              | 7 years          | Cold Storage
Call Recordings            | 3 years          | Cold Storage
Adverse Action Notices     | 7 years          | Cloud Archive
Fair Lending Analysis      | Permanent        | Cloud Archive
Compliance Reviews         | Permanent        | Cloud Archive
```

### 5.3 Document Access Control

```
Access by Role:
├─ Loan Officer
│  ├─ Own loan applications ✓
│  ├─ Own loan files ✓
│  ├─ Loan agreements ✓
│  └─ Own payment records ✓
│
├─ Collector
│  ├─ Assigned collection files ✓
│  ├─ Collection communications ✓
│  ├─ Payment history ✓
│  └─ Contact info ✓
│
├─ Compliance Officer
│  ├─ All audit logs ✓
│  ├─ All compliance records ✓
│  ├─ All consent forms ✓
│  └─ Fair lending analysis ✓
│
└─ Finance/Accounting
   ├─ Transaction records ✓
   ├─ Payment records ✓
   ├─ General ledger access ✓
   └─ Financial reports ✓

Access logging:
├─ Every document access recorded
├─ User, timestamp, duration
├─ Reason captured (if required)
└─ Unauthorized access alerts
```

---

## 6. API ENDPOINTS

### 6.1 CRM Sync

```
POST /api/v1/sync/crm/loan
──────────────────────────
(Internal only - not authenticated)
Request:
{
  "loan_id": 100,
  "sync_type": "full"  // or "partial"
}

Response: 200 OK
{
  "sync_id": "SYNC-001",
  "status": "completed",
  "synced_fields": 15,
  "crm_reference": "FA-INV-100"
}

────────────────────────────

POST /api/v1/sync/crm/collection-activity
──────────────────────────────────────────
Request:
{
  "activity_id": 5001,
  "activity_type": "called"
}

Response: 200 OK
{
  "synced": true,
  "crm_reference": "FA-COMM-5001"
}
```

### 6.2 Payment Processing

```
POST /api/v1/payments/process-batch
───────────────────────────────────
Request:
{
  "batch_date": "2026-04-28",
  "payments": [
    {
      "loan_id": 100,
      "amount": 500.00,
      "payment_method": "ach"
    }
  ]
}

Response: 200 OK
{
  "batch_id": "BATCH-20260428-001",
  "status": "submitted",
  "payment_count": 1,
  "total_amount": 500.00,
  "submission_time": "2026-04-28T07:00:00Z"
}

────────────────────────────

GET /api/v1/payments/batch/:batch_id/status
────────────────────────────────────────────
Response: 200 OK
{
  "batch_id": "BATCH-20260428-001",
  "status": "settled",
  "settlement_date": "2026-04-29",
  "payments": [
    {
      "payment_id": 1001,
      "loan_id": 100,
      "status": "settled",
      "amount": 500.00
    }
  ]
}
```

### 6.3 Compliance

```
GET /api/v1/compliance/fair-lending/quarterly
──────────────────────────────────────────────
Query Parameters:
  - quarter: Q1, Q2, Q3, Q4
  - year: 2026

Response: 200 OK
{
  "period": "Q1 2026",
  "compliance_status": "pass",
  "approval_rates": {
    "white": 89.0,
    "hispanic": 87.0,
    "black": 86.5,
    "asian": 91.0
  },
  "disparate_impact_detected": false,
  "interest_rate_differential": 12,  // bps
  "findings": [],
  "recommendations": []
}

────────────────────────────

POST /api/v1/compliance/adverse-action
──────────────────────────────────────
Request:
{
  "application_id": 5001,
  "decision": "denied",
  "reasons": ["Insufficient income", "High debt ratio"],
  "mail_date": "2026-04-28"
}

Response: 201 Created
{
  "notice_id": "AA-5001",
  "status": "sent",
  "mail_date": "2026-04-28",
  "tracking_number": "USPS-123456"
}
```

### 6.4 Audit Trail

```
GET /api/v1/audit/entity/:entity_type/:entity_id
────────────────────────────────────────────────
Response: 200 OK
{
  "entity": {
    "type": "Loan",
    "id": 100,
    "history": [
      {
        "audit_id": 10001,
        "action": "created",
        "timestamp": "2026-01-15T10:00:00Z",
        "old_values": null,
        "new_values": {
          "loan_number": "LN-2026-001",
          "amount": 50000.00
        },
        "changed_by": "john_officer"
      },
      {
        "audit_id": 10002,
        "action": "updated",
        "timestamp": "2026-03-01T14:30:00Z",
        "old_values": { "status": "funded" },
        "new_values": { "status": "active" },
        "changed_by": "system"
      }
    ]
  }
}

────────────────────────────

GET /api/v1/audit/search
────────────────────────
Query Parameters:
  - entity_type: Loan, Payment, CollectionTask
  - action: created, updated, deleted
  - user_id: john_officer
  - from_date: 2026-01-01
  - to_date: 2026-04-30
  - limit: 100

Response: 200 OK
{
  "results": [
    { ... }
  ],
  "total_count": 245,
  "page": 1
}
```

---

## 7. IMPLEMENTATION CHECKLIST

Phase 1: Core Infrastructure (2 weeks)
- [ ] CRM sync service
- [ ] Payment processor integration
- [ ] Audit logging system
- [ ] Database schema

Phase 2: Regulatory Compliance (2 weeks)
- [ ] Fair lending monitoring
- [ ] FDCPA compliance checks
- [ ] TCPA compliance checks
- [ ] Adverse action notices

Phase 3: Document Management (1 week)
- [ ] Document storage setup
- [ ] Access control system
- [ ] Retention policy enforcement
- [ ] Archive automation

Phase 4: Testing & Deployment (1 week)
- [ ] Integration testing
- [ ] Compliance validation
- [ ] Performance testing
- [ ] Production deployment

---

**Status**: Specification complete, ready for development  
**Estimated Timeline**: 8 weeks (with 3 developers)  
**Next Step**: CRM integration service implementation

