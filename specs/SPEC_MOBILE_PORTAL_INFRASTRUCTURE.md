# SPEC-MOBILE: Mobile & Portal Infrastructure Specification

**Version**: 1.0 | **Date**: April 28, 2026 | **Status**: Ready for Implementation

---

## 1. OVERVIEW

The Mobile & Portal Infrastructure provides borrowers with self-service capabilities and collections team with field tools. Includes borrower portal, mobile apps, document signing, notifications, and offline capabilities.

### Key Features
- Borrower self-service portal
- Mobile app (iOS/Android) for borrowers
- Collector field mobile app
- E-signature integration
- Push notifications & SMS alerts
- Offline payment processing
- Real-time account access
- Document upload & management
- Payment arrangements via portal
- Automated reminders

### Success Metrics
- Portal adoption: 70%+ of borrowers
- Mobile app rating: 4.5+ stars
- Payment portal usage: 60%+ of payments
- Mobile app availability: 99.9%
- Push notification open rate: 35%+

---

## 2. BORROWER PORTAL

### 2.1 Portal Architecture

```
Frontend Stack:
├─ React.js (UI framework)
├─ TypeScript (type safety)
├─ Redux (state management)
├─ React Router (navigation)
├─ Axios (API client)
└─ Material-UI (component library)

Authentication:
├─ OAuth 2.0 Integration
├─ Multi-factor authentication (SMS/Email)
├─ Session management
├─ Secure token refresh
└─ Rate limiting per user

Security:
├─ HTTPS only
├─ Content Security Policy (CSP)
├─ CSRF protection
├─ XSS prevention
├─ SQL injection prevention
└─ PII encryption (sensitive fields)
```

### 2.2 Portal Features

#### Dashboard View

```
BORROWER PORTAL - My Loans
═══════════════════════════════════════════════════

QUICK SUMMARY
┌────────────────────────────────────────────────┐
│ Account Status: IN GOOD STANDING ✓             │
│ Last Payment: Received 4/15/2026               │
│ Next Payment: Due 5/15/2026 ($500.00)          │
│ Days Until Due: 17                             │
│ Total Paid: $2,500 (50%)                       │
│ Remaining Balance: $25,000                     │
└────────────────────────────────────────────────┘

YOUR LOANS (Active: 1, Paid Off: 0)
┌──────────────────────────────────────────────────┐
│ LOAN: LN-2026-001 - Personal Loan               │
│ Amount: $50,000                                 │
│ Rate: 8.5%                                      │
│ Term: 36 months (24 remaining)                  │
│ Balance: $25,000                                │
│ Monthly Payment: $500.00                        │
│                                                │
│ Status: CURRENT ✓                              │
│ Performance: On time (100%)                    │
│                                                │
│ [View Details] [Make Payment] [Print]          │
└──────────────────────────────────────────────────┘

QUICK ACTIONS
┌─────────────────┬──────────────────┬────────────┐
│ Make Payment    │ Download Docs    │ Contact   │
│     [→]         │       [→]        │   [→]    │
└─────────────────┴──────────────────┴────────────┘

RECENT ACTIVITY
┌──────────────────────────────────────────────────┐
│ 04/15/2026 │ Payment Received    │ $500.00     │
│ 04/01/2026 │ Monthly Interest    │ $354.17     │
│ 03/15/2026 │ Payment Received    │ $500.00     │
└──────────────────────────────────────────────────┘

[View All] [Download Statement]
```

#### Account Management

```
VIEW LOAN DETAILS
├─ Loan Information
│  ├─ Loan Number
│  ├─ Original Amount
│  ├─ Current Balance
│  ├─ Interest Rate
│  ├─ Term (months)
│  ├─ Start Date
│  └─ Maturity Date
│
├─ Payment Information
│  ├─ Monthly Payment
│  ├─ Payment Due Date
│  ├─ Principal Paid
│  ├─ Interest Paid
│  ├─ Fees Paid
│  └─ Total Payments Made
│
├─ Amortization Schedule
│  └─ View/Download full amortization schedule
│      (Excel or PDF)
│
└─ Loan Documents
   ├─ Original Agreement
   ├─ Promissory Note
   ├─ TILA Disclosure
   ├─ Payment Statements
   └─ Tax Forms (if applicable)
```

#### Payment Portal

```
MAKE A PAYMENT
═════════════════════════════════════════════════

Payment Amount: [$$$]  [Suggested: $500.00]

Payment Type:
  ○ One-time payment
  ○ Set up automatic recurring payment

Payment Method:
  ○ Bank Account (ACH) [Recommended - Free]
  ○ Debit Card [Fee: $0.00]
  ○ Credit Card [Fee: 2.95%]
  ○ Wire Transfer [Fee: $25.00]

If Bank Account Selected:
┌──────────────────────────────────────────┐
│ Account Holder: [Your Name - Pre-filled] │
│ Bank Name: [Select...]                   │
│ Account Type: ○ Checking ○ Savings      │
│ Routing Number: [Input]                  │
│ Account Number: [Input / Link existing]  │
│                                          │
│ ☑ Save for future payments               │
│ ☑ Make this my default payment method    │
└──────────────────────────────────────────┘

Payment Date:
  ○ Process immediately (available in 1-2 days)
  ○ Schedule for specific date: [Select]

Confirmation:
┌──────────────────────────────────────────┐
│ You will pay: $500.00                    │
│ From: [Last 4 digits of account]         │
│ On: April 28, 2026                       │
│ Reference: LN-2026-001                   │
│                                          │
│ ☑ I authorize this payment               │
└──────────────────────────────────────────┘

[AUTHORIZE PAYMENT] [CANCEL]

────────────────────────────────────────────

PAYMENT CONFIRMATION
═════════════════════════════════════════════════

✓ Payment Authorized!

Confirmation Number: PAY-20260428-001
Amount: $500.00
Method: ACH Bank Transfer
Expected Arrival: April 30, 2026

Payment Details:
├─ Confirmation sent to email
├─ Downloadable receipt
└─ Check status anytime in "My Payments"

[Download Receipt] [Done]
```

#### Payment Arrangements (Borrower View)

```
MANAGE ARRANGEMENTS
═════════════════════════════════════════════════

Current Arrangements:
┌──────────────────────────────────────────────┐
│ No active payment arrangements               │
│                                              │
│ If struggling with payments,                 │
│ [Contact Collections Team]                   │
└──────────────────────────────────────────────┘

Previous Arrangements History:
├─ 2025-08: 3-month arrangement (completed)
│  └─ Status: ✓ Successfully completed
└─ 2024-12: 2-month arrangement (completed)
   └─ Status: ✓ Successfully completed
```

#### E-Signature & Agreements

```
DOCUMENTS & AGREEMENTS
═════════════════════════════════════════════════

Pending Your Signature:
┌──────────────────────┐
│ Loan Amendment       │
│ Interest Rate Change │
│                      │
│ [Review & Sign]      │
└──────────────────────┘

Signed Documents:
├─ Original Loan Agreement (04/15/2026)
├─ Payment Arrangement (08/12/2025)
└─ TILA Disclosure (04/15/2026)

[View All Documents]
```

#### Notifications & Preferences

```
NOTIFICATION SETTINGS
═════════════════════════════════════════════════

Email Notifications:
  ☑ Payment due reminder (5 days before)
  ☑ Payment received confirmation
  ☑ Account statements
  ☑ Important account notices
  ☐ Promotional offers
  ☐ Newsletter

SMS Notifications:
  ☑ Payment due reminder (2 days before)
  ☑ Payment received confirmation
  ☐ Account alerts
  ☐ Promotions

Push (App):
  ☑ Payment due reminder
  ☑ Payment received confirmation
  ☑ Account alerts
  ☐ Promotional offers

[Save Preferences]
```

---

## 3. MOBILE APPLICATION

### 3.1 Mobile App Architecture

```
Platform: React Native (Cross-platform iOS/Android)
Frontend:
├─ React Native (UI)
├─ Redux (State Management)
├─ Redux Persist (Offline State)
├─ Async Storage (Local Data)
├─ React Navigation (Navigation)
└─ React Query (Server State)

Backend:
└─ RESTful API (same as portal)

Device Features:
├─ Biometric authentication
├─ Camera (for document/check deposit)
├─ Push notifications
├─ Offline capability (LocalStorage)
├─ App analytics
└─ Crash reporting
```

### 3.2 Borrower Mobile App Features

#### Main Features

```
Tab Navigation:
├─ Home
├─ Payments
├─ Documents
├─ Messages
└─ Profile

HOME TAB:
├─ Account Balance Widget
├─ Next Payment Due (with countdown)
├─ Quick Action Buttons
│  ├─ Make Payment (immediate redirect)
│  ├─ View Statement
│  └─ Contact Support
├─ Recent Transactions
└─ Important Notices

PAYMENTS TAB:
├─ Make New Payment (form)
├─ Payment History (with filters)
├─ Scheduled Payments
├─ Saved Payment Methods
├─ Payment Status Tracking
└─ Alternative Payment Methods
   ├─ Mobile wallet (Apple Pay, Google Pay)
   ├─ Instant bank transfer
   └─ Digital wallet

DOCUMENTS TAB:
├─ Download Statement
├─ View Amortization Schedule
├─ Original Loan Documents
├─ Tax Documents (Form 1098)
├─ Upload Documents (payments proof, etc.)
└─ Document Archive

MESSAGES TAB:
├─ Inbox (messages from company)
├─ Read/Unread status
├─ Message center replies
├─ Contact Support
└─ FAQ

PROFILE TAB:
├─ Account Information (view-only)
├─ Contact Information (edit)
├─ Notification Preferences
├─ Saved Payment Methods (manage)
├─ Biometric Settings
├─ App Settings
└─ Logout
```

#### Offline Capability

```
Offline Feature Support:
├─ View loan balance (cached)
├─ View payment history (cached, last 30 days)
├─ View amortization schedule (cached)
├─ View statements (cached)
├─ Scheduled payment queue
└─ Queued messages to support

Sync When Online:
├─ Upload pending payments
├─ Sync notification preferences
├─ Sync balance & transactions
├─ Download new statements
├─ Download new messages
└─ Update cached data
```

### 3.3 Collector Mobile App

#### Features

```
Tab Navigation:
├─ Tasks
├─ Accounts
├─ Activities Log
├─ Performance
└─ Settings

TASKS TAB:
├─ Today's Tasks
│  ├─ Task List (sorted by priority)
│  ├─ Task Details (borrower info, phone, address)
│  ├─ Action Menu
│  │  ├─ Call (dial directly)
│  │  ├─ Log Activity
│  │  ├─ Propose Arrangement
│  │  ├─ Send Message
│  │  └─ Complete Task
│  └─ Map View (borrower location)
│
├─ Task History
├─ Task Search
└─ Filter (by priority, age, type)

ACCOUNTS TAB:
├─ My Assigned Accounts
├─ Account Search
├─ Account Details
│  ├─ Borrower Information
│  ├─ Loan Details
│  ├─ Payment History
│  ├─ Delinquency Status
│  ├─ Collection Notes
│  └─ Contact History

ACTIVITIES LOG TAB:
├─ Log Collection Call
├─ Log SMS Sent
├─ Log Email Sent
├─ Log Message Left
├─ Log Payment Arrangement
├─ Payment Received (notify)

PERFORMANCE TAB:
├─ Personal Statistics (MTD)
│  ├─ Tasks Completed
│  ├─ Collections Amount
│  ├─ Average Per Task
│  ├─ Contact Attempts
│  └─ Collection Rate
├─ Leaderboard (vs team)
└─ Goals & Progress

SETTINGS TAB:
├─ Work Hours
├─ Contact Preferences
├─ Notification Settings
└─ Logout
```

---

## 4. API ENDPOINTS FOR MOBILE

### 4.1 Authentication

```
POST /api/v1/mobile/auth/login
──────────────────────────────
Request:
{
  "email": "john@example.com",
  "password": "secure_password",
  "device_id": "uuid",
  "device_type": "ios|android"
}

Response: 200 OK
{
  "access_token": "eyJhbGc...",
  "refresh_token": "eyJhbGc...",
  "expires_in": 3600,
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "borrower|collector"
  }
}

─────────────────────────────

POST /api/v1/mobile/auth/biometric
──────────────────────────────────
Request:
{
  "device_id": "uuid",
  "biometric_token": "face_recognition_data"
}

Response: 200 OK
{
  "access_token": "eyJhbGc...",
  "refresh_token": "eyJhbGc...",
  "expires_in": 3600
}
```

### 4.2 Borrower Endpoints

```
GET /api/v1/mobile/borrower/dashboard
─────────────────────────────────────
Response: 200 OK
{
  "loans": [
    {
      "loan_id": 100,
      "loan_number": "LN-2026-001",
      "balance": 25000.00,
      "next_payment_date": "2026-05-15",
      "next_payment_amount": 500.00,
      "days_until_due": 17,
      "status": "current",
      "progress_pct": 50
    }
  ],
  "next_action": {
    "type": "payment_due",
    "date": "2026-05-15",
    "amount": 500.00
  }
}

─────────────────────────────

GET /api/v1/mobile/borrower/payment-history
──────────────────────────────────────────
Query Parameters:
  - loan_id: 100
  - limit: 10
  - offset: 0

Response: 200 OK
{
  "payments": [
    {
      "payment_id": 1001,
      "date": "2026-04-15",
      "amount": 500.00,
      "method": "ach",
      "status": "settled",
      "principal": 420.00,
      "interest": 80.00
    }
  ]
}

─────────────────────────────

POST /api/v1/mobile/borrower/payments
─────────────────────────────────────
Request:
{
  "loan_id": 100,
  "amount": 500.00,
  "payment_date": "2026-04-28",
  "payment_method": "ach",
  "bank_account_id": "ba-123"  // Pre-saved
}

Response: 201 Created
{
  "payment_id": 1002,
  "status": "pending",
  "confirmation_number": "PAY-20260428-001",
  "expected_settlement": "2026-04-30"
}
```

### 4.3 Collector Endpoints

```
GET /api/v1/mobile/collector/tasks
──────────────────────────────────
Query Parameters:
  - date: 2026-04-28
  - priority: all|high|medium|low
  - status: open|in_progress
  - limit: 20
  - offset: 0

Response: 200 OK
{
  "tasks": [
    {
      "task_id": 1001,
      "loan_id": 100,
      "loan_number": "LN-2026-001",
      "borrower_name": "John Doe",
      "phone": "555-555-5555",
      "amount_due": 2450.00,
      "days_late": 45,
      "priority": "high",
      "last_contact": "2026-04-25T14:30:00Z",
      "contact_attempts": 3,
      "contact_history": [
        { "date": "2026-04-25", "method": "call", "result": "voicemail" }
      ]
    }
  ],
  "task_count": 12,
  "completed_today": 5
}

─────────────────────────────

POST /api/v1/mobile/collector/tasks/:task_id/activity
─────────────────────────────────────────────────────
Request:
{
  "activity_type": "called",
  "contact_method": "phone",
  "notes": "Spoke with borrower, promised payment by 5/1",
  "promised_date": "2026-05-01",
  "promised_amount": 2450.00,
  "call_duration_seconds": 240,
  "recording_url": "s3://recordings/task-1001.mp3"  // Optional
}

Response: 201 Created
{
  "activity_id": 5001,
  "task_id": 1001,
  "status": "recorded",
  "timestamp": "2026-04-28T14:30:00Z"
}

─────────────────────────────

POST /api/v1/mobile/collector/payment-arrangements
───────────────────────────────────────────────────
Request:
{
  "task_id": 1001,
  "loan_id": 100,
  "payment_schedule": [
    { "date": "2026-05-15", "amount": 1000.00 },
    { "date": "2026-06-15", "amount": 1225.00 },
    { "date": "2026-07-15", "amount": 1225.00 }
  ]
}

Response: 201 Created
{
  "arrangement_id": 801,
  "status": "proposed",
  "agreement_url": "https://portal.example.com/arrangements/801/agreement.pdf",
  "borrower_sms_sent": true,
  "message": "Payment arrangement proposed, check email for details"
}
```

---

## 5. PUSH NOTIFICATIONS & ALERTS

### 5.1 Notification Service

```php
class NotificationService {
    public function sendNotification(
        User $recipient,
        string $type,  // payment_due, payment_received, account_alert, task_assigned
        array $data
    ): void {
        // Send via configured channels
        $this->sendPush($recipient, $type, $data);
        $this->sendSMS($recipient, $type, $data);
        $this->sendEmail($recipient, $type, $data);
    }
}
```

### 5.2 Notification Types

```
Borrower Notifications:
├─ Payment Due (5 days before, 2 days before, day of)
├─ Payment Received (confirmation)
├─ Account Status Change (delinquency, charge-off)
├─ Important Notice (regulatory, rate change)
├─ Settlement Offer (collections)
└─ Account Update Required

Collector Notifications:
├─ Task Assigned
├─ Task Escalated
├─ Collection Call Reminder
├─ Payment Arrangement Update
├─ Performance Alert (goal achievement)
└─ System Alert (important event)

Content Examples:
├─ "Payment due in 2 days: $500 on 05/15. [Pay Now]"
├─ "Your payment of $500 was received. Thank you!"
├─ "We are unable to reach you. Call 800-NUMBERS."
└─ "Settlement offer: Pay $2,000 to settle for $2,450"
```

### 5.3 Push Notification Architecture

```
Firebase Cloud Messaging (FCM):
├─ APNs (for iOS)
├─ FCM Direct (for Android)
├─ Fallback to SMS if app not installed

Database:
├─ user_devices table (device tokens)
├─ notification_log table (tracking)
├─ notification_preferences table

Scheduling:
├─ Real-time (immediate)
├─ Scheduled (future date/time)
├─ Recurring (daily/weekly)
└─ Quiet hours respected (user preference)

Analytics:
├─ Send count
├─ Delivery rate
├─ Open rate
├─ Click-through rate
└─ Unsubscribe rate
```

---

## 6. SECURITY & COMPLIANCE

### 6.1 Mobile Security

```
Authentication:
├─ Biometric (Face ID, Touch ID, Android biometric)
├─ PIN Code (4-digit fallback)
├─ Session management (auto-logout after 15 min)
└─ Token refresh mechanism

Data Encryption:
├─ TLS 1.2+ for all API calls (HTTPS)
├─ AES-256 for local data storage
├─ Sensitive field encryption (PII)
└─ Token encryption in storage

App Security:
├─ Certificate pinning (HTTPS)
├─ Code obfuscation
├─ Jailbreak/root detection
├─ Tamper detection
├─ App attestation (iOS/Android)
└─ Rate limiting (login attempts)

Data Privacy:
├─ Minimal data cached locally (essential only)
├─ PII masked in logs
├─ CCPA/GDPR compliance
├─ User data deletion capability
└─ Privacy policy in app
```

### 6.2 Compliance

```
Regulatory Requirements:
├─ GLBA (Gramm-Leach-Bliley) compliance
├─ FCPA § 501(b) (Safeguards Rule)
├─ ECOA (Equal Credit Opportunity Act)
├─ TCPA (if SMS notifications)
├─ GDPR (if EU borrowers)
├─ SOC 2 Type II compliance
└─ PCI DSS (if handling cards)

Implementation:
├─ Encryption of sensitive data in transit & rest
├─ Access controls (role-based)
├─ Audit logging
├─ Incident response plan
├─ Regular security assessments
├─ Annual compliance audit
└─ Security training for staff
```

---

## 7. DEPLOYMENT & DISTRIBUTION

### 7.1 App Store Distribution

```
iOS Distribution:
├─ Apple App Store
├─ TestFlight (beta)
├─ Internal Enterprise distribution (collectors)
└─ Version management (auto-update prompts)

Android Distribution:
├─ Google Play Store
├─ Google Play Beta
├─ Internal Firebase distribution (collectors)
└─ Version management (auto-update)

Release Process:
1. Build release version
2. Run security scans
3. Submit to app store
4. Wait for review (3-7 days)
5. Release when approved
6. Monitor crash reports
7. Push bug fixes if needed
```

### 7.2 Versioning & Updates

```
Version Numbering: X.Y.Z
├─ X: Major features (requires app store update)
├─ Y: Minor features (may not require app store)
└─ Z: Bug fixes

Update Strategy:
├─ Forced updates (security-critical)
├─ Recommended updates (new features)
├─ Optional updates (nice-to-haves)
└─ Sunset old versions (< 2 versions back)
```

---

## 8. IMPLEMENTATION CHECKLIST

Phase 1: Portal Foundation (3 weeks)
- [ ] React portal setup
- [ ] Authentication system
- [ ] Dashboard UI
- [ ] API integration

Phase 2: Portal Features (2 weeks)
- [ ] Payment processing
- [ ] Statement generation
- [ ] Document management
- [ ] Notifications

Phase 3: Mobile App (3 weeks)
- [ ] React Native app setup
- [ ] Authentication
- [ ] Core features (borrower)
- [ ] Offline capability

Phase 4: Collector Mobile (2 weeks)
- [ ] Task management
- [ ] Activity logging
- [ ] Collection tools
- [ ] Performance tracking

Phase 5: Testing & Release (2 weeks)
- [ ] Security testing
- [ ] Performance optimization
- [ ] App store submission
- [ ] Production launch

---

**Status**: Specification complete, ready for development  
**Estimated Timeline**: 12 weeks (with 4 developers)  
**Next Step**: Portal backend API implementation

