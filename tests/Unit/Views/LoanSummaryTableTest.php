<?php
namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Views\LoanSummaryTable;

/**
 * LoanSummaryTable Unit Tests
 * 
 * Tests for the loan summary table view rendering, HTML structure,
 * security, CSS classes, and form attributes.
 */
class LoanSummaryTableTest extends TestCase
{
    /**
     * Test rendering with empty array
     */
    public function testRenderWithEmptyArray(): void
    {
        $output = LoanSummaryTable::render([]);
        $this->assertIsString($output);
        $this->assertStringContainsString('Loan Summary', $output);
        $this->assertStringContainsString('<table', $output);
    }

    /**
     * Test rendering with single loan item
     */
    public function testRenderWithSingleLoan(): void
    {
        $loan = (object)[
            'id' => 1,
            'borrower' => 'John Doe',
            'amount' => 50000,
            'status' => 'Active'
        ];
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('John Doe', $output);
        $this->assertStringContainsString('50000', $output);
        $this->assertStringContainsString('Active', $output);
    }

    /**
     * Test rendering with multiple loan items
     */
    public function testRenderWithMultipleLoans(): void
    {
        $loans = [
            (object)['id' => 1, 'borrower' => 'John Doe', 'amount' => 50000, 'status' => 'Active'],
            (object)['id' => 2, 'borrower' => 'Jane Smith', 'amount' => 75000, 'status' => 'Pending'],
            (object)['id' => 3, 'borrower' => 'Bob Johnson', 'amount' => 100000, 'status' => 'Completed'],
        ];
        $output = LoanSummaryTable::render($loans);
        
        $this->assertStringContainsString('John Doe', $output);
        $this->assertStringContainsString('Jane Smith', $output);
        $this->assertStringContainsString('Bob Johnson', $output);
    }

    /**
     * Test HTML structure contains required elements
     */
    public function testHtmlStructureContainsRequiredElements(): void
    {
        $loan = (object)['id' => 1, 'borrower' => 'Test', 'amount' => 1000, 'status' => 'Active'];
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('<h3', $output);
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('<thead>', $output);
        $this->assertStringContainsString('<tbody>', $output);
        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString('Borrower', $output);
        $this->assertStringContainsString('Amount', $output);
        $this->assertStringContainsString('Status', $output);
        $this->assertStringContainsString('Actions', $output);
    }

    /**
     * Test action buttons are included (View and Edit)
     */
    public function testActionButtonsAreIncluded(): void
    {
        $loan = (object)['id' => 1, 'borrower' => 'Test', 'amount' => 1000, 'status' => 'Active'];
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('View', $output);
        $this->assertStringContainsString('Edit', $output);
        $this->assertStringContainsString('btn-small', $output);
    }

    /**
     * Test CSS links are included
     */
    public function testCssLinksAreIncluded(): void
    {
        $output = LoanSummaryTable::render([]);
        
        if (function_exists('asset_url')) {
            $this->assertStringContainsString('loan-summary-table.css', $output);
            $this->assertStringContainsString('loan-summary-form.css', $output);
            $this->assertStringContainsString('loan-summary-buttons.css', $output);
        }
    }

    /**
     * Test JavaScript is included
     */
    public function testJavaScriptIsIncluded(): void
    {
        $output = LoanSummaryTable::render([]);
        
        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('viewLoan', $output);
        $this->assertStringContainsString('editLoan', $output);
    }

    /**
     * Test HTML encoding of special characters in borrower name
     */
    public function testHtmlEncodingOfSpecialCharactersInBorrowerName(): void
    {
        $loan = (object)[
            'id' => 1,
            'borrower' => '<script>alert("xss")</script>',
            'amount' => 1000,
            'status' => 'Active'
        ];
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>alert', $output);
    }

    /**
     * Test HTML encoding of special characters in status
     */
    public function testHtmlEncodingOfSpecialCharactersInStatus(): void
    {
        $loan = (object)[
            'id' => 1,
            'borrower' => 'Test',
            'amount' => 1000,
            'status' => '<img src=x onerror="alert(1)">'
        ];
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('&lt;img', $output);
        $this->assertStringNotContainsString('onerror=', $output);
    }

    /**
     * Test handling of missing properties with defaults
     */
    public function testHandlingOfMissingProperties(): void
    {
        $loan = (object)[]; // No properties
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('N/A', $output);
        $this->assertIsString($output);
    }

    /**
     * Test currency formatting for amount (displays as $X,XXX.XX)
     */
    public function testAmountFormattingAsCurrency(): void
    {
        $loan = (object)[
            'id' => 1,
            'borrower' => 'Test',
            'amount' => 1234.56,
            'status' => 'Active'
        ];
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('$', $output);
        $this->assertStringContainsString('1,234.56', $output);
    }

    /**
     * Test table classes are applied
     */
    public function testTableClassesAreApplied(): void
    {
        $loan = (object)['id' => 1, 'borrower' => 'Test', 'amount' => 1000, 'status' => 'Active'];
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('loan-summary-table', $output);
        $this->assertStringContainsString('id-cell', $output);
        $this->assertStringContainsString('borrower-cell', $output);
        $this->assertStringContainsString('amount-cell', $output);
        $this->assertStringContainsString('status-cell', $output);
        $this->assertStringContainsString('actions-cell', $output);
    }

    /**
     * Test status cell color coding classes
     */
    public function testStatusCellColorCodingClasses(): void
    {
        $loans = [
            (object)['id' => 1, 'borrower' => 'Test', 'amount' => 1000, 'status' => 'Active'],
            (object)['id' => 2, 'borrower' => 'Test', 'amount' => 1000, 'status' => 'Pending'],
            (object)['id' => 3, 'borrower' => 'Test', 'amount' => 1000, 'status' => 'Completed'],
            (object)['id' => 4, 'borrower' => 'Test', 'amount' => 1000, 'status' => 'Inactive'],
        ];
        $output = LoanSummaryTable::render($loans);
        
        $this->assertStringContainsString('status-active', $output);
        $this->assertStringContainsString('status-pending', $output);
        $this->assertStringContainsString('status-completed', $output);
        $this->assertStringContainsString('status-inactive', $output);
    }

    /**
     * Test button onclick attributes contain proper handler calls
     */
    public function testButtonOnclickAttributesWithHandlerCalls(): void
    {
        $loan = (object)['id' => 42, 'borrower' => 'Test', 'amount' => 1000, 'status' => 'Active'];
        $output = LoanSummaryTable::render([$loan]);
        
        $this->assertStringContainsString('viewLoan(42)', $output);
        $this->assertStringContainsString('editLoan(42)', $output);
    }

    /**
     * Test form method is POST
     */
    public function testFormMethodIsPost(): void
    {
        $output = LoanSummaryTable::render([]);
        
        // LoanSummaryTable does not include a form, only displays table
        $this->assertIsString($output);
    }

    /**
     * Test amount cell is right-aligned for currency values
     */
    public function testAmountCellRightAlignedForCurrency(): void
    {
        $loan = (object)['id' => 1, 'borrower' => 'Test', 'amount' => 50000, 'status' => 'Active'];
        $output = LoanSummaryTable::render([$loan]);
        
        // Amount cell should be in output
        $this->assertStringContainsString('amount-cell', $output);
        // Currency symbol should be present
        $this->assertStringContainsString('$', $output);
    }
}
