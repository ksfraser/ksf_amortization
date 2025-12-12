<?php

namespace Tests\Mocks;

use Ksfraser\Amortizations\Models\Arrears;
use Ksfraser\Amortizations\Repositories\ArrearsRepository;

/**
 * Mock ArrearsRepository for integration testing
 *
 * @implements ArrearsRepository
 */
class MockArrearsRepository implements ArrearsRepository
{
    private array $arrears = [];
    private int $nextId = 1;

    public function findById(int $arrearsId): ?Arrears
    {
        return $this->arrears[$arrearsId] ?? null;
    }

    public function findByLoanId(int $loanId): array
    {
        return array_filter($this->arrears, fn($a) => $a->getLoanId() === $loanId);
    }

    public function findActiveByLoanId(int $loanId): array
    {
        return array_filter(
            $this->arrears,
            fn($a) => $a->getLoanId() === $loanId && !$a->isCleared()
        );
    }

    public function getTotalArrearsForLoan(int $loanId): float
    {
        $total = 0;
        foreach ($this->findByLoanId($loanId) as $arrears) {
            $total += $arrears->getPrincipalAmount() + $arrears->getInterestAmount();
        }
        return $total;
    }

    public function save(Arrears $arrears): int
    {
        if ($arrears->getId() === null) {
            $id = $this->nextId++;
            $arrears->setId($id);
        }
        $this->arrears[$arrears->getId()] = $arrears;
        return $arrears->getId();
    }

    public function delete(int $arrearsId): bool
    {
        unset($this->arrears[$arrearsId]);
        return true;
    }

    public function deleteByLoanId(int $loanId): int
    {
        $count = 0;
        foreach (array_keys($this->arrears) as $id) {
            if ($this->arrears[$id]->getLoanId() === $loanId) {
                unset($this->arrears[$id]);
                $count++;
            }
        }
        return $count;
    }

    public function getLoansWithActiveArrears(): array
    {
        $loans = [];
        foreach ($this->arrears as $arrears) {
            if (!$arrears->isCleared()) {
                $loans[] = $arrears->getLoanId();
            }
        }
        return array_unique($loans);
    }

    public function findByDaysOverdue(int $days): array
    {
        return array_filter($this->arrears, fn($a) => $a->getDaysOverdue() >= $days);
    }

    public function getTotalPenaltiesForLoan(int $loanId): float
    {
        $total = 0;
        foreach ($this->findByLoanId($loanId) as $arrears) {
            $total += $arrears->getPenaltyAmount();
        }
        return $total;
    }

    public function hasActiveArrears(int $loanId): bool
    {
        return count($this->findActiveByLoanId($loanId)) > 0;
    }
}
