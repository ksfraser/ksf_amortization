# Phase 18: API Authentication & OAuth2 - Implementation Guide

**Date:** April 3, 2026  
**Status:** Implementation Complete  
**Duration:** 1 session  

---

## Overview

Phase 18 implements comprehensive OAuth2 authentication for the KSF Amortization API. This guide covers the implementation, testing, and deployment of the authentication system.

## Components Implemented

### 1. JWT Token Manager (`JWTTokenManager`)
Handles JWT token generation, validation, and lifecycle management.

**Features:**
- HMAC-SHA256 token signing
- Token expiration validation
- Claims-based token structure
- Secure base64url encoding

**Usage:**
```php
$tokenManager = new JWTTokenManager($secretKey);

// Generate token
$token = $tokenManager->generate([
    'client_id' => 'my-app',
    'scopes' => ['read', 'write'],
    'exp' => time() + 3600,
], 'issuer', 'audience');

// Validate token
$claims = $tokenManager->validate($token, 'issuer', 'audience');
```

### 2. OAuth2 Service (`OAuth2Service`)
Central authentication authority handling OAuth2 flows.

**Features:**
- Client credentials authentication
- Access token generation
- Refresh token management
- Token revocation
- Scope validation

**Usage:**
```php
$oauth2 = new OAuth2Service($tokenManager, $config, $db);

// Authenticate client
$tokenResponse = $oauth2->authenticateClient(
    $clientId,
    $clientSecret,
    ['read', 'write']
);

// Refresh access token
$newToken = $oauth2->refreshAccessToken($refreshToken);

// Validate token
$claims = $oauth2->validateToken($accessToken);
```

### 3. Scope Manager (`ScopeManager`)
Manages API permissions and scope hierarchy.

**Built-in Scopes:**
- `read` - Read-only access
- `write` - Create and modify operations
- `delete` - Delete operations
- `admin` - Full administrative access
- `analytics` - Access to analytics data
- `reporting` - Generate and export reports
- `webhooks` - Webhook management
- `audit` - Access audit logs

**Features:**
- Scope validation
- Scope hierarchy (admin implies others)
- Endpoint-specific scope requirements
- Custom scope support

**Usage:**
```php
$scopeManager = new ScopeManager();

// Check scope
if ($scopeManager->hasScope($grantedScopes, 'write')) {
    // Can write
}

// Require scope (throws on missing)
$scopeManager->requireScope($scopes, 'delete');

// Get endpoint requirements
$requiredScopes = $scopeManager->getEndpointScopes('POST /api/loans');
```

### 4. API Authentication Middleware (`ApiAuthMiddleware`)
Validates Bearer tokens on API requests.

**Features:**
- Bearer token extraction
- Token validation and claims verification
- Scope-based access control
- Audit logging
- Error handling

**Usage:**
```php
$middleware = new ApiAuthMiddleware($oauth2Service, $scopeManager, $db);

// Authenticate request
$context = $middleware->authenticate($headers, $endpoint, $clientIp);

// Require specific scope
$middleware->requireScope('write');

// Get authenticated client info
$clientId = $middleware->getClientId();
$scopes = $middleware->getScopes();
```

## API Endpoint Security

All API endpoints are protected with OAuth2 authentication. Example endpoints:

```
GET /api/loans                     [scope: read]
GET /api/loans/{id}                [scope: read]
POST /api/loans                    [scope: write]
PUT /api/loans/{id}                [scope: write]
DELETE /api/loans/{id}             [scope: delete]

GET /api/analytics/portfolio       [scope: analytics]
GET /api/reports                   [scope: reporting]
POST /api/webhooks                 [scope: webhooks]
GET /api/audit/logs                [scope: audit]
```

## Authentication Flow

### 1. Client Authentication (OAuth2 Client Credentials Grant)

```
Client                             Auth Service
  │                                     │
  ├─────── POST /oauth/token ────────>  │
  │   (client_id, client_secret,        │
  │    scope: "read write")             │
  │                                     │
  │ <───────── Token Response ────────  │
  │  (access_token, refresh_token,      │
  │   expires_in: 3600)                 │
  │                                     │
```

Response:
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "scope": "read write"
}
```

### 2. API Request with Bearer Token

```
Client                             API Server
  │                                     │
  ├─ GET /api/loans ────────────────>  │
  │  Authorization: Bearer ACCESS_TOKEN │
  │                                     │
  │ <────────── 200 OK ───────────────  │
  │   (loan data)                      │
  │                                     │
```

### 3. Token Refresh

```
Client                             Auth Service
  │                                     │
  ├─ POST /oauth/refresh ────────────> │
  │  (refresh_token)                    │
  │                                     │
  │ <─── New Access Token ────────────  │
  │  (new access_token, expires_in)    │
  │                                     │
```

## Database Schema

Run the migration to create required tables:
```bash
mysql -u user -p database < migrations/migration_20260403_001_oauth2_schema.sql
```

**Tables Created:**
- `oauth2_clients` - Registered API clients
- `oauth2_tokens` - Token tracking and revocation
- `auth_logs` - Authentication audit trail
- `api_scopes` - Scope definitions

## Testing

All components include comprehensive unit tests:

```bash
# Run all OAuth2 tests
composer test -- tests/Unit/Security/OAuth2/

# Run specific test suite
composer test -- tests/Unit/Security/OAuth2/JWTTokenManagerTest.php
composer test -- tests/Unit/Security/OAuth2/OAuth2ServiceTest.php
composer test -- tests/Unit/Security/OAuth2/ScopeManagerTest.php
composer test -- tests/Unit/Api/Middleware/ApiAuthMiddlewareTest.php
```

**Test Coverage:**
- JWTTokenManager: 10 test cases
- OAuth2Service: 11 test cases
- ScopeManager: 13 test cases
- ApiAuthMiddleware: 14 test cases

**Total: 48 test cases covering:**
- Token generation and validation
- Token expiration and revocation
- Scope management and hierarchy
- Authentication flows
- Error handling
- Bearer token extraction

## Configuration

### Creating OAuth2 Clients

Insert client credentials into database:

```sql
INSERT INTO oauth2_clients (client_id, client_secret, client_name, scopes)
VALUES (
  'my-app',
  '$2y$10$...hashed_secret...',
  'My Application',
  'read,write,analytics'
);
```

### Secret Key Management

Generate a secure secret key for token signing:

```php
$secretKey = bin2hex(random_bytes(32)); // 64 character hex string
```

**Requirements:**
- Minimum 32 characters
- Should be generated from cryptographically secure random source
- Store in environment configuration
- Rotate periodically

### Token Expiration

Configure in OAuth2Service initialization:

```php
$oauth2 = new OAuth2Service($tokenManager, [
    'tokenExpiry' => 3600,      // 1 hour
    'refreshTokenExpiry' => 604800, // 7 days
]);
```

## Security Best Practices

1. **Secret Key Storage**
   - Never commit secret keys to repository
   - Use environment variables or secure vaults
   - Rotate regularly

2. **Token Storage (Client-side)**
   - Store in secure, httpOnly cookies
   - Never store in localStorage
   - Include CSRF protection

3. **Token Transmission**
   - Always use HTTPS
   - Include Bearer token in Authorization header
   - Never pass tokens in URL parameters

4. **Scope Management**
   - Use minimum required scopes
   - Regularly audit scope grants
   - Implement scope hierarchy for admin access

5. **Audit Logging**
   - All authentication attempts logged
   - Review audit trail regularly
   - Alert on suspicious patterns

6. **Token Expiration**
   - Use short-lived access tokens (1 hour)
   - Longer-lived refresh tokens (7 days)
   - Implement token refresh mechanism

## Troubleshooting

### Token Validation Fails

**Problem:** "Invalid token signature"
- Verify secret key matches
- Check token hasn't been tampered with
- Ensure token format is correct (3 dot-separated parts)

**Problem:** "Token has expired"
- Check server time is synchronized
- Verify token expiration time
- Use token refresh endpoint

### Scope Errors

**Problem:** "Insufficient permissions"
- Verify client has required scope
- Check scope names match definitions
- Review endpoint scope requirements

**Problem:** "Missing required scopes"
- Request additional scopes during authentication
- Verify scope hierarchy (admin includes all)
- Check endpoint registration

## Future Enhancements

- [ ] OAuth2 Authorization Code flow (for user delegation)
- [ ] Implicit grant flow (for SPA applications)
- [ ] Resource owner password credentials (legacy)
- [ ] PKCE support (for mobile apps)
- [ ] OpenID Connect integration
- [ ] Token introspection endpoint
- [ ] Dynamic client registration
- [ ] Rate limiting per scope

## Next Steps

1. **Database Setup**
   - Run OAuth2 schema migration
   - Create initial client credentials

2. **API Integration**
   - Add ApiAuthMiddleware to API routes
   - Implement scope validation on endpoints
   - Test authentication flows

3. **Documentation**
   - Generate API documentation with auth requirements
   - Create client integration guides
   - Document scope usage patterns

4. **Deployment**
   - Configure production secret key
   - Set up monitoring for auth logs
   - Test token refresh in production
   - Document token rotation process
