# SPEC-PORTAL: Customer Portal Specification

**Version**: 1.0 | **Date**: April 28, 2026 | **Status**: Ready for Implementation

---

## 1. OVERVIEW

The Customer Portal provides borrowers with self-service access to loan information, payment capabilities, and communication with the lender. Deep integration with CRM system enables messaging, support tickets, and personalized notifications.

### Key Features
- Secure login via FrontAccounting authentication
- Multi-loan dashboard
- Payment portal (ACH, credit card, bank transfer)
- Amortization schedule access
- Payment history & receipts
- Payoff quote & early payoff scenarios
- Delinquency notices & collection status
- Message center (CRM integrated)
- Notification preferences
- Document downloads

### Success Metrics
- User adoption > 70% (within 3 months)
- Portal transaction rate > 40% (of all payments)
- Customer satisfaction > 4.5/5
- Support chat resolution rate > 85%

---

## 2. USER FLOWS

### 2.1 Login Flow

```
Customer visits portal.example.com
  ↓
┌─────────────────────────────────────┐
│ Select: "Existing Customer" or      │
│ "First Time? Register"              │
└─────────────────────────────────────┘
  │                                   │
  ├─ Existing Customer                │
  │  └ Enter email + password         │
  │  └ 2FA (SMS or email)             │
  │  └ Redirect to dashboard          │
  │                                   │
  └─ New Customer Registration        │
     └ Enter email, phone, SSN        │
     └ Verify email                   │
     └ Create password                │
     └ Link to FA customer (debtor_no)
     └ Redirect to dashboard
```

### 2.2 Dashboard Flow

```
Dashboard View (after login)
├─ Account Summary Widget
│  ├─ Total balance across loans
│  ├─ Next payment due date
│  ├─ Recent payment history (last 3)
│  └─ Account status (CURRENT, LATE, etc.)
│
├─ Loans List View
│  ├─ Select loan to drill down
│  ├─ View details: balance, rate, term remaining
│  └─ Quick actions (Pay, Payoff Quote, Schedule)
│
├─ Recent Messages (from CRM)
│  ├─ New notifications badge
│  └─ Quick access to message center
│
└─ Action Widget
   ├─ Make Payment
   ├─ View Schedule
   ├─ Request Modification
   └─ Contact Support
```

### 2.3 Payment Flow

```
User clicks "Make Payment"
  ↓
┌─────────────────────────────────────┐
│ Select Which Loan                   │
│ (if multiple loans)                 │
└─────────────────────────────────────┘
  ↓
┌─────────────────────────────────────┐
│ Select Payment Type/Amount          │
│ ├─ Regular payment (auto-filled)    │
│ ├─ Extra payment (additional)       │
│ ├─ Custom amount (min, max limits)  │
│ └─ Full payoff amount               │
└─────────────────────────────────────┘
  ↓
┌─────────────────────────────────────┐
│ Select Payment Method               │
│ ├─ ACH from bank account            │
│ ├─ Debit card                       │
│ ├─ Credit card (with fee)           │
│ └─ Wire transfer (large amounts)    │
└─────────────────────────────────────┘
  ↓
┌─────────────────────────────────────┐
│ Review & Confirm                    │
│ ├─ Confirmation # generated         │
│ ├─ Payment schedule confirmed       │
│ └─ Receipt shown + emailed          │
└─────────────────────────────────────┘
  ↓
┌─────────────────────────────────────┐
│ Payment Processed                   │
│ ├─ Status updated in real-time      │
│ ├─ CRM communication logged         │
│ └─ Portal balance updates           │
└─────────────────────────────────────┘
```

---

## 3. COMPONENT ARCHITECTURE

### 3.1 High-Level Structure

```
/portal
├── /src
│   ├── /components
│   │   ├── Dashboard.vue
│   │   ├── LoanList.vue
│   │   ├── LoanDetail.vue
│   │   ├── PaymentPortal.vue
│   │   ├── ScheduleView.vue
│   │   ├── PaymentHistory.vue
│   │   ├── MessageCenter.vue
│   │   ├── Settings.vue
│   │   └── /common
│   │       ├── Header.vue
│   │       ├── Navigation.vue
│   │       ├── Footer.vue
│   │       └── NotificationBell.vue
│   │
│   ├── /services
│   │   ├── AuthService.ts
│   │   ├── LoanService.ts
│   │   ├── PaymentService.ts
│   │   ├── ScheduleService.ts
│   │   ├── CommunicationService.ts
│   │   ├── NotificationService.ts
│   │   └── PreferenceService.ts
│   │
│   ├── /stores
│   │   ├── authStore.ts
│   │   ├── loanStore.ts
│   │   ├── paymentStore.ts
│   │   └── notificationStore.ts
│   │
│   ├── router.ts
│   └── App.vue
│
├── /public
│   ├── index.html
│   └── assets/
│
└── vite.config.ts
```

### 3.2 Component Details

#### Dashboard.vue
```typescript
<template>
  <div class="dashboard">
    <!-- Account Summary -->
    <AccountSummary :summary="accountSummary" />
    
    <!-- Loan Card Grid -->
    <div class="loan-cards">
      <LoanCard 
        v-for="loan in loans" 
        :key="loan.id" 
        :loan="loan"
        @click="selectLoan(loan.id)"
      />
    </div>
    
    <!-- Recent Messages -->
    <RecentMessages :messages="recentMessages" />
    
    <!-- Quick Actions -->
    <QuickActions />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useLoanStore } from '@/stores/loanStore'
import { useCommunicationStore } from '@/stores/communicationStore'

const loanStore = useLoanStore()
const commStore = useCommunicationStore()

const loans = ref([])
const accountSummary = ref({})
const recentMessages = ref([])

onMounted(async () => {
  // Load all loans for current user
  loans.value = await loanStore.getMyLoans()
  
  // Calculate summary
  accountSummary.value = {
    totalBalance: loans.value.reduce((sum, l) => sum + l.balance, 0),
    nextPaymentDate: getNextPaymentDue(loans.value),
    accountStatus: getPortfolioStatus(loans.value)
  }
  
  // Load recent messages from CRM
  recentMessages.value = await commStore.getRecentMessages(3)
})
</script>
```

#### PaymentPortal.vue
```typescript
<template>
  <div class="payment-portal">
    <div class="payment-wizard">
      <!-- Step 1: Select Loan -->
      <div v-if="step === 1" class="step">
        <h2>Select Loan</h2>
        <div class="loan-select">
          <div 
            v-for="loan in loans" 
            :key="loan.id"
            class="loan-option"
            @click="selectPaymentLoan(loan)"
          >
            {{ loan.description }} - Balance: ${{ loan.balance.toFixed(2) }}
          </div>
        </div>
      </div>
      
      <!-- Step 2: Payment Amount -->
      <div v-if="step === 2" class="step">
        <h2>Payment Amount</h2>
        <div class="payment-options">
          <div class="option">
            <input 
              type="radio" 
              v-model="paymentType" 
              value="regular"
            >
            <label>Regular Payment: ${{ selectedLoan.regular_payment }}</label>
          </div>
          <div class="option">
            <input 
              type="radio" 
              v-model="paymentType" 
              value="extra"
            >
            <label>Extra Payment</label>
            <input 
              v-if="paymentType === 'extra'"
              v-model.number="customAmount"
              type="number"
              placeholder="Enter amount"
              :min="1"
              :max="selectedLoan.balance"
            >
          </div>
          <div class="option">
            <input 
              type="radio" 
              v-model="paymentType" 
              value="payoff"
            >
            <label>Full Payoff: ${{ selectedLoan.payoff_amount }}</label>
          </div>
        </div>
        <p class="info">Amount Due: ${{ getPaymentAmount() }}</p>
      </div>
      
      <!-- Step 3: Payment Method -->
      <div v-if="step === 3" class="step">
        <h2>Payment Method</h2>
        <div class="payment-methods">
          <div class="method ach">
            <input 
              type="radio" 
              v-model="paymentMethod" 
              value="ach"
            >
            <label>Bank Account (ACH) - FREE</label>
            <p class="note">2-3 business days</p>
          </div>
          <div class="method debit">
            <input 
              type="radio" 
              v-model="paymentMethod" 
              value="debit"
            >
            <label>Debit Card - FREE</label>
            <p class="note">1 business day</p>
          </div>
          <div class="method credit">
            <input 
              type="radio" 
              v-model="paymentMethod" 
              value="credit"
            >
            <label>Credit Card - 2.5% fee</label>
            <p class="note">1 business day</p>
          </div>
        </div>
      </div>
      
      <!-- Step 4: Review & Confirm -->
      <div v-if="step === 4" class="step">
        <h2>Review Payment</h2>
        <div class="review-summary">
          <p><strong>Loan:</strong> {{ selectedLoan.description }}</p>
          <p><strong>Amount:</strong> ${{ getPaymentAmount() }}</p>
          <p><strong>Method:</strong> {{ paymentMethod }}</p>
          <p v-if="paymentType === 'extra'"><strong>Impact:</strong> Saves $X in interest</p>
        </div>
        <div class="action-buttons">
          <button @click="step--">Back</button>
          <button @click="submitPayment" class="primary">Confirm Payment</button>
        </div>
      </div>
      
      <!-- Navigation -->
      <div class="step-navigation">
        <button 
          @click="step--" 
          :disabled="step === 1"
        >← Back</button>
        <span>Step {{ step }} of 4</span>
        <button 
          @click="step++" 
          :disabled="step === 4 || !canProceed"
        >Next →</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePaymentStore } from '@/stores/paymentStore'

const paymentStore = usePaymentStore()
const step = ref(1)
const selectedLoan = ref(null)
const paymentType = ref('regular')
const customAmount = ref(0)
const paymentMethod = ref('ach')

const getPaymentAmount = () => {
  if (paymentType.value === 'regular') return selectedLoan.value.regular_payment
  if (paymentType.value === 'extra') return customAmount.value
  if (paymentType.value === 'payoff') return selectedLoan.value.payoff_amount
}

const submitPayment = async () => {
  const payment = {
    loan_id: selectedLoan.value.id,
    amount: getPaymentAmount(),
    method: paymentMethod.value,
    type: paymentType.value
  }
  
  const result = await paymentStore.submitPayment(payment)
  // Show confirmation
  alert(`Payment confirmed! Reference: ${result.reference_number}`)
  // Log in CRM
  await communicationStore.logPaymentActivity(payment)
}
</script>
```

#### ScheduleView.vue
```typescript
<template>
  <div class="schedule-view">
    <div class="schedule-header">
      <h2>Amortization Schedule</h2>
      <div class="export-buttons">
        <button @click="exportPDF">PDF</button>
        <button @click="exportCSV">CSV</button>
        <button @click="printSchedule">Print</button>
      </div>
    </div>
    
    <div class="filters">
      <label>Show:</label>
      <select v-model="viewMode">
        <option value="all">All Payments</option>
        <option value="upcoming">Upcoming (12 months)</option>
        <option value="past">Past Payments</option>
      </select>
    </div>
    
    <table class="schedule-table">
      <thead>
        <tr>
          <th>Payment #</th>
          <th>Payment Date</th>
          <th>Payment Amount</th>
          <th>Principal</th>
          <th>Interest</th>
          <th>Remaining Balance</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="payment in filteredSchedule" :key="payment.id" :class="payment.status">
          <td>{{ payment.number }}</td>
          <td>{{ formatDate(payment.date) }}</td>
          <td>${{ payment.amount.toFixed(2) }}</td>
          <td>${{ payment.principal.toFixed(2) }}</td>
          <td>${{ payment.interest.toFixed(2) }}</td>
          <td>${{ payment.balance.toFixed(2) }}</td>
          <td><span class="badge" :class="payment.status">{{ payment.status }}</span></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useScheduleStore } from '@/stores/scheduleStore'

const scheduleStore = useScheduleStore()
const loanId = route.params.loanId
const schedule = ref([])
const viewMode = ref('upcoming')

const filteredSchedule = computed(() => {
  if (viewMode.value === 'all') return schedule.value
  if (viewMode.value === 'upcoming') return schedule.value.filter(p => p.status !== 'PAID')
  if (viewMode.value === 'past') return schedule.value.filter(p => p.status === 'PAID')
})

onMounted(async () => {
  schedule.value = await scheduleStore.getSchedule(loanId)
})

const exportPDF = () => {
  // Use jsPDF or similar
  generatePDFReport(schedule.value)
}

const exportCSV = () => {
  // Generate CSV
  const csv = convertToCSV(schedule.value)
  downloadFile(csv, 'schedule.csv')
}
</script>
```

---

## 4. DATA MODEL & API

### 4.1 Portal User Entity

```php
class PortalUser {
    private int $user_id;
    private int $debtor_no;        // FK to FA debtors
    private string $email;
    private string $phone;
    private string $password_hash;
    private bool $two_factor_enabled;
    private string $two_factor_type;  // sms, email
    private bool $email_verified;
    private bool $phone_verified;
    private array $notification_preferences;
    private \DateTimeImmutable $last_login_at;
    private \DateTimeImmutable $created_at;
    private \DateTimeImmutable $updated_at;
}
```

### 4.2 API Endpoints

```
GET /api/v1/portal/me
──────────────────────
Response: 200 OK
{
  "user_id": 1,
  "email": "john@example.com",
  "phone": "555-555-5555",
  "two_factor_enabled": true,
  "notification_preferences": {
    "payment_reminders": true,
    "payment_confirmation": true,
    "rate_changes": true,
    "delinquency_notices": true,
    "marketing_offers": false
  }
}

────────────────────────

GET /api/v1/portal/loans
────────────────────────
Response: 200 OK
{
  "loans": [
    {
      "loan_id": 1,
      "loan_number": "LN-2025-001",
      "type": "auto",
      "original_amount": 25000.00,
      "current_balance": 18234.56,
      "regular_payment": 483.32,
      "payment_due_date": "2026-05-28",
      "status": "CURRENT",
      "interest_rate": 6.50,
      "term_months": 60,
      "months_remaining": 42,
      "next_payment": {
        "amount": 483.32,
        "due_date": "2026-05-28",
        "days_until_due": 15
      }
    }
  ]
}

────────────────────────

GET /api/v1/portal/loans/:loan_id/dashboard
─────────────────────────────────────────────
Response: 200 OK
{
  "loan": { ... }, // Full loan details
  "account_status": {
    "status": "CURRENT",
    "days_late": 0,
    "next_payment_due_date": "2026-05-28",
    "last_payment_date": "2026-04-28",
    "last_payment_amount": 483.32
  },
  "payoff_quote": {
    "amount": 18234.56,
    "date_if_regular": "2030-08-28",
    "date_if_paid_early": "2027-12-28",
    "interest_if_paid_early": 4500.00,
    "interest_saved": 2100.00
  },
  "recent_activity": [
    {
      "date": "2026-04-28",
      "type": "payment",
      "amount": 483.32,
      "balance_after": 18234.56
    }
  ]
}

────────────────────────

GET /api/v1/portal/loans/:loan_id/schedule
────────────────────────────────────────────
Query Parameters:
  - view_mode: all, upcoming, past
  - page: 1
  - per_page: 20

Response: 200 OK
{
  "schedule": [
    {
      "id": 1,
      "number": 1,
      "date": "2025-05-28",
      "payment": 483.32,
      "principal": 383.32,
      "interest": 100.00,
      "balance": 24616.68,
      "status": "PAID",
      "paid_date": "2025-05-25"
    }
  ],
  "pagination": { }
}

────────────────────────

POST /api/v1/portal/payments
────────────────────────────
Request:
{
  "loan_id": 1,
  "amount": 483.32,
  "payment_type": "regular",  // regular, extra, payoff
  "payment_method": "ach",    // ach, debit, credit, wire
  "account_token": "tok_visa_xyz123"  // For card/ach
}

Response: 201 Created
{
  "payment_id": 5001,
  "reference_number": "PAY-2026-000001",
  "amount": 483.32,
  "status": "SUBMITTED",
  "expected_posting_date": "2026-05-01",
  "receipt_url": "https://portal.example.com/receipts/PAY-2026-000001"
}

────────────────────────

GET /api/v1/portal/loans/:loan_id/payoff
──────────────────────────────────────────
Response: 200 OK
{
  "current_balance": 18234.56,
  "payoff_amount": 18245.23,
  "payoff_date": "2026-04-29",
  "interest_if_paid_today": 10.67,
  "interest_saved_vs_schedule": 2100.00,
  "prepayment_penalty": 0.00,
  "total_cost": 18245.23
}

────────────────────────

GET /api/v1/portal/statements
──────────────────────────────
Query Parameters:
  - month: 4
  - year: 2026

Response: 200 OK
{
  "statements": [
    {
      "document_id": 1,
      "month": 4,
      "year": 2026,
      "url": "https://storage.example.com/statements/2026-04.pdf",
      "created_at": "2026-05-05"
    }
  ]
}

────────────────────────

GET /api/v1/portal/communications
──────────────────────────────────
Response: 200 OK (from CRM)
{
  "messages": [
    {
      "message_id": 1,
      "from": "customer_service@example.com",
      "subject": "Your Loan Update",
      "message": "...",
      "created_at": "2026-04-28T10:00:00Z",
      "is_read": false
    }
  ]
}

────────────────────────

PUT /api/v1/portal/preferences
───────────────────────────────
Request:
{
  "notification_preferences": {
    "payment_reminders": true,
    "payment_confirmation": true,
    "rate_changes": false,
    "marketing": false,
    "preferred_channel": "email"  // email, sms, both
  }
}

Response: 200 OK
```

---

## 5. CRM INTEGRATION

### 5.1 Communication Logging

```php
// Every portal activity logged to CRM as communication
$this->crm->logCommunication([
    'debtor_no' => $user->getDebtorNo(),
    'communication_type' => 'portal_payment',
    'subject' => "Payment of $483.32 submitted via portal",
    'message' => "Payment ID: PAY-2026-000001, Method: ACH, Reference: {$refNum}",
    'created_by' => 'CUSTOMER',
    'external_reference' => $paymentId,
    'linked_date' => $paymentDate
]);
```

### 5.2 Message Center Integration

```php
// Fetch messages from CRM for portal display
$messages = $this->crm->getCustomerCommunications(
    debtor_no: $user->getDebtorNo(),
    limit: 50,
    orderBy: 'created_at DESC'
);

// Portal shows: support tickets, alerts, notices, promotional messages
// Respect CRM opt-out preferences
```

### 5.3 Notification Preferences

```php
// Portal preferences sync to CRM
$this->crm->updateCustomerCommunicationPreferences([
    'debtor_no' => $debtor_no,
    'email_opt_in' => $preferences['email'],
    'sms_opt_in' => $preferences['sms'],
    'marketing_opt_in' => $preferences['marketing'],
    'communication_language' => $preferences['language'],
    'preferred_contact_time' => $preferences['contact_time']
]);
```

---

## 6. SECURITY & COMPLIANCE

### 6.1 Authentication
- FrontAccounting session integration
- Two-factor authentication (SMS/Email)
- Session timeout (15 minutes)
- Password requirements (12+ chars, complexity)

### 6.2 PCI DSS Compliance
- Payment card data only handled by tokenized provider (Stripe, Adyen)
- No card data stored in local database
- SSL/TLS encryption all connections
- PCI annual certification

### 6.3 Data Privacy
- GDPR compliance (EU customers)
- CCPA compliance (California customers)
- Data retention policies
- Secure document disposal

---

## 7. PERFORMANCE TARGETS

- Portal load: < 2 seconds
- API response: < 500ms (95th percentile)
- Payment submission: < 1 second
- Concurrent users: 1000+
- Uptime: 99.5%

---

## 8. IMPLEMENTATION CHECKLIST

Phase 1: Authentication & Dashboard (2 weeks)
- [ ] Portal user registration/login
- [ ] FA integration for authentication
- [ ] Dashboard component
- [ ] Loan list view
- [ ] Basic styling

Phase 2: Loan Management (2 weeks)
- [ ] Loan detail page
- [ ] Schedule view
- [ ] Payoff calculations
- [ ] Payment history
- [ ] Export functionality

Phase 3: Payments (2 weeks)
- [ ] Payment portal wizard
- [ ] Multiple payment methods
- [ ] Payment processing integration
- [ ] Receipt generation
- [ ] Confirmation emails

Phase 4: CRM Integration (1 week)
- [ ] Communication logging
- [ ] Message center
- [ ] Notification preferences
- [ ] Activity tracking

Phase 5: Polish & Deployment (1 week)
- [ ] Performance optimization
- [ ] Security audit
- [ ] UAT with customers
- [ ] Production deployment

---

**Status**: Specification complete, ready for development  
**Estimated Timeline**: 10 weeks (2 frontend devs + 1 backend dev)  
**Next Step**: Create component skeleton with Vue 3 + TypeScript

