<?php
namespace Ksfraser\Amortizations\Tests;

use Ksfraser\Amortizations\AmortizationModel;
use Ksfraser\Amortizations\DataProviderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AmortizationModel
 * @covers Ksfraser\Amortizations\AmortizationModel
 */
class AmortizationModelTest extends TestCase {
    private $model;
    private $mockDb;

    protected function setUp(): void {
        $this->mockDb = $this->createMock(DataProviderInterface::class);
        $this->model = new AmortizationModel($this->mockDb);
    }

    public function testCalculatePayment() {
        $principal = 10000;
        $rate = 5.0;
        $num_payments = 12;
        $payment = $this->model->calculatePayment($principal, $rate, $num_payments);
        $this->assertIsFloat($payment);
        $this->assertGreaterThan(0, $payment);
    }

    public function testCreateLoanCallsDb() {
        $data = ['amount_financed' => 10000, 'interest_rate' => 5.0, 'num_payments' => 12];
        $this->mockDb->expects($this->once())
            ->method('insertLoan')
            ->with($data)
            ->willReturn(1);
        $loanId = $this->model->createLoan($data);
        $this->assertEquals(1, $loanId);
    }
}
