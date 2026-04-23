<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Models\Arrears;

class ArrearsTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $arrears = new Arrears(loanId: 1);

        $this->assertEquals(1, $arrears->getLoanId());
        $this->assertEquals(0.0, $arrears->getTotalAmount());
        $this->assertEquals(0.0, $arrears->getPrincipalAmount());
        $this->assertEquals(0.0, $arrears->getInterestAmount());
        $this->assertEquals(0.0, $arrears->getPenaltyAmount());
        $this->assertEquals(0, $arrears->getDaysOverdue());
        $this->assertNull($arrears->getId());
    }

    public function testConstructorWithPrincipalAndInterestArrears(): void
    {
        $arrears = new Arrears(
            loanId: 1,
            principalAmount: 500.00,
            interestAmount: 100.00,
            daysOverdue: 15
        );

        $this->assertEquals(600.00, $arrears->getTotalAmount());
        $this->assertEquals(500.00, $arrears->getPrincipalAmount());
        $this->assertEquals(100.00, $arrears->getInterestAmount());
        $this->assertEquals(15, $arrears->getDaysOverdue());
    }

    public function testConstructorThrowsOnNegativePrincipal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Amounts cannot be negative");

        new Arrears(loanId: 1, principalAmount: -100.00);
    }

    public function testConstructorThrowsOnNegativeInterest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Amounts cannot be negative");

        new Arrears(loanId: 1, interestAmount: -50.00);
    }

    public function testSetAndGetId(): void
    {
        $arrears = new Arrears(loanId: 1);
        $arrears->setId(100);
        $this->assertEquals(100, $arrears->getId());
    }

    public function testApplyPaymentClearsPenaltyFirst(): void
    {
        $arrears = new Arrears(loanId: 1, principalAmount: 100.00, interestAmount: 50.00);
        $arrears->addPenalty(200.00);

        $remaining = $arrears->applyPayment(200.00);

        $this->assertEquals(0, $remaining);
        $this->assertEquals(0.0, $arrears->getPenaltyAmount());
        $this->assertEquals(50.00, $arrears->getInterestAmount());
        $this->assertEquals(100.00, $arrears->getPrincipalAmount());
    }

    public function testApplyPaymentClearsInterestSecond(): void
    {
        $arrears = new Arrears(loanId: 1, principalAmount: 100.00, interestAmount: 50.00);
        $arrears->addPenalty(20.00);

        $remaining = $arrears->applyPayment(70.00);

        $this->assertEquals(0, $remaining);
        $this->assertEquals(0.0, $arrears->getPenaltyAmount());
        $this->assertEquals(0.0, $arrears->getInterestAmount());
        $this->assertEquals(100.00, $arrears->getPrincipalAmount());
    }

    public function testApplyPaymentClearsPrincipalThird(): void
    {
        $arrears = new Arrears(loanId: 1, principalAmount: 100.00, interestAmount: 50.00);
        $arrears->addPenalty(20.00);

        $remaining = $arrears->applyPayment(170.00);

        $this->assertEquals(0, $remaining);
        $this->assertEquals(0.0, $arrears->getPenaltyAmount());
        $this->assertEquals(0.0, $arrears->getInterestAmount());
        $this->assertEquals(0.0, $arrears->getPrincipalAmount());
    }

    public function testApplyPaymentReturnsRemaining(): void
    {
        $arrears = new Arrears(loanId: 1, principalAmount: 50.00, interestAmount: 30.00);

        $remaining = $arrears->applyPayment(200.00);

        $this->assertEquals(120.00, $remaining);
        $this->assertEquals(0.0, $arrears->getTotalAmount());
    }

    public function testApplyPaymentThrowsOnNegativeAmount(): void
    {
        $arrears = new Arrears(loanId: 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Payment cannot be negative");

        $arrears->applyPayment(-100.00);
    }

    public function testApplyPaymentUpdatesTotal(): void
    {
        $arrears = new Arrears(loanId: 1, principalAmount: 100.00, interestAmount: 50.00);
        $this->assertEquals(150.00, $arrears->getTotalAmount());

        $arrears->applyPayment(100.00);
        $this->assertEquals(50.00, $arrears->getTotalAmount());
    }

    public function testAddPenalty(): void
    {
        $arrears = new Arrears(loanId: 1, principalAmount: 100.00);
        $this->assertEquals(100.00, $arrears->getTotalAmount());

        $arrears->addPenalty(25.00);

        $this->assertEquals(25.00, $arrears->getPenaltyAmount());
        $this->assertEquals(125.00, $arrears->getTotalAmount());
    }

    public function testAddPenaltyRoundsToTwoDecimals(): void
    {
        $arrears = new Arrears(loanId: 1);
        $arrears->addPenalty(25.999);

        $this->assertEquals(26.00, $arrears->getPenaltyAmount());
    }

    public function testAddPenaltyThrowsOnNegativeAmount(): void
    {
        $arrears = new Arrears(loanId: 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Penalty cannot be negative");

        $arrears->addPenalty(-10.00);
    }

    public function testSetDaysOverdue(): void
    {
        $arrears = new Arrears(loanId: 1);
        $arrears->setDaysOverdue(30);

        $this->assertEquals(30, $arrears->getDaysOverdue());
    }

    public function testSetDaysOverdueThrowsOnNegative(): void
    {
        $arrears = new Arrears(loanId: 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Days overdue cannot be negative");

        $arrears->setDaysOverdue(-5);
    }

    public function testIsClearedWhenZero(): void
    {
        $arrears = new Arrears(loanId: 1);
        $this->assertTrue($arrears->isCleared());
    }

    public function testIsClearedAfterFullPayment(): void
    {
        $arrears = new Arrears(loanId: 1, principalAmount: 100.00);
        $this->assertFalse($arrears->isCleared());

        $arrears->applyPayment(100.00);
        $this->assertTrue($arrears->isCleared());
    }

    public function testIsClearedWithSmallAmount(): void
    {
        $arrears = new Arrears(loanId: 1);
        $arrears->addPenalty(0.005);

        $this->assertEquals(0.01, $arrears->getPenaltyAmount());
        $this->assertFalse($arrears->isCleared());
    }

    public function testMarkUpdated(): void
    {
        $arrears = new Arrears(loanId: 1);
        $this->assertNull($arrears->getUpdatedAt());

        $arrears->addPenalty(10.00);

        $this->assertInstanceOf(\DateTimeImmutable::class, $arrears->getUpdatedAt());
    }

    public function testToString(): void
    {
        $arrears = new Arrears(
            loanId: 1,
            principalAmount: 500.00,
            interestAmount: 200.00,
            daysOverdue: 15
        );
        $arrears->addPenalty(50.00);

        $expected = "$750.00 arrears (\$500.00 principal, \$200.00 interest, \$50.00 penalty) - 15 days overdue";
        $this->assertEquals($expected, (string)$arrears);
    }

    public function testGetCreatedAt(): void
    {
        $arrears = new Arrears(loanId: 1);
        $this->assertInstanceOf(\DateTimeImmutable::class, $arrears->getCreatedAt());
    }

    public function testPaymentPriorityWithMultipleComponents(): void
    {
        $arrears = new Arrears(loanId: 1);
        $arrears->addPenalty(100.00);

        $reflection = new \ReflectionClass($arrears);
        $property = $reflection->getProperty('interestAmount');
        $property->setAccessible(true);
        $property->setValue($arrears, 200.00);

        $property = $reflection->getProperty('principalAmount');
        $property->setAccessible(true);
        $property->setValue($arrears, 300.00);

        $property = $reflection->getProperty('totalAmount');
        $property->setAccessible(true);
        $property->setValue($arrears, 600.00);

        $remaining = $arrears->applyPayment(250.00);

        $this->assertEquals(0.00, $arrears->getPenaltyAmount());
        $this->assertEquals(50.00, $arrears->getInterestAmount());
        $this->assertEquals(300.00, $arrears->getPrincipalAmount());
        $this->assertEquals(0.00, $remaining);
    }
}
