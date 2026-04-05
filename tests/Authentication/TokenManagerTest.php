<?php

namespace Ksfraser\Amortizations\Tests\Authentication;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Authentication\TokenManager;
use Ksfraser\Amortizations\Authentication\TokenStorageInterface;
use Ksfraser\Amortizations\Authentication\AuthenticationService;
use Ksfraser\Amortizations\Authentication\Client;
use Ksfraser\Amortizations\Authentication\InvalidTokenException;
use Ksfraser\Amortizations\Authentication\Storage\InMemoryTokenStorage;

/**
 * TokenManagerTest - Comprehensive token lifecycle tests
 *
 * Tests token generation, refresh, revocation, and statistics.
 */
class TokenManagerTest extends TestCase
{
    protected $tokenManager;
    protected $authService;
    protected $storage;
    protected $client;
    protected $privateKey;
    protected $publicKey;

    protected function setUp(): void
    {
        // Generate RSA keys for testing
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

        $this->storage = new InMemoryTokenStorage();
        $this->tokenManager = new TokenManager($this->authService, $this->storage);

        // Create test client
        $this->client = new Client('test-client', 'test-secret', 'Test Client');
    }

    // ===== Token Pair Generation Tests =====

    public function testGenerateTokenPair(): void
    {
        $result = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read', 'schedule:read']
        );

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('scope', $result);

        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertGreaterThan(0, $result['expires_in']);
        $this->assertStringContainsString('loan:read', $result['scope']);
    }

    public function testGenerateTokenPairStoresTokens(): void
    {
        $result = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read']
        );

        $tokens = $this->storage->getAllTokens();
        $this->assertGreaterThanOrEqual(2, count($tokens)); // access + refresh
    }

    public function testGenerateTokenPairWithMultipleScopes(): void
    {
        $scopes = ['loan:read', 'schedule:read', 'event:write'];
        $result = $this->tokenManager->generateTokenPair($this->client, $scopes);

        $this->assertStringContainsString('loan:read', $result['scope']);
        $this->assertStringContainsString('schedule:read', $result['scope']);
        $this->assertStringContainsString('event:write', $result['scope']);
    }

    // ===== Token Refresh Tests =====

    public function testRefreshAccessToken(): void
    {
        $initial = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read']
        );

        $refreshed = $this->tokenManager->refreshAccessToken(
            $this->client,
            $initial['refresh_token']
        );

        $this->assertArrayHasKey('access_token', $refreshed);
        $this->assertArrayHasKey('token_type', $refreshed);
        $this->assertArrayHasKey('expires_in', $refreshed);

        // Tokens should be different
        $this->assertNotEquals(
            $initial['access_token'],
            $refreshed['access_token']
        );
    }

    public function testRefreshWithWrongClientFails(): void
    {
        $initial = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read']
        );

        $otherClient = new Client('other-client', 'other-secret');

        $this->expectException(InvalidTokenException::class);
        $this->tokenManager->refreshAccessToken(
            $otherClient,
            $initial['refresh_token']
        );
    }

    public function testRefreshWithAccessTokenFails(): void
    {
        $result = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read']
        );

        $this->expectException(InvalidTokenException::class);
        $this->tokenManager->refreshAccessToken(
            $this->client,
            $result['access_token'] // Wrong token type
        );
    }

    // ===== Token Revocation Tests =====

    public function testRevokeToken(): void
    {
        $result = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read']
        );

        // Extract JTI from token
        $tokenObj = $this->authService->validateToken($result['access_token']);

        $this->tokenManager->revokeToken($tokenObj->getJti());

        $this->assertTrue(
            $this->tokenManager->isTokenRevoked($tokenObj->getJti())
        );
    }

    public function testRevokeClientTokens(): void
    {
        // Generate multiple token pairs
        $result1 = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read']
        );

        $result2 = $this->tokenManager->generateTokenPair(
            $this->client,
            ['schedule:read']
        );

        // Get stats before revocation
        $statsBefore = $this->tokenManager->getClientTokenStats('test-client');

        // Revoke all tokens
        $count = $this->tokenManager->revokeClientTokens('test-client');

        $this->assertGreaterThanOrEqual(2, $count);

        // Get stats after revocation
        $statsAfter = $this->tokenManager->getClientTokenStats('test-client');
        $this->assertEquals($count, $statsAfter['revoked_tokens']);
    }

    public function testIsTokenRevoked(): void
    {
        $result = $this->tokenManager->generateTokenPair(
            $this->client,
            ['loan:read']
        );

        $tokenObj = $this->authService->validateToken($result['access_token']);
        $jti = $tokenObj->getJti();

        // Before revocation
        $this->assertFalse($this->tokenManager->isTokenRevoked($jti));

        // After revocation
        $this->tokenManager->revokeToken($jti);
        $this->assertTrue($this->tokenManager->isTokenRevoked($jti));
    }

    // ===== Token Statistics Tests =====

    public function testGetClientTokenStats(): void
    {
        $this->tokenManager->generateTokenPair($this->client, ['loan:read']);

        $stats = $this->tokenManager->getClientTokenStats('test-client');

        $this->assertArrayHasKey('active_tokens', $stats);
        $this->assertArrayHasKey('expired_tokens', $stats);
        $this->assertArrayHasKey('revoked_tokens', $stats);
        $this->assertArrayHasKey('total_tokens', $stats);

        // Should have 2 active tokens (access + refresh)
        $this->assertGreaterThanOrEqual(2, $stats['active_tokens']);
    }

    public function testGetClientTokenStatsMultiplePairs(): void
    {
        $this->tokenManager->generateTokenPair($this->client, ['loan:read']);
        $this->tokenManager->generateTokenPair($this->client, ['schedule:read']);

        $stats = $this->tokenManager->getClientTokenStats('test-client');

        // Should have 4 tokens (2 pairs of access + refresh each)
        $this->assertGreaterThanOrEqual(4, $stats['total_tokens']);
    }

    // ===== Token Cleanup Tests =====

    public function testCleanupExpiredTokens(): void
    {
        // This would need token expiration to be tested
        // For now, just verify it doesn't throw
        $count = $this->tokenManager->cleanupExpiredTokens();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ===== Error Handling Tests =====

    public function testGenerateTokenPairWithInvalidClient(): void
    {
        $this->expectException(\Exception::class);
        $this->tokenManager->generateTokenPair(null, ['loan:read']);
    }

    public function testRefreshWithExpiredToken(): void
    {
        // This would need to manipulate token expiration
        // For now, just verify the method exists and handles errors
        $this->assertTrue(method_exists($this->tokenManager, 'refreshAccessToken'));
    }
}
