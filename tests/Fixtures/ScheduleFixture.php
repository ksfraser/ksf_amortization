<?php
namespace Tests\Fixtures;

/**
 * Schedule Test Fixture
 *
 * Provides standard test data for payment schedule records.
 * Ensures consistent schedule data across all test suites.
 *
 * ### Usage
 *
 * ```php
 * // Single schedule row
 * $row = ScheduleFixture::createRow(1, 1, '2025-02-01');
 *
 * // Multiple rows for a loan
 * $rows = ScheduleFixture::createSchedule(1, 12);
 *
 * // Custom schedule
 * $rows = ScheduleFixture::createSchedule(1, 60, [
 *     'payment_amount' => 600,
 *     'remaining_balance' => 25000
 * ]);
 * ```
 *
 * @package   Ksfraser\Amortizations\Tests\Fixtures
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-17
 */
class ScheduleFixture
{
    /**
     * Default schedule row values
     * @var array
     */
    private static $defaults = [
        'payment_date' => '2025-02-01',
        'payment_amount' => 554.73,
        'principal_portion' => 454.73,
        'interest_portion' => 100.00,
        'remaining_balance' => 29545.27,
        'payment_status' => 'pending',
        'posted_to_gl' => 0,
        'posted_at' => null,
        'trans_no' => 0,
        'trans_type' => '0',
        'created_at' => '2025-01-01 12:00:00',
        'updated_at' => '2025-01-01 12:00:00'
    ];

    /**
     * Create a single schedule row
     *
     * @param int $loanId Loan ID
     * @param int $paymentNumber Payment number (for date calculation)
     * @param string $startDate Schedule start date
     * @param array $overrides Overrides
     * @return array
     */
    public static function createRow(
        int $loanId,
        int $paymentNumber = 1,
        string $startDate = '2025-01-01',
        array $overrides = []
    ): array {
        $row = self::$defaults;
        $row['loan_id'] = $loanId;

        // Calculate payment date (monthly intervals)
        $date = new \DateTime($startDate);
        $date->modify("+{$paymentNumber} months");
        $row['payment_date'] = $date->format('Y-m-d');

        // Adjust balance based on payment number
        $row['remaining_balance'] = 30000 - ($paymentNumber * 450); // approximate

        // Merge overrides
        return array_merge($row, $overrides);
    }

    /**
     * Create a complete 12-month schedule
     *
     * @param int $loanId Loan ID
     * @param int $months Number of months
     * @param array $overrides Overrides for all rows
     * @return array Array of schedule rows
     */
    public static function createSchedule(
        int $loanId,
        int $months = 12,
        array $overrides = []
    ): array {
        $schedule = [];
        $remainingBalance = 30000.00;

        for ($i = 1; $i <= $months; $i++) {
            $interestPortion = round($remainingBalance * 0.045 / 12, 2);
            $principalPortion = 554.73 - $interestPortion;
            $remainingBalance = round($remainingBalance - $principalPortion, 2);

            $schedule[] = self::createRow($loanId, $i, '2025-01-01', array_merge([
                'payment_amount' => 554.73,
                'principal_portion' => round($principalPortion, 2),
                'interest_portion' => $interestPortion,
                'remaining_balance' => max(0, $remainingBalance)
            ], $overrides));
        }

        return $schedule;
    }

    /**
     * Create a schedule for a mortgage (360 months)
     *
     * @param int $loanId Loan ID
     * @param array $overrides Overrides
     * @return array Array of schedule rows
     */
    public static function createMortgageSchedule(int $loanId, array $overrides = []): array
    {
        return self::createSchedule($loanId, 360, array_merge([
            'payment_amount' => 1347.13,
        ], $overrides));
    }

    /**
     * Create a schedule with extra payments
     *
     * @param int $loanId Loan ID
     * @param int $extraPaymentMonth Month to apply extra payment
     * @param float $extraAmount Extra payment amount
     * @param array $overrides Overrides
     * @return array
     */
    public static function createScheduleWithExtraPayment(
        int $loanId,
        int $extraPaymentMonth = 1,
        float $extraAmount = 500.00,
        array $overrides = []
    ): array {
        $schedule = self::createSchedule($loanId, 12, $overrides);

        // Apply extra payment (reduces remaining balance)
        if (isset($schedule[$extraPaymentMonth - 1])) {
            $schedule[$extraPaymentMonth - 1]['payment_amount'] += $extraAmount;
        }

        // Recalculate subsequent rows
        $remainingBalance = $schedule[$extraPaymentMonth - 1]['remaining_balance'] ?? 30000;
        for ($i = $extraPaymentMonth; $i < count($schedule); $i++) {
            $interestPortion = round($remainingBalance * 0.045 / 12, 2);
            $principalPortion = 554.73 - $interestPortion;
            $remainingBalance = round($remainingBalance - $principalPortion, 2);

            $schedule[$i]['interest_portion'] = $interestPortion;
            $schedule[$i]['principal_portion'] = round($principalPortion, 2);
            $schedule[$i]['remaining_balance'] = max(0, $remainingBalance);
        }

        return $schedule;
    }

    /**
     * Create a posted schedule row
     *
     * @param int $loanId Loan ID
     * @param int $paymentNumber Payment number
     * @param int $transNo GL transaction number
     * @param string $transType GL transaction type
     * @return array
     */
    public static function createPostedRow(
        int $loanId,
        int $paymentNumber = 1,
        int $transNo = 1000,
        string $transType = '1'
    ): array {
        return self::createRow($loanId, $paymentNumber, '2025-01-01', [
            'posted_to_gl' => 1,
            'posted_at' => date('Y-m-d H:i:s'),
            'trans_no' => $transNo,
            'trans_type' => $transType,
            'payment_status' => 'posted'
        ]);
    }

    /**
     * Create multiple schedule rows for batch testing
     *
     * @param int $loanId Loan ID
     * @param int $count Number of rows
     * @param array $overrides Overrides
     * @return array
     */
    public static function createMultipleRows(int $loanId, int $count = 5, array $overrides = []): array
    {
        return self::createSchedule($loanId, $count, $overrides);
    }

    /**
     * Get schedule row IDs for database-less testing
     *
     * @param int $count Number of IDs
     * @param int $start Starting ID
     * @return array
     */
    public static function getRowIds(int $count = 12, int $start = 1): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $start + $i;
        }
        return $ids;
    }
}
