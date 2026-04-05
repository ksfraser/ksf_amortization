# Phase 18D: OAuth2 HTTP API Integration Plan

## Overview
Phase 18D focuses on exposing the OAuth2 implementation (Authorization Code Flow, PKCE, OpenID Connect) as production-ready HTTP endpoints and integrating with the existing Amortization API.

## Phase 18D Objectives

### 1. HTTP Endpoints Implementation (OAuth2 Endpoints)
Create RESTful endpoints for OAuth2 authorization and token operations:

**Authorization Endpoint** - `/oauth2/authorize` (GET/POST)
- Handles authorization request from clients
- Prompts user for login/consent
- Returns authorization code or error
- PKCE support with code_challenge validation
- State parameter CSRF protection
- Scope approval UI

**Token Endpoint** - `/oauth2/token` (POST)
- Exchanges authorization code for access token
- Handles token refresh requests
- Returns access_token, refresh_token, ID token
- PKCE code_verifier validation
- Client authentication
- Scope enforcement

**UserInfo Endpoint** - `/oauth2/userinfo` (GET)
- Returns authenticated user information
- Requires valid access token
- Returns claims based on granted scopes
- OpenID Connect compliant

**Discovery Endpoint** - `/.well-known/openid-configuration` (GET)
- OpenID Connect metadata
- Endpoint URIs, supported algorithms
- Scope and claim listings
- JWKs URI for public key federation

### 2. API Integration Layer
Integrate OAuth2 with existing Amortization API:

**Create OAuth2 Authorization Middleware**
- Validate access tokens on API requests
- Extract user identity from JWT
- Enforce scope requirements per endpoint
- Rate limiting by client/user

**Scope Mapping to API Resources**
- `amortization:read` - GET endpoints (analysis, reports)
- `amortization:write` - POST/PUT endpoints (create, update)
- `amortization:delete` - DELETE endpoints
- `amortization:admin` - Administrative operations
- `portfolio:read`, `portfolio:write`, `portfolio:delete`
- `market:read`, `market:write`

**Update Existing API Endpoints**
- Add OAuth2 authentication checks
- Validate required scopes
- Return appropriate 401/403 errors
- Support both JWT Bearer tokens and session cookies

### 3. Database Migration Execution
Deploy OAuth2 schema to production:

**Execution Steps**
1. Backup production database
2. Execute authorization_code_flow migration
3. Verify table creation and indexes
4. Test authorization code generation
5. Test token exchange
6. Validate OpenID Connect queries

**Schema Validation**
- Verify oauth2_authorization_codes table
- Verify oauth2_user_identities table
- Verify oauth2_user_consents table
- Check index performance
- Test foreign key constraints

### 4. End-to-End OAuth2 Flow Tests
Comprehensive integration tests:

**Authorization Code Flow (Web)**
- User initiates login → redirect to /authorize
- User authenticates and grants consent
- Server redirects with authorization code
- Client exchanges code for token
- Client requests protected resource
- Server validates token and returns data

**PKCE Flow (Mobile)**
- Client generates code_verifier + challenge
- Requests authorization with code_challenge
- User authenticates
- Client exchanges code + verifier for token
- Validates constant-time challenge verification

**Refresh Token Flow**
- Access token expires (e.g., 1 hour)
- Client uses refresh_token to get new access_token
- Server validates refresh_token and issues new token
- Validates expiration and revocation

**OpenID Connect Flow**
- Include `openid` scope in authorization request
- Server returns ID token with user identity
- Client validates ID token signature
- Extracts user claims (sub, email, name, etc.)

**Scope Enforcement**
- Request limited scopes
- Verify only granted scopes included in token
- Test scope mismatch rejection
- Validate scope filtering in userinfo endpoint

### 5. Performance Testing
Ensure production readiness:

**Load Testing**
- 1000 concurrent authorization requests
- Authorization code generation performance
- Token exchange performance (crypto operations)
- UserInfo endpoint response time
- Redis caching effectiveness (if used)

**Security Testing**
- Authorization code replay prevention
- Token signature validation performance
- PKCE constant-time comparison verification
- SQL injection prevention in scope queries
- CSRF state parameter effectiveness

**Metrics to Capture**
- Average latency per endpoint
- 99th percentile response time
- Peak throughput (requests/second)
- Memory usage under load
- Database query performance

### 6. Documentation & Deployment
Create production deployment materials:

**API Documentation**
- OpenAPI/Swagger specification
- OAuth2 flow diagrams
- CURL examples for each endpoint
- SDK integration guides
- Error response codes

**Deployment Guide**
- Database migration procedure
- Environment variables required
- Configuration for different environments
- Health check endpoints
- Monitoring and logging setup

**Security Guide**
- PKCE requirement policy
- State parameter validation
- CORS configuration
- Rate limiting settings
- Token expiration policies

## Implementation Order

### Priority 1: OAuth2 Endpoints (Days 1-2)
1. Create OAuth2Controller with /authorize endpoint
2. Create /token endpoint
3. Create /userinfo endpoint
4. Create /.well-known/openid-configuration endpoint
5. Basic endpoint tests (20+ tests)

### Priority 2: API Integration (Days 2-3)
1. Create OAuth2Middleware
2. Create ScopeValidator
3. Update existing API fields with @OAuth2Protected annotation
4. Implement scope enforcement per endpoint
5. Integration tests (15+ tests)

### Priority 3: Database & E2E (Days 3-4)
1. Deploy database migration
2. Create E2E authorization code flow test
3. Create E2E PKCE flow test
4. Create E2E refresh token test
5. Create E2E OpenID Connect test
6. E2E integration tests (20+ tests)

### Priority 4: Performance & Documentation (Days 4-5)
1. Performance baseline testing
2. Load testing scenarios
3. Create API documentation (Swagger/OpenAPI)
4. Create deployment guide
5. Create security guide
6. Final validation and sign-off

## Success Criteria

### Functionality (Must Have)
- ✅ All OAuth2 endpoints operational
- ✅ Authorization code flow working end-to-end
- ✅ PKCE validation working
- ✅ Token refresh working
- ✅ OpenID Connect ID tokens valid
- ✅ UserInfo endpoint returns correct claims
- ✅ Scope enforcement working on API endpoints
- ✅ Discovery endpoint returns appropriate metadata

### Testing (Must Have)
- ✅ 50+ new unit/integration tests
- ✅ All tests passing (100% success rate)
- ✅ Code coverage > 85% for new code
- ✅ End-to-end flow validation passing

### Performance (Desired)
- ✅ /token endpoint < 100ms average latency
- ✅ /userinfo endpoint < 50ms average latency
- ✅ /authorize endpoint < 200ms average latency
- ✅ Support 1000+ concurrent requests

### Security (Must Have)
- ✅ All OWASP Top 10 mitigations
- ✅ SQL injection protection
- ✅ CSRF protection
- ✅ Timing attack prevention (PKCE)
- ✅ Token validation on every API call
- ✅ Scope validation before resource access

### Documentation (Must Have)
- ✅ OpenAPI specification
- ✅ Deployment guide
- ✅ API usage examples
- ✅ Security best practices
- ✅ Monitoring and alerting guide

## Estimated Effort
- **Coding:** 12-15 hours
- **Testing:** 8-10 hours
- **Performance/Documentation:** 4-6 hours
- **Total:** 24-31 hours

## Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Performance degradation | High | Early load testing, caching strategy |
| Database migration failure | Critical | Backup, rollback plan tested beforehand |
| Scope mismatch bugs | Medium | Comprehensive test coverage |
| Token validation bugs | Critical | Security-focused testing |
| Integration issues with existing API | Medium | Phased rollout, feature flags |

## Current Status
- Phase 18C: ✅ Complete (82/82 tests passing, committed to GitHub)
- Phase 18D: 📋 Ready to begin

## Next Steps
1. ✅ Review Phase 18D plan
2. Confirm implementation priorities
3. Create OAuth2Controller with endpoints
4. Create middleware and validators
5. Execute database migration
6. Create E2E tests
7. Performance testing
8. Deploy to production
