# Phase 18 Session 3 - Controller Middleware Integration Plan

**Session Focus:** Integrate authentication middleware into existing API controllers
**Date:** March 31, 2026

---

## Objectives

Protect existing API endpoints with OAuth2 authentication by extending BaseApiController and adding scope validation to all endpoints.

---

## Target Controllers (4)

### 1. AnalysisController
**Location:** `src/Ksfraser/Amortizations/Api/AnalysisController.php`
**Status:** ✅ Already extends BaseApiController
**Endpoints:**
- GET /api/v1/analysis/compare
- POST /api/v1/analysis/forecast
- GET /api/v1/analysis/recommendations
- GET /api/v1/analysis/timeline

**Required Scopes:**
- analysis:read (read operations)
- analysis:advanced (advanced operations like forecasting)

### 2. LoanAnalysisController
**Location:** `src/Ksfraser/Amortizations/Api/LoanAnalysisController.php`
**Status:** 🔄 Needs BaseApiController extension
**Endpoints:**
- analyze()
- getRates()
- compare()

**Required Scopes:**
- loan:read (read operations)
- loan:write (if endpoints support creation/update)

### 3. PortfolioController
**Location:** `src/Ksfraser/Amortizations/Api/PortfolioController.php`
**Status:** 🔄 Needs BaseApiController extension
**Endpoints:**
- analyze()
- retrieve()
- getYield()

**Required Scopes:**
- portfolio:read
- portfolio:write

### 4. ReportingController
**Location:** `src/Ksfraser\Amortizations/Api/ReportingController.php`
**Status:** 🔄 Needs BaseApiController extension
**Endpoints:** (To be determined)

**Required Scopes:**
- report:read
- report:write

---

## Integration Steps

### Step 1: Update AnalysisController
- Already extends BaseApiController ✅
- Add middleware injection to constructor
- Add scope requirements to each endpoint
- Verify token validation

### Step 2: Update LoanAnalysisController
- Extend BaseApiController
- Constructor to accept middleware
- Add endpoint scope checks
- Implement scope verification

### Step 3: Update PortfolioController
- Extend BaseApiController
- Add middleware support
- Protect endpoints with scopes
- Add validation

### Step 4: Update ReportingController
- Extend BaseApiController
- Add endpoints with scopes
- Implement middleware integration

### Step 5: Create API Routing Configuration
- Map endpoints to scope requirements
- Configure middleware for protected routes

### Step 6: End-to-End Integration Tests
- Test authenticated access
- Test scope validation
- Test access denial

---

## Implementation Details

### Middleware Integration Pattern

Each controller will follow this pattern:

```php
class LoanAnalysisController extends BaseApiController
{
    public function __construct(
        AuthenticationMiddleware $authMiddleware,
        LoanAnalysisService $service = null
    ) {
        $this->setAuthMiddleware($authMiddleware)
            ->requireScopes(['loan:read']);
        
        $this->service = $service;
    }
    
    public function analyze(array $request = [], string $bearerToken = ''): ApiResponse
    {
        // Verify authentication and scopes
        if ($error = $this->verifyRequest($bearerToken)) {
            return $error;
        }
        
        // Log for audit trail
        $this->logAccess('analyze', [
            'context' => $this->getRequestContext()
        ]);
        
        // ... endpoint logic
    }
}
```

### Scope Requirements Matrix

| Endpoint | Method | Scope | Description |
|----------|--------|-------|-------------|
| analyze | POST | loan:write | Create loan analysis |
| getRates | GET | loan:read | View interest rates |
| compare | POST | loan:read | Compare loans |
| analyze | POST | portfolio:read | Analyze portfolio |
| retrieve | GET | portfolio:read | Get portfolio |
| getYield | GET | portfolio:read | Calculate yield |

---

## Success Criteria

- [x] All 4 controllers extend BaseApiController
- [ ] Middleware injected in all constructors
- [ ] Scope requirements configured per endpoint
- [ ] Token verification working
- [ ] Scope validation working
- [ ] Error responses for insufficient permissions
- [ ] Audit logging functional
- [ ] Integration tests passing
- [ ] Documentation updated

---

## Files to Modify

```
✅ src/Ksfraser/Amortizations/Api/AnalysisController.php
   - Already done (extends BaseApiController)
   - Verify middleware setup

🔄 src/Ksfraser/Amortizations/Api/LoanAnalysisController.php
   - Add BaseApiController extension
   - Add middleware injection
   - Add scope checks

🔄 src/Ksfraser/Amortizations/Api/PortfolioController.php
   - Add BaseApiController extension
   - Add middleware injection
   - Add scope checks

🔄 src/Ksfraser/Amortizations/Api/ReportingController.php
   - Add BaseApiController extension
   - Add middleware injection
   - Add scope checks

📝 src/Api/Routing.php
   - Create/update routing configuration
   - Map endpoints to scopes
```

---

## Timeline

- **Step 1:** AnalysisController verification (5 min)
- **Step 2:** LoanAnalysisController integration (10 min)
- **Step 3:** PortfolioController integration (10 min)
- **Step 4:** ReportingController integration (10 min)
- **Step 5:** API routing configuration (10 min)
- **Step 6:** Integration testing (15 min)

**Total Estimated Time:** ~1 hour

---

## Next: Start Implementation

Ready to begin integrating middleware into the controllers.

