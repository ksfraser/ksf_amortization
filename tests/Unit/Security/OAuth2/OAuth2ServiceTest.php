<?php
namespace Tests\Unit\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\OAuth2Service;
use Ksfraser\Security\OAuth2\JWTTokenManager;
use Ksfraser\Security\Exceptions\AuthenticationException;
use Ksfraser\Security\Exceptions\TokenException;

class OAuth2ServiceTest extends TestCase
{
    /**
     * @var OAuth2Service
     */
    private $oauth2Service;

    /**
     * @var JWTTokenManager
     */
    private $tokenManager;

    /**
     * @var string
     */
    private $secretKey = 'this-is-a-very-long-secret-key-for-testing-purposes-12345';

    protected function setUp(): void
    {
        $this->tokenManager = new JWTTokenManager($this->secretKey);
        $this->oauth2Service = new OAuth2Service(
            $this->tokenManager,
            [
                'issuer' => 'test-api',
                'audience' => 'test-audience',
                'tokenExpiry' => 3600,
            ]
        );
    }

    /**
     * Test authenticateClient without database
     */
    public function testAuthenticateClientWithoutDatabase(): void
    {
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read', 'write']
        );

        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('token_type', $response);
        $this->assertArrayHasKey('expires_in', $response);
        $this->assertArrayHasKey('refresh_token', $response);
        $this->assertEquals('Bearer', $response['token_type']);
    }

    /**
     * Test authenticateClient returns valid access token
     */
    public function testAuthenticateClientReturnsValidToken(): void
    {
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read', 'write']
        );

        // Validate the access token
        $claims = $this->tokenManager->validate(
            $response['access_token'],
            'test-api',
            'test-audience'
        );

        $this->assertEquals('test-client', $claims['client_id']);
        $this->assertContains('read', $claims['scopes']);
        $this->assertContains('write', $claims['scopes']);
        $this->assertEquals('access', $claims['type']);
    }

    /**
     * Test authenticateClient rejects invalid credentials
     */
    public function testAuthenticateClientRejectsInvalidCredentials(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid client credentials');

        $this->oauth2Service->authenticateClient(
            '',
            '',
            ['read']
        );
    }

    /**
     * Test validateToken with valid token
     */
    public function testValidateTokenWithValidToken(): void
    {
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read', 'write']
        );

        $claims = $this->oauth2Service->validateToken($response['access_token']);
        $this->assertEquals('test-client', $claims['client_id']);
    }

    /**
     * Test validateToken with expired token
     */
    public function testValidateTokenWithExpiredToken(): void
    {
        $this->expectException(TokenException::class);

        // Create an expired token manually
        $now = time();
        $expiredClaims = [
            'client_id' => 'test-client',
            'scopes' => ['read'],
            'type' => 'access',
            'iat' => $now - 7200,
            'exp' => $now - 3600, // Expired
        ];

        $token = $this->tokenManager->generate($expiredClaims, 'test-api', 'test-audience');
        $this->oauth2Service->validateToken($token);
    }

    /**
     * Test refreshAccessToken with valid refresh token
     */
    public function testRefreshAccessTokenWithValidToken(): void
    {
        // First authenticate to get a refresh token
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read', 'write']
        );

        // Use refresh token
        $refreshResponse = $this->oauth2Service->refreshAccessToken($response['refresh_token']);

        $this->assertArrayHasKey('access_token', $refreshResponse);
        $this->assertArrayHasKey('token_type', $refreshResponse);
        $this->assertEquals('Bearer', $refreshResponse['token_type']);

        // New access token should be valid
        $claims = $this->tokenManager->validate(
            $refreshResponse['access_token'],
            'test-api',
            'test-audience'
        );
        $this->assertEquals('access', $claims['type']);
    }

    /**
     * Test refreshAccessToken with access token (should fail)
     */
    public function testRefreshAccessTokenWithAccessToken(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Not a refresh token');

        // Get an access token
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        // Try to use access token as refresh token
        $this->oauth2Service->refreshAccessToken($response['access_token']);
    }

    /**
     * Test revokeToken throws without database
     */
    public function testRevokeTokenWithoutDatabase(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Database not configured');

        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        $this->oauth2Service->revokeToken($response['access_token']);
    }

    /**
     * Test scopes are included in token response
     */
    public function testScopesIncludedInTokenResponse(): void
    {
        $requestedScopes = ['read', 'analytics', 'reporting'];
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            $requestedScopes
        );

        $this->assertArrayHasKey('scope', $response);
        $this->assertStringContainsString('read', $response['scope']);
        $this->assertStringContainsString('analytics', $response['scope']);
        $this->assertStringContainsString('reporting', $response['scope']);
    }

    /**
     * Test refresh token has longer expiry than access token
     */
    public function testRefreshTokenHasLongerExpiry(): void
    {
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        $accessClaims = $this->tokenManager->decode($response['access_token']);
        $refreshClaims = $this->tokenManager->decode($response['refresh_token']);

        // Refresh token should have later expiration
        $this->assertGreaterThan($accessClaims['exp'], $refreshClaims['exp']);
    }

    /**
     * Test token includes correct issuer and audience
     */
    public function testTokenIncludesIssuerAndAudience(): void
    {
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            ['read']
        );

        $claims = $this->tokenManager->validate(
            $response['access_token'],
            'test-api',
            'test-audience'
        );

        $this->assertEquals('test-api', $claims['iss']);
        $this->assertEquals('test-audience', $claims['aud']);
    }

    /**
     * Test authenticateClient with empty scopes
     */
    public function testAuthenticateClientWithEmptyScopes(): void
    {
        $response = $this->oauth2Service->authenticateClient(
            'test-client',
            'test-secret',
            []
        );

        // Should still return valid response
        $this->assertArrayHasKey('access_token', $response);
        
        $claims = $this->tokenManager->validate(
            $response['access_token'],
            'test-api',
            'test-audience'
        );

        // Scopes should be empty or not present
        $this->assertEmpty($claims['scopes'] ?? []);
    }
}
