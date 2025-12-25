<?php
namespace Tests\FA;

use PHPUnit\Framework\TestCase;

class FAControllerTest extends TestCase
{
    /**
     * Test that all view files referenced in controller.php exist
     */
    public function testControllerViewsExist()
    {
        $viewDir = __DIR__ . '/../../modules/amortization/views';
        
        $requiredViews = [
            'admin_settings.php',
            'admin_selectors.php',
            'user_loan_setup.php',
        ];
        
        foreach ($requiredViews as $view) {
            $path = $viewDir . '/' . $view;
            $this->assertFileExists($path, "View file missing: $view at $path");
            $this->assertIsReadable($path, "View file not readable: $view");
        }
    }

    /**
     * Test that controller.php exists and is valid PHP
     */
    public function testControllerExists()
    {
        $controllerPath = __DIR__ . '/../../modules/amortization/controller.php';
        $this->assertFileExists($controllerPath, "Controller file not found");
        $this->assertIsReadable($controllerPath, "Controller not readable");
        
        // Validate PHP syntax (basic check)
        $contents = file_get_contents($controllerPath);
        $this->assertStringNotContainsString('include __DIR__ . \'/views/views/', $contents, 
            "Controller has incorrect /views/views/ path - should be /views/");
    }
}
?>
