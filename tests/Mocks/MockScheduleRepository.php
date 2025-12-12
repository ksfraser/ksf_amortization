<?php

namespace Tests\Mocks;

use Ksfraser\Amortizations\Repositories\ScheduleRepository;
use DateTimeImmutable;

/**
 * Mock ScheduleRepository for integration testing
 *
 * @implements ScheduleRepository
 */
class MockScheduleRepository implements ScheduleRepository
{
    private array $schedules = [];

    public function saveSchedule(int $loanId, array $schedule): int
    {
        $this->schedules[$loanId] = $schedule;
        return count($schedule);
    }

    public function getScheduleForLoan(int $loanId): array
    {
        return $this->schedules[$loanId] ?? [];
    }

    public function getScheduleRow(int $loanId, int $paymentNumber): ?array
    {
        $schedule = $this->schedules[$loanId] ?? [];
        foreach ($schedule as $row) {
            if ($row['payment_number'] === $paymentNumber) {
                return $row;
            }
        }
        return null;
    }

    public function getRemainingSchedule(int $loanId, int $afterPaymentNumber): array
    {
        $schedule = $this->schedules[$loanId] ?? [];
        return array_filter($schedule, fn($row) => $row['payment_number'] > $afterPaymentNumber);
    }

    public function getScheduleByDateRange(int $loanId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $schedule = $this->schedules[$loanId] ?? [];
        return array_filter($schedule, function ($row) use ($startDate, $endDate) {
            $date = new DateTimeImmutable($row['payment_date']);
            return $date >= $startDate && $date <= $endDate;
        });
    }

    public function deleteSchedule(int $loanId): int
    {
        $count = count($this->schedules[$loanId] ?? []);
        unset($this->schedules[$loanId]);
        return $count;
    }

    public function getNextPaymentDate(int $loanId): ?DateTimeImmutable
    {
        $schedule = $this->schedules[$loanId] ?? [];
        if (empty($schedule)) {
            return null;
        }
        return new DateTimeImmutable($schedule[0]['payment_date']);
    }

    public function getPayoffAmount(int $loanId): float
    {
        $schedule = $this->schedules[$loanId] ?? [];
        if (empty($schedule)) {
            return 0;
        }
        $first = $schedule[0];
        return $first['balance'] + $first['interest'];
    }

    public function getTotalInterest(int $loanId): float
    {
        $schedule = $this->schedules[$loanId] ?? [];
        $total = 0;
        foreach ($schedule as $row) {
            $total += $row['interest'];
        }
        return $total;
    }
}
