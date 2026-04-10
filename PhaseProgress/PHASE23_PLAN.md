# Phase 23: Route Integration & Page Templates - Plan

**Planned Date:** Week of April 13, 2026  
**Duration:** 2 weeks (5-7 development days)  
**Focus:** Route setup, page layouts, and E2E testing infrastructure

---

## Phase 23 Objectives

### Primary Goals
1. Integrate Phase 22 components into page templates with Vue Router
2. Create layout system (BaseLayout, AuthLayout, AdminLayout)
3. Implement routing structure for all major sections
4. Set up E2E testing with Playwright
5. Validate user flows end-to-end

### Success Criteria
- [ ] All routes configured and working
- [ ] Page templates created and rendering
- [ ] E2E test suite with 20+ user flow tests
- [ ] 100% route coverage
- [ ] Navigation working across all pages
- [ ] Performance within acceptable range
- [ ] Tests passing on CI/CD

---

## Week 1: Route Integration & Layouts (Days 1-3)

### Day 1: Route Setup & File Structure

**Deliverables:**
- [x] Create router configuration (`router/index.js`)
- [x] Define route structure
  - `/` - Login redirect or dashboard
  - `/login` - LoginForm page
  - `/consent/:clientId` - Consent page
  - `/dashboard` - User dashboard
  - `/profile` - ProfileView page
  - `/admin` - Admin section (role-protected)
  - `/admin/tokens` - Token management
  - `/admin/consents` - Consent/permission management
  - `/admin/clients` - ClientList page
  - `/admin/clients/new` - ClientForm (create)
  - `/admin/clients/:id` - ClientForm (edit)
  - `/admin/metrics` - MetricsDashboard
- [x] Implement route guards (authentication, role-based)
- [x] Create NotFound page (404)
- [x] Create ErrorBoundary component

**Files to Create:**
- `src/router/index.js` - Main router configuration
- `src/router/guards.js` - Route protection middleware
- `src/pages/NotFound.vue` - 404 page
- `src/pages/ErrorBoundary.vue` - Error handling

**Tests:** Route guard tests, redirect logic, 404 page

### Day 2: Layout Components

**Deliverables:**
- [x] Create BaseLayout component
  - Header with TopNavigation
  - Sidebar navigation (optional)
  - Main content area
  - Footer
- [x] Create AuthLayout (for login/consent)
- [x] Create AdminLayout (with admin navigation)
- [x] Implement layout switching based on routes
- [x] Create responsive breakpoints

**Files to Create:**
- `src/layouts/BaseLayout.vue` - Main application layout
- `src/layouts/AuthLayout.vue` - Authentication pages layout
- `src/layouts/AdminLayout.vue` - Admin section layout
- `src/components/common/Footer.vue` - Footer component
- `src/components/common/Sidebar.vue` - Sidebar navigation

**Tests:** Layout rendering, nested components, responsive behavior

### Day 3: Page Templates & Integration

**Deliverables:**
- [x] Create page components using Phase 22 components
  - `src/pages/Dashboard.vue` - Main user dashboard
  - `src/pages/Login.vue` - Wraps LoginForm
  - `src/pages/Consent.vue` - Wraps ConsentForm
  - `src/pages/Profile.vue` - Wraps ProfileView
  - `src/pages/Tokens.vue` - Token management page
  - `src/pages/Consents.vue` - Consent management page
  - `src/pages/AdminClients.vue` - Wraps ClientList
  - `src/pages/AdminMetrics.vue` - Wraps MetricsDashboard
- [x] Test all page loads and component rendering
- [x] Verify navigation between pages

**Files to Create:**
- `src/pages/Dashboard.vue`
- `src/pages/Login.vue`
- `src/pages/Consent.vue`
- `src/pages/Profile.vue`
- `src/pages/Tokens.vue`
- `src/pages/Consents.vue`
- `src/pages/AdminClients.vue`
- `src/pages/AdminMetrics.vue`

**Tests:** Page component rendering, props passing, store integration

---

## Week 2: E2E Testing & Refinement (Days 4-7)

### Day 4-5: E2E Testing Setup & User Flows

**Deliverables:**
- [x] Install and configure Playwright
- [x] Create E2E test suite structure
- [x] Write 20+ user flow tests:
  - Login flow (invalid credentials, success)
  - Logout flow
  - Profile viewing and editing
  - Password change flow
  - Consent approval/denial
  - Admin client management (list, create, delete)
  - Navigation between pages
  - Error scenarios
- [x] Set up test runners (headless, headed, debugging)

**Files to Create:**
- `frontend/e2e/tests/auth.spec.js` - Authentication flows
- `frontend/e2e/tests/profile.spec.js` - Profile management flows
- `frontend/e2e/tests/admin.spec.js` - Admin section flows
- `frontend/e2e/tests/navigation.spec.js` - Navigation and routing flows
- `frontend/e2e/fixtures/user.js` - Test data and fixtures
- `playwright.config.js` - Playwright configuration

**Test Categories:**
- Authentication (5+ tests)
- User Profile (5+ tests)
- Admin Operations (5+ tests)
- Navigation (3+ tests)
- Error Handling (2+ tests)

### Day 6: Performance & Optimization

**Deliverables:**
- [x] Measure and optimize component load times
- [x] Implement lazy loading for routes
- [x] Check bundle size
- [x] Optimize images and assets
- [x] Measure Core Web Vitals

**Tools to Use:**
- Lighthouse for performance audit
- Webpack Bundle Analyzer
- Chrome DevTools profiling

### Day 7: Final Testing & Documentation

**Deliverables:**
- [x] Run full test suite (unit + E2E)
- [x] Fix any failing tests
- [x] Generate coverage reports
- [x] Update documentation
- [x] Create Phase 23 completion report

---

## Expected Deliverables

### Main Deliverables
```
src/
├── router/
│   ├── index.js                 (Main router config)
│   └── guards.js                (Auth/role guards)
├── layouts/
│   ├── BaseLayout.vue
│   ├── AuthLayout.vue
│   └── AdminLayout.vue
├── pages/
│   ├── Dashboard.vue
│   ├── Login.vue
│   ├── Consent.vue
│   ├── Profile.vue
│   ├── Tokens.vue
│   ├── Consents.vue
│   ├── AdminClients.vue
│   ├── AdminMetrics.vue
│   ├── NotFound.vue
│   └── ErrorBoundary.vue
├── components/
│   └── common/
│       ├── Footer.vue
│       └── Sidebar.vue
└── App.vue                      (Updated for routing)

frontend/e2e/
├── tests/
│   ├── auth.spec.js
│   ├── profile.spec.js
│   ├── admin.spec.js
│   └── navigation.spec.js
├── fixtures/
│   └── user.js
└── playwright.config.js

tests/unit/pages/               (Unit tests for pages)
tests/unit/layouts/             (Unit tests for layouts)
```

### Git Commits Expected
- Day 1: "Phase 23 Day 1: Setup routing and route guards"
- Day 2: "Phase 23 Day 2: Implement layout components"
- Day 3: "Phase 23 Day 3: Create page templates and integrate components"
- Day 4: "Phase 23 Day 4: Setup E2E testing with Playwright"
- Day 5: "Phase 23 Day 5: Implement user flow E2E tests"
- Day 6: "Phase 23 Day 6: Performance optimization and Core Web Vitals"
- Day 7: "Phase 23 Day 7: Final testing and Phase 23 completion"

---

## Technical Implementation Details

### Router Configuration Pattern
```javascript
// src/router/index.js
const routes = [
  {
    path: '/',
    component: BaseLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        component: Dashboard,
        name: 'Dashboard',
      },
      {
        path: 'profile',
        component: Profile,
        name: 'Profile',
      },
      // ... more routes
    ],
  },
  {
    path: '/auth',
    component: AuthLayout,
    children: [
      {
        path: 'login',
        component: Login,
        name: 'Login',
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    component: NotFound,
    name: 'NotFound',
  },
]

// Route guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' })
  } else if (to.meta.requiresRole && 
             !authStore.hasRole(to.meta.requiresRole)) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})
```

### Layout Pattern
```vue
<!-- src/layouts/BaseLayout.vue -->
<template>
  <div class="min-h-screen flex flex-col">
    <TopNavigation />
    
    <div class="flex-1 flex">
      <Sidebar v-if="showSidebar" />
      
      <main class="flex-1 p-6">
        <transition name="fade" mode="out-in">
          <router-view />
        </transition>
      </main>
    </div>
    
    <Footer />
  </div>
</template>
```

### Page Pattern
```vue
<!-- src/pages/Dashboard.vue -->
<template>
  <div>
    <h1>Dashboard</h1>
    <!-- Page-specific content and components -->
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const dashboardData = ref(null)

onMounted(async () => {
  // Load page-specific data
})
</script>
```

### E2E Test Pattern
```javascript
// frontend/e2e/tests/auth.spec.js
import { test, expect } from '@playwright/test'

test('user can login with valid credentials', async ({ page }) => {
  await page.goto('http://localhost:5173/login')
  
  await page.fill('input[type="email"]', 'user@example.com')
  await page.fill('input[type="password"]', 'password123')
  
  await page.click('button[type="submit"]')
  
  await page.waitForURL('http://localhost:5173/dashboard')
  expect(await page.title()).toContain('Dashboard')
})
```

---

## Dependencies to Install

```bash
# If not already installed
npm install @playwright/test --save-dev
npm install @vitejs/plugin-vue-router --save-dev
npm install vue-router@4 --save
npm install lighthouse --save-dev
```

---

## Pre-Phase 23 Checklist

Before starting Phase 23, ensure:

- [ ] All Phase 22 components tested and working
- [ ] Phase 22 completion report reviewed
- [ ] Git repository up to date and pushed
- [ ] Development environment ready
- [ ] Node version 18+
- [ ] npm/yarn dependencies up to date

---

## Success Metrics for Phase 23

| Metric | Target | Status |
|--------|--------|--------|
| Routes working | 100% | TBD |
| E2E tests passing | 100% | TBD |
| Page load time | < 2s | TBD |
| Lighthouse score | 80+ | TBD |
| Code coverage | 85%+ | TBD |
| Accessibility score | 95+ | TBD |
| Mobile responsive | Pass | TBD |

---

## Contingency Plans

### If route guard implementation takes longer
- Focus on basic routing first, add role-based guards later
- Use simple token check instead of complex permission system

### If E2E tests are slow
- Run E2E tests on CI only, not locally
- Implement test parallelization
- Use mock API responses for faster tests

### If performance is below target
- Implement route-based code splitting
- Lazy load admin section components
- Optimize image loading
- Consider service worker for caching

---

## Appendix: Phase 23 Checklist

### Pre-Implementation
- [ ] Review Phase 22 completion report
- [ ] Plan router structure
- [ ] Design layout components
- [ ] Set up Playwright configuration

### Implementation (Days 1-3)
- [ ] Create router/index.js
- [ ] Create router/guards.js
- [ ] Implement BaseLayout
- [ ] Implement AuthLayout
- [ ] Implement AdminLayout
- [ ] Create all page components
- [ ] Test all routes

### E2E Testing (Days 4-5)
- [ ] Install Playwright
- [ ] Configure playwright.config.js
- [ ] Write authentication tests
- [ ] Write profile flow tests
- [ ] Write admin flow tests
- [ ] Write navigation tests

### Optimization (Day 6)
- [ ] Measure performance
- [ ] Optimize bundle size
- [ ] Improve load times
- [ ] Check Core Web Vitals

### Finalization (Day 7)
- [ ] Run full test suite
- [ ] Generate coverage report
- [ ] Update documentation
- [ ] Commit and push
- [ ] Create Phase 23 completion report

---

**Phase 23 Plan Created:** April 6, 2026  
**Ready for:** April 13, 2026 (or as scheduled)  
**Previous Phase:** Phase 22 ✅ COMPLETE
