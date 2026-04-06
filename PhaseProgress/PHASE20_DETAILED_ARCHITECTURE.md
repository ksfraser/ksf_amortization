# Phase 20: Detailed Implementation Architecture

**Approach:** REST API-First + Vue.js Frontend SPA (Single Page Application)

---

## 📐 Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Vue.js Frontend (SPA)                     │
│  ┌──────────────────┐              ┌──────────────────────┐ │
│  │ User Auth Flow   │              │ Admin Dashboard      │ │
│  │ - Login Screen   │              │ - Client Management  │ │
│  │ - Consent Screen │              │ - Metrics/Analytics  │ │
│  │ - Token Mgmt     │              │ - Audit Log          │ │
│  └──────────────────┘              └──────────────────────┘ │
└──────────────┬───────────────────────────────────────┬───────┘
               │ HTTP REST API Calls                   │
┌──────────────▼───────────────────────────────────────▼───────┐
│              PHP REST API Layer (Controllers)                 │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ /api/v1/                                               │  │
│  │ ├── auth/ (Authorization endpoints)                    │  │
│  │ │   ├── login (POST)                                   │  │
│  │ │   ├── authorize (GET/POST)                           │  │
│  │ │   ├── token (POST)                                   │  │
│  │ │   └── verify (POST)                                  │  │
│  │ ├── admin/ (Admin endpoints)                           │  │
│  │ │   ├── clients (CRUD)                                 │  │
│  │ │   ├── metrics (GET)                                  │  │
│  │ │   ├── audit-log (GET)                                │  │
│  │ │   └── system-health (GET)                            │  │
│  │ └── user/ (User profile)                               │  │
│  │     ├── me (GET)                                       │  │
│  │     ├── consents (GET)                                 │  │
│  │     └── tokens (GET)                                   │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────┬──────────────────────────────────────────┘
                   │
        ┌──────────▼────────────┐
        │  Existing OAuth2      │
        │  Backend Services     │
        │  - Repositories       │
        │  - JWT Manager        │
        │  - PerformanceMetrics │
        └───────────────────────┘
```

---

## 📁 Directory Structure - Phase 20

```
src/Ksfraser/
├── Api/                              (Existing - extend for OAuth2)
│   └── Controllers/
│       ├── AuthorizationController.php     (NEW - User auth flows)
│       ├── AdminController.php             (NEW - Admin operations)
│       ├── MetricsController.php           (NEW - Performance data)
│       └── AuditLogController.php          (NEW - Authorization history)
│
└── Api/Middleware/                   (NEW - API-specific)
    ├── ApiAuthMiddleware.php
    ├── ApiCorsMiddleware.php
    └── ApiResponseMiddleware.php

frontend/                              (NEW - Separate Vue.js app)
├── package.json
├── vite.config.js                    (Build tool)
├── index.html                        (SPA entry point)
├── src/
│   ├── App.vue                       (Root component)
│   ├── main.js                       (Entry point)
│   ├── router.js                     (Vue Router config)
│   ├── api.js                        (API client)
│   ├── store.js                      (State management - Pinia)
│   ├── components/
│   │   ├── auth/
│   │   │   ├── LoginForm.vue
│   │   │   ├── ConsentScreen.vue
│   │   │   ├── TokenDisplay.vue
│   │   │   └── AuthError.vue
│   │   ├── admin/
│   │   │   ├── Dashboard.vue
│   │   │   ├── ClientList.vue
│   │   │   ├── ClientForm.vue
│   │   │   ├── MetricsChart.vue
│   │   │   ├── AuditLog.vue
│   │   │   └── SystemHealth.vue
│   │   └── common/
│   │       ├── Navigation.vue
│   │       ├── Footer.vue
│   │       ├── LoadingSpinner.vue
│   │       └── AlertBox.vue
│   ├── pages/
│   │   ├── Login.vue
│   │   ├── Authorize.vue
│   │   ├── AdminDashboard.vue
│   │   ├── NotFound.vue
│   │   └── Unauthorized.vue
│   ├── styles/
│   │   ├── main.css
│   │   ├── variables.css
│   │   └── responsive.css
│   └── utils/
│       ├── auth.js
│       ├── api.js
│       └── validation.js
├── tests/
│   ├── unit/
│   │   ├── components/
│   │   └── utils/
│   └── e2e/
│       └── authorization.spec.js
└── public/
    ├── favicon.ico
    └── logo.svg

tests/Unit/Api/
├── AuthorizationControllerTest.php   (NEW)
├── AdminControllerTest.php           (NEW)
├── MetricsControllerTest.php         (NEW)
└── AuditLogControllerTest.php        (NEW)

tests/Integration/
└── OAuthUIFlowTest.php               (NEW - Full flow testing)
```

---

## 🔌 REST API Endpoints

### Authentication Endpoints (`/api/v1/auth/`)

```php
POST   /api/v1/auth/login
  ├── Request: { username, password }
  └── Response: { user_id, session_token }

GET    /api/v1/auth/authorize
  ├── Query: { client_id, redirect_uri, scope, state, code_challenge }
  └── Response: { authorization_code, consents_required, user_info }

POST   /api/v1/auth/authorize (consent approval)
  ├── Request: { authorization_code, granted_scopes }
  └── Response: { code, state }

POST   /api/v1/auth/token
  ├── Request: { code, client_id, client_secret, redirect_uri, code_verifier }
  └── Response: { access_token, token_type, expires_in, refresh_token }

POST   /api/v1/auth/verify
  ├── Request: { access_token }
  └── Response: { valid, user_id, scopes, expires_at }

POST   /api/v1/auth/logout
  ├── Request: { access_token }
  └── Response: { success }
```

### User Endpoints (`/api/v1/user/`)

```php
GET    /api/v1/user/me
  ├── Headers: { Authorization: Bearer <token> }
  └── Response: { user_id, email, name, picture, ... }

GET    /api/v1/user/consents
  ├── Headers: { Authorization: Bearer <token> }
  └── Response: [ { client_id, granted_scopes, granted_at, expires_at } ]

POST   /api/v1/user/consents/{client_id}/revoke
  ├── Headers: { Authorization: Bearer <token> }
  └── Response: { success }

GET    /api/v1/user/tokens
  ├── Headers: { Authorization: Bearer <token> }
  └── Response: [ { token_id, scope, created_at, used_at } ]
```

### Admin Endpoints (`/api/v1/admin/`) - Requires admin scope

```php
GET    /api/v1/admin/clients
  ├── Headers: { Authorization: Bearer <admin_token> }
  └── Response: [ { client_id, name, redirect_uris, created_at } ]

POST   /api/v1/admin/clients
  ├── Request: { name, description, redirect_uris, scopes, ... }
  └── Response: { client_id, client_secret }

GET    /api/v1/admin/clients/{client_id}
  └── Response: { Full client details }

PUT    /api/v1/admin/clients/{client_id}
  └── Response: { Updated client }

DELETE /api/v1/admin/clients/{client_id}
  └── Response: { success }

GET    /api/v1/admin/metrics
  ├── Query: { period, metric_type }
  └── Response: { latencies, cache_hit_rates, error_rates, ... }

GET    /api/v1/admin/audit-log
  ├── Query: { limit, offset, filter }
  └── Response: [ { timestamp, user_id, action, client_id, status } ]

GET    /api/v1/admin/health
  └── Response: { status, memory, connections, uptime, ... }
```

---

## 🎨 Vue.js Frontend Structure

### Component Hierarchy

```
App.vue (Root)
├── Router (Vue Router)
│   ├── pages/Login.vue
│   │   └── components/auth/LoginForm.vue
│   ├── pages/Authorize.vue
│   │   └── components/auth/ConsentScreen.vue
│   ├── pages/AdminDashboard.vue
│   │   ├── components/admin/Dashboard.vue
│   │   ├── components/admin/ClientList.vue
│   │   ├── components/admin/MetricsChart.vue
│   │   └── components/admin/AuditLog.vue
│   └── pages/NotFound.vue
└── Global
    ├── components/common/Navigation.vue
    ├── components/common/AlertBox.vue
    └── store (Pinia state management)
```

### Key Vue.js Technologies

- **Vite** - Lightning-fast build tool
- **Vue Router 4** - Client-side routing
- **Pinia** - State management (smaller than Vuex)
- **Axios** - HTTP client for API calls
- **Chart.js** - Metrics visualization
- **TailwindCSS** - Utility-first CSS framework
- **Vitest** - Unit tests
- **Playwright** - E2E tests

---

## 📋 Phase 20 Implementation Timeline

### Week 1: REST API Setup
- [ ] Create API controllers (Auth, Admin, Metrics, AuditLog)
- [ ] Implement all endpoints documented above
- [ ] Add middleware (CORS, authentication, responses)
- [ ] Write API tests (50+)
- [ ] Commit: `api-v1`

### Week 2: Vue.js Foundation + Auth UI
- [ ] Setup frontend project structure
- [ ] Configure Vue Router, Pinia, API client
- [ ] Build Login page + component
- [ ] Build Authorize/Consent page + component
- [ ] Add token display and storage
- [ ] Write Vue component tests (40+)
- [ ] Commit: `frontend-auth-v1`

### Week 3: Admin Dashboard
- [ ] Build Dashboard layout
- [ ] Implement Client Management (CRUD)
- [ ] Build Metrics visualization
- [ ] Implement Audit Log view
- [ ] Add System Health display
- [ ] Write admin tests (50+)
- [ ] Commit: `frontend-admin-v1`

### Week 4: Polish & Testing
- [ ] Full E2E testing (auth flow → admin)
- [ ] Error handling and validation
- [ ] Responsive design refinement
- [ ] Documentation and setup guides
- [ ] Performance optimization
- [ ] Commit & Tag: `v1.0.0`

---

## 🛠️ Technology Stack Summary

| Layer | Technology | Version |
|-------|-----------|---------|
| **Backend** | PHP 7.3+ | - |
| **API** | REST with JSON | v1 |
| **Frontend** | Vue.js | 3.x |
| **Build** | Vite | Latest |
| **Router** | Vue Router | 4.x |
| **State** | Pinia | Latest |
| **HTTP** | Axios | Latest |
| **CSS** | TailwindCSS | v3 |
| **Charts** | Chart.js | Latest |
| **Testing** | Vitest + Playwright | Latest |

---

## ✅ Success Criteria for Phase 20

- ✅ All 12+ REST API endpoints implemented
- ✅ 100+ API tests (50+ controllers, 50+ integration)
- ✅ Vue.js SPA with 3+ main pages
- ✅ 15+ Vue components (auth + admin)
- ✅ 80+ Vue component/unit tests
- ✅ 10+ E2E authorization flow tests
- ✅ Full user authorization flow working end-to-end
- ✅ Full admin dashboard working end-to-end
- ✅ 200+ total new tests
- ✅ Comprehensive documentation
- ✅ Tagged as v1.0.0

---

## 🚀 Ready to Build!

Phase 20 is ready to implement with:
- Generic REST API (no platform dependencies)
- Vue.js frontend SPA
- Both user-facing and admin components
- Quality-focused (200+ tests)
- Full documentation

**Starting now...**
