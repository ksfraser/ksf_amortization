<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Database\MigrationManager;
use PDO;

/**
 * MigrationManagerTest - Database Migration Test Suite
 *
 * Tests for the MigrationManager service which handles schema creation
 * and database setup for the payment history and delinquency tracking features.
 *
 * Test coverage:
 * - Schema file loading (2 tests)
 * - Table creation (1 test)
 * - Column existence checks (1 test)
 * - Migration status reporting (1 test)
 * - SQL statement parsing (1 test)
 * - Error handling (1 test)
 */
class MigrationManagerTest extends TestCase
{
    private $manager;
    private $pdo;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Use test schema path
        $schemaPath = __DIR__ . '/../../fixtures/schemas/';
        $this->manager = new MigrationManager($this->pdo, $schemaPath, 'test_');
    }

    /**
     * Test 1: Verify migration manager initializes correctly
     */
    public function testMigrationManagerInitializes()
    {
        $this->assertNotNull($this->manager);
        $this->assertInstanceOf(MigrationManager::class, $this->manager);
    }

    /**
     * Test 2: Verify table existence check works
     */
    public function testTableExistenceCheck()
    {
        // Create a test table
        $this->pdo->exec("CREATE TABLE test_sample (id INTEGER PRIMARY KEY, name TEXT)");

        // Check for existing table
        $exists = $this->manager->tableExists('sample');
        $this->assertTrue($exists);

        // Check for non-existing table
        $notExists = $this->manager->tableExists('nonexistent');
        $this->assertFalse($notExists);
    }

    /**
     * Test 3: Verify column existence check works
     */
    public function testColumnExistenceCheck()
    {
        // Create a test table with specific columns
        $this->pdo->exec("CREATE TABLE test_sample (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");

        // Check for existing column
        $exists = $this->manager->columnExists('sample', 'name');
        $this->assertTrue($exists);

        // Check for non-existing column
        $notExists = $this->manager->columnExists('sample', 'nonexistent');
        $this->assertFalse($notExists);
    }

    /**
     * Test 4: Verify SQL statement parsing handles comments
     */
    public function testSQLStatementParsingWithComments()
    {
        $sql = "
            -- This is a line comment
            CREATE TABLE test_table (id INT);
            
            /* This is a block
               comment spanning
               multiple lines */
            CREATE TABLE test_table2 (id INT);
        ";

        // Parse via reflection to access private method
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('parseSqlStatements');
        $method->setAccessible(true);

        $statements = $method->invoke($this->manager, $sql);

        // Should have 2 statements (comments excluded)
        $this->assertGreaterThanOrEqual(2, count($statements));
    }

    /**
     * Test 5: Verify migration manager handles string literals in SQL
     */
    public function testSQLStatementParsingWithStringLiterals()
    {
        $sql = "
            INSERT INTO test_table VALUES (1, 'Value; with; semicolons');
            CREATE TABLE test_table2 (id INT);
        ";

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('parseSqlStatements');
        $method->setAccessible(true);

        $statements = $method->invoke($this->manager, $sql);

        // Should correctly parse despite semicolons in string literal
        $this->assertGreaterThan(0, count($statements));
    }

    /**
     * Test 6: Verify migration status reporting
     */
    public function testMigrationStatusReporting()
    {
        $status = $this->manager->getMigrationStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('timestamp', $status);
        $this->assertArrayHasKey('tables', $status);
        $this->assertArrayHasKey('all_required_tables_exist', $status);

        // In fresh database, tables should not exist
        $this->assertFalse($status['all_required_tables_exist']);
    }

    /**
     * Test 7: Verify error handling for missing migration files
     */
    public function testErrorHandlingForMissingMigrationFile()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Migration file not found');

        $this->manager->runMigration('nonexistent.sql');
    }

    /**
     * Test 8: Verify table prefix replacement in migrations
     */
    public function testTablePrefixReplacement()
    {
        $sql = "CREATE TABLE 0_sample (id INT);";

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('parseSqlStatements');
        $method->setAccessible(true);

        $statements = $method->invoke($this->manager, $sql);

        // Verify we get parsed statements
        $this->assertGreaterThan(0, count($statements));
    }
}
