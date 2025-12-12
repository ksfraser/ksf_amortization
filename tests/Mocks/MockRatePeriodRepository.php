<?php

namespace Tests\Mocks;

use Ksfraser\Amortizations\Models\RatePeriod;
use Ksfraser\Amortizations\Repositories\RatePeriodRepository;
use DateTimeImmutable;

/**
 * Mock RatePeriodRepository for integration testing
 *
 * @implements RatePeriodRepository
 */
class MockRatePeriodRepository implements RatePeriodRepository
{
    private array $ratePeriods = [];
    private int $nextId = 1;
    private array $variableRates = [];

    public function findById(int $ratePeriodId): ?RatePeriod
    {
        return $this->ratePeriods[$ratePeriodId] ?? null;
    }

    public function findByLoanId(int $loanId): array
    {
        return array_filter($this->ratePeriods, fn($rp) => $rp->getLoanId() === $loanId);
    }

    public function findActiveOnDate(int $loanId, DateTimeImmutable $date): ?RatePeriod
    {
        $periods = $this->findByLoanId($loanId);
        foreach ($periods as $period) {
            if ($period->isActive($date)) {
                return $period;
            }
        }
        return null;
    }

    public function save(RatePeriod $ratePeriod): int
    {
        if ($ratePeriod->getId() === null) {
            $id = $this->nextId++;
            $ratePeriod->setId($id);
        }
        $this->ratePeriods[$ratePeriod->getId()] = $ratePeriod;
        return $ratePeriod->getId();
    }

    public function delete(int $ratePeriodId): bool
    {
        unset($this->ratePeriods[$ratePeriodId]);
        return true;
    }

    public function deleteByLoanId(int $loanId): int
    {
        $count = 0;
        foreach (array_keys($this->ratePeriods) as $id) {
            if ($this->ratePeriods[$id]->getLoanId() === $loanId) {
                unset($this->ratePeriods[$id]);
                $count++;
            }
        }
        return $count;
    }

    public function getCurrentRate(int $loanId): ?float
    {
        $period = $this->findActiveOnDate($loanId, new DateTimeImmutable());
        return $period?->getRate();
    }

    public function getNextRateChangeDate(int $loanId): ?DateTimeImmutable
    {
        $periods = $this->findByLoanId($loanId);
        $dates = [];
        foreach ($periods as $period) {
            if ($period->getEndDate() !== null) {
                $dates[] = $period->getEndDate();
            }
        }
        if (empty($dates)) {
            return null;
        }
        sort($dates);
        return $dates[0];
    }

    public function hasVariableRates(int $loanId): bool
    {
        return isset($this->variableRates[$loanId]) || count($this->findByLoanId($loanId)) > 0;
    }

    public function addRatePeriod(int $loanId): void
    {
        $this->variableRates[$loanId] = true;
    }
}
