<?php

namespace Ksfraser\Amortizations\Repositories;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO-backed implementation of DelinquencyRepository.
 *
 * Maps to tables:
 *   0_ksf_delinquency_status
 *   0_ksf_collection_actions
 *   0_ksf_payment_arrangement
 */
class PdoDelinquencyRepository implements DelinquencyRepository
{
    /** @var PDO */
    private $pdo;

    /** @var string Table prefix (default: '0_ksf_') */
    private $prefix;

    public function __construct(PDO $pdo, string $prefix = '0_ksf_')
    {
        $this->pdo    = $pdo;
        $this->prefix = $prefix;
    }

    // ------------------------------------------------------------------
    // Delinquency Status
    // ------------------------------------------------------------------

    public function saveDelinquencyStatus(int $loanId, array $data): int
    {
        $table = $this->prefix . 'delinquency_status';

        $existing = $this->getDelinquencyStatus($loanId);

        if ($existing !== null) {
            $this->updateDelinquencyStatus($loanId, $data);
            return (int) $existing['id'];
        }

        $sql = "INSERT INTO {$table}
                    (loan_id, status, days_overdue, missed_payments, risk_score, risk_level,
                     pattern_type, trend, on_time_percentage, late_percentage, missed_percentage,
                     next_action_date, last_action, last_action_date)
                VALUES
                    (:loan_id, :status, :days_overdue, :missed_payments, :risk_score, :risk_level,
                     :pattern_type, :trend, :on_time_percentage, :late_percentage, :missed_percentage,
                     :next_action_date, :last_action, :last_action_date)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':loan_id'            => $loanId,
                ':status'             => $data['status'] ?? 'CURRENT',
                ':days_overdue'       => $data['days_overdue'] ?? 0,
                ':missed_payments'    => $data['missed_payments'] ?? 0,
                ':risk_score'         => $data['risk_score'] ?? 0,
                ':risk_level'         => $data['risk_level'] ?? 'LOW',
                ':pattern_type'       => $data['pattern_type'] ?? null,
                ':trend'              => $data['trend'] ?? null,
                ':on_time_percentage' => $data['on_time_percentage'] ?? 100.0,
                ':late_percentage'    => $data['late_percentage'] ?? 0.0,
                ':missed_percentage'  => $data['missed_percentage'] ?? 0.0,
                ':next_action_date'   => $data['next_action_date'] ?? null,
                ':last_action'        => $data['last_action'] ?? null,
                ':last_action_date'   => $data['last_action_date'] ?? null,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException('saveDelinquencyStatus failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getDelinquencyStatus(int $loanId): ?array
    {
        $table = $this->prefix . 'delinquency_status';
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE loan_id = :loan_id LIMIT 1");
            $stmt->execute([':loan_id' => $loanId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            throw new RuntimeException('getDelinquencyStatus failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function updateDelinquencyStatus(int $loanId, array $data): bool
    {
        $table   = $this->prefix . 'delinquency_status';
        $allowed = [
            'status', 'days_overdue', 'missed_payments', 'risk_score', 'risk_level',
            'pattern_type', 'trend', 'on_time_percentage', 'late_percentage',
            'missed_percentage', 'next_action_date', 'last_action', 'last_action_date',
        ];

        $sets   = [];
        $params = [':loan_id' => $loanId];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]         = "{$col} = :{$col}";
                $params[":{$col}"] = $data[$col];
            }
        }

        if (empty($sets)) {
            return true;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE loan_id = :loan_id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('updateDelinquencyStatus failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function clearDelinquencyStatus(int $loanId): bool
    {
        $table = $this->prefix . 'delinquency_status';
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE loan_id = :loan_id");
            $stmt->execute([':loan_id' => $loanId]);
            return true;
        } catch (PDOException $e) {
            throw new RuntimeException('clearDelinquencyStatus failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // ------------------------------------------------------------------
    // Querying loans by delinquency attributes
    // ------------------------------------------------------------------

    public function getLoansByStatus(string $status, ?int $limit = null, ?int $offset = null): array
    {
        return $this->queryByColumn('status', $status, $limit, $offset);
    }

    public function getLoansByRiskLevel(string $riskLevel, ?int $limit = null, ?int $offset = null): array
    {
        return $this->queryByColumn('risk_level', $riskLevel, $limit, $offset);
    }

    public function getLoansByPaymentPattern(string $patternType): array
    {
        return $this->queryByColumn('pattern_type', $patternType);
    }

    public function getLoansDueForAction(?int $limit = null, ?int $offset = null): array
    {
        $table = $this->prefix . 'delinquency_status';
        $sql   = "SELECT * FROM {$table} WHERE next_action_date <= CURDATE() AND status <> 'CURRENT'
                  ORDER BY next_action_date ASC";
        $sql   .= $this->limitClause($limit, $offset);

        try {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('getLoansDueForAction failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // ------------------------------------------------------------------
    // Collection Actions
    // ------------------------------------------------------------------

    public function recordCollectionAction(int $loanId, array $actionData): int
    {
        $table = $this->prefix . 'collection_actions';
        $sql   = "INSERT INTO {$table}
                    (loan_id, action_type, description, action_date, due_date,
                     result, notes, assigned_to, next_action)
                  VALUES
                    (:loan_id, :action_type, :description, :action_date, :due_date,
                     :result, :notes, :assigned_to, :next_action)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':loan_id'     => $loanId,
                ':action_type' => $actionData['action_type'],
                ':description' => $actionData['description'] ?? null,
                ':action_date' => $actionData['action_date'] ?? date('Y-m-d H:i:s'),
                ':due_date'    => $actionData['due_date'] ?? null,
                ':result'      => $actionData['result'] ?? 'pending',
                ':notes'       => $actionData['notes'] ?? null,
                ':assigned_to' => $actionData['assigned_to'] ?? null,
                ':next_action' => $actionData['next_action'] ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException('recordCollectionAction failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getCollectionActions(int $loanId, ?int $limit = null): array
    {
        $table = $this->prefix . 'collection_actions';
        $sql   = "SELECT * FROM {$table} WHERE loan_id = :loan_id ORDER BY action_date DESC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':loan_id' => $loanId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('getCollectionActions failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getMostRecentAction(int $loanId): ?array
    {
        $rows = $this->getCollectionActions($loanId, 1);
        return $rows[0] ?? null;
    }

    // ------------------------------------------------------------------
    // Payment Arrangements
    // ------------------------------------------------------------------

    public function savePaymentArrangement(int $loanId, array $arrangementData): int
    {
        $table = $this->prefix . 'payment_arrangement';
        $sql   = "INSERT INTO {$table}
                    (loan_id, arrangement_type, status, start_date, end_date,
                     modified_payment, modified_term, description, created_date, created_by)
                  VALUES
                    (:loan_id, :arrangement_type, :status, :start_date, :end_date,
                     :modified_payment, :modified_term, :description, :created_date, :created_by)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':loan_id'           => $loanId,
                ':arrangement_type'  => $arrangementData['arrangement_type'] ?? 'payment_plan',
                ':status'            => $arrangementData['status'] ?? 'active',
                ':start_date'        => $arrangementData['start_date'] ?? date('Y-m-d'),
                ':end_date'          => $arrangementData['end_date'] ?? null,
                ':modified_payment'  => $arrangementData['modified_payment'] ?? null,
                ':modified_term'     => $arrangementData['modified_term'] ?? null,
                ':description'       => $arrangementData['description'] ?? null,
                ':created_date'      => $arrangementData['created_date'] ?? date('Y-m-d H:i:s'),
                ':created_by'        => $arrangementData['created_by'] ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new RuntimeException('savePaymentArrangement failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getActiveArrangement(int $loanId): ?array
    {
        $table = $this->prefix . 'payment_arrangement';
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM {$table} WHERE loan_id = :loan_id AND status = 'active'
                 ORDER BY created_date DESC LIMIT 1"
            );
            $stmt->execute([':loan_id' => $loanId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            throw new RuntimeException('getActiveArrangement failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // ------------------------------------------------------------------
    // Portfolio / Aggregates
    // ------------------------------------------------------------------

    public function getCountByStatus(): array
    {
        $table = $this->prefix . 'delinquency_status';
        try {
            $stmt   = $this->pdo->query("SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status");
            $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($rows as $row) {
                $result[$row['status']] = (int) $row['cnt'];
            }
            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('getCountByStatus failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getCountByRiskLevel(): array
    {
        $table = $this->prefix . 'delinquency_status';
        try {
            $stmt   = $this->pdo->query("SELECT risk_level, COUNT(*) AS cnt FROM {$table} GROUP BY risk_level");
            $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($rows as $row) {
                $result[$row['risk_level']] = (int) $row['cnt'];
            }
            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('getCountByRiskLevel failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPortfolioStatistics(): array
    {
        $table = $this->prefix . 'delinquency_status';
        try {
            $stmt = $this->pdo->query(
                "SELECT
                    COUNT(*) AS total_loans,
                    SUM(status = 'CURRENT') AS current_count,
                    SUM(status = '30_DAYS_PAST_DUE') AS `30_day_count`,
                    SUM(status = '60_DAYS_PAST_DUE') AS `60_day_count`,
                    SUM(status = '90_PLUS_DAYS_PAST_DUE') AS `90_day_count`,
                    SUM(risk_level IN ('HIGH','CRITICAL')) AS high_risk_count,
                    AVG(days_overdue) AS average_days_overdue
                 FROM {$table}"
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $total = max(1, (int) $row['total_loans']);

            return [
                'total_loans'          => (int) $row['total_loans'],
                'current_count'        => (int) $row['current_count'],
                'current_percentage'   => round((int) $row['current_count'] / $total * 100, 2),
                '30_day_count'         => (int) $row['30_day_count'],
                '30_day_percentage'    => round((int) $row['30_day_count'] / $total * 100, 2),
                '60_day_count'         => (int) $row['60_day_count'],
                '60_day_percentage'    => round((int) $row['60_day_count'] / $total * 100, 2),
                '90_day_count'         => (int) $row['90_day_count'],
                '90_day_percentage'    => round((int) $row['90_day_count'] / $total * 100, 2),
                'high_risk_count'      => (int) $row['high_risk_count'],
                'average_days_overdue' => round((float) $row['average_days_overdue'], 1),
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('getPortfolioStatistics failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getRiskScoreDistribution(int $bucketSize = 10): array
    {
        $table = $this->prefix . 'delinquency_status';
        $safe  = max(1, (int) $bucketSize);

        try {
            $sql  = "SELECT FLOOR(risk_score / {$safe}) * {$safe} AS bucket, COUNT(*) AS cnt
                     FROM {$table}
                     GROUP BY bucket
                     ORDER BY bucket ASC";
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($rows as $row) {
                $low   = (int) $row['bucket'];
                $high  = $low + $safe - 1;
                $key   = "{$low}-{$high}";
                $result[$key] = (int) $row['cnt'];
            }
            return $result;
        } catch (PDOException $e) {
            throw new RuntimeException('getRiskScoreDistribution failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function queryByColumn(string $column, string $value, ?int $limit = null, ?int $offset = null): array
    {
        $table = $this->prefix . 'delinquency_status';
        $sql   = "SELECT * FROM {$table} WHERE {$column} = :val ORDER BY updated_at DESC";
        $sql   .= $this->limitClause($limit, $offset);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':val' => $value]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("queryByColumn({$column}) failed: " . $e->getMessage(), 0, $e);
        }
    }

    private function limitClause(?int $limit, ?int $offset = null): string
    {
        if ($limit === null) {
            return '';
        }
        $clause = ' LIMIT ' . (int) $limit;
        if ($offset !== null) {
            $clause .= ' OFFSET ' . (int) $offset;
        }
        return $clause;
    }
}
