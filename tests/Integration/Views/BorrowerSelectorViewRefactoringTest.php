<?php

namespace Tests\Integration\Views;

use PHPUnit\Framework\TestCase;

/**
 * BorrowerSelectorViewRefactoringTest - Integration Test for fa_loan_borrower_selector View
 * 
 * Tests the fa_loan_borrower_selector view refactoring to ensure:
 * - Uses AjaxSelectPopulator for AJAX-driven select population
 * - Uses HtmlSelect builder for form elements
 * - Eliminates hardcoded jQuery AJAX code
 * - Proper encapsulation of AJAX logic
 * 
 * @package    Tests\Integration\Views
 * @author     Test Suite
 * @since      20251220
 * @version    1.0.0
 */
class BorrowerSelectorViewRefactoringTest extends TestCase
{
    /**
     * @var string Path to the refactored view file
     */
    private $viewPath;

    protected function setUp(): void
    {
        $this->viewPath = realpath(__DIR__ . '/../../../packages/ksf-amortizations-frontaccounting/module/amortization/views/views/fa_loan_borrower_selector.php');
        
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
     * Test view uses AjaxSelectPopulator instead of manual AJAX code
     */
    public function testViewUsesAjaxSelectPopulator()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should import AjaxSelectPopulator
        $this->assertStringContainsString(
            'use Ksfraser\\HTML\\Elements\\AjaxSelectPopulator',
            $content,
            "View should import AjaxSelectPopulator"
        );
        
        // Should instantiate the populator
        $this->assertStringContainsString(
            'new AjaxSelectPopulator',
            $content,
            "View should instantiate AjaxSelectPopulator"
        );
    }

    /**
     * Test view eliminates hardcoded jQuery AJAX calls
     */
    public function testViewNoHardcodedAjaxCalls()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should NOT have $.ajax() calls
        $this->assertStringNotContainsString('$.ajax', $content);
        
        // Should NOT have manual event handlers
        $ajaxOnChangeCount = preg_match_all('/\.on\s*\(\s*["\']change["\']/', $content);
        $this->assertLessThan(2, $ajaxOnChangeCount, "View should not manually bind change events");
    }

    /**
     * Test view uses HtmlSelect or Select builder
     */
    public function testViewUsesHtmlSelectBuilder()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should import HtmlSelect OR Select (either is valid)
        $importsSelect = (
            strpos($content, 'use Ksfraser\\HTML\\Elements\\HtmlSelect') !== false ||
            strpos($content, 'use Ksfraser\\HTML\\Elements\\Select') !== false
        );
        $this->assertTrue($importsSelect, "View should import HtmlSelect or Select");
        
        // Should instantiate select
        $usesSelect = (strpos($content, 'new HtmlSelect') !== false || strpos($content, 'new Select') !== false);
        $this->assertTrue($usesSelect, "View should use HtmlSelect or Select builder");
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
     * Test view has significantly less code (refactoring goal)
     */
    public function testViewCodeReductionAchieved()
    {
        $content = file_get_contents($this->viewPath);
        $lines = count(array_filter(explode("\n", $content)));
        
        // Original had 21+ echo lines for AJAX, refactored should be much cleaner
        // After refactoring: ~40-50 lines including use statements
        $this->assertLessThan(100, $lines, "View should be significantly smaller after refactoring");
    }
}
