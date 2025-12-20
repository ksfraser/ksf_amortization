<?php
namespace Ksfraser\Amortizations;

/**
 * AmortizationModuleInstaller
 * 
 * Dynamically creates tables and pre-populates selector tables using the correct DB prefix.
 * Supports FrontAccounting (TB_PREF), WordPress (wpdb->prefix), SuiteCRM, and other platforms.
 * 
 * @package Ksfraser\Amortizations
 */
class AmortizationModuleInstaller
{
    /**
     * Platform-specific DB adapter (PDO, wpdb, SuiteCRM, etc.)
     * @var object
     */
    private $dbAdapter;

    /**
     * Table prefix (e.g., '0_' for FA, 'wp_' for WordPress)
     * @var string
     */
    private $dbPrefix;

    /**
     * Schema files to execute (optional)
     * @var array
     */
    private $schemaFiles;

    /**
     * Constructor
     *
     * @param object $dbAdapter   Platform-specific DB adapter (PDO, wpdb, SuiteCRM, etc.)
     * @param string $dbPrefix    Table prefix determined by platform
     * @param array  $schemaFiles Optional schema files to execute
     */
    public function __construct($dbAdapter, $dbPrefix = '', $schemaFiles = [])
    {
        $this->dbAdapter = $dbAdapter;
        $this->dbPrefix = $dbPrefix;
        $this->schemaFiles = $schemaFiles;
    }

    /**
     * Install amortization module tables and seed data
     */
    public function install()
    {
        // Execute schema files if provided
        foreach ($this->schemaFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }
            $sql = file_get_contents($file);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt) {
                    $this->runIfNotExists($stmt);
                }
            }
        }

        // Create selector tables with correct prefix
        $this->createSelectorTables();
        $this->populateSelectorTables();
    }

    /**
     * Create selector tables
     */
    private function createSelectorTables()
    {
        $sqls = [
            "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}ksf_amort_loan_types` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(32) NOT NULL,
                description VARCHAR(128)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}ksf_amort_interest_calc_frequencies` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(32) NOT NULL,
                description VARCHAR(128)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        foreach ($sqls as $sql) {
            $this->execute($sql);
        }
    }

    /**
     * Populate selector tables with default values
     */
    private function populateSelectorTables()
    {
        $loanTypes = [
            ['auto', 'Auto loan'],
            ['mortgage', 'Mortgage loan'],
            ['personal', 'Personal loan'],
            ['student', 'Student loan']
        ];
        $insertLoanType = "INSERT INTO `{$this->dbPrefix}ksf_amort_loan_types` (name, description) VALUES (?, ?)";
        foreach ($loanTypes as $row) {
            $this->execute($insertLoanType, $row);
        }

        $freqs = [
            ['monthly', 'Monthly'],
            ['annual', 'Annual'],
            ['daily', 'Daily'],
            ['semi-annual', 'Semi-annual']
        ];
        $insertFreq = "INSERT INTO `{$this->dbPrefix}ksf_amort_interest_calc_frequencies` (name, description) VALUES (?, ?)";
        foreach ($freqs as $row) {
            $this->execute($insertFreq, $row);
        }
    }

    /**
     * Execute SQL, only if table doesn't already exist
     */
    private function runIfNotExists($sql)
    {
        // Extract table name from CREATE TABLE statement
        if (preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/i', $sql, $matches) ||
            preg_match('/CREATE TABLE `(\w+)`/i', $sql, $matches)) {
            $table = $matches[1];
            if ($this->tableExists($table)) {
                return;
            }
        }
        $this->execute($sql);
    }

    /**
     * Check if table exists
     */
    private function tableExists($table)
    {
        // PDO
        if ($this->dbAdapter instanceof \PDO) {
            $stmt = $this->dbAdapter->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->fetchColumn() !== false;
        }

        // WordPress wpdb
        if (isset($this->dbAdapter->get_results)) {
            $result = $this->dbAdapter->get_results(
                "SHOW TABLES LIKE '" . $this->dbAdapter->esc_like($table) . "'",
                ARRAY_N
            );
            return !empty($result);
        }

        return false;
    }

    /**
     * Platform-agnostic SQL execution
     */
    private function execute($sql, $params = [])
    {
        // PDO
        if ($this->dbAdapter instanceof \PDO) {
            if (!empty($params)) {
                $stmt = $this->dbAdapter->prepare($sql);
                $stmt->execute($params);
            } else {
                $this->dbAdapter->exec($sql);
            }
        }
        // WordPress wpdb
        elseif (isset($this->dbAdapter->get_results)) {
            if (!empty($params)) {
                $this->dbAdapter->query(
                    $this->dbAdapter->prepare($sql, $params)
                );
            } else {
                $this->dbAdapter->query($sql);
            }
        }
    }
}
