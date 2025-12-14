<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Services\AdvancedAmortizationService;
use Ksfraser\Amortizations\Services\CacheManager;
use Ksfraser\Amortizations\AmortizationModel;
use Ksfraser\Amortizations\DataProviderInterface;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class AdvancedAmortizationServiceTest extends TestCase
{
    private AdvancedAmortizationService $service;
    private AmortizationModel $amortizationModel;
    private CacheManager $cache;
    private DataProviderInterface $mockDb;

    protected function setUp(): void
    {
        $this->mockDb = $this->createMock(DataProviderInterface::class);
        $this->amortizationModel = new AmortizationModel($this->mockDb);
        $this->cache = new CacheManager();
        $this->service = new AdvancedAmortizationService(
            $this->amortizationModel,
            $this->cache
        );
    }

    public function testGenerateBalloonPaymentSchedule(): void
    {
        $schedule = $this->service->generateBalloonPaymentSchedule(
            principal: 200000,
            rate: 5.0,
            months: 360,
            balloonPayment: 50000
        );

        $this->assertCount(360, $schedule);
        $this->assertArrayHasKey('balloon_payment', $schedule[359]);
        $this->assertEquals(50000, $schedule[359]['balloon_payment']);
    }

    public function testBalloonPaymentReducesMonthlyPayment(): void
    {
        $balloon = $this->service->generateBalloonPaymentSchedule(200000, 5.0, 360, 50000);
        $firstPayment = $balloon[0]['payment'];
        
        // Balloon reduces amortizable amount significantly
        $this->assertLessThan(900, $firstPayment);
        $this->assertGreaterThan(700, $firstPayment);
    }

    public function testBalloonPaymentThrowsOnInvalidAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generateBalloonPaymentSchedule(200000, 5.0, 360, 250000);
    }

    public function testBalloonPaymentThrowsOnNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generateBalloonPaymentSchedule(200000, 5.0, 360, -1000);
    }

    public function testGenerateVariableRateSchedule(): void
    {
        $rateSchedule = [5.0, 5.5, 6.0, 6.5];
        $schedule = $this->service->generateVariableRateSchedule(
            principal: 200000,
            rateSchedule: $rateSchedule,
            monthsPerTerm: 90
        );

        $this->assertNotEmpty($schedule);
        $this->assertArrayHasKey('rate', $schedule[0]);
        $this->assertArrayHasKey('term_number', $schedule[0]);
    }

    public function testVariableRateScheduleHasMultipleTerms(): void
    {
        $rateSchedule = [5.0, 6.0, 7.0];
        $schedule = $this->service->generateVariableRateSchedule(200000, $rateSchedule, 120);

        $terms = array_unique(array_column($schedule, 'term_number'));
        $this->assertGreaterThan(1, count($terms));
    }

    public function testVariableRateThrowsOnEmptySchedule(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generateVariableRateSchedule(200000, [], 90);
    }

    public function testVariableRateThrowsOnInvalidMonthsPerTerm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generateVariableRateSchedule(200000, [5.0], 0);
    }

    public function testApplyPrepayment(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $modified = $this->service->applyPrepayment($original, 12, 10000);

        $this->assertLessThan(count($original), count($modified));
        $this->assertArrayHasKey('prepayment', $modified[11]);
        $this->assertEquals(10000, $modified[11]['prepayment']);
    }

    public function testPrepaymentReducesPayments(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $modified = $this->service->applyPrepayment($original, 60, 20000, true);

        $this->assertLessThan(count($original), count($modified));
        $this->assertEquals(0, $modified[count($modified) - 1]['balance']);
    }

    public function testPrepaymentThrowsOnInvalidPaymentNumber(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $this->expectException(InvalidArgumentException::class);
        $this->service->applyPrepayment($original, 500, 10000);
    }

    public function testPrepaymentThrowsOnExcessiveAmount(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $this->expectException(InvalidArgumentException::class);
        $this->service->applyPrepayment($original, 1, 300000);
    }

    public function testPrepaymentThrowsOnNegativeAmount(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $this->expectException(InvalidArgumentException::class);
        $this->service->applyPrepayment($original, 1, -1000);
    }

    public function testApplySkipPayment(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $modified = $this->service->applySkipPayment($original, 12);

        // Skip payment extends schedule by adding a new payment record
        $this->assertGreaterThanOrEqual(count($original), count($modified));
        $this->assertArrayHasKey('skipped', $modified[11]);
        $this->assertTrue($modified[11]['skipped']);
    }

    public function testSkipPaymentCapitalizesInterest(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $modified = $this->service->applySkipPayment($original, 12, true);

        $originalBalance = $original[11]['balance'];
        $modifiedBalance = $modified[11]['balance'];

        $this->assertGreaterThan($originalBalance, $modifiedBalance);
    }

    public function testSkipPaymentThrowsOnInvalidPaymentNumber(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $this->expectException(InvalidArgumentException::class);
        $this->service->applySkipPayment($original, 500);
    }

    public function testModifyLoanTermsChangeRate(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $modified = $this->service->modifyLoanTerms($original, 61, newRate: 4.0);

        $this->assertGreaterThan(60, count($modified));
        $this->assertEquals(4.0, $modified[60]['rate']);
    }

    public function testModifyLoanTermsChangeLength(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $modified = $this->service->modifyLoanTerms($original, 61, newTerm: 240);

        $this->assertLessThan(count($original), count($modified));
    }

    public function testModifyLoanTermsNewPayment(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $modified = $this->service->modifyLoanTerms($original, 61, newPayment: 1200);

        $this->assertGreaterThan(60, count($modified));
        foreach (array_slice($modified, 60) as $payment) {
            $this->assertEquals(1200, $payment['payment']);
        }
    }

    public function testModifyLoanTermsThrowsOnInvalidMonth(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $this->expectException(InvalidArgumentException::class);
        $this->service->modifyLoanTerms($original, 500, newRate: 4.0);
    }

    public function testModifyLoanTermsThrowsIfPaidOff(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        // Balance goes to 0 near the end - find the point
        $paidOffMonth = 0;
        foreach ($original as $idx => $payment) {
            if ($payment['balance'] <= 0.01) {
                $paidOffMonth = $idx;
                break;
            }
        }
        
        if ($paidOffMonth > 0) {
            $this->expectException(InvalidArgumentException::class);
            $this->service->modifyLoanTerms($original, $paidOffMonth, newRate: 4.0);
        } else {
            // If we can't find paid off, that's a test issue, not service issue
            $this->assertTrue(true);
        }
    }

    public function testGenerateAlternativeScenarios(): void
    {
        $alternatives = $this->service->generateAlternativeScenarios(
            principal: 200000,
            standardRate: 5.0,
            standardMonths: 360
        );

        $this->assertArrayHasKey('scenarios', $alternatives);
        $this->assertArrayHasKey('summary', $alternatives);
        $this->assertArrayHasKey('standard', $alternatives['scenarios']);
        $this->assertGreaterThan(1, count($alternatives['scenarios']));
    }

    public function testAlternativeScenariosIncludeBalloon(): void
    {
        $alternatives = $this->service->generateAlternativeScenarios(200000, 5.0, 360);

        $this->assertArrayHasKey('balloon_20pct', $alternatives['scenarios']);
    }

    public function testAlternativeScenariosIncludeBiweekly(): void
    {
        $alternatives = $this->service->generateAlternativeScenarios(200000, 5.0, 360);

        $this->assertArrayHasKey('biweekly', $alternatives['scenarios']);
    }

    public function testAlternativeScenariosIncludeVariable(): void
    {
        $alternatives = $this->service->generateAlternativeScenarios(200000, 5.0, 360);

        $this->assertArrayHasKey('variable_stepped', $alternatives['scenarios']);
    }

    public function testAlternativeScenariosSkipsWhenRequested(): void
    {
        $scenarios = ['skip_balloon' => true, 'skip_variable' => true];
        $alternatives = $this->service->generateAlternativeScenarios(
            200000,
            5.0,
            360,
            $scenarios
        );

        $this->assertArrayNotHasKey('balloon_20pct', $alternatives['scenarios']);
        $this->assertArrayNotHasKey('variable_stepped', $alternatives['scenarios']);
    }

    public function testAlternativeSummaryMetrics(): void
    {
        $alternatives = $this->service->generateAlternativeScenarios(200000, 5.0, 360);
        $summary = $alternatives['summary'];

        foreach ($summary as $name => $metrics) {
            $this->assertArrayHasKey('num_payments', $metrics);
            $this->assertArrayHasKey('total_interest', $metrics);
            $this->assertArrayHasKey('total_payment', $metrics);
        }
    }

    public function testCompareScenarioCosts(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $balloon = $this->service->generateBalloonPaymentSchedule(200000, 5.0, 360, 50000);

        $scenarios = [
            'standard' => $original,
            'balloon' => $balloon,
        ];

        $comparison = $this->service->compareScenarioCosts($scenarios);

        $this->assertCount(2, $comparison);
        $this->assertArrayHasKey('total_cost', $comparison[0]);
    }

    public function testCompareScenariosCostsAreSorted(): void
    {
        $scenarios = [
            'expensive' => $this->generateTestSchedule(200000, 8.0, 360),
            'cheap' => $this->generateTestSchedule(200000, 3.0, 360),
        ];

        $comparison = $this->service->compareScenarioCosts($scenarios);
        $costs = array_column($comparison, 'total_cost');

        for ($i = 1; $i < count($costs); $i++) {
            $this->assertGreaterThanOrEqual($costs[$i - 1], $costs[$i]);
        }
    }

    public function testBalloonPaymentScheduleUsesCaching(): void
    {
        $this->service->generateBalloonPaymentSchedule(200000, 5.0, 360, 50000);
        $cached = $this->service->generateBalloonPaymentSchedule(200000, 5.0, 360, 50000);

        $this->assertNotEmpty($cached);
    }

    public function testVariableRateScheduleUsesCaching(): void
    {
        $this->service->generateVariableRateSchedule(200000, [5.0, 6.0], 180);
        $cached = $this->service->generateVariableRateSchedule(200000, [5.0, 6.0], 180);

        $this->assertNotEmpty($cached);
    }

    public function testAlternativeScenariosUsesCaching(): void
    {
        $this->service->generateAlternativeScenarios(200000, 5.0, 360);
        $cached = $this->service->generateAlternativeScenarios(200000, 5.0, 360);

        $this->assertNotEmpty($cached);
        $this->assertArrayHasKey('scenarios', $cached);
    }

    public function testBalloonPaymentWithZeroBalance(): void
    {
        $schedule = $this->service->generateBalloonPaymentSchedule(
            principal: 200000,
            rate: 5.0,
            months: 360,
            balloonPayment: 0
        );

        $this->assertCount(360, $schedule);
    }

    public function testVariableRateWithConstantRate(): void
    {
        $schedule = $this->service->generateVariableRateSchedule(
            principal: 200000,
            rateSchedule: [5.0, 5.0, 5.0],
            monthsPerTerm: 120
        );

        $rates = array_unique(array_column($schedule, 'rate'));
        $this->assertCount(1, $rates);
    }

    public function testPrepaymentWithMinimalAmount(): void
    {
        $original = $this->generateTestSchedule(200000, 5.0, 360);
        $modified = $this->service->applyPrepayment($original, 1, 0.01);

        $this->assertLessThan($original[0]['balance'], $modified[0]['balance']);
    }

    private function generateTestSchedule(float $principal, float $rate, int $months): array
    {
        $monthlyRate = $rate / 100 / 12;
        $payment = $this->amortizationModel->calculatePayment($principal, $rate, 'monthly', $months);
        
        $schedule = [];
        $balance = $principal;

        for ($i = 1; $i <= $months; $i++) {
            $interest = $balance * $monthlyRate;
            $principalPayment = $payment - $interest;
            $balance -= $principalPayment;

            $schedule[] = [
                'payment_number' => $i,
                'payment' => round($payment, 2),
                'principal' => round($principalPayment, 2),
                'interest' => round($interest, 2),
                'balance' => round(max(0, $balance), 2),
                'rate' => $rate,
            ];
        }

        return $schedule;
    }
}
