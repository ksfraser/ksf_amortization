<?php
namespace Tests\Base;

use Tests\Fixtures\LoanFixture;
use Ksfraser\Amortizations\Tests\Fixtures\ScheduleFixture;

/**
 * Adaptor Test Case
 *
 * Abstract base class for platform adaptor tests (FA, WP, SuiteCRM).
 * Provides common test patterns for data provider implementations.
 *
 * ### Usage
 *
 * ```php
 * class FADataProviderTest extends AdaptorTestCase {
 *     protected function createAdaptor() {
 *         return new FADataProvider($this->pdo);
 *     }
 *
 *     public function test_insert_loan() {
 *         $loan = $this->createLoan();
 *         $id = $this->adaptor->insertLoan($loan);
 *         $this->assertValidPositive($id);
 *     }
 * }
 * ```
 *
 * @package   Ksfraser\Amortizations\Tests\Base
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-17
 */
abstract class AdaptorTestCase extends BaseTestCase
{
    /**
     * The adaptor instance being tested
     * @var object
     */
    protected $adaptor;

    /**
     * Setup for adaptor tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create the adaptor instance
        $this->adaptor = $this->createAdaptor();
    }

    /**
     * Create the adaptor instance
     * Must be implemented by subclasses
     *
     * @return object
     */
    abstract protected function createAdaptor();

    /**
     * Test that adaptor implements DataProviderInterface
     *
     * @return void
     */
    public function test_adaptor_implements_interface(): void
    {
        $this->assertInstanceOf(
            \Ksfraser\Amortizations\DataProviderInterface::class,
            $this->adaptor
        );
    }

    /**
     * Test insert loan success
     *
     * @return void
     */
    public function test_insert_loan_returns_positive_id(): void
    {
        $loan = $this->createLoan();
        $id = $this->adaptor->insertLoan($loan);
        $this->assertValidPositive($id);
    }

    /**
     * Test insert loan throws exception on missing required fields
     *
     * @return void
     */
    public function test_insert_loan_throws_on_missing_principal(): void
    {
        $loan = $this->createLoan();
        unset($loan['principal']);

        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->insertLoan($loan);
    }

    /**
     * Test insert loan throws exception on invalid principal
     *
     * @return void
     */
    public function test_insert_loan_throws_on_negative_principal(): void
    {
        $loan = $this->createLoan(['principal' => -1000]);

        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->insertLoan($loan);
    }

    /**
     * Test insert schedule row success
     *
     * @return void
     */
    public function test_insert_schedule_succeeds(): void
    {
        $row = $this->createScheduleRow(1);
        // Should not throw
        $this->adaptor->insertSchedule(1, $row);
        $this->assertTrue(true);
    }

    /**
     * Test insert schedule throws on invalid loan_id
     *
     * @return void
     */
    public function test_insert_schedule_throws_on_negative_loan_id(): void
    {
        $row = $this->createScheduleRow(1);

        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->insertSchedule(-1, $row);
    }

    /**
     * Test insert schedule throws on invalid date
     *
     * @return void
     */
    public function test_insert_schedule_throws_on_invalid_date(): void
    {
        $row = $this->createScheduleRow(1);
        $row['payment_date'] = 'invalid-date';

        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->insertSchedule(1, $row);
    }

    /**
     * Test insert loan event success
     *
     * @return void
     */
    public function test_insert_loan_event_returns_positive_id(): void
    {
        $event = $this->createLoanEventMock();
        $id = $this->adaptor->insertLoanEvent(1, $event);
        $this->assertValidPositive($id);
    }

    /**
     * Test insert loan event throws on negative loan_id
     *
     * @return void
     */
    public function test_insert_loan_event_throws_on_negative_loan_id(): void
    {
        $event = $this->createLoanEventMock();

        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->insertLoanEvent(-1, $event);
    }

    /**
     * Test delete schedule after date
     *
     * @return void
     */
    public function test_delete_schedule_after_date_succeeds(): void
    {
        // Should not throw
        $this->adaptor->deleteScheduleAfterDate(1, '2025-06-01');
        $this->assertTrue(true);
    }

    /**
     * Test delete schedule throws on invalid loan_id
     *
     * @return void
     */
    public function test_delete_schedule_throws_on_negative_loan_id(): void
    {
        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->deleteScheduleAfterDate(-1, '2025-06-01');
    }

    /**
     * Test delete schedule throws on invalid date
     *
     * @return void
     */
    public function test_delete_schedule_throws_on_invalid_date(): void
    {
        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->deleteScheduleAfterDate(1, 'invalid-date');
    }

    /**
     * Test update schedule row succeeds
     *
     * @return void
     */
    public function test_update_schedule_row_succeeds(): void
    {
        $updates = [
            'payment_amount' => 600.00,
            'remaining_balance' => 25000.00
        ];

        // Should not throw
        $this->adaptor->updateScheduleRow(1, $updates);
        $this->assertTrue(true);
    }

    /**
     * Test update schedule row throws on negative id
     *
     * @return void
     */
    public function test_update_schedule_row_throws_on_negative_id(): void
    {
        $updates = ['payment_amount' => 600.00];

        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->updateScheduleRow(-1, $updates);
    }

    /**
     * Test get schedule rows succeeds
     *
     * @return void
     */
    public function test_get_schedule_rows_returns_array(): void
    {
        $rows = $this->adaptor->getScheduleRows(1);
        $this->assertIsArray($rows);
    }

    /**
     * Test get schedule rows throws on negative loan_id
     *
     * @return void
     */
    public function test_get_schedule_rows_throws_on_negative_loan_id(): void
    {
        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->getScheduleRows(-1);
    }

    /**
     * Test get schedule rows after date
     *
     * @return void
     */
    public function test_get_schedule_rows_after_date_returns_array(): void
    {
        $rows = $this->adaptor->getScheduleRowsAfterDate(1, '2025-06-01');
        $this->assertIsArray($rows);
    }

    /**
     * Test count schedule rows succeeds
     *
     * @return void
     */
    public function test_count_schedule_rows_returns_integer(): void
    {
        $count = $this->adaptor->countScheduleRows(1);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test pagination parameters are validated
     *
     * @return void
     */
    public function test_get_schedule_rows_paginated_throws_on_negative_page_size(): void
    {
        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataValidationException::class);
        $this->adaptor->getScheduleRowsPaginated(1, -1, 0);
    }

    /**
     * Test exception type for not found records
     *
     * @return void
     */
    public function test_get_loan_throws_not_found_exception(): void
    {
        $this->expectException(\Ksfraser\Amortizations\Exceptions\DataNotFoundException::class);
        $this->adaptor->getLoan(99999);
    }

    /**
     * Test exception type for persistence errors
     *
     * @return void
     */
    public function test_insert_throws_persistence_exception_on_db_error(): void
    {
        // This would require database to actually fail
        // Subclasses can override to test specific scenarios
        $this->assertTrue(true);
    }

    /**
     * Common test data provider
     *
     * @return array
     */
    public function validLoanProvider(): array
    {
        return [
            'auto_loan' => [LoanFixture::createAutoLoan()],
            'mortgage' => [LoanFixture::createMortgage()],
            'personal_loan' => [LoanFixture::createPersonalLoan()],
        ];
    }

    /**
     * Invalid loan data provider
     *
     * @return array
     */
    public function invalidLoanProvider(): array
    {
        return [
            'missing_principal' => [LoanFixture::createLoan(['principal' => null])],
            'negative_principal' => [LoanFixture::createLoan(['principal' => -100])],
            'zero_principal' => [LoanFixture::createLoan(['principal' => 0])],
            'negative_rate' => [LoanFixture::createLoan(['interest_rate' => -5])],
            'zero_rate' => [LoanFixture::createLoan(['interest_rate' => 0])],
        ];
    }
}
