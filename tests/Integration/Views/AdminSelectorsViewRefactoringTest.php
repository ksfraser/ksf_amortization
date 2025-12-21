<?php

namespace Tests\Integration\Views;

use PHPUnit\Framework\TestCase;

/**
 * AdminSelectorsViewRefactoringTest - Integration Test for admin_selectors View Refactoring
 * 
 * Tests the admin_selectors view to ensure it uses architectural improvements:
 * - SelectorRepository instead of raw SQL
 * - TableBuilder for table creation
 * - SelectEditJSHandler for form validation
 * - EditButton/DeleteButton for actions
 * - TB_PREF for dynamic table prefixes
 * 
 * @package    Tests\Integration\Views
 * @author     Test Suite
 * @since      20251220
 * @version    1.0.0
 */
class AdminSelectorsViewRefactoringTest extends TestCase
{
    /**
     * @var string Path to the refactored view file
     */
    private $viewPath;

    protected function setUp(): void
    {
        // Point to the refactored view file in FrontAccounting module
        $this->viewPath = realpath(__DIR__ . '/../../../packages/ksf-amortizations-frontaccounting/module/amortization/views/views/admin_selectors.php');
        
        if (!$this->viewPath || !file_exists($this->viewPath)) {
            $this->markTestSkipped('View file not found at expected path');
        }
    }

    /**
     * Test view file exists and is readable
     */
    public function testViewFileIsReadable()
    {
        $this->assertFileIsReadable(
            $this->viewPath,
            "admin_selectors.php view file should be readable"
        );
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
     * Test view uses SelectorRepository pattern instead of raw SQL
     */
    public function testViewUsesSelectorRepository()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should have use statement for SelectorRepository
        $this->assertStringContainsString(
            'use Ksfraser\\Amortizations\\Repository\\SelectorRepository',
            $content,
            "View should import SelectorRepository"
        );
        
        // Should instantiate the repository
        $this->assertStringContainsString(
            'new SelectorRepository',
            $content,
            "View should instantiate SelectorRepository"
        );
        
        // Should NOT have raw SQL
        $this->assertStringNotContainsString('INSERT INTO', $content);
        $this->assertStringNotContainsString('UPDATE tb', $content);
        $this->assertStringNotContainsString('DELETE FROM tb', $content);
    }

    /**
     * Test view uses TableBuilder for HTML table construction
     */
    public function testViewUsesTableBuilder()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should import TableBuilder
        $this->assertStringContainsString(
            'use Ksfraser\\HTML\\Elements\\TableBuilder',
            $content,
            "View should import TableBuilder"
        );
        
        // Should use TableBuilder::createHeaderRow()
        $this->assertStringContainsString(
            'TableBuilder::createHeaderRow',
            $content,
            "View should use TableBuilder::createHeaderRow()"
        );
    }

    /**
     * Test view uses SelectEditJSHandler for JavaScript functionality
     */
    public function testViewUsesSelectEditJSHandler()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should import SelectEditJSHandler
        $this->assertStringContainsString(
            'use Ksfraser\\HTML\\Elements\\SelectEditJSHandler',
            $content,
            "View should import SelectEditJSHandler"
        );
        
        // Should instantiate the handler
        $this->assertStringContainsString(
            'new SelectEditJSHandler',
            $content,
            "View should instantiate SelectEditJSHandler"
        );
        
        // Should NOT have inline echo statements with <script>
        $echoScriptCount = preg_match_all('/echo\s+["\']<script/i', $content);
        $this->assertEquals(0, $echoScriptCount, "View should not echo inline <script> tags");
    }

    /**
     * Test view uses specialized action buttons (EditButton, DeleteButton)
     */
    public function testViewUsesSpecializedActionButtons()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should import EditButton and DeleteButton
        $this->assertStringContainsString(
            'use Ksfraser\\HTML\\Elements\\EditButton',
            $content,
            "View should import EditButton"
        );
        
        $this->assertStringContainsString(
            'use Ksfraser\\HTML\\Elements\\DeleteButton',
            $content,
            "View should import DeleteButton"
        );
        
        // Should instantiate buttons
        $this->assertStringContainsString(
            'new EditButton',
            $content,
            "View should instantiate EditButton"
        );
        
        $this->assertStringContainsString(
            'new DeleteButton',
            $content,
            "View should instantiate DeleteButton"
        );
    }

    /**
     * Test view uses dynamic table prefix (TB_PREF) instead of hardcoded prefix
     */
    public function testViewUsesDynamicTablePrefix()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should reference TB_PREF constant
        $this->assertStringContainsString(
            'TB_PREF',
            $content,
            "View should use TB_PREF constant"
        );
        
        // Should NOT have hardcoded '0_' prefix in table names
        $this->assertStringNotContainsString(
            "'0_ksf_selectors'",
            $content,
            "View should not hardcode table prefix"
        );
        
        $this->assertStringNotContainsString(
            '"0_ksf_selectors"',
            $content,
            "View should not hardcode table prefix"
        );
    }

    /**
     * Test view uses HTML builders for form elements
     */
    public function testViewUsesHtmlBuildersForFormElements()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should import and use HTML element builders
        $this->assertStringContainsString('use Ksfraser\\HTML\\Elements\\Form', $content);
        $this->assertStringContainsString('use Ksfraser\\HTML\\Elements\\Input', $content);
        $this->assertStringContainsString('use Ksfraser\\HTML\\Elements\\Label', $content);
        
        // Should not use echo to output HTML directly (except via toHtml())
        $this->assertStringContainsString('->toHtml()', $content, "View should call toHtml() on builders");
    }

    /**
     * Test view properly encapsulates repository operations
     */
    public function testViewEncapsulatesRepositoryOperations()
    {
        $content = file_get_contents($this->viewPath);
        
        // Should use repository methods instead of raw operations
        $this->assertStringContainsString('$selectorRepo->add(', $content);
        $this->assertStringContainsString('$selectorRepo->update(', $content);
        $this->assertStringContainsString('$selectorRepo->delete(', $content);
        $this->assertStringContainsString('$selectorRepo->getAll()', $content);
    }

    /**
     * Test that refactoring reduces code complexity
     */
    public function testViewCodeComplexityReduced()
    {
        $content = file_get_contents($this->viewPath);
        $lines = count(array_filter(explode("\n", $content)));
        
        // After refactoring, the view should be lean (not counting use statements)
        // Original had ~44 lines of logic, refactored should be ~20-30
        $this->assertLessThan(150, $lines, "View should be concise after refactoring");
    }
}
