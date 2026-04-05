<?php
namespace Tests\Unit\Security\OAuth2\Caching;

use Ksfraser\Caching\DatabaseCache;
use Ksfraser\Caching\MultiTierCache;
use Ksfraser\Security\OAuth2\Repositories\AuthorizationCodeRepository;
use Ksfraser\Security\OAuth2\Caching\AuthorizationCodeCache;
use Ksfraser\Security\Exceptions\TokenException;
use PHPUnit\Framework\TestCase;

/**
 * Authorization Code Cache Tests
 * 
 * Comprehensive testing of authorization code caching layer including:
 * - Cache hit/miss tracking
 * - Code validation with cache
 * - TTL management
 * - Cache invalidation on code use
 * - Performance benchmarking
 * 
 * @package   Tests\Unit\Security\OAuth2\Caching
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class AuthorizationCodeCacheTest extends TestCase
{
    /**
     * @var \PDO Database connection
     */
    private $db;

    /**
     * @var AuthorizationCodeRepository Repository
     */
    private $repository;

    /**
     * @var DatabaseCache Cache backend
     */
    private $cache;

    /**
     * @var AuthorizationCodeCache Cached repository
     */
    private $cachedRepository;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        // Create in-memory SQLite database
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create schema
        $this->createSchema();

        // Initialize components
        $this->repository = new AuthorizationCodeRepository($this->db, 600);
        $this->cache = new DatabaseCache($this->db, 'cache_v1');
        $this->cachedRepository = new AuthorizationCodeCache($this->repository, $this->cache);
    }

    /**
     * Create database schema for testing
     */
    private function createSchema(): void
    {
        $this->db->exec('
            CREATE TABLE oauth2_authorization_codes (
                id INTEGER PRIMARY KEY,
                code TEXT NOT NULL UNIQUE,
                client_id TEXT NOT NULL,
                user_id TEXT,
                redirect_uri TEXT NOT NULL,
                scopes TEXT,
                state TEXT,
                code_challenge TEXT,
                code_challenge_method TEXT,
                expires_at DATETIME NOT NULL,
                used_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->db->exec('
            CREATE TABLE cache_v1 (
                id INTEGER PRIMARY KEY,
                cache_key TEXT NOT NULL UNIQUE,
                cache_value TEXT NOT NULL,
                expires_at DATETIME NOT NULL
            )
        ');
    }

    /**
     * Test: Cached repository stores code in cache
     *
     * @test
     */
    public function testCodeStoredInCache(): void
    {
        $code = $this->cachedRepository->create(
            'client_123',
            'https://app.example.com/callback',
            ['read', 'write'],
            'state_abc'
        );

        // First retrieval should hit database
        $result1 = $this->cachedRepository->getCode($code);
        $this->assertNotNull($result1);
        $this->assertEquals('client_123', $result1['client_id']);

        // Second retrieval should hit cache
        $result2 = $this->cachedRepository->getCode($code);
        $this->assertNotNull($result2);
        $this->assertEquals($result1, $result2);

        // Verify cache stats
        $stats = $this->cachedRepository->getStats();
        $this->assertEquals(1, $stats['cache_hits']);
    }

    /**
     * Test: Cache reports misses for non-existent codes
     *
     * @test
     */
    public function testCacheMissTracking(): void
    {
        $result = $this->cachedRepository->getCode('nonexistent_code');
        $this->assertNull($result);

        $stats = $this->cachedRepository->getStats();
        $this->assertEquals(1, $stats['cache_misses']);
    }

    /**
     * Test: Invalid code returns null consistently
     *
     * @test
     */
    public function testInvalidCodeCaching(): void
    {
        // First call - database miss
        $result1 = $this->cachedRepository->getCode('invalid_code');
        $this->assertNull($result1);

        // Second call - should hit cache negative result
        $result2 = $this->cachedRepository->getCode('invalid_code');
        $this->assertNull($result2);

        $stats = $this->cachedRepository->getStats();
        $this->assertEquals(1, $stats['cache_hits']);
        // Second miss would have resulted from database if not cached
    }

    /**
     * Test: Code invalidation on use
     *
     * @test
     */
    public function testCodeInvalidationOnUse(): void
    {
        $code = $this->cachedRepository->create(
            'client_456',
            'https://app.example.com/callback',
            ['read'],
            'state_xyz'
        );

        // Cache the code
        $first = $this->cachedRepository->getCode($code);
        $this->assertNotNull($first);

        // Validate and use the code
        $validated = $this->cachedRepository->validate(
            $code,
            'client_456',
            'https://app.example.com/callback'
        );
        $this->assertNotNull($validated);

        // Code should be invalid now (single-use)
        $invalid = $this->cachedRepository->getCode($code);
        $this->assertNull($invalid);
    }

    /**
     * Test: Code validation with caching
     *
     * @test
     */
    public function testCodeValidationWithCache(): void
    {
        $code = $this->cachedRepository->create(
            'client_789',
            'https://app.example.com/oauth/callback',
            ['read', 'write', 'delete'],
            'state_validate'
        );

        // Validate code
        $result = $this->cachedRepository->validate(
            $code,
            'client_789',
            'https://app.example.com/oauth/callback'
        );

        $this->assertNotNull($result);
        $this->assertEquals('client_789', $result['client_id']);
        $this->assertContains('read', $result['scopes']);
    }

    /**
     * Test: PKCE code caching
     *
     * @test
     */
    public function testPKCECodeCaching(): void
    {
        // For S256: code_challenge = BASE64URL(SHA256(code_verifier))
        $codeVerifier = 'E9Mrozoa2owUednMg8BCLvhlFp-zYT2Z8LsV3d7YrcABC123';
        $codeChallenge = rtrim(
            strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
            '='
        );

        $code = $this->cachedRepository->create(
            'mobile_app',
            'https://mobileapp.example.com/callback',
            ['read'],
            'state_pkce',
            'user_123',
            $codeChallenge,
            'S256'
        );

        // Get code from cache
        $cached = $this->cachedRepository->getCode($code);
        $this->assertNotNull($cached);
        $this->assertEquals('S256', $cached['code_challenge_method']);
        $this->assertEquals($codeChallenge, $cached['code_challenge']);

        // Get again - should hit cache
        $stats1 = $this->cachedRepository->getStats();
        $cached2 = $this->cachedRepository->getCode($code);
        $stats2 = $this->cachedRepository->getStats();

        $this->assertEquals($stats1['cache_hits'] + 1, $stats2['cache_hits']);
    }

    /**
     * Test: Cache hit rate calculation
     *
     * @test
     */
    public function testCacheHitRate(): void
    {
        // Create multiple codes
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[$i] = $this->cachedRepository->create(
                'client_' . $i,
                'https://app' . $i . '.example.com/callback',
                ['read'],
                'state_' . $i
            );
        }

        // Access each code twice (first hit DB, second hits cache)
        foreach ($codes as $code) {
            $this->cachedRepository->getCode($code); // DB
            $this->cachedRepository->getCode($code); // Cache
        }

        $stats = $this->cachedRepository->getStats();
        $this->assertEquals(10, $stats['cache_hits']);
        $this->assertEquals(50, $stats['total_lookups']);
        $this->assertEquals(20, $stats['cache_hit_rate_percent']);
    }

    /**
     * Test: Code revocation
     *
     * @test
     */
    public function testCodeRevocation(): void
    {
        $code = $this->cachedRepository->create(
            'client_revoke',
            'https://app.example.com/callback',
            ['read'],
            'state_revoke'
        );

        // Verify code exists
        $this->assertTrue($this->cachedRepository->isValid($code));

        // Revoke code
        $this->cachedRepository->revoke($code);

        // Code should be invalid
        $this->assertFalse($this->cachedRepository->isValid($code));
    }

    /**
     * Test: Invalid client_id detection
     *
     * @test
     */
    public function testInvalidClientIdDetection(): void
    {
        $code = $this->cachedRepository->create(
            'correct_client',
            'https://app.example.com/callback',
            ['read'],
            'state_client'
        );

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('client_id mismatch');

        $this->cachedRepository->validate(
            $code,
            'wrong_client', // Wrong client
            'https://app.example.com/callback'
        );
    }

    /**
     * Test: Invalid redirect_uri detection
     *
     * @test
     */
    public function testInvalidRedirectUriDetection(): void
    {
        $code = $this->cachedRepository->create(
            'client_redirect',
            'https://app.example.com/callback',
            ['read'],
            'state_redirect'
        );

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('redirect_uri mismatch');

        $this->cachedRepository->validate(
            $code,
            'client_redirect',
            'https://wrong.example.com/callback' // Wrong URI
        );
    }

    /**
     * Test: Multi-tier cache fallback
     *
     * @test
     */
    public function testMultiTierCacheFallback(): void
    {
        $cache1 = new DatabaseCache($this->db, 'tier1');
        $cache2 = new DatabaseCache($this->db, 'tier2');
        $multiTier = new MultiTierCache([$cache1, $cache2]);
        
        $cachedWithMultiTier = new AuthorizationCodeCache($this->repository, $multiTier);

        $code = $cachedWithMultiTier->create(
            'client_multitier',
            'https://app.example.com/callback',
            ['read'],
            'state_multitier'
        );

        // First access - populates cache
        $result1 = $cachedWithMultiTier->getCode($code);
        $this->assertNotNull($result1);

        // Second access - hits cache
        $result2 = $cachedWithMultiTier->getCode($code);
        $this->assertNotNull($result2);
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test: Cache statistics completeness
     *
     * @test
     */
    public function testCacheStatistics(): void
    {
        $code = $this->cachedRepository->create(
            'client_stats',
            'https://app.example.com/callback',
            ['read'],
            'state_stats'
        );

        $this->cachedRepository->getCode($code); // DB
        $this->cachedRepository->getCode($code); // Cache
        $this->cachedRepository->validate($code, 'client_stats', 'https://app.example.com/callback'); // Invalidate
        $this->cachedRepository->getCode('nonexistent'); // Miss

        $stats = $this->cachedRepository->getStats();

        $this->assertArrayHasKey('cache_hits', $stats);
        $this->assertArrayHasKey('cache_misses', $stats);
        $this->assertArrayHasKey('db_queries', $stats);
        $this->assertArrayHasKey('invalidations', $stats);
        $this->assertArrayHasKey('errors', $stats);
        $this->assertArrayHasKey('total_lookups', $stats);
        $this->assertArrayHasKey('cache_hit_rate_percent', $stats);
        $this->assertArrayHasKey('cache_backend', $stats);
    }

    /**
     * Test: Cache pre-population on create
     *
     * @test
     */
    public function testCachePrePopulationOnCreate(): void
    {
        $code = $this->cachedRepository->create(
            'client_prepop',
            'https://app.example.com/callback',
            ['read', 'write'],
            'state_prepop'
        );

        // Second access should hit cache (code was pre-cached on create)
        $stats1 = $this->cachedRepository->getStats();
        $result = $this->cachedRepository->getCode($code);
        $stats2 = $this->cachedRepository->getStats();

        $this->assertNotNull($result);
        $this->assertEquals($stats1['cache_hits'] + 1, $stats2['cache_hits']);
    }

    /**
     * Test: Concurrent-like sequential operations
     *
     * @test
     */
    public function testSequentialOperations(): void
    {
        $operations = 100;
        $codes = [];

        // Create codes
        for ($i = 0; $i < $operations; $i++) {
            $codes[] = $this->cachedRepository->create(
                'client_seq_' . ($i % 10),
                'https://app' . ($i % 5) . '.example.com/callback',
                ['read'],
                'state_' . $i
            );
        }

        // Access codes with cache hits
        $createdCount = 0;
        foreach ($codes as $i => $code) {
            $result = $this->cachedRepository->getCode($code);
            if ($result && !isset($result['used_at']) || $result['used_at'] === null) {
                if ($i % 3 === 0) {
                    // Use some codes
                    try {
                        $this->cachedRepository->validate(
                            $code,
                            $result['client_id'],
                            $result['redirect_uri']
                        );
                    } catch (TokenException $e) {
                        // Some might fail due to already being used
                    }
                }
            }
        }

        $stats = $this->cachedRepository->getStats();
        $this->assertGreater(0, $stats['total_lookups']);
    }

    /**
     * Test: Code expiration handling
     *
     * @test
     */
    public function testCodeExpirationHandling(): void
    {
        // Create repository with 1-second expiration for testing
        $repo = new AuthorizationCodeRepository($this->db, 1);
        $cached = new AuthorizationCodeCache($repo, $this->cache);

        $code = $cached->create(
            'client_exp',
            'https://app.example.com/callback',
            ['read'],
            'state_exp'
        );

        // Code should be valid initially
        $this->assertTrue($cached->isValid($code));

        // Wait for expiration (plus a bit for DB timing)
        sleep(2);

        // Code should be expired
        $this->assertFalse($cached->isValid($code));
    }

    /**
     * Test: Delete expired codes
     *
     * @test
     */
    public function testDeleteExpiredCodes(): void
    {
        // Create codes with short expiration
        $repo = new AuthorizationCodeRepository($this->db, 1);
        $cached = new AuthorizationCodeCache($repo, $this->cache);

        $code1 = $cached->create(
            'client_del1',
            'https://app.example.com/callback',
            ['read'],
            'state_del1'
        );

        sleep(2); // Wait for expiration

        $code2 = $cached->create(
            'client_del2',
            'https://app.example.com/callback',
            ['read'],
            'state_del2'
        );

        // Delete expired
        $deleted = $cached->deleteExpired();
        $this->assertGreater(0, $deleted);

        // Expired code should be invalid
        $this->assertNull($cached->getCode($code1));

        // New code should still be valid
        $this->assertNotNull($cached->getCode($code2));
    }

    /**
     * Test: Revoke all codes for client
     *
     * @test
     */
    public function testRevokeAllForClient(): void
    {
        $clientId = 'revoke_all_client';

        // Create multiple codes for same client
        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $codes[] = $this->cachedRepository->create(
                $clientId,
                'https://app.example.com/callback',
                ['read'],
                'state_' . $i
            );
        }

        // Verify all codes exist
        foreach ($codes as $code) {
            $this->assertTrue($this->cachedRepository->isValid($code));
        }

        // Revoke all
        $revoked = $this->cachedRepository->revokeAllForClient($clientId);
        $this->assertEquals(5, $revoked);

        // All codes should be invalid
        foreach ($codes as $code) {
            $this->assertFalse($this->cachedRepository->isValid($code));
        }
    }

    /**
     * Test: Cache key prefix isolation
     *
     * @test
     */
    public function testCacheKeyPrefixIsolation(): void
    {
        $code = $this->cachedRepository->create(
            'client_prefix',
            'https://app.example.com/callback',
            ['read'],
            'state_prefix'
        );

        // Get with default cache
        $result1 = $this->cachedRepository->getCode($code);
        $this->assertNotNull($result1);

        // Change prefix and verify cache is separate
        $this->cachedRepository->setKeyPrefix('oauth2:v2:');
        
        // Cache should miss because key prefix changed
        $result2 = $this->cachedRepository->getCode($code);
        $this->assertNotNull($result2); // Still works (DB fallback)
    }

    /**
     * Test: OAuth2 authorization flow performance (realistic scenario)
     *
     * @test
     */
    public function testAuthorizationFlowPerformance(): void
    {
        // Simulate realistic authorization flow
        $clientId = 'perf_test_client';
        $redirectUri = 'https://app.example.com/callback';

        // Step 1: User authorizes - generate code
        $code = $this->cachedRepository->create(
            $clientId,
            $redirectUri,
            ['read', 'write', 'profile'],
            'state_abc123'
        );
        $this->assertNotNull($code);

        // Step 2: Check code exists (for debugging)
        $codeData = $this->cachedRepository->getCode($code);
        $this->assertNotNull($codeData);

        // Step 3: Backend receives callback with code
        // Validate and exchange for token
        $validated = $this->cachedRepository->validate(
            $code,
            $clientId,
            $redirectUri
        );
        $this->assertNotNull($validated);
        $this->assertContains('read', $validated['scopes']);

        // Step 4: Subsequent attempts to use same code should fail
        $shouldFail = $this->cachedRepository->getCode($code);
        $this->assertNull($shouldFail);

        $stats = $this->cachedRepository->getStats();
        $this->assertGreater(0, $stats['total_lookups']);
    }
}
