<?php

declare(strict_types=1);

namespace Tests\Unit\Persistence;

use Ksfraser\Amortizations\Persistence\Database;
use Ksfraser\Amortizations\Persistence\LoanRepository;
use Ksfraser\Amortizations\Persistence\PortfolioRepository;
use Ksfraser\Amortizations\Persistence\ApplicationRepository;
use Ksfraser\Amortizations\Persistence\PaymentScheduleRepository;
use Ksfraser\Amortizations\Persistence\AuditLogRepository;
use Ksfraser\Amortizations\Persistence\Migration;
use Ksfraser\Amortizations\Persistence\Schema;
use PHPUnit\Framework\TestCase;
use PDO;

class DatabasePersistenceTest extends TestCase
{
    private Database $db;
    private Schema $schema;
    private Migration $migration;

    protected function setUp(): void
    {
        // Use in-memory SQLite for testing
        $this->db = new Database('sqlite::memory:');
        $this->schema = new Schema($this->db);
        $this->migration = new Migration($this->db);

        // Create tables
        $this->schema->createLoansTable();
        $this->schema->createPortfoliosTable();
        $this->schema->createApplicationsTable();
        $this->schema->createPaymentSchedulesTable();
        $this->schema->createAuditLogsTable();
        $this->schema->createPortfolioLoansTable();
    }

    // ==================== Database Connection Tests ====================

    public function testDatabaseConnection(): void
    {
        $this->assertNotNull($this->db->getConnection());
        $this->assertInstanceOf(PDO::class, $this->db->getConnection());
    }

    public function testDatabaseInvalidConnection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Database('invalid_dsn');
    }

    // ==================== Transaction Tests ====================

    public function testBeginTransaction(): void
    {
        $this->assertTrue($this->db->beginTransaction());
        $this->assertTrue($this->db->inTransaction());
    }

    public function testCommitTransaction(): void
    {
        $this->db->beginTransaction();
        $this->assertTrue($this->db->commit());
        $this->assertFalse($this->db->inTransaction());
    }

    public function testRollbackTransaction(): void
    {
        $this->db->beginTransaction();
        $this->assertTrue($this->db->rollback());
        $this->assertFalse($this->db->inTransaction());
    }

    public function testNestedTransactions(): void
    {
        $this->db->beginTransaction();
        $this->assertTrue($this->db->inTransaction());

        $this->db->beginTransaction();
        $this->assertTrue($this->db->inTransaction());

        $this->db->commit();
        $this->assertTrue($this->db->inTransaction());

        $this->db->commit();
        $this->assertFalse($this->db->inTransaction());
    }

    // ==================== Loan Repository Tests ====================

    public function testCreateLoan(): void
    {
        $repo = new LoanRepository($this->db);
        $loanId = $repo->create([
            'loan_number' => 'LOAN001',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->assertNotNull($loanId);
        $this->assertGreaterThan(0, $loanId);
    }

    public function testFindLoan(): void
    {
        $repo = new LoanRepository($this->db);
        $loanId = $repo->create([
            'loan_number' => 'LOAN002',
            'borrower_id' => 1,
            'principal' => 150000,
            'interest_rate' => 4.5,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $loan = $repo->find($loanId);
        $this->assertNotNull($loan);
        $this->assertEquals('LOAN002', $loan['loan_number']);
        $this->assertEquals(150000, $loan['principal']);
    }

    public function testUpdateLoan(): void
    {
        $repo = new LoanRepository($this->db);
        $loanId = $repo->create([
            'loan_number' => 'LOAN003',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $repo->update($loanId, ['status' => 'paid_off']);
        $loan = $repo->find($loanId);

        $this->assertEquals('paid_off', $loan['status']);
    }

    public function testDeleteLoan(): void
    {
        $repo = new LoanRepository($this->db);
        $loanId = $repo->create([
            'loan_number' => 'LOAN004',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->assertTrue($repo->delete($loanId));
        $this->assertNull($repo->find($loanId));
    }

    public function testFindActiveLoan(): void
    {
        $repo = new LoanRepository($this->db);
        $repo->create([
            'loan_number' => 'LOAN005',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);
        $repo->create([
            'loan_number' => 'LOAN006',
            'borrower_id' => 2,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'closed',
        ]);

        $active = $repo->findActive();
        $this->assertEquals(1, count($active));
        $this->assertEquals('LOAN005', $active[0]['loan_number']);
    }

    public function testFindLoansByBorrower(): void
    {
        $repo = new LoanRepository($this->db);
        $repo->create([
            'loan_number' => 'LOAN007',
            'borrower_id' => 5,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);
        $repo->create([
            'loan_number' => 'LOAN008',
            'borrower_id' => 5,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $loans = $repo->findByBorrower(5);
        $this->assertEquals(2, count($loans));
    }

    // ==================== Portfolio Repository Tests ====================

    public function testCreatePortfolio(): void
    {
        $repo = new PortfolioRepository($this->db);
        $portfolioId = $repo->create([
            'name' => 'Test Portfolio',
            'manager_id' => 1,
            'description' => 'A test portfolio',
        ]);

        $this->assertNotNull($portfolioId);
        $this->assertGreaterThan(0, $portfolioId);
    }

    public function testFindPortfolioByManager(): void
    {
        $repo = new PortfolioRepository($this->db);
        $repo->create([
            'name' => 'Manager Portfolio',
            'manager_id' => 10,
            'description' => 'Portfolio for manager 10',
        ]);

        $portfolios = $repo->findByManager(10);
        $this->assertEquals(1, count($portfolios));
        $this->assertEquals('Manager Portfolio', $portfolios[0]['name']);
    }

    // ==================== Application Repository Tests ====================

    public function testCreateApplication(): void
    {
        $repo = new ApplicationRepository($this->db);
        $appId = $repo->create([
            'applicant_id' => 1,
            'loan_amount' => 250000,
            'loan_purpose' => 'Home Purchase',
            'status' => 'pending',
        ]);

        $this->assertNotNull($appId);
    }

    public function testFindPendingApplications(): void
    {
        $repo = new ApplicationRepository($this->db);
        $repo->create([
            'applicant_id' => 1,
            'loan_amount' => 250000,
            'loan_purpose' => 'Home Purchase',
            'status' => 'pending',
        ]);
        $repo->create([
            'applicant_id' => 2,
            'loan_amount' => 250000,
            'loan_purpose' => 'Home Purchase',
            'status' => 'approved',
        ]);

        $pending = $repo->findPending();
        $this->assertEquals(1, count($pending));
        $this->assertEquals('pending', $pending[0]['status']);
    }

    public function testFindApprovedApplications(): void
    {
        $repo = new ApplicationRepository($this->db);
        $repo->create([
            'applicant_id' => 1,
            'loan_amount' => 250000,
            'loan_purpose' => 'Home Purchase',
            'status' => 'approved',
        ]);

        $approved = $repo->findApproved();
        $this->assertEquals(1, count($approved));
    }

    // ==================== Payment Schedule Tests ====================

    public function testCreatePaymentSchedule(): void
    {
        $loanRepo = new LoanRepository($this->db);
        $loanId = $loanRepo->create([
            'loan_number' => 'LOAN100',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $schedRepo = new PaymentScheduleRepository($this->db);
        $schedId = $schedRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $this->assertNotNull($schedId);
    }

    public function testFindPaymentsByLoan(): void
    {
        $loanRepo = new LoanRepository($this->db);
        $loanId = $loanRepo->create([
            'loan_number' => 'LOAN101',
            'borrower_id' => 1,
            'principal' => 200000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $schedRepo = new PaymentScheduleRepository($this->db);
        $schedRepo->create([
            'loan_id' => $loanId,
            'payment_number' => 1,
            'due_date' => '2024-02-01',
            'payment_amount' => 1073.64,
            'principal' => 70.56,
            'interest' => 1003.08,
            'balance' => 199929.44,
            'status' => 'pending',
        ]);

        $schedules = $schedRepo->findByLoan($loanId);
        $this->assertEquals(1, count($schedules));
        $this->assertEquals($loanId, $schedules[0]['loan_id']);
    }

    // ==================== Audit Log Tests ====================

    public function testLogAction(): void
    {
        $repo = new AuditLogRepository($this->db);
        $logId = $repo->log('Loan', 1, 'create', ['principal' => 200000], 'user123');

        $this->assertNotNull($logId);
        $this->assertGreaterThan(0, $logId);
    }

    public function testGetEntityHistory(): void
    {
        $repo = new AuditLogRepository($this->db);
        $repo->log('Loan', 1, 'create', ['principal' => 200000], 'user123');
        $repo->log('Loan', 1, 'update', ['status' => 'active'], 'user123');

        $history = $repo->getHistory('Loan', 1);
        $this->assertEquals(2, count($history));
        $this->assertEquals('create', $history[0]['action']);
        $this->assertEquals('update', $history[1]['action']);
    }

    public function testGetUserActions(): void
    {
        $repo = new AuditLogRepository($this->db);
        $repo->log('Loan', 1, 'create', ['principal' => 200000], 'user456');
        $repo->log('Portfolio', 2, 'create', ['name' => 'Portfolio'], 'user456');

        $actions = $repo->getUserActions('user456');
        $this->assertEquals(2, count($actions));
    }

    // ==================== Count Tests ====================

    public function testCountRecords(): void
    {
        $repo = new LoanRepository($this->db);
        $repo->create([
            'loan_number' => 'LOAN200',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);
        $repo->create([
            'loan_number' => 'LOAN201',
            'borrower_id' => 2,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $count = $repo->count();
        $this->assertEquals(2, $count);
    }

    public function testCountWithCriteria(): void
    {
        $repo = new LoanRepository($this->db);
        $repo->create([
            'loan_number' => 'LOAN202',
            'borrower_id' => 3,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);
        $repo->create([
            'loan_number' => 'LOAN203',
            'borrower_id' => 3,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $count = $repo->count(['borrower_id' => 3]);
        $this->assertEquals(2, $count);
    }

    // ==================== Migration Tests ====================

    public function testMigrationTableCreation(): void
    {
        $this->migration->createMigrationsTable();
        // If no error, test passes
        $this->assertTrue(true);
    }

    public function testSchemaTableCreation(): void
    {
        // Tables already created in setUp
        $loanRepo = new LoanRepository($this->db);
        $loanId = $loanRepo->create([
            'loan_number' => 'TEST001',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $this->assertNotNull($loanId);
    }

    // ==================== Transaction with Persistence Tests ====================

    public function testTransactionWithCreate(): void
    {
        $repo = new LoanRepository($this->db);

        $this->db->beginTransaction();
        $loanId = $repo->create([
            'loan_number' => 'LOAN300',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);
        $this->db->commit();

        $loan = $repo->find($loanId);
        $this->assertNotNull($loan);
    }

    public function testTransactionRollbackDoesNotPersist(): void
    {
        $repo = new LoanRepository($this->db);

        $this->db->beginTransaction();
        $loanId = $repo->create([
            'loan_number' => 'LOAN301',
            'borrower_id' => 1,
            'principal' => 100000,
            'interest_rate' => 5.0,
            'term_months' => 360,
            'start_date' => '2024-01-01',
            'status' => 'active',
        ]);
        $this->db->rollback();

        $loan = $repo->find($loanId);
        $this->assertNull($loan);
    }
}
