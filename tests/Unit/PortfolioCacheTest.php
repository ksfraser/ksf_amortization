<?php
namespace Tests;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\PortfolioManagementService;
use Ksfraser\Amortizations\Services\CacheManager;
use Ksfraser\Amortizations\Services\PortfolioCache;
use PHPUnit\Framework\TestCase;

class PortfolioCacheTest extends TestCase {
    private PortfolioCache $cache;
    private array $testLoans;

    protected function setUp(): void {
        $this->cache = new PortfolioCache();
        $this->testLoans = $this->createTestLoans();
    }

    private function createTestLoans(): array {
        $loans = [];
        
        $loan1 = new Loan();
        $loan1->setId(1)->setPrincipal(200000)->setAnnualRate(0.05)->setMonths(360);
        $loans[] = $loan1;

        $loan2 = new Loan();
        $loan2->setId(2)->setPrincipal(150000)->setAnnualRate(0.06)->setMonths(300);
        $loans[] = $loan2;

        return $loans;
    }

    public function testGetCachedPortfolioReportCachesResult(): void {
        $report1 = $this->cache->getCachedPortfolioReport($this->testLoans);
        $stats1 = $this->cache->getCacheStats();

        // Get again - should hit cache
        $report2 = $this->cache->getCachedPortfolioReport($this->testLoans);
        $stats2 = $this->cache->getCacheStats();

        $this->assertEquals($report1, $report2);
        $this->assertGreaterThan($stats1['hits'], $stats2['hits']);
    }

    public function testGetCachedRiskProfileCachesResult(): void {
        $risk1 = $this->cache->getCachedRiskProfile($this->testLoans);
        $stats1 = $this->cache->getCacheStats();

        $risk2 = $this->cache->getCachedRiskProfile($this->testLoans);
        $stats2 = $this->cache->getCacheStats();

        $this->assertEquals($risk1, $risk2);
        $this->assertGreaterThan($stats1['hits'], $stats2['hits']);
    }

    public function testGetCachedYieldCachesResult(): void {
        $yield1 = $this->cache->getCachedYield($this->testLoans);
        $stats1 = $this->cache->getCacheStats();

        $yield2 = $this->cache->getCachedYield($this->testLoans);
        $stats2 = $this->cache->getCacheStats();

        $this->assertEquals($yield1, $yield2);
        $this->assertGreaterThan($stats1['hits'], $stats2['hits']);
    }

    public function testGetCachedProfitabilityCachesResult(): void {
        $profit1 = $this->cache->getCachedProfitability($this->testLoans);
        $stats1 = $this->cache->getCacheStats();

        $profit2 = $this->cache->getCachedProfitability($this->testLoans);
        $stats2 = $this->cache->getCacheStats();

        $this->assertEquals($profit1, $profit2);
        $this->assertGreaterThan($stats1['hits'], $stats2['hits']);
    }

    public function testGetCachedDiversificationCachesResult(): void {
        $div1 = $this->cache->getCachedDiversification($this->testLoans);
        $stats1 = $this->cache->getCacheStats();

        $div2 = $this->cache->getCachedDiversification($this->testLoans);
        $stats2 = $this->cache->getCacheStats();

        $this->assertEquals($div1, $div2);
        $this->assertGreaterThan($stats1['hits'], $stats2['hits']);
    }

    public function testGetCachedRankingCachesResult(): void {
        $rank1 = $this->cache->getCachedRanking($this->testLoans);
        $stats1 = $this->cache->getCacheStats();

        $rank2 = $this->cache->getCachedRanking($this->testLoans);
        $stats2 = $this->cache->getCacheStats();

        $this->assertEquals($rank1, $rank2);
        $this->assertGreaterThan($stats1['hits'], $stats2['hits']);
    }

    public function testInvalidatePortfolioCacheRemovesEntries(): void {
        $this->cache->getCachedPortfolioReport($this->testLoans);
        $this->cache->getCachedRiskProfile($this->testLoans);

        $deleted = $this->cache->invalidatePortfolioCache();

        $this->assertGreaterThan(0, $deleted);
        $stats = $this->cache->getCacheStats();
        $this->assertEquals(0, $stats['current_size']);
    }

    public function testInvalidateForLoansRemovesSpecificEntries(): void {
        $this->cache->getCachedPortfolioReport($this->testLoans);
        $this->cache->getCachedYield($this->testLoans);

        // Verify cache has entries
        $manager = $this->cache->getCacheManager();
        $keysBefore = count($manager->getKeys());

        // Invalidate
        $deleted = $this->cache->invalidatePortfolioCache();

        $this->assertGreaterThan(0, $deleted);
        $this->assertEquals($keysBefore, $deleted);
    }

    public function testWarmCachePopulatesMultipleMetrics(): void {
        $warmed = $this->cache->warmCache($this->testLoans);

        $this->assertGreaterThan(0, $warmed);
        $stats = $this->cache->getCacheStats();
        $this->assertGreaterThan(0, $stats['current_size']);
    }

    public function testGetCacheStatsReturnsMetrics(): void {
        $this->cache->getCachedPortfolioReport($this->testLoans);
        $this->cache->getCachedYield($this->testLoans);

        $stats = $this->cache->getCacheStats();

        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertGreaterThan(0, $stats['sets']);
    }

    public function testGetCacheManagerReturnsUnderlyingManager(): void {
        $manager = $this->cache->getCacheManager();

        $this->assertInstanceOf(CacheManager::class, $manager);
    }

    public function testSetDefaultTTLAffectsCaching(): void {
        $this->cache->setDefaultTTL(60);
        $this->cache->getCachedPortfolioReport($this->testLoans);

        $manager = $this->cache->getCacheManager();
        $keys = $manager->getKeys();

        $this->assertGreaterThan(0, count($keys));
    }

    public function testGetCacheSizeReturnsBytes(): void {
        $this->cache->getCachedPortfolioReport($this->testLoans);
        $size = $this->cache->getCacheSize();

        $this->assertGreaterThan(0, $size);
    }

    public function testClearCacheRemovesAllEntries(): void {
        $this->cache->getCachedPortfolioReport($this->testLoans);
        $this->cache->getCachedYield($this->testLoans);

        $this->cache->clearCache();

        $manager = $this->cache->getCacheManager();
        $this->assertEquals(0, count($manager->getKeys()));
    }

    public function testCacheWithCustomTTL(): void {
        $report = $this->cache->getCachedPortfolioReport($this->testLoans, 120);
        $stats = $this->cache->getCacheStats();

        $this->assertGreaterThan(0, $stats['sets']);
    }

    public function testDifferentLoansHaveDifferentCacheKeys(): void {
        $loan3 = new Loan();
        $loan3->setId(3)->setPrincipal(100000)->setAnnualRate(0.04)->setMonths(240);

        $otherLoans = array_merge($this->testLoans, [$loan3]);

        $report1 = $this->cache->getCachedPortfolioReport($this->testLoans);
        $report2 = $this->cache->getCachedPortfolioReport($otherLoans);

        $this->assertNotEquals($report1, $report2);
    }

    public function testCacheMissFollowedByHit(): void {
        // First call - miss
        $this->cache->getCachedPortfolioReport($this->testLoans);
        $stats1 = $this->cache->getCacheStats();
        $misses1 = $stats1['misses'];

        // Second call - hit
        $this->cache->getCachedPortfolioReport($this->testLoans);
        $stats2 = $this->cache->getCacheStats();
        $hits2 = $stats2['hits'];

        $this->assertGreaterThan(0, $hits2);
        $this->assertGreaterThan($misses1, $stats2['misses'] + $hits2);
    }
}
