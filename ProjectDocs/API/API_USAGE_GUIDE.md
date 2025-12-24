# KSF Amortization API - Usage Guide

**Version:** 1.0.0  
**Last Updated:** December 2025

---

## Quick Start

### Installation

No installation required. The API is REST-based and accessible via HTTP requests.

### Basic Requirements
- HTTP client (curl, Postman, JavaScript fetch, Python requests, etc.)
- API endpoint: `http://localhost:8000/api/v1`
- JSON format for request/response bodies

### First Request

```bash
# Get all loans
curl http://localhost:8000/api/v1/loans
```

---

## Authentication

Currently, the API does **not** require authentication.

**Future versions** will require API keys in the Authorization header:
```
Authorization: Bearer YOUR_API_KEY
```

---

## Common Workflows

### Workflow 1: Create and Analyze a Loan

**Objective:** Create a new loan, generate schedule, and analyze it.

#### Step 1: Create Loan
```bash
curl -X POST http://localhost:8000/api/v1/loans \
  -H "Content-Type: application/json" \
  -d '{
    "principal": 30000,
    "annual_rate": 0.045,
    "months": 60
  }'
```

**Response:**
```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "id": 1,
    "principal": 30000,
    "annual_rate": 0.045,
    "months": 60,
    "current_balance": 30000,
    "status": "active"
  }
}
```

**Save the ID** from response: `id: 1`

#### Step 2: Generate Schedule
```bash
curl -X POST http://localhost:8000/api/v1/loans/1/schedule/generate
```

#### Step 3: View Schedule
```bash
curl "http://localhost:8000/api/v1/loans/1/schedule?limit=12"
```

**Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": [
    {
      "payment_number": 1,
      "payment_date": "2025-02-01",
      "payment_amount": 531.86,
      "principal": 412.50,
      "interest": 112.50,
      "balance": 29587.50
    }
  ]
}
```

---

### Workflow 2: Track Extra Payments

**Objective:** Record extra payments and see how they affect payoff.

#### Step 1: Record Extra Payment Event
```bash
curl -X POST http://localhost:8000/api/v1/events/record \
  -H "Content-Type: application/json" \
  -d '{
    "loan_id": 1,
    "event_type": "extra_payment",
    "amount": 500,
    "date": "2025-02-01"
  }'
```

**Response:**
```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "event": {
      "id": 1,
      "loan_id": 1,
      "event_type": "extra_payment",
      "amount": 500,
      "event_date": "2025-02-01"
    },
    "loan": {
      "id": 1,
      "current_balance": 24500,
      "status": "active"
    }
  }
}
```

#### Step 2: Forecast Payoff Impact
```bash
curl -X POST http://localhost:8000/api/v1/analysis/forecast \
  -H "Content-Type: application/json" \
  -d '{
    "loan_id": 1,
    "extra_payment_amount": 500,
    "frequency": "monthly"
  }'
```

**Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "original_payoff": {
      "months": 60,
      "total_interest": 1951.60
    },
    "with_extra_payments": {
      "months": 42,
      "total_interest": 1050.00
    },
    "savings": {
      "months_saved": 18,
      "interest_saved": 901.60
    }
  }
}
```

---

### Workflow 3: Manage Multiple Loans

**Objective:** Create multiple loans and compare them.

#### Step 1: Create First Loan
```bash
curl -X POST http://localhost:8000/api/v1/loans \
  -H "Content-Type: application/json" \
  -d '{"principal": 30000, "annual_rate": 0.045, "months": 60}'
```

#### Step 2: Create Second Loan
```bash
curl -X POST http://localhost:8000/api/v1/loans \
  -H "Content-Type: application/json" \
  -d '{"principal": 50000, "annual_rate": 0.035, "months": 84}'
```

#### Step 3: Compare Loans
```bash
curl "http://localhost:8000/api/v1/analysis/compare?loan_ids=1,2"
```

**Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "loans": [
      {
        "id": 1,
        "monthly_payment": 531.86,
        "total_interest": 1951.60,
        "total_cost": 31951.60
      },
      {
        "id": 2,
        "monthly_payment": 630.50,
        "total_interest": 2963.00,
        "total_cost": 52963.00
      }
    ],
    "summary": {
      "cheapest_by_interest": 1,
      "lowest_payment": 1
    }
  }
}
```

#### Step 4: Get Recommendations
```bash
curl "http://localhost:8000/api/v1/analysis/recommendations?loan_ids=1,2"
```

#### Step 5: Get Payoff Timeline
```bash
curl "http://localhost:8000/api/v1/analysis/timeline?loan_ids=1,2"
```

---

### Workflow 4: Handle Rate Change

**Objective:** Record an interest rate change and recalculate.

#### Step 1: Record Rate Change Event
```bash
curl -X POST http://localhost:8000/api/v1/events/record \
  -H "Content-Type: application/json" \
  -d '{
    "loan_id": 1,
    "event_type": "rate_change",
    "new_rate": 0.035,
    "date": "2025-06-01"
  }'
```

**Response:**
```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "event": {
      "id": 2,
      "event_type": "rate_change",
      "new_rate": 0.035
    },
    "loan": {
      "id": 1,
      "annual_rate": 0.035
    }
  }
}
```

#### Step 2: View Updated Schedule
```bash
curl "http://localhost:8000/api/v1/loans/1/schedule"
```

The schedule has been automatically regenerated with the new rate.

---

### Workflow 5: Skip Payment

**Objective:** Skip a payment and extend the loan term.

#### Step 1: Record Skip Payment Event
```bash
curl -X POST http://localhost:8000/api/v1/events/record \
  -H "Content-Type: application/json" \
  -d '{
    "loan_id": 1,
    "event_type": "skip_payment",
    "date": "2025-03-01"
  }'
```

**Response:**
```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "event": {
      "id": 3,
      "event_type": "skip_payment"
    },
    "loan": {
      "id": 1,
      "months": 61
    }
  }
}
```

#### Step 2: View Updated Loan
```bash
curl http://localhost:8000/api/v1/loans/1
```

The loan term has been extended by 1 month.

---

## Code Examples

### JavaScript (Fetch API)

```javascript
// Get all loans
async function getLoans() {
  const response = await fetch('http://localhost:8000/api/v1/loans');
  const data = await response.json();
  
  if (data.success) {
    console.log('Loans:', data.data);
  } else {
    console.error('Error:', data.errors);
  }
}

// Create a loan
async function createLoan(principal, rate, months) {
  const response = await fetch('http://localhost:8000/api/v1/loans', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      principal: principal,
      annual_rate: rate,
      months: months
    })
  });
  
  const data = await response.json();
  return data.data.id;  // Return created loan ID
}

// Record an event
async function recordEvent(loanId, eventType, amount, date) {
  const response = await fetch('http://localhost:8000/api/v1/events/record', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      loan_id: loanId,
      event_type: eventType,
      amount: amount,
      date: date
    })
  });
  
  return response.json();
}

// Compare loans
async function compareLoans(loanIds) {
  const idsString = loanIds.join(',');
  const response = await fetch(
    `http://localhost:8000/api/v1/analysis/compare?loan_ids=${idsString}`
  );
  
  const data = await response.json();
  return data.data;
}
```

---

### Python (Requests)

```python
import requests
import json

BASE_URL = 'http://localhost:8000/api/v1'

# Get all loans
def get_loans():
    response = requests.get(f'{BASE_URL}/loans')
    return response.json()

# Create a loan
def create_loan(principal, rate, months):
    payload = {
        'principal': principal,
        'annual_rate': rate,
        'months': months
    }
    response = requests.post(f'{BASE_URL}/loans', json=payload)
    data = response.json()
    return data['data']['id'] if data['success'] else None

# Record an event
def record_event(loan_id, event_type, amount=None, date=None):
    payload = {
        'loan_id': loan_id,
        'event_type': event_type
    }
    if amount:
        payload['amount'] = amount
    if date:
        payload['date'] = date
    
    response = requests.post(f'{BASE_URL}/events/record', json=payload)
    return response.json()

# Forecast payoff
def forecast_payoff(loan_id, extra_amount, frequency):
    payload = {
        'loan_id': loan_id,
        'extra_payment_amount': extra_amount,
        'frequency': frequency
    }
    response = requests.post(f'{BASE_URL}/analysis/forecast', json=payload)
    return response.json()

# Compare loans
def compare_loans(loan_ids):
    ids_string = ','.join(str(id) for id in loan_ids)
    response = requests.get(
        f'{BASE_URL}/analysis/compare?loan_ids={ids_string}'
    )
    return response.json()

# Example usage
if __name__ == '__main__':
    # Create two loans
    loan1_id = create_loan(30000, 0.045, 60)
    loan2_id = create_loan(50000, 0.035, 84)
    
    print(f'Created loans: {loan1_id}, {loan2_id}')
    
    # Compare them
    comparison = compare_loans([loan1_id, loan2_id])
    print(json.dumps(comparison, indent=2))
```

---

### cURL Examples

#### List loans with pagination
```bash
curl -X GET "http://localhost:8000/api/v1/loans?offset=0&limit=10"
```

#### Create a loan
```bash
curl -X POST http://localhost:8000/api/v1/loans \
  -H "Content-Type: application/json" \
  -d '{
    "principal": 30000,
    "annual_rate": 0.045,
    "months": 60
  }'
```

#### Get specific loan
```bash
curl -X GET http://localhost:8000/api/v1/loans/1
```

#### Update a loan
```bash
curl -X PUT http://localhost:8000/api/v1/loans/1 \
  -H "Content-Type: application/json" \
  -d '{
    "annual_rate": 0.040
  }'
```

#### Delete a loan
```bash
curl -X DELETE http://localhost:8000/api/v1/loans/1
```

#### Generate schedule
```bash
curl -X POST http://localhost:8000/api/v1/loans/1/schedule/generate
```

#### Get schedule
```bash
curl -X GET "http://localhost:8000/api/v1/loans/1/schedule?limit=12"
```

#### Record event
```bash
curl -X POST http://localhost:8000/api/v1/events/record \
  -H "Content-Type: application/json" \
  -d '{
    "loan_id": 1,
    "event_type": "extra_payment",
    "amount": 500,
    "date": "2025-02-01"
  }'
```

#### Compare loans
```bash
curl -X GET "http://localhost:8000/api/v1/analysis/compare?loan_ids=1,2,3"
```

#### Forecast payoff
```bash
curl -X POST http://localhost:8000/api/v1/analysis/forecast \
  -H "Content-Type: application/json" \
  -d '{
    "loan_id": 1,
    "extra_payment_amount": 500,
    "frequency": "monthly"
  }'
```

#### Get recommendations
```bash
curl -X GET "http://localhost:8000/api/v1/analysis/recommendations?loan_ids=1,2"
```

#### Get timeline
```bash
curl -X GET "http://localhost:8000/api/v1/analysis/timeline?loan_ids=1,2"
```

---

## Best Practices

### 1. Always Check Response Success
```javascript
const response = await fetch(url);
const data = await response.json();

if (!data.success) {
  // Handle error
  data.errors.forEach(err => console.error(err));
  return;
}

// Use data.data
console.log(data.data);
```

### 2. Validate Input Before Sending
```python
# Wrong - direct conversion
annual_rate = 4.5  # Will fail - expects 0-1

# Correct - convert percentage to decimal
annual_rate = 4.5 / 100  # 0.045
```

### 3. Use Proper Date Format
```bash
# Wrong
"date": "02/01/2025"

# Correct
"date": "2025-02-01"
```

### 4. Handle Errors Gracefully
```javascript
try {
  const response = await fetch(url);
  const data = await response.json();
  
  if (!data.success) {
    showUserError(data.errors[0]);
  }
} catch (error) {
  console.error('Network error:', error);
  showUserError('Unable to connect to server');
}
```

### 5. Cache Results When Appropriate
```javascript
// Cache analysis results for 5 minutes
const cache = new Map();
const CACHE_DURATION = 5 * 60 * 1000;

async function cachedAnalysis(endpoint, params) {
  const key = `${endpoint}-${JSON.stringify(params)}`;
  
  if (cache.has(key)) {
    const cached = cache.get(key);
    if (Date.now() - cached.time < CACHE_DURATION) {
      return cached.data;
    }
  }
  
  const response = await fetch(`${endpoint}?${params}`);
  const data = await response.json();
  
  cache.set(key, { data: data.data, time: Date.now() });
  return data.data;
}
```

### 6. Implement Retry Logic
```python
import time

def call_with_retry(func, max_retries=3):
    for attempt in range(max_retries):
        try:
            return func()
        except Exception as e:
            if attempt == max_retries - 1:
                raise
            wait_time = 2 ** attempt  # Exponential backoff
            time.sleep(wait_time)
```

### 7. Batch Operations
```bash
# Create multiple loans efficiently
for i in {1..10}; do
  curl -X POST http://localhost:8000/api/v1/loans \
    -H "Content-Type: application/json" \
    -d "{
      \"principal\": $((20000 + i * 1000)),
      \"annual_rate\": 0.045,
      \"months\": 60
    }"
done
```

### 8. Use Pagination for Large Results
```javascript
// Wrong - might timeout with large data
const allLoans = await fetch('/api/v1/loans');

// Correct - use pagination
async function getAllLoans() {
  let allLoans = [];
  let offset = 0;
  let hasMore = true;
  
  while (hasMore) {
    const response = await fetch(
      `/api/v1/loans?offset=${offset}&limit=100`
    );
    const data = await response.json();
    
    allLoans = allLoans.concat(data.data);
    hasMore = data.data.length === 100;
    offset += 100;
  }
  
  return allLoans;
}
```

---

## Common Mistakes

### Mistake 1: Using Percentage Instead of Decimal
```bash
# ❌ Wrong
"annual_rate": 4.5

# ✅ Correct
"annual_rate": 0.045
```

### Mistake 2: Not Checking Response Status
```javascript
// ❌ Wrong
const data = await response.json();
console.log(data.data);  // May be undefined if error

// ✅ Correct
const data = await response.json();
if (data.success) {
  console.log(data.data);
} else {
  console.error('Error:', data.errors);
}
```

### Mistake 3: Wrong Date Format
```bash
# ❌ Wrong
"date": "2/1/25"
"date": "02-01-2025"
"date": "January 1, 2025"

# ✅ Correct
"date": "2025-02-01"
```

### Mistake 4: Not Generating Schedule
```bash
# ❌ Wrong - expecting schedule without generating
GET /api/v1/loans/1/schedule

# ✅ Correct
POST /api/v1/loans/1/schedule/generate
GET /api/v1/loans/1/schedule
```

### Mistake 5: Using Non-Existent Loan ID
```bash
# ❌ Wrong
GET /api/v1/loans/999  # Assuming ID 999 exists

# ✅ Correct
GET /api/v1/loans      # List first to find valid IDs
GET /api/v1/loans/1    # Use valid ID
```

---

## Performance Tips

1. **Limit results with pagination** - Use offset/limit for large datasets
2. **Cache analysis results** - Don't recalculate on every request
3. **Batch multiple operations** - Fewer round trips
4. **Use appropriate update operations** - Only update changed fields
5. **Consider data size** - Large schedules may be slow to process

---

## Monitoring and Debugging

### Check Server Health
```bash
# Simple health check
curl http://localhost:8000/api/v1/loans
```

### Enable Logging
```php
// In your application
define('API_DEBUG', true);
```

### Monitor Response Times
```javascript
async function timedFetch(url) {
  const start = Date.now();
  const response = await fetch(url);
  const duration = Date.now() - start;
  
  console.log(`Request took ${duration}ms`);
  return response.json();
}
```

---

## Next Steps

1. **Explore the API** - Try the examples above
2. **Read API_DOCUMENTATION.md** - Detailed endpoint reference
3. **Check ERROR_REFERENCE.md** - Error codes and troubleshooting
4. **Review openapi.json** - Machine-readable API specification

---

## Support

For questions or issues:
- **Documentation:** See API_DOCUMENTATION.md
- **Errors:** See ERROR_REFERENCE.md
- **Email:** support@ksf-amortization.local

