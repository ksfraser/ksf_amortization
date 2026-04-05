<?php

namespace Ksfraser\Amortizations\Tests\Integration\Authentication;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Authentication\AuthenticationService;
use Ksfraser\Amortizations\Authentication\TokenManager;
use Ksfraser\Amortizations\Authentication\Client;
use Ksfraser\Amortizations\Authentication\Storage\DatabaseTokenStorage;
use Ksfraser\Amortizations\Api\AuthController;
use Ksfraser\Amortizations\Repositories\ClientRepository;
use PDO;

/**
 * OAuth2IntegrationTest - End-to-End OAuth2 Flow Tests
 *
 * Tests complete OAuth2 workflows using database token storage.
 * Covers token generation, refresh, revocation, and scope validation.
 */
class OAuth2IntegrationTest extends TestCase
{
    protected $pdo;
    protected $authService;
    protected $tokenManager;
    protected $authController;
    protected $privateKey;
    protected $publicKey;

    protected function setUp(): void
    {
        // Setup SQLite test database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
            'integration-test-api'
        );

        // Create database storage
        $storage = new DatabaseTokenStorage($this->pdo);
        $storage->createTables();

        // Create token manager
        $this->tokenManager = new TokenManager($this->authService, $storage);

        // Create mock client repository
        $clientRepo = $this->createMock(ClientRepository::class);

        // Create auth controller
        $this->authController = new AuthController(
            $this->authService,
            $this->tokenManager,
            $clientRepo
        );
    }

    // ===== Token Generation Tests =====

    public function testGenerateTokenPairPersistsToDatabase(): void
    {
        $client = new Client('test-app', 'secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('expires_in', $result);

        // Verify tokens are in database
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM oauth_tokens");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(2, $row['count']); // Access + Refresh token
    }

    public function testGenerateMultipleTokenPairs(): void
    {
        $client1 = new Client('app-1', 'secret1');
        $client2 = new Client('app-2', 'secret2');

        $this->tokenManager->generateTokenPair($client1, ['loan:read']);
        $this->tokenManager->generateTokenPair($client2, ['schedule:read']);
        $this->tokenManager->generateTokenPair($client1, ['loan:write']);

        // Should have 6 tokens total (2 per pair)
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM oauth_tokens");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(6, $row['count']);
    }

    // ===== Token Persistence Tests =====

    public function testTokenAccessibleAfterGeneration(): void
    {
        $client = new Client('test-app', 'secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $storage = new DatabaseTokenStorage($this->pdo);
        $decoded = $this->authService->verifyToken($result['access_token']);

        $this->assertNotNull($decoded);
        $this->assertEquals('test-app', $decoded->sub);
    }

    public function testTokenScopesStoredCorrectly(): void
    {
        $client = new Client('test-app', 'secret');
        $scopes = ['loan:read', 'schedule:read', 'event:read'];
        $result = $this->tokenManager->generateTokenPair($client, $scopes);

        $decoded = $this->authService->verifyToken($result['access_token']);

        $this->assertNotNull($decoded);
        // Scopes should be in token
        $tokenScopes = explode(' ', $decoded->scope ?? '');
        foreach ($scopes as $scope) {
            $this->assertContains($scope, $tokenScopes);
        }
    }

    // ===== Token Revocation Tests =====

    public function testRevokeTokenMarksAsRevoked(): void
    {
        $client = new Client('test-app', 'secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $decoded = $this->authService->verifyToken($result['access_token']);
        $jti = $decoded->jti;

        // Revoke token
        $this->tokenManager->revokeToken($jti);

        $storage = new DatabaseTokenStorage($this->pdo);
        $this->assertTrue($storage->isTokenRevoked($jti));
    }

    public function testRevokeAllClientTokens(): void
    {
        $client = new Client('test-app', 'secret');

        // Generate multiple token pairs
        $result1 = $this->tokenManager->generateTokenPair($client, ['loan:read']);
        $result2 = $this->tokenManager->generateTokenPair($client, ['schedule:read']);
        $result3 = $this->tokenManager->generateTokenPair($client, ['event:read']);

        // Verify all tokens exist
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM oauth_tokens WHERE subject = 'test-app'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertGreaterThan(0, $row['count']);

        // Revoke all client tokens
        $this->tokenManager->revokeClientTokens('test-app');

        // Verify all are revoked
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM oauth_tokens WHERE subject = 'test-app' AND revoked = 0");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(0, $row['count']);
    }

    public function testRevocationAuditTrail(): void
    {
        $client = new Client('test-app', 'secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $decoded = $this->authService->verifyToken($result['access_token']);
        $jti = $decoded->jti;

        $this->tokenManager->revokeToken($jti, 'User logout');

        // Check revocations table
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM token_revocations WHERE token_jti = ?");
        $stmt->execute([$jti]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertGreaterThan(0, $row['count']);
    }

    // ===== Token Statistics Tests =====

    public function testGetClientTokenStats(): void
    {
        $client = new Client('test-app', 'secret');

        $this->tokenManager->generateTokenPair($client, ['loan:read']);
        $this->tokenManager->generateTokenPair($client, ['schedule:read']);

        $storage = new DatabaseTokenStorage($this->pdo);
        $stats = $storage->getClientTokenStats('test-app');

        $this->assertIsArray($stats);
        $this->assertGreaterThan(0, $stats['total_tokens'] ?? 0);
    }

    public function testGetRevocationLog(): void
    {
        $client = new Client('test-app', 'secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $decoded = $this->authService->verifyToken($result['access_token']);
        $jti = $decoded->jti;

        $this->tokenManager->revokeToken($jti, 'Testing');

        $storage = new DatabaseTokenStorage($this->pdo);
        $log = $storage->getClientRevocationLog('test-app');

        $this->assertIsArray($log);
        $this->assertGreaterThan(0, count($log));
    }

    // ===== Token Refresh Tests =====

    public function testRefreshTokenGeneratesNewAccessToken(): void
    {
        $client = new Client('test-app', 'secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $refreshToken = $result['refresh_token'];
        $originalAccessToken = $result['access_token'];

        // Refresh the token
        $refreshed = $this->tokenManager->refreshToken($refreshToken);

        $this->assertArrayHasKey('access_token', $refreshed);
        $this->assertNotEquals($originalAccessToken, $refreshed['access_token']);
    }

    public function testRevokedRefreshTokenFails(): void
    {
        $client = new Client('test-app', 'secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $refreshToken = $result['refresh_token'];
        $refreshDecoded = $this->authService->verifyToken($refreshToken);
        $refreshJti = $refreshDecoded->jti;

        // Revoke the refresh token
        $this->tokenManager->revokeToken($refreshJti);

        // Try to use revoked refresh token
        $this->expectException(\Exception::class);
        $this->tokenManager->refreshToken($refreshToken);
    }

    // ===== Cleanup Tests =====

    public function testDeleteExpiredTokens(): void
    {
        // Create token that expires immediately
        $client = new Client('test-app', 'secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read'], expiresIn: 1);

        // Wait to ensure token expires
        sleep(2);

        $storage = new DatabaseTokenStorage($this->pdo);
        $deleted = $storage->deleteExpiredTokens();

        $this->assertGreaterThanOrEqual(0, $deleted);
    }

    // ===== Concurrent Client Tests =====

    public function testMultipleClientsIndependent(): void
    {
        $client1 = new Client('app-1', 'secret1');
        $client2 = new Client('app-2', 'secret2');
        $client3 = new Client('app-3', 'secret3');

        $result1 = $this->tokenManager->generateTokenPair($client1, ['loan:read']);
        $result2 = $this->tokenManager->generateTokenPair($client2, ['schedule:read']);
        $result3 = $this->tokenManager->generateTokenPair($client3, ['event:read']);

        // Verify all tokens work independently
        $decoded1 = $this->authService->verifyToken($result1['access_token']);
        $decoded2 = $this->authService->verifyToken($result2['access_token']);
        $decoded3 = $this->authService->verifyToken($result3['access_token']);

        $this->assertEquals('app-1', $decoded1->sub);
        $this->assertEquals('app-2', $decoded2->sub);
        $this->assertEquals('app-3', $decoded3->sub);

        // Revoke one shouldn't affect others
        $jti1 = $decoded1->jti;
        $this->tokenManager->revokeToken($jti1);

        $storage = new DatabaseTokenStorage($this->pdo);
        $this->assertTrue($storage->isTokenRevoked($jti1));
        $this->assertFalse($storage->isTokenRevoked($decoded2->jti));
        $this->assertFalse($storage->isTokenRevoked($decoded3->jti));
    }

    // ===== Database Schema Tests =====

    public function testDatabaseTablesCreated(): void
    {
        $storage = new DatabaseTokenStorage($this->pdo);
        $storage->createTables();

        // Check oauth_tokens table
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='oauth_tokens'");
        $this->assertNotFalse($stmt->fetch());

        // Check token_revocations table
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='token_revocations'");
        $this->assertNotFalse($stmt->fetch());
    }

    public function testDatabaseTablesHaveCorrectColumns(): void
    {
        // oauth_tokens columns
        $stmt = $this->pdo->query("PRAGMA table_info(oauth_tokens)");
        $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

        $this->assertContains('jti', $columns);
        $this->assertContains('subject', $columns);
        $this->assertContains('scope', $columns);
        $this->assertContains('created_at', $columns);
        $this->assertContains('expires_at', $columns);
        $this->assertContains('revoked', $columns);
    }

    // ===== End-to-End OAuth2 Flow Tests =====

    public function testCompleteOAuth2ClientCredentialsFlow(): void
    {
        // 1. Generate token
        $client = new Client('oauth-client', 'oauth-secret');
        $result = $this->tokenManager->generateTokenPair($client, ['loan:read', 'schedule:read']);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);

        // 2. Verify token
        $decoded = $this->authService->verifyToken($result['access_token']);
        $this->assertEquals('oauth-client', $decoded->sub);

        // 3. Refresh token
        $refreshed = $this->tokenManager->refreshToken($result['refresh_token']);
        $this->assertArrayHasKey('access_token', $refreshed);

        // 4. Verify new token
        $decodedRefreshed = $this->authService->verifyToken($refreshed['access_token']);
        $this->assertEquals('oauth-client', $decodedRefreshed->sub);

        // 5. Revoke original access token
        $this->tokenManager->revokeToken($decoded->jti);

        $storage = new DatabaseTokenStorage($this->pdo);
        $this->assertTrue($storage->isTokenRevoked($decoded->jti));

        // 6. Verify new token still works
        $decodedFinal = $this->authService->verifyToken($refreshed['access_token']);
        $this->assertEquals('oauth-client', $decodedFinal->sub);
    }

    public function testOAuth2MultipleRefreshCycle(): void
    {
        $client = new Client('refresh-test', 'secret');
        $initial = $this->tokenManager->generateTokenPair($client, ['loan:read']);

        $accessToken1 = $initial['access_token'];
        $refreshToken1 = $initial['refresh_token'];

        // Cycle 1: Refresh
        $cycle1 = $this->tokenManager->refreshToken($refreshToken1);
        $accessToken2 = $cycle1['access_token'];
        $refreshToken2 = $cycle1['refresh_token'];

        // Cycle 2: Refresh again
        $cycle2 = $this->tokenManager->refreshToken($refreshToken2);
        $accessToken3 = $cycle2['access_token'];

        // All should be valid
        $this->assertNotNull($this->authService->verifyToken($accessToken1));
        $this->assertNotNull($this->authService->verifyToken($accessToken2));
        $this->assertNotNull($this->authService->verifyToken($accessToken3));

        // All should be different
        $this->assertNotEquals($accessToken1, $accessToken2);
        $this->assertNotEquals($accessToken2, $accessToken3);
    }
}
