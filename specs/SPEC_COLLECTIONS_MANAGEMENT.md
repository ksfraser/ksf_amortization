# SPEC-COLLECTIONS: Collections Management Workflow Specification

**Version**: 1.0 | **Date**: April 28, 2026 | **Status**: Ready for Implementation

---

## 1. OVERVIEW

The Collections Management system automates and tracks delinquent loan accounts, enabling structured workflows for collecting past-due payments while maintaining FDCPA compliance. Tight CRM integration enables task management, collections team dashboards, and communication tracking.

### Key Features
- Delinquency monitoring & escalation
- Automated collection task assignment
- Collection letter workflow
- Payment arrangement negotiation
- Collector performance tracking
- Collections activity logging
- FDCPA compliance checking
- Settlement calculations
- Charge-off recommendations
- CRM opportunity integration

### Success Metrics
- Collection rate: 90%+ for accounts < 30 days past due
- Collection rate: 75%+ for accounts 30-60 days
- Collection rate: 50%+ for accounts 60-90 days
- Compliance violations: 0 per quarter
- Collector productivity: 15-20 calls/day

---

## 2. DELINQUENCY CLASSIFICATION

### 2.1 Delinquency Tiers

```
CURRENT: All payments on schedule
├─ Aging: 0-9 days
├─ Action: None (monitoring only)
└─ Letter: Reminder email/SMS

30_DAYS_PAST_DUE: First missed payment + grace period
├─ Aging: 10-39 days late
├─ Action: Automated task created for collector
├─ Letter: "Payment Reminder" (1st notice)
└─ Goal: Collect payment within 5 business days

60_DAYS_PAST_DUE: Second month past due
├─ Aging: 40-69 days late
├─ Action: Escalated task + manual review
├─ Letter: "Urgent Payment Notice" (2nd notice)
├─ Goal: Payment arrangement or collections action
└─ Note: "Final Notice Before Legal Action" sent

90_PLUS_DAYS_PAST_DUE: Third+ month past due
├─ Aging: 70+ days late
├─ Action: Attorney review, charge-off consideration
├─ Letter: Legal threat notice (if applicable)
├─ Goal: Settlement, payment plan, or charge-off
└─ Note: Reported to credit bureau as delinquent claim
```

### 2.2 Payment Pattern Detection

```
CURRENT: All on-time payments
  └─ No action needed

CHRONIC_LATE (75%+ payments 10+ days late)
  ├─ Pattern: Systemic late payment pattern
  ├─ Risk: High default risk
  └─ Strategy: Payment arrangement with earlier due date

RECENT_DETERIORATION (was on-time, now late/missed)
  ├─ Pattern: Recently began missing payments
  ├─ Risk: Medium (may be temporary hardship)
  └─ Strategy: Contact to understand cause, payment arrangement

SPORADIC_PAYER (mix of on-time and late/missed)
  ├─ Pattern: Unpredictable payment behavior
  ├─ Risk: Medium to high
  └─ Strategy: Frequent contact, shorter follow-up windows

SKIP_PATTERN (systematic payment skips)
  ├─ Pattern: Skip month A, pay month B, skip month C, etc.
  ├─ Risk: High (intentional non-payment)
  └─ Strategy: Escalation, possible legal action
```

---

## 3. COLLECTIONS WORKFLOW

### 3.1 Automated Task Creation

```
Payment becomes 10+ days late
  ↓
System triggers delinquency event
  ├─ Create collection task
  ├─ Calculate days overdue
  ├─ Generate collection letter
  └─ Assign to available collector (workload balanced)
  ↓
Collector receives task in CRM dashboard
  ├─ Click "Accept Task"
  ├─ View customer profile (CRM)
  ├─ View payment history
  ├─ View contact info (email, phone, address)
  └─ View prior collection attempts (history)
  ↓
Collector logs attempt
  ├─ "Called customer" → Promise to pay by date X
  ├─ "Left voicemail" → Scheduled follow-up
  ├─ "Reached answering service" → Message left
  ├─ "Customer not home" → Called back later
  ├─ "Bad debt" → Account flagged for legal review
  ├─ "Payment arrangement" → Created arrangement record
  └─ "Paid in full" → Task closed
```

### 3.2 Collection Letter Strategy

```
10-15 Days Late: "Friendly Reminder"
  └─ Tone: Helpful, assuming good faith
  └─ Template: "We noticed payment is late. Please remit to avoid..."
  └─ Channel: Email + SMS
  └─ CTA: "Make payment now" + link

20-30 Days Late: "Urgent Payment Notice"
  └─ Tone: Firmer, mentions consequences
  └─ Template: "Your account is now 30 days past due. Immediate action required..."
  └─ Channel: Email + SMS + postal mail
  └─ CTA: "Pay now or contact us to arrange payment"
  └─ Legal: May mention "final notice before legal action"

40-60 Days Late: "Final Notice Before Legal Action"
  └─ Tone: Legal tone, clear warning
  └─ Template: "If payment is not received within 10 days, we will pursue..."
  └─ Channel: Postal mail (certified) + Email + SMS
  └─ CTA: "Contact immediately to settle"
  └─ Legal: Clear threat of court action (if applicable)

70+ Days Late: Attorney Referral
  └─ Tone: Formal legal notice
  └─ Template: Legal demand letter from attorney
  └─ Channel: Certified mail
  └─ CTA: None (legal matter)
  └─ Note: Account flagged for charge-off consideration
```

### 3.3 Payment Arrangement Workflow

```
Collector negotiates arrangement with borrower
  ↓
Create Payment Arrangement
  ├─ Principal: Original amount
  ├─ Past_due: Late fees/interest
  ├─ Arrangement_total: Principal + past_due
  │
  ├─ Payment Schedule:
  │  ├─ Lump sum catch-up: $X by date Y
  │  ├─ Resume regular: $ per month starting Z
  │  └─ Example: Pay $1000 by 5/15, then $500/mo starting 6/1
  │
  └─ Terms:
     ├─ If payment missed: Account reverts to collections
     ├─ if arrangement completed: Full forgiveness of late fees
     └─ If arrangement broken: Attorney referral (if > $5k)
  ↓
Send Arrangement Agreement (email + postal)
  ├─ Borrower signature required
  ├─ Collector follows up (within 2 days)
  └─ Agreement effective once signed
  ↓
Track Arrangement Payments
  ├─ First payment due: Alert/reminder 3 days before
  ├─ Payment missed: Escalation task created
  ├─ Arrangement completed: Account status updated
  └─ Arrangement broken: Back to collections
```

---

## 4. SERVICE ARCHITECTURE

### 4.1 CollectionsService

```php
namespace KerryFraser\KsfAmortization\Collections;

class CollectionsService {
    public function __construct(
        private EntityManager $em,
        private CRMService $crm,
        private DelinquencyClassifier $classifier,
        private LetterTemplateEngine $letters,
        private NotificationService $notifications,
        private EventDispatcher $events
    ) {}
    
    // Get accounts requiring collection action
    public function getCollectionQueue(?string $collectorId = null): array;
    
    // Get pending tasks for collector
    public function getCollectorTasks(string $collectorId): array;
    
    // Assign task to collector
    public function assignCollectionTask(
        int $loanId,
        string $collectorId
    ): CollectionTask;
    
    // Log collection activity
    public function logCollectionActivity(
        int $taskId,
        string $activityType,  // called, message_left, promised_pay, paid, etc.
        array $details
    ): CollectionActivity;
    
    // Create payment arrangement
    public function createPaymentArrangement(
        int $loanId,
        PaymentArrangementData $data
    ): PaymentArrangement;
    
    // Generate collection letter
    public function generateCollectionLetter(
        int $loanId,
        string $letterType  // friendly, urgent, final, legal
    ): CollectionLetter;
    
    // Recommend for charge-off
    public function flagForChargeOff(int $loanId, string $reason): void;
    
    // Get collector performance
    public function getCollectorStats(string $collectorId): CollectorStats;
    
    // Get portfolio delinquency summary
    public function getPortfolioDelinquencySummary(): DelinquencySummary;
}
```

### 4.2 DelinquencyTaskManager

```php
class DelinquencyTaskManager {
    // Auto-create tasks when delinquency detected
    public function scanAndCreateTasks(): void {
        $delinquent = $this->getNewlyDelinquent();  // 10+ days late, no task
        foreach ($delinquent as $loan) {
            $this->assignCollectionTask($loan);
        }
    }
    
    // Auto-escalate tasks based on aging
    public function escalateTasks(): void {
        // 30-day accounts → Escalate priority
        // 60-day accounts → Manual review flag
        // 90-day accounts → Attorney review
    }
}
```

### 4.3 PaymentArrangementService

```php
class PaymentArrangementService {
    public function createArrangement(
        int $loanId,
        int $collectorId,
        array $terms
    ): PaymentArrangement;
    
    public function generateAgreement(
        int $arrangementId
    ): PDF;  // Downloadable agreement
    
    public function trackPayment(
        int $arrangementId,
        Payment $payment
    ): ArrangementStatus;  // on_track, completed, defaulted
}
```

### 4.4 FDCPAComplianceChecker

```php
class FDCPAComplianceChecker {
    // Fair Debt Collection Practices Act compliance
    
    public function validateLetterContent(string $content): ValidationResult {
        // Check for required disclosures
        // Check for prohibited language
        // Verify debt amount accuracy
    }
    
    public function validateContactTime(
        string $debtor_state,
        \DateTime $contact_time
    ): bool {
        // Ensure 8 AM - 9 PM debtor's local time (with exceptions)
        // Check for do-not-call registration (if required)
    }
    
    public function validateContactFrequency(
        int $loanId,
        int $days = 30
    ): bool {
        // No more than 7 contact attempts per week
        // No more than 1-2 calls per day
    }
}
```

---

## 5. DATA MODEL

### 5.1 Collection Task Entity

```php
class CollectionTask {
    private int $task_id;
    private int $loan_id;
    private string $status;  // OPEN, IN_PROGRESS, RESOLVED, ESCALATED
    private string $priority;  // LOW, MEDIUM, HIGH, CRITICAL
    private int $delinquency_age;  // Days past due
    private int $assigned_collector_id;
    private \DateTimeImmutable $assigned_at;
    private \DateTimeImmutable $due_date;  // When action required
    private \DateTimeImmutable $resolved_at;
    private string $resolution_reason;  // payment_received, arrangement_made, escalated, written_off
    
    private int $contact_attempts;  // Number of contact attempts
    private \DateTimeImmutable $last_contact_date;
    private string $promised_payment_date;
    private float $promised_payment_amount;
    
    public function getDebtorInfo(): array;
    public function getContactInfo(): array;
    public function getPaymentHistory(): array;
    public function getPriorCollectionAttempts(): array;
}
```

### 5.2 Collection Activity Entity

```php
class CollectionActivity {
    private int $activity_id;
    private int $task_id;
    private string $activity_type;  // called, message_left, promised_pay, paid, customer_not_home, bad_number, etc.
    private string $contact_method;  // phone, email, sms, mail, in_person
    private string $notes;
    private string $recorded_by;  // Collector name/ID
    private \DateTimeImmutable $created_at;
    
    // For calls
    private int $call_duration_seconds;
    private string $call_recording_url;  // Optional recording
    
    // For promises to pay
    private float $promised_amount;
    private \DateTime $promised_date;
}
```

### 5.3 Payment Arrangement Entity

```php
class PaymentArrangement {
    private int $arrangement_id;
    private int $loan_id;
    private string $status;  // PROPOSED, ACCEPTED, COMPLETED, DEFAULTED
    
    // Amount details
    private float $principal_balance;
    private float $past_due_amount;
    private float $late_fees;
    private float $arrangement_total;  // Total to pay to cure
    
    // Schedule
    private array $payment_schedule;  // [{'date': '2026-05-15', 'amount': 1000}, ...]
    private \DateTime $first_payment_date;
    private \DateTime $final_payment_date;
    
    // Terms
    private string $terms_text;
    private \DateTime $agreement_date;
    private bool $borrower_accepted;
    private bool $collector_authorized_by;
    
    // Tracking
    private int $payments_made;
    private int $payments_missed;
    private \DateTimeImmutable $created_at;
    private \DateTimeImmutable $completed_at;
}
```

### 5.4 Database Schema

```sql
CREATE TABLE 0_ksf_collection_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    status ENUM('OPEN', 'IN_PROGRESS', 'RESOLVED', 'ESCALATED') NOT NULL DEFAULT 'OPEN',
    priority ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL,
    delinquency_age INT,  -- Days late
    
    assigned_collector_id INT,
    assigned_at DATETIME,
    due_date DATETIME,
    resolved_at DATETIME,
    resolution_reason VARCHAR(100),
    
    contact_attempts INT DEFAULT 0,
    last_contact_date DATETIME,
    promised_payment_date DATE,
    promised_payment_amount DECIMAL(15,2),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES 0_ksf_loans_summary(loan_id),
    FOREIGN KEY (assigned_collector_id) REFERENCES 0_users(id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_assigned_collector (assigned_collector_id)
);

CREATE TABLE 0_ksf_collection_activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    contact_method ENUM('phone', 'email', 'sms', 'mail', 'in_person') NOT NULL,
    notes TEXT,
    recorded_by VARCHAR(100) NOT NULL,
    
    call_duration_seconds INT,
    call_recording_url VARCHAR(500),
    promised_amount DECIMAL(15,2),
    promised_date DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES 0_ksf_collection_tasks(task_id),
    INDEX idx_task_id (task_id),
    INDEX idx_activity_type (activity_type)
);

CREATE TABLE 0_ksf_payment_arrangements (
    arrangement_id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    status ENUM('PROPOSED', 'ACCEPTED', 'COMPLETED', 'DEFAULTED') NOT NULL DEFAULT 'PROPOSED',
    
    principal_balance DECIMAL(15,2),
    past_due_amount DECIMAL(15,2),
    late_fees DECIMAL(15,2),
    arrangement_total DECIMAL(15,2),
    
    payment_schedule JSON,  -- Array of {date, amount}
    first_payment_date DATE,
    final_payment_date DATE,
    
    terms_text TEXT,
    agreement_date DATE,
    borrower_accepted BOOLEAN DEFAULT FALSE,
    collector_authorized_by VARCHAR(100),
    
    payments_made INT DEFAULT 0,
    payments_missed INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    
    FOREIGN KEY (loan_id) REFERENCES 0_ksf_loans_summary(loan_id),
    INDEX idx_loan_id (loan_id),
    INDEX idx_status (status)
);
```

---

## 6. API ENDPOINTS

### 6.1 Collections Queue & Tasks

```
GET /api/v1/collections/queue
──────────────────────────────
Query Parameters:
  - priority: critical, high, medium, low
  - age_days_min: 10
  - age_days_max: 60
  - assigned_to: collector_id (optional)
  - page: 1
  - per_page: 20

Response: 200 OK
{
  "tasks": [
    {
      "task_id": 1001,
      "loan_id": 100,
      "loan_number": "LN-2025-001",
      "borrower_name": "John Doe",
      "phone": "555-555-5555",
      "amount_due": 2450.00,
      "days_late": 45,
      "priority": "HIGH",
      "status": "OPEN",
      "payment_pattern": "CHRONIC_LATE",
      "assigned_to": null,
      "contact_attempts": 3,
      "last_contact": "2026-04-25T14:30:00Z"
    }
  ],
  "pagination": { }
}

───────────────────────────────

GET /api/v1/collections/tasks/my
────────────────────────────────
(Authenticated as collector)
Response: 200 OK
{
  "tasks": [
    {
      "task_id": 1001,
      ... // Same as above
    }
  ],
  "stats": {
    "total_assigned": 12,
    "total_open": 8,
    "urgent_count": 3
  }
}

───────────────────────────────

POST /api/v1/collections/tasks/:task_id/accept
───────────────────────────────────────────────
Request:
{}

Response: 200 OK
{
  "task_id": 1001,
  "status": "IN_PROGRESS",
  "assigned_to": "john_collector",
  "assigned_at": "2026-04-28T10:00:00Z"
}
```

### 6.2 Collection Activities

```
POST /api/v1/collections/tasks/:task_id/activities
───────────────────────────────────────────────────
Request:
{
  "activity_type": "called",
  "contact_method": "phone",
  "notes": "Spoke with John, said will pay by 5/1",
  "promised_amount": 2450.00,
  "promised_date": "2026-05-01",
  "call_duration_seconds": 240
}

Response: 201 Created
{
  "activity_id": 5001,
  "task_id": 1001,
  "activity_type": "called",
  "created_at": "2026-04-28T14:30:00Z"
}

───────────────────────────────

GET /api/v1/collections/tasks/:task_id/activities
──────────────────────────────────────────────────
Response: 200 OK
{
  "activities": [
    {
      "activity_id": 5003,
      "activity_type": "called",
      "contact_method": "phone",
      "notes": "Spoke with John, said will pay by 5/1",
      "promised_date": "2026-05-01",
      "created_at": "2026-04-28T14:30:00Z",
      "recorded_by": "john_collector"
    }
  ]
}
```

### 6.3 Payment Arrangements

```
POST /api/v1/collections/arrangements
──────────────────────────────────────
Request:
{
  "loan_id": 100,
  "payment_schedule": [
    { "date": "2026-05-15", "amount": 1000.00 },
    { "date": "2026-06-15", "amount": 1225.00 },
    { "date": "2026-07-15", "amount": 1225.00 }
  ],
  "terms": "Full payment of past due amount required by 7/15"
}

Response: 201 Created
{
  "arrangement_id": 801,
  "loan_id": 100,
  "status": "PROPOSED",
  "arrangement_total": 3450.00,
  "agreement_url": "https://portal.example.com/arrangements/801/agreement.pdf"
}

───────────────────────────────

GET /api/v1/collections/arrangements/:arrangement_id
────────────────────────────────────────────────────
Response: 200 OK
{
  "arrangement_id": 801,
  "loan_id": 100,
  "status": "ACCEPTED",
  "arrangement_total": 3450.00,
  "payment_schedule": [
    {
      "date": "2026-05-15",
      "amount": 1000.00,
      "paid": true,
      "paid_date": "2026-05-14"
    },
    {
      "date": "2026-06-15",
      "amount": 1225.00,
      "paid": false,
      "status": "upcoming"
    }
  ],
  "borrower_accepted": true,
  "borrower_accepted_at": "2026-04-29T10:00:00Z"
}

───────────────────────────────

POST /api/v1/collections/arrangements/:arrangement_id/accept
────────────────────────────────────────────────────────────
(Borrower endpoint)
Request:
{ }

Response: 200 OK
{
  "arrangement_id": 801,
  "status": "ACCEPTED",
  "first_payment_due": "2026-05-15"
}
```

### 6.4 Collection Letters

```
GET /api/v1/collections/loans/:loan_id/letters
───────────────────────────────────────────────
Query Parameters:
  - letter_type: friendly, urgent, final, legal
  - format: pdf, html, text

Response: 200 OK
{
  "letter_type": "urgent",
  "content": "HTML/PDF content",
  "generated_at": "2026-04-28T10:00:00Z"
}

───────────────────────────────

POST /api/v1/collections/loans/:loan_id/letters/send
─────────────────────────────────────────────────────
Request:
{
  "letter_type": "urgent",
  "channels": ["email", "sms", "mail"]
}

Response: 200 OK
{
  "letter_sent": true,
  "email_sent": true,
  "sms_sent": true,
  "postal_request_created": true
}
```

### 6.5 Collector Performance

```
GET /api/v1/collections/collectors/:collector_id/stats
───────────────────────────────────────────────────────
Query Parameters:
  - period: week, month, quarter, year

Response: 200 OK
{
  "collector_id": "john_collector",
  "period": "month",
  "start_date": "2026-04-01",
  "end_date": "2026-04-30",
  "stats": {
    "tasks_assigned": 45,
    "tasks_completed": 38,
    "completion_rate": 84.4,
    "contact_attempts": 120,
    "calls": 85,
    "messages": 35,
    "promises_to_pay": 28,
    "promises_kept": 24,
    "keep_rate": 85.7,
    "average_call_duration": 320,  // seconds
    "collections": [
      { "date": "2026-04-01", "amount": 2450.00 },
      { "date": "2026-04-05", "amount": 1225.50 }
    ],
    "total_collected": 12450.00,
    "average_per_task": 327.63
  }
}
```

### 6.6 Portfolio Delinquency

```
GET /api/v1/collections/portfolio/delinquency-summary
─────────────────────────────────────────────────────
Response: 200 OK
{
  "total_loans": 1250,
  "current": { count: 1100, percentage: 88.0 },
  "30_days": { count: 100, percentage: 8.0 },
  "60_days": { count: 35, percentage: 2.8 },
  "90_plus_days": { count: 15, percentage: 1.2 },
  "total_delinquent": { count: 150, percentage: 12.0 },
  
  "past_due_amount": 125450.00,
  "portfolio_balance": 5250000.00,
  "delinquency_rate": 2.4,  // % of portfolio
  
  "aging_detail": [
    { "days_late": "10-19", "count": 45, "amount": 35000.00 },
    { "days_late": "20-29", "count": 30, "amount": 25000.00 },
    { "days_late": "30-39", "count": 20, "amount": 18000.00 },
    // ...
  ],
  
  "trends": {
    "30_day_trend": "+5.2%",  // Increase this month
    "60_day_trend": "-2.1%",
    "90_plus_trend": "=0%"
  }
}
```

---

## 7. CRM INTEGRATION

### 7.1 Collection Task Creation

```php
// When delinquency detected:
$this->crm->createOpportunity([
    'debtor_no' => $loan->getDebtorNo(),
    'opportunity_type' => 'collection_task',
    'title' => "Collection: {$borrowerName} - {$loanId} ({$daysLate} days late)",
    'detail' => "Amount: ${$amountDue}",
    'probability' => 95,  // Always high for collections
    'estimated_value' => $amountDue,
    'assigned_to' => $collectorId,
    'external_reference' => $taskId,
    'priority' => $priority  // HIGH, MEDIUM, LOW
]);
```

### 7.2 Activity Logging

```php
// Every collection activity logged to CRM:
$this->crm->logCommunication([
    'debtor_no' => $loan->getDebtorNo(),
    'communication_type' => 'collection_call',
    'subject' => "Collection Call - {$daysLate} days late",
    'message' => $notes,  // "Spoke with John, promised payment by 5/1"
    'created_by' => $collectorId,
    'external_reference' => $taskId,
    'duration_seconds' => $callDuration,
    'linked_loan_id' => $loanId
]);
```

### 7.3 Collector Dashboard

```php
// Collector sees all tasks assigned in CRM interface
// Uses CRM opportunity list filtered to:
// - opportunity_type = 'collection_task'
// - assigned_to = $collectorId
// - status != 'closed'

// Buttons lead to:
// - "Accept Task" → Mark in-progress
// - "Log Activity" → Add call/message note
// - "Create Arrangement" → Setup payment plan
// - "Generate Letter" → Send collection notice
// - "Close Task" → Mark resolved
```

---

## 8. COMPLIANCE & SECURITY

### 8.1 FDCPA Compliance
- No contact before 8 AM or after 9 PM (debtor's time zone)
- No contact with third parties without consent
- Clear debt identification in all communications
- No harassment (defined as repeated calls/contact)
- Verification of debt upon request
- Right to dispute debt notice
- Cease communication upon written request

### 8.2 TCPA Compliance (SMS)
- Consent required before SMS contact
- Opt-out capability on every message
- No automated systems without prior consent
- Message rate limiting

### 8.3 Audit Trail
- All contact attempts logged with timestamp
- Recording of calls (where legal)
- Document all promises made
- Track all letters/notices sent

---

## 9. IMPLEMENTATION CHECKLIST

Phase 1: Foundation (2 weeks)
- [ ] Database schema
- [ ] CollectionsService core
- [ ] DelinquencyTaskManager
- [ ] Task assignment logic

Phase 2: Workflow (2 weeks)
- [ ] Collection task APIs
- [ ] Activity logging
- [ ] Letter generation
- [ ] CRM integration

Phase 3: Arrangements & Features (2 weeks)
- [ ] Payment arrangement workflow
- [ ] FDCPA compliance checker
- [ ] Collector dashboard UI
- [ ] Performance metrics

Phase 4: Testing & Deployment (1 week)
- [ ] Unit tests (80% coverage)
- [ ] Integration testing
- [ ] UAT with collections team
- [ ] Production deployment

---

**Status**: Specification complete, ready for development  
**Estimated Timeline**: 10 weeks (with 3 developers)  
**Next Step**: Create service classes and database migrations

