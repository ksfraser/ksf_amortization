
<?php
// Amortization Model

<?php
namespace Ksfraser\Amortizations;

/**
 * Interface for data access abstraction
 */
interface DataProviderInterface {
    public function insertLoan(array $data): int;
    public function getLoan(int $loan_id): array;
    public function insertSchedule(int $loan_id, array $schedule_row): void;
    public function updateLoan(int $loan_id, array $data): void;
}

class AmortizationModel {
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
     * Update an existing loan
     * @param int $loan_id
     * @param array $data
     */
    public function updateLoan($loan_id, $data) {
        $this->db->updateLoan($loan_id, $data);
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
    /**
     * Calculate regular payment amount based on frequency and loan type
     * @param float $principal
     * @param float $rate
     * @param int $num_payments
     * @param string $interest_calc_frequency
     * @param string $loan_type
     * @return float
     */
    public function calculatePayment($principal, $rate, $num_payments, $interest_calc_frequency = 'monthly', $loan_type = 'auto') {
        switch (strtolower($interest_calc_frequency)) {
            case 'daily':
                $periods_per_year = 365;
                break;
            case 'weekly':
                $periods_per_year = 52;
                break;
            case 'bi-weekly':
                $periods_per_year = 26;
                break;
            case 'semi-monthly':
                $periods_per_year = 24;
                break;
            case 'monthly':
                $periods_per_year = 12;
                break;
            case 'semi-annual':
                $periods_per_year = 2;
                break;
            case 'annual':
                $periods_per_year = 1;
                break;
            default:
                $periods_per_year = 12;
        }
        $periodic_rate = $rate / 100 / $periods_per_year;
        if ($periodic_rate > 0) {
            return $principal * $periodic_rate / (1 - pow(1 + $periodic_rate, -$num_payments));
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
        $interest_calc_frequency = $loan['interest_calc_frequency'] ?? 'monthly';
        $loan_type = $loan['loan_type'] ?? 'auto';
        $payment = $loan['override_payment'] ? $loan['regular_payment'] : $this->calculatePayment($principal, $rate, $n, $interest_calc_frequency, $loan_type);
        $balance = $principal;
        $date = new \DateTime($loan['first_payment_date']);

        for ($i = 1; $i <= $n; $i++) {
            switch (strtolower($interest_calc_frequency)) {
                case 'daily':
                    $periodic_rate = $rate / 100 / 365;
                    $interest = $balance * $periodic_rate * 30; // Approximate for monthly payment
                    $date->modify('+1 month');
                    break;
                case 'semi-annual':
                    $periodic_rate = $rate / 100 / 2;
                    $interest = $balance * $periodic_rate / 6; // Approximate for monthly payment
                    $date->modify('+1 month');
                    break;
                case 'monthly':
                default:
                    $periodic_rate = $rate / 100 / 12;
                    $interest = $balance * $periodic_rate;
                    $date->modify('+1 month');
                    break;
            }
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
        }
    }
}
