# KSF Amortization API - Endpoints Reference

## Base URL
```
http://localhost:8000/api/
```

## Authentication

All endpoints except health checks require authentication via one of:

1. **API Key** (header):
   ```
   Authorization: Bearer YOUR_API_KEY
   ```

2. **OAuth2 Token**:
   ```
   Authorization: Bearer YOUR_JWT_TOKEN
   ```

## Response Format

### Success (200-201)
```json
{
  "success": true,
  "data": { /* endpoint-specific data */ },
  "status": 200,
  "timestamp": "2026-04-12T10:30:00Z"
}
```

### Validation Error (422)
```json
{
  "error": true,
  "code": 422,
  "message": "Validation failed",
  "details": {
    "field_name": ["error message"],
    "amount": ["Must be greater than 0"]
  },
  "timestamp": "2026-04-12T10:30:00Z"
}
```

### Server Error (500)
```json
{
  "error": true,
  "code": 500,
  "message": "Internal server error",
  "timestamp": "2026-04-12T10:30:00Z"
}
```

## Endpoints

### Health & Status

#### Health Check
```
GET /health
```
No auth required. Returns service status.

**Response:**
```json
{
  "status": "ok",
  "version": "1.0.0",
  "database": "connected"
}
```

#### API Info
```
GET /info
```
Returns API version and metadata.

### Authentication

#### Login
```
POST /auth/login
```

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "role": "admin"
  }
}
```

#### Logout
```
POST /auth/logout
```
Invalidates current session/token.

#### Verify Token
```
GET /auth/verify
```
Validates current authentication token.

### Loans

#### Create Loan
```
POST /loans
```

**Request:**
```json
{
  "client_id": 1,
  "amount": 50000,
  "interest_rate": 5.5,
  "term_months": 60,
  "payment_frequency": "monthly",
  "start_date": "2026-04-12"
}
```

**Validation Rules:**
- `client_id`: required, integer, exists in clients table
- `amount`: required, numeric, > 0
- `interest_rate`: required, numeric, 0-100
- `term_months`: required, integer, > 0
- `payment_frequency`: required, in: monthly, bi-weekly, weekly
- `start_date`: required, date format YYYY-MM-DD

**Response:**
```json
{
  "id": 123,
  "client_id": 1,
  "amount": 50000,
  "interest_rate": 5.5,
  "term_months": 60,
  "status": "active",
  "created_at": "2026-04-12T10:30:00Z"
}
```

#### Get Loan
```
GET /loans/:id
```

**Parameters:**
- `id` (path): Loan ID

**Response:**
```json
{
  "id": 123,
  "client_id": 1,
  "amount": 50000,
  "interest_rate": 5.5,
  "term_months": 60,
  "status": "active",
  "created_at": "2026-04-12T10:30:00Z",
  "payments_made": 12,
  "next_payment_date": "2026-05-12"
}
```

#### List Loans
```
GET /loans?client_id=1&status=active&page=1&limit=20
```

**Query Parameters:**
- `client_id` (optional): Filter by client
- `status` (optional): active, completed, default
- `page` (optional): Page number (default 1)
- `limit` (optional): Results per page (default 20)

**Response:**
```json
{
  "data": [
    { /* loan objects */ }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 42,
    "pages": 3
  }
}
```

#### Update Loan
```
PUT /loans/:id
```

**Request:**
```json
{
  "status": "completed",
  "notes": "Loan fully paid"
}
```

#### Delete Loan
```
DELETE /loans/:id
```

Permanently removes loan and associated schedules.

### Schedules

#### Generate Schedule
```
POST /loans/:id/schedule
```

Generates complete amortization schedule for a loan.

**Request:**
```json
{
  "recalculate": false,
  "include_interest_breakdown": true
}
```

**Response:**
```json
{
  "schedule_id": "sch_123",
  "loan_id": 123,
  "total_payments": 60,
  "payment_amount": 943.56,
  "total_interest": 6613.60,
  "payments": [
    {
      "period": 1,
      "payment_date": "2026-05-12",
      "principal": 786.40,
      "interest": 229.90,
      "balance": 49213.60
    },
    /* ... more payments ... */
  ]
}
```

#### Get Schedule
```
GET /loans/:id/schedule
```

Retrieves cached schedule (or generates if missing).

#### Get Payment Details
```
GET /loans/:id/schedule/:period
```

Gets specific payment details for a loan period.

### Clients

#### Create Client
```
POST /clients
```

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1-555-0123",
  "address": "123 Main St",
  "city": "Springfield",
  "state": "IL",
  "zip": "62701"
}
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "created_at": "2026-04-12T10:30:00Z"
}
```

#### Get Client
```
GET /clients/:id
```

#### List Clients
```
GET /clients?search=john&page=1&limit=20
```

#### Update Client
```
PUT /clients/:id
```

#### Get Client Loans
```
GET /clients/:id/loans
```

Returns all loans for a specific client.

### Payments

#### Record Payment
```
POST /loans/:id/payments
```

**Request:**
```json
{
  "amount": 943.56,
  "payment_date": "2026-04-12",
  "method": "bank_transfer",
  "reference": "TXN-123456"
}
```

**Response:**
```json
{
  "id": 456,
  "loan_id": 123,
  "amount": 943.56,
  "principal_paid": 786.40,
  "interest_paid": 157.16,
  "payment_date": "2026-04-12",
  "status": "completed"
}
```

#### Get Payment
```
GET /loans/:id/payments/:paymentId
```

#### List Payments
```
GET /loans/:id/payments?status=completed&page=1
```

### Metrics & Reports

#### Get Loan Metrics
```
GET /metrics/loans/:id
```

**Response:**
```json
{
  "loan_id": 123,
  "total_payments": 60,
  "payments_made": 12,
  "payments_remaining": 48,
  "principal_paid": 9436.80,
  "interest_paid": 1894.32,
  "principal_remaining": 40563.20,
  "interest_remaining": 4719.28,
  "next_payment_date": "2026-05-12",
  "next_payment_amount": 943.56
}
```

#### Portfolio Analytics
```
GET /metrics/portfolio?client_id=1
```

Aggregated metrics across loans.

**Response:**
```json
{
  "total_active_loans": 5,
  "total_portfolio_value": 250000,
  "total_principal_remaining": 180000,
  "total_interest_remaining": 45000,
  "weighted_average_rate": 5.2,
  "default_rate": 0.02
}
```

#### Compliance Report
```
GET /reports/compliance?from=2026-01-01&to=2026-04-12
```

Returns compliance audit trail.

**Response:**
```json
{
  "period": {
    "from": "2026-01-01",
    "to": "2026-04-12"
  },
  "events": [
    {
      "id": 1,
      "type": "interest_calculation",
      "loan_id": 123,
      "description": "Daily compound interest applied",
      "timestamp": "2026-04-12T10:30:00Z",
      "user_id": 1
    }
  ]
}
```

### Settings

#### Get Settings
```
GET /settings
```
Admin only. System-wide settings.

#### Update Settings
```
PUT /settings
```
Admin only.

**Request:**
```json
{
  "default_interest_method": "daily_compound",
  "max_term_years": 30,
  "min_loan_amount": 1000,
  "max_loan_amount": 1000000
}
```

### Analytics

#### Dashboard Data
```
GET /analytics/dashboard
```

Comprehensive dashboard metrics for admin.

**Response:**
```json
{
  "summary": {
    "total_clients": 250,
    "active_loans": 180,
    "portfolio_value": 12500000
  },
  "trends": {
    "loans_created_month": 15,
    "payments_processed_month": 280,
    "default_rate_month": 0.008
  },
  "top_clients": [ /* ... */ ]
}
```

## Rate Limiting

Default: 100 requests per minute per API key

**Headers:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1681234567
```

## Error Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Failed |
| 429 | Too Many Requests |
| 500 | Server Error |

## Timestamp Format

All timestamps are ISO 8601 UTC:
```
2026-04-12T10:30:00Z
```

## Pagination

Paginated endpoints return:
```json
{
  "data": [ /* results */ ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "pages": 8
  }
}
```

**Query Parameters:**
- `page`: Page number (1-indexed, default 1)
- `limit`: Results per page (1-100, default 20)

## Sorting

Add `sort` parameter to list endpoints:
```
GET /loans?sort=-created_at,amount
```

Prefix with `-` for descending order.

## Filtering

Complex filters using bracket notation:
```
GET /loans?filter[amount][gt]=1000&filter[status][in]=active,completed
```

Operators: `eq`, `ne`, `gt`, `gte`, `lt`, `lte`, `in`, `nin`, `like`

## Examples

### Create Loan and Generate Schedule

```bash
# 1. Create client
curl -X POST http://localhost:8000/api/clients \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "name": "Jane Smith",
    "email": "jane@example.com"
  }'

# Response: { "id": 10, ... }

# 2. Create loan
curl -X POST http://localhost:8000/api/loans \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "client_id": 10,
    "amount": 100000,
    "interest_rate": 4.5,
    "term_months": 360,
    "payment_frequency": "monthly",
    "start_date": "2026-04-12"
  }'

# Response: { "id": 456, ... }

# 3. Generate schedule
curl -X POST http://localhost:8000/api/loans/456/schedule \
  -H "Authorization: Bearer TOKEN"

# Response: { "payments": [ ... ], ... }
```

### Record Payment

```bash
curl -X POST http://localhost:8000/api/loans/456/payments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{
    "amount": 506.69,
    "payment_date": "2026-05-12",
    "method": "bank_transfer"
  }'
```

### Get Metrics

```bash
curl http://localhost:8000/api/metrics/loans/456 \
  -H "Authorization: Bearer TOKEN"
```
