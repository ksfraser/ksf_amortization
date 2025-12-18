<?php
namespace Tests\Fixtures;

/**
 * Loan Test Fixture
 *
 * Provides standard test data for loan records across all test suites.
 * Centralizes loan data creation to ensure consistency and reduce duplication.
 *
 * ### Usage
 *
 * ```php
 * // Basic loan
 * $loan = LoanFixture::createDefaultLoan();
 *
 * // Customized loan
 * $loan = LoanFixture::createLoan([
 *     'principal' => 50000,
 *     'interest_rate' => 5.5,
 *     'term_months' => 84
 * ]);
 *
 * // Loan with specific type
 * $loan = LoanFixture::createAutoLoan();
 * $loan = LoanFixture::createMortgage();
 * ```
 *
 * @package   Ksfraser\Amortizations\Tests\Fixtures
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-17
 */
class LoanFixture
{
    /**
     * Default loan values
     * @var array
     */
    private static $defaults = [
        'loan_type' => 'Auto',
        'description' => 'Test Auto Loan',
        'principal' => 30000.00,
        'interest_rate' => 4.5,
        'term_months' => 60,
        'repayment_schedule' => 'monthly',
        'start_date' => '2025-01-01',
        'end_date' => '2030-01-01',
        'created_by' => 'test_user',
        'amount_financed' => 30000.00,
        'payment_frequency' => 'monthly',
        'interest_calc_frequency' => 'monthly',
        'loan_term_years' => 5,
        'payments_per_year' => 12,
        'regular_payment' => 554.73,
        'first_payment_date' => '2025-02-01',
        'last_payment_date' => '2030-01-01',
        'override_payment' => 0,
        'borrower_type' => 'Employee'
    ];

    /**
     * Create a loan with default values
     *
     * @return array
     */
    public static function createDefaultLoan(): array
    {
        return self::$defaults;
    }

    /**
     * Create a loan with custom values
     *
     * @param array $overrides Fields to override
     * @return array
     */
    public static function createLoan(array $overrides = []): array
    {
        return array_merge(self::$defaults, $overrides);
    }

    /**
     * Create an auto loan
     *
     * @param array $overrides Additional overrides
     * @return array
     */
    public static function createAutoLoan(array $overrides = []): array
    {
        return self::createLoan(array_merge([
            'loan_type' => 'Auto',
            'description' => 'Test Auto Loan',
            'principal' => 30000.00,
            'interest_rate' => 4.5,
            'term_months' => 60,
        ], $overrides));
    }

    /**
     * Create a mortgage loan
     *
     * @param array $overrides Additional overrides
     * @return array
     */
    public static function createMortgage(array $overrides = []): array
    {
        return self::createLoan(array_merge([
            'loan_type' => 'Mortgage',
            'description' => 'Test Mortgage',
            'principal' => 300000.00,
            'interest_rate' => 3.5,
            'term_months' => 360,
            'regular_payment' => 1347.13,
            'loan_term_years' => 30,
        ], $overrides));
    }

    /**
     * Create a personal loan
     *
     * @param array $overrides Additional overrides
     * @return array
     */
    public static function createPersonalLoan(array $overrides = []): array
    {
        return self::createLoan(array_merge([
            'loan_type' => 'Personal',
            'description' => 'Test Personal Loan',
            'principal' => 15000.00,
            'interest_rate' => 7.5,
            'term_months' => 36,
            'regular_payment' => 451.50,
            'loan_term_years' => 3,
        ], $overrides));
    }

    /**
     * Create a short-term loan (high interest)
     *
     * @param array $overrides Additional overrides
     * @return array
     */
    public static function createShortTermLoan(array $overrides = []): array
    {
        return self::createLoan(array_merge([
            'loan_type' => 'ShortTerm',
            'description' => 'Test Short-term Loan',
            'principal' => 5000.00,
            'interest_rate' => 12.0,
            'term_months' => 12,
            'regular_payment' => 430.56,
            'loan_term_years' => 1,
        ], $overrides));
    }

    /**
     * Create a loan with variable rate scenario
     *
     * @param array $overrides Additional overrides
     * @return array
     */
    public static function createVariableRateLoan(array $overrides = []): array
    {
        return self::createLoan(array_merge([
            'loan_type' => 'Auto',
            'description' => 'Test Variable Rate Loan',
            'principal' => 25000.00,
            'interest_rate' => 4.0,
            'term_months' => 48,
            'regular_payment' => 555.75,
        ], $overrides));
    }

    /**
     * Create a loan with balloon payment
     *
     * @param array $overrides Additional overrides
     * @return array
     */
    public static function createBalloonLoan(array $overrides = []): array
    {
        return self::createLoan(array_merge([
            'loan_type' => 'Auto',
            'description' => 'Test Balloon Loan',
            'principal' => 35000.00,
            'interest_rate' => 4.5,
            'term_months' => 60,
            'regular_payment' => 450.00,
            'balloon_payment' => 10000.00,
        ], $overrides));
    }

    /**
     * Create multiple loans for batch testing
     *
     * @param int $count Number of loans to create
     * @param array $overrides Overrides for all loans
     * @return array Array of loan arrays
     */
    public static function createMultipleLoans(int $count, array $overrides = []): array
    {
        $loans = [];
        for ($i = 0; $i < $count; $i++) {
            $loans[] = self::createLoan(array_merge([
                'description' => "Test Loan #{$i}"
            ], $overrides));
        }
        return $loans;
    }

    /**
     * Get loan IDs for database-less testing
     *
     * @param int $count Number of IDs
     * @param int $start Starting ID
     * @return array Array of loan IDs
     */
    public static function getLoanIds(int $count = 5, int $start = 1): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $start + $i;
        }
        return $ids;
    }
}
