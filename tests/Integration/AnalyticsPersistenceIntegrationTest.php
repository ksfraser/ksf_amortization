<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * Analytics-Persistence Integration Test
 * Validates analytics queries against real loan data
 */
class AnalyticsPersistenceIntegrationTest extends IntegrationTestCase
{
    /**
     * Test portfolio balance calculation from real data
     */
    public function testPortfolioBalanceCalculation(): void
    {
        // Create portfolio with 3 loans
        $loan1Id = $this->createLoanWithSchedule('LOAN-101', 1, 100000, 5.0, 360);
        $loan2Id = $this->createLoanWithSchedule('LOAN-102', 2, 150000, 5.5, 360);
        $loan3Id = $this->createLoanWithSchedule('LOAN-103', 3, 200000, 4.5, 360);

        $portfolioId = $this->createPortfolioWithLoans('Portfolio A', 1, [
            $loan1Id, $loan2Id, $loan3Id
        ]);

        // Get portfolio balance (sum of pending payment schedules)
        $balance = $this->portfolioAnalytics->getTotalPrincipalBalance($portfolioId);

        // Verify balance is numeric and positive
        $this->assertIsNumeric($balance);
        $this->assertGreaterThan(0, $balance);
    }

    /**
     * Test weighted average interest rate calculation
     */
    public function testWeightedAverageRateCalculation(): void
    {
        $loan1Id = $this->createLoanWithSchedule('LOAN-104', 1, 100000, 5.0, 360);
        $loan2Id = $this->createLoanWithSchedule('LOAN-105', 2, 100000, 6.0, 360);

        $portfolioId = $this->createPortfolioWithLoans('Portfolio B', 1, [
            $loan1Id, $loan2Id
        ]);

        $avgRate = $this->portfolioAnalytics->getWeightedAverageRate($portfolioId);

        // Expected: (100000*5.0 + 100000*6.0) / 200000 = 5.5
        $this->assertEquals(5.5, $avgRate);
    }

    /**
     * Test portfolio status distribution
     */
    public function testPortfolioStatusDistribution(): void
    {
        $loan1Id = $this->createLoanWithSchedule('LOAN-106', 1, 100000, 5.0, 360);
        $loan2Id = $this->createLoanWithSchedule('LOAN-107', 2, 150000, 5.5, 360);

        // Mark second loan as paid off
        $this->loanRepo->update($loan2Id, ['status' => 'paid_off']);

        $portfolioId = $this->createPortfolioWithLoans('Portfolio C', 1, [
            $loan1Id, $loan2Id
        ]);

        $statusDist = $this->portfolioAnalytics->getPortfolioLoanStatus($portfolioId);

        $this->assertIsArray($statusDist);
        $this->assertArrayHasKey('active', $statusDist);
        $this->assertArrayHasKey('paid_off', $statusDist);
        $this->assertEquals(1, $statusDist['active']);
        $this->assertEquals(1, $statusDist['paid_off']);
    }

    /**
     * Test payment history time series
     */
    public function testPaymentHistoryTimeSeries(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-108', 1, 100000, 5.0, 360);

        // Mark some payments as paid
        $schedules = $this->scheduleRepo->findBy(['loan_id' => $loanId]);
        for ($i = 0; $i < 3; $i++) {
            $this->scheduleRepo->update($schedules[$i]['id'], [
                'status' => 'paid',
                'paid_date' => date('Y-m-d', strtotime('+' . ($i + 1) . ' months')),
            ]);
        }

        // Get payment history
        $history = $this->timeSeriesAnalytics->getLoanPaymentHistory($loanId);

        $this->assertIsArray($history);
        $this->assertGreaterThan(0, count($history));
    }

    /**
     * Test cumulative interest paid calculation
     */
    public function testCumulativeInterestPaidCalculation(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-109', 1, 100000, 5.0, 360);

        // Get cumulative interest for first 3 months
        $cumulative = $this->timeSeriesAnalytics->getCumulativeInterestPaid($loanId);

        $this->assertIsArray($cumulative);
        $this->assertGreaterThan(0, count($cumulative));

        // Verify cumulative values are increasing
        if (count($cumulative) > 1) {
            for ($i = 1; $i < count($cumulative); $i++) {
                $this->assertGreaterThanOrEqual(
                    $cumulative[$i - 1]['cumulative_interest'],
                    $cumulative[$i]['cumulative_interest']
                );
            }
        }
    }

    /**
     * Test amortization rate calculation
     */
    public function testAmortizationRateCalculation(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-110', 1, 100000, 5.0, 360);

        $rate = $this->timeSeriesAnalytics->getAmortizationRate($loanId);

        // Returns a single float value representing average balance reduction per period
        $this->assertIsNumeric($rate);
        $this->assertGreaterThanOrEqual(0, $rate);
    }

    /**
     * Test predictive analytics with real loan data
     */
    public function testPredictiveAnalyticsWithRealData(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-111', 1, 100000, 5.0, 360);

        // Get predictions
        $remainingTerm = $this->predictiveAnalytics->predictRemainingTerm($loanId);
        $totalInterest = $this->predictiveAnalytics->estimateTotalInterest($loanId);

        $this->assertIsNumeric($remainingTerm);
        $this->assertGreaterThan(0, $remainingTerm);
        $this->assertIsNumeric($totalInterest);
        $this->assertGreaterThan(0, $totalInterest);
    }

    /**
     * Test delinquency risk prediction
     */
    public function testDelinquencyRiskPrediction(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-112', 1, 100000, 5.0, 360);

        // Mark a payment as delinquent
        $schedules = $this->scheduleRepo->findBy(['loan_id' => $loanId]);
        $this->scheduleRepo->update($schedules[0]['id'], [
            'status' => 'delinquent',
            'delinquency_days' => 30,
        ]);

        // Predict delinquency risk
        $risk = $this->predictiveAnalytics->predictDelinquencyRisk($loanId);

        $this->assertIsNumeric($risk);
        $this->assertGreaterThanOrEqual(0, $risk);
        $this->assertLessThanOrEqual(1, $risk);
    }

    /**
     * Test analytics consistency across multiple loans
     */
    public function testAnalyticsConsistencyMultipleLoans(): void
    {
        $loan1Id = $this->createLoanWithSchedule('LOAN-113', 1, 100000, 5.0, 360);
        $loan2Id = $this->createLoanWithSchedule('LOAN-114', 2, 100000, 5.0, 360);

        $portfolioId = $this->createPortfolioWithLoans('Portfolio D', 1, [
            $loan1Id, $loan2Id
        ]);

        // Get portfolio balance
        $balance = $this->portfolioAnalytics->getTotalPrincipalBalance($portfolioId);

        // Verify balance is positive (sum of pending payment schedules)
        $this->assertIsNumeric($balance);
        $this->assertGreaterThan(0, $balance);
    }

    /**
     * Test LTV (Loan-to-Value) estimation
     */
    public function testLTVEstimation(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-115', 1, 100000, 5.0, 360);

        $ltv = $this->predictiveAnalytics->estimateLTV($loanId);

        $this->assertIsNumeric($ltv);
        $this->assertGreaterThanOrEqual(0, $ltv);
    }

    /**
     * Test prepayment probability calculation
     */
    public function testPrepaymentProbabilityCalculation(): void
    {
        $loanId = $this->createLoanWithSchedule('LOAN-116', 1, 100000, 5.0, 360);

        $probability = $this->predictiveAnalytics->estimatePrepaymentProbability($loanId);

        $this->assertIsNumeric($probability);
        $this->assertGreaterThanOrEqual(0, $probability);
        $this->assertLessThanOrEqual(1, $probability);
    }
}
