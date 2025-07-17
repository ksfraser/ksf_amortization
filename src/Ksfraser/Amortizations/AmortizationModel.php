<?php
namespace Ksfraser\Amortizations;

/**
 * Amortization business logic
 * @package Ksfraser\Amortizations
 * @author ksfraser
 *
 * UML:
 * ```
 * class AmortizationModel {
 *   - db: DataProviderInterface
 *   + __construct(db: DataProviderInterface)
 *   + createLoan(data: array): int
 *   + getLoan(loan_id: int): array
 *   + calculatePayment(principal: float, rate: float, num_payments: int): float
 *   + calculateSchedule(loan_id: int): void
 * }
 * ```
 */
class AmortizationModel {
    /**
     * @var DataProviderInterface
     */
    private $db;

    public function __construct(DataProviderInterface $db) {
        $this->db = $db;
    }

    /**
     * Create a new loan
     * @param array $data
     * @return int Loan ID
     */
    public function createLoan($data) {
        return $this->db->insertLoan($data);
    }

    /**
     * Retrieve loan by ID
     * @param int $loan_id
     * @return array
     */
    public function getLoan($loan_id) {
        return $this->db->getLoan($loan_id);
    }

    /**
     * Calculate regular payment amount
     * @param float $principal
     * @param float $rate
     * @param int $num_payments
     * @return float
     */
    public function calculatePayment($principal, $rate, $num_payments) {
        $monthly_rate = $rate / 100 / 12;
        if ($monthly_rate > 0) {
            return $principal * $monthly_rate / (1 - pow(1 + $monthly_rate, -$num_payments));
        } else {
            return $principal / $num_payments;
        }
    }

    /**
     * Calculate amortization schedule and populate staging table
     * @param int $loan_id
     */
    public function calculateSchedule($loan_id) {
        $loan = $this->getLoan($loan_id);
        $principal = $loan['amount_financed'];
        $rate = $loan['interest_rate'];
        $n = $loan['num_payments'];
        $payment = $loan['override_payment'] ? $loan['regular_payment'] : $this->calculatePayment($principal, $rate, $n);
        $balance = $principal;
        $date = new \DateTime($loan['first_payment_date']);

        for ($i = 1; $i <= $n; $i++) {
            $monthly_rate = $rate / 100 / 12;
            $interest = $balance * $monthly_rate;
            $principal_portion = $payment - $interest;
            $balance -= $principal_portion;
            $row = [
                'payment_date' => $date->format('Y-m-d'),
                'payment_amount' => round($payment, 2),
                'principal_portion' => round($principal_portion, 2),
                'interest_portion' => round($interest, 2),
                'remaining_balance' => round(max($balance, 0), 2)
            ];
            $this->db->insertSchedule($loan_id, $row);
            $date->modify('+1 month'); // TODO: adjust for payment frequency
        }
    }
}
