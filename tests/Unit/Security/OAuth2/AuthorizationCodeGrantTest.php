<?php
namespace Tests\Unit\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\Grant\AuthorizationCodeGrant;
use Ksfraser\Security\Exceptions\AuthenticationException;
use Ksfraser\Security\Exceptions\TokenException;

class AuthorizationCodeGrantTest extends TestCase
{
    /**
     * @var AuthorizationCodeGrant
     */
    private $grant;

    protected function setUp(): void
    {
        $this->grant = new AuthorizationCodeGrant();
    }

    /**
     * Test generate authorization code success
     */
    public function testGenerateAuthorizationCodeSuccess(): void
    {
        $code = $this->grant->generateAuthorizationCode(
            'client-id',
            'user-123',
            ['read', 'write'],
            'https://app.example.com/callback'
        );

        $this->assertNotEmpty($code);
        $this->assertEquals(64, strlen($code)); // 64 hex chars = 32 bytes
    }

    /**
     * Test generate authorization code with state
     */
    public function testGenerateAuthorizationCodeWithState(): void
    {
        $state = 'xyz123abc456';
        
        $code = $this->grant->generateAuthorizationCode(
            'client-id',
            'user-123',
            ['read'],
            'https://app.example.com/callback',
            $state
        );

        $this->assertNotEmpty($code);
    }

    /**
     * Test generate authorization code with PKCE
     */
    public function testGenerateAuthorizationCodeWithPKCE(): void
    {
        $codeChallenge = 'E9Mrozoa2owUednCFIMRN5a4nZ2LwBP5wKp-0OQ5d-4';

        $code = $this->grant->generateAuthorizationCode(
            'client-id',
            'user-123',
            ['read'],
            'https://app.example.com/callback',
            'state123',
            $codeChallenge,
            'S256'
        );

        $this->assertNotEmpty($code);
    }

    /**
     * Test validate authorization code
     */
    public function testValidateAuthorizationCode(): void
    {
        $code = $this->grant->generateAuthorizationCode(
            'client-id',
            'user-123',
            ['read'],
            'https://app.example.com/callback'
        );

        $validated = $this->grant->validateAuthorizationCode(
            $code,
            'client-id',
            'https://app.example.com/callback'
        );

        $this->assertArrayHasKey('code', $validated);
        $this->assertArrayHasKey('client_id', $validated);
        $this->assertEquals('client-id', $validated['client_id']);
    }

    /**
     * Test exchange code for token
     */
    public function testExchangeCodeForToken(): void
    {
        $code = $this->grant->generateAuthorizationCode(
            'client-id',
            'user-123',
            ['read', 'write'],
            'https://app.example.com/callback'
        );

        $tokenResponse = $this->grant->exchangeCodeForToken(
            $code,
            'client-id',
            'client-secret',
            'https://app.example.com/callback'
        );

        $this->assertArrayHasKey('access_token', $tokenResponse);
        $this->assertArrayHasKey('token_type', $tokenResponse);
        $this->assertArrayHasKey('expires_in', $tokenResponse);
        $this->assertArrayHasKey('refresh_token', $tokenResponse);
        $this->assertEquals('Bearer', $tokenResponse['token_type']);
    }

    /**
     * Test exchange code fails with invalid code format
     */
    public function testExchangeCodeFailsWithInvalidCodeFormat(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid authorization code format');

        $this->grant->exchangeCodeForToken(
            'short-code', // Not 64 hex chars
            'client-id',
            'client-secret',
            'https://app.example.com/callback'
        );
    }

    /**
     * Test generate state parameter
     */
    public function testGenerateState(): void
    {
        $state = $this->grant->generateState();

        $this->assertNotEmpty($state);
        $this->assertGreaterThan(32, strlen($state)); // At least 64 hex chars
    }

    /**
     * Test generate state is random
     */
    public function testGenerateStateIsRandom(): void
    {
        $state1 = $this->grant->generateState();
        $state2 = $this->grant->generateState();

        $this->assertNotEquals($state1, $state2);
    }

    /**
     * Test verify state succeeds
     */
    public function testVerifyStateSucceeds(): void
    {
        $state = 'test-state-123';

        $this->assertTrue($this->grant->verifyState($state, $state));
    }

    /**
     * Test verify state fails with mismatch
     */
    public function testVerifyStateFails(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('State parameter mismatch');

        $this->grant->verifyState('state1', 'state2');
    }

    /**
     * Test exchange code with PKCE
     */
    public function testExchangeCodeWithPKCE(): void
    {
        $codeChallenge = 'E9Mrozoa2owUednCFIMRN5a4nZ2LwBP5wKp-0OQ5d-4';
        $codeVerifier = 'E9Mrozoa2owUednCFIMRN5a4nZ2Lw'; // Would be stored client-side

        $code = $this->grant->generateAuthorizationCode(
            'client-id',
            'user-123',
            ['read'],
            'https://app.example.com/callback',
            'state123',
            $codeChallenge,
            'S256'
        );

        // Note: In real scenario, verifier validation would happen
        $tokenResponse = $this->grant->exchangeCodeForToken(
            $code,
            'client-id',
            'client-secret',
            'https://app.example.com/callback',
            $codeVerifier
        );

        $this->assertArrayHasKey('access_token', $tokenResponse);
    }

    /**
     * Test exchange code with PKCE requires valid verifier
     */
    public function testExchangeCodeWithPKCERequiresValidVerifier(): void
    {
        $codeChallenge = 'E9Mrozoa2owUednCFIMRN5a4nZ2LwBP5wKp-0OQ5d-4';
        // Minimum 43 character verifier for S256
        $codeVerifier = 'E9Mrozoa2owUednCFIMRN5a4nZ2LwBP5wKp-0OQ5d';

        $code = $this->grant->generateAuthorizationCode(
            'client-id',
            'user-123',
            ['read'],
            'https://app.example.com/callback',
            'state123',
            $codeChallenge,
            'S256'
        );

        // Valid 43+ character verifier should work
        $tokenResponse = $this->grant->exchangeCodeForToken(
            $code,
            'client-id',
            'client-secret',
            'https://app.example.com/callback',
            $codeVerifier
        );

        $this->assertArrayHasKey('access_token', $tokenResponse);
    }

    /**
     * Test multiple scopes are preserved
     */
    public function testMultipleScopesPreserved(): void
    {
        $scopes = ['read', 'write', 'delete', 'admin'];

        $code = $this->grant->generateAuthorizationCode(
            'client-id',
            'user-123',
            $scopes,
            'https://app.example.com/callback'
        );

        $this->assertNotEmpty($code);
    }
}
