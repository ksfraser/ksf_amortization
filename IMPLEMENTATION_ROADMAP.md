# Amortization Module: Implementation Roadmap & Specifications

**Date**: April 28, 2026  
**Status**: Feature specifications ready for development  
**Integration**: Full ksf_CRM integration planned

---

## PHASE 1: CRITICAL PATH (8-12 weeks)

### 1.1 Loan Origination Workflow
**Priority**: CRITICAL | **Effort**: 100-120 hours | **Team**: 2-3 devs

#### Specification: [SPEC_LOAN_ORIGINATION.md](./specs/SPEC_LOAN_ORIGINATION.md)
- Application intake & validation
- Credit check integration (mock API ready)
- Income verification workflow
- Document management (S3-ready)
- Underwriting checklist & approval
- TILA disclosure generation
- e-Signature workflow (DocuSign integration)

#### Deliverables
- [ ] LoanOriginationService (fully implemented)
- [ ] OriginationController with REST endpoints
- [ ] Origination workflow event system
- [ ] Application staging/validation
- [ ] Credit check API integration
- [ ] Document storage service
- [ ] E-signature webhook handler

#### Database
- [ ] `0_ksf_loan_applications` table
- [ ] `0_ksf_origination_events` audit table
- [ ] `0_ksf_loan_documents` table

#### API Endpoints
```
POST    /api/v1/origination/applications     - Submit application
GET     /api/v1/origination/applications/:id - Check status
POST    /api/v1/origination/documents        - Upload document
POST    /api/v1/origination/sign             - E-signature webhook
GET     /api/v1/origination/timeline         - Application timeline
```

---

### 1.2 Customer Portal (Integrated with CRM)
**Priority**: HIGH | **Effort**: 50-70 hours | **Team**: 1-2 devs

#### Specification: [SPEC_CUSTOMER_PORTAL.md](./specs/SPEC_CUSTOMER_PORTAL.md)
- Login via FrontAccounting/CRM auth
- Dashboard (balance, next payment, account health)
- Payment portal (online payments, scheduling)
- Amortization schedule view
- Payment history & statements
- Payoff quote & early payoff scenarios
- Delinquency/notice viewing
- Message center (CRM integration)
- Preference management

#### Deliverables
- [ ] CustomerPortalController
- [ ] Portal Vue 3 components (30+ components)
- [ ] PaymentProcessingService
- [ ] ScheduleExportService (PDF, CSV)
- [ ] DisclsureService (TILA, rate changes, etc.)
- [ ] CRM communication logging

#### API Endpoints
```
GET     /api/v1/portal/me                    - Current user profile
GET     /api/v1/portal/loans                 - My loans
GET     /api/v1/portal/loans/:id/dashboard   - Loan dashboard
GET     /api/v1/portal/loans/:id/schedule    - Amortization schedule
POST    /api/v1/portal/loans/:id/payoff      - Calculate payoff
POST    /api/v1/payments                     - Submit payment
GET     /api/v1/payments/history             - Payment history
GET     /api/v1/statements                   - Download statements
POST    /api/v1/communications/message       - Send message
```

#### CRM Integration Points
- Use CRM customer entity
- Log all portal activities as communications
- Track portal login as touchpoint
- Send notifications via CRM SMS/Email service

---

### 1.3 Collections Management Workflow
**Priority**: HIGH | **Effort**: 80-100 hours | **Team**: 2-3 devs

#### Specification: [SPEC_COLLECTIONS_MANAGEMENT.md](./specs/SPEC_COLLECTIONS_MANAGEMENT.md)
- Delinquency dashboard & task list
- Collection letter workflow (automated generation)
- Payment arrangement negotiation tracker
- Collector assignment & workload
- Call tracking & dialer integration hooks
- FDCPA compliance checking
- Settlement & charge-off workflows
- Collection notes & activity log
- CRM integration (collections team dashboard)

#### Deliverables
- [ ] CollectionsService
- [ ] DelinquencyTaskManager
- [ ] LetterTemplateEngine
- [ ] PaymentArrangementService
- [ ] FDCPAComplianceChecker
- [ ] CollectorWorkloadBalancer
- [ ] SettlementCalculator

#### API Endpoints
```
GET     /api/v1/collections/delinquent       - Delinquent accounts
GET     /api/v1/collections/tasks            - Collector tasks
POST    /api/v1/collections/tasks/:id/close  - Complete task
POST    /api/v1/collections/letters          - Generate letter
POST    /api/v1/collections/arrangement      - Create payment arrangement
GET     /api/v1/collections/arrangement/:id  - Arrangement details
POST    /api/v1/collections/settlement       - Create settlement offer
POST    /api/v1/collections/charge-off       - Recommend charge-off
```

#### CRM Integration Points
- Create opportunities for each collection task
- Log all activities as communications
- Assign to CRM users/territories
- Use CRM contact preferences for communication

---

## PHASE 2: MARKET EXPANSION (4-8 weeks)

### 2.1 Floating Rate Support
**Priority**: MEDIUM | **Effort**: 40-60 hours | **Team**: 1-2 devs

#### Specification: [SPEC_FLOATING_RATES.md](./specs/SPEC_FLOATING_RATES.md)
- Rate index configuration (Prime, LIBOR, SOFR, etc.)
- External rate feed integration
- Cap/floor settings
- Margin management
- Automated rate adjustment calculation
- Rate change notification workflow
- Disclosure generation (TILA)
- Rate adjustment history & audit

#### Deliverables
- [ ] FloatingRateStrategy
- [ ] RateIndexService
- [ ] ExternalRateFeedAdapter (for Fed, ECB, etc.)
- [ ] RateAdjustmentScheduler
- [ ] RateChangeNotificationService
- [ ] FloatingRateController

#### API Endpoints
```
GET     /api/v1/rates/indices                - Available indices
GET     /api/v1/rates/current                - Current rate feeds
POST    /api/v1/loans/:id/floating-config    - Configure floating rate
GET     /api/v1/loans/:id/rate-history       - Rate adjustment history
POST    /api/v1/loans/:id/recalculate        - Force recalc on rate change
```

---

### 2.2 SMS & Email Notification System (CRM Integration)
**Priority**: MEDIUM | **Effort**: 30-50 hours | **Team**: 1-2 devs

#### Specification: [SPEC_NOTIFICATIONS.md](./specs/SPEC_NOTIFICATIONS.md)
- SMS provider integration (Twilio/AWS SNS primary options)
- Outbound email provider integration (SendGrid/AWS SES)
- Message template engine
- Notification trigger system (payment due, rate change, delinquency, etc.)
- Opt-in/opt-out management (TCPA compliance)
- Delivery tracking & retry logic
- Notification history & audit

#### Deliverables
- [ ] SMSGatewayService (with Twilio adapter)
- [ ] EmailGatewayService (with SendGrid adapter)
- [ ] MessageTemplateEngine
- [ ] NotificationTriggerService
- [ ] NotificationPreferenceService
- [ ] DeliveryTrackingService
- [ ] TwilioWebhookController (SMS delivery status)

#### API Endpoints
```
GET     /api/v1/notifications/preferences    - Notification settings
PUT     /api/v1/notifications/preferences    - Update preferences
GET     /api/v1/notifications/history        - Notification history
POST    /api/v1/notifications/test-sms       - Send test SMS
POST    /api/v1/notifications/test-email     - Send test email
```

#### CRM Integration Points
- Use CRM contact phone/email
- Respect CRM communication preferences
- Log all notifications as communications
- Use CRM template system

---

## PHASE 3: INVESTOR & COMPLIANCE (6-12 weeks)

### 3.1 Investor/Loan-Level Reporting
**Priority**: MEDIUM | **Effort**: 80-120 hours | **Team**: 2-3 devs

#### Specification: [SPEC_INVESTOR_REPORTING.md](./specs/SPEC_INVESTOR_REPORTING.md)
- Loan-level detail export (all fields)
- Performance summary reporting
- Delinquency detail reports
- Cash flow projections
- Portfolio composition analytics
- Expected loss calculations
- MISMO XML format support
- Scheduled report generation & delivery

#### Deliverables
- [ ] InvestorReportingService
- [ ] LoanDetailExporter
- [ ] PerformanceSummaryGenerator
- [ ] MISMOFormatter
- [ ] ReportScheduler
- [ ] ReportDeliveryService

#### API Endpoints
```
POST    /api/v1/reporting/investors/loans    - Generate loan-level report
POST    /api/v1/reporting/investors/summary  - Performance summary
POST    /api/v1/reporting/investors/delinq   - Delinquency details
GET     /api/v1/reporting/investors/history  - Previous reports
POST    /api/v1/reporting/investors/schedule - Setup auto-report
```

---

### 3.2 Loss Modeling & CECL Compliance
**Priority**: MEDIUM-LOW | **Effort**: 100-150 hours | **Team**: 2-3 devs

#### Specification: [SPEC_LOSS_MODELING.md](./specs/SPEC_LOSS_MODELING.md)
- Probability of Default (PD) model
- Loss Given Default (LGD) calculation
- Exposure at Default (EAD) tracking
- Expected Loss (EL) = PD × LGD × EAD
- CECL allowance calculation
- Scenario stress testing
- Monte Carlo simulation for loss distribution
- Quarterly CECL reserve reporting

#### Deliverables
- [ ] ProbabilityOfDefaultCalculator
- [ ] LossGivenDefaultCalculator
- [ ] ExposureAtDefaultCalculator
- [ ] CECLReserveCalculator
- [ ] StressTestingService
- [ ] MonteCarloSimulator
- [ ] LossModelingReporter

---

## IMPLEMENTATION SEQUENCE

### Sprint 1-2 (2 weeks): Foundation
- [ ] Setup SMS/Email provider accounts (Twilio, SendGrid)
- [ ] Create NotificationService base classes
- [ ] Setup CRM integration utilities
- [ ] Create base specifications for core services

### Sprint 3-4 (2 weeks): Origination Phase 1
- [ ] LoanOriginationService skeleton
- [ ] Application submission endpoint
- [ ] Credit check adapter (mock)
- [ ] Database schema

### Sprint 5-6 (2 weeks): Origination Phase 2
- [ ] Document management
- [ ] E-signature integration
- [ ] Approval workflow
- [ ] Disclosure generation

### Sprint 7-8 (2 weeks): Portal Phase 1
- [ ] Portal authentication & authorization
- [ ] Dashboard & loan list
- [ ] Schedule viewing
- [ ] Basic payment portal

### Sprint 9-10 (2 weeks): Portal Phase 2 & Collections Phase 1
- [ ] Portal payment processing
- [ ] Collections dashboard
- [ ] Task management
- [ ] Letter generation

### Sprint 11-12 (2 weeks): Collections Phase 2 & Notifications
- [ ] Collection workflows
- [ ] SMS/Email integration
- [ ] Notification triggers
- [ ] CRM integration

### Sprint 13-14 (2 weeks): Floating Rates
- [ ] Rate index configuration
- [ ] External feed integration
- [ ] Rate adjustment logic
- [ ] Notification system

### Sprint 15+ (Ongoing): Reporting & Analytics
- [ ] Investor reporting
- [ ] Loss modeling
- [ ] CECL calculations
- [ ] Advanced analytics

---

## ARCHITECTURE OVERVIEW

```
┌─────────────────────────────────────────────────────┐
│         Customer Portal / Website                    │
│  (Vue 3 + REST API consumption)                     │
└──────────────────────┬──────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
        ▼              ▼              ▼
┌────────────────┐ ┌────────────┐ ┌──────────────┐
│ Origination    │ │  Servicing │ │ Collections  │
│  Service       │ │  Service   │ │   Service    │
└────────────────┘ └────────────┘ └──────────────┘
        │              │              │
        └──────────────┼──────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
        ▼              ▼              ▼
   ┌────────────┐ ┌─────────┐ ┌──────────────┐
   │ Amortization│ │ CRM Core│ │ Notification │
   │  Engine    │ │ Service │ │  Service     │
   └────────────┘ └─────────┘ └──────────────┘
        │              │              │
        └──────────────┼──────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
        ▼              ▼              ▼
    ┌────────┐   ┌──────────┐   ┌──────────┐
    │Database│   │ Twilio   │   │SendGrid  │
    │ (FA)   │   │ (SMS)    │   │(Email)   │
    └────────┘   └──────────┘   └──────────┘
```

---

## TESTING STRATEGY

### Unit Tests (60% coverage)
- Interest calculations (all methods)
- Payment processors
- Notification logic
- Compliance checks

### Integration Tests (30% coverage)
- CRM data sync
- API endpoints
- SMS/Email delivery
- GL posting

### E2E Tests (10% coverage)
- Full origination workflow
- Customer portal journey
- Collections workflow

### Load Testing
- Portal: 100 concurrent users
- API: 1000 requests/minute
- Notifications: 10k emails/hour

---

## DEPLOYMENT ROADMAP

### Staging (Week 1)
- Deploy Phase 1 features to staging
- CRM integration testing
- Notification service testing
- User acceptance testing

### Production (Week 2)
- Gradual rollout (10% users per day)
- Monitoring & alerting
- Support team training
- Customer communication

### Optimization (Week 3+)
- Performance tuning
- Bug fixes
- Feature feedback incorporation

---

## TEAM ALLOCATION

### Backend Development (3 devs)
- Dev 1: Origination workflow
- Dev 2: Collections & notifications
- Dev 3: Floating rates & reporting

### Frontend Development (2 devs)
- Dev 1: Customer portal UI components
- Dev 2: Collections dashboard

### DevOps (1 dev)
- Infrastructure setup
- SMS/Email provider config
- Monitoring & alerts
- Database migrations

### QA (1 dev)
- Test case creation
- Integration testing
- Performance testing
- UAT coordination

---

## RISK MITIGATION

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|-----------|
| CRM integration issues | Medium | High | Early integration spike |
| SMS/Email delivery delays | Low | Medium | Provider SLA review |
| Regulatory compliance gaps | Low | High | Compliance review w/ legal |
| Performance under load | Medium | High | Early load testing |
| Data migration from legacy | Medium | Medium | Parallel run process |

---

## SUCCESS CRITERIA

### Phase 1 Complete
- [ ] 50+ new loans originated through system (weekly)
- [ ] 1000+ portal logins (monthly)
- [ ] 80% of delinquent accounts in collections workflow
- [ ] 95% notification delivery rate

### Phase 2 Complete
- [ ] 1000+ loans with floating rates
- [ ] SMS open rate > 60%
- [ ] Email open rate > 40%

### Phase 3 Complete
- [ ] 100% investor reporting automated
- [ ] CECL reserve calculation accurate within 2%

---

**Status**: Ready to begin Phase 1  
**Next Step**: Create detailed technical specifications (SPEC_*.md files)

See individual specification files for detailed requirements, API contracts, and implementation details.

