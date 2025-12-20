# Phase 15: API Layer Development - Implementation Plan

**Status:** 🚀 In Progress  
**Priority:** Option 3 from user queue  
**Expected Effort:** 6-8 hours  
**Target Test Infrastructure:** BaseTestCase + AdaptorTestCase from Phase 14

---

## Objectives

1. ✅ Design REST endpoint structure (REST conventions)
2. ✅ Implement request/response standardization  
3. ✅ Create comprehensive error handling
4. ✅ Add request validation framework
5. ✅ Implement API versioning strategy (v1, v2, etc.)
6. ✅ Create Swagger/OpenAPI documentation
7. ✅ Build comprehensive API test suite using Phase 14 infrastructure
8. ✅ Document all endpoints with examples

---

## API Endpoint Structure

### Core Endpoints

#### Loans Resource
```
GET    /api/v1/loans              - List all loans (with pagination)
POST   /api/v1/loans              - Create new loan
GET    /api/v1/loans/{id}         - Get loan details
PUT    /api/v1/loans/{id}         - Update loan
DELETE /api/v1/loans/{id}         - Delete loan
```

#### Schedules Resource
```
GET    /api/v1/loans/{loanId}/schedules     - Get payment schedule
POST   /api/v1/loans/{loanId}/schedules     - Generate/regenerate schedule
PUT    /api/v1/loans/{loanId}/schedules/{id} - Update schedule row
DELETE /api/v1/loans/{loanId}/schedules     - Delete schedule rows after date
```

#### Loan Events Resource (Extra Payments, Skip Payments)
```
GET    /api/v1/loans/{loanId}/events       - Get loan events
POST   /api/v1/loans/{loanId}/events       - Record event (extra payment, skip, etc)
GET    /api/v1/loans/{loanId}/events/{id}  - Get event details
DELETE /api/v1/loans/{loanId}/events/{id}  - Delete event
```

#### Analysis Endpoints
```
POST   /api/v1/loans/analyze              - Analyze loan qualification
POST   /api/v1/schedules/compare          - Compare different scenarios
POST   /api/v1/schedules/forecast         - Forecast with extra payments
```

#### Batch Operations
```
POST   /api/v1/loans/batch                - Create multiple loans
POST   /api/v1/schedules/batch            - Batch schedule operations
POST   /api/v1/events/batch               - Batch event recording
```

---

## Request/Response Standardization

### Standard Request Format

```json
{
  "data": {
    // Resource-specific fields
  },
  "meta": {
    "version": "1.0.0",
    "timestamp": "2025-12-17T10:30:00Z",
    "requestId": "req-abc123"
  }
}
```

### Standard Response Format

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Resource data or array of resources
  },
  "meta": {
    "version": "1.0.0",
    "timestamp": "2025-12-17T10:30:00Z",
    "requestId": "req-abc123"
  },
  "pagination": {
    "page": 1,
    "perPage": 20,
    "total": 100,
    "totalPages": 5
  }
}
```

### Error Response Format

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "principal": ["Principal must be greater than 0"],
    "annual_rate": ["Annual rate must be between 0 and 1"]
  },
  "meta": {
    "version": "1.0.0",
    "timestamp": "2025-12-17T10:30:00Z",
    "requestId": "req-abc123"
  }
}
```

---

## Error Handling Strategy

### HTTP Status Codes

| Code | Scenario | Message |
|------|----------|---------|
| 200 | Success | OK |
| 201 | Created | Resource created |
| 204 | No Content | Success (no response body) |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Authentication failed |
| 403 | Forbidden | Permission denied |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource conflict |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | Service temporarily unavailable |

### Exception Handling

```php
ApiException
├── ValidationException (400/422)
├── AuthenticationException (401)
├── AuthorizationException (403)
├── ResourceNotFoundException (404)
├── ConflictException (409)
├── RateLimitException (429)
└── InternalServerException (500)
```

---

## Request Validation Framework

### Validator Classes

```php
class LoanValidator
{
    public function validateCreate(array $data): array {}
    public function validateUpdate(array $data): array {}
    public function validateDelete(string $id): array {}
}

class ScheduleValidator
{
    public function validateCreate(array $data): array {}
    public function validateUpdate(array $data): array {}
}

class EventValidator
{
    public function validateCreate(array $data): array {}
}
```

### Common Validation Rules

- Required fields presence
- Data type validation
- Format validation (dates, emails, URLs)
- Range validation (min/max values)
- Business logic validation (loan state, schedule constraints)

---

## API Versioning Strategy

### Version Structure

```
/api/v1/loans
/api/v2/loans (future)
/api/v3/loans (future)
```

### Backward Compatibility

- Major version changes for breaking changes
- Minor version increments for backward-compatible additions
- Deprecation notices in headers for phase-outs

### Version Header

```
Accept: application/vnd.ksf-amortization.v1+json
API-Version: 1.0.0
```

---

## Implementation Phases

### Phase 15.1: Core API Infrastructure (2 hours)
- [x] ApiResponse standardization
- [x] Request validation framework
- [x] Error handling layer
- [x] API versioning middleware

### Phase 15.2: Loan Endpoints (2 hours)
- [ ] GET /api/v1/loans
- [ ] POST /api/v1/loans
- [ ] GET /api/v1/loans/{id}
- [ ] PUT /api/v1/loans/{id}
- [ ] DELETE /api/v1/loans/{id}

### Phase 15.3: Schedule Endpoints (2 hours)
- [ ] GET /api/v1/loans/{loanId}/schedules
- [ ] POST /api/v1/loans/{loanId}/schedules
- [ ] PUT /api/v1/loans/{loanId}/schedules/{id}
- [ ] DELETE /api/v1/loans/{loanId}/schedules

### Phase 15.4: Event Endpoints (1 hour)
- [ ] GET /api/v1/loans/{loanId}/events
- [ ] POST /api/v1/loans/{loanId}/events
- [ ] GET /api/v1/loans/{loanId}/events/{id}
- [ ] DELETE /api/v1/loans/{loanId}/events/{id}

### Phase 15.5: Comprehensive Tests (1 hour)
- [ ] API endpoint tests using BaseTestCase
- [ ] Request validation tests
- [ ] Error handling tests
- [ ] Integration tests

### Phase 15.6: Documentation (1 hour)
- [ ] Swagger/OpenAPI schema
- [ ] Endpoint examples
- [ ] Quick start guide

---

## Testing Strategy

### Test Structure (Using Phase 14 Infrastructure)

```php
// Base test class for all API tests
abstract class ApiTestCase extends BaseTestCase {
    protected string $baseUrl = '/api/v1';
    protected ApiClient $client;
    
    protected function setUp(): void {
        parent::setUp();
        $this->client = new ApiClient();
    }
}

// Specific endpoint tests
class LoanEndpointTest extends ApiTestCase {
    public function test_list_loans_returns_array() { }
    public function test_create_loan_validates_required_fields() { }
    public function test_create_loan_returns_201() { }
    public function test_get_loan_returns_404_for_missing() { }
    // ... more tests
}
```

### Test Coverage Goals

- ✅ Happy path (successful operations)
- ✅ Validation errors (missing/invalid fields)
- ✅ Not found errors (missing resources)
- ✅ Authorization errors
- ✅ Server errors
- ✅ Edge cases (boundary values, special characters)

---

## Deliverables Checklist

### Core Infrastructure
- [ ] ApiRequest base class
- [ ] ApiResponse standardization  
- [ ] ApiException hierarchy
- [ ] RequestValidator framework
- [ ] ErrorHandler middleware

### Endpoint Controllers
- [ ] LoanController (5 endpoints)
- [ ] ScheduleController (4 endpoints)
- [ ] EventController (4 endpoints)

### Validators
- [ ] LoanValidator
- [ ] ScheduleValidator
- [ ] EventValidator
- [ ] PaginationValidator

### Documentation
- [ ] OpenAPI/Swagger schema
- [ ] Endpoint documentation
- [ ] Error codes reference
- [ ] Usage examples

### Tests
- [ ] ApiTestCase base class
- [ ] LoanEndpointTest (20+ tests)
- [ ] ScheduleEndpointTest (15+ tests)
- [ ] EventEndpointTest (15+ tests)
- [ ] ValidationTest (20+ tests)
- [ ] ErrorHandlingTest (15+ tests)

**Total Expected:** 85+ new test methods, 3,000+ lines of API code

---

## Next Steps

1. ✅ Review and approve this plan
2. Start Phase 15.1: Core API Infrastructure
3. Proceed through phases 15.2-15.6 sequentially
4. Verify all 791+ tests pass (including new API tests)
5. Transition to Phase 16: Feature Development

---

**Status:** Ready to begin Phase 15.1  
**Test Infrastructure:** Phase 14 components ready to use  
**Estimated Completion:** 6-8 hours from start
