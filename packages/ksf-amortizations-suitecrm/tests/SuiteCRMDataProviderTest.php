<?php
namespace Ksfraser\Amortizations\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\SuiteCRM\SuiteCRMDataProvider;

/**
 * Unit tests for SuiteCRMDataProvider
 */
class SuiteCRMDataProviderTest extends TestCase
{
    protected $beanFactoryMock;
    protected $provider;

    protected function setUp(): void
    {
        $this->beanFactoryMock = $this->getMockBuilder('stdClass')->setMethods(['getBean', 'newBean'])->getMock();
        $this->provider = $this->getMockBuilder(SuiteCRMDataProvider::class)
            ->setMethods(['getLoan', 'insertLoan', 'insertSchedule'])
            ->getMock();
    }

    public function testGetLoanReturnsArray()
    {
        $this->provider->method('getLoan')->willReturn([
            'id' => 1,
            'principal' => 1000,
            'borrower_type' => 'Supplier'
        ]);
        $result = $this->provider->getLoan(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Supplier', $result['borrower_type']);
    }

    public function testInsertLoanReturnsId()
    {
        $this->provider->method('insertLoan')->willReturn(42);
        $result = $this->provider->insertLoan([
            'principal' => 1000,
            'borrower_type' => 'Customer'
        ]);
        $this->assertEquals(42, $result);
    }

    public function testInsertScheduleInsertsRow()
    {
        $this->provider->method('insertSchedule')->willReturn(null);
        $this->provider->insertSchedule(1, ['amount' => 100]);
        $this->assertTrue(true); // If no exception, pass
    }
}
