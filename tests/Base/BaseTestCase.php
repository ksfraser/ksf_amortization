<?php
namespace Tests\Base;

use PHPUnit\Framework\TestCase;
use Tests\Helpers\AssertionHelpers;
use Tests\Helpers\MockBuilder;
use Tests\Fixtures\LoanFixture;
use Tests\Fixtures\ScheduleFixture;

/**
 * Base Test Case
 *
 * Abstract base class for all unit tests.
 * Provides common setup, fixtures, helpers, and utilities.
 *
 * ### Usage
 *
 * ```php
 * class LoanTest extends BaseTestCase {
 *     public function test_loan_creation() {
 *         $loan = $this->createLoan(['principal' => 50000]);
 *         $this->assertValidLoan($loan);
 *     }
 * }
 * ```
 *
 * @package   Ksfraser\Amortizations\Tests\Base
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-17
 */
abstract class BaseTestCase extends TestCase
{
    use AssertionHelpers;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize mock builder with this test case
        MockBuilder::setTestCase($this);
    }

    /**
     * Tear down test environment
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Create a loan using LoanFixture
     *
     * @param array $overrides Field overrides
     * @return array
     */
    protected function createLoan(array $overrides = []): array
    {
        return LoanFixture::createLoan($overrides);
    }

    /**
     * Create an auto loan
     *
     * @param array $overrides Field overrides
     * @return array
     */
    protected function createAutoLoan(array $overrides = []): array
    {
        return LoanFixture::createAutoLoan($overrides);
    }

    /**
     * Create a mortgage
     *
     * @param array $overrides Field overrides
     * @return array
     */
    protected function createMortgage(array $overrides = []): array
    {
        return LoanFixture::createMortgage($overrides);
    }

    /**
     * Create a schedule using ScheduleFixture
     *
     * @param int $loanId Loan ID
     * @param int $months Number of months
     * @param array $overrides Field overrides
     * @return array
     */
    protected function createSchedule(int $loanId, int $months = 12, array $overrides = []): array
    {
        return ScheduleFixture::createSchedule($loanId, $months, $overrides);
    }

    /**
     * Create a schedule row
     *
     * @param int $loanId Loan ID
     * @param int $paymentNumber Payment number
     * @param array $overrides Field overrides
     * @return array
     */
    protected function createScheduleRow(int $loanId, int $paymentNumber = 1, array $overrides = []): array
    {
        return ScheduleFixture::createRow($loanId, $paymentNumber, '2025-01-01', $overrides);
    }

    /**
     * Create a mock PDO
     *
     * @param array $returnValues Method return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createPdoMock(array $returnValues = [])
    {
        return MockBuilder::createPdoMock($returnValues);
    }

    /**
     * Create a mock DataProvider
     *
     * @param array $methodReturnValues Method return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createDataProviderMock(array $methodReturnValues = [])
    {
        return MockBuilder::createDataProviderMock($methodReturnValues);
    }

    /**
     * Create a mock LoanEvent
     *
     * @param array $properties Event properties
     * @return object
     */
    protected function createLoanEventMock(array $properties = []): object
    {
        return MockBuilder::createLoanEventMock($properties);
    }

    /**
     * Create a mock wpdb
     *
     * @param array $returnValues Method return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createWpdbMock(array $returnValues = [])
    {
        return MockBuilder::createWpdbMock($returnValues);
    }

    /**
     * Create a calculator mock
     *
     * @param string $calculatorClass Calculator class
     * @param array $methodReturnValues Method return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createCalculatorMock(string $calculatorClass, array $methodReturnValues = [])
    {
        return MockBuilder::createCalculatorMock($calculatorClass, $methodReturnValues);
    }

    /**
     * Assert that a loan is valid
     *
     * @param array $loan Loan to validate
     * @return void
     */
    protected function assertLoanValid(array $loan): void
    {
        $this->assertValidLoan($loan);
    }

    /**
     * Assert that a schedule is valid
     *
     * @param array $schedule Schedule to validate
     * @return void
     */
    protected function assertScheduleValid(array $schedule): void
    {
        $this->assertValidSchedule($schedule);
    }

    /**
     * Get a temporary test file path
     *
     * @param string $suffix File name suffix
     * @return string
     */
    protected function getTempFilePath(string $suffix = ''): string
    {
        return sys_get_temp_dir() . '/test_' . uniqid() . $suffix;
    }

    /**
     * Create a temporary test directory
     *
     * @return string
     */
    protected function createTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/test_' . uniqid();
        mkdir($dir, 0755, true);
        return $dir;
    }

    /**
     * Clean up temporary directory
     *
     * @param string $dir Directory to remove
     * @return void
     */
    protected function cleanupTempDir(string $dir): void
    {
        if (is_dir($dir)) {
            array_map('unlink', glob("$dir/*"));
            rmdir($dir);
        }
    }

    /**
     * Get current memory usage
     *
     * @return string
     */
    protected function getMemoryUsage(): string
    {
        return round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
    }

    /**
     * Assert performance is acceptable (execution time)
     *
     * @param callable $callback Code to execute
     * @param float $maxSeconds Maximum allowed seconds
     * @param string $message Custom message
     * @return mixed Return value from callback
     */
    protected function assertPerformance(callable $callback, float $maxSeconds = 1.0, string $message = '')
    {
        $start = microtime(true);
        $result = $callback();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(
            $maxSeconds,
            $elapsed,
            $message ?: "Execution took {$elapsed}s, expected < {$maxSeconds}s"
        );

        return $result;
    }
}
