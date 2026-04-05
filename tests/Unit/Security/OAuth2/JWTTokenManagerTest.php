<?php
namespace Tests\Unit\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\JWTTokenManager;
use Ksfraser\Security\Exceptions\TokenException;

/**
 * JWT Token Manager Tests - Using firebase/php-jwt
 * 
 * Tests the wrapper around firebase/php-jwt which is battle-tested
 * and used by millions of applications.
 */
class JWTTokenManagerTest extends TestCase
{
    /**
     * @var JWTTokenManager
     */
    private $tokenManager;

    /**
     * @var string Secret key for testing (minimum 32 characters)
     */
    private $secretKey = 'this-is-a-very-long-secret-key-for-testing-purposes-12345';

    protected function setUp(): void
    {
        $this->tokenManager = new JWTTokenManager($this->secretKey);
    }

    /**
     * Test token generation with valid claims
     */
    public function testGenerateTokenWithValidClaims(): void
    {
        $now = time();
        $claims = [
            'client_id' => 'test-client',
            'scopes' => ['read', 'write'],
            'type' => 'access',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $token = $this->tokenManager->generate($claims, 'test-issuer', 'test-audience');

        // Token should be a string with 3 parts separated by dots
        $this->assertIsString($token);
        $this->assertCount(3, explode('.', $token));
    }

    /**
     * Test token validation with valid token
     */
    public function testValidateTokenWithValidToken(): void
    {
        $now = time();
        $claims = [
            'client_id' => 'test-client',
            'scopes' => ['read', 'write'],
            'type' => 'access',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $token = $this->tokenManager->generate($claims, 'test-issuer', 'test-audience');
        $decoded = $this->tokenManager->validate($token, 'test-issuer', 'test-audience');

        $this->assertEquals('test-client', $decoded['client_id']);
        $this->assertEquals(['read', 'write'], $decoded['scopes']);
        $this->assertEquals('test-issuer', $decoded['iss']);
        $this->assertEquals('test-audience', $decoded['aud']);
    }

    /**
     * Test token validation fails with expired token
     */
    public function testValidateTokenWithExpiredToken(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Token has expired');

        $now = time();
        $claims = [
            'client_id' => 'test-client',
            'iat' => $now - 7200,
            'exp' => $now - 3600, // Expired 1 hour ago
        ];

        $token = $this->tokenManager->generate($claims, 'test-issuer', 'test-audience');
        $this->tokenManager->validate($token, 'test-issuer', 'test-audience');
    }

    /**
     * Test token validation fails with tampered token
     */
    public function testValidateTokenWithTamperedToken(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Invalid token signature');

        $now = time();
        $claims = [
            'client_id' => 'test-client',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $token = $this->tokenManager->generate($claims, 'test-issuer', 'test-audience');

        // Tamper with token by changing one character in the signature
        $parts = explode('.', $token);
        $parts[2] = 'tampered' . $parts[2];
        $tamperedToken = implode('.', $parts);

        $this->tokenManager->validate($tamperedToken, 'test-issuer', 'test-audience');
    }

    /**
     * Test token validation fails with wrong issuer
     */
    public function testValidateTokenWithWrongIssuer(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Invalid token issuer');

        $now = time();
        $claims = [
            'client_id' => 'test-client',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $token = $this->tokenManager->generate($claims, 'test-issuer', 'test-audience');
        $this->tokenManager->validate($token, 'wrong-issuer', 'test-audience');
    }

    /**
     * Test isExpired method
     */
    public function testIsExpiredMethod(): void
    {
        $now = time();

        // Create valid token
        $validClaims = [
            'client_id' => 'test-client',
            'iat' => $now,
            'exp' => $now + 3600,
        ];
        $validToken = $this->tokenManager->generate($validClaims);
        $this->assertFalse($this->tokenManager->isExpired($validToken));

        // Create expired token
        $expiredClaims = [
            'client_id' => 'test-client',
            'iat' => $now - 7200,
            'exp' => $now - 3600,
        ];
        $expiredToken = $this->tokenManager->generate($expiredClaims);
        $this->assertTrue($this->tokenManager->isExpired($expiredToken));
    }

    /**
     * Test decode method without verification
     */
    public function testDecodeTokenWithoutVerification(): void
    {
        $now = time();
        $claims = [
            'client_id' => 'test-client',
            'custom_claim' => 'custom_value',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $token = $this->tokenManager->generate($claims, 'test-issuer', 'test-audience');
        $decoded = $this->tokenManager->decode($token);

        $this->assertEquals('test-client', $decoded['client_id']);
        $this->assertEquals('custom_value', $decoded['custom_claim']);
    }

    /**
     * Test constructor rejects short secret key
     */
    public function testConstructorRejectsShortSecretKeyHMAC(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Secret key must be at least 32 characters long');

        new JWTTokenManager('short-key', 'HS256');
    }

    /**
     * Test token format validation
     */
    public function testTokenFormatValidation(): void
    {
        $this->expectException(TokenException::class);

        $invalidToken = 'invalid.token.format.with.too.many.parts';
        $this->tokenManager->validate($invalidToken);
    }

    /**
     * Test algorithm setting
     */
    public function testAlgorithmSetting(): void
    {
        // Test algorithm can be set (though we don't test all algos here)
        $this->tokenManager->setAlgorithm('HS256');
        $this->assertEquals('HS256', $this->tokenManager->getAlgorithm());

        // With proper key, other algorithms could be tested too
        // RS256, ES256, etc. (requires proper key format)
    }

    /**
     * Test token includes correct issuer and audience
     */
    public function testTokenIncludesIssuerAndAudience(): void
    {
        $now = time();
        $claims = [
            'client_id' => 'test-client',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $token = $this->tokenManager->generate($claims, 'my-issuer', 'my-audience');
        $decoded = $this->tokenManager->validate($token, 'my-issuer', 'my-audience');

        $this->assertEquals('my-issuer', $decoded['iss']);
        $this->assertEquals('my-audience', $decoded['aud']);
    }

    /**
     * Test token without issuer/audience requirements
     */
    public function testTokenValidationWithoutIssuerCheck(): void
    {
        $now = time();
        $claims = [
            'client_id' => 'test-client',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $token = $this->tokenManager->generate($claims, 'issuer-a', 'audience-a');
        
        // Should succeed even though we don't verify issuer/audience
        $decoded = $this->tokenManager->validate($token, '', '');
        $this->assertEquals('test-client', $decoded['client_id']);

        // But should fail if we specify wrong issuer
        $this->expectException(TokenException::class);
        $this->tokenManager->validate($token, 'issuer-b', 'audience-a');
    }

    /**
     * Test multiple scopes in token
     */
    public function testMultipleScopesInToken(): void
    {
        $now = time();
        $scopes = ['read', 'write', 'admin', 'analytics'];
        $claims = [
            'client_id' => 'test-client',
            'scopes' => $scopes,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $token = $this->tokenManager->generate($claims);
        $decoded = $this->tokenManager->validate($token);

        $this->assertEquals($scopes, $decoded['scopes']);
    }
}
