<?php
namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Ksfraser\Database\DatabaseMigrationRunner;
use PDO;

/**
 * DatabaseMigrationRunner Tests
 * 
 * Tests for database migration execution and tracking.
 * 
 * @package   Tests\Unit\Database
 * @author    KSF Development Team
 */
class DatabaseMigrationRunnerTest extends TestCase
{
    private $db;
    private $migrationsDir;
    private $runner;

    protected function setUp(): void
    {
        // Create in-memory SQLite database
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create temporary migrations directory
        $this->migrationsDir = sys_get_temp_dir() . '/migrations_' . uniqid();
        mkdir($this->migrationsDir, 0777, true);

        $this->runner = new DatabaseMigrationRunner($this->db, $this->migrationsDir);
    }

    protected function tearDown(): void
    {
        // Clean up temporary migrations directory
        $this->removeDirectory($this->migrationsDir);
    }

    private function removeDirectory(string $path): void
    {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filePath = $path . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($filePath)) {
                        $this->removeDirectory($filePath);
                    } else {
                        unlink($filePath);
                    }
                }
            }
            rmdir($path);
        }
    }

    /**
     * Create a migration file with SQL content
     */
    private function createMigrationFile(string $name, string $sql): string
    {
        $filename = $name . '.sql';
        $filepath = $this->migrationsDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filepath, $sql);
        return $filepath;
    }

    // ===== INITIALIZATION TESTS =====

    /**
     * Test initialize tracking table
     */
    public function testInitializeTrackingTable(): void
    {
        $this->runner->initializeTrackingTable();

        // Verify table was created
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='schema_migrations'");
        $this->assertNotFalse($stmt->fetch());
    }

    /**
     * Test initialize with custom table name
     */
    public function testInitializeWithCustomTableName(): void
    {
        $this->runner->setMigrationsTable('custom_migrations');
        $this->runner->initializeTrackingTable();

        // Verify custom table was created
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='custom_migrations'");
        $this->assertNotFalse($stmt->fetch());
    }

    // ===== MIGRATION DISCOVERY TESTS =====

    /**
     * Test get available migrations
     */
    public function testGetAvailableMigrations(): void
    {
        $this->createMigrationFile('migration_20260404_001_test1', 'SELECT 1;');
        $this->createMigrationFile('migration_20260404_002_test2', 'SELECT 1;');

        $migrations = $this->runner->getAvailableMigrations();

        $this->assertCount(2, $migrations);
        $this->assertStringContainsString('migration_20260404_001_test1', $migrations[0]);
        $this->assertStringContainsString('migration_20260404_002_test2', $migrations[1]);
    }

    /**
     * Test get available migrations sorted chronologically
     */
    public function testMigrationsSortedChronologically(): void
    {
        // Create out-of-order files
        $this->createMigrationFile('migration_20260404_003_z', 'SELECT 1;');
        $this->createMigrationFile('migration_20260404_001_a', 'SELECT 1;');
        $this->createMigrationFile('migration_20260404_002_b', 'SELECT 1;');

        $migrations = $this->runner->getAvailableMigrations();

        $this->assertCount(3, $migrations);
        $this->assertStringContainsString('_001_', $migrations[0]);
        $this->assertStringContainsString('_002_', $migrations[1]);
        $this->assertStringContainsString('_003_', $migrations[2]);
    }

    /**
     * Test ignores non-migration files
     */
    public function testIgnoresNonMigrationFiles(): void
    {
        file_put_contents($this->migrationsDir . '/README.md', 'Not a migration');
        file_put_contents($this->migrationsDir . '/setup.sql', 'Not a migration');
        $this->createMigrationFile('migration_20260404_001_real', 'SELECT 1;');

        $migrations = $this->runner->getAvailableMigrations();

        $this->assertCount(1, $migrations);
        $this->assertStringContainsString('migration_20260404_001_real', $migrations[0]);
    }

    // ===== MIGRATION EXECUTION TESTS =====

    /**
     * Test run single migration
     */
    public function testRunSingleMigration(): void
    {
        $this->runner->initializeTrackingTable();

        $sql = 'CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT);';
        $this->createMigrationFile('migration_20260404_001_test', $sql);

        $result = $this->runner->runMigration('migration_20260404_001_test.sql');
        
        $this->assertTrue($result);

        // Verify table was created
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'");
        $this->assertNotFalse($stmt->fetch());
    }

    /**
     * Test run all pending migrations
     */
    public function testRunAllPendingMigrations(): void
    {
        $this->createMigrationFile('migration_20260404_001_users', 'CREATE TABLE users (id INTEGER PRIMARY KEY);');
        $this->createMigrationFile('migration_20260404_002_posts', 'CREATE TABLE posts (id INTEGER PRIMARY KEY);');

        $count = $this->runner->runAllPending();

        $this->assertEquals(2, $count);

        // Verify both tables were created
        $usersExist = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
        $postsExist = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='posts'")->fetch();

        $this->assertNotFalse($usersExist);
        $this->assertNotFalse($postsExist);
    }

    /**
     * Test migration with multiple SQL statements
     */
    public function testMigrationWithMultipleStatements(): void
    {
        $this->runner->initializeTrackingTable();

        $sql = <<<SQL
            CREATE TABLE table1 (id INTEGER PRIMARY KEY);
            CREATE TABLE table2 (id INTEGER PRIMARY KEY);
            CREATE TABLE table3 (id INTEGER PRIMARY KEY);
        SQL;

        $this->createMigrationFile('migration_20260404_001_multi', $sql);
        $this->runner->runMigration('migration_20260404_001_multi.sql');

        // Verify all three tables were created
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='table{$i}'");
            $this->assertNotFalse($stmt->fetch(), "Table table{$i} was not created");
        }
    }

    /**
     * Test migration SQL with comments is ignored
     */
    public function testMigrationCommentsIgnored(): void
    {
        $this->runner->initializeTrackingTable();

        $sql = <<<SQL
            -- This is a comment
            CREATE TABLE test_table (id INTEGER PRIMARY KEY);
            -- This is another comment
        SQL;

        $this->createMigrationFile('migration_20260404_001_comments', $sql);
        $this->runner->runMigration('migration_20260404_001_comments.sql');

        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'");
        $this->assertNotFalse($stmt->fetch());
    }

    // ===== MIGRATION TRACKING TESTS =====

    /**
     * Test get executed migrations
     */
    public function testGetExecutedMigrations(): void
    {
        $this->createMigrationFile('migration_20260404_001_first', 'CREATE TABLE t1 (id INTEGER PRIMARY KEY);');
        $this->createMigrationFile('migration_20260404_002_second', 'CREATE TABLE t2 (id INTEGER PRIMARY KEY);');

        $this->runner->runAllPending();

        $executed = $this->runner->getExecutedMigrations();

        $this->assertCount(2, $executed);
        $this->assertStringContainsString('migration_20260404_001_first', $executed[0]);
        $this->assertStringContainsString('migration_20260404_002_second', $executed[1]);
    }

    /**
     * Test get pending migrations
     */
    public function testGetPendingMigrations(): void
    {
        $this->createMigrationFile('migration_20260404_001_first', 'CREATE TABLE t1 (id INTEGER PRIMARY KEY);');
        $this->createMigrationFile('migration_20260404_002_second', 'CREATE TABLE t2 (id INTEGER PRIMARY KEY);');
        $this->createMigrationFile('migration_20260404_003_third', 'CREATE TABLE t3 (id INTEGER PRIMARY KEY);');

        // Run only first migration
        $this->runner->runMigration('migration_20260404_001_first.sql');

        $pending = $this->runner->getPendingMigrations();

        $this->assertCount(2, $pending);
        $this->assertStringContainsString('migration_20260404_002_second', $pending[1]);
        $this->assertStringContainsString('migration_20260404_003_third', $pending[2]);
    }

    /**
     * Test skip already executed migrations
     */
    public function testSkipAlreadyExecutedMigration(): void
    {
        $this->runner->initializeTrackingTable();

        $sql = 'CREATE TABLE test_table (id INTEGER PRIMARY KEY);';
        $this->createMigrationFile('migration_20260404_001_test', $sql);

        // First run succeeds
        $result1 = $this->runner->runMigration('migration_20260404_001_test.sql');
        $this->assertTrue($result1);

        // Second run returns true (already executed)
        $result2 = $this->runner->runMigration('migration_20260404_001_test.sql');
        $this->assertTrue($result2);
    }

    /**
     * Test is migration executed
     */
    public function testIsMigrationExecuted(): void
    {
        $this->createMigrationFile('migration_20260404_001_test', 'CREATE TABLE t1 (id INTEGER PRIMARY KEY);');

        $this->assertFalse($this->runner->isMigrationExecuted('migration_20260404_001_test.sql'));

        $this->runner->runMigration('migration_20260404_001_test.sql');

        $this->assertTrue($this->runner->isMigrationExecuted('migration_20260404_001_test.sql'));
    }

    // ===== STATUS REPORTING TESTS =====

    /**
     * Test get status
     */
    public function testGetStatus(): void
    {
        $this->createMigrationFile('migration_20260404_001_first', 'CREATE TABLE t1 (id INTEGER PRIMARY KEY);');
        $this->createMigrationFile('migration_20260404_002_second', 'CREATE TABLE t2 (id INTEGER PRIMARY KEY);');

        $this->runner->runMigration('migration_20260404_001_first.sql');

        $status = $this->runner->getStatus();

        $this->assertEquals(2, $status['total_available']);
        $this->assertEquals(1, $status['total_executed']);
        $this->assertEquals(1, $status['total_pending']);
        $this->assertEquals(50, $status['availability_percentage']);
    }

    /**
     * Test get status with no migrations
     */
    public function testGetStatusNoMigrations(): void
    {
        $status = $this->runner->getStatus();

        $this->assertEquals(0, $status['total_available']);
        $this->assertEquals(0, $status['total_executed']);
        $this->assertEquals(0, $status['total_pending']);
        $this->assertEquals(0, $status['availability_percentage']);
    }

    /**
     * Test get status with all executed
     */
    public function testGetStatusAllExecuted(): void
    {
        $this->createMigrationFile('migration_20260404_001_first', 'CREATE TABLE t1 (id INTEGER PRIMARY KEY);');
        $this->runner->runAllPending();

        $status = $this->runner->getStatus();

        $this->assertEquals(1, $status['total_available']);
        $this->assertEquals(1, $status['total_executed']);
        $this->assertEquals(0, $status['total_pending']);
        $this->assertEquals(100, $status['availability_percentage']);
    }

    // ===== ERROR HANDLING TESTS =====

    /**
     * Test migration file not found
     */
    public function testMigrationFileNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->runner->runMigration('nonexistent_migration.sql');
    }

    /**
     * Test invalid migrations directory
     */
    public function testInvalidMigrationsDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DatabaseMigrationRunner($this->db, '/nonexistent/path');
    }

    /**
     * Test SQL error handling
     */
    public function testSQLErrorHandling(): void
    {
        $this->runner->initializeTrackingTable();

        // Create migration with invalid SQL
        $sql = 'CREATE TABLE test_table (id INTEGER PRIMARY KEY); INVALID SQL HERE;';
        $this->createMigrationFile('migration_20260404_001_bad', $sql);

        $this->expectException(\RuntimeException::class);
        $this->runner->runMigration('migration_20260404_001_bad.sql');
    }
}
