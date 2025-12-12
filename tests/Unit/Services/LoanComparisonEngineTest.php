<?php

namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\LoanComparisonEngine;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class LoanComparisonEngineTest extends TestCase
{
    private LoanComparisonEngine $engine;
    private array $offers;

    protected function setUp(): void
    {
        $this->engine = new LoanComparisonEngine();
        $this->offers = $this->createTestOffers();
    }

    private function createTestOffers(): array
    {
        // Offer 1: 4% for 30 years with $3000 fees
        $offer1 = new Loan();
        $offer1->setId(1);
        $offer1->setPrincipal(300000.00);
        $offer1->setAnnualRate(0.04);
        $offer1->setMonths(360);
        $offer1->setStartDate(new DateTimeImmutable('2024-01-01'));
        $offer1->setCurrentBalance(300000.00);

        // Offer 2: 3.8% for 30 years with $2500 fees
        $offer2 = new Loan();
        $offer2->setId(2);
        $offer2->setPrincipal(300000.00);
        $offer2->setAnnualRate(0.038);
        $offer2->setMonths(360);
        $offer2->setStartDate(new DateTimeImmutable('2024-01-01'));
        $offer2->setCurrentBalance(300000.00);

        // Offer 3: 4.2% for 15 years with $1500 fees
        $offer3 = new Loan();
        $offer3->setId(3);
        $offer3->setPrincipal(300000.00);
        $offer3->setAnnualRate(0.042);
        $offer3->setMonths(180);
        $offer3->setStartDate(new DateTimeImmutable('2024-01-01'));
        $offer3->setCurrentBalance(300000.00);

        return [$offer1, $offer2, $offer3];
    }

    /**
     * Test 1: Compare offers side-by-side
     */
    public function testCompareLoanOffersSideBySide()
    {
        $comparison = $this->engine->compareLoanOffersSideBySide(
            $this->offers,
            [3000, 2500, 1500]  // Fees for each offer
        );

        $this->assertIsArray($comparison);
        $this->assertCount(3, $comparison);
        $this->assertArrayHasKey('offer_id', $comparison[0]);
        $this->assertArrayHasKey('total_cost', $comparison[0]);
    }

    /**
     * Test 2: Calculate total cost (principal + interest + fees)
     */
    public function testCalculateTotalCost()
    {
        $totalCost = $this->engine->calculateTotalCost(
            $this->offers[0],
            3000,
            0.04
        );

        // 30-year mortgage at 4%: ~$215,609 interest + $3000 fees
        $this->assertGreaterThan(215000, $totalCost);
        $this->assertLessThan(220000, $totalCost);
    }

    /**
     * Test 3: Calculate effective APR including fees
     */
    public function testCalculateEffectiveAPRWithFees()
    {
        $effectiveAPR = $this->engine->calculateEffectiveAPRWithFees(
            $this->offers[0]->getPrincipal(),
            $this->offers[0]->getAnnualRate(),
            3000,
            360
        );

        // Effective APR with fees (simplified calculation)
        $this->assertGreaterThan(0.02, $effectiveAPR);
        $this->assertLessThan(0.05, $effectiveAPR);
    }

    /**
     * Test 4: Calculate monthly payment
     */
    public function testCalculateMonthlyPayment()
    {
        $payment = $this->engine->calculateMonthlyPayment(
            $this->offers[0]->getPrincipal(),
            $this->offers[0]->getAnnualRate(),
            $this->offers[0]->getMonths()
        );

        // $300k at 4% for 30 years: ~$1,432/month
        $this->assertGreaterThan(1400, $payment);
        $this->assertLessThan(1500, $payment);
    }

    /**
     * Test 5: Compare total interest costs
     */
    public function testCompareTotalInterestCosts()
    {
        $interestComparison = $this->engine->compareTotalInterestCosts(
            $this->offers,
            0.04
        );

        $this->assertIsArray($interestComparison);
        $this->assertCount(3, $interestComparison);
        // 15-year should have less total interest than 30-year
        $this->assertLessThan(
            $interestComparison[0]['total_interest'],
            $interestComparison[2]['total_interest']
        );
    }

    /**
     * Test 6: Calculate break-even point for offers
     */
    public function testCalculateBreakEvenPoint()
    {
        $breakEven = $this->engine->calculateBreakEvenPoint(
            $this->offers[0],
            $this->offers[1],
            [3000, 2500]  // Fees
        );

        // Months until higher-cost offer becomes cheaper
        $this->assertGreaterThan(0, $breakEven);
        $this->assertLessThan(360, $breakEven);
    }

    /**
     * Test 7: Generate offer recommendation
     */
    public function testGenerateOfferRecommendation()
    {
        $recommendation = $this->engine->generateOfferRecommendation(
            $this->offers,
            [3000, 2500, 1500],
            'minimize_cost'  // Goal: minimize_cost, minimize_payment, minimize_term
        );

        $this->assertIsArray($recommendation);
        $this->assertArrayHasKey('recommended_offer_id', $recommendation);
        $this->assertArrayHasKey('reason', $recommendation);
        $this->assertArrayHasKey('savings', $recommendation);
    }

    /**
     * Test 8: Calculate cost savings between offers
     */
    public function testCalculateCostSavingsBetweenOffers()
    {
        $savings = $this->engine->calculateCostSavingsBetweenOffers(
            $this->offers[0],
            $this->offers[1],
            3000,
            2500,
            0.04
        );

        // Offer 2 (3.8%) should save vs Offer 1 (4%)
        $this->assertGreaterThan(0, $savings);
    }

    /**
     * Test 9: Validate offer comparison assumptions
     */
    public function testValidateOfferAssumptions()
    {
        $validation = $this->engine->validateOfferAssumptions($this->offers);

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('issues', $validation);
    }

    /**
     * Test 10: Generate offer comparison matrix
     */
    public function testGenerateOfferComparisonMatrix()
    {
        $matrix = $this->engine->generateOfferComparisonMatrix(
            $this->offers,
            [3000, 2500, 1500]
        );

        $this->assertIsArray($matrix);
        $this->assertArrayHasKey('offers', $matrix);
        $this->assertArrayHasKey('metrics', $matrix);
        $this->assertCount(3, $matrix['offers']);
    }

    /**
     * Test 11: Calculate payment-to-principal ratio
     */
    public function testCalculatePaymentToPrincipalRatio()
    {
        $ratio = $this->engine->calculatePaymentToPrincipalRatio(
            $this->offers[0]->getPrincipal(),
            $this->offers[0]->getAnnualRate(),
            $this->offers[0]->getMonths()
        );

        // Total payments / principal
        $this->assertGreaterThan(1.7, $ratio);  // Pay back >170% due to interest
    }

    /**
     * Test 12: Rank offers by scoring criteria
     */
    public function testRankOffersByScoring()
    {
        $ranked = $this->engine->rankOffersByScoring(
            $this->offers,
            [3000, 2500, 1500],
            [
                'cost' => 0.5,
                'payment' => 0.3,
                'term' => 0.2,
            ]
        );

        $this->assertIsArray($ranked);
        $this->assertCount(3, $ranked);
        // First item should be highest score
        $this->assertGreaterThan($ranked[1]['score'], $ranked[0]['score']);
    }

    /**
     * Test 13: Export comparison to JSON
     */
    public function testExportComparisonToJSON()
    {
        $matrix = $this->engine->generateOfferComparisonMatrix(
            $this->offers,
            [3000, 2500, 1500]
        );

        $json = $this->engine->exportToJSON($matrix);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('offers', $decoded);
    }

    /**
     * Test 14: Calculate loan affordability metric
     */
    public function testCalculateLoanAffordability()
    {
        $affordability = $this->engine->calculateLoanAffordability(
            $this->offers[0],
            3000,
            150000  // Annual income
        );

        // Monthly payment / monthly income
        $this->assertIsArray($affordability);
        $this->assertArrayHasKey('dti_ratio', $affordability);
        $this->assertGreaterThan(0, $affordability['dti_ratio']);
    }
}
