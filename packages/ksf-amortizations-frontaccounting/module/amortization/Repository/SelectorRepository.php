<?php

namespace Ksfraser\Amortizations\Repository;

use PDO;
use Exception;

/**
 * SelectorRepository - Data Access Object for Selector Options
 * 
 * Encapsulates all database operations for selector option management.
 * Handles CRUD operations (Create, Read, Update, Delete) for selector options.
 * 
 * This class follows SRP (Single Responsibility Principle):
 * - Single responsibility: Database access for selector options
 * - Isolated from presentation logic (views)
 * - Isolated from business logic
 * - Reusable across different UI implementations (FA, WordPress, SuiteCRM)
 * 
 * Design Pattern: Repository Pattern
 * - Abstracts data access logic
 * - Centralizes database queries
 * - Makes testing easier
 * 
 * SOLID Principles:
 * - Single Responsibility: Only handles selector data access
 * - Open/Closed: Can be extended for additional queries
 * - Liskov Substitution: Could implement an interface
 * - Interface Segregation: Simple, focused methods
 * - Dependency Inversion: Depends on PDO abstraction (passed in)
 * 
 * Usage:
 * ```php
 * $repo = new SelectorRepository($db, 'ksf_selectors', $dbPrefix);
 * 
 * // Create
 * $repo->add($selectorName, $optionName, $optionValue);
 * 
 * // Read
 * $options = $repo->getAll();
 * 
 * // Update
 * $repo->update($id, $selectorName, $optionName, $optionValue);
 * 
 * // Delete
 * $repo->delete($id);
 * ```
 * 
 * @package    Ksfraser\Amortizations\Repository
 * @author     Kevin Fraser / GitHub Copilot
 * @since      20251220
 * @version    1.0.0
 */
class SelectorRepository
{
    /**
     * PDO database connection
     * 
     * @var PDO
     */
    protected $db;

    /**
     * Table name (without prefix)
     * 
     * @var string
     */
    protected $tableName;

    /**
     * Table prefix (e.g., '0_' for FrontAccounting)
     * 
     * @var string
     */
    protected $tablePrefix;

    /**
     * Full qualified table name (prefix + name)
     * 
     * @var string
     */
    protected $fullTableName;

    /**
     * Constructor
     * 
     * @param PDO    $db          PDO database connection
     * @param string $tableName   Table name without prefix (default: 'ksf_selectors')
     * @param string $tablePrefix Table prefix (default: '0_')
     */
    public function __construct(PDO $db, $tableName = 'ksf_selectors', $tablePrefix = '0_')
    {
        $this->db = $db;
        $this->tableName = $tableName;
        $this->tablePrefix = $tablePrefix;
        $this->fullTableName = $tablePrefix . $tableName;
    }

    /**
     * Add a new selector option
     * 
     * @param string $selectorName Name of the selector
     * @param string $optionName   Name of the option
     * @param string $optionValue  Value of the option
     * 
     * @return bool True on success, false on failure
     * 
     * @throws Exception If query execution fails
     */
    public function add($selectorName, $optionName, $optionValue)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO " . $this->fullTableName . " (selector_name, option_name, option_value) VALUES (?, ?, ?)"
            );
            return $stmt->execute([$selectorName, $optionName, $optionValue]);
        } catch (Exception $e) {
            throw new Exception("Failed to add selector option: " . $e->getMessage());
        }
    }

    /**
     * Update an existing selector option
     * 
     * @param int    $id            ID of the option to update
     * @param string $selectorName  Updated selector name
     * @param string $optionName    Updated option name
     * @param string $optionValue   Updated option value
     * 
     * @return bool True on success, false on failure
     * 
     * @throws Exception If query execution fails
     */
    public function update($id, $selectorName, $optionName, $optionValue)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE " . $this->fullTableName . " SET selector_name=?, option_name=?, option_value=? WHERE id=?"
            );
            return $stmt->execute([$selectorName, $optionName, $optionValue, $id]);
        } catch (Exception $e) {
            throw new Exception("Failed to update selector option: " . $e->getMessage());
        }
    }

    /**
     * Delete a selector option
     * 
     * @param int $id ID of the option to delete
     * 
     * @return bool True on success, false on failure
     * 
     * @throws Exception If query execution fails
     */
    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM " . $this->fullTableName . " WHERE id=?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            throw new Exception("Failed to delete selector option: " . $e->getMessage());
        }
    }

    /**
     * Retrieve all selector options
     * 
     * Sorted by selector_name and option_name for consistent ordering.
     * 
     * @return array Array of associative arrays with columns: id, selector_name, option_name, option_value
     * 
     * @throws Exception If query execution fails
     */
    public function getAll()
    {
        try {
            $result = $this->db->query(
                "SELECT * FROM " . $this->fullTableName . " ORDER BY selector_name, option_name"
            );
            return $result->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve selector options: " . $e->getMessage());
        }
    }

    /**
     * Retrieve selector options for a specific selector
     * 
     * @param string $selectorName Name of the selector to filter by
     * 
     * @return array Array of matching selector options
     * 
     * @throws Exception If query execution fails
     */
    public function getBySelectorName($selectorName)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM " . $this->fullTableName . " WHERE selector_name=? ORDER BY option_name"
            );
            $stmt->execute([$selectorName]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve selector options: " . $e->getMessage());
        }
    }

    /**
     * Retrieve a single selector option by ID
     * 
     * @param int $id Option ID
     * 
     * @return array|null Associative array if found, null if not found
     * 
     * @throws Exception If query execution fails
     */
    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM " . $this->fullTableName . " WHERE id=?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve selector option: " . $e->getMessage());
        }
    }

    /**
     * Get the full qualified table name
     * 
     * Useful for reference in queries or logging.
     * 
     * @return string Full table name (prefix + name)
     */
    public function getTableName()
    {
        return $this->fullTableName;
    }
}
