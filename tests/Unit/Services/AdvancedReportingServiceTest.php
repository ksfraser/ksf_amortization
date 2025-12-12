<?php
namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\AdvancedReportingService;
use PHPUnit\Framework\TestCase;

class AdvancedReportingServiceTest extends TestCase {
    private AdvancedReportingService $service;
    private Loan $loan;
    private array $schedule;

    protected function setUp(): void {
        $this->service = new AdvancedReportingService();
        $this->loan = new Loan();
        $this->loan->setId(1)->setPrincipal(100000)->setAnnualRate(0.05)->setMonths(120);
        $this->schedule = $this->createTestSchedule();
    }

    private function createTestSchedule(): array {
        $schedule = [];
        for ($i = 0; $i < 120; $i++) {
            $schedule[$i] = [
                'payment' => 943.56,
                'principal' => 500 + ($i * 2),
                'interest' => 416.67 - ($i * 3),
                'balance' => 100000 - ($i * 500)
            ];
        }
        return $schedule;
    }

    public function testGenerateAmortizationChart(): void {
        $chart = $this->service->generateAmortizationChart($this->loan, $this->schedule);

        $this->assertIsString($chart);
        $this->assertStringContainsString('<table', $chart);
        $this->assertStringContainsString('Period', $chart);
        $this->assertStringContainsString('Payment', $chart);
    }

    public function testGeneratePaymentTrendChart(): void {
        $trends = $this->service->generatePaymentTrendChart($this->schedule);

        $this->assertIsArray($trends);
        $this->assertArrayHasKey('payments', $trends);
        $this->assertArrayHasKey('principals', $trends);
        $this->assertArrayHasKey('interests', $trends);
        $this->assertArrayHasKey('periods', $trends);
        $this->assertNotEmpty($trends['periods']);
    }

    public function testCalculateTotalInterest(): void {
        $totalInterest = $this->service->calculateTotalInterest($this->schedule);

        $this->assertIsFloat($totalInterest);
        $this->assertGreaterThan(0, $totalInterest);
    }

    public function testCalculateTotalPrincipal(): void {
        $totalPrincipal = $this->service->calculateTotalPrincipal($this->schedule);

        $this->assertIsFloat($totalPrincipal);
        $this->assertGreaterThan(0, $totalPrincipal);
    }

    public function testGenerateFinancialSummary(): void {
        $summary = $this->service->generateFinancialSummary($this->loan, $this->schedule);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('loan_amount', $summary);
        $this->assertArrayHasKey('interest_rate', $summary);
        $this->assertArrayHasKey('total_payments', $summary);
        $this->assertArrayHasKey('total_interest', $summary);
        $this->assertArrayHasKey('total_cost', $summary);
    }

    public function testExportToCSV(): void {
        $csv = $this->service->exportToCSV($this->schedule);

        $this->assertIsString($csv);
        $this->assertStringContainsString('Period', $csv);
        $this->assertStringContainsString('Payment', $csv);
        $this->assertStringContainsString(',', $csv);
    }

    public function testExportToJSON(): void {
        $json = $this->service->exportToJSON($this->loan, $this->schedule);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('principal', $decoded);
        $this->assertArrayHasKey('schedule', $decoded);
    }

    public function testGenerateHTML(): void {
        $html = $this->service->generateHTML($this->loan, $this->schedule);

        $this->assertIsString($html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('Loan Amortization Report', $html);
    }

    public function testGenerateMonthlyAnalysis(): void {
        $analysis = $this->service->generateMonthlyAnalysis($this->schedule);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('total_periods', $analysis);
        $this->assertArrayHasKey('monthly_average_payment', $analysis);
        $this->assertArrayHasKey('monthly_average_principal', $analysis);
        $this->assertArrayHasKey('monthly_average_interest', $analysis);
    }

    public function testCalculateInterestAccrual(): void {
        $accrual = $this->service->calculateInterestAccrual($this->schedule);

        $this->assertIsArray($accrual);
        $this->assertGreaterThan(0, count($accrual));
        $this->assertGreaterThan(0, end($accrual));
    }

    public function testSummarizePaymentHistory(): void {
        $summary = $this->service->summarizePaymentHistory($this->schedule);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_payments_scheduled', $summary);
        $this->assertArrayHasKey('first_payment_amount', $summary);
        $this->assertArrayHasKey('last_payment_amount', $summary);
    }

    public function testVisualizePaymentSchedule(): void {
        $visualization = $this->service->visualizePaymentSchedule($this->schedule);

        $this->assertIsArray($visualization);
        $this->assertGreaterThan(0, count($visualization));
    }

    public function testGenerateComparisonReport(): void {
        $comparison = $this->service->generateComparisonReport($this->schedule, $this->schedule);

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('schedule_1_total_interest', $comparison);
        $this->assertArrayHasKey('schedule_2_total_interest', $comparison);
        $this->assertArrayHasKey('interest_savings', $comparison);
    }

    public function testExportToXML(): void {
        $xml = $this->service->exportToXML($this->loan, $this->schedule);

        $this->assertIsString($xml);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('<loan>', $xml);
        $this->assertStringContainsString('</loan>', $xml);
    }
}
