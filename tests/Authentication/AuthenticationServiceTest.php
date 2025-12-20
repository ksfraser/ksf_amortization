<?php

namespace Tests\Authentication;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Authentication\AuthenticationService;
use Ksfraser\Amortizations\Authentication\Client;
use Ksfraser\Amortizations\Authentication\Token;
use Ksfraser\Amortizations\Authentication\InvalidTokenException;
use DateTimeImmutable;

/**
 * AuthenticationServiceTest - OAuth2 Authentication Tests
 *
 * Tests OAuth2 token generation, validation, revocation,
 * and scope management.
 *
 * @package Tests\Authentication
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class AuthenticationServiceTest extends TestCase
{
    /**
     * Authentication service instance
     *
     * @var AuthenticationService
     */
    private $authService;

    /**
     * Test client
     *
     * @var Client
     */
    private $client;

    /**
     * RSA private key for testing
     *
     * @var string
     */
    private $privateKey;

    /**
     * RSA public key for testing
     *
     * @var string
     */
    private $publicKey;

    /**
     * Setup test fixtures
     *
     * Creates RSA key pair and authentication service
     * using test keys (never use in production).
     */
    public function setUp(): void
    {
        // Generate test RSA key pair
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $this->privateKey);

        $pubKey = openssl_pkey_get_details($res);
        $this->publicKey = $pubKey['key'];

        // Create authentication service
        $this->authService = new AuthenticationService(
            $this->privateKey,
            $this->publicKey,
            'test-api'
        );

        // Create test client
        $this->client = new Client('test_client', 'test_secret', 'Test App');
        $this->client->grantScopes(['loan:read', 'loan:write']);
    }

    /**
     * Test token generation
     *
     * @test
     */
    public function testGenerateToken(): void
    {
        $token = $this->authService->generateToken(
            $this->client,
            ['loan:read']
        );

        $this->assertInstanceOf(Token::class, $token);
        $this->assertNotEmpty($token->getJti());
        $this->assertNotEmpty($token->getTokenString());
        $this->assertEquals('test_client', $token->getClientId());
        $this->assertContains('loan:read', $token->getScopes());
        $this->assertTrue($token->isAccessToken());
    }

    /**
     * Test token has correct scopes
     *
     * @test
     */
    public function testTokenHasCorrectScopes(): void
    {
        $scopes = ['loan:read', 'loan:write', 'analysis:read'];
        $token = $this->authService->generateToken($this->client, $scopes);

        $this->assertEquals($scopes, $token->getScopes());
        $this->assertTrue($token->hasScope('loan:read'));
        $this->assertTrue($token->hasScope('analysis:read'));
        $this->assertFalse($token->hasScope('admin'));
    }

    /**
     * Test token expiration
     *
     * @test
     */
    public function testTokenExpiration(): void
    {
        $token = $this->authService->generateToken($this->client, ['loan:read']);

        $this->assertFalse($token->isExpired());
        $this->assertGreaterThan(0, $token->getSecondsUntilExpiration());
        $this->assertLessThanOrEqual(3600, $token->getSecondsUntilExpiration());
    }

    /**
     * Test token validation succeeds with valid token
     *
     * @test
     */
    public function testValidateTokenSucceeds(): void
    {
        $token = $this->authService->generateToken(
            $this->client,
            ['loan:read']
        );
        $tokenString = $token->getTokenString();

        $validated = $this->authService->validateToken($tokenString);

        $this->assertInstanceOf(Token::class, $validated);
        $this->assertEquals($token->getJti(), $validated->getJti());
        $this->assertEquals('test_client', $validated->getClientId());
        $this->assertTrue($validated->hasScope('loan:read'));
    }

    /**
     * Test token validation fails with invalid signature
     *
     * @test
     */
    public function testValidateTokenFailsWithInvalidSignature(): void
    {
        $token = $this->authService->generateToken(
            $this->client,
            ['loan:read']
        );
        
        // Tamper with token
        $tokenParts = explode('.', $token->getTokenString());
        $tokenParts[2] = 'invalid_signature';
        $tamperedToken = implode('.', $tokenParts);

        $this->expectException(InvalidTokenException::class);
        $this->authService->validateToken($tamperedToken);
    }

    /**
     * Test token validation fails with malformed token
     *
     * @test
     */
    public function testValidateTokenFailsWithMalformedToken(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->authService->validateToken('not.a.valid.token');
    }

    /**
     * Test token validation fails with empty token
     *
     * @test
     */
    public function testValidateTokenFailsWithEmptyToken(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->authService->validateToken('');
    }

    /**
     * Test token revocation
     *
     * @test
     */
    public function testRevokeToken(): void
    {
        $token = $this->authService->generateToken(
            $this->client,
            ['loan:read']
        );

        // Validate token before revocation
        $this->assertInstanceOf(
            Token::class,
            $this->authService->validateToken($token->getTokenString())
        );

        // Revoke token
        $revoked = $this->authService->revokeToken($token->getJti());
        $this->assertTrue($revoked);

        // Validation should fail after revocation
        $this->expectException(InvalidTokenException::class);
        $this->authService->validateToken($token->getTokenString());
    }

    /**
     * Test revoke all client tokens
     *
     * @test
     */
    public function testRevokeAllClientTokens(): void
    {
        $token1 = $this->authService->generateToken(
            $this->client,
            ['loan:read']
        );
        $token2 = $this->authService->generateToken(
            $this->client,
            ['loan:write']
        );

        // Both tokens valid
        $this->assertInstanceOf(Token::class, $this->authService->validateToken($token1->getTokenString()));
        $this->assertInstanceOf(Token::class, $this->authService->validateToken($token2->getTokenString()));

        // Revoke all
        $this->authService->revokeClientTokens('test_client');

        // Both should fail
        $this->expectException(InvalidTokenException::class);
        $this->authService->validateToken($token1->getTokenString());
    }

    /**
     * Test generate refresh token
     *
     * @test
     */
    public function testGenerateRefreshToken(): void
    {
        $accessToken = $this->authService->generateToken(
            $this->client,
            ['loan:read']
        );

        $refreshToken = $this->authService->generateRefreshToken(
            $this->client,
            $accessToken->getJti()
        );

        $this->assertInstanceOf(Token::class, $refreshToken);
        $this->assertTrue($refreshToken->isRefreshToken());
        $this->assertNotEquals($accessToken->getJti(), $refreshToken->getJti());
    }

    /**
     * Test set token expiration
     *
     * @test
     */
    public function testSetTokenExpiration(): void
    {
        $this->authService->setTokenExpiration(7200);

        $this->assertEquals(7200, $this->authService->getTokenExpiration());
    }

    /**
     * Test set token expiration validation
     *
     * @test
     */
    public function testSetTokenExpirationValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->authService->setTokenExpiration(0);
    }

    /**
     * Test get issuer
     *
     * @test
     */
    public function testGetIssuer(): void
    {
        $this->assertEquals('test-api', $this->authService->getIssuer());
    }

    /**
     * Test token metrics
     *
     * @test
     */
    public function testTokenMetrics(): void
    {
        $token1 = $this->authService->generateToken(
            $this->client,
            ['loan:read']
        );
        $token2 = $this->authService->generateToken(
            $this->client,
            ['loan:write']
        );

        $this->assertEquals(2, $this->authService->getActiveTokenCount());

        $this->authService->revokeToken($token1->getJti());

        $this->assertEquals(1, $this->authService->getRevokedTokenCount());
    }

    /**
     * Test invalid constructor with missing keys
     *
     * @test
     */
    public function testConstructorValidatesKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AuthenticationService('', '', 'test');
    }

    /**
     * Test invalid constructor with invalid PEM
     *
     * @test
     */
    public function testConstructorValidatesPemFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AuthenticationService('not_pem', 'not_pem', 'test');
    }

    /**
     * Test token string includes bearer information
     *
     * @test
     */
    public function testTokenStringFormat(): void
    {
        $token = $this->authService->generateToken(
            $this->client,
            ['loan:read']
        );

        $tokenString = $token->getTokenString();
        
        // JWT should have 3 parts separated by dots
        $parts = explode('.', $tokenString);
        $this->assertCount(3, $parts);
    }

    /**
     * Test token to array
     *
     * @test
     */
    public function testTokenToArray(): void
    {
        $token = $this->authService->generateToken(
            $this->client,
            ['loan:read', 'loan:write']
        );

        $array = $token->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Bearer', $array['token_type']);
        $this->assertEquals('access', $array['type']);
        $this->assertStringContainsString('loan:read', $array['scope']);
        $this->assertStringContainsString('loan:write', $array['scope']);
    }

    /**
     * Test multiple clients with different scopes
     *
     * @test
     */
    public function testMultipleClientsWithDifferentScopes(): void
    {
        $client1 = new Client('client1', 'secret1');
        $client1->grantScopes(['loan:read']);

        $client2 = new Client('client2', 'secret2');
        $client2->grantScopes(['loan:read', 'loan:write', 'admin']);

        $token1 = $this->authService->generateToken(
            $client1,
            $client1->getScopes()
        );
        $token2 = $this->authService->generateToken(
            $client2,
            $client2->getScopes()
        );

        $this->assertCount(1, $token1->getScopes());
        $this->assertCount(3, $token2->getScopes());
        $this->assertFalse($token1->hasScope('admin'));
        $this->assertTrue($token2->hasScope('admin'));
    }
}
