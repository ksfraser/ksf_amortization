<?php
namespace Ksfraser\Amortizations;

use Ksfraser\Amortizations\LoanType;
use Ksfraser\Amortizations\InterestCalcFrequency;

class SelectorProvider {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function getLoanTypes(): array {
        $result = $this->db->query("SELECT * FROM ksf_amort_loan_types ORDER BY name ASC");
        $types = [];
        while ($row = $this->db->fetch_assoc($result)) {
            $types[] = new LoanType($row);
        }
        return $types;
    }
    public function getInterestCalcFrequencies(): array {
        $result = $this->db->query("SELECT * FROM ksf_amort_interest_calc_frequencies ORDER BY name ASC");
        $freqs = [];
        while ($row = $this->db->fetch_assoc($result)) {
            $freqs[] = new InterestCalcFrequency($row);
        }
        return $freqs;
    }
    public function addLoanType($name, $description = ''): void {
        $sql = "INSERT INTO ksf_amort_loan_types (name, description) VALUES ('" . $this->db->escape($name) . "', '" . $this->db->escape($description) . "')";
        $this->db->query($sql);
    }
    public function updateLoanType($id, $name, $description = ''): void {
        $sql = "UPDATE ksf_amort_loan_types SET name = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($description) . "' WHERE id = '" . $this->db->escape($id) . "'";
        $this->db->query($sql);
    }
    public function deleteLoanType($id): void {
        $sql = "DELETE FROM ksf_amort_loan_types WHERE id = '" . $this->db->escape($id) . "'";
        $this->db->query($sql);
    }
    public function addInterestCalcFrequency($name, $description = ''): void {
        $sql = "INSERT INTO ksf_amort_interest_calc_frequencies (name, description) VALUES ('" . $this->db->escape($name) . "', '" . $this->db->escape($description) . "')";
        $this->db->query($sql);
    }
    public function updateInterestCalcFrequency($id, $name, $description = ''): void {
        $sql = "UPDATE ksf_amort_interest_calc_frequencies SET name = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($description) . "' WHERE id = '" . $this->db->escape($id) . "'";
        $this->db->query($sql);
    }
    public function deleteInterestCalcFrequency($id): void {
        $sql = "DELETE FROM ksf_amort_interest_calc_frequencies WHERE id = '" . $this->db->escape($id) . "'";
        $this->db->query($sql);
    }
}
