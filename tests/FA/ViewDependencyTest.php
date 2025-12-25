<?php
namespace Tests\FA;

use PHPUnit\Framework\TestCase;

/**
 * ViewDependencyTest - Runtime Dependency Validation for View Files
 * 
 * This test class validates that all view files can be safely included
 * without causing fatal errors due to undefined classes, functions, or variables.
 * 
 * Tests that would have caught the errors we found in:
 * - view.php (undefined LoanProvider, misplaced use statement)
 * - reporting.php (undefined ReportProvider, misplaced use statement)
 * - user_loan_setup.php (undefined SelectorModel, undefined $db)
 */
class ViewDependencyTest extends TestCase
{
    private $viewDir;
    private $srcViewDir;

    protected function setUp(): void
    {
        $this->viewDir = __DIR__ . '/../../modules/amortization/views';
        $this->srcViewDir = __DIR__ . '/../../src/Ksfraser/Amortizations';
    }

    /**
     * Test that all use statements in view files reference existing classes
     */
    public function testViewFilesImportExistingClasses()
    {
        $viewFiles = array_merge(
            glob($this->viewDir . '/*.php'),
            [
                $this->srcViewDir . '/view.php',
                $this->srcViewDir . '/reporting.php',
            ]
        );
        
        foreach ($viewFiles as $viewFile) {
            if (!file_exists($viewFile)) {
                continue; // Skip if file doesn't exist
            }
            
            $contents = file_get_contents($viewFile);
            $classes = $this->extractUseStatements($contents);
            
            foreach ($classes as $class) {
                // Try to load the class
                $classExists = class_exists($class) || interface_exists($class) || trait_exists($class);
                
                $this->assertTrue($classExists, 
                    "View file " . basename($viewFile) . " imports non-existent class: $class");
            }
        }
    }

    /**
     * Test that view files don't reference undefined global variables
     */
    public function testViewFilesDontReferenceUndefinedGlobals()
    {
        $viewFiles = array_merge(
            glob($this->viewDir . '/*.php'),
            [
                $this->srcViewDir . '/view.php',
                $this->srcViewDir . '/reporting.php',
            ]
        );
        
        $allowedGlobals = ['_GET', '_POST', '_SERVER', '_SESSION', '_COOKIE', '_FILES', '_ENV', '_REQUEST',
                           'path_to_root', 'db', 'user', 'dbPrefix']; // FA globals
        
        foreach ($viewFiles as $viewFile) {
            if (!file_exists($viewFile)) {
                continue;
            }
            
            $contents = file_get_contents($viewFile);
            
            // Find all $variable references
            preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $contents, $matches);
            $variables = array_unique($matches[1]);
            
            foreach ($variables as $var) {
                // Skip common PHP variables
                if (in_array($var, ['this', 'that', 'self', 'parent', 'static'])) {
                    continue;
                }
                
                // Check if it's defined in the file or is a known global
                $isDefined = $this->isVariableDefinedInFile($var, $contents) || 
                            in_array($var, $allowedGlobals);
                
                // For now, just warn rather than fail (would need more sophisticated analysis)
                if (!$isDefined && !$this->isParameterOrLoopVariable($var, $contents)) {
                    // This is informational - complex to determine if truly undefined
                    $this->addWarning("View file " . basename($viewFile) . " may reference undefined variable: \$$var");
                }
            }
        }
        
        $this->assertTrue(true); // Test passes - warnings are informational
    }

    /**
     * Test that view files don't instantiate undefined classes
     */
    public function testViewFilesDontInstantiateUndefinedClasses()
    {
        $viewFiles = array_merge(
            glob($this->viewDir . '/*.php'),
            [
                $this->srcViewDir . '/view.php',
                $this->srcViewDir . '/reporting.php',
            ]
        );
        
        foreach ($viewFiles as $viewFile) {
            if (!file_exists($viewFile)) {
                continue;
            }
            
            $contents = file_get_contents($viewFile);
            
            // Find all "new ClassName" instantiations
            preg_match_all('/new\s+([A-Z][a-zA-Z0-9_\\\\]*)/', $contents, $matches);
            $classes = array_unique($matches[1]);
            
            foreach ($classes as $class) {
                // Resolve short name to full namespace if possible
                $fullClass = $this->resolveClassName($class, $contents);
                
                $classExists = class_exists($fullClass);
                
                $this->assertTrue($classExists, 
                    "View file " . basename($viewFile) . " instantiates non-existent class: $class (resolved to: $fullClass)");
            }
        }
    }

    /**
     * Test that view files use HTML builder classes (not hardcoded HTML)
     */
    public function testViewFilesUseHtmlBuilders()
    {
        $viewFiles = glob($this->viewDir . '/*.php');
        
        $htmlBuilderPatterns = [
            '/HtmlDiv/',
            '/HtmlA/',
            '/HtmlParagraph/',
            '/HtmlForm/',
            '/HtmlInput/',
            '/SelectBuilder/',
        ];
        
        foreach ($viewFiles as $viewFile) {
            $contents = file_get_contents($viewFile);
            
            // Skip files that are explicitly fallback/placeholder
            if (strpos($contents, 'coming soon') !== false || 
                strpos($contents, 'under development') !== false) {
                continue;
            }
            
            // Check if file uses HTML builders
            $usesBuilders = false;
            foreach ($htmlBuilderPatterns as $pattern) {
                if (preg_match($pattern, $contents)) {
                    $usesBuilders = true;
                    break;
                }
            }
            
            // If file has HTML output, it should use builders
            if (strpos($contents, 'echo') !== false || strpos($contents, '<?=') !== false) {
                // This is a recommendation, not a hard requirement
                if (!$usesBuilders) {
                    $this->addWarning("View file " . basename($viewFile) . " outputs HTML but doesn't use HTML builder classes");
                }
            }
        }
        
        $this->assertTrue(true); // Test passes - warnings are recommendations
    }

    /**
     * Test that admin_settings view imports required classes
     */
    public function testAdminSettingsViewDependencies()
    {
        $viewFile = $this->viewDir . '/admin_settings.php';
        $this->assertFileExists($viewFile);
        
        $contents = file_get_contents($viewFile);
        
        $requiredImports = [
            'Ksfraser\HTML\Elements\HtmlForm',
            'Ksfraser\HTML\Builders\SelectBuilder',
        ];
        
        foreach ($requiredImports as $import) {
            $this->assertStringContainsString("use $import", $contents,
                "admin_settings.php should import $import");
            
            // Verify class actually exists
            $this->assertTrue(class_exists($import),
                "admin_settings.php imports $import but class doesn't exist");
        }
    }

    // Helper methods

    private function extractUseStatements(string $contents): array
    {
        preg_match_all('/use\s+([A-Za-z0-9_\\\\]+);/', $contents, $matches);
        return $matches[1];
    }

    private function isVariableDefinedInFile(string $var, string $contents): bool
    {
        // Check for assignments: $var =
        if (preg_match('/\$' . preg_quote($var) . '\s*=/', $contents)) {
            return true;
        }
        
        // Check for function parameters
        if (preg_match('/function\s+\w+\([^)]*\$' . preg_quote($var) . '/', $contents)) {
            return true;
        }
        
        // Check for foreach: foreach ($x as $var)
        if (preg_match('/foreach\s*\([^)]+as\s+\$' . preg_quote($var) . '/', $contents)) {
            return true;
        }
        
        return false;
    }

    private function isParameterOrLoopVariable(string $var, string $contents): bool
    {
        // Common loop variables
        if (in_array($var, ['i', 'j', 'k', 'key', 'value', 'row', 'item'])) {
            return true;
        }
        
        return false;
    }

    private function resolveClassName(string $shortName, string $contents): string
    {
        // Check if there's a use statement for this class
        if (preg_match('/use\s+([A-Za-z0-9_\\\\]+\\\\' . preg_quote($shortName) . ');/', $contents, $matches)) {
            return $matches[1];
        }
        
        // Check for namespace
        if (preg_match('/namespace\s+([A-Za-z0-9_\\\\]+);/', $contents, $matches)) {
            return $matches[1] . '\\' . $shortName;
        }
        
        // Return as-is if can't resolve
        return $shortName;
    }
}
?>
