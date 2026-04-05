<?php
namespace Tests\Unit\Api\Middleware;

use PHPUnit\Framework\TestCase;
use Ksfraser\Api\Middleware\ApiAuthMiddleware;
use Ksfraser\Security\OAuth2\OAuth2Service;
use Ksfraser\Security\OAuth2\JWTTokenManager;
use Ksfraser\Security\OAuth2\ScopeManager;
use Ksfraser\Security\Exceptions\AuthenticationException;
use Ksfraser\Security\Exceptions\AuthorizationException;

class ApiAuthMiddlewareTest extends TestCase
{
    /**
     * @var ApiAuthMiddleware
     */
    private $middleware;

    /**
     * @var OAuth2Service
     */
    private $oauth2Service;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var string
     */
    private $secretKey = 'this-is-a-very-long-secret-key-for-testing-purposes-12345';

    protected function setUp(): void
    {
        $tokenManager = new JWTTokenManager($this->secretKey);
        $this->oauth2Service = new OAuth2Service(
            $tokenManager,
            ['issuer' => 'test-api', 'audience' => 'test-audience']
        );
        $this->scopeManager = new ScopeManager();
        $this->middleware = new ApiAuthMiddleware($this->oauth2Service, $this->scopeManager);
    }

    /**
     * Test authenticate with valid Bearer token
     */
    public function testAuthenticateWithValidBearerToken(): void
    {
        // Get a valid token
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read', 'write']
        );

        $headers = ['Authorization' => 'Bearer ' . $tokenResponse['access_token']];

        $context = $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');

        $this->assertTrue($context['authenticated']);
        $this->assertEquals('test-client', $context['client_id']);
        $this->assertContains('read', $context['scopes']);
        $this->assertContains('write', $context['scopes']);
    }

    /**
     * Test authenticate fails with missing Authorization header
     */
    public function testAuthenticateFailsWithMissingHeader(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Missing or invalid Authorization header');

        $this->middleware->authenticate([], 'GET /api/loans', '127.0.0.1');
    }

    /**
     * Test authenticate fails with invalid Bearer token
     */
    public function testAuthenticateFailsWithInvalidToken(): void
    {
        $this->expectException(AuthenticationException::class);

        $headers = ['Authorization' => 'Bearer invalid-token-here'];
        $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');
    }

    /**
     * Test authenticate with case-insensitive Authorization header
     */
    public function testAuthenticateWithCaseInsensitiveHeader(): void
    {
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        // Try lowercase header name
        $headers = ['authorization' => 'Bearer ' . $tokenResponse['access_token']];
        $context = $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');

        $this->assertTrue($context['authenticated']);
    }

    /**
     * Test requireScope succeeds for granted scope
     */
    public function testRequireScopeSucceedsForGrantedScope(): void
    {
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read', 'write']
        );

        $headers = ['Authorization' => 'Bearer ' . $tokenResponse['access_token']];
        $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');

        // Should not throw
        $this->middleware->requireScope('read');
        $this->middleware->requireScope('write');
        
        $this->assertTrue(true);
    }

    /**
     * Test requireScope throws for missing scope
     */
    public function testRequireScopeThrowsForMissingScope(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Insufficient permissions');

        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        $headers = ['Authorization' => 'Bearer ' . $tokenResponse['access_token']];
        $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');

        $this->middleware->requireScope('write');
    }

    /**
     * Test requireScopes with multiple scopes
     */
    public function testRequireMultipleScopes(): void
    {
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read', 'write', 'analytics']
        );

        $headers = ['Authorization' => 'Bearer ' . $tokenResponse['access_token']];
        $this->middleware->authenticate($headers, 'POST /api/loans', '127.0.0.1');

        // Should not throw
        $this->middleware->requireScopes(['read', 'write']);
        
        $this->assertTrue(true);
    }

    /**
     * Test getContext returns current context
     */
    public function testGetContextReturnsCurrentContext(): void
    {
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        $headers = ['Authorization' => 'Bearer ' . $tokenResponse['access_token']];
        $this->middleware->authenticate($headers, 'GET /api/loans', '192.168.1.1');

        $context = $this->middleware->getContext();

        $this->assertTrue($context['authenticated']);
        $this->assertEquals('test-client', $context['client_id']);
        $this->assertEquals('GET /api/loans', $context['endpoint']);
        $this->assertEquals('192.168.1.1', $context['ip']);
    }

    /**
     * Test isAuthenticated method
     */
    public function testIsAuthenticatedMethod(): void
    {
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        // Before authentication
        $this->assertFalse($this->middleware->isAuthenticated());

        // After authentication
        $headers = ['Authorization' => 'Bearer ' . $tokenResponse['access_token']];
        $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');
        $this->assertTrue($this->middleware->isAuthenticated());
    }

    /**
     * Test getClientId method
     */
    public function testGetClientIdMethod(): void
    {
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        $headers = ['Authorization' => 'Bearer ' . $tokenResponse['access_token']];
        $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');

        $this->assertEquals('test-client', $this->middleware->getClientId());
    }

    /**
     * Test getScopes method
     */
    public function testGetScopesMethod(): void
    {
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read', 'write', 'analytics']
        );

        $headers = ['Authorization' => 'Bearer ' . $tokenResponse['access_token']];
        $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');

        $scopes = $this->middleware->getScopes();
        $this->assertContains('read', $scopes);
        $this->assertContains('write', $scopes);
        $this->assertContains('analytics', $scopes);
    }

    /**
     * Test requireScope without authentication
     */
    public function testRequireScopeWithoutAuthentication(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Request not authenticated');

        $this->middleware->requireScope('read');
    }

    /**
     * Test authenticate with expired token fails
     */
    public function testAuthenticateWithExpiredTokenFails(): void
    {
        $this->expectException(AuthenticationException::class);

        // Create an expired token manually
        $tokenManager = new JWTTokenManager($this->secretKey);
        $now = time();
        $expiredClaims = [
            'client_id' => 'test-client',
            'scopes' => ['read'],
            'type' => 'access',
            'iat' => $now - 7200,
            'exp' => $now - 3600,
        ];

        $expiredToken = $tokenManager->generate($expiredClaims, 'test-api', 'test-audience');
        $headers = ['Authorization' => 'Bearer ' . $expiredToken];

        $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');
    }

    /**
     * Test Bearer token extraction with different formats
     */
    public function testBearerTokenExtractionVariations(): void
    {
        $tokenResponse = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        $token = $tokenResponse['access_token'];

        // Test with "bearer" (lowercase)
        $headers = ['Authorization' => 'bearer ' . $token];
        $context = $this->middleware->authenticate($headers, 'GET /api/loans', '127.0.0.1');
        $this->assertTrue($context['authenticated']);
    }
}
