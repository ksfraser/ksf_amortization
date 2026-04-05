<?php
namespace Ksfraser\Database;

use PDO;
use PDOException;

/**
 * Database Migration Runner
 * 
 * Executes SQL migration files in order to build/update database schema.
 * Tracks executed migrations to prevent re-execution.
 * 
 * Features:
 * - Sequential migration execution
 * - Migration history tracking
 * - Idempotent operations (IF NOT EXISTS/IF EXISTS support)
 * - Transaction-based execution for consistency
 * - Error handling and rollback
 * - Support for multiple SQL statements per file
 * 
 * Usage:
 * ```php
 * $db = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
 * $runner = new DatabaseMigrationRunner($db, '/path/to/migrations');
 * $runner->runAllPending();
 * ```
 * 
 * @package   Ksfraser\Database
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class DatabaseMigrationRunner
{
    /**
     * @var PDO Database connection
     */
    private $db;

    /**
     * @var string Path to migrations directory
     */
    private $migrationsPath;

    /**
     * @var string Table name for tracking migrations
     */
    private $migrationsTable = 'schema_migrations';

    /**
     * DatabaseMigrationRunner constructor
     *
     * @param PDO $db Database connection
     * @param string $migrationsPath Path to directory containing migration files
     * @param string $migrationsTable Migration tracking table name (default: schema_migrations)
     */
    public function __construct(PDO $db, string $migrationsPath, string $migrationsTable = 'schema_migrations')
    {
        $this->db = $db;
        $this->migrationsPath = rtrim($migrationsPath, '/\\') . DIRECTORY_SEPARATOR;
        $this->migrationsTable = $migrationsTable;

        if (!is_dir($this->migrationsPath)) {
            throw new \InvalidArgumentException("Migrations directory not found: {$this->migrationsPath}");
        }
    }

    /**
     * Set custom migrations table name
     *
     * @param string $tableName
     *
     * @return self
     */
    public function setMigrationsTable(string $tableName): self
    {
        $this->migrationsTable = $tableName;
        return $this;
    }

    /**
     * Initialize migrations tracking table
     *
     * @return void
     *
     * @throws PDOException
     */
    public function initializeTrackingTable(): void
    {
        try {
            $tableName = $this->migrationsTable;
            
            // Create migrations tracking table if it doesn't exist
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS {$tableName} (
                    id INTEGER PRIMARY KEY AUTO_INCREMENT,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_migration (migration)
                )
            ");
        } catch (PDOException $e) {
            // If MySQL syntax fails, try SQLite syntax
            try {
                $tableName = $this->migrationsTable;
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS {$tableName} (
                        id INTEGER PRIMARY KEY,
                        migration TEXT NOT NULL UNIQUE,
                        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
            } catch (PDOException $e2) {
                throw new \RuntimeException("Failed to create migrations table: " . $e2->getMessage());
            }
        }
    }

    /**
     * Get list of all available migration files
     *
     * @return array Migration filenames sorted chronologically
     */
    public function getAvailableMigrations(): array
    {
        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^migration_\d{14}_\d{3}_.*\.sql$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Get list of migrations that have been executed
     *
     * @return array Executed migration filenames
     *
     * @throws PDOException
     */
    public function getExecutedMigrations(): array
    {
        $this->ensureTrackingTableExists();

        try {
            $tableName = $this->migrationsTable;
            $stmt = $this->db->query("SELECT migration FROM {$tableName} ORDER BY executed_at");
            $result = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[] = $row['migration'];
            }

            return $result;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to fetch executed migrations: " . $e->getMessage());
        }
    }

    /**
     * Get list of pending (not yet executed) migrations
     *
     * @return array Pending migration filenames
     *
     * @throws PDOException
     */
    public function getPendingMigrations(): array
    {
        $available = $this->getAvailableMigrations();
        $executed = $this->getExecutedMigrations();

        return array_diff($available, $executed);
    }

    /**
     * Check if migration has been executed
     *
     * @param string $migrationName Migration filename
     *
     * @return bool True if migration has been executed
     *
     * @throws PDOException
     */
    public function isMigrationExecuted(string $migrationName): bool
    {
        $this->ensureTrackingTableExists();

        try {
            $tableName = $this->migrationsTable;
            $stmt = $this->db->prepare("SELECT 1 FROM {$tableName} WHERE migration = ?");
            $stmt->execute([$migrationName]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to check migration status: " . $e->getMessage());
        }
    }

    /**
     * Execute single migration file
     *
     * @param string $migrationName Migration filename
     *
     * @return bool True on success
     *
     * @throws PDOException
     * @throws \RuntimeException
     */
    public function runMigration(string $migrationName): bool
    {
        if ($this->isMigrationExecuted($migrationName)) {
            return true; // Already executed
        }

        $filePath = $this->migrationsPath . $migrationName;
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Migration file not found: {$filePath}");
        }

        try {
            // Read migration SQL
            $sql = file_get_contents($filePath);

            if ($sql === false) {
                throw new \RuntimeException("Failed to read migration file: {$filePath}");
            }

            // Begin transaction
            $this->db->beginTransaction();

            // Execute SQL statements (split by semicolon)
            $this->executeSqlStatements($sql);

            // Record migration execution
            $this->recordMigration($migrationName);

            // Commit transaction
            $this->db->commit();

            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new \RuntimeException("Migration failed: {$migrationName}: " . $e->getMessage());
        }
    }

    /**
     * Execute all pending migrations
     *
     * @return int Number of migrations executed
     *
     * @throws PDOException
     * @throws \RuntimeException
     */
    public function runAllPending(): int
    {
        $this->initializeTrackingTable();
        
        $pending = $this->getPendingMigrations();
        $count = 0;

        foreach ($pending as $migration) {
            if ($this->runMigration($migration)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Execute all available migrations
     *
     * @return int Number of migrations executed
     *
     * @throws PDOException
     * @throws \RuntimeException
     */
    public function runAll(): int
    {
        $this->initializeTrackingTable();

        $available = $this->getAvailableMigrations();
        $count = 0;

        foreach ($available as $migration) {
            if ($this->runMigration($migration)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Reset database (execute all migrations from scratch)
     * 
     * WARNING: This will clear migration history!
     *
     * @param bool $force Require explicit force flag for safety
     *
     * @return int Number of migrations executed
     *
     * @throws RuntimeException
     */
    public function reset(bool $force = false): int
    {
        if (!$force) {
            throw new \RuntimeException('Reset requires $force = true to prevent accidents');
        }

        // Clear migration history
        try {
            $tableName = $this->migrationsTable;
            $this->db->exec("TRUNCATE TABLE {$tableName}");
        } catch (PDOException $e) {
            // SQLite doesn't support TRUNCATE
            try {
                $tableName = $this->migrationsTable;
                $this->db->exec("DELETE FROM {$tableName}");
            } catch (PDOException $e2) {
                // Ignore if table doesn't exist
            }
        }

        // Re-run all migrations
        return $this->runAll();
    }

    /**
     * Create a new migration file template
     *
     * @param string $name Migration name (e.g., "create_users_table")
     *
     * @return string Path to created migration file
     *
     * @throws \RuntimeException
     */
    public function createMigration(string $name): string
    {
        $timestamp = strtotime('now');
        $version = str_pad(1, 3, '0', STR_PAD_LEFT);
        
        $filename = sprintf(
            'migration_%s_%s_%s.sql',
            date('Ymd', $timestamp) . date('Hi', $timestamp),
            $version,
            preg_replace('/[^a-z0-9_]/', '_', strtolower($name))
        );

        $filepath = $this->migrationsPath . $filename;

        $template = "-- Migration: {$name}
-- Created: " . date('Y-m-d H:i:s') . "
-- Description: Add your migration description here

-- Add your SQL statements below:
-- Example: CREATE TABLE IF NOT EXISTS users (...)

";

        if (file_put_contents($filepath, $template) === false) {
            throw new \RuntimeException("Failed to create migration file: {$filepath}");
        }

        return $filepath;
    }

    /**
     * Execute multiple SQL statements from a string
     *
     * @param string $sql SQL statements (separated by semicolons)
     *
     * @return void
     *
     * @throws PDOException
     */
    private function executeSqlStatements(string $sql): void
    {
        // Split by semicolon and execute non-empty statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && !preg_match('/^--/', $stmt)
        );

        foreach ($statements as $statement) {
            // Skip SQL comments
            $lines = array_filter(
                array_map('trim', explode("\n", $statement)),
                fn($line) => !empty($line) && !preg_match('/^--/', $line)
            );
            
            $cleanStatement = implode(' ', $lines);

            if (!empty($cleanStatement)) {
                $this->db->exec($cleanStatement);
            }
        }
    }

    /**
     * Record migration execution in tracking table
     *
     * @param string $migrationName
     *
     * @return void
     *
     * @throws PDOException
     */
    private function recordMigration(string $migrationName): void
    {
        try {
            $tableName = $this->migrationsTable;
            $stmt = $this->db->prepare("INSERT INTO {$tableName} (migration) VALUES (?)");
            $stmt->execute([$migrationName]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to record migration: " . $e->getMessage());
        }
    }

    /**
     * Ensure tracking table exists
     *
     * @return void
     *
     * @throws PDOException
     */
    private function ensureTrackingTableExists(): void
    {
        try {
            $tableName = $this->migrationsTable;
            $this->db->query("SELECT 1 FROM {$tableName} LIMIT 1");
        } catch (PDOException $e) {
            // Table doesn't exist, create it
            $this->initializeTrackingTable();
        }
    }

    /**
     * Get migration status report
     *
     * @return array Status information
     *
     * @throws PDOException
     */
    public function getStatus(): array
    {
        $available = $this->getAvailableMigrations();
        $executed = $this->getExecutedMigrations();
        $pending = array_diff($available, $executed);

        return [
            'total_available' => count($available),
            'total_executed' => count($executed),
            'total_pending' => count($pending),
            'availability_percentage' => count($available) > 0 
                ? round((count($executed) / count($available)) * 100, 2)
                : 0,
            'migrations_path' => $this->migrationsPath,
            'migrations_table' => $this->migrationsTable,
            'available_migrations' => $available,
            'executed_migrations' => $executed,
            'pending_migrations' => $pending
        ];
    }
}
