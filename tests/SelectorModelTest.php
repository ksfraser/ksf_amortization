<?php
namespace Ksfraser\Amortizations\Tests;

use Ksfraser\Amortizations\SelectorModel;
use PHPUnit\Framework\TestCase;

class SelectorModelTest extends TestCase
{
    private $pdo;
    private $model;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(\PDO::class);
        $this->model = new SelectorModel($this->pdo);
    }

    public function testGetOptionsReturnsArray()
    {
        $stmt = $this->createMock(\stdClass::class);
        $stmt->method('fetchAll')->willReturn([
            ['option_name' => 'Monthly', 'option_value' => 'monthly'],
            ['option_name' => 'Annual', 'option_value' => 'annual']
        ]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $stmt->expects($this->once())->method('execute')->with(['payment_frequency']);
        $result = $this->model->getOptions('payment_frequency');
        $this->assertIsArray($result);
        $this->assertEquals('Monthly', $result[0]['option_name']);
        $this->assertEquals('monthly', $result[0]['option_value']);
    }

    public function testGetOptionsForBorrowerType()
    {
        $stmt = $this->createMock(\stdClass::class);
        $stmt->method('fetchAll')->willReturn([
            ['option_name' => 'Customer', 'option_value' => 'Customer'],
            ['option_name' => 'Supplier', 'option_value' => 'Supplier'],
            ['option_name' => 'Employee', 'option_value' => 'Employee']
        ]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $stmt->expects($this->once())->method('execute')->with(['borrower_type']);
        $result = $this->model->getOptions('borrower_type');
        $this->assertCount(3, $result);
        $this->assertEquals('Employee', $result[2]['option_name']);
    }
}
