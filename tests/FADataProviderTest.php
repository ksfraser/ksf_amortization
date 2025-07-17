<?php
namespace Ksfraser\Amortizations\Tests;

use Ksfraser\Amortizations\FADataProvider;
use Ksfraser\Amortizations\DataProviderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FADataProvider
 * @covers Ksfraser\Amortizations\FADataProvider
 */
class FADataProviderTest extends TestCase {
    private $pdo;
    private $provider;

    protected function setUp(): void {
        $this->pdo = $this->createMock(\PDO::class);
        $this->provider = new FADataProvider($this->pdo);
    }

    public function testInsertLoanReturnsInt() {
        $this->pdo->method('prepare')->willReturn($this->getMockBuilder('stdClass')->addMethods(['execute'])->getMock());
        $data = ['loan_type' => 'Auto', 'description' => 'Test', 'principal' => 10000, 'interest_rate' => 5.0, 'term_months' => 12, 'repayment_schedule' => 'monthly', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'created_by' => 1, 'amount_financed' => 10000, 'payment_frequency' => 'monthly', 'interest_calc_frequency' => 'monthly', 'num_payments' => 12, 'regular_payment' => 856.07, 'first_payment_date' => '2025-01-01', 'last_payment_date' => '2025-12-31', 'override_payment' => 0];
        $this->pdo->method('lastInsertId')->willReturn(1);
        $result = $this->provider->insertLoan($data);
        $this->assertEquals(1, $result);
    }
}
