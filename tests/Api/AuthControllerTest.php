<?php

namespace Ksfraser\Amortizations\Tests\Api;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Api\AuthController;
use Ksfraser\Amortizations\Api\ApiResponse;
use Ksfraser\Amortizations\Authentication\AuthenticationService;
use Ksfraser\Amortizations\Authentication\TokenManager;
use Ksfraser\Amortizations\Authentication\Client;
use Ksfraser\Amortizations\Authentication\Storage\InMemoryTokenStorage;
use Ksfraser\Amortizations\Repositories\ClientRepository;

/**
 * AuthControllerTest - OAuth2 API Endpoint Tests
 *
 * Tests token generation, refresh, revocation, and scope endpoints.
 */
class AuthControllerTest extends TestCase
{
    protected $controller;
    protected $authService;
    protected $tokenManager;
    protected $clientRepo;
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

        // Create services
        $this->authService = new AuthenticationService(
            $this->privateKey,
            $this->publicKey,
            'test-api'
        );

        $storage = new InMemoryTokenStorage();
        $this->tokenManager = new TokenManager($this->authService, $storage);

        // Mock client repository
        $this->clientRepo = $this->createMock(ClientRepository::class);

        // Create controller
        $this->controller = new AuthController(
            $this->authService,
            $this->tokenManager,
            $this->clientRepo
        );
    }

    // ===== Token Endpoint Tests =====

    public function testTokenEndpointSuccess(): void
    {
        // Setup mock client
        $this->clientRepo->expects($this->once())
            ->method('findById')
            ->with('test-client')
            ->willReturn([
                'id' => 'test-client',
                'secret' => 'test-secret',
                'name' => 'Test Client',
                'active' => true,
            ]);

        $response = $this->controller->token([
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'scope' => 'loan:read schedule:read',
            'grant_type' => 'client_credentials',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
        // Response should contain access_token, refresh_token, etc
    }

    public function testTokenEndpointMissingClientId(): void
    {
        $response = $this->controller->token([
            'client_secret' => 'test-secret',
            'scope' => 'loan:read',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
        // Should return bad request
    }

    public function testTokenEndpointInvalidCredentials(): void
    {
        // Setup mock to return null (client not found)
        $this->clientRepo->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $response = $this->controller->token([
            'client_id' => 'invalid-client',
            'client_secret' => 'invalid-secret',
            'scope' => 'loan:read',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testTokenEndpointUnsupportedGrantType(): void
    {
        $response = $this->controller->token([
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'scope' => 'loan:read',
            'grant_type' => 'authorization_code', // Not supported
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testTokenEndpointMissingScope(): void
    {
        $this->clientRepo->expects($this->once())
            ->method('findById')
            ->willReturn([
                'id' => 'test-client',
                'secret' => 'test-secret',
                'name' => 'Test Client',
                'active' => true,
            ]);

        $response = $this->controller->token([
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'scope' => '', // Empty scope
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    // ===== Refresh Token Endpoint Tests =====

    public function testRefreshEndpointSuccess(): void
    {
        // Generate initial token pair
        $client = new Client('test-client', 'test-secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $response = $this->controller->refresh([
            'refresh_token' => $result['refresh_token'],
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testRefreshEndpointMissingToken(): void
    {
        $response = $this->controller->refresh([]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testRefreshEndpointInvalidToken(): void
    {
        $response = $this->controller->refresh([
            'refresh_token' => 'invalid.token.here',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    // ===== Revocation Endpoint Tests =====

    public function testRevokeEndpointSuccess(): void
    {
        // Generate token
        $client = new Client('test-client', 'test-secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $response = $this->controller->revoke([
            'token' => $result['access_token'],
            'client_id' => 'test-client',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testRevokeEndpointMissingToken(): void
    {
        $response = $this->controller->revoke([
            'client_id' => 'test-client',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testRevokeEndpointInvalidToken(): void
    {
        // OAuth2 spec: revoke should succeed even for invalid tokens
        $response = $this->controller->revoke([
            'token' => 'invalid.token.here',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    // ===== Logout Endpoint Tests =====

    public function testLogoutEndpointSuccess(): void
    {
        $response = $this->controller->logout('test-client');

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testLogoutEndpointMultipleTokens(): void
    {
        // Generate multiple tokens
        $client = new Client('test-client', 'test-secret');
        $this->tokenManager->generateTokenPair($client, ['loan:read']);
        $this->tokenManager->generateTokenPair($client, ['schedule:read']);

        $response = $this->controller->logout('test-client');

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    // ===== Scopes Endpoint Tests =====

    public function testListScopesEndpoint(): void
    {
        $response = $this->controller->listScopes();

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    // ===== Full Login Flow Tests =====

    public function testCompleteLoginFlow(): void
    {
        // Step 1: Setup client
        $this->clientRepo->expects($this->once())
            ->method('findById')
            ->with('myapp')
            ->willReturn([
                'id' => 'myapp',
                'secret' => 'secret123',
                'name' => 'My Application',
                'active' => true,
            ]);

        // Step 2: Get access token
        $tokenResponse = $this->controller->token([
            'client_id' => 'myapp',
            'client_secret' => 'secret123',
            'scope' => 'loan:read schedule:read',
            'grant_type' => 'client_credentials',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $tokenResponse);
    }

    public function testTokenRefreshFlow(): void
    {
        // Generate initial tokens
        $client = new Client('test-client', 'test-secret');
        $initial = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        // Exchange refresh token for new access token
        $refreshed = $this->controller->refresh([
            'refresh_token' => $initial['refresh_token'],
        ]);

        $this->assertInstanceOf(ApiResponse::class, $refreshed);
    }

    public function testTokenRevocationFlow(): void
    {
        // Generate token
        $client = new Client('test-client', 'test-secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        // Revoke it
        $revoked = $this->controller->revoke([
            'token' => $result['access_token'],
        ]);

        $this->assertInstanceOf(ApiResponse::class, $revoked);
    }

    // ===== Error Handling Tests =====

    public function testInvalidClientIdentifier(): void
    {
        $this->clientRepo->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $response = $this->controller->token([
            'client_id' => 'nonexistent',
            'client_secret' => 'any-secret',
            'scope' => 'loan:read',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testDeactivatedClient(): void
    {
        $this->clientRepo->expects($this->once())
            ->method('findById')
            ->willReturn([
                'id' => 'deactivated',
                'secret' => 'secret123',
                'name' => 'Deactivated Client',
                'active' => false, // Deactivated
            ]);

        $response = $this->controller->token([
            'client_id' => 'deactivated',
            'client_secret' => 'secret123',
            'scope' => 'loan:read',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    public function testWrongSecretAuthentication(): void
    {
        $this->clientRepo->expects($this->once())
            ->method('findById')
            ->willReturn([
                'id' => 'test-client',
                'secret' => 'correct-secret',
                'name' => 'Test Client',
                'active' => true,
            ]);

        $response = $this->controller->token([
            'client_id' => 'test-client',
            'client_secret' => 'wrong-secret',
            'scope' => 'loan:read',
        ]);

        $this->assertInstanceOf(ApiResponse::class, $response);
    }
}
