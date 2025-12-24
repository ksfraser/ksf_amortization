# KSF Amortization API - Error Reference Guide

**Version:** 1.0.0  
**Last Updated:** December 2025

---

## Overview

This document provides comprehensive error code documentation for the KSF Amortization API. All errors follow a standardized format and include actionable information for debugging.

---

## HTTP Status Codes

### 2xx Success Codes

#### 200 OK
**When:** Request succeeded and returned data.

**Example:**
```json
{
  "success": true,
  "status_code": 200,
  "data": { /* response data */ }
}
```

#### 201 Created
**When:** Resource was successfully created.

**Example:**
```json
{
  "success": true,
  "status_code": 201,
  "data": { /* created resource */ }
}
```

---

### 4xx Client Error Codes

#### 400 Bad Request
**When:** Request is malformed or has invalid parameters.

**Common Causes:**
- Missing required fields
- Invalid data types
- Malformed JSON in request body
- Invalid query parameters

**Example Error:**
```json
{
  "success": false,
  "status_code": 400,
  "errors": [
    "principal is required",
    "annual_rate must be a number"
  ]
}
```

**Troubleshooting:**
- Verify all required fields are included
- Check that numeric fields are not quoted (numbers, not strings)
- Validate JSON syntax (use JSON validator)
- Ensure parameter names match API specification

**Example Fix:**
```json
{
  "principal": 30000,
  "annual_rate": 0.045,
  "months": 60
}
```

---

#### 404 Not Found
**When:** Requested resource doesn't exist.

**Common Causes:**
- Non-existent loan ID
- Non-existent event ID
- Typo in loan ID

**Example Error:**
```json
{
  "success": false,
  "status_code": 404,
  "errors": ["Loan not found"]
}
```

**Troubleshooting:**
- Verify the loan/event ID exists: `GET /api/v1/loans`
- Check for typos in the ID
- Confirm the resource wasn't deleted
- Verify you're using correct resource type (loan vs event)

**Example:**
```bash
# List all loans to find correct ID
GET /api/v1/loans

# Then use confirmed ID
GET /api/v1/loans/1
```

---

#### 422 Unprocessable Entity
**When:** Request is valid but contains data that violates business rules.

**Common Causes:**
- Negative loan amount
- Interest rate outside valid range
- Invalid event date (before loan start)
- Incompatible state transition

**Example Error:**
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

**Troubleshooting:**
- Review the business rule that was violated
- Validate numeric values are within expected ranges
- For dates: ensure they're after loan start date
- For rates: convert percentage to decimal (4.5% = 0.045)

**Common Validation Rules:**

| Field | Valid Range | Type | Example |
|-------|------------|------|---------|
| principal | > 0 | number | 30000 |
| annual_rate | 0.0 to 1.0 | number | 0.045 |
| months | > 0 | integer | 60 |
| amount | > 0 | number | 500.00 |
| new_rate | 0.0 to 1.0 | number | 0.035 |
| date | After loan start | YYYY-MM-DD | 2025-02-01 |

---

### 5xx Server Error Codes

#### 500 Internal Server Error
**When:** Unexpected server error occurred.

**Common Causes:**
- Database connection failed
- Calculation error in service
- Unhandled exception
- Server misconfiguration

**Example Error:**
```json
{
  "success": false,
  "status_code": 500,
  "errors": ["Internal server error"]
}
```

**Troubleshooting:**
- Check server logs for detailed error message
- Verify database is running and connected
- Try request again (may be temporary)
- Contact support with request details

---

## Field-Specific Validation Errors

### Loan Fields

#### principal Error
**Error:** "principal must be a positive number"
**Cause:** Provided value is negative, zero, or not a number
**Fix:**
```json
{
  "principal": 30000  // Not -30000 or "30000"
}
```

#### annual_rate Error
**Error:** "annual_rate must be between 0 and 1"
**Cause:** Rate is percentage (like 4.5) instead of decimal (0.045)
**Fix:**
```bash
# Wrong (percentage)
annual_rate: 4.5

# Correct (decimal)
annual_rate: 0.045
```

#### months Error
**Error:** "months must be a positive integer"
**Cause:** Months is negative, zero, or a decimal
**Fix:**
```json
{
  "months": 60  // Not 60.5 or "60"
}
```

---

### Event Fields

#### amount Error for Events
**Error:** "amount must be positive"
**Cause:** Event amount is negative or zero
**Fix:**
```json
{
  "event_type": "extra_payment",
  "amount": 500  // Not -500 or 0
}
```

#### event_type Error
**Error:** "event_type must be one of: [list]"
**Cause:** Invalid event type specified

**Valid Event Types:**
- `extra_payment` - Additional payment reduces principal
- `skip_payment` - Skip payment, extend term
- `rate_change` - Change interest rate
- `loan_modification` - Adjust principal or term
- `payment_applied` - Record regular payment
- `accrual` - Track interest accrual

**Fix:**
```json
{
  "event_type": "extra_payment"  // Must match valid list
}
```

#### date Error
**Error:** "date must be after loan start date"
**Cause:** Event date is before loan creation date
**Fix:** Use date after loan was created

```bash
# Get loan creation date
GET /api/v1/loans/1

# Then use date after that
{
  "date": "2025-02-01"
}
```

---

## API Endpoint-Specific Errors

### Loan Endpoints

#### GET /loans - List All Loans
**Possible Errors:**
- `400` - Invalid offset/limit parameters

**Error Message:**
```json
{
  "success": false,
  "status_code": 400,
  "errors": ["offset must be non-negative integer"]
}
```

**Troubleshooting:**
```bash
# Wrong
GET /api/v1/loans?offset=-1&limit=abc

# Correct
GET /api/v1/loans?offset=0&limit=20
```

---

#### POST /loans - Create Loan
**Possible Errors:**
- `400` - Missing fields
- `422` - Invalid field values

**Complete Error Response:**
```json
{
  "success": false,
  "status_code": 400,
  "errors": [
    "principal is required",
    "annual_rate is required",
    "months is required"
  ]
}
```

**Troubleshooting:**
```bash
# Must include all 3 required fields
curl -X POST http://localhost:8000/api/v1/loans \
  -H "Content-Type: application/json" \
  -d '{
    "principal": 30000,
    "annual_rate": 0.045,
    "months": 60
  }'
```

---

#### GET /loans/{id} - Get Specific Loan
**Possible Errors:**
- `400` - Invalid ID format
- `404` - Loan not found

**Fix:**
```bash
# Wrong - loan doesn't exist
GET /api/v1/loans/999

# Get valid loan ID first
GET /api/v1/loans

# Then use valid ID
GET /api/v1/loans/1
```

---

#### PUT /loans/{id} - Update Loan
**Possible Errors:**
- `404` - Loan not found
- `422` - Invalid values in update

**Error Response:**
```json
{
  "success": false,
  "status_code": 422,
  "errors": ["annual_rate must be between 0 and 1"]
}
```

**Troubleshooting:**
```bash
# Verify loan exists first
GET /api/v1/loans/1

# Then update with valid values
PUT /api/v1/loans/1
{
  "annual_rate": 0.040
}
```

---

#### DELETE /loans/{id} - Delete Loan
**Possible Errors:**
- `404` - Loan not found

**Fix:**
```bash
# Verify loan exists
GET /api/v1/loans

# Then delete
DELETE /api/v1/loans/1
```

---

### Schedule Endpoints

#### GET /loans/{id}/schedule - Get Schedule
**Possible Errors:**
- `404` - Loan not found
- `400` - Invalid pagination parameters

**Troubleshooting:**
```bash
# Ensure loan exists
GET /api/v1/loans/1

# Then get schedule with valid pagination
GET /api/v1/loans/1/schedule?offset=0&limit=12
```

---

#### POST /loans/{id}/schedule/generate - Generate Schedule
**Possible Errors:**
- `404` - Loan not found

**Error Message:**
```json
{
  "success": false,
  "status_code": 404,
  "errors": ["Loan not found"]
}
```

---

#### DELETE /loans/{id}/schedule/after/{date} - Delete Schedule After Date
**Possible Errors:**
- `404` - Loan not found
- `400` - Invalid date format

**Error Message:**
```json
{
  "success": false,
  "status_code": 400,
  "errors": ["date must be in YYYY-MM-DD format"]
}
```

**Fix:**
```bash
# Wrong format
DELETE /api/v1/loans/1/schedule/after/02-01-2025

# Correct format
DELETE /api/v1/loans/1/schedule/after/2025-02-01
```

---

### Event Endpoints

#### POST /events/record - Record Event
**Possible Errors:**
- `400` - Missing or invalid fields
- `404` - Loan not found
- `422` - Business rule violation

**Complete Error Example:**
```json
{
  "success": false,
  "status_code": 422,
  "errors": [
    "amount must be positive",
    "date must be after loan start date"
  ]
}
```

**Event Type-Specific Errors:**

**Extra Payment:**
- `"amount is required"` - Must specify payment amount
- `"amount must be positive"` - Payment must be > 0

**Rate Change:**
- `"new_rate is required"` - Must specify new rate
- `"new_rate must be between 0 and 1"` - Invalid rate value

**Loan Modification:**
- `"Must provide at least principal or months"` - No changes specified

---

#### GET /events/{id} - Get Event
**Possible Errors:**
- `404` - Event not found

---

#### DELETE /events/{id} - Delete Event
**Possible Errors:**
- `404` - Event not found

---

### Analysis Endpoints

#### GET /analysis/compare - Compare Loans
**Possible Errors:**
- `400` - Missing loan_ids parameter
- `404` - One or more loans not found

**Error Message:**
```json
{
  "success": false,
  "status_code": 400,
  "errors": ["loan_ids parameter is required"]
}
```

**Fix:**
```bash
# Provide loan IDs as comma-separated list
GET /api/v1/analysis/compare?loan_ids=1,2,3
```

**If Loan Not Found:**
```json
{
  "success": false,
  "status_code": 404,
  "errors": ["Loan 999 not found"]
}
```

**Fix:** Verify all loan IDs exist:
```bash
GET /api/v1/loans
```

---

#### POST /analysis/forecast - Forecast Payoff
**Possible Errors:**
- `400` - Missing required fields
- `404` - Loan not found
- `422` - Invalid forecast parameters

**Error Message:**
```json
{
  "success": false,
  "status_code": 400,
  "errors": [
    "loan_id is required",
    "extra_payment_amount is required",
    "frequency is required"
  ]
}
```

**Frequency Validation:**
```json
{
  "success": false,
  "status_code": 422,
  "errors": ["frequency must be one of: monthly, quarterly, annual"]
}
```

**Fix:**
```bash
POST /api/v1/analysis/forecast
{
  "loan_id": 1,
  "extra_payment_amount": 500,
  "frequency": "monthly"  // Valid: monthly, quarterly, annual
}
```

---

#### GET /analysis/recommendations - Get Recommendations
**Possible Errors:**
- `400` - Missing loan_ids parameter
- `404` - One or more loans not found

---

#### GET /analysis/timeline - Get Timeline
**Possible Errors:**
- `400` - Missing loan_ids parameter
- `404` - One or more loans not found

---

## Error Handling Best Practices

### 1. Always Check Status Code
```javascript
// Correct approach
const response = await fetch('/api/v1/loans');
if (response.status === 200) {
  const data = await response.json();
  console.log(data.data);
} else if (response.status === 404) {
  console.log('Loan not found');
} else if (response.status === 400) {
  const data = await response.json();
  console.log('Validation errors:', data.errors);
}
```

### 2. Check response.success Field
```javascript
const data = await response.json();
if (!data.success) {
  // Handle error
  data.errors.forEach(error => console.log(error));
}
```

### 3. Provide User-Friendly Messages
```javascript
// Map API errors to user-friendly messages
const errorMessages = {
  'principal is required': 'Please enter a loan amount',
  'annual_rate must be between 0 and 1': 'Interest rate must be 0-100%',
  'Loan not found': 'The loan you selected does not exist'
};

data.errors.forEach(error => {
  const message = errorMessages[error] || error;
  showErrorToUser(message);
});
```

### 4. Log Errors for Debugging
```javascript
if (!data.success) {
  console.error('API Error:', {
    endpoint: '/api/v1/loans',
    method: 'POST',
    status: data.status_code,
    errors: data.errors,
    requestBody: requestData
  });
}
```

### 5. Implement Retry Logic
```javascript
async function apiCallWithRetry(url, options, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      const response = await fetch(url, options);
      if (response.status === 500) {
        // Server error - retry
        await new Promise(r => setTimeout(r, 1000 * (i + 1)));
        continue;
      }
      return response;
    } catch (error) {
      if (i === maxRetries - 1) throw error;
    }
  }
}
```

---

## Common Error Scenarios and Solutions

### Scenario 1: Create Loan Fails
**Problem:** POST /loans returns 400 error

**Checklist:**
- [ ] All three fields provided (principal, annual_rate, months)
- [ ] principal is a positive number (not negative, not string)
- [ ] annual_rate is decimal 0-1 (not percentage like 4.5)
- [ ] months is positive integer (not string, not decimal)
- [ ] JSON is properly formatted (valid syntax)

**Example Fix:**
```json
{
  "principal": 30000,
  "annual_rate": 0.045,
  "months": 60
}
```

---

### Scenario 2: Record Event Fails
**Problem:** POST /events/record returns 422 error

**Checklist:**
- [ ] Loan exists (verify with GET /loans/{id})
- [ ] Event date is after loan creation
- [ ] Amount is positive for payment events
- [ ] Event type matches valid list
- [ ] New rate is 0-1 for rate_change events

**Debug Steps:**
```bash
# Step 1: Verify loan exists
GET /api/v1/loans/1

# Step 2: Check loan start date
# (compare with event date)

# Step 3: Record event with correct date
POST /api/v1/events/record
{
  "loan_id": 1,
  "event_type": "extra_payment",
  "amount": 500,
  "date": "2025-02-01"
}
```

---

### Scenario 3: Analysis Fails
**Problem:** GET /analysis/compare returns 404 error

**Checklist:**
- [ ] loan_ids parameter provided
- [ ] Loan IDs are comma-separated (no spaces recommended)
- [ ] All loan IDs exist

**Example:**
```bash
# Wrong - no parameter
GET /api/v1/analysis/compare

# Wrong - space after comma
GET /api/v1/analysis/compare?loan_ids=1, 2, 3

# Correct
GET /api/v1/analysis/compare?loan_ids=1,2,3
```

---

## Response Headers

All API responses include standard HTTP headers:

```
Content-Type: application/json
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640000000
```

---

## Support and Debugging

### When Reporting Issues

Provide the following information:

1. **Exact API endpoint** that failed
2. **HTTP method** (GET, POST, PUT, DELETE)
3. **Request body** (if applicable)
4. **Query parameters** (if applicable)
5. **HTTP status code** returned
6. **Full error response** including all error messages
7. **Server logs** if available

### Example Issue Report

```
Endpoint: POST /api/v1/loans
Status: 400
Error: principal is required

Request Body:
{
  "annual_rate": 0.045,
  "months": 60
}

Expected: Loan created with ID
Actual: Missing principal field error
```

---

## Troubleshooting Checklist

- [ ] Is the API server running?
- [ ] Is database connected?
- [ ] Are all required fields included?
- [ ] Are data types correct (number vs string)?
- [ ] Are values within valid ranges?
- [ ] Does resource exist (for GET/PUT/DELETE)?
- [ ] Is date format correct (YYYY-MM-DD)?
- [ ] Are percentage values converted to decimals?
- [ ] Is JSON syntax valid?
- [ ] Are special characters properly escaped?

---

## Contact

For support with API errors not covered here, contact:
- **Email:** api-support@ksf-amortization.local
- **Documentation:** https://docs.ksf-amortization.local
- **Issues:** https://github.com/ksf-amortization/issues
