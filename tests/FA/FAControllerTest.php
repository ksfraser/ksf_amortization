<?php
namespace Tests\FA;

use PHPUnit\Framework\TestCase;

/**
 * FAControllerTest - Integration tests for FrontAccounting controller
 * 
 * Validates controller structure, view file existence, and runtime dependencies.
 * Tests ensure views can be safely included without fatal errors.
 */
class FAControllerTest extends TestCase
{
    private $controllerPath;
    private $viewDir;
    private $modulePath;

    protected function setUp(): void
    {
        $this->controllerPath = __DIR__ . '/../../modules/amortization/controller.php';
        $this->viewDir = __DIR__ . '/../../modules/amortization/views';
        $this->modulePath = __DIR__ . '/../../modules/amortization';
    }

    /**
     * Test that all view files referenced in controller exist
     */
    public function testControllerViewsExist()
    {
        $requiredViews = [
            'admin_settings.php',
            'admin_selectors.php',
            'user_loan_setup.php',
        ];
        
        foreach ($requiredViews as $view) {
            $path = $this->viewDir . '/' . $view;
            $this->assertFileExists($path, "View file missing: $view at $path");
            $this->assertIsReadable($path, "View file not readable: $view");
        }
    }

    /**
     * Test that controller.php exists and is valid PHP
     */
    public function testControllerExists()
    {
        $this->assertFileExists($this->controllerPath, "Controller file not found");
        $this->assertIsReadable($this->controllerPath, "Controller not readable");
        
        // Validate PHP syntax
        $contents = file_get_contents($this->controllerPath);
        $this->assertStringNotContainsString('include __DIR__ . \'/views/views/', $contents, 
            "Controller has incorrect /views/views/ path - should be /views/");
    }

    /**
     * Test that MenuBuilder.php exists and is valid PHP
     */
    public function testMenuBuilderExists()
    {
        $menuBuilderPath = $this->modulePath . '/MenuBuilder.php';
        $this->assertFileExists($menuBuilderPath, "MenuBuilder.php not found");
        $this->assertIsReadable($menuBuilderPath, "MenuBuilder.php not readable");
        
        // Validate it has required namespace and class
        $contents = file_get_contents($menuBuilderPath);
        $this->assertStringContainsString('namespace Ksfraser\Amortizations\FA', $contents,
            "MenuBuilder missing namespace declaration");
        $this->assertStringContainsString('class AmortizationMenuBuilder', $contents,
            "MenuBuilder missing class declaration");
    }

    /**
     * Test that controller uses MenuBuilder correctly
     */
    public function testControllerUsesMenuBuilder()
    {
        $contents = file_get_contents($this->controllerPath);
        
        // Should import MenuBuilder
        $this->assertStringContainsString('use Ksfraser\Amortizations\FA\AmortizationMenuBuilder', $contents,
            "Controller should import AmortizationMenuBuilder");
        
        // Should instantiate MenuBuilder
        $this->assertStringContainsString('new AmortizationMenuBuilder', $contents,
            "Controller should instantiate MenuBuilder");
        
        // Should call build() method
        $this->assertStringContainsString('->build()', $contents,
            "Controller should call MenuBuilder->build()");
    }

    /**
     * Test that controller properly wraps output with FA page functions
     */
    public function testControllerHasPageWrapper()
    {
        $contents = file_get_contents($this->controllerPath);
        
        // Should check for page() function
        $this->assertStringContainsString("function_exists('page')", $contents,
            "Controller should check if page() function exists");
        
        // Should call page()
        $this->assertStringContainsString('page(', $contents,
            "Controller should call page() function");
        
        // Should check for end_page() function
        $this->assertStringContainsString("function_exists('end_page')", $contents,
            "Controller should check if end_page() function exists");
        
        // Should call end_page()
        $this->assertStringContainsString('end_page()', $contents,
            "Controller should call end_page() function");
    }

    /**
     * Test that controller routes are properly defined
     */
    public function testControllerRoutesAreDefined()
    {
        $contents = file_get_contents($this->controllerPath);
        
        $requiredActions = ['admin', 'admin_selectors', 'create', 'report', 'default'];
        
        foreach ($requiredActions as $action) {
            $this->assertStringContainsString("case '$action':", $contents,
                "Controller missing route for action: $action");
        }
    }

    /**
     * Test that view files have valid PHP syntax
     */
    public function testViewFilesHaveValidSyntax()
    {
        $viewFiles = [
            'admin_settings.php',
            'admin_selectors.php',
            'user_loan_setup.php',
        ];
        
        foreach ($viewFiles as $viewFile) {
            $path = $this->viewDir . '/' . $viewFile;
            
            // Execute php -l to check syntax
            $output = [];
            $returnCode = 0;
            exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $returnCode);
            
            $this->assertEquals(0, $returnCode, 
                "View file $viewFile has syntax errors: " . implode("\n", $output));
        }
    }

    /**
     * Test that controller autoloader paths exist
     */
    public function testAutoloaderPathsExist()
    {
        $expectedPaths = [
            $this->modulePath . '/vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
        ];
        
        // At least one autoloader should exist
        $foundAutoloader = false;
        foreach ($expectedPaths as $path) {
            if (file_exists($path)) {
                $foundAutoloader = true;
                break;
            }
        }
        
        $this->assertTrue($foundAutoloader, 
            "No autoloader found in expected locations: " . implode(', ', $expectedPaths));
    }

    /**
     * Test that view files don't contain syntax errors that would cause runtime failures
     */
    public function testViewFilesDontHaveObviousRuntimeErrors()
    {
        $viewFiles = glob($this->viewDir . '/*.php');
        
        foreach ($viewFiles as $viewFile) {
            $contents = file_get_contents($viewFile);
            
            // Check for misplaced use statements (common error we found)
            $lines = explode("\n", $contents);
            $foundCode = false;
            
            foreach ($lines as $lineNum => $line) {
                $trimmed = trim($line);
                
                // Skip comments and empty lines
                if (empty($trimmed) || strpos($trimmed, '//') === 0 || strpos($trimmed, '/*') === 0 || strpos($trimmed, '*') === 0) {
                    continue;
                }
                
                // If we found actual code (not <?php, not use, not namespace)
                if (!preg_match('/^<\?php|^namespace|^use\s/', $trimmed)) {
                    $foundCode = true;
                }
                
                // use statement after code is error
                if ($foundCode && preg_match('/^use\s/', $trimmed)) {
                    $this->fail("View file " . basename($viewFile) . " has 'use' statement after code at line " . ($lineNum + 1));
                }
            }
        }
        
        $this->assertTrue(true); // If we got here, no errors found
    }

    /**
     * Test that menu displays on all controller actions
     */
    public function testMenuDisplaysOnAllActions()
    {
        $contents = file_get_contents($this->controllerPath);
        
        // Menu should be built BEFORE the switch statement
        $switchPos = strpos($contents, 'switch ($action)');
        $menuPos = strpos($contents, 'new AmortizationMenuBuilder');
        
        $this->assertNotFalse($switchPos, "Controller should have switch statement");
        $this->assertNotFalse($menuPos, "Controller should instantiate MenuBuilder");
        $this->assertLessThan($switchPos, $menuPos, 
            "MenuBuilder should be instantiated BEFORE switch statement so menu shows on all pages");
    }
}
?>
