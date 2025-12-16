<?php
namespace Ksfraser\Amortizations\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Services\QueryOptimizationService;

/**
 * Phase 13 Week 1: Query Optimization Tests
 *
 * Tests for all 4 optimized query patterns:
 * 1. Portfolio balance batch queries
 * 2. Payment schedule with pagination
 * 3. Interest calculation with denormalization
 * 4. GL account mapping with caching
 *
 * Performance targets:
 * - Portfolio balance: <120ms for 500 loans (50%+ improvement)
 * - Payment schedule: <250ms for 1000 records (40%+ improvement)
 * - Interest calculation: <0.8ms per loan (70%+ improvement)
 * - GL account mapping: <70ms (60%+ improvement with cache)
 *
 * @package Ksfraser\Amortizations\Tests
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   2025-12-16
 */
class QueryOptimizationTest extends TestCase {

    /**
     * @var object Mock DataProvider
     */
    private $mockDataProvider;

    /**
     * @var object Mock Cache
     */
    private $mockCache;

    /**
     * @var QueryOptimizationService
     */
    private $queryService;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void {
        parent::setUp();

        // Create mock data provider
        $this->mockDataProvider = $this->createMock('Ksfraser\Amortizations\DataProviderInterface');
        $this->mockCache = $this->createMock('Psr\SimpleCache\CacheInterface');

        // Create service with mocks
        $this->queryService = new QueryOptimizationService(
            $this->mockDataProvider,
            $this->mockCache
        );
    }

    // ========================================================================
    // QUERY 1: Portfolio Balance Tests
    // ========================================================================

    /**
     * Test portfolio balance batch query returns correct format
     *
     * @test
     */
    public function testPortfolioBalanceBatchQueryFormat() {
        // Arrange
        $loanIds = [1, 2, 3];
        $expectedResults = [
            1 => ['balance' => 50000.00, 'interest_accrued' => 1250.00],
            2 => ['balance' => 25000.00, 'interest_accrued' => 625.00],
            3 => ['balance' => 75000.00, 'interest_accrued' => 1875.00]
        ];

        $this->mockCache->method('has')->willReturn(false);
        $this->mockDataProvider->method('getPortfolioBalancesBatch')
            ->with($loanIds)
            ->willReturn($expectedResults);

        // Act
        $result = $this->queryService->getPortfolioBalances($loanIds);

        // Assert
        $this->assertEquals($expectedResults, $result);
        $this->assertCount(3, $result);
        foreach ($result as $loanId => $data) {
            $this->assertArrayHasKey('balance', $data);
            $this->assertArrayHasKey('interest_accrued', $data);
        }
    }

    /**
     * Test portfolio balance returns empty array for empty input
     *
     * @test
     */
    public function testPortfolioBalanceEmptyInput() {
        // Act
        $result = $this->queryService->getPortfolioBalances([]);

        // Assert
        $this->assertEmpty($result);
    }

    /**
     * Test portfolio balance cache hit
     *
     * @test
     */
    public function testPortfolioBalanceCacheHit() {
        // Arrange
        $loanIds = [1, 2, 3];
        $cachedResults = [
            1 => ['balance' => 50000.00, 'interest_accrued' => 1250.00]
        ];
        $cacheKey = 'portfolio_balances_' . hash('sha256', json_encode($loanIds));

        $this->mockCache->method('has')->with($cacheKey)->willReturn(true);
        $this->mockCache->method('get')->with($cacheKey)->willReturn($cachedResults);
        $this->mockDataProvider->expects($this->never())
            ->method('getPortfolioBalancesBatch');

        // Act
        $result = $this->queryService->getPortfolioBalances($loanIds);

        // Assert
        $this->assertEquals($cachedResults, $result);
    }

    /**
     * Test total portfolio balance calculation
     *
     * @test
     */
    public function testTotalPortfolioBalance() {
        // Arrange
        $loanIds = [1, 2, 3];
        $balances = [
            1 => ['balance' => 50000.00, 'interest_accrued' => 1250.00],
            2 => ['balance' => 25000.00, 'interest_accrued' => 625.00],
            3 => ['balance' => 75000.00, 'interest_accrued' => 1875.00]
        ];

        $this->mockCache->method('has')->willReturn(false);
        $this->mockDataProvider->method('getPortfolioBalancesBatch')
            ->willReturn($balances);

        // Act
        $total = $this->queryService->getTotalPortfolioBalance($loanIds);

        // Assert
        $this->assertEquals(150000.00, $total); // 50k + 25k + 75k
    }

    /**
     * Test total portfolio balance with empty input
     *
     * @test
     */
    public function testTotalPortfolioBalanceEmpty() {
        // Act
        $total = $this->queryService->getTotalPortfolioBalance([]);

        // Assert
        $this->assertEquals(0.0, $total);
    }

    // ========================================================================
    // QUERY 2: Payment Schedule Tests
    // ========================================================================

    /**
     * Test optimized schedule returns selective columns
     *
     * @test
     */
    public function testOptimizedScheduleSelectiveColumns() {
        // Arrange
        $loanId = 123;
        $expectedColumns = [
            'payment_number',
            'payment_date',
            'payment_amount',
            'principal_payment',
            'interest_payment',
            'balance_after_payment',
            'payment_status'
        ];
        $expectedResults = [
            ['payment_number' => 1, 'payment_date' => '2025-01-15', 'payment_amount' => 300.00],
            ['payment_number' => 2, 'payment_date' => '2025-02-15', 'payment_amount' => 300.00]
        ];

        $this->mockDataProvider->method('getScheduleRowsOptimized')
            ->with($loanId, $expectedColumns, ['pending', 'scheduled'])
            ->willReturn($expectedResults);

        // Act
        $result = $this->queryService->getOptimizedSchedule($loanId);

        // Assert
        $this->assertEquals($expectedResults, $result);
    }

    /**
     * Test payment schedule pagination
     *
     * @test
     */
    public function testSchedulePagination() {
        // Arrange
        $loanId = 123;
        $pageSize = 50;
        $offset = 0;
        $total = 360;
        $pageData = array_fill(0, 50, ['payment_number' => 1, 'payment_amount' => 300.00]);

        $this->mockDataProvider->method('countScheduleRows')
            ->with($loanId)
            ->willReturn($total);
        $this->mockDataProvider->method('getScheduleRowsPaginated')
            ->with($loanId, $pageSize, $offset)
            ->willReturn($pageData);

        // Act
        $result = $this->queryService->getSchedulePage($loanId, $pageSize, $offset);

        // Assert
        $this->assertEquals(['total' => 360, 'page_size' => 50, 'offset' => 0, 'pages' => 8], array_except($result, ['data']));
        $this->assertCount(50, $result['data']);
    }

    /**
     * Test remaining schedule filters by date
     *
     * @test
     */
    public function testRemainingScheduleFiltering() {
        // Arrange
        $loanId = 123;
        $afterDate = '2025-12-16';
        $expectedResults = [
            ['payment_number' => 50, 'payment_date' => '2026-01-15'],
            ['payment_number' => 51, 'payment_date' => '2026-02-15']
        ];

        $this->mockDataProvider->method('getScheduleRowsAfterDate')
            ->with($loanId, $afterDate)
            ->willReturn($expectedResults);

        // Act
        $result = $this->queryService->getRemainingSchedule($loanId, $afterDate);

        // Assert
        $this->assertEquals($expectedResults, $result);
    }

    // ========================================================================
    // QUERY 3: Interest Calculation Tests
    // ========================================================================

    /**
     * Test cumulative interest paid from denormalized column
     *
     * @test
     */
    public function testCumulativeInterestPaidDenormalized() {
        // Arrange
        $loanId = 123;
        $loanData = [
            'id' => $loanId,
            'principal' => 100000.00,
            'total_interest_paid' => 15250.75,
            'total_interest_accrued' => 18500.50
        ];

        $this->mockCache->method('has')->willReturn(false);
        $this->mockDataProvider->method('getLoan')
            ->with($loanId)
            ->willReturn($loanData);

        // Act
        $interest = $this->queryService->getCumulativeInterestPaid($loanId);

        // Assert
        $this->assertEquals(15250.75, $interest);
    }

    /**
     * Test cumulative interest paid from cache
     *
     * @test
     */
    public function testCumulativeInterestPaidCache() {
        // Arrange
        $loanId = 123;
        $cacheKey = "interest_paid_loan_{$loanId}";
        $cachedInterest = 15250.75;

        $this->mockCache->method('has')->with($cacheKey)->willReturn(true);
        $this->mockCache->method('get')->with($cacheKey)->willReturn($cachedInterest);
        $this->mockDataProvider->expects($this->never())->method('getLoan');

        // Act
        $interest = $this->queryService->getCumulativeInterestPaid($loanId);

        // Assert
        $this->assertEquals($cachedInterest, $interest);
    }

    /**
     * Test cumulative accrued interest
     *
     * @test
     */
    public function testCumulativeInterestAccrued() {
        // Arrange
        $loanId = 123;
        $loanData = [
            'id' => $loanId,
            'total_interest_accrued' => 18500.50
        ];

        $this->mockDataProvider->method('getLoan')
            ->with($loanId)
            ->willReturn($loanData);

        // Act
        $interest = $this->queryService->getCumulativeInterestAccrued($loanId);

        // Assert
        $this->assertEquals(18500.50, $interest);
    }

    // ========================================================================
    // QUERY 4: GL Account Mapping Tests
    // ========================================================================

    /**
     * Test account mappings batch query
     *
     * @test
     */
    public function testAccountMappingsBatchQuery() {
        // Arrange
        $accountTypes = ['asset', 'liability', 'equity'];
        $expectedMappings = [
            'asset' => [
                ['account_code' => '1000', 'account_name' => 'Checking'],
                ['account_code' => '1100', 'account_name' => 'Savings']
            ],
            'liability' => [
                ['account_code' => '2000', 'account_name' => 'Accounts Payable']
            ],
            'equity' => [
                ['account_code' => '3000', 'account_name' => 'Common Stock']
            ]
        ];

        $this->mockCache->method('has')->willReturn(false);
        $this->mockDataProvider->method('getAccountMappingsBatch')
            ->with($accountTypes)
            ->willReturn($expectedMappings);

        // Act
        $result = $this->queryService->getAccountMappings($accountTypes);

        // Assert
        $this->assertEquals($expectedMappings, $result);
        $this->assertCount(3, $result);
    }

    /**
     * Test account mapping cache hit
     *
     * @test
     */
    public function testAccountMappingsCacheHit() {
        // Arrange
        $accountTypes = ['asset', 'liability'];
        $cachedMappings = ['asset' => [], 'liability' => []];
        $cacheKey = 'gl_mappings_' . hash('sha256', json_encode($accountTypes));

        $this->mockCache->method('has')->with($cacheKey)->willReturn(true);
        $this->mockCache->method('get')->with($cacheKey)->willReturn($cachedMappings);
        $this->mockDataProvider->expects($this->never())
            ->method('getAccountMappingsBatch');

        // Act
        $result = $this->queryService->getAccountMappings($accountTypes);

        // Assert
        $this->assertEquals($cachedMappings, $result);
    }

    /**
     * Test single account mapping
     *
     * @test
     */
    public function testSingleAccountMapping() {
        // Arrange
        $accountType = 'asset';
        $allMappings = [
            'asset' => [
                ['account_code' => '1000', 'account_name' => 'Checking']
            ]
        ];

        $this->mockCache->method('has')->willReturn(false);
        $this->mockDataProvider->method('getAccountMappingsBatch')
            ->willReturn($allMappings);

        // Act
        $result = $this->queryService->getAccountMapping($accountType);

        // Assert
        $this->assertEquals($allMappings['asset'], $result);
    }

    /**
     * Test account mapping empty input
     *
     * @test
     */
    public function testAccountMappingEmptyInput() {
        // Act
        $result = $this->queryService->getAccountMappings([]);

        // Assert
        $this->assertEmpty($result);
    }

    // ========================================================================
    // Cache Invalidation Tests
    // ========================================================================

    /**
     * Test cache invalidation for loan
     *
     * @test
     */
    public function testInvalidateLoanCache() {
        // Arrange
        $loanId = 123;
        $this->mockCache->expects($this->atLeastOnce())
            ->method('delete');

        // Act
        $this->queryService->invalidateLoanCache($loanId);

        // Assert - should delete cache entries
        $this->assertTrue(true); // Verify delete was called
    }

    /**
     * Test clear all caches
     *
     * @test
     */
    public function testClearAllCaches() {
        // Arrange
        $this->mockCache->expects($this->once())
            ->method('clear');

        // Act
        $this->queryService->clearAllCaches();

        // Assert
        $this->assertTrue(true); // Verify clear was called
    }

    // ========================================================================
    // Caching Control Tests
    // ========================================================================

    /**
     * Test disabling caching bypasses cache
     *
     * @test
     */
    public function testDisableCaching() {
        // Arrange
        $loanIds = [1, 2, 3];
        $results = [1 => ['balance' => 50000.00]];

        $this->mockCache->expects($this->never())->method('has');
        $this->mockDataProvider->method('getPortfolioBalancesBatch')
            ->willReturn($results);

        // Act
        $this->queryService->disableCaching();
        $result = $this->queryService->getPortfolioBalances($loanIds);

        // Assert
        $this->assertEquals($results, $result);
    }

    /**
     * Test enabling caching restores caching
     *
     * @test
     */
    public function testEnableCaching() {
        // Arrange
        $loanIds = [1, 2, 3];
        $results = [1 => ['balance' => 50000.00]];

        $this->mockCache->method('has')->willReturn(false);
        $this->mockCache->method('set');
        $this->mockDataProvider->method('getPortfolioBalancesBatch')
            ->willReturn($results);

        // Act
        $this->queryService->disableCaching()->enableCaching();
        $result = $this->queryService->getPortfolioBalances($loanIds);

        // Assert
        $this->mockCache->expects($this->atLeastOnce())->method('set');
    }
}
