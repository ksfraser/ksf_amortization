<?php

namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\LoanSchedule;
use Ksfraser\Amortizations\Services\TaxDeductionReportGenerator;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class TaxDeductionReportGeneratorTest extends TestCase
{
    private TaxDeductionReportGenerator $generator;
    private Loan $loan;

    protected function setUp(): void
    {
        $this->generator = new TaxDeductionReportGenerator();
        $this->loan = $this->createTestLoan();
    }

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(200000.00);
        $loan->setAnnualRate(0.04);
        $loan->setMonths(360);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(200000.00);
        return $loan;
    }

    private function createTestSchedule($loan): array
    {
        $schedule = [];
        $balance = $loan->getPrincipal();
        $rate = $loan->getAnnualRate() / 12;

        for ($month = 1; $month <= $loan->getMonths(); $month++) {
            $interest = round($balance * $rate, 2);
            $payment = 954.83;  // Fixed payment for 30-year mortgage at 4%
            $principal = $payment - $interest;
            $balance -= $principal;

            $schedule[] = [
                'month' => $month,
                'payment' => $payment,
                'principal' => $principal,
                'interest' => $interest,
                'balance' => max(0, $balance),
                'date' => (new DateTimeImmutable('2024-01-01'))->modify("+{$month} month")->format('Y-m-d'),
            ];
        }

        return $schedule;
    }

    private function getSchedulePayments(array $schedule): array
    {
        return $schedule;  // For mock compatibility
    }

    /**
     * Test 1: Generate annual tax deduction report
     */
    public function testGenerateAnnualTaxDeductionReport()
    {
        $schedule = $this->createTestSchedule($this->loan);

        $report = $this->generator->generateAnnualTaxDeductionReport($this->loan, $schedule, 2024);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('year', $report);
        $this->assertArrayHasKey('total_interest', $report);
        $this->assertArrayHasKey('monthly_breakdown', $report);
        $this->assertEquals(2024, $report['year']);
    }

    /**
     * Test 2: Calculate total interest for year
     */
    public function testCalculateTotalInterestForYear()
    {
        $schedule = $this->createTestSchedule($this->loan);

        $totalInterest = $this->generator->calculateTotalInterestForYear($schedule, 2024);

        // First year of 30-year mortgage at 4%: ~$7,200-$7,350
        $this->assertGreaterThan(7200, $totalInterest);
        $this->assertLessThan(7400, $totalInterest);
    }

    /**
     * Test 3: Generate monthly breakdown
     */
    public function testGenerateMonthlyBreakdown()
    {
        $schedule = $this->createTestSchedule($this->loan);

        $breakdown = $this->generator->generateMonthlyBreakdown($schedule, 2024);

        $this->assertIsArray($breakdown);
        $this->assertGreaterThanOrEqual(11, count($breakdown));  // 2024 is leap year, Feb may vary
        $this->assertArrayHasKey('month', $breakdown[0]);
        $this->assertArrayHasKey('interest', $breakdown[0]);
    }

    /**
     * Test 4: Calculate cumulative interest through year
     */
    public function testCalculateCumulativeInterestThroughYear()
    {
        $schedule = $this->createTestSchedule($this->loan);

        $cumulative = $this->generator->calculateCumulativeInterestThroughYear($schedule, 2024);

        $this->assertIsArray($cumulative);
        $this->assertGreaterThanOrEqual(11, count($cumulative));  // 2024 is leap year
        // Each month should be >= previous (monotonic increase)
        for ($i = 1; $i < count($cumulative); $i++) {
            $this->assertGreaterThanOrEqual($cumulative[$i - 1], $cumulative[$i]);
        }
    }

    /**
     * Test 5: Generate tax summary with itemized interest
     */
    public function testGenerateTaxSummaryWithItemizedInterest()
    {
        $schedule = $this->createTestSchedule($this->loan);

        $summary = $this->generator->generateTaxSummary($this->loan, $schedule, 2024);

        $this->assertArrayHasKey('year', $summary);
        $this->assertArrayHasKey('total_interest_deductible', $summary);
        $this->assertArrayHasKey('itemized_deductions', $summary);
        $this->assertArrayHasKey('loan_info', $summary);
        $this->assertGreaterThan(0, $summary['total_interest_deductible']);
    }

    /**
     * Test 6: Calculate mortgage interest deduction limit
     */
    public function testCalculateMortgageInterestDeductionLimit()
    {
        $schedule = $this->createTestSchedule($this->loan);
        $totalInterest = $this->generator->calculateTotalInterestForYear($schedule, 2024);

        // Under $750k original balance: No limit (full deduction)
        $limit = $this->generator->calculateMortgageInterestDeductionLimit(
            $this->loan->getPrincipal(),
            $totalInterest
        );

        $this->assertEquals($totalInterest, $limit);
    }

    /**
     * Test 7: Calculate mortgage interest deduction with high balance
     */
    public function testCalculateMortgageInterestDeductionWithHighBalance()
    {
        $highBalanceLoan = new Loan();
        $highBalanceLoan->setId(2);
        $highBalanceLoan->setPrincipal(1000000.00);
        $highBalanceLoan->setAnnualRate(0.04);
        $highBalanceLoan->setMonths(360);
        $highBalanceLoan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $highBalanceLoan->setCurrentBalance(1000000.00);

        $schedule = $this->createTestSchedule($highBalanceLoan);
        $totalInterest = $this->generator->calculateTotalInterestForYear($schedule, 2024);

        // Over $750k: Deduction limit applies (pro-rata reduction)
        $limit = $this->generator->calculateMortgageInterestDeductionLimit(
            $highBalanceLoan->getPrincipal(),
            $totalInterest
        );

        $this->assertLessThanOrEqual($totalInterest, $limit);
    }

    /**
     * Test 8: Export tax report to JSON
     */
    public function testExportTaxReportToJSON()
    {
        $schedule = $this->createTestSchedule($this->loan);
        $report = $this->generator->generateAnnualTaxDeductionReport($this->loan, $schedule, 2024);

        $json = $this->generator->exportToJSON($report);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($report['year'], $decoded['year']);
    }

    /**
     * Test 9: Generate multi-year tax deduction report
     */
    public function testGenerateMultiYearTaxDeductionReport()
    {
        $schedule = $this->createTestSchedule($this->loan);

        $reports = $this->generator->generateMultiYearTaxDeductionReport(
            $this->loan,
            $schedule,
            2024,
            2026
        );

        $this->assertIsArray($reports);
        $this->assertCount(3, $reports);
        $this->assertEquals(2024, $reports[0]['year']);
        $this->assertEquals(2026, $reports[2]['year']);
    }

    /**
     * Test 10: Calculate tax deduction for refinanced loan
     */
    public function testCalculateTaxDeductionForRefinancedLoan()
    {
        $schedule1 = $this->createTestSchedule($this->loan);
        $schedulePartial1 = array_slice($schedule1, 0, 6);  // 6 months

        $refinancedLoan = new Loan();
        $refinancedLoan->setId(2);
        $refinancedLoan->setPrincipal(195000.00);
        $refinancedLoan->setAnnualRate(0.035);
        $refinancedLoan->setMonths(360);
        $refinancedLoan->setStartDate(new DateTimeImmutable('2024-07-01'));
        $refinancedLoan->setCurrentBalance(195000.00);

        $schedule2 = $this->createTestSchedule($refinancedLoan);
        $schedulePartial2 = array_slice($schedule2, 0, 6);  // 6 months

        // Combine for year 2024
        $combinedSchedule = array_merge($schedulePartial1, $schedulePartial2);

        $totalInterest = $this->generator->calculateTotalInterestForYear(
            $combinedSchedule,
            2024
        );

        $this->assertGreaterThan(0, $totalInterest);
    }

    /**
     * Test 11: Generate tax estimate projection
     */
    public function testGenerateTaxEstimateProjection()
    {
        $schedule = $this->createTestSchedule($this->loan);

        $projection = $this->generator->generateTaxEstimateProjection(
            $this->loan,
            $schedule,
            2024,
            5
        );

        $this->assertIsArray($projection);
        $this->assertCount(5, $projection);
        $this->assertEquals(2024, $projection[0]['year']);
        $this->assertEquals(2028, $projection[4]['year']);
    }

    /**
     * Test 12: Validate tax deduction compliance
     */
    public function testValidateTaxDeductionCompliance()
    {
        $schedule = $this->createTestSchedule($this->loan);
        $report = $this->generator->generateAnnualTaxDeductionReport($this->loan, $schedule, 2024);

        $validation = $this->generator->validateTaxDeductionCompliance($report);

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('compliant', $validation);
        $this->assertTrue($validation['compliant']);
    }
}
