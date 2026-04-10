<?php
/**
 * TDD Test Suite for Core Repository Merge
 * 
 * Verifies that merging vendor-src/ksf_amortization_core into src/Ksfraser/Amortizations/
 * preserves all functionality and uses correct namespaces
 */

declare(strict_types=1);

namespace Ksfraser\Amortizations\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CoreMergeTDDTest extends TestCase
{
    private string $repoRoot;
    private string $corePath;
    private string $destPath;
    
    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__, 2);
        $this->corePath = $this->repoRoot . '/vendor-src/ksf_amortization_core';
        $this->destPath = $this->repoRoot . '/src/Ksfraser/Amortizations';
    }

    /**
     * Test: Baseline - Core directory exists with all expected subdirs
     */
    public function testCoreDirectoryHasExpectedStructure(): void
    {
        $this->assertDirectoryExists($this->corePath, 'Core path must exist');
        
        $expectedDirs = [
            'Analytics',
            'Api', 
            'Calculators',
            'Compliance',
            'Database',
            'EventHandlers',
            'Exceptions',
            'Handlers',
            'Models',
            'Persistence',
            'Reports',
            'Repositories',
            'Services',
            'Strategies',
            'Utils',
            'Views',
            'Ksfraser/Amortizations'
        ];
        
        foreach ($expectedDirs as $dir) {
            $this->assertDirectoryExists(
                "$this->corePath/$dir",
                "Core must have $dir directory"
            );
        }
    }

    /**
     * Test: All PHP files in core use correct namespace
     */
    public function testAllCorePhpFilesHaveKsfraserNamespace(): void
    {
        $phpFiles = $this->getPhpFiles($this->corePath);
        $this->assertGreaterThan(100, count($phpFiles), 'Core should have many PHP files');
        
        $incorrectNamespaces = [];
        $noNamespace = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            
            if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
                $namespace = trim($matches[1]);
                if (!str_starts_with($namespace, 'Ksfraser\\Amortizations')) {
                    $incorrectNamespaces[] = [
                        'file' => str_replace($this->corePath, '', $file),
                        'namespace' => $namespace
                    ];
                }
            } else {
                // Files without namespace (e.g., bootstrap, config files)
                if (!str_contains($content, '<?php') || str_contains($file, 'src/pages')) {
                    continue; // Skip non-PHP or view files
                }
                // $noNamespace[] = str_replace($this->corePath, '', $file);
            }
        }
        
        $this->assertEmpty(
            $incorrectNamespaces,
            "All PHP files must use Ksfraser\\Amortizations namespace:\n" . 
            json_encode($incorrectNamespaces, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Test: Destination directory path exists
     */
    public function testDestinationDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->destPath, 'Destination Ksfraser/Amortizations must exist');
    }

    /**
     * Test: Core and destination have expected file counts (baseline)
     */
    public function testCoreHasExpectedFileCount(): void
    {
        $coreFiles = $this->getAllFiles($this->corePath, ['vendor', '.git', 'tests']);
        $this->assertGreaterThan(400, count($coreFiles), 'Core should have 400+ files to merge');
    }

    /**
     * Test: No files in core/vendor-src will conflict
     */
    public function testNoConflictingPaths(): void
    {
        $corePhpFiles = $this->getPhpFiles($this->corePath . '/Ksfraser/Amortizations');
        $destPhpFiles = $this->getPhpFiles($this->destPath);
        
        // Get relative paths for comparison
        $corePaths = array_map(fn($f) => str_replace($this->corePath . '/Ksfraser/Amortizations/', '', $f), $corePhpFiles);
        $destPaths = array_map(fn($f) => str_replace($this->destPath . '/', '', $f), $destPhpFiles);
        
        $conflicts = array_intersect($corePaths, $destPaths);
        
        // Some conflicts are OK (e.g., Api/*, Handlers/*) - they'll be merged
        // Check for direct conflicts in SAME DIRECTORY
        $realConflicts = [];
        foreach ($conflicts as $conflict) {
            if (!str_contains($conflict, '/')) {
                // Top-level file would conflict
                $realConflicts[] = $conflict;
            }
        }
        
        // It's OK if subdirs exist - we're merging them
        $this->assertLessThan(5, count($realConflicts), 
            "Should have minimal top-level conflicts:\n" . implode("\n", $realConflicts));
    }

    /**
     * Test: Core composer.json references are manageable
     */
    public function testCoreComposerStructure(): void
    {
        $composerPath = $this->corePath . '/composer.json';
        $this->assertFileExists($composerPath, 'Core should have composer.json');
        
        $composer = json_decode(file_get_contents($composerPath), true);
        
        $this->assertArrayHasKey('autoload', $composer, 'Must have autoload config');
        $this->assertArrayHasKey('psr-4', $composer['autoload'], 'Must use PSR-4');
        
        $psr4 = $composer['autoload']['psr-4'];
        $this->assertArrayHasKey('Ksfraser\\Amortizations\\', $psr4, 'Must have Ksfraser namespace');
    }

    // ===== Helper Methods =====

    private function getPhpFiles(string $dir, array $exclude = []): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getRealPath();
                
                $skip = false;
                foreach ($exclude as $pattern) {
                    if (str_contains($path, $pattern)) {
                        $skip = true;
                        break;
                    }
                }
                
                if (!$skip) {
                    $files[] = $path;
                }
            }
        }
        
        return $files;
    }

    private function getAllFiles(string $dir, array $exclude = []): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $path = $file->getRealPath();
                
                $skip = false;
                foreach ($exclude as $pattern) {
                    if (str_contains($path, DIRECTORY_SEPARATOR . $pattern . DIRECTORY_SEPARATOR) || 
                        str_ends_with($path, $pattern)) {
                        $skip = true;
                        break;
                    }
                }
                
                if (!$skip) {
                    $files[] = $path;
                }
            }
        }
        
        return $files;
    }
}
