<?php

namespace Tests\Unit\HTML;

use PHPUnit\Framework\TestCase;
use Ksfraser\HTML\Elements\TableBuilder;
use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlTableHeaderCell;
use Ksfraser\HTML\Elements\HtmlTableRowCell;

/**
 * TableBuilderTest - Unit Tests for TableBuilder Utility Class
 * 
 * Tests all table building convenience methods.
 * 
 * @package    Tests\Unit\HTML
 * @author     Test Suite
 * @since      20251220
 * @version    1.0.0
 */
class TableBuilderTest extends TestCase
{
    /**
     * Test creating header row from array
     */
    public function testCreateHeaderRow()
    {
        $headers = ['ID', 'Name', 'Email', 'Actions'];
        $row = TableBuilder::createHeaderRow($headers);
        
        $this->assertInstanceOf(HtmlTableRow::class, $row);
    }

    /**
     * Test creating data row from array
     */
    public function testCreateDataRow()
    {
        $cells = ['1', 'John Doe', 'john@example.com'];
        $row = TableBuilder::createDataRow($cells);
        
        $this->assertInstanceOf(HtmlTableRow::class, $row);
    }

    /**
     * Test building header row with builder instance
     */
    public function testBuildHeaderRow()
    {
        $builder = new TableBuilder();
        $headers = ['Col1', 'Col2', 'Col3'];
        $row = $builder->buildHeaderRow($headers);
        
        $this->assertInstanceOf(HtmlTableRow::class, $row);
    }

    /**
     * Test building data row with builder instance
     */
    public function testBuildDataRow()
    {
        $builder = new TableBuilder();
        $cells = ['Value1', 'Value2', 'Value3'];
        $row = $builder->buildDataRow($cells);
        
        $this->assertInstanceOf(HtmlTableRow::class, $row);
    }

    /**
     * Test building styled header row
     */
    public function testBuildStyledHeaderRow()
    {
        $builder = new TableBuilder();
        $headers = ['Styled1', 'Styled2'];
        $attrs = ['class' => 'bg-dark'];
        $row = $builder->buildStyledHeaderRow($headers, $attrs);
        
        $this->assertInstanceOf(HtmlTableRow::class, $row);
    }

    /**
     * Test empty header array
     */
    public function testCreateHeaderRowEmpty()
    {
        $row = TableBuilder::createHeaderRow([]);
        
        $this->assertInstanceOf(HtmlTableRow::class, $row);
    }

    /**
     * Test single header
     */
    public function testCreateHeaderRowSingle()
    {
        $row = TableBuilder::createHeaderRow(['Only']);
        
        $this->assertInstanceOf(HtmlTableRow::class, $row);
    }

    /**
     * Test creating data row with special characters
     */
    public function testCreateDataRowSpecialChars()
    {
        $cells = ['ID<1>', 'Name&Co.', 'test@email.com'];
        $row = TableBuilder::createDataRow($cells);
        
        $this->assertInstanceOf(HtmlTableRow::class, $row);
    }
}
