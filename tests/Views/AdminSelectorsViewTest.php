<?php
require_once __DIR__ . '/../../../../vendor/autoload.php';
use PHPUnit\Framework\TestCase;

class AdminSelectorsViewTest extends TestCase
{
    public function testAddSelectorOption()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'add_option' => true,
            'selector_name' => '_new',
            'new_selector_name' => 'TestSelector',
            'option_name' => 'TestOption',
            'option_value' => 'TestValue'
        ];
        // Provide a mock $db connection
        global $db;
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("CREATE TABLE ksf_selectors (id INTEGER PRIMARY KEY AUTOINCREMENT, selector_name TEXT NOT NULL, option_name TEXT NOT NULL, option_value TEXT NOT NULL)");
        ob_start();
        include __DIR__ . '/../../views/admin_selectors.php';
        $output = ob_get_clean();
        $this->assertStringContainsString('Option added successfully', $output);
    }

    public function testDeleteSelectorOption()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'delete_option' => true,
            'option_id' => 1
        ];
        // Provide a mock $db connection
        global $db;
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("CREATE TABLE ksf_selectors (id INTEGER PRIMARY KEY AUTOINCREMENT, selector_name TEXT NOT NULL, option_name TEXT NOT NULL, option_value TEXT NOT NULL)");
        // Add an option to delete
        $db->exec("INSERT INTO ksf_selectors (selector_name, option_name, option_value) VALUES ('TestSelector', 'TestOption', 'TestValue')");
        ob_start();
        include __DIR__ . '/../../views/admin_selectors.php';
        $output = ob_get_clean();
        $this->assertStringContainsString('Option deleted successfully', $output);
    }

    public function testViewRendersWithoutDb()
    {
        global $db;
        $db = null;
        ob_start();
        include __DIR__ . '/../../views/admin_selectors.php';
        $output = ob_get_clean();
        $this->assertStringContainsString('Database connection not available', $output);
    }
}
