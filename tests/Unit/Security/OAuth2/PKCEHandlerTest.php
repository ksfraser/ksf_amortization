<?php
namespace Tests\Unit\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\PKCE\PKCEHandler;
use Ksfraser\Security\Exceptions\TokenException;

class PKCEHandlerTest extends TestCase
{
    /**
     * @var PKCEHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new PKCEHandler();
    }

    /**
     * Test generate code verifier creates valid string
     */
    public function testGenerateCodeVerifier(): void
    {
        $verifier = $this->handler->generateCodeVerifier();
        
        $this->assertNotEmpty($verifier);
        $this->assertGreaterThanOrEqual(43, strlen($verifier));
        $this->assertLessThanOrEqual(128, strlen($verifier));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-._~]+$/', $verifier);
    }

    /**
     * Test generate code verifier with custom length
     */
    public function testGenerateCodeVerifierWithCustomLength(): void
    {
        $verifier = $this->handler->generateCodeVerifier(64);
        
        $this->assertEquals(64, strlen($verifier));
    }

    /**
     * Test code verifier is random on each call
     */
    public function testGenerateCodeVerifierIsRandom(): void
    {
        $verifier1 = $this->handler->generateCodeVerifier();
        $verifier2 = $this->handler->generateCodeVerifier();
        
        $this->assertNotEquals($verifier1, $verifier2);
    }

    /**
     * Test generate code verifier rejects too short length
     */
    public function testGenerateCodeVerifierRejectsTooShortLength(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('at least 43 characters');
        
        $this->handler->generateCodeVerifier(42);
    }

    /**
     * Test generate code verifier rejects too long length
     */
    public function testGenerateCodeVerifierRejectsTooLongLength(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('must not exceed 128 characters');
        
        $this->handler->generateCodeVerifier(129);
    }

    /**
     * Test generate S256 code challenge
     */
    public function testGenerateS256CodeChallenge(): void
    {
        $verifier = $this->handler->generateCodeVerifier();
        $challenge = $this->handler->generateCodeChallenge($verifier, 'S256');
        
        $this->assertNotEmpty($challenge);
        $this->assertNotEquals($verifier, $challenge);
        // S256 is base64url, should only contain URL-safe chars
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]*$/', $challenge);
    }

    /**
     * Test generate plain code challenge
     */
    public function testGeneratePlainCodeChallenge(): void
    {
        $verifier = $this->handler->generateCodeVerifier();
        $challenge = $this->handler->generateCodeChallenge($verifier, 'plain');
        
        // Plain method: challenge equals verifier
        $this->assertEquals($verifier, $challenge);
    }

    /**
     * Test generate code challenge rejects invalid method
     */
    public function testGenerateCodeChallengeRejectsInvalidMethod(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Unsupported code challenge method');
        
        $verifier = $this->handler->generateCodeVerifier();
        $this->handler->generateCodeChallenge($verifier, 'invalid');
    }

    /**
     * Test validate S256 code challenge succeeds
     */
    public function testValidateS256CodeChallengeSucceeds(): void
    {
        $verifier = $this->handler->generateCodeVerifier();
        $challenge = $this->handler->generateCodeChallenge($verifier, 'S256');
        
        $this->assertTrue(
            $this->handler->validateCodeChallenge($verifier, $challenge, 'S256')
        );
    }

    /**
     * Test validate S256 code challenge fails with wrong verifier
     */
    public function testValidateS256CodeChallengeFails(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('Code challenge verification failed');
        
        $verifier1 = $this->handler->generateCodeVerifier();
        $verifier2 = $this->handler->generateCodeVerifier();
        $challenge = $this->handler->generateCodeChallenge($verifier1, 'S256');
        
        $this->handler->validateCodeChallenge($verifier2, $challenge, 'S256');
    }

    /**
     * Test validate plain code challenge succeeds
     */
    public function testValidatePlainCodeChallengeSucceeds(): void
    {
        $verifier = $this->handler->generateCodeVerifier();
        $challenge = $this->handler->generateCodeChallenge($verifier, 'plain');
        
        $this->assertTrue(
            $this->handler->validateCodeChallenge($verifier, $challenge, 'plain')
        );
    }

    /**
     * Test validate plain code challenge fails with wrong verifier
     */
    public function testValidatePlainCodeChallengeFails(): void
    {
        $this->expectException(TokenException::class);
        
        $verifier1 = $this->handler->generateCodeVerifier();
        $verifier2 = $this->handler->generateCodeVerifier();
        $challenge = $this->handler->generateCodeChallenge($verifier1, 'plain');
        
        $this->handler->validateCodeChallenge($verifier2, $challenge, 'plain');
    }

    /**
     * Test validate verifier with valid format
     */
    public function testValidateVerifierWithValidFormat(): void
    {
        // Valid 43-character verifier (minimum length)
        // 'a' repeated 43 times
        $validVerifier = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa1'; // Exactly 43 chars
        
        $this->assertTrue($this->handler->validateVerifier($validVerifier));
    }

    /**
     * Test validate verifier rejects too short
     */
    public function testValidateVerifierRejectsTooShort(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('too short');
        
        $this->handler->validateVerifier('short');
    }

    /**
     * Test validate verifier rejects too long
     */
    public function testValidateVerifierRejectsTooLong(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('too long');
        
        $this->handler->validateVerifier(str_repeat('a', 129));
    }

    /**
     * Test validate verifier rejects invalid characters
     */
    public function testValidateVerifierRejectsInvalidCharacters(): void
    {
        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('invalid characters');
        
        // Valid length but invalid characters (spaces, special chars)
        $this->handler->validateVerifier(str_repeat('a', 50) . '!@#$');
    }

    /**
     * Test is PKCE required for public client
     */
    public function testIsPKCERequiredForPublicClient(): void
    {
        $this->assertTrue($this->handler->isPKCERequired('public'));
    }

    /**
     * Test is PKCE required for confidential client
     */
    public function testIsPKCERequiredForConfidentialClient(): void
    {
        $this->assertFalse($this->handler->isPKCERequired('confidential'));
    }

    /**
     * Test get recommended parameters
     */
    public function testGetRecommendedParameters(): void
    {
        $params = $this->handler->getRecommendedParameters();
        
        $this->assertArrayHasKey('code_verifier', $params);
        $this->assertArrayHasKey('code_challenge', $params);
        $this->assertArrayHasKey('code_challenge_method', $params);
        $this->assertEquals('S256', $params['code_challenge_method']);
        $this->assertGreaterThanOrEqual(43, strlen($params['code_verifier']));
        $this->assertNotEmpty($params['code_challenge']);
    }
}
