<?php
namespace Ksfraser\Amortizations\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\WordPress\WPDataProvider;

/**
 * Unit tests for WPDataProvider
 */
class WPDataProviderTest extends TestCase
{
    protected $wpdbMock;
    protected $provider;

    protected function setUp(): void
    {
        $this->wpdbMock = $this->getMockBuilder('stdClass')->setMethods(['get_row', 'insert', 'prepare'])->getMock();
        $this->provider = new WPDataProvider($this->wpdbMock);
    }

    public function testGetLoanReturnsArray()
    {
        $this->wpdbMock->method('prepare')->willReturn('SQL');
        $this->wpdbMock->method('get_row')->willReturn([
            'id' => 1,
            'principal' => 1000,
            'borrower_type' => 'Customer'
        ]);
        $result = $this->provider->getLoan(1);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Customer', $result['borrower_type']);
    }

    public function testInsertLoanReturnsId()
    {
        $this->wpdbMock->method('insert')->willReturn(true);
        $this->wpdbMock->insert_id = 42;
        $result = $this->provider->insertLoan([
            'principal' => 1000,
            'borrower_type' => 'Supplier'
        ]);
        $this->assertEquals(42, $result);
    }

    public function testInsertScheduleInsertsRow()
    {
        $this->wpdbMock->method('insert')->willReturn(true);
        $this->provider->insertSchedule(1, ['amount' => 100]);
        $this->assertTrue(true); // If no exception, pass
    }
}
