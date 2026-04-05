# Phase 18: API Authentication & Project Cleanup - Implementation Plan

**Date:** April 3, 2026  
**Status:** In Progress  
**Duration:** 4-5 days  
**Focus:** OAuth2 Authentication + Composer Submodule Cleanup

---

## Part 1: Project Cleanup - Submodule to Packagist Transition

### Current State Analysis

**Existing Submodules:**
1. `vendor-src/ksf_amortization_core` → https://github.com/ksfraser/ksf_amortization_core.git
2. `vendor-src/ksf_amortization` → https://github.com/ksfraser/ksf_amortization.git
3. `modules/wordpress` → https://github.com/ksfraser/ksf_amortization_wp.git
4. `modules/suitecrm` → https://github.com/ksfraser/ksf_amortization_suitecrm.git
5. `ksfraser-html` (special path reference) - Available in software-devel

**Issues with Current Setup:**
- Submodule dependencies scattered across vendor-src and modules/
- Autoload paths pointing to multiple locations
- Development workflow requires submodule initialization
- Difficult to manage package versioning

### Cleanup Strategy

**Approach:**
1. Move ksfraser-html to standalone package in software-devel/
2. Remove submodule references from .gitmodules
3. Update composer.json to require packages from Packagist
4. Configure path repositories for local development
5. Document development workflow

**Development Workflow (Post-Cleanup):**
```
For local development:
1. Edit code in software-devel/ksfraser-html/ (or other package)
2. Commit and push to GitHub
3. Run: composer update ksfraser/html

For production:
- Composer pulls directly from Packagist
```

---

## Part 2: Phase 18 - API Authentication & Security

### OAuth2 Implementation

**Objectives:**
1. Implement OAuth2 authentication service
2. Create JWT token management system
3. Add token validation middleware
4. Define API scopes and permissions
5. Implement audit logging
6. Create comprehensive test suite

### Architecture Pattern

```
└── API Request
    ├── Token Validation Middleware
    │   ├── Check Bearer token
    │   ├── Validate JWT signature
    │   ├── Check token expiration
    │   └── Verify scopes
    ├── Authentication Service
    │   ├── Generate tokens
    │   ├── Manage refresh tokens
    │   └── Handle token revocation
    ├── Scope Manager
    │   ├── Define permissions
    │   ├── Check scope access
    │   └── Log access attempts
    └── Route Handler
```

### Security Implementation Checklist

- [ ] OAuth2 Service implementation
- [ ] JWT token generation
- [ ] Token validation middleware
- [ ] Scope-based access control
- [ ] Rate limiting
- [ ] Audit logging
- [ ] Comprehensive test coverage
- [ ] Security documentation

---

## Timeline

**Day 1-2:** Project cleanup (submodule transition)
**Day 3-4:** OAuth2 & token management implementation
**Day 5:** Testing, documentation, and cleanup
