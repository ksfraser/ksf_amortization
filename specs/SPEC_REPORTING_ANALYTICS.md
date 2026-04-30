# SPEC-REPORTING: Reporting & Analytics Workflow Specification

**Version**: 1.0 | **Date**: April 28, 2026 | **Status**: Ready for Implementation

---

## 1. OVERVIEW

The Reporting & Analytics system provides real-time dashboards, comprehensive reports, and data-driven insights for loan management, collections performance, financial health, and operational metrics. Built on a dimensional data warehouse and optimized for sub-second query performance.

### Key Features
- Real-time dashboard widgets
- Multi-dimensional reporting (slice/dice)
- Export to Excel/PDF/CSV
- Scheduled email reports
- Advanced analytics & forecasting
- Performance benchmarking
- Risk scoring & portfolio analysis
- Regulatory reporting (CRA, audit-ready)
- Mobile-responsive dashboards
- Role-based report access

### Success Metrics
- Dashboard load time: < 2 seconds
- Report execution: < 5 seconds (average)
- Data freshness: Real-time to 15 minutes lag
- Report accuracy: 99.9%
- User adoption: 85%+ of team

---

## 2. DASHBOARD ARCHITECTURE

### 2.1 Dashboard Personas

#### Loan Officer Dashboard
```
┌─────────────────────────────────────────────────┐
│ LOAN OFFICER DASHBOARD                          │
├─────────────────────────────────────────────────┤
│
│ MY PORTFOLIO SUMMARY
│ ┌─────────────┬─────────────┬─────────────┐
│ │ Total Loans │ Total AUM   │ Avg Rate    │
│ │    45       │ $2,250,000  │   8.5%      │
│ └─────────────┴─────────────┴─────────────┘
│
│ ORIGINATION THIS MONTH
│ ┌─────────────┬─────────────┐
│ │ Loans: 12   │ Volume: $425K│
│ │ vs Target   │ vs Target    │
│ │ +33% 📈     │ -5% 📉       │
│ └─────────────┴─────────────┘
│
│ LOAN STATUS BREAKDOWN
│ ┌─────────────────────────────┐
│ │ CURRENT: 40 (88.9%)         │
│ │ 30 DAYS:  3 (6.7%)          │
│ │ 60 DAYS:  2 (4.4%)          │
│ └─────────────────────────────┘
│
│ RECENT APPROVALS (Last 5)
│ ┌──────────────────────────────┐
│ │ Borrower | Amount | Status   │
│ │ John Doe | $50K   | APPROVED │
│ │ Jane Sm. | $75K   | PENDING  │
│ └──────────────────────────────┘
│
│ PERFORMANCE VS GOALS (YTD)
│ ┌──────────────────────────────┐
│ │ Origination: $425K / $600K 71%│
│ │ Default Rate: 1.2% vs 2% ✓   │
│ │ Avg Rate: 8.5% vs 8.25% ✗    │
│ └──────────────────────────────┘
```

#### Collections Manager Dashboard
```
┌─────────────────────────────────────────────────┐
│ COLLECTIONS MANAGER DASHBOARD                   │
├─────────────────────────────────────────────────┤
│
│ PORTFOLIO HEALTH
│ ┌──────────┬──────────┬──────────┬──────────┐
│ │ Current  │ 30 Days  │ 60 Days  │ 90+ Days │
│ │ 1,100    │   100    │    35    │    15    │
│ │ 88.0%    │   8.0%   │   2.8%   │   1.2%   │
│ └──────────┴──────────┴──────────┴──────────┘
│
│ COLLECTION PERFORMANCE (THIS MONTH)
│ ┌────────────────────────────────┐
│ │ Collections: $125,450           │
│ │ vs Target: $100,000 ✓ 125.5%   │
│ │ Avg per task: $3,298            │
│ │ Completion rate: 84%            │
│ └────────────────────────────────┘
│
│ TOP COLLECTORS (This Month)
│ ┌──────────────────────────────┐
│ │ Collector    | Collections   │
│ │ John Smith   | $18,500 (1)   │
│ │ Jane Wilson  | $16,200 (2)   │
│ │ Mike Brown   | $14,850 (3)   │
│ └──────────────────────────────┘
│
│ DELINQUENCY TRENDS (3 Months)
│ │ 30+ Days Delinquency Rate     │
│ │        ╱─────────╲             │
│ │      ╱             ╲           │
│ │  12% ──────────────── 10.2%   │
│ │      Mar        Apr            │
│ └──────────────────────────────┘
```

#### Executive Dashboard
```
┌─────────────────────────────────────────────────┐
│ EXECUTIVE DASHBOARD                             │
├─────────────────────────────────────────────────┤
│
│ BUSINESS METRICS (YTD)
│ ┌–─────────────────────────────────────┐
│ │ Revenue (Fees + Interest):  $425K     │
│ │ Operating Costs:            $185K     │
│ │ Net Margin:                 56.5%     │
│ │ ROA:                        12.3%     │
│ └–─────────────────────────────────────┘
│
│ PORTFOLIO HEALTH
│ │ Total AUM: $5,250,000
│ │ Delinquency Rate: 2.4% (Target: 2%) ⚠️
│ │ Default Rate: 1.1% (Target: 1.5%) ✓
│ │ Loss Rate: $58K YTD (1.1% of volume)
│ └─────────────────────────────────────┘
│
│ LOAN PRODUCTION
│ │ Loans Originated: 245 (vs 300 target) 81.7%
│ │ Volume: $12.25M (vs $15M target) 81.7%
│ │ Avg Loan Size: $50,000
│ │ Approval Rate: 89%
│ └─────────────────────────────────────┘
│
│ COLLECTIONS IMPACT
│ │ Collections YTD: $845K
│ │ Collections Rate (30+ days): 82%
│ │ Arrangements Active: 28
│ │ Legal Actions Pending: 3
│ └─────────────────────────────────────┘
```

### 2.2 Real-Time Widgets

```
1. Account Health Ticker
   └─ Current accounts: 1,100 (%)
   └─ 30 days: 100 (↑ 12%)
   └─ 60 days: 35 (↓ 5%)
   └─ 90+ days: 15 (→ 0%)

2. Collections Leaderboard
   └─ Top 10 collectors by $ collected this month
   └─ Auto-updates every hour
   └─ Click for individual performance

3. Recent Transactions
   └─ Last 10 payment received
   └─ Loan disbursements
   └─ Arrangement updates

4. Risk Alert Stream
   └─ New accounts scoring > 0.8 risk
   └─ Sudden payment pattern changes
   └─ Accounts approaching charge-off

5. YTD Metrics Comparison
   └─ Revenue vs Budget
   └─ Default Rate vs Target
   └─ Collection Rate vs Historical
```

---

## 3. STANDARD REPORTS

### 3.1 Loan Portfolio Reports

#### Portfolio Summary Report
```
Report: Loan Portfolio Summary
Period: April 2026
═══════════════════════════════════════════════════════════

PORTFOLIO OVERVIEW
───────────────────────────────────────
Total Loans:              1,250
Total Loan Amount:        $5,250,000
Total Outstanding:        $5,125,500
Total Collected (YTD):    $845,250

Average Loan Size:        $50,000
Average Interest Rate:    8.5%
Average Loan Term:        36 months

LOAN STATUS BREAKDOWN
───────────────────────────────────────
Current (0-9 days):       1,100 (88.0%)
30 Days Past Due:         100 (8.0%)
60 Days Past Due:         35 (2.8%)
90+ Days Past Due:        15 (1.2%)
───────────────────────────────────────
Total Delinquent:         150 (12.0%)

DELINQUENT BALANCE ANALYSIS
───────────────────────────────────────
30 Days Past Due:         $75,000 (6.0%)
60 Days Past Due:         $28,000 (2.2%)
90+ Days Past Due:        $12,500 (1.0%)
───────────────────────────────────────
Total Past Due:           $124,500 (10.0%)

DEFAULT & LOSS ANALYSIS (YTD)
───────────────────────────────────────
Total Defaults:           14 (1.1%)
Total Charge-offs:        3 (0.24%)
Total Loss Amount:        $58,000 (1.1% of orig. volume)
Recovery Rate:            68% (of defaulted loans)

PERFORMANCE VS GOALS
───────────────────────────────────────
Origination Target (YTD): $15,000,000
Origination Actual (YTD): $12,250,000
Variance:                 -18.3% ⚠️

Delinquency Target:       < 2.0%
Delinquency Actual:       2.4%
Variance:                 +0.4% ⚠️

Default Rate Target:      < 1.5%
Default Rate Actual:      1.1%
Variance:                 -0.4% ✓
```

#### Loan Performance by Product Type
```
Report: Loan Performance by Product Type
Period: April 2026
════════════════════════════════════════════════════════

PRODUCT: Personal Loans
────────────────────────────
Count:                    425
Total Amount:             $21.25M
Current Status %:         87.1%
30-Day Delinquency %:     8.9%
Default Rate:             1.4%
Avg Interest Rate:        8.2%
Collection Rate (30+):    81%

PRODUCT: Auto Loans
────────────────────────────
Count:                    580
Total Amount:             $29.0M
Current Status %:         90.0%
30-Day Delinquency %:     6.2%
Default Rate:             0.9%
Avg Interest Rate:        7.8%
Collection Rate (30+):    85%

PRODUCT: Business Loans
────────────────────────────
Count:                    245
Total Amount:             $24.5M
Current Status %:         84.5%
30-Day Delinquency %:     10.2%
Default Rate:             1.6%
Avg Interest Rate:        9.5%
Collection Rate (30+):    78%
```

### 3.2 Collections Reports

#### Collections Performance Report
```
Report: Collections Team Performance
Period: April 1-30, 2026
══════════════════════════════════════════════════

TEAM SUMMARY
─────────────────────────────────
Active Collectors:        12
Tasks Assigned:           480
Tasks Completed:          403
Completion Rate:          83.9%
Avg Tasks per Collector:  40

COLLECTIONS RESULTS
─────────────────────────────────
Total Collections:        $145,750
Avg per Task:             $362
Avg per Collector:        $12,146
Collections Target:       $120,000
Target Achievement:       121.5% ✓

CONTACT ACTIVITY
─────────────────────────────────
Total Contacts:           1,240
Calls:                    890 (71.8%)
Emails:                   210 (16.9%)
SMS Messages:             140 (11.3%)
Avg Contacts per Task:    3.1

PROMISES & FOLLOW-THROUGH
─────────────────────────────────
Promises Made:            340
Promises Kept:            287
Promise Keep Rate:        84.4%
Arranged Payments:        28
Arrangement Default Rate: 3.6%

INDIVIDUAL COLLECTOR RANKINGS (YTD Collections)
─────────────────────────────────────────────────
Rank | Collector      | Collections | Tasks | Avg/Task
─────┼────────────────┼─────────────┼───────┼─────────
 1   | John Smith     | $18,500     | 42    | $440
 2   | Jane Wilson    | $16,200     | 38    | $426
 3   | Mike Brown     | $14,850     | 41    | $362
 4   | Sarah Jones    | $13,750     | 45    | $306
 5   | David Lee      | $12,450     | 40    | $311
```

#### Delinquency Aging Report
```
Report: Delinquency Aging Analysis
Period: April 30, 2026
═════════════════════════════════════════════════

AGING BUCKETS
─────────────────────────────────────────────
  10-19 Days:              45 loans | $35,000
  20-29 Days:              30 loans | $25,000
  30-39 Days:              20 loans | $18,000
  40-49 Days:              18 loans | $16,200
  50-59 Days:              12 loans | $11,500
  60-69 Days:              10 loans | $9,500
  70-79 Days:              8 loans  | $7,200
  80-89 Days:              5 loans  | $4,500
  90+ Days:                 12 loans | $11,000
─────────────────────────────────────────────
TOTAL PAST DUE:            160 loans | $137,900

CURRENT COLLECTION EFFORTS
─────────────────────────────────────────────
0-19 Days: Active Collections (88% collection rate)
20-39 Days: Intensive Collections (80% rate)
40-59 Days: Attorney Review (65% rate)
60+ Days: Legal Action/Charge-off (40% rate)

30-DAY TRENDS (Total Delinquency)
─────────────────────────────────────────────
March:      145 loans (+2.1% vs Feb)
April:      160 loans (+10.3% vs Mar) ⚠️
May Fcast:  155 loans (-3.1% projected)

ANALYSIS & RECOMMENDATIONS
─────────────────────────────────────────────
✓ Collections team performing well (+121% vs target)
✓ Default rate favorable (1.1% vs 1.5% target)
⚠️ Delinquency increasing this month (+10%)
⚠️ 60+ day delinquencies at 15 loans (highest this quarter)
→ Recommend legal review for 5 oldest accounts
```

### 3.3 Financial Reports

#### Interest Income Report
```
Report: Interest Income Analysis
Period: April 2026
══════════════════════════════════════════════

SCHEDULED VS ACTUAL INCOME
───────────────────────────────────────
Scheduled Interest Income:    $45,250
Actual Interest Collected:    $42,100
Collection Rate:              93.0%

Interest Deferred/Accrued:
  Current Accounts:           $45,250
  30 Days Past Due:           $3,750 (partial reserve)
  60+ Days Past Due:          $2,100 (reserve)
  ────────────────────────────────────
  Total Reserve:              $5,850

NET REALIZED INTEREST INCOME:  $42,100

INTEREST BY PRODUCT TYPE
───────────────────────────────────────
Personal Loans:             $18,500 (43.8%)
Auto Loans:                 $16,200 (38.4%)
Business Loans:             $7,250 (17.2%)
Other:                      $1,150 (2.7%)

DEFERRED INTEREST AGING
───────────────────────────────────────
< 30 Days:                  $2,100 (likely collection)
30-60 Days:                 $1,750 (partial reserve)
60+ Days:                   $2,000 (full reserve)
```

#### Fee Income Report
```
Report: Fee Income Analysis
Period: April 2026
══════════════════════════════════════════════

ORIGINATION FEES
───────────────────────────────────────
Loans Originated:           12 loans
Origination Fee Rate:       2.5% average
Total Origination Fees:     $12,500
YTD Origination Fees:       $185,750

DEFAULT FEES
───────────────────────────────────────
Late Payment Fees:          $2,450
NSF Fees:                   $850
Loan Modification Fees:     $1,200
Total Fee Income (Month):   $16,550
YTD Fee Income:             $189,250
```

### 3.4 Executive Reports

#### Monthly Executive Summary
```
Report: Monthly Executive Summary
April 2026
══════════════════════════════════════════════════════════════

FINANCIAL PERFORMANCE
─────────────────────────────────────────────────────
Revenue (Interest + Fees):      $58,650
Operating Expenses:             $23,500
Operating Margin:               59.9%
ROA (Annualized):              12.1%
YTD Net Income:                $287,550

LOAN PRODUCTION
─────────────────────────────────────────────────────
Loans Originated:               12 loans
Origination Amount:             $500,000
Origination Rate vs Target:     87.5% (Target: $575K)
Approval Rate:                  89%
Average Processing Time:        5.2 days

PORTFOLIO HEALTH
─────────────────────────────────────────────────────
Total AUM:                      $5,250,000
Current Accounts %:             88.0%
Delinquency Rate:               2.4% (Target: 2.0%) ⚠️
Default Rate (YTD):             1.1% (Target: 1.5%) ✓
Expected Loss (Reserve):        $58,000 (1.1%)

COLLECTIONS
─────────────────────────────────────────────────────
Collections (Month):            $145,750
Collections Rate (30+):         82% (Target: 80%) ✓
Arrangements Created:           4
Arrangements Defaulted:         1 (3.6% default rate)
Accounts to Charge-off:         2

KEY DECISIONS NEEDED
─────────────────────────────────────────────────────
→ Delinquency rate rising - consider tightened approval criteria
→ 5 accounts in 60+ days category - legal review recommended
→ Consider adjustment to origination targets down 12%

FORECAST (Next 30 Days)
─────────────────────────────────────────────────────
Projected Won't-Pay Rate:      1.3%
Projected Collections:         $152,000 (+4.3% vs April)
Projected Revenue:             $62,100 (+5.9% vs April)
Risk Factors:                  Higher delinquency trend
```

---

## 4. AD-HOC REPORTING

### 4.1 Report Builder UI

```
Report Builder
═══════════════════════════════════════════════════

1. Select Report Type
   ○ Loan Portfolio
   ○ Collections
   ○ Financial
   ○ Risk Analysis
   ○ Custom (Ad-hoc)

2. Time Period
   ○ Year to Date
   ○ Last 30 Days
   ○ Last 90 Days
   ○ Custom: [From] [To]

3. Dimensions (Multi-select)
   ☑ Loan Officer
   ☑ Loan Product Type
   ☑ Borrower Location
   ☑ Loan Stage
   ☐ Interest Rate Tier
   ☐ Other

4. Metrics (Multi-select)
   ☑ Count
   ☑ Volume
   ☑ Current Balance
   ☑ Collections
   ☑ Default Count
   ☑ Default Rate
   ☐ Interest Income
   ☐ Late Fees Received

5. Filters
   ┌─ Loan Officer [Select: John Smith, Jane Wilson, All]
   ├─ State [Multi-select]
   ├─ Loan Status [Current, 30+, All]
   ├─ Min Loan Size [$0]
   └─ Max Loan Size [$∞]

6. Output Format
   ○ View Online (Table/Chart)
   ○ Export Excel
   ○ Export PDF
   ○ Download CSV

[Build Report] [Save Report] [Cancel]
```

### 4.2 Saved Custom Reports

```
MyReports
═════════════════════════════════════════════════════

1. Production by Officer (Monthly)
   └─ Last run: Apr 27, 2026 03:00 AM
   └─ Loan Officer (All) | Period (MTD)
   └─ [Re-run] [Edit] [Delete]

2. High-Risk Accounts
   └─ Last run: Apr 28, 2026 10:15 AM
   └─ Delinquency > 30 days, Default Risk > 0.6
   └─ [Re-run] [Edit] [Delete]

3. Collections Leaderboard
   └─ Last run: Apr 28, 2026 10:30 AM
   └─ By Collector (All) | Period (MTD)
   └─ [Re-run] [Edit] [Delete]

[Create New Report]
```

### 4.3 Scheduled Email Reports

```
Scheduled Reports
═════════════════════════════════════════════════════

1. Daily Collections Summary
   └─ Recipients: Collections Manager, Director
   └─ Time: 8:00 AM EST (Daily)
   └─ Format: Email + PDF attachment
   └─ Includes: Top performers, deals closed
   └─ Status: ✓ Active

2. Weekly Portfolio Summary
   └─ Recipients: Executive Team
   └─ Time: Monday 9:00 AM EST
   └─ Format: Email + Excel attachment
   └─ Status: ✓ Active

3. Monthly Financial Close
   └─ Recipients: CFO, Controller, CEO
   └─ Time: 1st of month 5:00 AM EST
   └─ Format: Email + PDF + Excel
   └─ Status: ✓ Active

[Create New Schedule] [Edit Existing]
```

---

## 5. DATA WAREHOUSE ARCHITECTURE

### 5.1 Dimensional Model

```
Facts:
├─ 0_ksf_fact_daily_loans
│  ├─ Date
│  ├─ Loan Dimension
│  ├─ Borrower Dimension
│  ├─ Product Dimension
│  ├─ Loan Officer Dimension
│  └─ Measures: balance, interest_earned, collections, status
│
├─ 0_ksf_fact_collections
│  ├─ Date
│  ├─ Collector Dimension
│  ├─ Loan Dimension
│  ├─ Activity Type
│  └─ Measures: count, amount, promised_date
│
└─ 0_ksf_fact_delinquency
   ├─ Date
   ├─ Loan Dimension
   ├─ Aging Bucket
   └─ Measures: count, days_late, past_due_amount

Dimensions:
├─ 0_ksf_dim_loans
│  ├─ loan_id
│  ├─ loan_number
│  ├─ product_type
│  ├─ origination_date
│  ├─ maturity_date
│  ├─ original_amount
│  └─ current_status
│
├─ 0_ksf_dim_borrowers
│  ├─ borrower_id
│  ├─ name
│  ├─ state
│  ├─ credit_score
│  └─ income_range
│
├─ 0_ksf_dim_product
│  ├─ product_id
│  ├─ product_name
│  └─ product_category
│
└─ 0_ksf_dim_loan_officers
   ├─ officer_id
   ├─ officer_name
   └─ division
```

### 5.2 Data Refresh Strategy

```
Real-Time (Every 15 minutes):
└─ Current account status
└─ Recent transactions
└─ Daily delinquency counts

Hourly (Every 60 minutes):
└─ Collections activity
└─ Loan stage changes
└─ Officer productivity metrics

Daily (Nightly 2:00 AM):
└─ Full fact table refresh
└─ Historical comparisons
└─ Aging analysis
└─ Financial summaries
```

---

## 6. API ENDPOINTS

### 6.1 Dashboard Data

```
GET /api/v1/reports/dashboard/loan-officer/:officer_id
──────────────────────────────────────────────────────
Response: 200 OK
{
  "portfolio_summary": {
    "total_loans": 45,
    "total_aum": 2250000.00,
    "current_count": 40,
    "current_pct": 88.9,
    "delinquent_30": 3,
    "delinquent_60": 2,
    "delinquent_90": 0
  },
  "origination_mtd": {
    "count": 12,
    "amount": 425000.00,
    "vs_target": -5.0,
    "approval_rate": 92.0
  },
  "performance_vs_goals": [
    {
      "metric": "Origination",
      "target": 600000.00,
      "actual": 425000.00,
      "pct": 70.8,
      "status": "below_target"
    }
  ]
}

───────────────────────────────────────────────────────

GET /api/v1/reports/dashboard/collections-manager
─────────────────────────────────────────────────
Response: 200 OK
{
  "portfolio_health": {
    "total_loans": 1250,
    "current": { "count": 1100, "pct": 88.0 },
    "delinquent_30": { "count": 100, "pct": 8.0 },
    "delinquent_60": { "count": 35, "pct": 2.8 },
    "delinquent_90": { "count": 15, "pct": 1.2 }
  },
  "collections_mtd": {
    "collections": 145750.00,
    "target": 120000.00,
    "achievement_pct": 121.5,
    "avg_per_task": 362.00
  },
  "top_collectors": [
    {
      "collector_id": "js001",
      "collector_name": "John Smith",
      "collections": 18500.00,
      "rank": 1
    }
  ],
  "trends": {
    "delinquency_trend": "+10.3%",
    "collection_trend": "-2.1%"
  }
}
```

### 6.2 Report Generation

```
GET /api/v1/reports/portfolio-summary
────────────────────────────────────
Query Parameters:
  - period: month, ytd, custom
  - from_date: 2026-01-01
  - to_date: 2026-04-30
  - format: json, pdf, excel, csv

Response: 200 OK (format=json)
{
  "report_id": "RPT-2026-04-001",
  "title": "Loan Portfolio Summary",
  "period": "April 2026",
  "generated_at": "2026-04-28T10:30:00Z",
  "sections": {
    "portfolio_overview": {
      "total_loans": 1250,
      "total_amount": 5250000.00,
      ...
    },
    "status_breakdown": { ... },
    "delinquency_analysis": { ... }
  }
}

Response: 200 OK (format=pdf)
[Binary PDF data with headers for download]
```

### 6.3 Custom Report Builder

```
POST /api/v1/reports/custom
───────────────────────────
Request:
{
  "report_name": "Collections by Officer",
  "report_type": "collections",
  "period": {
    "from_date": "2026-04-01",
    "to_date": "2026-04-30"
  },
  "dimensions": ["collector_id", "activity_type"],
  "metrics": ["contact_count", "collections_amount"],
  "filters": {
    "collector_id": "all",
    "activity_type": ["called", "promised_pay"]
  },
  "output_format": "json"
}

Response: 201 Created
{
  "report_id": "CUSTOM-20260428-001",
  "title": "Collections by Officer",
  "data": [
    {
      "collector_id": "js001",
      "collector_name": "John Smith",
      "activity_type": "called",
      "contact_count": 42,
      "collections_amount": 18500.00
    }
  ]
}
```

### 6.4 Scheduled Reports

```
POST /api/v1/reports/schedule
─────────────────────────────
Request:
{
  "report_id": "RPT-DAILY-COLLECTIONS",
  "report_type": "collections_summary",
  "schedule": {
    "frequency": "daily",
    "time": "08:00",
    "timezone": "US/Eastern"
  },
  "recipients": ["manager@example.com", "director@example.com"],
  "format": "pdf",
  "enabled": true
}

Response: 201 Created
{
  "schedule_id": "SCHED-001",
  "report_id": "RPT-DAILY-COLLECTIONS",
  "next_run": "2026-04-29T08:00:00Z",
  "status": "active"
}
```

---

## 7. IMPLEMENTATION CHECKLIST

Phase 1: Foundation (2 weeks)
- [ ] Data warehouse schema design
- [ ] Fact & dimension table creation
- [ ] ETL for daily loads
- [ ] Base dashboard SQL queries

Phase 2: Dashboard & Reports (2 weeks)
- [ ] Dashboard widget implementations
- [ ] Standard report generation
- [ ] Report export (Excel, PDF)
- [ ] Scheduled email reports

Phase 3: Ad-Hoc Reporting (1 week)
- [ ] Report builder UI
- [ ] Custom dimension support
- [ ] Saved report management
- [ ] Performance optimization

Phase 4: Integration & Testing (1 week)
- [ ] API integration with web UI
- [ ] CRM dashboard sync
- [ ] Data accuracy validation
- [ ] Performance testing (sub-2 sec queries)

---

**Status**: Specification complete, ready for development  
**Estimated Timeline**: 8 weeks (with 2 developers)  
**Next Step**: Data warehouse schema implementation

