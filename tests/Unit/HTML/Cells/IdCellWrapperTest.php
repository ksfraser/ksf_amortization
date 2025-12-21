<?php
namespace Tests\Unit\HTML\Cells;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Cells\IdLoanTableCell;
use Ksfraser\HTML\Cells\IdFrequencyTableCell;
use Ksfraser\HTML\Cells\IdTypeTableCell;
use Ksfraser\HTML\Elements\TableData;

class IdCellWrapperTest extends TestCase {
    
    public function testIdLoanTableCellBuildsCorrectly() {
        $cell = new IdLoanTableCell();
        $result = $cell->build(123, 'loan-123');
        
        $this->assertInstanceOf(TableData::class, $result);
        $html = $result->render();
        
        // Check cell ID
        $this->assertStringContainsString('id="id-cell-loan-123"', $html);
        // Check data attribute
        $this->assertStringContainsString('data-loan-id="123"', $html);
        // Check content
        $this->assertStringContainsString('>123<', $html);
    }
    
    public function testIdFrequencyTableCellBuildsCorrectly() {
        $cell = new IdFrequencyTableCell();
        $result = $cell->build(456, 'freq-456');
        
        $this->assertInstanceOf(TableData::class, $result);
        $html = $result->render();
        
        // Check cell ID
        $this->assertStringContainsString('id="id-cell-freq-456"', $html);
        // Check data attribute
        $this->assertStringContainsString('data-frequency-id="456"', $html);
        // Check content
        $this->assertStringContainsString('>456<', $html);
    }
    
    public function testIdTypeTableCellBuildsCorrectly() {
        $cell = new IdTypeTableCell();
        $result = $cell->build(789, 'type-789');
        
        $this->assertInstanceOf(TableData::class, $result);
        $html = $result->render();
        
        // Check cell ID
        $this->assertStringContainsString('id="id-cell-type-789"', $html);
        // Check data attribute
        $this->assertStringContainsString('data-type-id="789"', $html);
        // Check content
        $this->assertStringContainsString('>789<', $html);
    }
    
    public function testIdLoanTableCellWithStringId() {
        $cell = new IdLoanTableCell();
        $result = $cell->build('loan-abc-123', 'loan-abc-123');
        
        $html = $result->render();
        
        $this->assertStringContainsString('data-loan-id="loan-abc-123"', $html);
        $this->assertStringContainsString('>loan-abc-123<', $html);
    }
    
    public function testIdFrequencyTableCellWithStringId() {
        $cell = new IdFrequencyTableCell();
        $result = $cell->build('freq-001', 'freq-001');
        
        $html = $result->render();
        
        $this->assertStringContainsString('data-frequency-id="freq-001"', $html);
        $this->assertStringContainsString('>freq-001<', $html);
    }
    
    public function testIdTypeTableCellWithStringId() {
        $cell = new IdTypeTableCell();
        $result = $cell->build('type-standard', 'type-standard');
        
        $html = $result->render();
        
        $this->assertStringContainsString('data-type-id="type-standard"', $html);
        $this->assertStringContainsString('>type-standard<', $html);
    }
}
