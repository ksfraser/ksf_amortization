# Phase 18C: Additional OAuth2 Flows Implementation Plan

**Status:** PLANNED  
**Phase:** 18C  
**Focus:** Authorization Code Flow, PKCE, OpenID Connect  
**Timeline:** This session  

---

## 📋 Overview

Phase 18C extends the OAuth2 implementation beyond client credentials to include browser-based and mobile application flows. This is critical for:
- User login mechanisms (Authorization Code flow)
- Mobile app security (PKCE)
- User identity federation (OpenID Connect)

### Current State (Phase 18B Complete)
✅ Client Credentials Grant - Server-to-server authentication  
✅ Refresh Token Mechanism - Long-lived access  
✅ JWT Token Management - firebase/php-jwt integrated  
✅ Scope-based Authorization - Permission model  

### Target State (Phase 18C)
🎯 Authorization Code Flow - User login  
🎯 PKCE Support - Mobile app security  
🎯 OpenID Connect basics - User identity  
🎯 Full test coverage (50+ tests)  
🎯 Production-ready implementation  

---

## 🎯 Implementation Goals

### Goal 1: Authorization Code Flow (RFC 6749 §4.1)
**Purpose:** Secure user login for web browsers  
**Flow:**
1. User visits application
2. App redirects to authorization endpoint
3. User logs in and grants permission
4. Authorization server redirects back with authorization code
5. App exchanges code for access token (backend)
6. App uses access token to access resources

**User Benefit:** Passwords never shared with app  
**Security:** Code valid for 10 minutes, single use  

### Goal 2: PKCE (RFC 7636)
**Purpose:** Protect mobile/public client applications  
**Components:**
- `code_challenge`: SHA256 hash of random code
- `code_verifier`: Original random code
- Prevents authorization code interception

**Implementation:**
- Generate 128-char random code_verifier
- Create SHA256 hash as code_challenge
- Verify on token exchange

### Goal 3: OpenID Connect
**Purpose:** User identity federation  
**Components:**
- ID Token (contains user info)
- UserInfo endpoint (returns user claims)
- Standard claim set (sub, email, name, etc.)

---

## 📊 Architecture

### New Classes

#### 1. AuthorizationCodeGrant
```php
class AuthorizationCodeGrant
{
    public function generateAuthorizationCode($clientId, $userId, $scopes, $redirectUri, $state = null)
    public function validateAuthorizationCode($code, $clientId, $redirectUri)
    public function exchangeCodeForToken($code, $clientId, $clientSecret, $redirectUri)
}
```

#### 2. PKCEHandler
```php
class PKCEHandler
{
    public function generateCodeVerifier(): string
    public function generateCodeChallenge(string $verifier): string
    public function validateCodeChallenge(string $verifier, string $challenge): bool
}
```

#### 3. OpenIDConnectProvider
```php
class OpenIDConnectProvider
{
    public function generateIDToken($userId, $claims, $accessToken)
    public function getUserInfo($accessToken)
    public function getDiscoveryDocument()
}
```

#### 4. AuthorizationEndpoint
```php
class AuthorizationEndpoint
{
    public function handleAuthorizationRequest(array $params): AuthorizationResponse
    public function handleTokenRequest(array $params): TokenResponse
}
```

### Database Tables

```sql
-- Authorization codes (temporary)
CREATE TABLE oauth2_authorization_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(255) UNIQUE NOT NULL,
    client_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255),
    redirect_uri TEXT,
    scopes JSON,
    state VARCHAR(255),
    code_challenge VARCHAR(255),
    expires_at TIMESTAMP,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES oauth2_clients(id)
);

-- User identities (for OpenID Connect)
CREATE TABLE oauth2_user_identities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255),
    name VARCHAR(255),
    given_name VARCHAR(255),
    family_name VARCHAR(255),
    picture_url TEXT,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Consent records (for OAuth2 consent screen)
CREATE TABLE oauth2_user_consents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(255) NOT NULL,
    client_id VARCHAR(255) NOT NULL,
    scopes JSON,
    consented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES oauth2_clients(id)
);
```

---

## 📝 Implementation Phases

### Phase 18C.1: Authorization Code Grant
**Duration:** 1-2 hours  
**Tasks:**
1. [ ] Create AuthorizationCodeGrant class
2. [ ] Implement authorization code generation
3. [ ] Add OAuth2_authorization_codes table migration
4. [ ] Create authorization endpoint (GET /oauth/authorize)
5. [ ] Create token endpoint (POST /oauth/token)
6. [ ] Add parameter validation
7. [ ] Write 15+ unit tests

**Files:**
- `src/Ksfraser/Security/OAuth2/Grant/AuthorizationCodeGrant.php`
- `src/Ksfraser/Security/OAuth2/Endpoint/AuthorizationEndpoint.php`
- `tests/Unit/Security/OAuth2/AuthorizationCodeGrantTest.php`

**Test Cases:**
- Generate authorization code
- Validate authorization code
- Exchange code for token
- Code expiration
- Invalid redirects
- Scope validation
- Client validation
- State parameter handling

### Phase 18C.2: PKCE Support
**Duration:** 45 minutes  
**Tasks:**
1. [ ] Create PKCEHandler class
2. [ ] Implement code_verifier generation (43-128 chars, base64url)
3. [ ] Implement code_challenge creation (SHA256 hash)
4. [ ] Update authorization code generation to store code_challenge
5. [ ] Update token endpoint to validate code_challenge
6. [ ] Add configuration for PKCE requirement
7. [ ] Write 10+ unit tests

**Files:**
- `src/Ksfraser/Security/OAuth2/PKCE/PKCEHandler.php`
- `tests/Unit/Security/OAuth2/PKCEHandlerTest.php`

**Test Cases:**
- Generate valid code_verifier
- Generate valid code_challenge
- Validate matching verifier/challenge
- Reject invalid verifier
- Reject plain code challenge (require S256)
- Optional PKCE support
- Required PKCE enforcement

### Phase 18C.3: OpenID Connect Basics
**Duration:** 1 hour  
**Tasks:**
1. [ ] Create OpenIDConnectProvider class
2. [ ] Implement ID token generation
3. [ ] Add standard claims (sub, email, name, aud, iat, exp)
4. [ ] Create UserInfo endpoint (GET /oauth/userinfo)
5. [ ] Create Discovery endpoint (.well-known/openid-configuration)
6. [ ] Add user identity table migrations
7. [ ] Write 12+ unit tests

**Files:**
- `src/Ksfraser/Security/OAuth2/OpenIDConnect/OpenIDConnectProvider.php`
- `tests/Unit/Security/OAuth2/OpenIDConnectProviderTest.php`

**Test Cases:**
- Generate ID token with claims
- Validate ID token signature
- Return correct user info
- Filter claims based on scopes
- Discovery document format
- Standard claims validation
- Email verification status

### Phase 18C.4: Integration & Testing
**Duration:** 1-2 hours  
**Tasks:**
1. [ ] Integrate all flows into OAuth2Service
2. [ ] Create end-to-end test scenarios
3. [ ] Add API middleware for protected routes
4. [ ] Create example client implementations
5. [ ] Full test suite execution (50+ tests)
6. [ ] Performance validation
7. [ ] Documentation

**Files:**
- `tests/Integration/OAuth2FlowsTest.php`
- `tests/Integration/OpenIDConnectFlowTest.php`
- `docs/OAUTH2_IMPLEMENTATION.md`

---

## 🔐 Security Considerations

### Authorization Code Flow
- ✅ Code expires after 10 minutes
- ✅ Code valid for single use only
- ✅ State parameter prevents CSRF attacks
- ✅ Redirect URI must match exactly
- ✅ Client must authenticate on token exchange

### PKCE
- ✅ Prevents authorization code interception
- ✅ Mandatory for public clients (mobile apps)
- ✅ Optional for confidential clients
- ✅ Uses SHA256 by default

### OpenID Connect
- ✅ ID token signed with same key as access token
- ✅ Sub claim links token to user
- ✅ UserInfo endpoint requires valid access token
- ✅ Claims filtered based on scopes requested

---

## 📊 Test Coverage Target

### Expected Test Results
- AuthorizationCodeGrantTest: 15/15 ✅
- PKCEHandlerTest: 10/10 ✅
- OpenIDConnectProviderTest: 12/12 ✅
- OAuth2IntegrationTest: 13+ ✅
- **TOTAL: 50+ tests** ✅

### End-to-End Scenarios
1. ✅ Authorization Code Flow (complete user login)
2. ✅ PKCE Flow (mobile app scenario)
3. ✅ OpenID Connect Flow (user identity)
4. ✅ Scope reduction (user grants subset)
5. ✅ Token refresh with new scopes
6. ✅ Invalid parameters handling
7. ✅ State validation
8. ✅ Code expiration

---

## 📚 API Reference (Target)

### Authorization Endpoint
```http
GET /oauth/authorize?
  client_id=my-app&
  redirect_uri=https://app.example.com/callback&
  response_type=code&
  scope=openid+email+profile&
  state=xyz123&
  code_challenge=E9Mrozoa2owUednCFIMRN5a4nZ2Lw&
  code_challenge_method=S256

Response: 302 Redirect to:
https://app.example.com/callback?code=abc123&state=xyz123
```

### Token Endpoint
```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
code=abc123&
client_id=my-app&
client_secret=secret123&
redirect_uri=https://app.example.com/callback&
code_verifier=base64url_encoded_verifier

Response:
{
  "access_token": "eyJhbGc...",
  "id_token": "eyJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "refresh123"
}
```

### UserInfo Endpoint
```http
GET /oauth/userinfo
Authorization: Bearer access_token

Response:
{
  "sub": "user123",
  "email": "user@example.com",
  "name": "John Doe",
  "email_verified": true,
  "picture": "https://example.com/photo.jpg"
}
```

### Discovery Document
```http
GET /.well-known/openid-configuration

Response:
{
  "issuer": "https://auth.example.com",
  "authorization_endpoint": "https://auth.example.com/oauth/authorize",
  "token_endpoint": "https://auth.example.com/oauth/token",
  "userinfo_endpoint": "https://auth.example.com/oauth/userinfo",
  "jwks_uri": "https://auth.example.com/.well-known/jwks.json",
  "scopes_supported": ["openid", "email", "profile"],
  "response_types_supported": ["code"],
  "grant_types_supported": ["authorization_code", "refresh_token"]
}
```

---

## 🚀 Success Criteria

### Functionality
- ✅ Authorization Code flow fully working
- ✅ PKCE flow fully working  
- ✅ OpenID Connect basics working
- ✅ All 50+ tests passing
- ✅ Zero security vulnerabilities

### Code Quality
- ✅ 100% test coverage for new classes
- ✅ Following PSR-4 standards
- ✅ Documented with PHPDoc
- ✅ Backward compatible with Phase 18B

### Documentation
- ✅ API documentation complete
- ✅ Example implementations provided
- ✅ Security guide created
- ✅ Integration guide created

---

## 📅 Session Goals

**Primary Goal:** 
Implement Authorization Code flow + PKCE + OpenID Connect basics with full test coverage

**Deliverables:**
1. AuthorizationCodeGrant class (fully tested)
2. PKCEHandler class (fully tested)
3. OpenIDConnectProvider class (fully tested)
4. Integration with OAuth2Service
5. 50+ unit tests (all passing)
6. Complete documentation

**Success Metric:**
- All 50+ tests passing ✅
- Code ready for production deployment ✅

---

## 🔗 Related Documentation

- [Phase 18B: Production Upgrade](PHASE18B_COMPLETE.md)
- [Phase 18: OAuth2 Implementation Guide](PHASE18_OAUTH2_IMPLEMENTATION_GUIDE.md)
- OAuth2 RFC 6749: https://tools.ietf.org/html/rfc6749
- PKCE RFC 7636: https://tools.ietf.org/html/rfc7636
- OpenID Connect: https://openid.net/connect/

---

## 📝 Next Steps

Ready to proceed with Phase 18C implementation?

**Immediate Actions:**
1. [ ] Create database migration for authorization codes
2. [ ] Create AuthorizationCodeGrant class
3. [ ] Create PKCEHandler class
4. [ ] Write unit tests
5. [ ] Create OpenIDConnectProvider
6. [ ] Integration testing
7. [ ] Documentation

**Ready to start?** ✨
