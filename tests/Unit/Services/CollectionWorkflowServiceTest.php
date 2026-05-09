<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Ksfraser\Amortizations\Repositories\DelinquencyRepository;
use Ksfraser\Amortizations\Services\CollectionWorkflowService;

class CollectionWorkflowServiceTest extends TestCase
{
    public function testCreateNextActionForThirtyDayPastDueLoan(): void
    {
        $repository = new InMemoryDelinquencyRepository();
        $repository->seedStatus(1001, [
            'loan_id' => 1001,
            'status' => '30_DAYS_PAST_DUE',
            'days_overdue' => 35,
            'next_action_date' => '2026-05-01',
        ]);

        $service = new CollectionWorkflowService($repository);
        $result = $service->createNextAction(1001, 'collector-a');

        $this->assertTrue($result['created']);
        $this->assertSame('courtesy_reminder', $result['action_type']);
        $this->assertSame('collector-a', $result['assigned_to']);

        $actions = $repository->getCollectionActions(1001);
        $this->assertCount(1, $actions);
        $this->assertSame('courtesy_reminder', $actions[0]['action_type']);
    }

    public function testCreatePaymentArrangementForSixtyDayPastDueLoan(): void
    {
        $repository = new InMemoryDelinquencyRepository();
        $repository->seedStatus(1002, [
            'loan_id' => 1002,
            'status' => '60_DAYS_PAST_DUE',
            'days_overdue' => 62,
        ]);

        $service = new CollectionWorkflowService($repository);
        $result = $service->createPaymentArrangement(1002, [
            'modified_payment' => 150.00,
            'duration_days' => 45,
            'created_by' => 'collector-b',
        ]);

        $this->assertTrue($result['created']);
        $this->assertSame('active', $result['arrangement']['status']);
        $this->assertSame(150.00, $result['arrangement']['modified_payment']);

        $activeArrangement = $repository->getActiveArrangement(1002);
        $this->assertNotNull($activeArrangement);
        $this->assertSame('collector-b', $activeArrangement['created_by']);
    }

    public function testCurrentLoanDoesNotCreateCollectionAction(): void
    {
        $repository = new InMemoryDelinquencyRepository();
        $repository->seedStatus(1003, [
            'loan_id' => 1003,
            'status' => 'CURRENT',
            'days_overdue' => 0,
        ]);

        $service = new CollectionWorkflowService($repository);
        $result = $service->createNextAction(1003);

        $this->assertFalse($result['created']);
        $this->assertSame('Loan is current', $result['reason']);
        $this->assertCount(0, $repository->getCollectionActions(1003));
    }

    public function testProcessDueActionsCreatesFormalNoticeForNinetyDayLoan(): void
    {
        $repository = new InMemoryDelinquencyRepository();
        $repository->seedStatus(1004, [
            'loan_id' => 1004,
            'status' => '90_PLUS_DAYS_PAST_DUE',
            'days_overdue' => 95,
            'next_action_date' => '2026-05-01',
        ]);
        $repository->seedStatus(1005, [
            'loan_id' => 1005,
            'status' => 'CURRENT',
            'days_overdue' => 0,
            'next_action_date' => '2026-06-01',
        ]);

        $service = new CollectionWorkflowService($repository);
        $results = $service->processDueActions('collector-c');

        $this->assertCount(1, $results);
        $this->assertSame('formal_collection_notice', $results[0]['action_type']);
        $this->assertSame('collector-c', $results[0]['assigned_to']);
    }
}

class InMemoryDelinquencyRepository implements DelinquencyRepository
{
    /** @var array<int, array<string, mixed>> */
    private array $statuses = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    private array $actions = [];

    /** @var array<int, array<string, mixed>> */
    private array $arrangements = [];

    private int $nextStatusId = 1;
    private int $nextActionId = 1;
    private int $nextArrangementId = 1;

    /** @param array<string, mixed> $data */
    public function seedStatus(int $loanId, array $data): void
    {
        $this->statuses[$loanId] = $data + ['id' => $this->nextStatusId++];
    }

    public function saveDelinquencyStatus(int $loanId, array $delinquencyData): int
    {
        $this->statuses[$loanId] = $delinquencyData + ['id' => $this->nextStatusId++, 'loan_id' => $loanId];
        return $this->statuses[$loanId]['id'];
    }

    public function getDelinquencyStatus(int $loanId): ?array
    {
        return $this->statuses[$loanId] ?? null;
    }

    public function getLoansByStatus(string $status, ?int $limit = null, ?int $offset = null): array
    {
        return array_values(array_filter($this->statuses, static function (array $row) use ($status): bool {
            return ($row['status'] ?? null) === $status;
        }));
    }

    public function getLoansByRiskLevel(string $riskLevel, ?int $limit = null, ?int $offset = null): array
    {
        return array_values(array_filter($this->statuses, static function (array $row) use ($riskLevel): bool {
            return ($row['risk_level'] ?? null) === $riskLevel;
        }));
    }

    public function getLoansByPaymentPattern(string $patternType): array
    {
        return array_values(array_filter($this->statuses, static function (array $row) use ($patternType): bool {
            return ($row['pattern_type'] ?? null) === $patternType;
        }));
    }

    public function getLoansDueForAction(?int $limit = null, ?int $offset = null): array
    {
        $today = '2026-05-08';
        return array_values(array_filter($this->statuses, static function (array $row) use ($today): bool {
            return isset($row['next_action_date']) && $row['next_action_date'] <= $today && ($row['status'] ?? 'CURRENT') !== 'CURRENT';
        }));
    }

    public function getCountByStatus(): array
    {
        $counts = [];
        foreach ($this->statuses as $row) {
            $status = $row['status'] ?? 'UNKNOWN';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }
        return $counts;
    }

    public function getCountByRiskLevel(): array
    {
        $counts = [];
        foreach ($this->statuses as $row) {
            $riskLevel = $row['risk_level'] ?? 'UNKNOWN';
            $counts[$riskLevel] = ($counts[$riskLevel] ?? 0) + 1;
        }
        return $counts;
    }

    public function recordCollectionAction(int $loanId, array $actionData): int
    {
        $actionData['id'] = $this->nextActionId++;
        $actionData['loan_id'] = $loanId;
        $this->actions[$loanId][] = $actionData;
        return $actionData['id'];
    }

    public function getCollectionActions(int $loanId, ?int $limit = null): array
    {
        return $this->actions[$loanId] ?? [];
    }

    public function getMostRecentAction(int $loanId): ?array
    {
        if (empty($this->actions[$loanId])) {
            return null;
        }
        return $this->actions[$loanId][count($this->actions[$loanId]) - 1];
    }

    public function savePaymentArrangement(int $loanId, array $arrangementData): int
    {
        $arrangementData['id'] = $this->nextArrangementId++;
        $arrangementData['loan_id'] = $loanId;
        $this->arrangements[$loanId] = $arrangementData;
        return $arrangementData['id'];
    }

    public function getActiveArrangement(int $loanId): ?array
    {
        $arrangement = $this->arrangements[$loanId] ?? null;
        if ($arrangement === null) {
            return null;
        }
        return ($arrangement['status'] ?? null) === 'active' ? $arrangement : null;
    }

    public function getPortfolioStatistics(): array
    {
        return ['total_loans' => count($this->statuses)];
    }

    public function getRiskScoreDistribution(int $bucketSize = 10): array
    {
        return [];
    }

    public function updateDelinquencyStatus(int $loanId, array $updateData): bool
    {
        if (!isset($this->statuses[$loanId])) {
            return false;
        }
        $this->statuses[$loanId] = array_merge($this->statuses[$loanId], $updateData);
        return true;
    }

    public function clearDelinquencyStatus(int $loanId): bool
    {
        unset($this->statuses[$loanId], $this->actions[$loanId], $this->arrangements[$loanId]);
        return true;
    }
}
