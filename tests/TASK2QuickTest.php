<?php
/**
 * TASK 2 Quick Validation Tests
 *
 * Light-weight tests for TASK 2 platform implementations
 * Does not require database - uses pure file checking
 * 
 * Tests verify that all 6 methods are implemented across all 4 platforms
 *
 * @package   Ksfraser\Amortizations\Tests
 * @author    KSF Development Team
 */

namespace Ksfraser\Amortizations\Tests;

use PHPUnit\Framework\TestCase;

class TASK2QuickTest extends TestCase
{
    /**
     * Test that FA DataProvider (modules/) has all 6 methods
     * @test
     */
    public function testFAModulesDataProviderHasAllMethods(): void
    {
        $filePath = __DIR__ . '/../modules/amortization/FADataProvider.php';
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        
        $methods = ['insertLoanEvent', 'getLoanEvents', 'deleteScheduleAfterDate',
                    'getScheduleRowsAfterDate', 'updateScheduleRow', 'getScheduleRows'];
        
        foreach ($methods as $method) {
            $this->assertStringContainsString("function $method", $content);
        }
    }
    
    /**
     * Test that FA DataProvider (src/) has all 6 methods
     * @test
     */
    public function testFASrcDataProviderHasAllMethods(): void
    {
        $filePath = __DIR__ . '/../src/Ksfraser/fa/FADataProvider.php';
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        
        $methods = ['insertLoanEvent', 'getLoanEvents', 'deleteScheduleAfterDate',
                    'getScheduleRowsAfterDate', 'updateScheduleRow', 'getScheduleRows'];
        
        foreach ($methods as $method) {
            $this->assertStringContainsString("function $method", $content);
        }
    }
    
    /**
     * Test that WordPress DataProvider has all 6 methods
     * @test
     */
    public function testWordPressDataProviderHasAllMethods(): void
    {
        $filePath = __DIR__ . '/../src/Ksfraser/wordpress/WPDataProvider.php';
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        
        $methods = ['insertLoanEvent', 'getLoanEvents', 'deleteScheduleAfterDate',
                    'getScheduleRowsAfterDate', 'updateScheduleRow', 'getScheduleRows'];
        
        foreach ($methods as $method) {
            $this->assertStringContainsString("function $method", $content);
        }
    }
    
    /**
     * Test that SuiteCRM DataProvider has all 6 methods
     * @test
     */
    public function testSuiteCRMDataProviderHasAllMethods(): void
    {
        $filePath = __DIR__ . '/../src/Ksfraser/suitecrm/SuiteCRMDataProvider.php';
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        
        $methods = ['insertLoanEvent', 'getLoanEvents', 'deleteScheduleAfterDate',
                    'getScheduleRowsAfterDate', 'updateScheduleRow', 'getScheduleRows'];
        
        foreach ($methods as $method) {
            $this->assertStringContainsString("function $method", $content);
        }
    }
    
    /**
     * Test that AmortizationModel has TASK 2 methods
     * @test
     */
    public function testAmortizationModelHasTask2Methods(): void
    {
        $filePath = __DIR__ . '/../src/Ksfraser/Amortizations/AmortizationModel.php';
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        
        $methods = ['recordExtraPayment', 'recordSkipPayment', 'recalculateScheduleAfterEvent'];
        
        foreach ($methods as $method) {
            $this->assertStringContainsString("function $method", $content);
        }
    }
    
    /**
     * Test that DataProviderInterface has new method declarations
     * @test
     */
    public function testDataProviderInterfaceHasNewMethods(): void
    {
        $filePath = __DIR__ . '/../src/Ksfraser/Amortizations/DataProviderInterface.php';
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        
        $methods = ['insertLoanEvent', 'getLoanEvents', 'deleteScheduleAfterDate',
                    'getScheduleRowsAfterDate', 'updateScheduleRow', 'getScheduleRows'];
        
        foreach ($methods as $method) {
            $this->assertStringContainsString("public function $method", $content);
        }
    }
    
    /**
     * Test that MockClasses has method implementations
     * @test
     */
    public function testMockClassesHasImplementations(): void
    {
        $filePath = __DIR__ . '/MockClasses.php';
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        
        // These should be implemented
        $methods = ['insertLoanEvent', 'insertSchedule', 'deleteScheduleAfterDate'];
        
        foreach ($methods as $method) {
            $this->assertStringContainsString("function $method", $content);
        }
    }
    
    /**
     * Test all provider files exist
     * @test
     */
    public function testAllProviderFilesExist(): void
    {
        $providers = [
            __DIR__ . '/../modules/amortization/FADataProvider.php',
            __DIR__ . '/../src/Ksfraser/fa/FADataProvider.php',
            __DIR__ . '/../src/Ksfraser/wordpress/WPDataProvider.php',
            __DIR__ . '/../src/Ksfraser/suitecrm/SuiteCRMDataProvider.php',
        ];
        
        foreach ($providers as $provider) {
            $this->assertFileExists($provider, "Provider should exist: $provider");
        }
    }
    
    /**
     * Test that TASK2 documentation exists
     * @test
     */
    public function testTask2DocumentationExists(): void
    {
        $files = [
            __DIR__ . '/../TASK2_IMPLEMENTATION_SUMMARY.md',
            __DIR__ . '/../TASK2_PLATFORM_IMPLEMENTATION_COMPLETE.md'
        ];
        
        foreach ($files as $file) {
            $this->assertFileExists($file, "TASK 2 documentation should exist: $file");
        }
    }
    
    /**
     * Test platform-specific SQL patterns exist
     * @test
     */
    public function testFAPlatformHasSQLImplementation(): void
    {
        $filePath = __DIR__ . '/../src/Ksfraser/fa/FADataProvider.php';
        $content = file_get_contents($filePath);
        
        // Should use PDO prepare() method
        $this->assertStringContainsString('$this->pdo->prepare', $content);
        $this->assertStringContainsString('INSERT INTO', $content);
        $this->assertStringContainsString('SELECT', $content);
    }
    
    /**
     * Test WordPress platform implementation
     * @test
     */
    public function testWordPressPlatformHasWPDBImplementation(): void
    {
        $filePath = __DIR__ . '/../src/Ksfraser/wordpress/WPDataProvider.php';
        $content = file_get_contents($filePath);
        
        // Should use WPDB API
        $this->assertStringContainsString('$this->wpdb', $content);
    }
    
    /**
     * Test SuiteCRM platform implementation
     * @test
     */
    public function testSuiteCRMPlatformHasBeanFactoryImplementation(): void
    {
        $filePath = __DIR__ . '/../src/Ksfraser/suitecrm/SuiteCRMDataProvider.php';
        $content = file_get_contents($filePath);
        
        // Should use BeanFactory
        $this->assertStringContainsString('BeanFactory', $content);
    }
    
    /**
     * Test that implementations use correct database abstraction
     * @test
     */
    public function testImplementationsUseCorrectDatabaseAPIs(): void
    {
        // FA should use PDO
        $faContent = file_get_contents(__DIR__ . '/../src/Ksfraser/fa/FADataProvider.php');
        $this->assertStringContainsString('PDO', $faContent);
        $this->assertStringContainsString('prepare', $faContent);
        
        // WordPress should use WPDB
        $wpContent = file_get_contents(__DIR__ . '/../src/Ksfraser/wordpress/WPDataProvider.php');
        $this->assertStringContainsString('wpdb', $wpContent);
        
        // SuiteCRM should use BeanFactory
        $scContent = file_get_contents(__DIR__ . '/../src/Ksfraser/suitecrm/SuiteCRMDataProvider.php');
        $this->assertStringContainsString('BeanFactory', $scContent);
    }
}
?>
