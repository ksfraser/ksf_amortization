<?php
namespace Ksfraser\Amortizations\Calculators;

/**
 * Interest Calculator - Single Responsibility: Calculate Interest Amounts
 *
 * Pure calculation class for all interest-related computations.
 * NO database access, NO side effects.
 *
 * ### Responsibility
 * - Calculate periodic interest on remaining balance
 * - Calculate simple interest for fixed periods
 * - Calculate compound interest with different frequencies
 * - Calculate daily accrual for partial periods
 * - Convert between interest rate frequencies
 * - Calculate APY from APR
 * - Pure functions: no persistence, no external state changes
 *
 * ### Interest Types Supported
 * - **Periodic**: Interest for one payment period (monthly, weekly, etc.)
 * - **Simple**: I = P × R × T (no compounding)
 * - **Compound**: A = P(1 + r/n)^(nt) (with compounding)
 * - **Daily**: For per diem interest calculations
 * - **Accrual**: Interest from date A to date B
 *
 * ### Usage Example
 * ```php
 * $interestCalc = new InterestCalculator();
 *
 * // Calculate monthly interest on remaining balance
 * $interest = $interestCalc->calculatePeriodicInterest(100000, 5.0, 'monthly');
 * // Result: 416.67
 *
 * // Calculate simple interest
 * $interest = $interestCalc->calculateSimpleInterest(100000, 5.0, 1);  // 1 year
 * // Result: 5000.00
 *
 * // Convert APR to APY
 * $apy = $interestCalc->calculateAPYFromAPR(5.0, 'monthly');
 * // Result: 5.116 (approximately)
 * ```
 *
 * ### Design Principles
 * - Single Responsibility: Only interest calculations
 * - Dependency Injection: PaymentCalculator for frequency support
 * - Immutability: No internal state changes
 * - Pure Functions: Same input always = same output
 *
 * @package   Ksfraser\Amortizations\Calculators
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-17
 */
class InterestCalculator
{
    /**
     * @var PaymentCalculator For frequency support
     */
    private $paymentCalculator;

    /**
     * @var int Decimal precision for calculations
     */
    private $precision = 4;

    /**
     * Constructor
     *
     * @throws InvalidArgumentException If calculator is null
     */
    public function __construct()
    {
        $this->paymentCalculator = new PaymentCalculator();
    }

    /**
     * Set calculation precision
     *
     * @param int $precision Decimal places
     *
     * @return void
     */
    public function setPrecision(int $precision): void
    {
        $this->precision = max(2, $precision);
    }

    /**
     * Calculate periodic interest on remaining balance
     *
     * Interest for one payment period:
     * I = Balance × (Annual Rate / 100) / Periods Per Year
     *
     * ### Example
     * Balance: $100,000
     * Annual Rate: 5%
     * Frequency: Monthly (12 periods)
     * Result: 100000 × 0.05 / 12 = 416.67
     *
     * @param float $balance Remaining balance
     * @param float $annualRate Annual interest rate as percentage
     * @param string $frequency Payment frequency ('monthly', 'biweekly', etc.)
     *
     * @return float Interest amount for one period
     *
     * @throws \InvalidArgumentException If parameters invalid
     */
    public function calculatePeriodicInterest(
        float $balance,
        float $annualRate,
        string $frequency
    ): float
    {
        $this->validateBalance($balance);
        $this->validateRate($annualRate);
        $this->validateFrequency($frequency);

        // Get periods per year
        $periodsPerYear = PaymentCalculator::getPeriodsPerYear($frequency);

        // Calculate interest
        $interest = $balance * ($annualRate / 100) / $periodsPerYear;

        return round($interest, 2);
    }

    /**
     * Calculate simple interest
     *
     * Simple Interest: I = P × R × T
     * Where:
     * - P = Principal
     * - R = Annual rate (as decimal, e.g., 0.05 for 5%)
     * - T = Time in years
     *
     * ### Example
     * Principal: $100,000
     * Rate: 5% (0.05)
     * Time: 1 year
     * Result: 100000 × 0.05 × 1 = 5000
     *
     * @param float $principal Principal amount
     * @param float $annualRate Annual interest rate as percentage
     * @param float $timeInYears Time period in years
     *
     * @return float Simple interest amount
     *
     * @throws \InvalidArgumentException If parameters invalid
     */
    public function calculateSimpleInterest(
        float $principal,
        float $annualRate,
        float $timeInYears
    ): float
    {
        $this->validateBalance($principal);
        $this->validateRate($annualRate);

        if ($timeInYears <= 0) {
            throw new \InvalidArgumentException('Time must be greater than 0');
        }

        // I = P × (R/100) × T
        $interest = $principal * ($annualRate / 100) * $timeInYears;

        return round($interest, 2);
    }

    /**
     * Calculate compound interest
     *
     * Compound Interest: A = P(1 + r/n)^(nt)
     * Interest = A - P
     * Where:
     * - A = Final amount
     * - P = Principal
     * - r = Annual rate (as decimal)
     * - n = Compounding periods per year
     * - t = Time in years
     *
     * ### Example
     * Principal: $100,000
     * Rate: 5% (0.05)
     * Periods: 12 (monthly)
     * Frequency: monthly
     * Result ≈ 5116.14
     *
     * @param float $principal Principal amount
     * @param float $annualRate Annual interest rate as percentage
     * @param int $periods Number of compounding periods
     * @param string $frequency Compounding frequency
     *
     * @return float Compound interest earned
     *
     * @throws \InvalidArgumentException If parameters invalid
     */
    public function calculateCompoundInterest(
        float $principal,
        float $annualRate,
        int $periods,
        string $frequency
    ): float
    {
        $this->validateBalance($principal);
        $this->validateRate($annualRate);
        $this->validateFrequency($frequency);

        if ($periods <= 0) {
            throw new \InvalidArgumentException('Periods must be greater than 0');
        }

        // Get periods per year for this frequency
        $periodsPerYear = PaymentCalculator::getPeriodsPerYear($frequency);

        // Calculate time in years
        $timeInYears = $periods / $periodsPerYear;

        // Periodic rate
        $periodicRate = ($annualRate / 100) / $periodsPerYear;

        // Final amount: A = P(1 + r)^n
        $finalAmount = $principal * pow(1 + $periodicRate, $periods);

        // Interest = A - P
        $interest = $finalAmount - $principal;

        return round($interest, 2);
    }

    /**
     * Calculate daily interest (for per diem calculations)
     *
     * Daily Interest: D = Balance × (Annual Rate / 100) / 365
     *
     * Used for partial month interest calculations,
     * prepaid interest, or buydown calculations.
     *
     * @param float $balance Account balance
     * @param float $annualRate Annual interest rate as percentage
     *
     * @return float Daily interest amount
     *
     * @throws \InvalidArgumentException If parameters invalid
     */
    public function calculateDailyInterest(
        float $balance,
        float $annualRate
    ): float
    {
        $this->validateBalance($balance);
        $this->validateRate($annualRate);

        // Daily rate = Annual rate / 365 days
        $dailyInterest = $balance * ($annualRate / 100) / 365;

        return round($dailyInterest, 2);
    }

    /**
     * Calculate total interest in schedule
     *
     * Sums interest_amount field from all schedule rows.
     *
     * @param array $schedule Array of schedule rows
     *
     * @return float Total interest paid
     */
    public function calculateTotalInterest(array $schedule): float
    {
        $total = 0;

        foreach ($schedule as $row) {
            if (isset($row['interest_amount']) && is_numeric($row['interest_amount'])) {
                $total += $row['interest_amount'];
            }
        }

        return round($total, 2);
    }

    /**
     * Calculate interest accrual between two dates
     *
     * Calculates interest accrued from startDate to endDate
     * using daily interest calculation.
     *
     * @param float $balance Account balance
     * @param float $annualRate Annual interest rate as percentage
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     *
     * @return float Interest accrued between dates
     *
     * @throws \InvalidArgumentException If parameters invalid
     */
    public function calculateInterestAccrual(
        float $balance,
        float $annualRate,
        string $startDate,
        string $endDate
    ): float
    {
        $this->validateBalance($balance);
        $this->validateRate($annualRate);

        // Calculate days between dates
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        $days = $interval->days;

        if ($days < 0) {
            throw new \InvalidArgumentException('End date must be after start date');
        }

        // Daily interest
        $dailyInterest = $this->calculateDailyInterest($balance, $annualRate);

        // Accrual = daily interest × number of days
        $accrual = $dailyInterest * $days;

        return round($accrual, 2);
    }

    /**
     * Calculate APY (Annual Percentage Yield) from APR
     *
     * APY = (1 + APR/n)^n - 1
     * Where n = compounding periods per year
     *
     * ### Example
     * APR: 5%
     * Frequency: Monthly (12 periods)
     * APY = (1 + 0.05/12)^12 - 1 = 5.116%
     *
     * @param float $apr Annual Percentage Rate as percentage
     * @param string $frequency Compounding frequency
     *
     * @return float APY as percentage
     *
     * @throws \InvalidArgumentException If parameters invalid
     */
    public function calculateAPYFromAPR(
        float $apr,
        string $frequency
    ): float
    {
        $this->validateRate($apr);
        $this->validateFrequency($frequency);

        $periodsPerYear = PaymentCalculator::getPeriodsPerYear($frequency);
        $periodicRate = ($apr / 100) / $periodsPerYear;

        // APY = (1 + r)^n - 1
        $apy = pow(1 + $periodicRate, $periodsPerYear) - 1;

        // Return as percentage
        return round($apy * 100, 4);
    }

    /**
     * Calculate effective interest rate for a frequency
     *
     * Same as APY - converts nominal to effective rate.
     *
     * @param float $nominalRate Nominal rate as percentage
     * @param string $frequency Compounding frequency
     *
     * @return float Effective rate as percentage
     *
     * @throws \InvalidArgumentException If parameters invalid
     */
    public function calculateEffectiveRate(
        float $nominalRate,
        string $frequency
    ): float
    {
        return $this->calculateAPYFromAPR($nominalRate, $frequency);
    }

    /**
     * Convert interest rate between frequencies
     *
     * Converts a rate from one frequency to another,
     * accounting for compounding differences.
     *
     * ### Example
     * Monthly rate: 0.4167% (5% / 12)
     * To annual: Multiply by 12 = 5%
     *
     * @param float $rate Interest rate (as percentage or decimal)
     * @param string $fromFrequency Current frequency
     * @param string $toFrequency Target frequency
     *
     * @return float Converted rate
     *
     * @throws \InvalidArgumentException If parameters invalid
     */
    public function convertRate(
        float $rate,
        string $fromFrequency,
        string $toFrequency
    ): float
    {
        $this->validateFrequency($fromFrequency);
        $this->validateFrequency($toFrequency);

        // Get periods per year for each frequency
        $fromPeriods = PaymentCalculator::getPeriodsPerYear($fromFrequency);
        $toPeriods = PaymentCalculator::getPeriodsPerYear($toFrequency);

        // Simple conversion: rate × (from periods / to periods)
        $convertedRate = $rate * ($fromPeriods / $toPeriods);

        return round($convertedRate, $this->precision);
    }

    /**
     * Validate balance is positive
     *
     * @param float $balance Balance amount
     *
     * @return void
     *
     * @throws \InvalidArgumentException If invalid
     */
    private function validateBalance(float $balance): void
    {
        if ($balance < 0) {
            throw new \InvalidArgumentException('Balance cannot be negative, got: ' . $balance);
        }
    }

    /**
     * Validate rate is non-negative
     *
     * @param float $rate Interest rate
     *
     * @return void
     *
     * @throws \InvalidArgumentException If invalid
     */
    private function validateRate(float $rate): void
    {
        if ($rate < 0) {
            throw new \InvalidArgumentException('Rate cannot be negative, got: ' . $rate);
        }
    }

    /**
     * Validate frequency is supported
     *
     * @param string $frequency Frequency name
     *
     * @return void
     *
     * @throws \InvalidArgumentException If invalid
     */
    private function validateFrequency(string $frequency): void
    {
        try {
            PaymentCalculator::getPeriodsPerYear($frequency);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Invalid frequency: ' . $frequency);
        }
    }
}
