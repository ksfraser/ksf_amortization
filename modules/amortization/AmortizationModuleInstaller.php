<?php
namespace Ksfraser\Amortizations;

/**
 * AmortizationModuleInstaller
 * Dynamically creates tables and pre-populates selector tables using the correct DB prefix for FA, WP, etc.
 */
class AmortizationModuleInstaller {
    private $dbAdapter;
    private $dbPrefix;

    /**
     * @param object $dbAdapter  Platform-specific DB adapter (PDO, wpdb, SuiteCRM, etc.)
     * @param string $dbPrefix   Table prefix determined by platform
     */
    public function __construct($dbAdapter, $dbPrefix) {
        $this->dbAdapter = $dbAdapter;
        $this->dbPrefix = $dbPrefix;
    }

    public function install() {
        $this->createSelectorTables();
        $this->populateSelectorTables();
    }

    private function createSelectorTables() {
        $sqls = [
            "CREATE TABLE {$this->dbPrefix}ksf_amort_loan_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(32) NOT NULL,
                description VARCHAR(128)
            )",
            "CREATE TABLE {$this->dbPrefix}ksf_amort_interest_calc_frequencies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(32) NOT NULL,
                description VARCHAR(128)
            )"
        ];
        foreach ($sqls as $sql) {
            $this->execute($sql);
        }
    }

    private function populateSelectorTables() {
        $loanTypes = [
            ['auto', 'Auto loan'],
            ['mortgage', 'Mortgage loan'],
            ['personal', 'Personal loan'],
            ['student', 'Student loan']
        ];
        $insertLoanType = "INSERT INTO {$this->dbPrefix}ksf_amort_loan_types (name, description) VALUES (?, ?)";
        foreach ($loanTypes as $row) {
            $this->execute($insertLoanType, $row);
        }

        $freqs = [
            ['monthly', 'Monthly'],
            ['annual', 'Annual'],
            ['daily', 'Daily'],
            ['semi-annual', 'Semi-annual']
        ];
        $insertFreq = "INSERT INTO {$this->dbPrefix}ksf_amort_interest_calc_frequencies (name, description) VALUES (?, ?)";
        foreach ($freqs as $row) {
            $this->execute($insertFreq, $row);
        }
    }

    /**
     * Platform-agnostic SQL execution
     */
    private function execute($sql, $params = []) {
        // PDO
        if ($this->dbAdapter instanceof \PDO) {
            if ($params) {
                $stmt = $this->dbAdapter->prepare($sql);
                $stmt->execute($params);
            } else {
                $this->dbAdapter->exec($sql);
            }
        }
        // WordPress wpdb
        elseif (isset($this->dbAdapter->query) && isset($this->dbAdapter->prepare)) {
            if ($params) {
                $sql = call_user_func_array([$this->dbAdapter, 'prepare'], array_merge([$sql], $params));
                $this->dbAdapter->query($sql);
            } else {
                $this->dbAdapter->query($sql);
            }
        }
        // Add SuiteCRM or other platform logic here
        else {
            throw new \Exception('Unsupported DB adapter');
        }
    }
}
