<?php

namespace Ksfraser\Amortizations\Tests\Authentication;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Authentication\Middleware\AuthenticationMiddleware;
use Ksfraser\Amortizations\Authentication\AuthenticationService;
use Ksfraser\Amortizations\Authentication\ScopeManager;
use Ksfraser\Amortizations\Authentication\TokenManager;
use Ksfraser\Amortizations\Authentication\Client;
use Ksfraser\Amortizations\Authentication\InvalidTokenException;
use Ksfraser\Amortizations\Authentication\Storage\InMemoryTokenStorage;

/**
 * AuthenticationMiddlewareTest - Token validation middleware tests
 *
 * Tests authentication, scope validation, and rate limiting.
 */
class AuthenticationMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $authService;
    protected $scopeManager;
    protected $tokenManager;
    protected $storage;
    protected $client;
    protected $privateKey;
    protected $publicKey;
    protected $token;

    protected function setUp(): void
    {
        // Generate RSA keys
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $this->privateKey);
        $keyDetails = openssl_pkey_get_details($res);
        $this->publicKey = $keyDetails['key'];

        // Create services
        $this->authService = new AuthenticationService(
            $this->privateKey,
            $this->publicKey,
            'test-api'
        );

        $this->scopeManager = new ScopeManager();
        $this->storage = new InMemoryTokenStorage();
        $this->tokenManager = new TokenManager($this->authService, $this->storage);

        // Create middleware
        $this->middleware = new AuthenticationMiddleware(
            $this->authService,
            $this->scopeManager,
            $this->tokenManager
        );

        // Create test client and tokens
        $this->client = new Client('test-client', 'test-secret');
        $result = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read', 'schedule:read']
        );
        $this->token = $result['access_token'];
    }

    // ===== Authentication Tests =====

    public function testAuthenticateWithValidToken(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];

        $authenticated = $this->middleware->authenticate($headers);

        $this->assertTrue($authenticated);
    }

    public function testAuthenticateMissingHeader(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Missing Authorization header');

        $this->middleware->authenticate([]);
    }

    public function testAuthenticateMalformedBearerToken(): void
    {
        $this->expectException(InvalidTokenException::class);

        $headers = ['Authorization' => 'Invalid-Format token'];
        $this->middleware->authenticate($headers);
    }

    public function testAuthenticateWithInvalidToken(): void
    {
        $this->expectException(InvalidTokenException::class);

        $headers = ['Authorization' => 'Bearer invalid.token.here'];
        $this->middleware->authenticate($headers);
    }

    public function testAuthenticateCaseInsensitiveBearer(): void
    {
        $headers = ['Authorization' => "bearer {$this->token}"];

        $authenticated = $this->middleware->authenticate($headers);

        $this->assertTrue($authenticated);
    }

    // ===== Scope Validation Tests =====

    public function testValidateScopeGranted(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        $valid = $this->middleware->validateScope('loan:read');

        $this->assertTrue($valid);
    }

    public function testValidateScopeDenied(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        $valid = $this->middleware->validateScope('admin');

        $this->assertFalse($valid);
    }

    public function testValidateScopeBeforeAuthenticate(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->middleware->validateScope('loan:read');
    }

    public function testValidateScopeAnySuccess(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        $valid = $this->middleware->validateScopeAny(['admin', 'loan:read']);

        $this->assertTrue($valid);
    }

    public function testValidateScopeAllfailsWithPartialScopes(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        // Token has loan:read and schedule:read, but not admin
        $valid = $this->middleware->validateScopeAll(['loan:read', 'admin']);

        $this->assertFalse($valid);
    }

    // ===== Token Access Tests =====

    public function testGetToken(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        $token = $this->middleware->getToken();

        $this->assertNotNull($token);
        $this->assertEquals('test-client', $token->getClientId());
    }

    public function testGetTokenBeforeAuthenticate(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->middleware->getToken();
    }

    public function testGetClientId(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        $clientId = $this->middleware->getClientId();

        $this->assertEquals('test-client', $clientId);
    }

    public function testGetScopes(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        $scopes = $this->middleware->getScopes();

        $this->assertContains('loan:read', $scopes);
        $this->assertContains('schedule:read', $scopes);
    }

    // ===== Context Tests =====

    public function testGetContext(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        $context = $this->middleware->getContext();

        $this->assertTrue($context['authenticated']);
        $this->assertEquals('test-client', $context['client_id']);
        $this->assertIsArray($context['scopes']);
        $this->assertNotEmpty($context['token_jti']);
    }

    // ===== Rate Limiting Tests =====

    public function testCheckRateLimitWithinLimit(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        $withinLimit = $this->middleware->checkRateLimit(10, 60);

        $this->assertTrue($withinLimit);
    }

    public function testCheckRateLimitExceeded(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        // Set very low limit
        for ($i = 0; $i < 3; $i++) {
            $result = $this->middleware->checkRateLimit(2, 60);
            if ($i < 2) {
                $this->assertTrue($result);
            } else {
                $this->assertFalse($result);
            }
        }
    }

    public function testCheckRateLimitBeforeAuthenticate(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->middleware->checkRateLimit();
    }

    // ===== Header Format Tests =====

    public function testAuthenticateWithHTTPAuthorizationHeader(): void
    {
        $headers = ['HTTP_AUTHORIZATION' => "Bearer {$this->token}"];

        $authenticated = $this->middleware->authenticate($headers);

        $this->assertTrue($authenticated);
    }

    public function testAuthenticateLowercaseAuthorizationHeader(): void
    {
        $headers = ['authorization' => "Bearer {$this->token}"];

        $authenticated = $this->middleware->authenticate($headers);

        $this->assertTrue($authenticated);
    }

    // ===== Token Revocation Tests =====

    public function testAuthenticateWithRevokedToken(): void
    {
        $headers = ['Authorization' => "Bearer {$this->token}"];
        $this->middleware->authenticate($headers);

        // Get token JTI and revoke
        $token = $this->middleware->getToken();
        $this->tokenManager->revokeToken($token->getJti());

        // Try to authenticate again with revoked token
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('revoked');

        $middleware2 = new AuthenticationMiddleware(
            $this->authService,
            $this->scopeManager,
            $this->tokenManager
        );

        $middleware2->authenticate($headers);
    }

    // ===== Complex Scenario Tests =====

    public function testMultipleRequestsWithSameClient(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $middleware = new AuthenticationMiddleware(
                $this->authService,
                $this->scopeManager,
                $this->tokenManager
            );

            $headers = ['Authorization' => "Bearer {$this->token}"];
            $authenticated = $middleware->authenticate($headers);

            $this->assertTrue($authenticated);
            $this->assertEquals('test-client', $middleware->getClientId());
        }
    }

    public function testScopePriorityWithHierarchy(): void
    {
        // Create token with write scope (implies read)
        $result = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:write']
        );

        $middleware = new AuthenticationMiddleware(
            $this->authService,
            $this->scopeManager,
            $this->tokenManager
        );

        $headers = ['Authorization' => "Bearer {$result['access_token']}"];
        $middleware->authenticate($headers);

        // Should have both implicitly
        $this->assertTrue($middleware->validateScope('loan:write'));
        $this->assertTrue($middleware->validateScope('loan:read'));
    }
}
