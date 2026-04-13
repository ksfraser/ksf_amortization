# API Security Hardening Guide

## Current Security Status

✅ **Implemented:**
- Rate limiting (60 req/min per client)
- Input validation on all endpoints
- Authentication middleware
- Request parameter validation
- Caching for GET requests
- Error handling (no detailed error messages)

⚠️ **To Review/Improve:**
- SQL injection prevention (verify parameterized queries)
- CSRF protection for state-changing requests
- CORS configuration
- HTTPS enforcement
- API key management
- OAuth2 token validation
- Sensitive data in logs
- Request body size limits

## Security Best Practices

### 1. Input Validation & Sanitization

**Current Implementation:**
```php
// In BaseController
protected function validate(array $rules): array
{
    foreach ($rules as $field => $fieldRules) {
        $rules[$field] = explode('|', $fieldRules);
    }
    // Validates each field against rules
    return $validated;
}
```

**Supported Validators:**
- `required` - Field must exist
- `string|integer|numeric` - Type checking
- `email|url|date` - Format validation
- `min:X|max:X` - Length/value bounds
- `regex:/pattern/` - Custom pattern matching

**Best Practices:**
```php
// Always validate before using data
$data = $this->validate([
    'email' => 'required|email',
    'amount' => 'required|numeric|min:1|max:1000000',
    'date' => 'required|date',
    'status' => 'required|in:active,pending,completed',
]);

// Don't trust user input
$userId = (int) $this->validate(['user_id' => 'required|integer'])['user_id'];
```

### 2. SQL Injection Prevention

**Current Implementation:**
Repositories use prepared statements:

```php
// In LoanRepository
public function findById($id): ?Loan
{
    $stmt = $this->db->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([$id]); // Parameterized query
    return $this->hydrate($stmt->fetchAssoc());
}
```

**Verification Required:**
- [ ] Audit all database queries for parameterization
- [ ] No string concatenation in WHERE clauses
- [ ] Use placeholder syntax consistently
- [ ] Test with SQL injection payloads

### 3. Cross-Site Request Forgery (CSRF)

**Current Status:** Not implemented

**Implementation Required:**
```php
// Generate CSRF token
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;

// Include in forms
<input type="hidden" name="csrf_token" value="<?= $token ?>">

// Validate in controller
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    return $this->error('Invalid CSRF token', 403);
}
```

**For API Requests:**
```php
// Validate token from header
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'OPTIONS'])) {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$this->validateCSRFToken($token)) {
        return $this->error('CSRF token invalid', 403);
    }
}
```

### 4. Authentication & Authorization

**Current Implementation:**
- API key validation
- Session-based authentication

**Recommended Enhancements:**

**API Key Rotation:**
```php
// Expire old keys periodically
if ($apiKey->created_at < now()->subMonths(3)) {
    // Notify user to generate new key
    $notification->send($user, new ApiKeyExpiring($apiKey));
}
```

**OAuth2 Improvements:**
```php
// Validate token expiry
$token = $this->validateOAuthToken($claim);
if ($token->expires_at < now()) {
    return $this->error('Token expired', 401);
}

// Validate token scope
if (!in_array($requiredScope, $token->scopes)) {
    return $this->error('Insufficient permissions', 403);
}
```

**Session Security:**
```php
// Set secure session configuration
ini_set('session.cookie_secure', true);      // HTTPS only
ini_set('session.cookie_httponly', true);    // JavaScript cannot access
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.gc_maxlifetime', 3600);     // 1 hour timeout
```

### 5. Data Sensitivity

**Sensitive Fields:**
- Passwords (never log)
- API keys (never log)
- Credit card info (PCI compliance)
- Social security numbers
- Tax IDs

**Logging Rules:**
```php
// SAFE to log
error_log("Loan created: {$loan->id}");
error_log("User authenticated: {$user->id}");

// NEVER log
error_log("API Key: {$apiKey}");  // WRONG!
error_log("Password: {$password}"); // WRONG!

// Sanitize before logging
error_log("Loan amount: " . (int)$amount);
error_log("Email: " . hash('sha256', $email)); // Anonymized
```

**Error Messages:**
```php
// SAFE: Generic error to user
return $this->error('Authentication failed', 401);

// ONLY in debug logs:
error_log("Auth failed for user {$email}: {$exception->getMessage()}");
```

### 6. Rate Limiting

**Current Implementation:**
- 60 requests per minute per client
- Based on user ID (authenticated) or IP (anonymous)

**Enhancement Recommendations:**

```php
// Differentiate rate limits by endpoint severity
$limits = [
    // Expensive operations
    'POST /schedules' => 5,           // 5 per minute
    
    // Moderate operations
    'POST /loans' => 20,              // 20 per minute
    
    // Read operations
    'GET /loans' => 100,              // 100 per minute
    
    // Health check (no limit)
    'GET /health' => PHP_INT_MAX,
];

// Implement exponential backoff
if ($failedAttempts > 5) {
    $backoffTime = min(60, 2 ** $failedAttempts); // Max 60 seconds
    return $this->error("Rate limited. Retry after {$backoffTime}s", 429, [
        'Retry-After' => $backoffTime
    ]);
}
```

### 7. CORS Configuration

**Not Currently Implemented**

**Add to API Bootstrap:**
```php
// Allow specific origins
$allowedOrigins = [
    'https://app.ksf-amortization.com',
    'https://admin.ksf-amortization.com',
    // NOT * (all origins) in production
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400'); // 1 day
}
```

### 8. HTTPS Enforcement

**Critical for Production**

```php
// Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $url", true, 301);
    exit;
}

// Set HSTS header
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
```

### 9. Request Size Limits

**Prevent DOS attacks**

```php
// In API Bootstrap
$maxContentLength = 1024 * 1024; // 1MB

if ($_SERVER['CONTENT_LENGTH'] > $maxContentLength) {
    return $this->error('Request body too large', 413);
}

// Also configure in nginx/php.ini
// php.ini: upload_max_filesize = 10M
// php.ini: post_max_size = 10M
// nginx: client_max_body_size 10M;
```

### 10. Audit Logging

**Track all sensitive operations**

```php
// Create audit log after state changes
$this->auditLog->log([
    'action' => 'loan_created',
    'user_id' => auth()->id(),
    'resource' => 'loans',
    'resource_id' => $loan->id,
    'changes' => $loan->getDirty(),
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'timestamp' => now()->toIso8601String(),
]);
```

**Audit Events to Track:**
- User authentication (login/logout)
- API key generation/revocation
- Permission changes
- Loan creation/updates
- Payment recording
- Report generation (access control)

## Security Checklist

- [ ] All database queries use parameterized statements
- [ ] CSRF tokens generated and validated
- [ ] HTTPS enforced in production
- [ ] API keys rotate every 90 days
- [ ] Rate limiting enabled (verified limits)
- [ ] CORS configured for specific origins only
- [ ] Authentication middleware on protected routes
- [ ] Sensitive fields not logged or exposed
- [ ] Input validation on all endpoints
- [ ] Error messages generic (details in logs only)
- [ ] HSTS headers set
- [ ] Request body size limits enforced
- [ ] Audit logs for sensitive operations
- [ ] No hardcoded secrets in code/config
- [ ] Dependencies regularly updated
- [ ] Penetration testing completed

## Testing Security

### SQL Injection Tests
```bash
# Try SQL injection in email field
curl -X POST http://localhost:8000/api/clients \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@test.com\" OR \"1\"=\"1",
    "name": "John"
  }'

# Should fail validation or be escaped
```

### XSS Prevention Tests
```bash
# Try XSS in text field
curl -X POST http://localhost:8000/api/clients \
  -H "Content-Type: application/json" \
  -d '{
    "name": "<script>alert(\"xss\")</script>",
    "email": "test@test.com"
  }'

# Should be escaped/sanitized
```

### Rate Limit Tests
```bash
# Send 65 requests in 1 minute (limit is 60)
for i in {1..65}; do
  curl http://localhost:8000/api/health -H "Authorization: Bearer token"
done

# 61st request should get 429 Too Many Requests
```

## Tools & Resources

- **OWASP Top 10**: https://owasp.org/www-project-top-ten/
- **PHP Security Guide**: https://www.php.net/manual/en/security.php
- **JWT Validation**: https://tools.ietf.org/html/rfc7519
- **Burp Suite**: For penetration testing
- **OWASP ZAP**: Free security scanning tool

## Incident Response

If security breach detected:

1. **Immediate Actions**
   - Revoke API keys
   - Reset tokens
   - Force password resets
   - Disable compromised accounts

2. **Investigation**
   - Review audit logs
   - Identify affected clients
   - Determine breach scope
   - Analyze attack vector

3. **Communication**
   - Notify affected users
   - Document incident
   - Update security policies
   - Implement preventative measures

4. **Hardening**
   - Patch vulnerabilities
   - Enable additional monitoring
   - Implement new controls
   - Conduct security audit
