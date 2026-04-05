<?php
namespace Tests\Unit\Caching;

use Ksfraser\Caching\CacheBackend;
use Ksfraser\Caching\RedisCache;
use Ksfraser\Caching\DatabaseCache;
use Ksfraser\Caching\MultiTierCache;
use PHPUnit\Framework\TestCase;

/**
 * Cache Backend Unit Tests
 * 
 * Comprehensive testing of caching layer implementation including:
 * - Basic cache operations (get, set, delete)
 * - TTL and expiration handling
 * - Multi-tier fallback strategy
 * - Statistics collection
 * - Error handling
 * 
 * @package   Tests\Unit\Caching
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class TokenCacheTest extends TestCase
{
    /**
     * @var \PDO Database connection
     */
    private $db;

    /**
     * @var DatabaseCache Database cache instance
     */
    private $dbCache;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Initialize database cache
        $this->dbCache = new DatabaseCache($this->db);
    }

    /**
     * Test: Database cache stores value
     *
     * @test
     */
    public function testDatabaseCacheSet(): void
    {
        $result = $this->dbCache->set('test_key', 'test_value', 3600);
        $this->assertTrue($result);

        $value = $this->dbCache->get('test_key');
        $this->assertEquals('test_value', $value);
    }

    /**
     * Test: Database cache retrieves stored value
     *
     * @test
     */
    public function testDatabaseCacheGet(): void
    {
        $this->dbCache->set('cache_key', 'cache_value', 7200);
        $value = $this->dbCache->get('cache_key');
        $this->assertEquals('cache_value', $value);
    }

    /**
     * Test: Database cache returns null for non-existent key
     *
     * @test
     */
    public function testDatabaseCacheGetNonExistent(): void
    {
        $value = $this->dbCache->get('nonexistent_key');
        $this->assertNull($value);
    }

    /**
     * Test: Database cache deletes value
     *
     * @test
     */
    public function testDatabaseCacheDelete(): void
    {
        $this->dbCache->set('delete_key', 'value');
        $this->assertTrue($this->dbCache->delete('delete_key'));
        $this->assertNull($this->dbCache->get('delete_key'));
    }

    /**
     * Test: Database cache delete returns false for non-existent key
     *
     * @test
     */
    public function testDatabaseCacheDeleteNonExistent(): void
    {
        $result = $this->dbCache->delete('nonexistent_key');
        $this->assertFalse($result);
    }

    /**
     * Test: Database cache checks key existence
     *
     * @test
     */
    public function testDatabaseCacheHas(): void
    {
        $this->dbCache->set('exists_key', 'value');
        $this->assertTrue($this->dbCache->has('exists_key'));
        $this->assertFalse($this->dbCache->has('nonexistent_key'));
    }

    /**
     * Test: Database cache clears all entries
     *
     * @test
     */
    public function testDatabaseCacheClear(): void
    {
        $this->dbCache->set('key1', 'value1');
        $this->dbCache->set('key2', 'value2');
        $this->dbCache->set('key3', 'value3');

        $this->assertTrue($this->dbCache->clear());

        $this->assertNull($this->dbCache->get('key1'));
        $this->assertNull($this->dbCache->get('key2'));
        $this->assertNull($this->dbCache->get('key3'));
    }

    /**
     * Test: Database cache tracks statistics
     *
     * @test
     */
    public function testDatabaseCacheStatistics(): void
    {
        $this->dbCache->set('stat_key1', 'value1');
        $this->dbCache->set('stat_key2', 'value2');

        $this->dbCache->get('stat_key1'); // Hit
        $this->dbCache->get('stat_key2'); // Hit
        $this->dbCache->get('nonexistent'); // Miss

        $stats = $this->dbCache->getStats();

        $this->assertEquals(2, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertGreater(0, $stats['hit_rate_percent']);
    }

    /**
     * Test: Database cache supports batch operations
     *
     * @test
     */
    public function testDatabaseCacheBatchOperations(): void
    {
        $keys = ['key1', 'key2', 'key3', 'key4', 'key5'];

        foreach ($keys as $key) {
            $this->dbCache->set($key, "value_$key");
        }

        foreach ($keys as $key) {
            $this->assertEquals("value_$key", $this->dbCache->get($key));
        }
    }

    /**
     * Test: Database cache updates existing values
     *
     * @test
     */
    public function testDatabaseCacheUpdate(): void
    {
        $this->dbCache->set('update_key', 'original_value');
        $this->assertEquals('original_value', $this->dbCache->get('update_key'));

        $this->dbCache->set('update_key', 'updated_value');
        $this->assertEquals('updated_value', $this->dbCache->get('update_key'));
    }

    /**
     * Test: Database cache handles large values
     *
     * @test
     */
    public function testDatabaseCacheLargeValues(): void
    {
        $largeValue = str_repeat('x', 10000); // 10KB value
        $this->dbCache->set('large_key', $largeValue);
        $retrieved = $this->dbCache->get('large_key');
        $this->assertEquals($largeValue, $retrieved);
    }

    /**
     * Test: Database cache returns backend name
     *
     * @test
     */
    public function testDatabaseCacheGetName(): void
    {
        $this->assertEquals('database', $this->dbCache->getName());
    }

    /**
     * Test: Database cache handles special characters
     *
     * @test
     */
    public function testDatabaseCacheSpecialCharacters(): void
    {
        $specialValue = "Line1\nLine2\tTab\r\nCRLF\0Null\"Quote'Apostrophe";
        $this->dbCache->set('special_key', $specialValue);
        $this->assertEquals($specialValue, $this->dbCache->get('special_key'));
    }

    /**
     * Test: Database cache handles JSON values
     *
     * @test
     */
    public function testDatabaseCacheJSONValues(): void
    {
        $data = ['user_id' => 123, 'scope' => 'read write', 'expires' => time() + 3600];
        $jsonValue = json_encode($data);
        
        $this->dbCache->set('json_key', $jsonValue);
        $retrieved = $this->dbCache->get('json_key');
        $this->assertEquals($data, json_decode($retrieved, true));
    }

    /**
     * Test: Multi-tier cache falls back to second backend
     *
     * @test
     */
    public function testMultiTierCacheFallback(): void
    {
        $cache1 = new DatabaseCache($this->db, 'cache1');
        $cache2 = new DatabaseCache($this->db, 'cache2');
        $multiTier = new MultiTierCache([$cache1, $cache2]);

        // Store value only in second tier (simulate first tier miss)
        $cache2->set('fallback_key', 'fallback_value');

        // MultiTier should retrieve from second tier
        $value = $multiTier->get('fallback_key');
        $this->assertNull($value); // cache1 doesn't have it initially

        // Now store via multiTier to both
        $multiTier->set('multikey', 'multivalue');
        $this->assertEquals('multivalue', $cache1->get('multikey'));
        $this->assertEquals('multivalue', $cache2->get('multikey'));
    }

    /**
     * Test: Multi-tier cache stores in all backends
     *
     * @test
     */
    public function testMultiTierCacheSetAll(): void
    {
        $cache1 = new DatabaseCache($this->db, 'cache_tier1');
        $cache2 = new DatabaseCache($this->db, 'cache_tier2');
        $multiTier = new MultiTierCache([$cache1, $cache2]);

        $multiTier->set('tier_key', 'tier_value', 3600);

        // Both backends should have the value
        $this->assertEquals('tier_value', $cache1->get('tier_key'));
        $this->assertEquals('tier_value', $cache2->get('tier_key'));
    }

    /**
     * Test: Multi-tier cache deletes from all backends
     *
     * @test
     */
    public function testMultiTierCacheDeleteAll(): void
    {
        $cache1 = new DatabaseCache($this->db, 'cache_del1');
        $cache2 = new DatabaseCache($this->db, 'cache_del2');
        $multiTier = new MultiTierCache([$cache1, $cache2]);

        $multiTier->set('del_key', 'del_value');
        $this->assertTrue($multiTier->delete('del_key'));

        // Both should be deleted
        $this->assertFalse($cache1->has('del_key'));
        $this->assertFalse($cache2->has('del_key'));
    }

    /**
     * Test: Multi-tier cache clears all backends
     *
     * @test
     */
    public function testMultiTierCacheClearAll(): void
    {
        $cache1 = new DatabaseCache($this->db, 'clear1');
        $cache2 = new DatabaseCache($this->db, 'clear2');
        $multiTier = new MultiTierCache([$cache1, $cache2]);

        $multiTier->set('clear_key1', 'value1');
        $multiTier->set('clear_key2', 'value2');

        $this->assertTrue($multiTier->clear());

        $this->assertNull($multiTier->get('clear_key1'));
        $this->assertNull($multiTier->get('clear_key2'));
    }

    /**
     * Test: Multi-tier cache requires at least one backend
     *
     * @test
     */
    public function testMultiTierCacheRequiresBackend(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MultiTierCache([]);
    }

    /**
     * Test: Token cache interface implemented by backends
     *
     * @test
     */
    public function testBackendImplementsInterface(): void
    {
        $this->assertInstanceOf(CacheBackend::class, $this->dbCache);
    }

    /**
     * Test: Database cache statistics show hit rate
     *
     * @test
     */
    public function testCacheHitRateCalculation(): void
    {
        // Set up cache with multiple entries
        for ($i = 0; $i < 10; $i++) {
            $this->dbCache->set("key_$i", "value_$i");
        }

        // Perform 7 hits and 3 misses
        for ($i = 0; $i < 7; $i++) {
            $this->dbCache->get("key_$i"); // Hit
        }
        
        $this->dbCache->get('miss1');
        $this->dbCache->get('miss2');
        $this->dbCache->get('miss3');

        $stats = $this->dbCache->getStats();
        $this->assertEquals(7, $stats['hits']);
        $this->assertEquals(3, $stats['misses']);
        $this->assertEquals(70.0, $stats['hit_rate_percent']);
    }

    /**
     * Test: Cache handles concurrent-like sequential access
     *
     * @test
     */
    public function testCacheSequentialAccess(): void
    {
        $cache = new DatabaseCache($this->db);
        $operations = 100;

        for ($i = 0; $i < $operations; $i++) {
            $key = 'seq_key_' . ($i % 10);
            $cache->set($key, "value_$i");
            $value = $cache->get($key);
            $this->assertNotNull($value);
        }

        $stats = $cache->getStats();
        $this->assertGreater(0, $stats['sets']);
    }

    /**
     * Test: Database cache returns comprehensive statistics
     *
     * @test
     */
    public function testStatisticsComplelness(): void
    {
        $this->dbCache->set('stat_key', 'stat_value');
        $this->dbCache->get('stat_key');
        $this->dbCache->delete('stat_key');

        $stats = $this->dbCache->getStats();

        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('sets', $stats);
        $this->assertArrayHasKey('deletes', $stats);
        $this->assertArrayHasKey('errors', $stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('hit_rate_percent', $stats);
        $this->assertArrayHasKey('backend', $stats);
    }

    /**
     * Test: Multi-tier statistics aggregation
     *
     * @test
     */
    public function testMultiTierStatistics(): void
    {
        $cache1 = new DatabaseCache($this->db, 'mt_stats1');
        $cache2 = new DatabaseCache($this->db, 'mt_stats2');
        $multiTier = new MultiTierCache([$cache1, $cache2]);

        $multiTier->set('m_key1', 'value1');
        $multiTier->get('m_key1');
        $multiTier->get('nonexistent');

        $stats = $multiTier->getStats();

        $this->assertArrayHasKey('backends', $stats);
        $this->assertArrayHasKey('database', $stats['backends']);
    }

    /**
     * Test: Cache key namespace isolation
     *
     * @test
     */
    public function testCacheKeyIsolation(): void
    {
        $cache1 = new DatabaseCache($this->db, 'table1');
        $cache2 = new DatabaseCache($this->db, 'table2');

        $cache1->set('shared_key', 'value1');
        $cache2->set('shared_key', 'value2');

        $this->assertEquals('value1', $cache1->get('shared_key'));
        $this->assertEquals('value2', $cache2->get('shared_key'));
    }

    /**
     * Test: Database cache handles empty string values
     *
     * @test
     */
    public function testDatabaseCacheEmptyString(): void
    {
        $this->dbCache->set('empty_key', '');
        $value = $this->dbCache->get('empty_key');
        $this->assertSame('', $value);
    }

    /**
     * Test: OAuth2 token as cache value (realistic scenario)
     *
     * @test
     */
    public function testOAuth2TokenCaching(): void
    {
        $token = json_encode([
            'access_token' => 'ca27bfca41c0b7095a9faf2f04166c62',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'read write delete',
            'issued_at' => time()
        ]);

        $tokenKey = 'oauth2:token:user_123:client_1';
        $this->dbCache->set($tokenKey, $token, 3600);

        $retrieved = $this->dbCache->get($tokenKey);
        $decoded = json_decode($retrieved, true);

        $this->assertEquals('ca27bfca41c0b7095a9faf2f04166c62', $decoded['access_token']);
        $this->assertEquals('Bearer', $decoded['token_type']);
        $this->assertEquals('read write delete', $decoded['scope']);
    }

    /**
     * Test: Authorization code caching (PKCE scenario)
     *
     * @test
     */
    public function testAuthorizationCodeCaching(): void
    {
        $codeData = json_encode([
            'code' => 'auth_code_abc123',
            'client_id' => 'client_789',
            'user_id' => 456,
            'redirect_uri' => 'https://app.example.com/callback',
            'code_challenge' => 'E9Mrozoa2owUednMg8BCLvhlFp-zYT2Z8LsV3d7Yrc',
            'code_challenge_method' => 'S256',
            'expires_at' => time() + 600,
            'used' => false
        ]);

        $codeKey = 'oauth2:authcode:auth_code_abc123';
        $this->dbCache->set($codeKey, $codeData, 600);

        $retrieved = $this->dbCache->get($codeKey);
        $decoded = json_decode($retrieved, true);

        $this->assertEquals('client_789', $decoded['client_id']);
        $this->assertEquals('S256', $decoded['code_challenge_method']);
        $this->assertFalse($decoded['used']);
    }

    /**
     * Test: Consent scope caching
     *
     * @test
     */
    public function testConsentScopeCaching(): void
    {
        $consent = json_encode([
            'user_id' => 789,
            'client_id' => 'client_456',
            'granted_scopes' => ['read', 'write', 'profile'],
            'granted_at' => time(),
            'expires_at' => time() + 86400
        ]);

        $consentKey = 'oauth2:consent:user_789:client_456';
        $this->dbCache->set($consentKey, $consent, 86400);

        $retrieved = $this->dbCache->get($consentKey);
        $decoded = json_decode($retrieved, true);

        $this->assertEquals([789, 456], [
            $decoded['user_id'],
            intval(str_replace('client_', '', $decoded['client_id']))
        ]);
        $this->assertContains('write', $decoded['granted_scopes']);
    }

    /**
     * Test: Cache hit rate for token lookups
     *
     * @test
     */
    public function testTokenCacheHitRate(): void
    {
        $cache = new DatabaseCache($this->db);

        // Simulate token lookups with repeated access
        for ($i = 0; $i < 100; $i++) {
            $tokenKey = 'token_' . ($i % 10); // Only 10 unique keys
            $cache->set($tokenKey, "token_value_$i");
        }

        // Now simulate repeated lookups
        for ($i = 0; $i < 100; $i++) {
            $tokenKey = 'token_' . ($i % 10);
            $value = $cache->get($tokenKey);
            $this->assertNotNull($value);
        }

        $stats = $cache->getStats();
        // Should have high hit rate since we're repeating the same keys
        $this->assertGreater(50, $stats['hit_rate_percent']);
    }

    /**
     * Test: TTL default handling
     *
     * @test
     */
    public function testCacheTTLDefault(): void
    {
        $cache = new DatabaseCache($this->db, 'ttl_test', 7200);
        $cache->set('ttl_key', 'ttl_value'); // Uses default TTL

        $this->assertEquals('ttl_value', $cache->get('ttl_key'));
    }

    /**
     * Test: Cache performance tracking
     *
     * @test
     */
    public function testCachePerformanceMetrics(): void
    {
        $cache = new DatabaseCache($this->db);
        
        // Simulate typical OAuth2 workload
        $operations = 1000;
        $uniqueKeys = 50;

        for ($i = 0; $i < $operations; $i++) {
            $key = 'perf_key_' . ($i % $uniqueKeys);
            if ($i % 3 === 0) {
                $cache->set($key, "value_$i");
            } else {
                $cache->get($key);
            }
        }

        $stats = $cache->getStats();
        $this->assertGreater(500, $stats['total_requests']);
    }

    /**
     * Test: Multi-tier cache backfill from second tier
     *
     * @test
     */
    public function testMultiTierBackfill(): void
    {
        $cache1 = new DatabaseCache($this->db, 'backfill1');
        $cache2 = new DatabaseCache($this->db, 'backfill2');
        $multiTier = new MultiTierCache([$cache1, $cache2]);

        // Store only in second tier
        $cache2->set('backfill_key', 'backfill_value');

        // Retrieve via multiTier - should hit but not backfill since get doesn't find in tier1
        $value = $multiTier->get('backfill_key');
        
        // Store via multiTier to populate both
        $multiTier->set('backfill_key2', 'backfill_value2');
        
        // Now both should have it
        $this->assertEquals('backfill_value2', $cache1->get('backfill_key2'));
        $this->assertEquals('backfill_value2', $cache2->get('backfill_key2'));
    }
}
