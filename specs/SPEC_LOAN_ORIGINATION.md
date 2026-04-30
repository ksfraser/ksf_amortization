# SPEC-ORIGINATION: Loan Origination Workflow Specification

**Version**: 1.0 | **Date**: April 28, 2026 | **Status**: Ready for Implementation

---

## 1. OVERVIEW

The Loan Origination Workflow enables customers to apply for loans through a web portal, undergo underwriting, and receive approval through a streamlined digital process. Integration with CRM system provides full audit trail and customer relationship tracking.

### Capabilities
- Digital loan application
- Credit check integration
- Income verification workflow
- Document management (upload, storage, retrieve)
- Underwriting checklist & approval
- TILA disclosure generation
- E-signature workflow
- Status tracking & notifications

### Success Metrics
- Application completion rate > 85%
- Average origination time < 2 hours (manual underwriting)
- Document rejection rate < 5%
- Customer satisfaction score > 4.5/5

---

## 2. USER FLOWS

### 2.1 Application Submission Flow

```
START
  ↓
┌─────────────────────────────────┐
│ Borrower Login / Registration   │ Create FA customer record if new
├─────────────────────────────────┤
│ Select Loan Type & Amount       │ Validate: amount, rate, term
├─────────────────────────────────┤
│ Enter Personal Information      │ First, last, DoB, SSN, email, phone
├─────────────────────────────────┤
│ Employment & Income Details     │ Employer, position, annual income, history
├─────────────────────────────────┤
│ Co-Loan Officer (optional)      │ Add authorized representatives
├─────────────────────────────────┤
│ Review Terms & Conditions       │ Show TILA, rate, payment, disclosure
├─────────────────────────────────┤
│ Submit Application              │ Status → SUBMITTED
└─────────────────────────────────┘
  ↓
  Application received → CRM logged as new opportunity
  Credit check initiated (async)
  Income verification document requested
```

### 2.2 Underwriting Flow

```
Application SUBMITTED
  ├─ Credit Check Received → Status CREDIT_APPROVED or CREDIT_DENIED
  │
  ├─ Income Verification
  │   ├─ Request: Recent paystubs, tax returns, bank statements
  │   ├─ Document upload by borrower
  │   └─ Verification: Automated or manual review
  │
  ├─ Risk Assessment
  │   ├─ DTI Ratio, Credit Score, LTV (if secured)
  │   ├─ Auto-approve if within guidelines
  │   └─ Flag for manual review if outside guidelines
  │
  ├─ Underwriting Decision
  │   ├─ APPROVED: Loan offered
  │   ├─ APPROVED_WITH_CONDITIONS: Conditional offer
  │   ├─ DENIED: Declination sent
  │   └─ SUSPENDED: Awaiting more information
  │
  └─ Offer Letter Generated
      └─ Sent via email + available in portal
```

### 2.3 E-Signature & Funding Flow

```
Borrower receives offer
  ├─ Reviews terms in portal or email
  ├─ Clicks "Accept Offer"
  └─ Redirected to e-signature workflow
    ├─ DocuSign (or Hellosign) authentication
    ├─ Document package assembled (Promissory Note, Disclosure, etc.)
    ├─ Borrower & Co-signer sign (if applicable)
    ├─ Signatures returned to system
    └─ Status → DOCUMENTS_SIGNED
  ├─ Final verification (address, employment)
  ├─ Funding authorization
  ├─ Funds disbursed (depends on loan type)
  ├─ Loan created in amortization system
  ├─ First payment scheduled
  └─ Status → FUNDED
      ↓
      Welcome email sent
      Portal access enabled
      CRM opportunity closed (won)
```

---

## 3. DATA MODEL

### 3.1 Loan Application Entity

```php
class LoanApplication implements \JsonSerializable {
    // Identifiers
    private int $application_id;
    private int $debtor_no;  // FrontAccounting customer
    private string $application_number;  // Unique reference
    
    // Status & Timeline
    private string $status;  // INITIATED, SUBMITTED, CREDIT_APPROVED, APPROVED, DENIED, FUNDED
    private \DateTimeImmutable $submitted_date;
    private \DateTimeImmutable $approved_date;
    private \DateTimeImmutable $funded_date;
    private \DateTimeImmutable $declined_date;
    
    // Loan Details
    private float $requested_amount;          // Decimal(15,2)
    private float $approved_amount;           // May differ from requested
    private string $loan_type;                // auto, personal, mortgage, business, etc.
    private string $loan_purpose;
    private float $interest_rate;
    private int $term_months;
    private float $fico_score;
    private float $debt_to_income_ratio;
    
    // Borrower Information
    private string $first_name;
    private string $last_name;
    private string $email;
    private string $phone;
    private \DateTimeImmutable $date_of_birth;
    private string $ssn_encrypted;  // Encrypted storage
    
    // Employment
    private string $employer_name;
    private string $employment_status;  // employed, self_employed, retired, unemployed
    private float $annual_income;
    private string $income_verification_status;  // PENDING, VERIFIED, FAILED
    
    // Co-applicant (Optional)
    private ?string $co_applicant_first_name;
    private ?string $co_applicant_last_name;
    private ?string $co_applicant_email;
    
    // Audit
    private string $created_by;
    private \DateTimeImmutable $created_at;
    private string $last_modified_by;
    private \DateTimeImmutable $last_modified_at;
}
```

### 3.2 Application Document Entity

```php
class ApplicationDocument {
    private int $document_id;
    private int $application_id;
    private string $document_type;  // paystub, tax_return, bank_statement, id_proof, etc.
    private string $file_name;
    private string $s3_key;  // Storage location
    private string $mime_type;
    private int $file_size_bytes;
    private string $upload_status;  // uploading, uploaded, virus_scanned, approved, rejected
    private string $rejection_reason;
    private \DateTimeImmutable $uploaded_at;
    private \DateTimeImmutable $verified_at;
    private string $verified_by;
}
```

### 3.3 Application Event Entity

```php
class ApplicationEvent {
    private int $event_id;
    private int $application_id;
    private string $event_type;  // SUBMITTED, CREDIT_CHECK_RESULT, INCOME_VERIFIED, APPROVED, etc.
    private string $event_data;  // JSON with details
    private string $created_by;   // System, underwriter, etc.
    private \DateTimeImmutable $created_at;
}
```

### 3.4 Database Schema

```sql
CREATE TABLE 0_ksf_loan_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    debtor_no INT NOT NULL,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM(
        'INITIATED', 'SUBMITTED', 'CREDIT_APPROVED', 'CREDIT_DENIED',
        'INCOME_VERIFIED', 'APPROVED', 'APPROVED_WITH_CONDITIONS', 
        'DENIED', 'SUSPENDED', 'ACCEPTED', 'DOCUMENTS_SIGNED', 'FUNDED', 'WITHDRAWN'
    ) NOT NULL DEFAULT 'INITIATED',
    
    requested_amount DECIMAL(15,2) NOT NULL,
    approved_amount DECIMAL(15,2),
    loan_type VARCHAR(32) NOT NULL,
    loan_purpose VARCHAR(255),
    interest_rate DECIMAL(8,4),
    term_months INT,
    
    fico_score INT,
    debt_to_income_ratio DECIMAL(5,2),
    
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date_of_birth DATE,
    ssn_encrypted VARCHAR(255),  -- Encrypted
    
    employer_name VARCHAR(255),
    employment_status ENUM('employed', 'self_employed', 'retired', 'unemployed'),
    annual_income DECIMAL(15,2),
    income_verification_status ENUM('PENDING', 'VERIFIED', 'FAILED') DEFAULT 'PENDING',
    
    co_applicant_first_name VARCHAR(100),
    co_applicant_last_name VARCHAR(100),
    co_applicant_email VARCHAR(100),
    
    submitted_date DATETIME,
    approved_date DATETIME,
    funded_date DATETIME,
    declined_date DATETIME,
    
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_modified_by VARCHAR(100),
    last_modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (debtor_no) REFERENCES 0_debtors(debtor_no),
    INDEX idx_debtor_no (debtor_no),
    INDEX idx_status (status),
    INDEX idx_submitted_date (submitted_date)
);

CREATE TABLE 0_ksf_application_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    s3_key VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100),
    file_size_bytes INT,
    upload_status ENUM('uploading', 'uploaded', 'virus_scanned', 'approved', 'rejected') DEFAULT 'uploading',
    rejection_reason VARCHAR(500),
    uploaded_at DATETIME,
    verified_at DATETIME,
    verified_by VARCHAR(100),
    
    FOREIGN KEY (application_id) REFERENCES 0_ksf_loan_applications(application_id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_document_type (document_type)
);

CREATE TABLE 0_ksf_application_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (application_id) REFERENCES 0_ksf_loan_applications(application_id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_event_type (event_type)
);
```

---

## 4. SERVICE ARCHITECTURE

### 4.1 LoanOriginationService

```php
namespace KerryFraser\KsfAmortization\Origination;

class LoanOriginationService {
    public function __construct(
        private EntityManager $em,
        private CreditCheckService $creditService,
        private IncomeVerificationService $incomeService,
        private CRMService $crm,
        private MailService $mailService,
        private EventDispatcher $events
    ) {}
    
    // Create new application
    public function createApplication(
        int $debtorNo,
        LoanApplicationData $data
    ): LoanApplication;
    
    // Submit for processing
    public function submitApplication(int $applicationId): void;
    
    // Retrieve application
    public function getApplication(int $applicationId): LoanApplication;
    
    // Get borrower's applications
    public function getApplicationsByDebtor(int $debtorNo): array;
    
    // Request income verification documents
    public function requestIncomeVerification(int $applicationId): void;
    
    // Upload document
    public function uploadDocument(
        int $applicationId,
        string $documentType,
        UploadedFile $file
    ): ApplicationDocument;
    
    // Trigger credit check
    public function triggerCreditCheck(int $applicationId): void;
    
    // Underwrite application
    public function underwriteApplication(
        int $applicationId,
        UnderwritingDecision $decision
    ): void;
    
    // Send loan offer
    public function sendLoanOffer(int $applicationId): void;
    
    // Accept offer & start e-signature
    public function acceptOffer(int $applicationId): void;
    
    // Webhook: E-signature completed
    public function handleSignatureComplete(int $applicationId, array $signatureData): void;
    
    // Fund loan - create in amortization system
    public function fundLoan(int $applicationId): Loan;
}
```

### 4.2 CreditCheckService

```php
class CreditCheckService {
    public function __construct(
        private EquifaxAdapter $equifax,  // Or Experian, TransUnion
        private Logger $logger
    ) {}
    
    public function checkCredit(LoanApplication $app): CreditCheckResult {
        // Call external API
        $result = $this->equifax->getPullCredit(
            $app->getFirstName(),
            $app->getLastName(),
            $app->getSSN(),
            $app->getDateOfBirth()
        );
        
        return CreditCheckResult::fromAPI($result);
    }
}
```

### 4.3 IncomeVerificationService

```php
class IncomeVerificationService {
    public function verifyFromDocuments(
        int $applicationId,
        array $documents
    ): VerificationResult {
        // Automated: Parse paystubs, tax returns
        // Returns: annual income, employment verification
    }
    
    public function flagForManualReview(int $applicationId): void {
        // Mark application requiring underwriter review
    }
}
```

### 4.4 UnderwritingEngine

```php
class UnderwritingEngine {
    public function autoDecide(
        LoanApplication $app,
        CreditCheckResult $credit
    ): AutoUnderwritingDecision {
        // Rules-based decision:
        // - Credit score > 700? APPROVED : REVIEW
        // - DTI < 43%? APPROVED : REVIEW
        // - Income verified? Required
        // - No recent delinquencies? Required
        
        return new AutoUnderwritingDecision();
    }
}
```

---

## 5. API ENDPOINTS

### 5.1 Application Management

```
POST /api/v1/origination/applications
─────────────────────────────────────
Request:
{
  "loan_type": "auto",
  "requested_amount": 25000.00,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "555-555-5555",
  "date_of_birth": "1985-05-15",
  "ssn": "123-45-6789",
  "employer_name": "Acme Corp",
  "employment_status": "employed",
  "annual_income": 75000.00
}

Response: 201 Created
{
  "application_id": 1001,
  "application_number": "APP-2026-000001",
  "status": "INITIATED",
  "created_at": "2026-04-28T10:00:00Z"
}

───────────────────────────────────────

GET /api/v1/origination/applications/:application_id
─────────────────────────────────────────────────────
Response: 200 OK
{
  "application_id": 1001,
  "debtor_no": 15,
  "application_number": "APP-2026-000001",
  "status": "SUBMITTED",
  "requested_amount": 25000.00,
  "approved_amount": 25000.00,
  "term_months": 60,
  "interest_rate": 6.50,
  "monthly_payment": 483.32,
  "fico_score": 750,
  "debt_to_income_ratio": 35.2,
  "submission_date": "2026-04-28T10:15:00Z",
  "documents": [
    {
      "document_id": 5001,
      "document_type": "paystub",
      "upload_status": "approved",
      "uploaded_at": "2026-04-28T10:20:00Z"
    }
  ],
  "timeline": [
    {
      "event_type": "SUBMITTED",
      "created_at": "2026-04-28T10:15:00Z"
    },
    {
      "event_type": "CREDIT_APPROVED",
      "created_at": "2026-04-28T10:30:00Z",
      "fico_score": 750
    }
  ]
}

───────────────────────────────────────

GET /api/v1/origination/applications
───────────────────────────────────────
Query Parameters:
  - debtor_no (int, optional)
  - status (string, optional): SUBMITTED, APPROVED, DENIED, FUNDED
  - page (int, default 1)
  - per_page (int, default 20)

Response: 200 OK
{
  "applications": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 156,
    "last_page": 8
  }
}
```

### 5.2 Document Management

```
POST /api/v1/origination/applications/:application_id/documents
────────────────────────────────────────────────────────────────
Content-Type: multipart/form-data
Request:
  - file: [binary file content]
  - document_type: "paystub" | "tax_return" | "bank_statement" | "id_proof" | "utility_bill"

Response: 201 Created
{
  "document_id": 5001,
  "application_id": 1001,
  "document_type": "paystub",
  "file_name": "paystub_2026_04.pdf",
  "upload_status": "uploaded",
  "uploaded_at": "2026-04-28T10:20:00Z"
}

────────────────────────────────────────────────────────────────

GET /api/v1/origination/applications/:application_id/documents
────────────────────────────────────────────────────────────────
Response: 200 OK
{
  "documents": [
    {
      "document_id": 5001,
      "document_type": "paystub",
      "file_name": "paystub_2026_04.pdf",
      "upload_status": "approved",
      "uploaded_at": "2026-04-28T10:20:00Z"
    }
  ]
}
```

### 5.3 Decision & Funding

```
POST /api/v1/origination/applications/:application_id/decisions
─────────────────────────────────────────────────────────────────
Request (Underwriter):
{
  "decision": "APPROVED",
  "approved_amount": 25000.00,
  "interest_rate": 6.50,
  "term_months": 60,
  "conditions": []  // or ["require_employment_letter", "require_longer_history"]
}

Response: 200 OK
{
  "application_id": 1001,
  "status": "APPROVED",
  "offer_letter_url": "https://portal.example.com/offers/APP-2026-000001"
}

─────────────────────────────────────────────────────────────────

POST /api/v1/origination/applications/:application_id/accept-offer
────────────────────────────────────────────────────────────────────
Request (Borrower):
{}

Response: 200 OK
{
  "application_id": 1001,
  "status": "ACCEPTED",
  "esignature_url": "https://docusign.example.com/envelope/xyz123"
}

────────────────────────────────────────────────────────────────────

POST /api/v1/origination/applications/:application_id/fund
────────────────────────────────────────────────────────────
Request (Admin/Funding Officer):
{
  "funding_method": "ach" | "wire" | "check",
  "funding_reference": "Check #12345"
}

Response: 201 Created
{
  "application_id": 1001,
  "status": "FUNDED",
  "loan_id": 1,  // New amortization loan created
  "first_payment_due": "2026-05-28"
}
```

---

## 6. EVENTS & CRM INTEGRATION

### 6.1 Application Events (PSR-14)

```php
// Event classes - dispatched when statuses change
LoanApplicationSubmittedEvent
  ├─ application_id
  ├─ debtor_no
  └─ listener: EmailNotificationListener, CRMLoggingListener

LoanApplicationApprovedEvent
  ├─ application_id
  ├─ approved_amount
  ├─ interest_rate
  └─ listener: EmailNotificationListener, CRMOpportunityListener

LoanApplicationFundedEvent
  ├─ application_id
  ├─ loan_id (newly created)
  └─ listener: PortalNotificationListener, CRMOpportunityListener
```

### 6.2 CRM Integration

```php
// LoanOriginationService constructor receives CRMService
// On application submission:
$this->crm->logCommunication([
    'debtor_no' => $application->getDebtorNo(),
    'communication_type' => 'system_note',
    'subject' => "Loan Application Received: APP-{$appNumber}",
    'message' => "New loan application for ${amount} submitted",
    'created_by' => 'LOAN_SYSTEM'
]);

// Create CRM Opportunity for sales tracking
$this->crm->createOpportunity([
    'debtor_no' => $debtor_no,
    'opportunity_type' => 'loan_application',
    'title' => "{$firstName} {$lastName} - Auto Loan ${$amount}",
    'detail' => $application->getID(),
    'probability' => 50,  // 50% approval estimate
    'estimated_value' => $amount
]);

// On approval, update opportunity
$this->crm->updateOpportunity($opportunityId, ['probability' => 95]);

// On funding, close opportunity (won)
$this->crm->closeOpportunity($opportunityId, 'WON', $loan_id);
```

---

## 7. COMPLIANCE & SECURITY

### 7.1 TILA Compliance

```php
class TILADisclosureGenerator {
    public function generate(LoanApplication $app): TILADisclosure {
        // Generate Truth in Lending Act disclosure
        // Include:
        // - Amount financed
        // - Finance charge
        // - Total of payments
        // - Annual Percentage Rate (APR)
        // - Payment schedule
        // - Late payment fees
        // - Prepayment penalties/savings
    }
}
```

### 7.2 Data Security

- SSN stored encrypted (AES-256)
- PCI DSS compliance for payment data
- Document files stored in encrypted S3 buckets
- Audit trail for all access
- Two-factor authentication for underwriters

### 7.3 Anti-Fraud

- Verify SSN format (123-45-6789)
- Cross-reference debtor_no with FA database
- Duplicate application detection
- Suspicious income level flagging
- Credit bureau address verification

---

## 8. NOTIFICATION FLOW

```
Application Submitted → Email to borrower (confirmation + next steps)
                     → Email to loan officer (task created)

Credit Check Complete → Email to borrower (results summary)
                      → Flagged in portal

Approved → Email to borrower (offer letter attached)
        → Portal shows "Review Your Offer"

Denied → Email to borrower (reason, appeal process)
      → CRM opportunity closed (lost)

Awaiting Signature → Email to borrower (e-signature link)
                  → Reminder if not signed in 3 days

Funded → Email to borrower (congratulations, welcome to portal)
      → Portal access automatically activated
      → First payment notification
```

---

## 9. IMPLEMENTATION CHECKLIST

Phase 1: Foundation
- [ ] Database schema creation
- [ ] Entity classes
- [ ] LoanOriginationService skeleton
- [ ] CRM integration utilities

Phase 2: Submission & Underwriting
- [ ] Application submission endpoint
- [ ] Document upload endpoint
- [ ] Credit check service adapter
- [ ] Auto-underwriting decision engine

Phase 3: Approval & E-Signature
- [ ] Approval workflow & endpoints
- [ ] E-signature integration (DocuSign)
- [ ] Webhook handling for signature completion
- [ ] TILA disclosure generation

Phase 4: Funding & Portal Integration
- [ ] Fund loan endpoint
- [ ] Create amortization loan from application
- [ ] Portal access provisioning
- [ ] Welcome email & notifications

Phase 5: Testing & Deployment
- [ ] Unit test coverage > 80%
- [ ] Integration tests with CRM
- [ ] E2E user journey testing
- [ ] Load testing (100 concurrent submissions)
- [ ] Staging deployment & UAT
- [ ] Production rollout

---

**Status**: Specification complete, ready for development  
**Estimated Timeline**: 14 weeks (with 3 developers)  
**Next Step**: Create implementation PR with Phase 1 code

