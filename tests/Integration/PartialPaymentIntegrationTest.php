<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Models\Arrears;
use Ksfraser\Amortizations\EventHandlers\PartialPaymentEventHandler;
use Tests\Mocks\MockLoanRepository;
use Tests\Mocks\MockArrearsRepository;
use DateTimeImmutable;

/**
 * PartialPaymentIntegrationTest
 *
 * Integration tests for partial payment scenarios.
 * Tests interaction between:
 * - Event Handler (PartialPaymentEventHandler)
 * - Models (Loan, Arrears)
 * - Repositories (LoanRepository, ArrearsRepository)
 * - Payment priority logic
 *
 * Scenarios tested:
 * 1. Create loan → Record partial payment → Create arrears
 * 2. Cumulative arrears → Multiple shortfalls → Track total
 * 3. Payment application order → Penalties → Interest → Principal
 * 4. Arrears clearance → Full payment coverage
 *
 * @covers \Ksfraser\Amortizations\EventHandlers\PartialPaymentEventHandler
 * @covers \Ksfraser\Amortizations\Models\Arrears
 */
class PartialPaymentIntegrationTest extends TestCase
{
    private PartialPaymentEventHandler $handler;
    private MockLoanRepository $loanRepo;
    private MockArrearsRepository $arrearsRepo;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        $this->handler = new PartialPaymentEventHandler();
        $this->loanRepo = new MockLoanRepository();
        $this->arrearsRepo = new MockArrearsRepository();
    }

    /**
     * Test complete workflow: Loan → Partial payment → Arrears → Save
     *
     * @test
     */
    public function testCompletePartialPaymentWorkflow(): void
    {
        // STEP 1: Create loan
        $loan = new Loan();
        $loan->setPrincipal(50000);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(60);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));

        // STEP 2: Save loan
        $loanId = $this->loanRepo->save($loan);
        $this->assertGreaterThan(0, $loanId);

        // STEP 3: Record partial payment event
        $requiredPayment = 943.56;  // Typical payment
        $partialPayment = 500.00;   // Only 50% of required
        $shortfall = $requiredPayment - $partialPayment;

        // STEP 4: Handler would process event (testing with actual calls is tested in unit tests)
        // Here we just verify arrears creation logic

        // STEP 5: Create and save arrears
        $arrears = new Arrears(
            $loanId,
            0,              // principal arrears
            $shortfall,     // interest arrears
            1               // days overdue
        );

        $arrearsId = $this->arrearsRepo->save($arrears);
        $this->assertGreaterThan(0, $arrearsId);

        // STEP 6: Retrieve arrears
        $retrievedArrears = $this->arrearsRepo->findById($arrearsId);
        $this->assertNotNull($retrievedArrears);
        $this->assertEqualsWithDelta($shortfall, $retrievedArrears->getInterestAmount(), 0.01);

        // STEP 7: Verify loan has arrears
        $loanArrears = $this->arrearsRepo->findByLoanId($loanId);
        $this->assertCount(1, $loanArrears);
    }

    /**
     * Test cumulative arrears accumulation from multiple partial payments.
     *
     * @test
     */
    public function testCumulativeArrearsAccumulation(): void
    {
        $loanId = 1;
        $requiredPayment = 943.56;

        // Simulate 3 consecutive months of partial payments
        for ($month = 1; $month <= 3; $month++) {
            $partialPayment = 600.00;  // 63% of required
            $shortfall = $requiredPayment - $partialPayment;

            $arrears = new Arrears(
                $loanId,
                0,          // principal arrears
                $shortfall, // interest arrears
                $month * 30 // days overdue
            );

            $this->arrearsRepo->save($arrears);
        }

        // Verify total arrears
        $total = $this->arrearsRepo->getTotalArrearsForLoan($loanId);
        $expectedTotal = ($requiredPayment - 600.00) * 3;
        $this->assertEqualsWithDelta($expectedTotal, $total, 0.01);
    }

    /**
     * Test payment priority: Penalties > Interest Arrears > Principal Arrears
     *
     * @test
     */
    public function testPaymentPriorityApplication(): void
    {
        $loanId = 1;

        // Create arrears with multiple components
        $arrears = new Arrears(
            $loanId,
            1000.00,  // principal arrears
            200.00,   // interest arrears
            0
        );
        // Add penalty separately
        $arrears->addPenalty(50.00);

        $this->arrearsRepo->save($arrears);

        // Apply payment - should be applied in priority order
        // Payment: 100 (covers penalty fully)
        $payment1 = 100.00;
        $arrears->applyPayment($payment1);
        $this->assertEqualsWithDelta(0, $arrears->getPenaltyAmount(), 0.01);
        $this->assertEqualsWithDelta(200.00, $arrears->getInterestAmount(), 0.01);

        // Payment: 150 (covers remaining penalty + partial interest)
        $payment2 = 150.00;
        $arrears->applyPayment($payment2);
        $this->assertEqualsWithDelta(50.00, $arrears->getInterestAmount(), 0.01);
        $this->assertEqualsWithDelta(1000.00, $arrears->getPrincipalAmount(), 0.01);

        // Payment: 1100 (covers remaining interest + principal)
        $payment3 = 1100.00;
        $arrears->applyPayment($payment3);
        $this->assertTrue($arrears->isCleared());
    }

    /**
     * Test arrears clearance workflow.
     *
     * @test
     */
    public function testArrearsClearanceFlow(): void
    {
        $loanId = 1;

        // Create arrears
        $arrears = new Arrears(
            $loanId,
            0,        // principal arrears
            443.56,   // interest arrears (shortfall from month 1)
            30        // days overdue
        );

        $this->arrearsRepo->save($arrears);

        // Verify arrears is active
        $activeArrears = $this->arrearsRepo->findActiveByLoanId($loanId);
        $this->assertCount(1, $activeArrears);
        $this->assertFalse($activeArrears[0]->isCleared());

        // Apply payment to clear arrears
        $arrears->applyPayment(443.56);
        $this->assertTrue($arrears->isCleared());

        // Update arrears in repository
        $this->arrearsRepo->save($arrears);

        // Query should show no active arrears
        $activeArrears = $this->arrearsRepo->findActiveByLoanId($loanId);
        $this->assertCount(0, $activeArrears);
    }

    /**
     * Test loan collection status based on active arrears.
     *
     * @test
     */
    public function testLoanDelinquencyStatus(): void
    {
        // Create arrears for loans 1 and 2
        $arrears1 = new Arrears(
            1,
            0,
            100.00,
            45
        );
        $this->arrearsRepo->save($arrears1);

        $arrears2 = new Arrears(
            2,
            0,
            200.00,
            60
        );
        $this->arrearsRepo->save($arrears2);

        // Query loans with active arrears (collection list)
        $delinquentLoans = $this->arrearsRepo->getLoansWithActiveArrears();
        $this->assertCount(2, $delinquentLoans);
        $this->assertContains(1, $delinquentLoans);
        $this->assertContains(2, $delinquentLoans);
    }

    /**
     * Test overdue period tracking and escalation.
     *
     * @test
     */
    public function testOverduePeriodTracking(): void
    {
        // Create arrears at various overdue stages
        $arrearsDay15 = new Arrears(
            1,
            0,
            100.00,
            15
        );
        $this->arrearsRepo->save($arrearsDay15);

        $arrearsDay30 = new Arrears(
            2,
            0,
            150.00,
            30
        );
        $this->arrearsRepo->save($arrearsDay30);

        $arrearsDay60 = new Arrears(
            3,
            0,
            200.00,
            60
        );
        $this->arrearsRepo->save($arrearsDay60);

        // Query by overdue period (e.g., find 30+ day overdue)
        $overdue30Days = $this->arrearsRepo->findByDaysOverdue(30);
        $this->assertCount(2, $overdue30Days);  // Day 30 and Day 60
    }

    /**
     * Test penalty calculation and tracking.
     *
     * @test
     */
    public function testPenaltyTracking(): void
    {
        $loanId = 1;

        // Create arrears with penalties
        $arrears = new Arrears(
            $loanId,
            0,
            100.00,
            0
        );

        // Add penalty for late payment (e.g., 5% of shortfall)
        $penaltyAmount = $arrears->getInterestAmount() * 0.05;
        $arrears->addPenalty($penaltyAmount);

        $this->arrearsRepo->save($arrears);

        // Query total penalties for loan
        $totalPenalties = $this->arrearsRepo->getTotalPenaltiesForLoan($loanId);
        $this->assertEqualsWithDelta($penaltyAmount, $totalPenalties, 0.01);
    }

    /**
     * Test detection of loans with active arrears.
     *
     * @test
     */
    public function testActiveArrearsDetection(): void
    {
        // Create cleared arrears
        $clearedArrears = new Arrears(
            1,
            0,
            100.00,
            0
        );
        $clearedArrears->applyPayment(100.00);  // Fully paid
        $this->arrearsRepo->save($clearedArrears);

        // Create active arrears
        $activeArrears = new Arrears(
            2,
            0,
            100.00,
            0
        );
        // Not fully paid
        $this->arrearsRepo->save($activeArrears);

        // Loan 1 should not have active arrears
        $this->assertFalse($this->arrearsRepo->hasActiveArrears(1));

        // Loan 2 should have active arrears
        $this->assertTrue($this->arrearsRepo->hasActiveArrears(2));
    }

    /**
     * Test handler priority for event ordering.
     *
     * @test
     */
    public function testHandlerPriority(): void
    {
        // PartialPaymentEventHandler should have priority 60
        $priority = $this->handler->getPriority();
        $this->assertEquals(60, $priority);

        // This ensures it runs after extra payment handler (70)
        // but before skip payment handler (10)
    }

    /**
     * Test event metadata validation.
     *
     * @test
     */
    public function testEventMetadataValidation(): void
    {
        // PartialPaymentEventHandler should be identifiable
        $this->assertNotNull($this->handler);

        // Handler should have processable logic
        $this->assertTrue(method_exists($this->handler, 'handle'));
        $this->assertTrue(method_exists($this->handler, 'supports'));
        $this->assertTrue(method_exists($this->handler, 'getPriority'));
    }
}
