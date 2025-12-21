<?php

namespace Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Repository\SelectorRepository;
use PDO;

/**
 * SelectorRepositoryTest - Unit Tests for SelectorRepository
 * 
 * Tests all CRUD operations and query methods for selector options.
 * Uses in-memory SQLite database for fast, isolated testing.
 * 
 * @package    Tests\Unit\Repository
 * @author     Test Suite
 * @since      20251220
 * @version    1.0.0
 */
class SelectorRepositoryTest extends TestCase
{
    /**
     * PDO database connection
     * 
     * @var PDO
     */
    protected $db;

    /**
     * SelectorRepository instance
     * 
     * @var SelectorRepository
     */
    protected $repository;

    /**
     * Setup test database and repository
     */
    protected function setUp(): void
    {
        // Create in-memory SQLite database
        $this->db = new PDO('sqlite::memory:');
        
        // Enable foreign keys
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test table
        $this->db->exec("
            CREATE TABLE ksf_selectors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                selector_name TEXT NOT NULL,
                option_name TEXT NOT NULL,
                option_value TEXT NOT NULL
            )
        ");
        
        // Initialize repository with test database and prefix
        $this->repository = new SelectorRepository($this->db, 'ksf_selectors', '');
    }

    /**
     * Test adding a selector option
     */
    public function testAddSelectorOption()
    {
        $result = $this->repository->add('color', 'Red', '#FF0000');
        
        $this->assertTrue($result);
        
        // Verify it was added
        $options = $this->repository->getAll();
        $this->assertCount(1, $options);
        $this->assertEquals('color', $options[0]['selector_name']);
        $this->assertEquals('Red', $options[0]['option_name']);
        $this->assertEquals('#FF0000', $options[0]['option_value']);
    }

    /**
     * Test updating a selector option
     */
    public function testUpdateSelectorOption()
    {
        // Add an option first
        $this->repository->add('size', 'Small', 'S');
        
        // Get the ID
        $options = $this->repository->getAll();
        $id = $options[0]['id'];
        
        // Update it
        $result = $this->repository->update($id, 'size', 'Small Updated', 'S_UPD');
        
        $this->assertTrue($result);
        
        // Verify it was updated
        $updated = $this->repository->getById($id);
        $this->assertEquals('Small Updated', $updated['option_name']);
        $this->assertEquals('S_UPD', $updated['option_value']);
    }

    /**
     * Test deleting a selector option
     */
    public function testDeleteSelectorOption()
    {
        // Add an option
        $this->repository->add('status', 'Active', 'A');
        
        $options = $this->repository->getAll();
        $id = $options[0]['id'];
        
        // Delete it
        $result = $this->repository->delete($id);
        
        $this->assertTrue($result);
        
        // Verify it was deleted
        $remaining = $this->repository->getAll();
        $this->assertCount(0, $remaining);
    }

    /**
     * Test retrieving all selector options
     */
    public function testGetAllSelectorOptions()
    {
        // Add multiple options
        $this->repository->add('color', 'Red', '#FF0000');
        $this->repository->add('color', 'Blue', '#0000FF');
        $this->repository->add('size', 'Small', 'S');
        
        $options = $this->repository->getAll();
        
        $this->assertCount(3, $options);
        
        // Check they're sorted by selector_name, option_name
        $this->assertEquals('color', $options[0]['selector_name']);
        $this->assertEquals('Blue', $options[0]['option_name']);
    }

    /**
     * Test retrieving selector options by selector name
     */
    public function testGetBySelectorName()
    {
        $this->repository->add('color', 'Red', '#FF0000');
        $this->repository->add('color', 'Green', '#00FF00');
        $this->repository->add('size', 'Large', 'L');
        
        $colorOptions = $this->repository->getBySelectorName('color');
        
        $this->assertCount(2, $colorOptions);
        $this->assertEquals('color', $colorOptions[0]['selector_name']);
        $this->assertEquals('color', $colorOptions[1]['selector_name']);
    }

    /**
     * Test retrieving selector option by ID
     */
    public function testGetById()
    {
        $this->repository->add('status', 'Inactive', 'I');
        
        $options = $this->repository->getAll();
        $id = $options[0]['id'];
        
        $option = $this->repository->getById($id);
        
        $this->assertNotNull($option);
        $this->assertEquals($id, $option['id']);
        $this->assertEquals('Inactive', $option['option_name']);
    }

    /**
     * Test getting full table name
     */
    public function testGetTableName()
    {
        $tableName = $this->repository->getTableName();
        
        $this->assertEquals('ksf_selectors', $tableName);
    }

    /**
     * Test repository with custom table prefix
     */
    public function testRepositoryWithCustomPrefix()
    {
        // Create another table with prefix
        $this->db->exec("CREATE TABLE fa_ksf_selectors AS SELECT * FROM ksf_selectors");
        
        // Create repository with prefix
        $prefixedRepo = new SelectorRepository($this->db, 'ksf_selectors', 'fa_');
        
        $this->assertEquals('fa_ksf_selectors', $prefixedRepo->getTableName());
    }

    /**
     * Test handling non-existent ID
     */
    public function testGetByIdNotFound()
    {
        $option = $this->repository->getById(999);
        
        $this->assertFalse($option);
    }

    /**
     * Test empty results
     */
    public function testGetAllEmpty()
    {
        $options = $this->repository->getAll();
        
        $this->assertIsArray($options);
        $this->assertCount(0, $options);
    }

    /**
     * Test multiple adds and retrievals
     */
    public function testMultipleOperations()
    {
        // Add several
        for ($i = 1; $i <= 5; $i++) {
            $this->repository->add('number', "Number $i", (string)$i);
        }
        
        $all = $this->repository->getAll();
        $this->assertCount(5, $all);
        
        // Update one
        $id = $all[0]['id'];
        $this->repository->update($id, 'number', 'Updated Number', '10');
        
        $updated = $this->repository->getById($id);
        $this->assertEquals('Updated Number', $updated['option_name']);
        $this->assertEquals('10', $updated['option_value']);
        
        // Delete one
        $this->repository->delete($id);
        
        $remaining = $this->repository->getAll();
        $this->assertCount(4, $remaining);
    }
}
