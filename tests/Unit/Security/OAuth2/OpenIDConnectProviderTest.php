<?php
namespace Tests\Unit\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\OpenIDConnect\OpenIDConnectProvider;
use Ksfraser\Security\OAuth2\JWTTokenManager;
use Ksfraser\Security\Exceptions\TokenException;

class OpenIDConnectProviderTest extends TestCase
{
    /**
     * @var OpenIDConnectProvider
     */
    private $provider;

    /**
     * @var JWTTokenManager
     */
    private $jwtManager;

    protected function setUp(): void
    {
        $this->jwtManager = new JWTTokenManager('test-secret-key-min-32-chars-12345');
        
        $this->provider = new OpenIDConnectProvider($this->jwtManager);
    }

    /**
     * Test generate ID token with openid scope
     */
    public function testGenerateIDTokenWithOpenIDScope(): void
    {
        $userData = [
            'id' => 'user-123',
            'email' => 'user@example.com',
            'name' => 'John Doe'
        ];

        $idToken = $this->provider->generateIDToken(
            'user-123',
            $userData,
            ['openid'],
            'client-id'
        );

        $this->assertNotEmpty($idToken);
    }

    /**
     * Test generate ID token with profile scope
     */
    public function testGenerateIDTokenWithProfileScope(): void
    {
        $userData = [
            'id' => 'user-123',
            'email' => 'user@example.com',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'picture_url' => 'https://example.com/photo.jpg'
        ];

        $idToken = $this->provider->generateIDToken(
            'user-123',
            $userData,
            ['openid', 'profile'],
            'client-id'
        );

        $this->assertNotEmpty($idToken);
    }

    /**
     * Test generate ID token with email scope
     */
    public function testGenerateIDTokenWithEmailScope(): void
    {
        $userData = [
            'id' => 'user-123',
            'email' => 'user@example.com',
            'email_verified' => true
        ];

        $idToken = $this->provider->generateIDToken(
            'user-123',
            $userData,
            ['openid', 'email'],
            'client-id'
        );

        $this->assertNotEmpty($idToken);
    }

    /**
     * Test generate ID token with address scope
     */
    public function testGenerateIDTokenWithAddressScope(): void
    {
        $userData = [
            'id' => 'user-123',
            'address' => [
                'street_address' => '123 Main St',
                'locality' => 'Anytown',
                'postal_code' => '12345',
                'country' => 'US'
            ]
        ];

        $idToken = $this->provider->generateIDToken(
            'user-123',
            $userData,
            ['openid', 'address'],
            'client-id'
        );

        $this->assertNotEmpty($idToken);
    }

    /**
     * Test generate ID token with phone scope
     */
    public function testGenerateIDTokenWithPhoneScope(): void
    {
        $userData = [
            'id' => 'user-123',
            'phone_number' => '+1-201-555-0123',
            'phone_number_verified' => true
        ];

        $idToken = $this->provider->generateIDToken(
            'user-123',
            $userData,
            ['openid', 'phone'],
            'client-id'
        );

        $this->assertNotEmpty($idToken);
    }

    /**
     * Test get user info returns correct structure
     */
    public function testGetUserInfoReturnsCorrectStructure(): void
    {
        $userId = 'user-123';
        $userInfo = $this->provider->getUserInfo($userId);

        $this->assertArrayHasKey('sub', $userInfo);
        $this->assertEquals($userId, $userInfo['sub']);
    }

    /**
     * Test discovery document format
     */
    public function testGetDiscoveryDocument(): void
    {
        $issuer = 'https://auth.example.com';
        $discovery = $this->provider->getDiscoveryDocument($issuer);

        $this->assertArrayHasKey('issuer', $discovery);
        $this->assertArrayHasKey('authorization_endpoint', $discovery);
        $this->assertArrayHasKey('token_endpoint', $discovery);
        $this->assertArrayHasKey('userinfo_endpoint', $discovery);
        $this->assertArrayHasKey('jwks_uri', $discovery);
        $this->assertArrayHasKey('scopes_supported', $discovery);
        $this->assertArrayHasKey('response_types_supported', $discovery);

        $this->assertEquals($issuer, $discovery['issuer']);
    }

    /**
     * Test discovery document includes standard endpoints
     */
    public function testDiscoveryDocumentIncludesEndpoints(): void
    {
        $issuer = 'https://auth.example.com';
        $discovery = $this->provider->getDiscoveryDocument($issuer);

        $this->assertStringContainsString('/authorize', $discovery['authorization_endpoint']);
        $this->assertStringContainsString('/token', $discovery['token_endpoint']);
        $this->assertStringContainsString('/userinfo', $discovery['userinfo_endpoint']);
        $this->assertStringContainsString('/jwks', $discovery['jwks_uri']);
    }

    /**
     * Test validate ID token succeeds
     */
    public function testValidateIDTokenSucceeds(): void
    {
        $userData = [
            'id' => 'user-123',
            'email' => 'user@example.com'
        ];

        $idToken = $this->provider->generateIDToken(
            'user-123',
            $userData,
            ['openid', 'email'],
            'client-id'
        );

        $validated = $this->provider->validateIDToken($idToken, 'client-id');

        $this->assertArrayHasKey('sub', $validated);
        $this->assertEquals('user-123', $validated['sub']);
    }

    /**
     * Test validate ID token fails with invalid signature
     */
    public function testValidateIDTokenFailsWithInvalidSignature(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Invalid token signature');

        // Tampered token
        $tamperedToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ1c2VyLTEyMyJ9.invalid';

        $this->provider->validateIDToken($tamperedToken, 'client-id');
    }
}
