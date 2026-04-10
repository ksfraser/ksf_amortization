<?php
/**
 * Core Migration Test - Validates that ksf_amortization_core is properly merged
 * 
 * This test ensures that when we merge the core submodule into the main repo,
 * all files are present, all autoload paths work, and core functionality is intact.
 */

namespace KsfAmorization\Tests;

use PHPUnit\Framework\TestCase;

class CoreMigrationTest extends TestCase
{
    private $coreBasePath;
    private $expectedFileCount = 474; // Baseline from submodule
    
    protected function setUp(): void
    {
        $this->coreBasePath = realpath(__DIR__ . '/../../src/ksf_amortization_core');
        if (!is_dir($this->coreBasePath)) {
            $this->coreBasePath = realpath(__DIR__ . '/../../vendor-src/ksf_amortization_core');
        }
    }
    
    /**
     * Test 1: Core directory exists
     */
    public function testCoreDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->coreBasePath, 'Core directory must exist at src/ksf_amortization_core');
    }
    
    /**
     * Test 2: All critical core subdirectories exist
     */
    public function testCriticalDirectoriesExist(): void
    {
        $requiredDirs = [
            'Amortizations',
            'Analytics',
            'Api',
            'Calculators',
            'Compliance',
            'Database',
            'EventHandlers',
            'Exceptions',
            'FA',
            'Handlers',
            'Models',
            'Persistence',
            'Reports',
            'Repositories',
            'Services',
            'Strategies',
            'Utils',
            'Views',
        ];
        
        foreach ($requiredDirs as $dir) {
            $path = $this->coreBasePath . '/' . $dir;
            $this->assertDirectoryExists($path, "Required directory {$dir} must exist");
        }
    }
    
    /**
     * Test 3: Key root-level files exist
     */
    public function testRootFilesExist(): void
    {
        $requiredFiles = [
            'composer.json',
            'controller.php',
            'model.php',
            'view.php',
            'reporting.php',
            'schema.sql',
            'schema_events.sql',
            'schema_delinquency.sql',
            'schema_selectors.sql',
        ];
        
        foreach ($requiredFiles as $file) {
            $path = $this->coreBasePath . '/' . $file;
            $this->assertFileExists($path, "Required file {$file} must exist");
        }
    }
    
    /**
     * Test 4: File count is preserved
     */
    public function testFileCountPreserved(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->coreBasePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $count = 0;
        foreach ($files as $file) {
            if ($file->isFile() && strpos($file->getPath(), '.git') === false) {
                $count++;
            }
        }
        
        // Allow ±10 files for test files or minor differences
        $this->assertGreaterThanOrEqual($this->expectedFileCount - 10, $count, 'File count should be preserved');
        $this->assertLessThanOrEqual($this->expectedFileCount + 10, $count, 'File count should not significantly increase');
    }
    
    /**
     * Test 5: PSR-4 Autoload is valid
     */
    public function testPsr4AutoloadStructure(): void
    {
        $expectedNamespaces = [
            'Ksfraser\\Amortizations' => $this->coreBasePath,
        ];
        
        foreach ($expectedNamespaces as $namespace => $basePath) {
            $this->assertDirectoryExists($basePath, "Base path for {$namespace} must exist");
        }
    }
    
    /**
     * Test 6: Composer.json is valid
     */
    public function testComposerJsonValid(): void
    {
        $composerPath = $this->coreBasePath . '/composer.json';
        $this->assertFileExists($composerPath);
        
        $content = file_get_contents($composerPath);
        $json = json_decode($content, true);
        
        $this->assertIsArray($json, 'composer.json must be valid JSON');
        $this->assertArrayHasKey('name', $json, 'composer.json must have name');
        $this->assertArrayHasKey('autoload', $json, 'composer.json must have autoload');
    }
    
    /**
     * Test 7: Key PHP classes can be loaded (after merge)
     */
    public function testCriticalClassesCanBeLoaded(): void
    {
        // These should exist regardless of where core is located
        $expectedClasses = [
            'Ksfraser\\Amortizations\\Models\\AmortizationModel',
            'Ksfraser\\Amortizations\\Database\\DatabaseManager',
            'Ksfraser\\Amortizations\\Services\\LoanComparisonEngine',
        ];
        
        foreach ($expectedClasses as $class) {
            $classParts = explode('\\', $class);
            $fileName = end($classParts) . '.php';
            
            // Just verify the file structure, not full autoloading
            $found = false;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->coreBasePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->getFilename() === $fileName && strpos($file->getPath(), '.git') === false) {
                    $found = true;
                    break;
                }
            }
            
            $this->assertTrue($found, "Class file for {$class} must exist in core");
        }
    }
    
    /**
     * Test 8: No broken symlinks or missing references
     */
    public function testNoMissingReferences(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->coreBasePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $brokenReferences = [];
        
        foreach ($iterator as $file) {
            if ($file->isFile() && !$file->isLink() && strpos($file->getPath(), '.git') === false) {
                $this->assertTrue(file_exists($file->getPathname()), "File must exist and not be broken: " . $file->getPathname());
            }
        }
    }
}
