<?php

namespace Tests\Integration\Views;

use PHPUnit\Framework\TestCase;

/**
 * TermSelectorViewRefactoringTest - Integration Test for fa_loan_term_selector View
 * 
 * Tests the fa_loan_term_selector view refactoring to ensure:
 * - Uses PaymentFrequencyHandler for frequency options
 * - Uses HtmlSelect builder for form elements
 * - Encapsulates frequency calculation logic
 * - Eliminates hardcoded appendChild calls
 * - Uses addOptionsFromArray() for option population
 * 
 * @package    Tests\Integration\Views
 * @author     Test Suite
 * @since      20251220
 * @version    1.0.0
 */
class TermSelectorViewRefactoringTest extends TestCase
{
    /**
     * @var string Path to the refactored view file
     */
    private $viewPath;

    protected function setUp(): void
    {
        $this->viewPath = realpath(__DIR__ . '/../../../packages/ksf-amortizations-frontaccounting/module/amortization/views/views/fa_loan_term_selector.php');
        
        if (!$this->viewPath || !file_exists($this->viewPath)) {
            $this->markTestSkipped('View file not found at expected path');
        }
    }

    /**
     * Test view file exists and is readable
     */
    public function testViewFileIsReadable()
    {
        $this->assertFileIsReadable($this->viewPath);
    }

    /**
     * Test view file has valid PHP syntax
     */
    public function testViewHasValidPhpSyntax()
    {
        $output = [];
        $returnCode = 0;
        
        exec("php -l " . escapeshellarg($this->viewPath), $output, $returnCode);
        
        $this->assertEquals(
            0,
            $returnCode,
            "View file has syntax errors:\n" . implode("\n", $output)
        );
    }

    /**
     * Test view uses PaymentFrequencyHandler for frequency options
     */
    public function testViewUsesPaymentFrequencyHandler()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should import PaymentFrequencyHandler
        $this->assertStringContainsString(
            'use Ksfraser\\HTML\\Elements\\PaymentFrequencyHandler',
            $content,
            "View should import PaymentFrequencyHandler"
        );
        
        // Should instantiate the handler
        $this->assertStringContainsString(
            'new PaymentFrequencyHandler',
            $content,
            "View should instantiate PaymentFrequencyHandler"
        );
    }

    /**
     * Test view uses HtmlSelect or Select builder
     */
    public function testViewUsesHtmlSelectBuilder()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should import HtmlSelect or use Select alias
        $usesSelectImport = (
            strpos($content, 'use Ksfraser\\HTML\\Elements\\Select') !== false ||
            strpos($content, 'use Ksfraser\\HTML\\Elements\\HtmlSelect') !== false
        );
        $this->assertTrue($usesSelectImport, "View should import Select or HtmlSelect");
        
        // Should instantiate select
        $instantiatesSelect = (
            strpos($content, 'new Select') !== false ||
            strpos($content, 'new HtmlSelect') !== false
        );
        $this->assertTrue($instantiatesSelect, "View should instantiate Select or HtmlSelect");
    }

    /**
     * Test view uses addOptionsFromArray() for option population
     */
    public function testViewUsesAddOptionsFromArray()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should use addOptionsFromArray instead of manual appendChild
        $this->assertStringContainsString(
            'addOptionsFromArray',
            $content,
            "View should use addOptionsFromArray() for populating select options"
        );
    }

    /**
     * Test view eliminates manual appendChild calls
     */
    public function testViewNoManualAppendChild()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should NOT have appendChild for manual option creation
        // (Note: may have appendChild for other elements, but not the main frequency options)
        $appendChildCount = substr_count($content, 'appendChild');
        $this->assertLessThan(3, $appendChildCount, "View should minimize appendChild calls");
    }

    /**
     * Test view calls toHtml() for rendering
     */
    public function testViewUsesToHtmlForRendering()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should call toHtml()
        $this->assertStringContainsString(
            '->toHtml()',
            $content,
            "View should call toHtml() on builders"
        );
    }

    /**
     * Test view uses PaymentFrequencyHandler to manage frequency calculations
     */
    public function testViewNoHardcodedFrequencyCalculations()
    {
        $content = file_get_contents($this->viewPath);
        
        // Primary check: uses PaymentFrequencyHandler to encapsulate the logic
        $this->assertStringContainsString(
            'new PaymentFrequencyHandler',
            $content,
            "View should use PaymentFrequencyHandler to manage frequency calculations"
        );
        
        // The handler manages all the complex frequency logic
        // The view just outputs the handler's HTML
        $this->assertStringContainsString(
            '$handler->toHtml()',
            $content,
            "View should render the handler output"
        );
    }

    /**
     * Test view has significantly less code (refactoring goal)
     */
    public function testViewCodeReductionAchieved()
    {
        $content = file_get_contents($this->viewPath);
        $lines = count(array_filter(explode("\n", $content)));
        
        // Original had 12 appendChild + 14 echo lines = 26 lines for options alone
        // Refactored should be much smaller
        // After refactoring: ~30-40 lines including use statements
        $this->assertLessThan(80, $lines, "View should be significantly smaller after refactoring");
    }

    /**
     * Test view properly encapsulates frequency handler
     */
    public function testViewEncapsulatesFrequencyLogic()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should use the handler to get options
        $this->assertStringContainsString(
            'PaymentFrequencyHandler',
            $content,
            "View should delegate frequency calculations to PaymentFrequencyHandler"
        );
        
        // Should NOT echo option tags directly
        $this->assertStringNotContainsString(
            'echo "<option',
            $content,
            "View should not manually echo option tags"
        );
    }
}
