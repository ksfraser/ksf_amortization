<?php
namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;

/**
 * Assertion Helpers
 *
 * Provides custom assertion methods for common test scenarios.
 * Reduces boilerplate and improves test readability.
 *
 * ### Usage
 *
 * ```php
 * // Use in test class
 * class LoanTest extends TestCase {
 *     use AssertionHelpers;
 *
 *     public function test_loan_data() {
 *         $loan = ['principal' => 30000, 'rate' => 4.5];
 *         $this->assertValidLoan($loan);
 *         $this->assertValidPositive($loan['principal']);
 *     }
 * }
 * ```
 *
 * @package   Ksfraser\Amortizations\Tests\Helpers
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-17
 */
trait AssertionHelpers
{
    /**
     * Assert that a value is a positive number
     *
     * @param mixed $value Value to check
     * @param string $message Custom message
     * @return void
     */
    public function assertValidPositive($value, string $message = ''): void
    {
        $this->assertTrue(
            is_numeric($value) && $value > 0,
            $message ?: "Expected positive number, got: {$value}"
        );
    }

    /**
     * Assert that a value is a non-negative number
     *
     * @param mixed $value Value to check
     * @param string $message Custom message
     * @return void
     */
    public function assertValidNonNegative($value, string $message = ''): void
    {
        $this->assertTrue(
            is_numeric($value) && $value >= 0,
            $message ?: "Expected non-negative number, got: {$value}"
        );
    }

    /**
     * Assert that a value is a valid date in YYYY-MM-DD format
     *
     * @param string $date Date to check
     * @param string $message Custom message
     * @return void
     */
    public function assertValidDate(string $date, string $message = ''): void
    {
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        $this->assertTrue(
            $dateObj && $dateObj->format('Y-m-d') === $date,
            $message ?: "Expected valid YYYY-MM-DD date, got: {$date}"
        );
    }

    /**
     * Assert that a loan record has all required fields
     *
     * @param array $loan Loan record
     * @param string $message Custom message
     * @return void
     */
    public function assertValidLoan(array $loan, string $message = ''): void
    {
        $required = ['loan_type', 'principal', 'interest_rate', 'term_months'];
        foreach ($required as $field) {
            $this->assertArrayHasKey(
                $field,
                $loan,
                $message ?: "Loan missing required field: {$field}"
            );
        }

        $this->assertValidPositive($loan['principal']);
        $this->assertValidPositive($loan['interest_rate']);
        $this->assertValidPositive($loan['term_months']);
    }

    /**
     * Assert that a schedule row has all required fields
     *
     * @param array $row Schedule row
     * @param string $message Custom message
     * @return void
     */
    public function assertValidScheduleRow(array $row, string $message = ''): void
    {
        $required = ['loan_id', 'payment_date', 'payment_amount', 'principal_portion', 'interest_portion', 'remaining_balance'];
        foreach ($required as $field) {
            $this->assertArrayHasKey(
                $field,
                $row,
                $message ?: "Schedule row missing required field: {$field}"
            );
        }

        $this->assertValidDate($row['payment_date']);
        $this->assertValidNonNegative($row['payment_amount']);
        $this->assertValidNonNegative($row['remaining_balance']);
    }

    /**
     * Assert that a schedule is valid (array of rows)
     *
     * @param array $schedule Array of schedule rows
     * @param string $message Custom message
     * @return void
     */
    public function assertValidSchedule(array $schedule, string $message = ''): void
    {
        $this->assertNotEmpty($schedule, $message ?: 'Schedule should not be empty');

        foreach ($schedule as $row) {
            $this->assertValidScheduleRow($row);
        }
    }

    /**
     * Assert that payment amounts are close within tolerance
     *
     * @param float $expected Expected amount
     * @param float $actual Actual amount
     * @param float $tolerance Tolerance (default: 0.01 for cents)
     * @param string $message Custom message
     * @return void
     */
    public function assertPaymentClose(
        float $expected,
        float $actual,
        float $tolerance = 0.01,
        string $message = ''
    ): void {
        $this->assertEqualsWithDelta(
            $expected,
            $actual,
            $tolerance,
            $message ?: "Expected payment {$expected}, got {$actual} (tolerance: {$tolerance})"
        );
    }

    /**
     * Assert that remaining balance is correct
     *
     * @param float $expectedBalance Expected balance
     * @param float $actualBalance Actual balance
     * @param string $message Custom message
     * @return void
     */
    public function assertBalanceCorrect(
        float $expectedBalance,
        float $actualBalance,
        string $message = ''
    ): void {
        $this->assertPaymentClose(
            $expectedBalance,
            $actualBalance,
            0.01,
            $message ?: "Balance mismatch: expected {$expectedBalance}, got {$actualBalance}"
        );
    }

    /**
     * Assert that schedule ends with zero balance
     *
     * @param array $schedule Schedule rows
     * @param string $message Custom message
     * @return void
     */
    public function assertScheduleEndsWithZeroBalance(array $schedule, string $message = ''): void
    {
        $this->assertNotEmpty($schedule);
        $lastRow = end($schedule);
        $this->assertPaymentClose(
            0,
            $lastRow['remaining_balance'],
            0.01,
            $message ?: 'Final schedule row should have zero balance'
        );
    }

    /**
     * Assert that schedule balance decreases monotonically
     *
     * @param array $schedule Schedule rows
     * @param string $message Custom message
     * @return void
     */
    public function assertBalanceDecreases(array $schedule, string $message = ''): void
    {
        $previousBalance = $schedule[0]['remaining_balance'] + $schedule[0]['principal_portion'];

        for ($i = 1; $i < count($schedule); $i++) {
            $currentBalance = $schedule[$i]['remaining_balance'];
            $this->assertLessThanOrEqual(
                $previousBalance,
                $currentBalance,
                $message ?: "Balance should decrease monotonically at row {$i}"
            );
            $previousBalance = $currentBalance;
        }
    }

    /**
     * Assert that principal + interest = payment for all rows
     *
     * @param array $schedule Schedule rows
     * @param string $message Custom message
     * @return void
     */
    public function assertPaymentBreakdown(array $schedule, string $message = ''): void
    {
        foreach ($schedule as $i => $row) {
            $calculated = round($row['principal_portion'] + $row['interest_portion'], 2);
            $this->assertPaymentClose(
                $row['payment_amount'],
                $calculated,
                0.01,
                $message ?: "Payment breakdown incorrect at row {$i}"
            );
        }
    }

    /**
     * Assert that exception is thrown with specific message
     *
     * @param string $exceptionClass Exception class name
     * @param string $message Expected message (or part of it)
     * @param callable $callback Code to execute
     * @return void
     */
    public function assertExceptionThrown(
        string $exceptionClass,
        string $message,
        callable $callback
    ): void {
        $this->expectException($exceptionClass);
        if (!empty($message)) {
            $this->expectExceptionMessage($message);
        }
        $callback();
    }

    /**
     * Assert that array has all required keys
     *
     * @param array $required Required keys
     * @param array $array Array to check
     * @param string $message Custom message
     * @return void
     */
    public function assertHasRequiredKeys(
        array $required,
        array $array,
        string $message = ''
    ): void {
        $missing = array_diff($required, array_keys($array));
        $this->assertEmpty(
            $missing,
            $message ?: 'Missing required keys: ' . implode(', ', $missing)
        );
    }

    /**
     * Assert that calculation result is within acceptable precision
     *
     * @param float $expected Expected value
     * @param float $actual Actual value
     * @param int $decimals Decimal places for comparison
     * @param string $message Custom message
     * @return void
     */
    public function assertPrecisionEqual(
        float $expected,
        float $actual,
        int $decimals = 2,
        string $message = ''
    ): void {
        $tolerance = pow(10, -$decimals);
        $this->assertEqualsWithDelta(
            $expected,
            $actual,
            $tolerance,
            $message ?: "Values not equal to {$decimals} decimal places"
        );
    }
}
