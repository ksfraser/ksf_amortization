# KSF Amortization API Documentation

**Version:** 1.0.0  
**Last Updated:** December 2025  
**Base URL:** `/api/v1`  

---

## Overview

The KSF Amortization API provides a comprehensive REST interface for loan management, amortization scheduling, event handling, and advanced financial analysis. The API supports 18 endpoints organized into 4 main categories:

- **Loan Management** (5 endpoints) - CRUD operations on loans
- **Schedule Management** (3 endpoints) - Amortization schedule generation and retrieval
- **Event Handling** (4 endpoints) - Record and manage loan events
- **Analysis & Forecasting** (4 endpoints) - Loan comparison, forecasting, and recommendations

---

## Authentication

Currently, the API does not require authentication. In production, API keys should be used. Include the API key in the `Authorization` header:

```
Authorization: Bearer YOUR_API_KEY
```

---

## Response Format

All API responses follow a standardized JSON format:

### Success Response
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    // Response data
  }
}
```

### Error Response
```json
{
  "success": false,
  "status_code": 400,
  "errors": [
    "Field is required",
    "Invalid value for field"
  ]
}
```

---

## Loan Management Endpoints

### 1. List All Loans
**GET** `/loans`

List all loans in the system with pagination.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| offset | integer | 0 | Number of records to skip |
| limit | integer | 20 | Maximum records to return (1-100) |

**Example Request:**
```bash
GET /api/v1/loans?offset=0&limit=20
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": [
    {
      "id": 1,
      "principal": 30000,
      "annual_rate": 0.045,
      "months": 60,
      "current_balance": 25000,
      "status": "active"
    },
    {
      "id": 2,
      "principal": 50000,
      "annual_rate": 0.035,
      "months": 84,
      "current_balance": 50000,
      "status": "active"
    }
  ]
}
```

---

### 2. Create a Loan
**POST** `/loans`

Create a new loan with specified terms.

**Request Body:**
```json
{
  "principal": 30000,
  "annual_rate": 0.045,
  "months": 60
}
```

**Required Fields:**
- `principal` (number) - Loan amount in currency units
- `annual_rate` (number) - Interest rate as decimal (0.045 = 4.5%)
- `months` (integer) - Loan term in months

**Example cURL Request:**
```bash
curl -X POST http://localhost:8000/api/v1/loans \
  -H "Content-Type: application/json" \
  -d '{
    "principal": 30000,
    "annual_rate": 0.045,
    "months": 60
  }'
```

**Example Response (201 Created):**
```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "id": 3,
    "principal": 30000,
    "annual_rate": 0.045,
    "months": 60,
    "current_balance": 30000,
    "status": "active"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "status_code": 400,
  "errors": [
    "principal must be a positive number",
    "annual_rate must be between 0 and 1"
  ]
}
```

---

### 3. Get Loan by ID
**GET** `/loans/{id}`

Retrieve a specific loan by its ID.

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Loan ID |

**Example Request:**
```bash
GET /api/v1/loans/1
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "id": 1,
    "principal": 30000,
    "annual_rate": 0.045,
    "months": 60,
    "current_balance": 25000,
    "status": "active"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "status_code": 404,
  "errors": ["Loan not found"]
}
```

---

### 4. Update Loan
**PUT** `/loans/{id}`

Update an existing loan's terms.

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Loan ID |

**Request Body (all fields optional):**
```json
{
  "principal": 32000,
  "annual_rate": 0.040,
  "months": 60
}
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "id": 1,
    "principal": 32000,
    "annual_rate": 0.040,
    "months": 60,
    "current_balance": 25000,
    "status": "active"
  }
}
```

---

### 5. Delete Loan
**DELETE** `/loans/{id}`

Delete a loan and all associated records (schedule, events, etc.).

**Example Request:**
```bash
DELETE /api/v1/loans/1
```

**Example Response (200 OK):**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "message": "Loan deleted successfully"
  }
}
```

---

## Schedule Management Endpoints

### 6. Get Amortization Schedule
**GET** `/loans/{id}/schedule`

Retrieve the complete amortization schedule for a loan.

**Path Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| id | integer | Loan ID |

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| offset | integer | 0 | Skip n entries |
| limit | integer | 100 | Max entries to return |

**Example Request:**
```bash
GET /api/v1/loans/1/schedule?offset=0&limit=12
```

**Example Response:**
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
    },
    {
      "payment_number": 2,
      "payment_date": "2025-03-01",
      "payment_amount": 531.86,
      "principal": 413.75,
      "interest": 112.50,
      "balance": 29173.75
    }
  ]
}
```

---

### 7. Generate Amortization Schedule
**POST** `/loans/{id}/schedule/generate`

Generate a new amortization schedule for a loan based on current terms.

**Example Request:**
```bash
POST /api/v1/loans/1/schedule/generate
```

**Example Response (201 Created):**
```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "message": "Schedule generated successfully",
    "entries_created": 60
  }
}
```

---

### 8. Delete Schedule Entries After Date
**DELETE** `/loans/{id}/schedule/after/{date}`

Delete all schedule entries after a specific date (useful after events).

**Path Parameters:**
| Parameter | Type | Format | Description |
|-----------|------|--------|-------------|
| id | integer | - | Loan ID |
| date | string | YYYY-MM-DD | Cutoff date |

**Example Request:**
```bash
DELETE /api/v1/loans/1/schedule/after/2025-06-01
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "message": "Schedule entries deleted",
    "entries_deleted": 36
  }
}
```

---

## Event Handling Endpoints

### 9. List All Events
**GET** `/events`

Retrieve all events in the system.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| offset | integer | 0 | Skip n records |
| limit | integer | 20 | Max records |

**Example Request:**
```bash
GET /api/v1/events?offset=0&limit=20
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": [
    {
      "id": 1,
      "loan_id": 1,
      "event_type": "extra_payment",
      "amount": 500,
      "event_date": "2025-02-01"
    }
  ]
}
```

---

### 10. Record a New Event
**POST** `/events/record`

Record a new event on a loan. Events trigger automatic recalculation of the schedule.

**Event Types Supported:**

#### 10.1 Extra Payment
Apply an additional payment to reduce principal.

**Request Body:**
```json
{
  "loan_id": 1,
  "event_type": "extra_payment",
  "amount": 500,
  "date": "2025-02-01"
}
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

#### 10.2 Skip Payment
Defer a payment and extend the loan term.

**Request Body:**
```json
{
  "loan_id": 1,
  "event_type": "skip_payment",
  "date": "2025-02-01"
}
```

**Response:**
```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "event": {
      "id": 2,
      "loan_id": 1,
      "event_type": "skip_payment",
      "event_date": "2025-02-01"
    },
    "loan": {
      "id": 1,
      "months": 61,
      "status": "active"
    }
  }
}
```

#### 10.3 Rate Change
Update the interest rate mid-loan.

**Request Body:**
```json
{
  "loan_id": 1,
  "event_type": "rate_change",
  "new_rate": 0.035,
  "date": "2025-06-01"
}
```

**Response:**
```json
{
  "success": true,
  "status_code": 201,
  "data": {
    "event": {
      "id": 3,
      "loan_id": 1,
      "event_type": "rate_change",
      "new_rate": 0.035,
      "event_date": "2025-06-01"
    },
    "loan": {
      "id": 1,
      "annual_rate": 0.035,
      "status": "active"
    }
  }
}
```

#### 10.4 Loan Modification
Adjust principal or term.

**Request Body:**
```json
{
  "loan_id": 1,
  "event_type": "loan_modification",
  "principal": 32000,
  "months": 62,
  "date": "2025-02-01"
}
```

#### 10.5 Payment Applied
Record a payment received.

**Request Body:**
```json
{
  "loan_id": 1,
  "event_type": "payment_applied",
  "amount": 531.86,
  "date": "2025-02-01"
}
```

#### 10.6 Accrual
Track interest accrual.

**Request Body:**
```json
{
  "loan_id": 1,
  "event_type": "accrual",
  "amount": 112.50,
  "date": "2025-02-01"
}
```

**Error Response (400):**
```json
{
  "success": false,
  "status_code": 400,
  "errors": [
    "amount must be positive",
    "event_type is required"
  ]
}
```

---

### 11. Get Event by ID
**GET** `/events/{id}`

Retrieve a specific event.

**Example Request:**
```bash
GET /api/v1/events/1
```

---

### 12. Delete Event
**DELETE** `/events/{id}`

Delete an event.

**Example Request:**
```bash
DELETE /api/v1/events/1
```

---

## Analysis & Forecasting Endpoints

### 13. Compare Multiple Loans
**GET** `/analysis/compare`

Compare multiple loans side-by-side with detailed metrics.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| loan_ids | string | Yes | Comma-separated loan IDs (e.g., "1,2,3") |

**Example Request:**
```bash
GET /api/v1/analysis/compare?loan_ids=1,2,3
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "loans": [
      {
        "id": 1,
        "principal": 30000,
        "rate": 0.045,
        "months": 60,
        "monthly_payment": 531.86,
        "total_interest": 1951.60,
        "total_cost": 31951.60,
        "ear": 0.0463
      },
      {
        "id": 2,
        "principal": 50000,
        "rate": 0.035,
        "months": 84,
        "monthly_payment": 630.50,
        "total_interest": 2963.00,
        "total_cost": 52963.00,
        "ear": 0.0357
      }
    ],
    "summary": {
      "cheapest_by_interest": 1,
      "shortest_term": 1,
      "lowest_payment": 1
    },
    "totals": {
      "combined_principal": 80000,
      "combined_interest": 4914.60,
      "average_rate": 0.0400
    }
  }
}
```

---

### 14. Forecast Early Payoff
**POST** `/analysis/forecast`

Model the impact of extra payments on loan payoff timeline.

**Request Body:**
```json
{
  "loan_id": 1,
  "extra_payment_amount": 500,
  "frequency": "monthly"
}
```

**Parameters:**
| Parameter | Type | Required | Enum | Description |
|-----------|------|----------|------|-------------|
| loan_id | integer | Yes | - | Target loan ID |
| extra_payment_amount | number | Yes | - | Amount to pay extra each period |
| frequency | string | Yes | monthly, quarterly, annual | How often to make extra payment |

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/v1/analysis/forecast \
  -H "Content-Type: application/json" \
  -d '{
    "loan_id": 1,
    "extra_payment_amount": 500,
    "frequency": "monthly"
  }'
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "loan_id": 1,
    "original_payoff": {
      "months": 60,
      "date": "2030-01-01",
      "total_interest": 1951.60
    },
    "with_extra_payments": {
      "months": 42,
      "date": "2028-07-01",
      "total_interest": 1050.00,
      "total_extra_payments": 6000.00
    },
    "savings": {
      "months_saved": 18,
      "interest_saved": 901.60,
      "percentage_saved": 46.18
    },
    "schedule": [
      {
        "month": 1,
        "payment": 531.86,
        "extra_payment": 500.00,
        "principal": 812.50,
        "interest": 112.50,
        "balance": 24187.50
      }
    ]
  }
}
```

---

### 15. Get Debt Recommendations
**GET** `/analysis/recommendations`

Get debt management recommendations based on loan analysis.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| loan_ids | string | Yes | Comma-separated loan IDs |

**Example Request:**
```bash
GET /api/v1/analysis/recommendations?loan_ids=1,2,3
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "total_debt": 80000,
    "highest_rate_loan": 1,
    "highest_rate": 0.065,
    "analysis": {
      "high_interest_loans": 1,
      "total_high_interest": 30000,
      "consolidation_opportunity": true
    },
    "actions": [
      {
        "type": "prioritize",
        "loan_id": 1,
        "reason": "Highest interest rate (6.5%)",
        "estimated_savings": 5000
      },
      {
        "type": "consider_consolidation",
        "loans": [1, 2],
        "reason": "Could save $2,000 in interest",
        "potential_rate": 0.04
      },
      {
        "type": "extra_payments",
        "loan_id": 1,
        "reason": "High interest rate - extra payments save most",
        "estimated_savings": 3000
      }
    ]
  }
}
```

---

### 16. Get Payoff Timeline
**GET** `/analysis/timeline`

Get visual timeline of debt payoff with milestone dates.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| loan_ids | string | Yes | Comma-separated loan IDs |

**Example Request:**
```bash
GET /api/v1/analysis/timeline?loan_ids=1,2
```

**Example Response:**
```json
{
  "success": true,
  "status_code": 200,
  "data": {
    "start_date": "2025-01-01",
    "end_date": "2030-06-01",
    "total_duration_months": 66,
    "total_debt": 80000,
    "loans": [
      {
        "id": 1,
        "payoff_date": "2029-07-01",
        "months": 54
      },
      {
        "id": 2,
        "payoff_date": "2030-06-01",
        "months": 66
      }
    ],
    "milestones": [
      {
        "percentage": 25,
        "date": "2026-08-01",
        "loans_paid_off": 0
      },
      {
        "percentage": 50,
        "date": "2027-08-01",
        "loans_paid_off": 0
      },
      {
        "percentage": 75,
        "date": "2029-01-01",
        "loans_paid_off": 1
      }
    ]
  }
}
```

---

## Error Codes Reference

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK | Successful GET request |
| 201 | Created | Loan/Event created successfully |
| 400 | Bad Request | Missing required field, invalid value |
| 404 | Not Found | Loan/Event ID doesn't exist |
| 422 | Unprocessable Entity | Validation error (e.g., negative amount) |
| 500 | Internal Server Error | Unexpected server error |

### Common Error Messages

**Missing Required Field:**
```json
{
  "success": false,
  "status_code": 400,
  "errors": ["principal is required"]
}
```

**Invalid Data Type:**
```json
{
  "success": false,
  "status_code": 400,
  "errors": ["annual_rate must be a number"]
}
```

**Loan Not Found:**
```json
{
  "success": false,
  "status_code": 404,
  "errors": ["Loan not found"]
}
```

**Validation Error:**
```json
{
  "success": false,
  "status_code": 422,
  "errors": [
    "principal must be positive",
    "annual_rate must be between 0 and 1"
  ]
}
```

---

## Request/Response Examples

### Complete Workflow Example

**1. Create a loan:**
```bash
POST /api/v1/loans
Content-Type: application/json

{
  "principal": 30000,
  "annual_rate": 0.045,
  "months": 60
}
```

**2. Generate schedule:**
```bash
POST /api/v1/loans/1/schedule/generate
```

**3. Record an event (extra payment):**
```bash
POST /api/v1/events/record
Content-Type: application/json

{
  "loan_id": 1,
  "event_type": "extra_payment",
  "amount": 500,
  "date": "2025-02-01"
}
```

**4. Forecast early payoff:**
```bash
POST /api/v1/analysis/forecast
Content-Type: application/json

{
  "loan_id": 1,
  "extra_payment_amount": 500,
  "frequency": "monthly"
}
```

**5. Get recommendations:**
```bash
GET /api/v1/analysis/recommendations?loan_ids=1
```

---

## Best Practices

1. **Always validate input** - Check response status_code before processing data
2. **Handle errors gracefully** - Check for error response structure
3. **Use pagination** - Always provide offset/limit for list endpoints
4. **Cache when possible** - Analysis endpoints can be expensive; cache results
5. **Batch operations** - Use multiple event types to reduce API calls
6. **Check loan status** - Verify loan is "active" before recording events
7. **Validate dates** - Ensure event dates are after loan start date

---

## Rate Limiting

Currently not implemented. Will be added in future versions.

---

## Versioning

API version is specified in the URL path: `/api/v1/`

Current version: 1.0.0

---

## Support

For issues or questions, please contact: support@ksf-amortization.local
