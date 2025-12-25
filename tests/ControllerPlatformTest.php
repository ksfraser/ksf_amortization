<?php
use PHPUnit\Framework\TestCase;

class ControllerPlatformTest extends TestCase
{
    public function testFAProviderExists()
    {
        $faProviderPath = __DIR__ . '/../modules/amortization/src/FADataProvider.php';
        $this->assertFileExists($faProviderPath, "FA DataProvider should exist in FA submodule");
    }

    public function testControllerExists()
    {
        $controllerPath = __DIR__ . '/../modules/amortization/controller.php';
        $this->assertFileExists($controllerPath, "FA controller should exist");
    }

    public function testViewFilesExist()
    {
        $viewsDir = __DIR__ . '/../modules/amortization/views';
        $this->assertTrue(is_dir($viewsDir), "Views directory should exist");
        
        $requiredViews = [
            'admin_settings.php',
            'admin_selectors.php',
            'user_loan_setup.php',
        ];
        
        foreach ($requiredViews as $view) {
            $this->assertFileExists($viewsDir . '/' . $view, "View $view should exist");
        }
    }
}
