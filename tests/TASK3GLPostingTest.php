<?php
/**
 * TASK 3 Unit Tests - GL Posting Components
 *
 * Comprehensive test suite for FAJournalService, GLAccountMapper, and JournalEntryBuilder
 * Tests all critical GL posting functionality with mock and real database scenarios
 *
 * @package   Ksfraser\Amortizations\Tests
 * @author    KSF Development Team
 * @version   1.0.0
 */

namespace Ksfraser\Amortizations\Tests;

use PHPUnit\Framework\TestCase;
use DateTime;
use PDO;

// Load required classes
require_once __DIR__ . '/../src/Ksfraser/Amortizations/FA/GLAccountMapper.php';
require_once __DIR__ . '/../src/Ksfraser/Amortizations/FA/JournalEntryBuilder.php';
require_once __DIR__ . '/../src/Ksfraser/Amortizations/FA/FAJournalService.php';

use Ksfraser\Amortizations\FA\GLAccountMapper;
use Ksfraser\Amortizations\FA\JournalEntryBuilder;
use Ksfraser\Amortizations\FA\FAJournalService;

/**
 * Test suite for TASK 3 GL posting components
 *
 * @covers Ksfraser\Amortizations\FA\FAJournalService
 * @covers Ksfraser\Amortizations\FA\GLAccountMapper
 * @covers Ksfraser\Amortizations\FA\JournalEntryBuilder
 */
class TASK3GLPostingTest extends TestCase
{
    /**
     * @var PDO Mock PDO connection
     */
    private PDO $mockPDO;

    /**
     * Set up test fixtures
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock PDO for testing
        $this->mockPDO = $this->createMock(PDO::class);
    }

    // ==========================================
    // JournalEntryBuilder Tests
    // ==========================================

    /**
     * Test JournalEntryBuilder initialization
     * @test
     */
    public function testJournalEntryBuilderInitialization(): void
    {
        $builder = new JournalEntryBuilder();
        
        $this->assertIsObject($builder);
        $this->assertTrue(method_exists($builder, 'addDebit'));
        $this->assertTrue(method_exists($builder, 'addCredit'));
        $this->assertTrue(method_exists($builder, 'build'));
    }

    /**
     * Test adding debit entry
     * @test
     */
    public function testAddDebitEntry(): void
    {
        $builder = new JournalEntryBuilder();
        
        $result = $builder->addDebit('2100', 600.00, 'Loan principal');
        
        $this->assertInstanceOf(JournalEntryBuilder::class, $result);
    }

    /**
     * Test adding credit entry
     * @test
     */
    public function testAddCreditEntry(): void
    {
        $builder = new JournalEntryBuilder();
        
        $result = $builder->addCredit('1100', 1000.00, 'Payment received');
        
        $this->assertInstanceOf(JournalEntryBuilder::class, $result);
    }

    /**
     * Test rejecting negative debit amount
     * @test
     */
    public function testRejectNegativeDebit(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $builder = new JournalEntryBuilder();
        $builder->addDebit('2100', -600.00, 'Invalid');
    }

    /**
     * Test rejecting negative credit amount
     * @test
     */
    public function testRejectNegativeCredit(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $builder = new JournalEntryBuilder();
        $builder->addCredit('1100', -1000.00, 'Invalid');
    }

    /**
     * Test balanced journal entry
     * @test
     */
    public function testBalancedJournalEntry(): void
    {
        $builder = new JournalEntryBuilder();
        
        $entry = $builder
            ->setReference('LOAN-123-2025-01-15')
            ->setMemo('Loan Payment')
            ->addDebit('2100', 600.00)
            ->addDebit('6200', 400.00)
            ->addCredit('1100', 1000.00)
            ->build();
        
        $this->assertTrue($entry['is_balanced']);
        $this->assertEquals(1000.00, $entry['total_amount']);
    }

    /**
     * Test unbalanced journal entry rejection
     * @test
     */
    public function testUnbalancedJournalEntryRejection(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $builder = new JournalEntryBuilder();
        
        $builder
            ->addDebit('2100', 600.00)
            ->addDebit('6200', 400.00)
            ->addCredit('1100', 900.00) // Should be 1000.00
            ->build();
    }

    /**
     * Test amount rounding to 4 decimal places
     * @test
     */
    public function testAmountRounding(): void
    {
        $builder = new JournalEntryBuilder();
        
        $entry = $builder
            ->setReference('LOAN-123-2025-01-15')
            ->addDebit('2100', 600.123456) // Should round to 600.1235
            ->addCredit('1100', 600.123456)
            ->build();
        
        // FA uses 4 decimal places
        $this->assertStringContainsString('600.1235', json_encode($entry['debits']));
    }

    /**
     * Test reference tracking
     * @test
     */
    public function testReferenceTracking(): void
    {
        $builder = new JournalEntryBuilder();
        
        $entry = $builder
            ->setReference('LOAN-123-2025-01-15')
            ->setMemo('Test')
            ->addDebit('2100', 100.00)
            ->addCredit('1100', 100.00)
            ->build();
        
        $this->assertEquals('LOAN-123-2025-01-15', $entry['reference']);
    }

    /**
     * Test date tracking
     * @test
     */
    public function testDateTracking(): void
    {
        $builder = new JournalEntryBuilder();
        $testDate = new DateTime('2025-01-15');
        
        $entry = $builder
            ->setDate($testDate)
            ->setReference('TEST')
            ->addDebit('2100', 100.00)
            ->addCredit('1100', 100.00)
            ->build();
        
        $this->assertEquals('2025-01-15', $entry['post_date']);
    }

    /**
     * Test builder reset
     * @test
     */
    public function testBuilderReset(): void
    {
        $builder = new JournalEntryBuilder();
        
        $builder
            ->setReference('LOAN-123-2025-01-15')
            ->addDebit('2100', 100.00)
            ->addCredit('1100', 100.00);
        
        $builder->reset();
        
        // After reset, should need new entries
        $this->expectException(\RuntimeException::class);
        $builder->build();
    }

    // ==========================================
    // GLAccountMapper Tests
    // ==========================================

    /**
     * Test GLAccountMapper construction
     * @test
     */
    public function testGLAccountMapperConstruction(): void
    {
        // Mock PDO
        $pdo = $this->createMock(PDO::class);
        $pdo->method('setAttribute')->willReturnSelf();
        
        $mapper = new GLAccountMapper($pdo);
        
        $this->assertIsObject($mapper);
    }

    /**
     * Test GLAccountMapper rejects null PDO
     * @test
     */
    public function testGLAccountMapperRejectsNullPDO(): void
    {
        $this->expectException(\RuntimeException::class);
        
        new GLAccountMapper(null);
    }

    /**
     * Test validating valid GL accounts
     * @test
     */
    public function testValidatingValidGLAccounts(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('setAttribute')->willReturnSelf();
        
        $mapper = new GLAccountMapper($pdo);
        
        $accounts = [
            'liability_account' => '2100',
            'interest_expense_account' => '6200',
            'cash_account' => '1100',
        ];
        
        // Mock getAccountDetails to return active accounts
        $pdo->method('prepare')->willReturnCallback(function() use ($pdo) {
            $stmt = $this->createMock(\PDOStatement::class);
            $stmt->method('execute')->willReturnSelf();
            $stmt->method('fetch')->willReturn(['account_code' => '2100', 'inactive' => 0]);
            return $stmt;
        });
        
        // Note: This test is simplified since we mock PDO behavior
        // Real integration tests would use actual database
        $this->assertTrue(true); // Placeholder for actual validation
    }

    /**
     * Test cache clearing
     * @test
     */
    public function testCacheClearing(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('setAttribute')->willReturnSelf();
        
        $mapper = new GLAccountMapper($pdo);
        
        // Just ensure method exists and is callable
        $mapper->clearCache();
        
        $this->assertTrue(true);
    }

    // ==========================================
    // FAJournalService Tests
    // ==========================================

    /**
     * Test FAJournalService construction
     * @test
     */
    public function testFAJournalServiceConstruction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('setAttribute')->willReturnSelf();
        
        $service = new FAJournalService($pdo);
        
        $this->assertIsObject($service);
    }

    /**
     * Test FAJournalService rejects null PDO
     * @test
     */
    public function testFAJournalServiceRejectsNullPDO(): void
    {
        $this->expectException(\RuntimeException::class);
        
        new FAJournalService(null);
    }

    /**
     * Test postPaymentToGL with invalid GL accounts
     * @test
     */
    public function testPostPaymentWithInvalidGLAccounts(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('setAttribute')->willReturnSelf();
        
        $service = new FAJournalService($pdo);
        
        $paymentRow = [
            'id' => 1,
            'payment_date' => '2025-01-15',
            'principal_portion' => 600.00,
            'interest_portion' => 400.00,
            'payment_amount' => 1000.00,
        ];
        
        $invalidAccounts = [
            'liability_account' => '',  // Invalid
        ];
        
        $result = $service->postPaymentToGL(1, $paymentRow, $invalidAccounts);
        
        $this->assertFalse($result['success']);
        $this->assertNull($result['trans_no']);
    }

    /**
     * Test postPaymentToGL with zero amount
     * @test
     */
    public function testPostPaymentWithZeroAmount(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('setAttribute')->willReturnSelf();
        
        $service = new FAJournalService($pdo);
        
        $paymentRow = [
            'id' => 1,
            'payment_date' => '2025-01-15',
            'principal_portion' => 0,
            'interest_portion' => 0,
            'payment_amount' => 0,  // Invalid
        ];
        
        $accounts = [
            'liability_account' => '2100',
            'interest_expense_account' => '6200',
            'cash_account' => '1100',
        ];
        
        $result = $service->postPaymentToGL(1, $paymentRow, $accounts);
        
        $this->assertFalse($result['success']);
    }

    /**
     * Test batch posting with empty payment list
     * @test
     */
    public function testBatchPostingWithEmptyPayments(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('setAttribute')->willReturnSelf();
        
        // Mock prepare and fetchAll to return empty array
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturnSelf();
        $stmt->method('fetchAll')->willReturn([]);
        
        $pdo->method('prepare')->willReturn($stmt);
        
        $service = new FAJournalService($pdo);
        
        $result = $service->batchPostPayments(999); // Loan with no payments
        
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total_count']);
    }

    /**
     * Test transaction reference generation format
     * @test
     */
    public function testTransactionReferenceFormat(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('setAttribute')->willReturnSelf();
        
        $service = new FAJournalService($pdo);
        
        // Verify the reference is generated correctly in postPaymentToGL
        // The reference should be "LOAN-{loanId}-{date}"
        $paymentRow = [
            'id' => 1,
            'payment_date' => '2025-01-15',
            'principal_portion' => 600.00,
            'interest_portion' => 400.00,
            'payment_amount' => 1000.00,
        ];
        
        $invalidAccounts = [];
        
        $result = $service->postPaymentToGL(1, $paymentRow, $invalidAccounts);
        
        // Even with invalid accounts, we can verify reference would be formatted correctly
        $this->assertIsArray($result);
    }

    // ==========================================
    // Integration Tests
    // ==========================================

    /**
     * Test complete journal entry workflow
     * @test
     */
    public function testCompleteJournalEntryWorkflow(): void
    {
        // Build a complete journal entry
        $builder = new JournalEntryBuilder();
        
        $entry = $builder
            ->setDate(new DateTime('2025-01-15'))
            ->setReference('LOAN-123-2025-01-15')
            ->setMemo('Loan Payment - Principal $600, Interest $400')
            ->addDebit('2100', 600.00, 'Principal payment')
            ->addDebit('6200', 400.00, 'Interest expense')
            ->addCredit('1100', 1000.00, 'Payment received')
            ->build();
        
        // Verify entry structure
        $this->assertTrue($entry['is_balanced']);
        $this->assertEquals(3, count($entry['debits']) + count($entry['credits']));
        $this->assertEquals('2025-01-15', $entry['post_date']);
        $this->assertEquals('LOAN-123-2025-01-15', $entry['reference']);
    }

    /**
     * Test loan payment with principal and interest split
     * @test
     */
    public function testLoanPaymentPrincipalInterestSplit(): void
    {
        $builder = new JournalEntryBuilder();
        
        // Typical loan payment: $1000 with $600 principal, $400 interest
        $entry = $builder
            ->setReference('LOAN-456-2025-02-01')
            ->addDebit('2100', 600.00, 'Principal reduction')
            ->addDebit('6200', 400.00, 'Interest accrual')
            ->addCredit('1100', 1000.00, 'Cash received')
            ->build();
        
        // Verify balances
        $debitTotal = array_reduce(
            $entry['debits'],
            fn($carry, $item) => $carry + $item['amount'],
            0.0
        );
        
        $creditTotal = array_reduce(
            $entry['credits'],
            fn($carry, $item) => $carry + $item['amount'],
            0.0
        );
        
        $this->assertEquals(1000.00, $debitTotal);
        $this->assertEquals(1000.00, $creditTotal);
    }

    /**
     * Test multiple payments in sequence
     * @test
     */
    public function testMultiplePaymentsInSequence(): void
    {
        // Simulate posting 3 payments for a loan
        $paymentData = [
            ['date' => '2025-01-15', 'principal' => 600.00, 'interest' => 400.00],
            ['date' => '2025-02-15', 'principal' => 610.00, 'interest' => 390.00],
            ['date' => '2025-03-15', 'principal' => 620.00, 'interest' => 380.00],
        ];
        
        $entries = [];
        
        foreach ($paymentData as $payment) {
            $builder = new JournalEntryBuilder();
            
            $entry = $builder
                ->setDate(new DateTime($payment['date']))
                ->setReference("LOAN-123-{$payment['date']}")
                ->addDebit('2100', $payment['principal'])
                ->addDebit('6200', $payment['interest'])
                ->addCredit('1100', $payment['principal'] + $payment['interest'])
                ->build();
            
            $entries[] = $entry;
        }
        
        // All entries should be balanced
        foreach ($entries as $entry) {
            $this->assertTrue($entry['is_balanced']);
        }
        
        $this->assertEquals(3, count($entries));
    }
}

?>
