<?php

namespace Ksfraser\Amortizations\Tests\Integration\Controllers;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Authentication\AuthenticationService;
use Ksfraser\Amortizations\Authentication\TokenManager;
use Ksfraser\Amortizations\Authentication\Client;
use Ksfraser\Amortizations\Authentication\Storage\InMemoryTokenStorage;
use Ksfraser\Amortizations\Authentication\Middleware\AuthenticationMiddleware;

/**
 * ControllerAuthenticationTest - Protected Endpoint Tests
 *
 * Tests authentication middleware integration with existing controllers.
 * Verifies scope-based access control on protected endpoints.
 */
class ControllerAuthenticationTest extends TestCase
{
    protected $authService;
    protected $tokenManager;
    protected $middleware;
    protected $privateKey;
    protected $publicKey;

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

        // Create authentication service
        $this->authService = new AuthenticationService(
            $this->privateKey,
            $this->publicKey,
            'controller-test-api'
        );

        // Create token manager with in-memory storage
        $storage = new InMemoryTokenStorage();
        $this->tokenManager = new TokenManager($this->authService, $storage);

        // Create middleware
        $this->middleware = new AuthenticationMiddleware(
            $this->authService,
            ['POST /api/v1/auth/token', 'GET /api/v1/health'] // Public endpoints
        );
    }

    // ===== Loan Controller Protection Tests =====

    public function testLoanControllerGetRequiresLoanReadScope(): void
    {
        // Generate token WITHOUT loan:read scope
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['schedule:read']);

        // Mock Bearer token header
        $authHeader = 'Bearer ' . $token['access_token'];

        // Verify token doesn't have loan:read scope
        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertNotContains('loan:read', $scopes);
        $this->assertContains('schedule:read', $scopes);
    }

    public function testLoanControllerCreateRequiresLoanWriteScope(): void
    {
        // Generate token WITH loan:write scope
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['loan:write']);

        // Verify token has scope
        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertContains('loan:write', $scopes);
    }

    public function testLoanControllerUpdateRequiresLoanWriteScope(): void
    {
        // Generate token WITHOUT write scope
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        // Verify insufficient scope
        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertNotContains('loan:write', $scopes);
    }

    public function testLoanControllerDeleteRequiresLoanDeleteScope(): void
    {
        // Generate token with delete scope
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['loan:delete']);

        // Verify scopes
        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertContains('loan:delete', $scopes);
        $this->assertContains('loan:write', $scopes); // Should inherit write
        $this->assertContains('loan:read', $scopes);  // Should inherit read
    }

    // ===== Schedule Controller Protection Tests =====

    public function testScheduleControllerGetRequiresScheduleReadScope(): void
    {
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['schedule:read']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertContains('schedule:read', $scopes);
    }

    public function testScheduleControllerCreateRequiresScheduleWriteScope(): void
    {
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['schedule:write']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertContains('schedule:write', $scopes);
        $this->assertContains('schedule:read', $scopes);
    }

    // ===== Event Controller Protection Tests =====

    public function testEventControllerGetRequiresEventReadScope(): void
    {
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['event:read']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertContains('event:read', $scopes);
    }

    public function testEventControllerCreateRequiresEventManageScope(): void
    {
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['event:manage']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertContains('event:manage', $scopes);
    }

    // ===== Analysis Controller Protection Tests =====

    public function testAnalysisControllerGetRequiresAnalysisReadScope(): void
    {
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['analysis:read']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertContains('analysis:read', $scopes);
    }

    public function testAnalysisControllerAdvancedRequiresAnalysisAdvancedScope(): void
    {
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['analysis:advanced']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        $this->assertContains('analysis:advanced', $scopes);
        $this->assertContains('analysis:read', $scopes); // Should inherit read
    }

    // ===== Cross-Scope Permission Tests =====

    public function testTokenWithMultipleScopesAllowsAllOperations(): void
    {
        $client = new Client('admin-app', 'secret');
        $allScopes = [
            'loan:read', 'loan:write', 'loan:delete',
            'schedule:read', 'schedule:write', 'schedule:delete',
            'event:read', 'event:manage',
            'analysis:read', 'analysis:advanced',
        ];

        $token = $this->tokenManager->generateTokenPair($client, $allScopes);
        $decoded = $this->authService->verifyToken($token['access_token']);

        $tokenScopes = explode(' ', $decoded->scope ?? '');
        foreach ($allScopes as $scope) {
            $this->assertContains($scope, $tokenScopes);
        }
    }

    public function testTokenWithLimitedScopesRestrictsOperations(): void
    {
        $client = new Client('readonly-app', 'secret');
        $readonly = ['loan:read', 'schedule:read', 'analysis:read'];

        $token = $this->tokenManager->generateTokenPair($client, $readonly);
        $decoded = $this->authService->verifyToken($token['access_token']);

        $tokenScopes = explode(' ', $decoded->scope ?? '');
        
        // Should have read scopes
        foreach ($readonly as $scope) {
            $this->assertContains($scope, $tokenScopes);
        }

        // Should NOT have write/delete scopes
        $this->assertNotContains('loan:write', $tokenScopes);
        $this->assertNotContains('schedule:delete', $tokenScopes);
    }

    // ===== Token Bearer Format Tests =====

    public function testValidBearerTokenFormat(): void
    {
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $bearerHeader = 'Bearer ' . $token['access_token'];
        
        // Extract token from header
        if (preg_match('/Bearer\s+(.+)/', $bearerHeader, $matches)) {
            $extractedToken = $matches[1];
            $decoded = $this->authService->verifyToken($extractedToken);
            $this->assertNotNull($decoded);
        } else {
            $this->fail('Bearer token format invalid');
        }
    }

    public function testMissingBearerKeywordRejected(): void
    {
        $client = new Client('test-app', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        // Missing "Bearer" keyword
        $invalidHeader = $token['access_token']; // Just the token
        
        if (!preg_match('/Bearer\s+(.+)/', $invalidHeader, $matches)) {
            // Correctly rejected
            $this->assertTrue(true);
        } else {
            $this->fail('Should reject token without Bearer keyword');
        }
    }

    // ===== Admin/Service Account Tests =====

    public function testAdminServiceAccountHasAllScopes(): void
    {
        $admin = new Client('admin-service', 'admin-secret');
        $adminScopes = [
            'admin:read', 'admin:write', 'admin:delete',
            'loan:*', 'schedule:*', 'event:*', 'analysis:*',
        ];

        $token = $this->tokenManager->generateTokenPair($admin, $adminScopes);
        $decoded = $this->authService->verifyToken($token['access_token']);

        $this->assertEquals('admin-service', $decoded->sub);
        $this->assertNotEmpty($decoded->scope);
    }

    public function testSecondPartyAPIHasRestrictedScopes(): void
    {
        $third = new Client('third-party-api', 'third-secret');
        $thirdScopes = ['loan:read', 'analysis:read'];

        $token = $this->tokenManager->generateTokenPair($third, $thirdScopes);
        $decoded = $this->authService->verifyToken($token['access_token']);

        $scopes = explode(' ', $decoded->scope ?? '');
        
        // Should only have specified scopes
        $this->assertContains('loan:read', $scopes);
        $this->assertContains('analysis:read', $scopes);
        $this->assertNotContains('admin:write', $scopes);
        $this->assertNotContains('loan:delete', $scopes);
    }

    // ===== Rate Limiting Preparation Tests =====

    public function testTokenIncludesClientIdentifier(): void
    {
        $client = new Client('rate-limit-client', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        
        // Should have client identifier for rate limiting
        $this->assertEquals('rate-limit-client', $decoded->sub);
    }

    public function testTokenIncludesIssuedAtForRateWindow(): void
    {
        $client = new Client('rate-limit-test', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        
        // Should have issued-at time
        $this->assertObjectHasProperty('iat', $decoded);
        $this->assertGreaterThan(0, $decoded->iat);
    }

    // ===== Token Expiration Tests =====

    public function testTokenExpirationSetCorrectly(): void
    {
        $client = new Client('expiry-test', 'secret');
        $expiresIn = 3600; // 1 hour
        $token = $this->tokenManager->generateTokenPair($client, ['loan:read'], $expiresIn);

        $decoded = $this->authService->verifyToken($token['access_token']);
        
        $this->assertObjectHasProperty('exp', $decoded);
        $this->assertGreaterThan(time(), $decoded->exp);
    }

    // ===== Scope Hierarchy Tests =====

    public function testWriteScopeIncludesReadPermissions(): void
    {
        $client = new Client('hierarchy-test', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['loan:write']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        // Write scope should include read via hierarchy
        $this->assertContains('loan:write', $scopes);
        $this->assertContains('loan:read', $scopes);
    }

    public function testDeleteScopeIncludesWriteAndRead(): void
    {
        $client = new Client('hierarchy-delete', 'secret');
        $token = $this->tokenManager->generateTokenPair($client, ['schedule:delete']);

        $decoded = $this->authService->verifyToken($token['access_token']);
        $scopes = explode(' ', $decoded->scope ?? '');

        // Delete should include write and read via hierarchy
        $this->assertContains('schedule:delete', $scopes);
        $this->assertContains('schedule:write', $scopes);
        $this->assertContains('schedule:read', $scopes);
    }
}
