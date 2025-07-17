<?php
namespace Ksfraser\Amortizations\FA;

use Ksfraser\Amortizations\DataProviderInterface;

/**
 * FrontAccounting adaptor for Amortization business logic.
 * Implements DataProviderInterface for FA integration.
 */
class FADataProvider implements DataProviderInterface
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * FADataProvider constructor.
     * @param \PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Insert a loan record into fa_loans
     * @param array $data Loan data
     * @return int Loan ID
     */
    public function insertLoan(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO fa_loans (loan_type, description, principal, interest_rate, term_months, repayment_schedule, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['loan_type'],
            $data['description'],
            $data['principal'],
            $data['interest_rate'],
            $data['term_months'],
            $data['repayment_schedule'],
            $data['start_date'],
            $data['end_date'],
            $data['created_by']
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Get a loan record from fa_loans
     * @param int $loan_id Loan ID
     * @return array Loan data
     */
    public function getLoan(int $loan_id): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM fa_loans WHERE id = ?");
        $stmt->execute([$loan_id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    /**
     * Insert a payment schedule row into fa_amortization_staging
     * @param int $loan_id Loan ID
     * @param array $schedule_row Payment schedule data
     */
    public function insertSchedule(int $loan_id, array $schedule_row): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO fa_amortization_staging (loan_id, payment_date, payment_amount, principal_portion, interest_portion, remaining_balance) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $loan_id,
            $schedule_row['payment_date'],
            $schedule_row['payment_amount'],
            $schedule_row['principal_portion'],
            $schedule_row['interest_portion'],
            $schedule_row['remaining_balance']
        ]);
    }
}
