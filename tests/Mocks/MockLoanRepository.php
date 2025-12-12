<?php

namespace Tests\Mocks;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Repositories\LoanRepository;

/**
 * Mock LoanRepository for integration testing
 *
 * @implements LoanRepository
 */
class MockLoanRepository implements LoanRepository
{
    private array $loans = [];
    private int $nextId = 1;

    public function findById(int $loanId): ?Loan
    {
        return $this->loans[$loanId] ?? null;
    }

    public function findByBorrowerId(int $borrowerId): array
    {
        return [];
    }

    public function findByStatus(string $status): array
    {
        return [];
    }

    public function save(Loan $loan): int
    {
        if ($loan->getId() === null) {
            $id = $this->nextId++;
            $loan->setId($id);
            $this->loans[$id] = $loan;
            return $id;
        }
        $this->loans[$loan->getId()] = $loan;
        return $loan->getId();
    }

    public function delete(int $loanId): bool
    {
        unset($this->loans[$loanId]);
        return true;
    }

    public function countActive(): int
    {
        return count($this->loans);
    }

    public function getTotalActiveBalance(): float
    {
        $total = 0;
        foreach ($this->loans as $loan) {
            $total += $loan->getPrincipal();
        }
        return $total;
    }

    public function findDueOnDate(\DateTimeImmutable $date): array
    {
        return [];
    }
}
