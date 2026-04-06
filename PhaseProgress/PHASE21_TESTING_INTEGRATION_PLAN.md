# Phase 21: Testing & Integration Plan
**Status:** IN PROGRESS  
**Start Date:** April 5, 2026  
**Target Completion:** April 20, 2026 (14 days)

---

## Overview
Phase 21 focuses on comprehensive testing and integration of the Phase 20 Vue.js SPA with the backend PHP REST API. This phase ensures code quality, reliability, and seamless user experience across all features.

### Phase 20 Context (What We're Testing)
- **Frontend:** 25 Vue.js components, 14 pages, 4 Pinia stores, Vue Router
- **Backend:** 4 PHP controllers with 18+ REST endpoints
- **Architecture:** REST API-First SPA with JWT authentication, OAuth2

---

## Testing Strategy

### 1. Unit Testing (Vitest) - 3-4 Days
**Target:** 80% component coverage

| Component Type | Count | Status |
|---|---|---|
| Common Components | 5 | Not Started |
| Auth Components | 3 | Not Started |
| Admin Components | 3 | Not Started |
| Page Components | 14 | Not Started |
| Stores (Pinia) | 4 | Not Started |
| Utils/Helpers | 2 | Not Started |
| Router | 1 | Not Started |
| **Total** | **32** | **0/32** |

#### What to Test per Component
- ✅ Props validation & defaults
- ✅ Computed properties & watchers
- ✅ Method execution & state updates
- ✅ Event emissions (emits)
- ✅ Conditional rendering (v-if, v-show)
- ✅ List rendering (v-for)
- ✅ Form handling & validation
- ✅ API integration (mocked with MSW)
- ✅ Error scenarios & edge cases
- ✅ Accessibility (ARIA labels, semantic HTML)

---

### 2. Backend Route Registration - 1-2 Days
**Endpoints to Register:** 18+

| Controller | Endpoints | Status |
|---|---|---|
| AuthorizationController | 6 | Not Started |
| AdminController | 6 | Not Started |
| MetricsController | 6 | Not Started |
| UserController | 5 | Not Started |
| **Total** | **23** | **0/23** |

#### Route Implementation
- ✅ HTTP method (GET, POST, PUT, DELETE)
- ✅ URL path & parameters
- ✅ Request validation (JSON schema)
- ✅ Authentication middleware (JWT)
- ✅ Authorization middleware (RBAC)
- ✅ Error handling & responses
- ✅ CORS headers
- ✅ Rate limiting (if needed)

---

### 3. Integration Testing - 2-3 Days
**Scenarios:** 15-20 test cases

| Scenario | Type | Status |
|---|---|---|
| User Login Flow | Happy Path | Not Started |
| OAuth2 Consent Process | Happy Path | Not Started |
| Token Refresh | Auth | Not Started |
| Admin Client CRUD | Happy Path | Not Started |
| Invalid Login | Error | Not Started |
| Unauthorized Access | Error | Not Started |
| Expired Token | Error | Not Started |
| API Error Handling | Error | Not Started |
| Concurrent Requests | Stress | Not Started |
| Offline Fallback | Edge Case | Not Started |

#### Test Structure
```
integration/
├── auth.spec.js          # Login, logout, token refresh
├── oauth.spec.js         # OAuth2 flows
├── admin.spec.js         # Client management, user management
├── metrics.spec.js       # Metrics & analytics
└── error-handling.spec.js # Error scenarios
```

---

### 4. E2E Testing (Playwright) - 3-4 Days
**Goal:** User journey validation in real browser

| Journey | Steps | Status |
|---|---|---|
| Complete User Login | 5 steps | Not Started |
| Admin Client Setup | 6 steps | Not Started |
| User Token Management | 5 steps | Not Started |
| Metrics Dashboard | 4 steps | Not Started |

#### Test Tools Setup
- Playwright v1.40+
- Headless Chrome/Firefox
- Visual regression (optional)
- Performance monitoring (optional)

---

### 5. Production Optimization - 1-2 Days

| Optimization | Target | Status |
|---|---|---|
| Bundle Size | < 200KB gzipped | Not Started |
| Lighthouse Score | > 90 | Not Started |
| Code Coverage | 80%+ | Not Started |
| Component Load Time | < 3s | Not Started |
| API Response Time | < 500ms | Not Started |

---

## Test Coverage Goals

### By Component Type
| Type | Current | Target | Gap |
|---|---|---|---|
| Common Components | 0% | 90% | 90% |
| Auth Components | 0% | 95% | 95% |
| Admin Components | 0% | 85% | 85% |
| Pages | 0% | 70% | 70% |
| Stores | 0% | 90% | 90% |
| Utilities | 0% | 100% | 100% |
| **Overall** | **0%** | **80%** | **80%** |

### Coverage Targets
- **Statements:** 80%
- **Branches:** 75%
- **Functions:** 80%
- **Lines:** 80%

---

## Phase Dependencies

### Passed From Phase 20
✅ Vue.js 3 components (25)
✅ PHP REST controllers (4)
✅ Pinia stores (4)
✅ Vue Router (20+ routes)
✅ Vitest configured

### Blocked By
⏳ None - Ready to test immediately

### Blocks for Phase 22 (Deployment)
- ⏳ 80%+ test coverage
- ⏳ All integration tests passing
- ⏳ E2E tests green
- ⏳ Bundle size optimized

---

## Success Criteria

### Unit Testing
- [ ] 25+ components tested
- [ ] 80%+ code coverage
- [ ] All tests passing
- [ ] 0 critical bugs

### Backend Routes
- [ ] All 23 endpoints registered
- [ ] Request/response validation
- [ ] Error handling working
- [ ] CORS configured

### Integration
- [ ] API + Frontend integration working
- [ ] Authentication flow tested
- [ ] Error scenarios handled
- [ ] No race conditions

### E2E
- [ ] User journeys pass
- [ ] Performance acceptable
- [ ] Mobile responsive
- [ ] Accessibility compliant

### Optimization
- [ ] Bundle < 200KB
- [ ] Lighthouse > 90
- [ ] Page load < 3s
- [ ] Zero console errors

---

## Daily Breakdown

### Day 1-2: Unit Tests (Common Components)
- Setup Vitest infrastructure
- Mock API with MSW
- Test Button, Modal, Alert, Loading Overlay, Navigation
- Aim for 90%+ coverage on common components

### Day 3-4: Unit Tests (Specialized Components)
- Test Auth components (LoginForm, ConsentForm, ProfileView)
- Test Admin components (ClientList, ClientForm, MetricsDashboard)
- Test utilities & helpers
- Target 85%+ coverage

### Day 5-6: Page & Store Tests
- Test 14 page components
- Test 4 Pinia stores
- Test Vue Router with guards
- Target 70% coverage for pages, 90% for stores

### Day 7-8: Backend Routes & Middleware
- Register all 23 API endpoints
- Implement request validation
- Add error handling
- CORS configuration

### Day 9-11: Integration Tests
- API + Frontend integration
- Authentication flows
- Error scenarios
- Concurrent requests

### Day 12-14: E2E & Optimization
- Playwright test setup
- User journey tests
- Performance optimization
- Bundle size reduction

---

## Tools & Libraries

### Vitest (Unit & Integration)
```json
{
  "vitest": "^1.0.0",
  "@vue/test-utils": "^2.4.0",
  "happy-dom": "^12.10.0",
  "msw": "^1.3.0",
  "@vitest/coverage-v8": "^1.0.0"
}
```

### Playwright (E2E)
```json
{
  "@playwright/test": "^1.40.0"
}
```

### Performance
```json
{
  "lighthouse": "^10.0.0",
  "web-vitals": "^3.0.0"
}
```

---

## File Structure for Tests

```
tests/
├── unit/
│   ├── components/
│   │   ├── common/
│   │   │   ├── Button.spec.js
│   │   │   ├── Modal.spec.js
│   │   │   ├── Alert.spec.js
│   │   │   ├── LoadingOverlay.spec.js
│   │   │   └── TopNavigation.spec.js
│   │   ├── auth/
│   │   │   ├── LoginForm.spec.js
│   │   │   ├── ConsentForm.spec.js
│   │   │   └── ProfileView.spec.js
│   │   ├── admin/
│   │   │   ├── ClientList.spec.js
│   │   │   ├── ClientForm.spec.js
│   │   │   └── MetricsDashboard.spec.js
│   │   └── pages/
│   │       ├── LoginPage.spec.js
│   │       ├── DashboardPage.spec.js
│   │       ├── AdminDashboardPage.spec.js
│   │       └── ... (14 page tests)
│   ├── stores/
│   │   ├── auth.spec.js
│   │   ├── clients.spec.js
│   │   ├── metrics.spec.js
│   │   └── ui.spec.js
│   ├── utils/
│   │   ├── api.spec.js
│   │   └── helpers.spec.js
│   └── router/
│       └── index.spec.js
├── integration/
│   ├── auth.spec.js
│   ├── oauth.spec.js
│   ├── admin.spec.js
│   ├── metrics.spec.js
│   └── error-handling.spec.js
├── e2e/
│   ├── auth.spec.ts
│   ├── admin.spec.ts
│   ├── dashboard.spec.ts
│   └── performance.spec.ts
├── fixtures/
│   ├── mocks.js       # MSW handlers
│   ├── stubs.js       # Test data
│   └── factories.js   # Factory functions
└── setup.js           # Global test setup
```

---

## Risk Assessment

| Risk | Severity | Mitigation |
|---|---|---|
| API not ready | High | Mock with MSW, proceed in parallel |
| Component complexity | Medium | Start with simple components |
| Test flakiness | Medium | Deterministic tests, proper setup/teardown |
| Time overrun | Medium | Prioritize critical paths |
| Browser compatibility | Low | Start with Chrome, expand later |

---

## Next Actions (Immediate)

1. ✅ Create Phase 21 plan (this document)
2. ⏳ Setup Vitest test infrastructure
3. ⏳ Create MSW mock handlers for API
4. ⏳ Write first batch of component tests
5. ⏳ Get to 80%+ coverage

---

## References

- Vitest: https://vitest.dev
- Vue Test Utils: https://test-utils.vuejs.org
- MSW: https://mswjs.io
- Playwright: https://playwright.dev
- Coverage: vitest --coverage

---

**Version:** 1.0  
**Last Updated:** 2026-04-05  
**Owner:** Development Team  
**Status:** ACTIVE
