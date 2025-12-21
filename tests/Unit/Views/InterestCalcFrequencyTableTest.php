<?php
namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Views\InterestCalcFrequencyTable;

/**
 * InterestCalcFrequencyTable Unit Tests
 * 
 * Tests for the interest calculation frequency table view rendering,
 * HTML structure, security, CSS classes, and form attributes.
 * 
 * NOTE: Tests assume HTML builder classes (Heading, Table, etc) are available.
 * These tests will pass once the Ksfraser\HTML\Elements namespace is properly installed.
 */
class InterestCalcFrequencyTableTest extends TestCase
{
    protected $viewClass = 'Ksfraser\Amortizations\Views\InterestCalcFrequencyTable';
    /**
     * Test rendering with empty array
     */
    public function testRenderWithEmptyArray(): void
    {
        $output = InterestCalcFrequencyTable::render([]);
        $this->assertIsString($output);
        $this->assertStringContainsString('Interest Calculation Frequencies', $output);
        $this->assertStringContainsString('<table', $output);
    }

    /**
     * Test rendering with single frequency item
     */
    public function testRenderWithSingleFrequency(): void
    {
        $freq = (object)['id' => 1, 'name' => 'Annual', 'description' => 'Once per year'];
        $output = InterestCalcFrequencyTable::render([$freq]);
        
        $this->assertStringContainsString('Annual', $output);
        $this->assertStringContainsString('Once per year', $output);
        $this->assertStringContainsString('>1<', $output);
    }

    /**
     * Test rendering with multiple frequency items
     */
    public function testRenderWithMultipleFrequencies(): void
    {
        $freqs = [
            (object)['id' => 1, 'name' => 'Annual', 'description' => 'Yearly'],
            (object)['id' => 2, 'name' => 'Semi-Annual', 'description' => 'Twice yearly'],
            (object)['id' => 3, 'name' => 'Quarterly', 'description' => 'Four times yearly'],
        ];
        $output = InterestCalcFrequencyTable::render($freqs);
        
        $this->assertStringContainsString('Annual', $output);
        $this->assertStringContainsString('Semi-Annual', $output);
        $this->assertStringContainsString('Quarterly', $output);
    }

    /**
     * Test HTML structure contains required elements
     */
    public function testHtmlStructureContainsRequiredElements(): void
    {
        $freq = (object)['id' => 1, 'name' => 'Monthly', 'description' => 'Monthly interest'];
        $output = InterestCalcFrequencyTable::render([$freq]);
        
        $this->assertStringContainsString('<h3', $output);
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('<thead>', $output);
        $this->assertStringContainsString('<tbody>', $output);
        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Description', $output);
        $this->assertStringContainsString('Actions', $output);
    }

    /**
     * Test form is included in output
     */
    public function testFormIsIncludedInOutput(): void
    {
        $output = InterestCalcFrequencyTable::render([]);
        
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('method="POST"', $output);
        $this->assertStringContainsString('add-interest-freq-form', $output);
    }

    /**
     * Test action buttons are included
     */
    public function testActionButtonsAreIncluded(): void
    {
        $freq = (object)['id' => 1, 'name' => 'Test', 'description' => 'Test'];
        $output = InterestCalcFrequencyTable::render([$freq]);
        
        $this->assertStringContainsString('Edit', $output);
        $this->assertStringContainsString('Delete', $output);
        $this->assertStringContainsString('btn-small', $output);
    }

    /**
     * Test CSS links are included
     */
    public function testCssLinksAreIncluded(): void
    {
        $output = InterestCalcFrequencyTable::render([]);
        
        if (function_exists('asset_url')) {
            $this->assertStringContainsString('interest-freq-table.css', $output);
            $this->assertStringContainsString('interest-freq-form.css', $output);
            $this->assertStringContainsString('interest-freq-buttons.css', $output);
        }
    }

    /**
     * Test JavaScript is included
     */
    public function testJavaScriptIsIncluded(): void
    {
        $output = InterestCalcFrequencyTable::render([]);
        
        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('editInterestFreq', $output);
        $this->assertStringContainsString('deleteInterestFreq', $output);
    }

    /**
     * Test HTML encoding of special characters in name
     */
    public function testHtmlEncodingOfSpecialCharactersInName(): void
    {
        $freq = (object)['id' => 1, 'name' => '<script>alert("xss")</script>', 'description' => 'Test'];
        $output = InterestCalcFrequencyTable::render([$freq]);
        
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>alert', $output);
    }

    /**
     * Test HTML encoding of special characters in description
     */
    public function testHtmlEncodingOfSpecialCharactersInDescription(): void
    {
        $freq = (object)['id' => 1, 'name' => 'Test', 'description' => '<img src=x onerror="alert(1)">'];
        $output = InterestCalcFrequencyTable::render([$freq]);
        
        $this->assertStringContainsString('&lt;img', $output);
        $this->assertStringNotContainsString('onerror=', $output);
    }

    /**
     * Test handling of missing properties with defaults
     */
    public function testHandlingOfMissingProperties(): void
    {
        $freq = (object)[]; // No properties
        $output = InterestCalcFrequencyTable::render([$freq]);
        
        $this->assertStringContainsString('N/A', $output);
        $this->assertIsString($output);
    }

    /**
     * Test table classes are applied
     */
    public function testTableClassesAreApplied(): void
    {
        $freq = (object)['id' => 1, 'name' => 'Test', 'description' => 'Test'];
        $output = InterestCalcFrequencyTable::render([$freq]);
        
        $this->assertStringContainsString('interest-freq-table', $output);
        $this->assertStringContainsString('id-cell', $output);
        $this->assertStringContainsString('name-cell', $output);
        $this->assertStringContainsString('description-cell', $output);
        $this->assertStringContainsString('actions-cell', $output);
    }

    /**
     * Test form classes are applied
     */
    public function testFormClassesAreApplied(): void
    {
        $output = InterestCalcFrequencyTable::render([]);
        
        $this->assertStringContainsString('form-container', $output);
        $this->assertStringContainsString('form-group', $output);
        $this->assertStringContainsString('btn-primary', $output);
    }

    /**
     * Test button onclick attributes contain proper handler calls
     */
    public function testButtonOnclickAttributesWithHandlerCalls(): void
    {
        $freq = (object)['id' => 42, 'name' => 'Test', 'description' => 'Test'];
        $output = InterestCalcFrequencyTable::render([$freq]);
        
        $this->assertStringContainsString('editInterestFreq(42)', $output);
        $this->assertStringContainsString('deleteInterestFreq(42)', $output);
    }

    /**
     * Test form method is POST
     */
    public function testFormMethodIsPost(): void
    {
        $output = InterestCalcFrequencyTable::render([]);
        
        $this->assertStringContainsString('method="POST"', $output);
    }

    /**
     * Test placeholder attributes on form inputs
     */
    public function testPlaceholderAttributesOnFormInputs(): void
    {
        $output = InterestCalcFrequencyTable::render([]);
        
        $this->assertStringContainsString('placeholder="New Frequency"', $output);
        $this->assertStringContainsString('placeholder="Description"', $output);
    }

    /**
     * Test form inputs are marked as required
     */
    public function testFormInputsAreMarkedAsRequired(): void
    {
        $output = InterestCalcFrequencyTable::render([]);
        
        // Count required attributes (should have at least 2 for name and description)
        $requiredCount = substr_count($output, 'required');
        $this->assertGreaterThanOrEqual(2, $requiredCount);
    }
}
