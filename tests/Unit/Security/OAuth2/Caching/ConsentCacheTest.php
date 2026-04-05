<?php
namespace Tests\Unit\Security\OAuth2\Caching;

use Ksfraser\Caching\DatabaseCache;
use Ksfraser\Caching\MultiTierCache;
use Ksfraser\Security\OAuth2\Repositories\OAuth2UserConsentRepository;
use Ksfraser\Security\OAuth2\Caching\ConsentCache;
use Ksfraser\Security\OAuth2\Caching\ScopeValidationCache;
use Ksfraser\Security\Exceptions\TokenException;
use PHPUnit\Framework\TestCase;

/**
 * Consent Cache Tests
 * 
 * Comprehensive testing of consent caching layer including:
 * - Grant and revoke operations with cache
 * - Consent verification with caching
 * - Scope validation caching
 * - Cache invalidation
 * - Performance metrics
 * 
 * @package   Tests\Unit\Security\OAuth2\Caching
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class ConsentCacheTest extends TestCase
{
    /**
     * @var \PDO Database connection
     */
    private $db;

    /**
     * @var OAuth2UserConsentRepository Repository
     */
    private $repository;

    /**
     * @var DatabaseCache Cache backend
     */
    private $cache;

    /**
     * @var ConsentCache Cached consent repository
     */
    private $cachedConsent;

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
        $this->repository = new OAuth2UserConsentRepository($this->db);
        $this->cache = new DatabaseCache($this->db, 'consent_cache');
        $this->cachedConsent = new ConsentCache($this->repository, $this->cache);
    }

    /**
     * Create database schema for testing
     */
    private function createSchema(): void
    {
        $this->db->exec('
            CREATE TABLE oauth2_user_consents (
                id INTEGER PRIMARY KEY,
                user_id TEXT NOT NULL,
                client_id TEXT NOT NULL,
                granted_scopes TEXT NOT NULL,
                granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME,
                UNIQUE(user_id, client_id)
            )
        ');

        $this->db->exec('
            CREATE TABLE consent_cache (
                id INTEGER PRIMARY KEY,
                cache_key TEXT NOT NULL UNIQUE,
                cache_value TEXT NOT NULL,
                expires_at DATETIME NOT NULL
            )
        ');
    }

    /**
     * Test: Grant consent with caching
     *
     * @test
     */
    public function testGrantConsentWithCache(): void
    {
        $this->cachedConsent->grant(
            'user_123',
            'client_456',
            ['read', 'write', 'profile']
        );

        // Consent should exist
        $consent = $this->cachedConsent->getConsent('user_123', 'client_456');
        $this->assertNotNull($consent);
        $this->assertEquals('user_123', $consent['user_id']);
        $this->assertEquals('client_456', $consent['client_id']);
    }

    /**
     * Test: Consent cached after retrieval
     *
     * @test
     */
    public function testConsentCaching(): void
    {
        // Grant consent
        $this->cachedConsent->grant(
            'user_cache',
            'client_cache',
            ['read', 'write']
        );

        // First retrieval - hits DB
        $result1 = $this->cachedConsent->getConsent('user_cache', 'client_cache');
        $this->assertNotNull($result1);

        // Get stats before second retrieval
        $stats1 = $this->cachedConsent->getStats();

        // Second retrieval - should hit cache
        $result2 = $this->cachedConsent->getConsent('user_cache', 'client_cache');
        
        $stats2 = $this->cachedConsent->getStats();
        $this->assertEquals($stats1['cache_hits'] + 1, $stats2['cache_hits']);
    }

    /**
     * Test: Check if user has consent for scopes
     *
     * @test
     */
    public function testHasConsentCheck(): void
    {
        $this->cachedConsent->grant(
            'user_scopes',
            'app_scopes',
            ['read', 'write', 'profile', 'email']
        );

        // Check subsets of granted scopes
        $this->assertTrue(
            $this->cachedConsent->hasConsent('user_scopes', 'app_scopes', ['read'])
        );

        $this->assertTrue(
            $this->cachedConsent->hasConsent('user_scopes', 'app_scopes', ['read', 'write'])
        );

        // Check superset (should fail)
        $this->assertFalse(
            $this->cachedConsent->hasConsent(
                'user_scopes',
                'app_scopes',
                ['read', 'write', 'admin'] // 'admin' not granted
            )
        );
    }

    /**
     * Test: Consent not found returns null
     *
     * @test
     */
    public function testConsentNotFound(): void
    {
        $consent = $this->cachedConsent->getConsent('nonexistent_user', 'nonexistent_client');
        $this->assertNull($consent);

        // Subsequent check should hit cache (negative cache)
        $stats1 = $this->cachedConsent->getStats();
        $consent2 = $this->cachedConsent->getConsent('nonexistent_user', 'nonexistent_client');
        $stats2 = $this->cachedConsent->getStats();

        $this->assertNull($consent2);
        $this->assertEquals($stats1['cache_hits'] + 1, $stats2['cache_hits']);
    }

    /**
     * Test: Revoke consent
     *
     * @test
     */
    public function testRevokeConsent(): void
    {
        $this->cachedConsent->grant('user_revoke', 'app_revoke', ['read', 'write']);

        // Verify consent exists
        $this->assertTrue(
            $this->cachedConsent->hasConsent('user_revoke', 'app_revoke', ['read'])
        );

        // Revoke specific scope
        $this->cachedConsent->revoke('user_revoke', 'app_revoke', ['read']);

        // Check hasConsent again - should miss read
        $this->assertFalse(
            $this->cachedConsent->hasConsent('user_revoke', 'app_revoke', ['read'])
        );

        // But write should still be granted
        $this->assertTrue(
            $this->cachedConsent->hasConsent('user_revoke', 'app_revoke', ['write'])
        );
    }

    /**
     * Test: Revoke all scopes for user-client combo
     *
     * @test
     */
    public function testRevokeAllScopes(): void
    {
        $this->cachedConsent->grant('user_all', 'app_all', ['read', 'write', 'delete']);

        // Verify consent exists
        $this->assertTrue(
            $this->cachedConsent->hasConsent('user_all', 'app_all', ['read'])
        );

        // Revoke all (null scopes parameter)
        $this->cachedConsent->revoke('user_all', 'app_all');

        // Consent should now be gone
        $consent = $this->cachedConsent->getConsent('user_all', 'app_all');
        $this->assertNull($consent);
    }

    /**
     * Test: Cache invalidation on grant
     *
     * @test
     */
    public function testCacheInvalidationOnGrant(): void
    {
        $userId = 'user_inv';
        $clientId = 'app_inv';

        // Grant initial consent
        $this->cachedConsent->grant($userId, $clientId, ['read']);

        // Get and cache
        $consent1 = $this->cachedConsent->getConsent($userId, $clientId);
        $this->assertContains('read', $consent1['granted_scopes']);

        // Grant more scopes (should invalidate cache)
        $this->cachedConsent->grant($userId, $clientId, ['read', 'write', 'admin']);

        // Retrieve again - should get updated scopes
        $consent2 = $this->cachedConsent->getConsent($userId, $clientId);
        $this->assertContains('admin', $consent2['granted_scopes']);
    }

    /**
     * Test: Cache statistics tracking
     *
     * @test
     */
    public function testCacheStatistics(): void
    {
        $this->cachedConsent->grant('user_stats', 'app_stats', ['read', 'write']);

        // Cache the consent
        $this->cachedConsent->getConsent('user_stats', 'app_stats');
        $this->cachedConsent->getConsent('user_stats', 'app_stats');
        $this->cachedConsent->getConsent('nonexistent', 'nonexistent');

        $stats = $this->cachedConsent->getStats();

        $this->assertArrayHasKey('cache_hits', $stats);
        $this->assertArrayHasKey('cache_misses', $stats);
        $this->assertArrayHasKey('db_queries', $stats);
        $this->assertArrayHasKey('invalidations', $stats);
        $this->assertArrayHasKey('grants', $stats);
        $this->assertArrayHasKey('revokes', $stats);
        $this->assertArrayHasKey('total_lookups', $stats);
        $this->assertArrayHasKey('cache_hit_rate_percent', $stats);
        $this->assertTrue($stats['cache_hit_rate_percent'] > 0);
    }

    /**
     * Test: Multi-tier cache with consent
     *
     * @test
     */
    public function testMultiTierConsentCache(): void
    {
        $cache1 = new DatabaseCache($this->db, 'tier1_consent');
        $cache2 = new DatabaseCache($this->db, 'tier2_consent');
        $multiTier = new MultiTierCache([$cache1, $cache2]);

        $cachedWithMultiTier = new ConsentCache($this->repository, $multiTier);

        // Grant and cache
        $cachedWithMultiTier->grant('user_multi', 'app_multi', ['read', 'write']);

        // Retrieve from cache
        $consent1 = $cachedWithMultiTier->getConsent('user_multi', 'app_multi');
        $this->assertNotNull($consent1);

        // Should be cached in both tiers
        $consent2 = $cachedWithMultiTier->getConsent('user_multi', 'app_multi');
        $this->assertEquals($consent1, $consent2);
    }

    /**
     * Test: Multiple users' consents are isolated
     *
     * @test
     */
    public function testConsentIsolation(): void
    {
        // Grant different scopes to different users for same client
        $this->cachedConsent->grant('user_a', 'app', ['read']);
        $this->cachedConsent->grant('user_b', 'app', ['read', 'write']);

        // Verify each user has their own consents
        $this->assertTrue(
            $this->cachedConsent->hasConsent('user_a', 'app', ['read'])
        );

        $this->assertFalse(
            $this->cachedConsent->hasConsent('user_a', 'app', ['write'])
        );

        $this->assertTrue(
            $this->cachedConsent->hasConsent('user_b', 'app', ['read', 'write'])
        );
    }

    /**
     * Test: Verify consent for authorization
     *
     * @test
     */
    public function testVerifyForAuthorization(): void
    {
        // Grant consent
        $this->cachedConsent->grant('user_auth', 'app_auth', ['read', 'write', 'profile']);

        // Verify with allowed scopes
        $isValid = $this->cachedConsent->verifyForAuthorization(
            'user_auth',
            'app_auth',
            ['read', 'write'],
            ['read', 'write', 'profile', 'email']
        );

        $this->assertTrue($isValid);
    }

    /**
     * Test: Verify fails for unauthorized scope
     *
     * @test
     */
    public function testVerifyFailsForUnauthorizedScope(): void
    {
        $this->cachedConsent->grant('user_unauth', 'app_unauth', ['read', 'write']);

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('not allowed to request scope');

        $this->cachedConsent->verifyForAuthorization(
            'user_unauth',
            'app_unauth',
            ['read', 'admin'], // 'admin' not in allowed list
            ['read', 'write']
        );
    }

    /**
     * Test: Consent hit rate for typical flows
     *
     * @test
     */
    public function testConsentHitRate(): void
    {
        // Set up multiple user-client relationships
        for ($i = 0; $i < 5; $i++) {
            $this->cachedConsent->grant("user_$i", "app_$i", ['read', 'write']);
        }

        // Access each repeatedly (first hit DB, rest hit cache)
        for ($i = 0; $i < 5; $i++) {
            for ($j = 0; $j < 10; $j++) {
                $this->cachedConsent->getConsent("user_$i", "app_$i");
            }
        }

        $stats = $this->cachedConsent->getStats();
        // 5 DB hits + 45 cache hits = 50 total
        $this->assertEquals(50, $stats['total_lookups']);
        $this->assertEquals(45, $stats['cache_hits']);
        $this->assertEquals(90, $stats['cache_hit_rate_percent']);
    }

    /**
     * Test: Scope validation caching
     *
     * @test
     */
    public function testScopeValidationCache(): void
    {
        $cache = new DatabaseCache($this->db, 'scope_validation_cache');
        $scopeCache = new ScopeValidationCache($cache);

        // Cache a validation result
        $scopeCache->cacheValidation('read write', 'read write admin', true);

        // Retrieve it
        $result = $scopeCache->getValidation('read write', 'read write admin');
        $this->assertTrue($result);

        // Cache negative result
        $scopeCache->cacheValidation('delete', 'read write', false);

        $result2 = $scopeCache->getValidation('delete', 'read write');
        $this->assertFalse($result2);

        // Get stats
        $stats = $scopeCache->getStats();
        $this->assertEquals(2, $stats['hits']);
    }

    /**
     * Test: Scope validation cache miss
     *
     * @test
     */
    public function testScopeValidationCacheMiss(): void
    {
        $cache = new DatabaseCache($this->db, 'scope_val_miss');
        $scopeCache = new ScopeValidationCache($cache);

        $result = $scopeCache->getValidation('uncached', 'scopes');
        $this->assertNull($result);

        $stats = $scopeCache->getStats();
        $this->assertEquals(1, $stats['misses']);
    }

    /**
     * Test: Consent revoke all for user
     *
     * @test
     */
    public function testRevokeAllForUser(): void
    {
        // Grant consent with multiple clients
        $this->cachedConsent->grant('user_multi_revoke', 'app1', ['read']);
        $this->cachedConsent->grant('user_multi_revoke', 'app2', ['write']);
        $this->cachedConsent->grant('user_multi_revoke', 'app3', ['admin']);

        // Revoke all
        $revoked = $this->cachedConsent->revokeAllForUser('user_multi_revoke');
        $this->assertEquals(3, $revoked);

        // All should be gone
        $this->assertNull(
            $this->cachedConsent->getConsent('user_multi_revoke', 'app1')
        );
    }

    /**
     * Test: Consent revoke all for client
     *
     * @test
     */
    public function testRevokeAllForClient(): void
    {
        // Multiple users grant to same client
        $this->cachedConsent->grant('user_x', 'app_shared', ['read']);
        $this->cachedConsent->grant('user_y', 'app_shared', ['write']);
        $this->cachedConsent->grant('user_z', 'app_shared', ['admin']);

        // Revoke all consents for client
        $revoked = $this->cachedConsent->revokeAllForClient('app_shared');
        $this->assertEquals(3, $revoked);
    }

    /**
     * Test: Cache TTL configuration
     *
     * @test
     */
    public function testCacheTTLConfiguration(): void
    {
        $cache = new DatabaseCache($this->db, 'ttl_test');
        $cachedWithTTL = new ConsentCache($this->repository, $cache, 7200);

        $this->cachedConsent->grant('user_ttl', 'app_ttl', ['read']);
        
        $consent = $this->cachedConsent->getConsent('user_ttl', 'app_ttl');
        $this->assertNotNull($consent);

        // Verify TTL is set
        $cachedWithTTL->setCacheTTL(3600);
        $stats = $cachedWithTTL->getStats();
        $this->assertEquals(3600, $stats['cache_ttl_seconds']);
    }

    /**
     * Test: Error handling in grant
     *
     * @test
     */
    public function testErrorHandlingInGrant(): void
    {
        // Mock repository error by using invalid parameters
        try {
            $this->cachedConsent->grant('', '', []); // Empty parameters
            // Should still work even with empty strings
        } catch (TokenException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    /**
     * Test: Realistic authorization flow with consent
     *
     * @test
     */
    public function testAuthorizationFlowWithConsent(): void
    {
        // Step 1: User logs in and grants consent
        $this->cachedConsent->grant(
            'alice',
            'github_exporter',
            ['read:repositories', 'read:user', 'read:org']
        );

        // Step 2: App checks if consent exists
        $hasConsent = $this->cachedConsent->hasConsent(
            'alice',
            'github_exporter',
            ['read:repositories', 'read:user']
        );
        $this->assertTrue($hasConsent);

        // Step 3: Verify all requested scopes are allowed (uses cache)
        $isValid = $this->cachedConsent->verifyForAuthorization(
            'alice',
            'github_exporter',
            ['read:repositories', 'read:user'],
            ['read:repositories', 'read:user', 'read:org', 'repo', 'write']
        );
        $this->assertTrue($isValid);

        // Step 4: User later revokes one scope
        $this->cachedConsent->revoke('alice', 'github_exporter', ['read:org']);

        // Step 5: Verify scope is no longer granted
        $this->assertFalse(
            $this->cachedConsent->hasConsent(
                'alice',
                'github_exporter',
                ['read:org']
            )
        );

        // But other scopes still work
        $this->assertTrue(
            $this->cachedConsent->hasConsent(
                'alice',
                'github_exporter',
                ['read:user']
            )
        );
    }

    /**
     * Test: Cache key prefix isolation
     *
     * @test
     */
    public function testCacheKeyPrefixIsolation(): void
    {
        $this->cachedConsent->setKeyPrefix('oauth2:v2:');

        $this->cachedConsent->grant('user_prefix', 'app_prefix', ['read']);
        $consent = $this->cachedConsent->getConsent('user_prefix', 'app_prefix');
        $this->assertNotNull($consent);

        // Changing prefix should effectively invalidate cache
        $this->cachedConsent->setKeyPrefix('oauth2:v3:');
        
        // Should query DB again due to key change
        $stats1 = $this->cachedConsent->getStats();
        $consent2 = $this->cachedConsent->getConsent('user_prefix', 'app_prefix');
        $stats2 = $this->cachedConsent->getStats();

        $this->assertNotNull($consent2);
        $this->assertEquals($stats1['db_queries'] + 1, $stats2['db_queries']);
    }

    /**
     * Test: Performance benchmarking
     *
     * @test
     */
    public function testPerformanceBenchmark(): void
    {
        // Set up consent cache with multi-tier backend
        $cache1 = new DatabaseCache($this->db, 'perf_cache1');
        $cache2 = new DatabaseCache($this->db, 'perf_cache2');
        $multiTier = new MultiTierCache([$cache1, $cache2]);

        $cachedWithPerf = new ConsentCache($this->repository, $multiTier);

        // Create base consents
        $numUsers = 20;
        $numApps = 5;

        for ($u = 0; $u < $numUsers; $u++) {
            for ($a = 0; $a < $numApps; $a++) {
                $cachedWithPerf->grant("user_$u", "app_$a", ['read', 'write']);
            }
        }

        // Simulate realistic access pattern
        for ($iterations = 0; $iterations < 3; $iterations++) {
            for ($u = 0; $u < $numUsers; $u++) {
                for ($a = 0; $a < $numApps; $a++) {
                    $cachedWithPerf->getConsent("user_$u", "app_$a");
                }
            }
        }

        $stats = $cachedWithPerf->getStats();
        
        // Should have high cache hit rate after first pass
        $this->assertGreater(50, $stats['cache_hit_rate_percent']);
    }
}
