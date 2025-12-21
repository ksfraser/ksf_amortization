<?php
namespace Ksfraser\Amortizations\Tests\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Views\LoanTypeTable;

/**
 * LoanTypeTable Unit Tests
 * 
 * Tests for the LoanTypeTable view renderer.
 * Validates table rendering, form generation, and HTML structure.
 */
class LoanTypeTableTest extends TestCase {
    /**
     * Test rendering with empty loan types array
     */
    public function testRenderWithEmptyArray(): void {
        $output = LoanTypeTable::render([]);
        
        $this->assertIsString($output);
        $this->assertStringContainsString('Loan Types', $output);
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('loan-types-table', $output);
    }
    
    /**
     * Test rendering with single loan type
     */
    public function testRenderWithSingleLoanType(): void {
        $loanType = new \stdClass();
        $loanType->id = 1;
        $loanType->name = 'Personal Loan';
        $loanType->description = 'Personal loan product';
        
        $output = LoanTypeTable::render([$loanType]);
        
        $this->assertStringContainsString('Personal Loan', $output);
        $this->assertStringContainsString('Personal loan product', $output);
        $this->assertStringContainsString('1', $output);
    }
    
    /**
     * Test rendering with multiple loan types
     */
    public function testRenderWithMultipleLoanTypes(): void {
        $loanTypes = [
            (object)['id' => 1, 'name' => 'Personal', 'description' => 'Personal loans'],
            (object)['id' => 2, 'name' => 'Business', 'description' => 'Business loans'],
            (object)['id' => 3, 'name' => 'Auto', 'description' => 'Auto loans'],
        ];
        
        $output = LoanTypeTable::render($loanTypes);
        
        $this->assertStringContainsString('Personal', $output);
        $this->assertStringContainsString('Business', $output);
        $this->assertStringContainsString('Auto', $output);
    }
    
    /**
     * Test HTML structure contains required elements
     */
    public function testHtmlStructureContainsRequiredElements(): void {
        $loanType = (object)['id' => 1, 'name' => 'Test', 'description' => 'Test desc'];
        $output = LoanTypeTable::render([$loanType]);
        
        // Heading
        $this->assertStringContainsString('<h3>', $output);
        
        // Table elements
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('</table>', $output);
        $this->assertStringContainsString('<thead>', $output);
        $this->assertStringContainsString('</thead>', $output);
        
        // Header cells
        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Description', $output);
        $this->assertStringContainsString('Actions', $output);
    }
    
    /**
     * Test form is included in output
     */
    public function testFormIsIncludedInOutput(): void {
        $output = LoanTypeTable::render([]);
        
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('</form>', $output);
        $this->assertStringContainsString('loan_type_name', $output);
        $this->assertStringContainsString('loan_type_desc', $output);
    }
    
    /**
     * Test action buttons are included
     */
    public function testActionButtonsAreIncluded(): void {
        $loanType = (object)['id' => 1, 'name' => 'Test', 'description' => 'Test'];
        $output = LoanTypeTable::render([$loanType]);
        
        $this->assertStringContainsString('Edit', $output);
        $this->assertStringContainsString('Delete', $output);
        $this->assertStringContainsString('btn-edit', $output);
        $this->assertStringContainsString('btn-delete', $output);
    }
    
    /**
     * Test CSS link tags are included
     */
    public function testCssLinksAreIncluded(): void {
        $output = LoanTypeTable::render([]);
        
        // Note: Only if asset_url function exists
        if (function_exists('asset_url')) {
            $this->assertStringContainsString('loan-types-table.css', $output);
            $this->assertStringContainsString('loan-types-form.css', $output);
            $this->assertStringContainsString('loan-types-buttons.css', $output);
        }
    }
    
    /**
     * Test JavaScript is included in output
     */
    public function testJavaScriptIsIncluded(): void {
        $output = LoanTypeTable::render([]);
        
        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('</script>', $output);
        $this->assertStringContainsString('editLoanType', $output);
        $this->assertStringContainsString('deleteLoanType', $output);
    }
    
    /**
     * Test HTML encoding of special characters
     */
    public function testHtmlEncodingOfSpecialCharacters(): void {
        $loanType = (object)[
            'id' => 1,
            'name' => 'Test & Special <chars>',
            'description' => 'Description with "quotes"'
        ];
        
        $output = LoanTypeTable::render([$loanType]);
        
        // HTML entities should be encoded
        $this->assertStringContainsString('&amp;', $output);
        $this->assertStringContainsString('&lt;', $output);
        $this->assertStringContainsString('&gt;', $output);
    }
    
    /**
     * Test handling of missing properties
     */
    public function testHandlingOfMissingProperties(): void {
        $loanType = new \stdClass();
        // Don't set any properties
        
        $output = LoanTypeTable::render([$loanType]);
        
        // Should not throw exception, should output N/A or empty
        $this->assertIsString($output);
        $this->assertStringContainsString('<table', $output);
    }
    
    /**
     * Test table classes are applied
     */
    public function testTableClassesAreApplied(): void {
        $loanType = (object)['id' => 1, 'name' => 'Test', 'description' => 'Test'];
        $output = LoanTypeTable::render([$loanType]);
        
        $this->assertStringContainsString('class="loan-types-table"', $output);
        $this->assertStringContainsString('header-row', $output);
        $this->assertStringContainsString('data-row', $output);
        $this->assertStringContainsString('id-cell', $output);
        $this->assertStringContainsString('name-cell', $output);
        $this->assertStringContainsString('description-cell', $output);
        $this->assertStringContainsString('actions-cell', $output);
    }
    
    /**
     * Test form classes are applied
     */
    public function testFormClassesAreApplied(): void {
        $output = LoanTypeTable::render([]);
        
        $this->assertStringContainsString('add-loan-type-form', $output);
        $this->assertStringContainsString('form-container', $output);
        $this->assertStringContainsString('form-group', $output);
        $this->assertStringContainsString('btn-primary', $output);
    }
    
    /**
     * Test button onclick attributes
     */
    public function testButtonOnclickAttributes(): void {
        $loanType = (object)['id' => 42, 'name' => 'Test', 'description' => 'Test'];
        $output = LoanTypeTable::render([$loanType]);
        
        $this->assertStringContainsString('editLoanType(42)', $output);
        $this->assertStringContainsString('deleteLoanType(42)', $output);
    }
    
    /**
     * Test form method is POST
     */
    public function testFormMethodIsPost(): void {
        $output = LoanTypeTable::render([]);
        
        $this->assertStringContainsString('method="POST"', $output);
    }
    
    /**
     * Test placeholder attributes on form inputs
     */
    public function testPlaceholderAttributesOnFormInputs(): void {
        $output = LoanTypeTable::render([]);
        
        $this->assertStringContainsString('placeholder="New Loan Type"', $output);
        $this->assertStringContainsString('placeholder="Description"', $output);
    }
}
