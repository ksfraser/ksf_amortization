# Implementation Guides: KSF Amortization Platform

**Document**: Step-by-Step Implementation Guides | **Date**: April 28, 2026 | **Status**: Ready for Development

---

## TABLE OF CONTENTS

1. [General Implementation Principles](#general-principles)
2. [SPEC-LOANS Implementation Guide](#loans-implementation)
3. [SPEC-COLLECTIONS Implementation Guide](#collections-implementation)
4. [SPEC-REPORTING Implementation Guide](#reporting-implementation)
5. [SPEC-INTEGRATION Implementation Guide](#integration-implementation)
6. [SPEC-MOBILE Implementation Guide](#mobile-implementation)
7. [Testing Strategies](#testing-strategies)
8. [Deployment Procedures](#deployment-procedures)

---

## GENERAL IMPLEMENTATION PRINCIPLES

### 1. Architecture Principles

#### Layered Architecture

```
┌─────────────────────────────────────┐
│ Presentation Layer (API Controllers)│
├─────────────────────────────────────┤
│ Application Layer (Services)        │
├─────────────────────────────────────┤
│ Domain Layer (Entities, Repositories) │
├─────────────────────────────────────┤
│ Infrastructure Layer (Database, APIs)│
└─────────────────────────────────────┘
```

**Benefits**:
- Clear separation of concerns
- Easy to test (mock dependencies)
- Reusable services across APIs
- Database implementation swappable

#### Domain-Driven Design (DDD)

```php
// Good: Domain-focused
$loan = $loanService->originateLoan(
    $borrower,
    $loanRequest,
    $underwritingDecision
);

// Avoid: Technical-focused
$loan = new Loan();
$loan->loan_number = 'LN-2026-001';
$loan->amount = 50000;
// ... manual setup
```

#### Service-Oriented Architecture

```
Principle: Each service has one responsibility

✓ LoanOriginationService - Creates loans, manages workflow
✓ UnderwritingService - Decision rules, scoring
✓ AmortizationService - Calculations, schedules
✓ PaymentService - Receipt, posting, distribution
✗ MegaLoanService - Does everything (avoid!)
```

### 2. Code Quality Standards

#### PHP Code Standards (PSR-12)

```php
// ✓ Good
class LoanService
{
    private EntityManager $em;
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    public function originateLoan(Borrower $borrower): Loan
    {
        return new Loan($borrower);
    }
}

// ✗ Avoid
class loan_service {
    function originate_loan($borrower) {
        return new loan_object($borrower);
    }
}
```

#### Type Hints & Return Types

```php
// ✓ Good
public function calculateInterest(
    float $balance,
    float $rate,
    int $days
): float {
    return ($balance * $rate * $days) / 36500;
}

// ✗ Avoid (no type hints)
public function calculateInterest($balance, $rate, $days) {
    return ($balance * $rate * $days) / 36500;
}
```

#### Error Handling

```php
// ✓ Good: Specific exceptions
if ($loan->isDelinquent()) {
    throw new DelinquentLoanException(
        "Cannot modify delinquent loan",
        $loan->getId()
    );
}

// ✗ Avoid: Generic exceptions
if ($loan->isDelinquent()) {
    throw new Exception("Error");
}
```

### 3. Testing Philosophy

#### Test Pyramid

```
              /\
             /  \  Unit Tests (80%)
            /  E2E tests (10%)
           /______\
          /        \
         / Integration\  (10%)
        /____________\
```

**Ratios**:
- Unit tests: 80% (fast, isolated, mocked dependencies)
- Integration tests: 10% (test real components, slower)
- E2E tests: 10% (full workflow, slowest)

#### Test Coverage Goals

```
Phase 1 (Foundation): 85%+ coverage
Phase 2 (Loans): 80%+ coverage (focus on calculations)
Phase 3 (Collections): 70%+ coverage (complex workflows)
Phase 4 (Portal): 50%+ coverage (frontend, more manual testing)
Phase 5 (Launch): 85%+ overall
```

---

## LOANS IMPLEMENTATION GUIDE {#loans-implementation}

### 1. Loan Entity & Database Design

#### Step 1: Create Database Migration

```php
// database/migrations/2024_04_28_create_loans_table.php
Schema::create('0_ksf_loans', function (Blueprint $table) {
    $table->id('loan_id');
    $table->string('loan_number', 20)->unique();
    $table->unsignedInteger('borrower_no');
    
    // Status tracking
    $table->enum('stage', ['ORIGINATION', 'PENDING', 'ACTIVE', 'PAID_OFF', 'CHARGED_OFF']);
    $table->enum('status', ['CURRENT', 'DELINQUENT_30', 'DELINQUENT_60', 'DELINQUENT_90', 'PAID_OFF']);
    
    // Loan terms
    $table->decimal('original_amount', 15, 2);
    $table->decimal('current_balance', 15, 2);
    $table->decimal('interest_rate', 6, 4);  // 7.75 = 7.7500
    $table->unsignedTinyInteger('term_months');
    $table->string('loan_type', 50);  // Personal, Auto, Business
    $table->decimal('monthly_payment', 15, 2);
    
    // Dates
    $table->date('origination_date')->nullable();
    $table->date('funding_date')->nullable();
    $table->date('next_due_date')->nullable();
    $table->date('maturity_date')->nullable();
    
    // Performance tracking
    $table->unsignedSmallInteger('days_past_due')->default(0);
    $table->decimal('past_due_amount', 15, 2)->default(0);
    
    $table->timestamps();
    
    // Indexes
    $table->foreign('borrower_no')->references('debtor_no')->on('0_debtors');
    $table->index(['status']);
    $table->index(['next_due_date']);
    $table->index('borrower_no');
});
```

#### Step 2: Create Loan Entity

```php
// app/Domain/Loan/Entity/Loan.php
namespace App\Domain\Loan\Entity;

use App\Domain\Shared\Entity\AggregateRoot;
use Decimal\Decimal;

class Loan extends AggregateRoot
{
    private int $loanId;
    private string $loanNumber;
    private Borrower $borrower;
    private LoanStatus $status;
    private Stage $stage;
    
    // Loan terms
    private Decimal $originalAmount;
    private Decimal $currentBalance;
    private Decimal $interestRate;
    private int $termMonths;
    private Decimal $monthlyPayment;
    
    // Dates
    private \DateTime $originationDate;
    private \DateTime $fundingDate;
    private \DateTime $nextDueDate;
    private \DateTime $maturityDate;
    
    // Factory method
    public static function initiate(
        Borrower $borrower,
        LoanRequest $request,
        PricingDecision $pricing
    ): self {
        $loan = new self();
        $loan->loanNumber = 'LN-' . date('Y') . '-' . rand(1000, 9999);
        $loan->borrower = $borrower;
        $loan->originalAmount = Decimal::make($request->amount);
        $loan->currentBalance = Decimal::make($request->amount);
        $loan->interestRate = Decimal::make($pricing->rate);
        $loan->termMonths = $request->termMonths;
        $loan->stage = Stage::ORIGINATION;
        $loan->status = LoanStatus::CURRENT;
        
        return $loan;
    }
    
    // Business logic
    public function approve(UnderwritingDecision $decision): void
    {
        if ($this->stage !== Stage::ORIGINATION) {
            throw new InvalidStateException("Cannot approve non-origination loan");
        }
        
        $this->stage = Stage::PENDING;
        $this->recordEvent(new LoanApproved($this));
    }
    
    public function fund(Decimal $amount): void
    {
        if ($this->stage !== Stage::PENDING) {
            throw new InvalidStateException("Cannot fund non-pending loan");
        }
        
        if ($amount !== $this->originalAmount) {
            throw new InvalidAmountException("Funding amount mismatch");
        }
        
        $this->fundingDate = new \DateTime();
        $this->stage = Stage::ACTIVE;
        $this->recordEvent(new LoanFunded($this));
    }
}
```

### 2. Amortization Calculation

#### Step 1: Create Amortization Calculator

```php
// app/Domain/Loan/Service/AmortizationCalculator.php
namespace App\Domain\Loan\Service;

use Decimal\Decimal;

class AmortizationCalculator
{
    /**
     * Calculate fixed monthly payment
     * M = P × [i(1+i)^n] / [(1+i)^n - 1]
     */
    public function calculateMonthlyPayment(
        Decimal $principal,
        Decimal $annualRate,
        int $months
    ): Decimal {
        // Monthly interest rate
        $monthlyRate = $annualRate->divide(new Decimal('12'))
                                   ->divide(new Decimal('100'));
        
        if ($monthlyRate->isZero()) {
            return $principal->divide(new Decimal($months));
        }
        
        // (1 + i)^n
        $powFactor = $monthlyRate->add(new Decimal('1'))->pow($months);
        
        // Numerator: i(1+i)^n
        $numerator = $monthlyRate->multiply($powFactor);
        
        // Denominator: (1+i)^n - 1
        $denominator = $powFactor->subtract(new Decimal('1'));
        
        // Payment = P × numerator / denominator
        $payment = $principal->multiply($numerator->divide($denominator));
        
        return $payment->round(2);
    }
    
    /**
     * Generate complete amortization schedule
     */
    public function generateSchedule(
        Decimal $principal,
        Decimal $annualRate,
        int $months,
        \DateTime $startDate
    ): array {
        $payment = $this->calculateMonthlyPayment($principal, $annualRate, $months);
        $monthlyRate = $annualRate->divide(new Decimal('12'))->divide(new Decimal('100'));
        
        $schedule = [];
        $balance = $principal;
        $currentDate = clone $startDate;
        
        for ($period = 1; $period <= $months; $period++) {
            // Calculate interest for this period
            $interest = $balance->multiply($monthlyRate)->round(2);
            
            // Calculate principal for this period
            $principal_payment = $payment->subtract($interest)->round(2);
            
            // Last payment adjustment
            if ($period === $months) {
                $principal_payment = $balance;
            }
            
            $balance = $balance->subtract($principal_payment)->round(2);
            
            $schedule[] = [
                'period' => $period,
                'due_date' => $currentDate->format('Y-m-d'),
                'payment' => (float) $payment,
                'principal' => (float) $principal_payment,
                'interest' => (float) $interest,
                'balance' => (float) $balance
            ];
            
            $currentDate->add(new \DateInterval('P1M'));
        }
        
        return $schedule;
    }
}
```

#### Step 2: Unit Tests for Amortization

```php
// tests/Unit/Domain/Loan/AmortizationCalculatorTest.php
namespace Tests\Unit\Domain\Loan;

use App\Domain\Loan\Service\AmortizationCalculator;
use Decimal\Decimal;
use PHPUnit\Framework\TestCase;

class AmortizationCalculatorTest extends TestCase
{
    private AmortizationCalculator $calculator;
    
    protected function setUp(): void
    {
        $this->calculator = new AmortizationCalculator();
    }
    
    public function test_monthly_payment_calculation(): void
    {
        $principal = new Decimal('50000');
        $rate = new Decimal('7.75');  // 7.75% APR
        $months = 36;
        
        $payment = $this->calculator->calculateMonthlyPayment($principal, $rate, $months);
        
        // Verified with external calculator: should be ~$1,510.00
        $this->assertEquals('510.00', (string) $payment);
    }
    
    public function test_schedule_generation(): void
    {
        $schedule = $this->calculator->generateSchedule(
            new Decimal('50000'),
            new Decimal('7.75'),
            36,
            new \DateTime('2026-04-15')
        );
        
        $this->assertCount(36, $schedule);
        $this->assertEquals(510.00, $schedule[0]['payment']);
        $this->assertLessThan(0.01, $schedule[35]['balance']);  // Last balance ~$0
    }
    
    public function test_interest_accuracy(): void
    {
        $schedule = $this->calculator->generateSchedule(
            new Decimal('50000'),
            new Decimal('7.75'),
            36,
            new \DateTime('2026-04-15')
        );
        
        // Sum all interest
        $totalInterest = array_reduce(
            $schedule,
            fn($sum, $period) => $sum + $period['interest'],
            0
        );
        
        // For $50K at 7.75% for 36 months, total interest ~$8,440
        $this->assertGreaterThan(8000);
        $this->assertLessThan(9000);
    }
}
```

### 3. Interest Calculation & Posting

#### Step 1: Interest Accrual Service

```php
// app/Domain/Loan/Service/InterestAccrualService.php
namespace App\Domain\Loan\Service;

use Decimal\Decimal;

class InterestAccrualService
{
    /**
     * Daily interest accrual
     * Interest = (Balance × Annual Rate) / 365
     */
    public function calculateDailyInterest(
        Decimal $balance,
        Decimal $annualRate,
        int $days = 1
    ): Decimal {
        $dailyRate = $annualRate->divide(new Decimal('36500'));  // 365 * 100 for %
        $interest = $balance->multiply($dailyRate)->multiply(new Decimal($days));
        
        return $interest->round(2);
    }
    
    /**
     * Accrue interest from start date to payment date
     */
    public function accrueInterestPeriod(
        Decimal $balance,
        Decimal $annualRate,
        \DateTime $startDate,
        \DateTime $endDate
    ): Decimal {
        $days = $endDate->diff($startDate)->days;
        return $this->calculateDailyInterest($balance, $annualRate, $days);
    }
}
```

### 4. Payment Processing

#### Step 1: Payment Processor Service

```php
// app/Application/Loan/PaymentProcessingService.php
namespace App\Application\Loan;

use App\Domain\Loan\Entity\Payment;
use App\Domain\Payment\PaymentProcessor;
use App\Infrastructure\Stripe\StripePaymentGateway;
use Decimal\Decimal;

class PaymentProcessingService
{
    public function __construct(
        private StripePaymentGateway $gateway,
        private PaymentRepository $paymentRepo,
        private InterestAccrualService $interestService
    ) {}
    
    /**
     * Process payment through gateway
     */
    public function processPayment(
        Loan $loan,
        Decimal $amount,
        string $method  // 'ach', 'card', 'wire'
    ): ProcessingResult {
        // Validate payment
        $this->validatePayment($loan, $amount);
        
        // Process through gateway
        $gateway_result = $this->gateway->charge(
            $loan->getBorrower(),
            $amount,
            $method
        );
        
        if (!$gateway_result->isSuccessful()) {
            return ProcessingResult::failed($gateway_result->getError());
        }
        
        // Create payment record (pending settlement)
        $payment = Payment::create(
            $loan,
            $amount,
            $method,
            $gateway_result->getTransactionId()
        );
        
        $this->paymentRepo->save($payment);
        
        return ProcessingResult::success($payment);
    }
    
    /**
     * Post payment once settled
     */
    public function postPayment(Payment $payment): void
    {
        $loan = $payment->getLoan();
        
        // Calculate interest accrued since last payment
        $lastPaymentDate = $loan->getLastPaymentDate();
        $accrued = $this->interestService->accrueInterestPeriod(
            $loan->getCurrentBalance(),
            $loan->getInterestRate(),
            $lastPaymentDate,
            new \DateTime()
        );
        
        // Distribute payment
        $this->distributePayment($loan, $payment, $accrued);
        
        // Update account status
        $this->updateAccountStatus($loan);
        
        // Generate receipt
        $receipt = $payment->generateReceipt();
        
        // Notify borrower
        event(new PaymentPosted($payment, $receipt));
    }
    
    /**
     * Distribute payment to principal, interest, fees
     */
    private function distributePayment(Loan $loan, Payment $payment, Decimal $accrued): void
    {
        $amount = $payment->getAmount();
        
        // Apply interest first
        $interest = min($amount, $accrued);
        $amount = $amount->subtract($interest);
        
        // Apply principal second
        $principal = $amount;
        
        $payment->setDistribution(
            interest: $interest,
            principal: $principal
        );
        
        // Update loan balance
        $loan->applyPayment($principal);
    }
}
```

---

## COLLECTIONS IMPLEMENTATION GUIDE {#collections-implementation}

### 1. Delinquency Detection

#### Step 1: Scheduled Job

```php
// app/Infrastructure/Console/Commands/DetectDelinquenciesCommand.php
namespace App\Infrastructure\Console\Commands;

use App\Domain\Collections\Service\DelinquencyDetectionService;
use Illuminate\Console\Command;

class DetectDelinquenciesCommand extends Command
{
    protected $signature = 'collections:detect-delinquencies';
    protected $description = 'Daily delinquency detection and classification';
    
    public function __construct(
        private DelinquencyDetectionService $service
    ) {
        parent::__construct();
    }
    
    public function handle(): void
    {
        $this->info('Detecting delinquencies...');
        
        $results = $this->service->detect();
        
        $this->info("New delinquencies: {$results['new']}");
        $this->info("Escalations: {$results['escalated']}");
        $this->info("Cured: {$results['cured']}");
    }
}
```

#### Step 2: Delinquency Service

```php
// app/Domain/Collections/Service/DelinquencyDetectionService.php
class DelinquencyDetectionService
{
    public function detect(): array
    {
        $results = [
            'new' => 0,
            'escalated' => 0,
            'cured' => 0
        ];
        
        // Find all CURRENT loans with past due dates
        $pastDue = $this->loanRepository->getPastDue();
        
        foreach ($pastDue as $loan) {
            $daysLate = $this->calculateDaysLate($loan);
            
            if ($daysLate >= 10 && $loan->getStatus() === 'CURRENT') {
                // Transition to 30-day delinquency
                $loan->setStatus('DELINQUENT_30');
                $this->createCollectionTask($loan);
                $results['new']++;
            } elseif ($daysLate >= 60 && $loan->getStatus() === 'DELINQUENT_30') {
                // Escalate to 60-day
                $loan->setStatus('DELINQUENT_60');
                $this->escalateTask($loan);
                $results['escalated']++;
            }
        }
        
        return $results;
    }
}
```

### 2. Collection Task Assignment

#### Step 1: Task Assignment Algorithm

```php
// app/Domain/Collections/Service/TaskAssignmentService.php
namespace App\Domain\Collections\Service;

class TaskAssignmentService
{
    /**
     * Assign task to least burdened collector
     */
    public function assignOptimal(CollectionTask $task): Collector
    {
        // Get all active collectors
        $collectors = $this->collectorRepository->getActive();
        
        // Score each collector by current workload + skill fit
        $scores = $collectors->map(function (Collector $collector) use ($task) {
            $workload = $this->getCollectorWorkload($collector);  // 0-100
            $skillFit = $this->getSkillFit($collector, $task);     // 0-1
            
            // Combined score (lower is better)
            return [
                'collector' => $collector,
                'workload' => $workload,
                'skill_fit' => $skillFit,
                'score' => ($workload * 0.7) - ($skillFit * 100 * 0.3)
            ];
        });
        
        // Sort and get best match
        $best = $scores->sortBy('score')->first();
        
        return $best['collector'];
    }
    
    private function getCollectorWorkload(Collector $c): float
    {
        $tasks = $this->taskRepository->getAssignedTo($c);
        $capacity = 50;  // Max tasks per collector
        
        return (count($tasks) / $capacity) * 100;
    }
    
    private function getSkillFit(Collector $c, CollectionTask $task): float
    {
        $score = 0.5;  // baseline
        
        // Boost if collector has worked with this borrower before
        if ($c->hasPriorInteraction($task->getLoan()->getBorrower())) {
            $score += 0.3;
        }
        
        return min($score, 1.0);
    }
}
```

### 3. FDCPA Compliance Checking

#### Step 1: Compliance Validator

```php
// app/Domain/Collections/Service/FdcpaComplianceValidator.php
namespace App\Domain\Collections\Service;

use App\Domain\Collections\Entity\CollectionActivity;

class FdcpaComplianceValidator
{
    /**
     * Validate contact is compliant before allowing
     */
    public function validateContactCompliance(
        CollectionTask $task,
        string $contactMethod,
        \DateTime $attemptTime
    ): ComplianceResult {
        $borrower = $task->getLoan()->getBorrower();
        
        // Check 1: Contact time restrictions
        if (!$this->isValidContactTime($borrower->getState(), $attemptTime)) {
            return ComplianceResult::violation(
                'CONTACT_TIME_VIOLATION',
                'Contact attempted outside 8 AM - 9 PM debtor time'
            );
        }
        
        // Check 2: Contact frequency (max 7 per week)
        $weekContacts = $this->activityRepository->countWeekContacts($task);
        if ($weekContacts >= 7) {
            return ComplianceResult::violation(
                'FREQUENCY_VIOLATION',
                'Maximum 7 contacts per week exceeded'
            );
        }
        
        // Check 3: Prior cease & desist
        if ($this->hasCeaseAndDesist($task)) {
            return ComplianceResult::violation(
                'CEASE_DESIST_VIOLATION',
                'Borrower has requested no further contact'
            );
        }
        
        return ComplianceResult::compliant();
    }
    
    private function isValidContactTime(string $state, \DateTime $time): bool
    {
        $tz = $this->getTimezone($state);
        $time->setTimezone($tz);
        
        $hour = (int) $time->format('H');
        return $hour >= 8 && $hour < 21;  // 8 AM - 9 PM
    }
}
```

---

## REPORTING IMPLEMENTATION GUIDE {#reporting-implementation}

### 1. Data Warehouse Schema

#### Step 1: Fact Table Design

```sql
-- Data warehouse fact table
CREATE TABLE dw_fact_loans (
    fact_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    date_key INT NOT NULL,  -- References dw_dim_date
    loan_key INT NOT NULL,  -- References dw_dim_loan
    borrower_key INT NOT NULL,
    product_key INT NOT NULL,
    officer_key INT NOT NULL,
    
    -- Measures
    original_amount DECIMAL(15,2),
    current_balance DECIMAL(15,2),
    interest_earned DECIMAL(12,2),
    collections_received DECIMAL(12,2),
    
    -- Status flags
    is_current TINYINT,
    is_delinquent_30 TINYINT,
    is_delinquent_60 TINYINT,
    days_late TINYINT,
    
    -- Aggregates
    total_payments_count INT,
    avg_payment_amount DECIMAL(12,2),
    
    FOREIGN KEY (date_key) REFERENCES dw_dim_date(date_key),
    INDEX idx_date (date_key),
    INDEX idx_loan (loan_key)
);
```

#### Step 2: Dimension Table Design

```sql
-- Date dimension
CREATE TABLE dw_dim_date (
    date_key INT PRIMARY KEY,  -- YYYYMMDD
    calendar_date DATE UNIQUE,
    year INT,
    month INT,
    day INT,
    quarter INT,
    week_of_year INT,
    day_of_week INT,
    is_weekend TINYINT,
    INDEX idx_date (calendar_date)
);

-- Loan dimension
CREATE TABLE dw_dim_loan (
    loan_key INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT,
    loan_number VARCHAR(20),
    loan_type VARCHAR(50),
    original_amount DECIMAL(15,2),
    interest_rate DECIMAL(6,4),
    term_months INT,
    origination_date DATE,
    INDEX idx_loan_id (loan_id),
    INDEX idx_loan_type (loan_type)
);
```

### 2. ETL Pipeline

#### Step 1: Nightly ETL Job

```php
// app/Infrastructure/Console/Commands/EtlDataWarehouseCommand.php
namespace App\Infrastructure\Console\Commands;

use Illuminate\Console\Command;

class EtlDataWarehouseCommand extends Command
{
    public function handle(): void
    {
        $this->call('dw:extract');
        $this->call('dw:transform');
        $this->call('dw:load');
        $this->call('dw:aggregate');
        
        $this->info('Data warehouse updated successfully');
    }
}

// app/Infrastructure/ETL/DataWarehouseETL.php
class DataWarehouseETL
{
    public function extract(): void
    {
        // Extract from operational DB to staging
        $loans = DB::connection('mysql')
            ->table('0_ksf_loans')
            ->where('updated_at', '>=', now()->subDay())
            ->get();
        
        foreach ($loans as $loan) {
            DB::connection('datawarehouse')
                ->table('dw_stg_loans')
                ->insertOrUpdate(
                    ['loan_id' => $loan->loan_id],
                    $this->transformLoan($loan)
                );
        }
    }
    
    public function load(): void
    {
        DB::connection('datawarehouse')->transaction(function () {
            // Merge staging into fact table
            DB::connection('datawarehouse')
                ->statement(
                    "INSERT INTO dw_fact_loans
                     SELECT ... FROM dw_stg_loans stg
                     LEFT JOIN dw_dim_loan dim ON stg.loan_id = dim.loan_id"
                );
        });
    }
}
```

### 3. Dashboard APIs

#### Step 1: Dashboard Query Service

```php
// app/Application/Reporting/DashboardQueryService.php
namespace App\Application\Reporting;

use Illuminate\Support\Facades\DB;

class DashboardQueryService
{
    /**
     * Get portfolio health summary (cached)
     */
    public function getPortfolioHealth(): array
    {
        return Cache::remember('dashboard:portfolio:health', 60*15, function () {
            $result = DB::connection('datawarehouse')
                ->table('dw_fact_loans')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN is_current = 1 THEN 1 ELSE 0 END) as current_count,
                    SUM(CASE WHEN is_delinquent_30 = 1 THEN 1 ELSE 0 END) as delinquent_30,
                    SUM(current_balance) as total_balance
                ')
                ->whereRaw('date_key = (SELECT MAX(date_key) FROM dw_fact_loans)')
                ->first();
            
            return [
                'total_loans' => $result->total,
                'current' => $result->current_count,
                'current_pct' => ($result->current_count / $result->total) * 100,
                'delinquent_30' => $result->delinquent_30,
                'portfolio_balance' => $result->total_balance
            ];
        });
    }
}
```

---

## INTEGRATION IMPLEMENTATION GUIDE {#integration-implementation}

### 1. CRM Synchronization

#### Step 1: CRM Sync Service

```php
// app/Infrastructure/Integrations/CRM/FrontAccountingSyncService.php
namespace App\Infrastructure\Integrations\CRM;

use App\Domain\Loan\Entity\Loan;
use GuzzleHttp\Client;

class FrontAccountingSyncService
{
    private Client $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('crm.frontaccounting.url'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('crm.frontaccounting.token')
            ]
        ]);
    }
    
    /**
     * Sync loan to CRM as invoice
     */
    public function syncLoan(Loan $loan): void
    {
        $payload = [
            'customer_no' => $loan->getBorrower()->getCrmId(),
            'invoice_no' => $loan->getLoanNumber(),
            'invoice_date' => $loan->getOriginationDate()->format('Y-m-d'),
            'due_date' => $loan->getMaturityDate()->format('Y-m-d'),
            'amount' => $loan->getOriginalAmount(),
            'memo' => "Loan {$loan->getLoanNumber()} - {$loan->getLoanType()}",
            'balance' => $loan->getCurrentBalance(),
            'status' => $this->mapLoanStatus($loan)
        ];
        
        try {
            $response = $this->client->post('/invoices', [
                'json' => $payload
            ]);
            
            if ($response->getStatusCode() === 201) {
                Log::info("Loan synced to CRM", ['loan_id' => $loan->getId()]);
            }
        } catch (\Exception $e) {
            Log::error("CRM sync failed", ['error' => $e->getMessage()]);
            // Implement retry logic
        }
    }
    
    private function mapLoanStatus(Loan $loan): string
    {
        $mapping = [
            'CURRENT' => 'active',
            'DELINQUENT_30' => 'overdue',
            'DELINQUENT_60' => 'overdue',
            'PAID_OFF' => 'paid',
            'CHARGED_OFF' => 'written_off'
        ];
        
        return $mapping[$loan->getStatus()] ?? 'unknown';
    }
}
```

### 2. Bank Integration (ACH)

#### Step 1: ACH Processing Service

```php
// app/Infrastructure/Payment/AchProcessingService.php
namespace App\Infrastructure\Payment;

use App\Domain\Payment\AchBatch;
use Nacha\NachaFile;

class AchProcessingService
{
    /**
     * Create NACHA-formatted ACH batch
     */
    public function createBatch(array $payments): NachaFile
    {
        $batch = new NachaFile();
        
        foreach ($payments as $payment) {
            $batch->addRecord([
                'type' => 'PPD',  // Pre-authorized payment/debit
                'amount' => (int)($payment->getAmount() * 100),  // Cents
                'account' => $payment->getBankAccount(),
                'routing' => $payment->getRoutingNumber(),
                'name' => $payment->getAccountHolder(),
                'settlement_date' => now()->addDays(1)->format('ymd')
            ]);
        }
        
        return $batch->generate();
    }
}
```

### 3. Audit Logging

#### Step 1: Audit Log Service

```php
// app/Application/Audit/AuditLogService.php
namespace App\Application\Audit;

class AuditLogService
{
    /**
     * Log critical action immutably
     */
    public function logAction(
        string $entity,
        int $entityId,
        string $action,
        array $oldValues,
        array $newValues,
        string $userId
    ): void {
        $log = [
            'entity_type' => $entity,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => json_encode($this->sanitize($oldValues)),
            'new_values' => json_encode($newValues),
            'user_id' => $userId,
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent')
        ];
        
        // Write to append-only audit table
        DB::table('0_ksf_audit_log')->insert($log);
        
        // Also write to immutable log file
        Log::channel('audit')->info(json_encode($log));
    }
}
```

---

## MOBILE IMPLEMENTATION GUIDE {#mobile-implementation}

### 1. Portal Setup (React.js)

#### Step 1: Project Structure

```
borrower-portal/
├── src/
│   ├── api/           # API client methods
│   ├── components/    # Reusable components
│   ├── features/      # Feature-based structure
│   │   ├── loans/
│   │   ├── payments/
│   │   └── profile/
│   ├── store/         # Redux state
│   ├── hooks/         # Custom React hooks
│   ├── utils/         # Helper functions
│   └── App.jsx
├── tests/
├── public/
└── package.json
```

#### Step 2: Authentication Setup

```javascript
// src/features/auth/authSlice.js
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { loginApi } from '../../api/auth';

export const login = createAsyncThunk(
    'auth/login',
    async (credentials) => {
        const response = await loginApi(credentials);
        localStorage.setItem('access_token', response.access_token);
        return response;
    }
);

const authSlice = createSlice({
    name: 'auth',
    initialState: {
        user: null,
        tokens: {},
        status: 'idle'
    },
    extraReducers: (builder) => {
        builder.addCase(login.fulfilled, (state, action) => {
            state.user = action.payload.user;
            state.tokens = action.payload;
            state.status = 'authenticated';
        });
    }
});

export default authSlice.reducer;
```

### 2. Mobile App Setup (React Native)

#### Step 1: Project Structure

```
borrower-mobile/
├── src/
│   ├── api/           # API client
│   ├── screens/       # Screen components
│   │   ├── HomeScreen.js
│   │   ├── PaymentScreen.js
│   │   └── ProfileScreen.js
│   ├── store/         # Redux + persist
│   ├── services/      # Business logic
│   │   └── authService.js
│   ├── utils/         # Helpers
│   └── App.js
├── tests/
└── package.json
```

#### Step 2: Biometric Authentication

```javascript
// src/services/authService.js
import * as SecureStore from 'expo-secure-store';
import * as LocalAuthentication from 'expo-local-authentication';

export const enableBiometric = async () => {
    const compatible = await LocalAuthentication.hasHardwareAsync();
    if (!compatible) throw new Error('Device does not support biometric');
    
    const enrolled = await LocalAuthentication.isEnrolledAsync();
    if (!enrolled) throw new Error('No biometric enrolled');
    
    return true;
};

export const authenticate = async () => {
    try {
        const result = await LocalAuthentication.authenticateAsync({
            disableDeviceFallback: false,
            reason: 'Authenticate to access your account'
        });
        return result.success;
    } catch (error) {
        throw error;
    }
};
```

---

## TESTING STRATEGIES {#testing-strategies}

### 1. Unit Testing Pattern

```php
// tests/Unit/Domain/Loan/LoanTest.php
namespace Tests\Unit\Domain\Loan;

use App\Domain\Loan\Entity\Loan;
use PHPUnit\Framework\TestCase;

class LoanTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_originated(): void
    {
        $borrower = $this->createBorrower();
        $request = new LoanRequest(50000, 36, 'personal');
        
        $loan = Loan::initiate($borrower, $request);
        
        $this->assertEquals('ORIGINATION', $loan->getStage());
        $this->assertEquals(50000, $loan->getOriginalAmount());
    }
    
    /**
     * @test
     */
    public function it_cannot_be_funded_twice(): void
    {
        $loan = $this->createLoan();
        $loan->fund(50000);
        
        $this->expectException(InvalidStateException::class);
        $loan->fund(50000);
    }
}
```

### 2. Integration Testing Pattern

```php
// tests/Integration/Loan/LoanOriginationTest.php
namespace Tests\Integration\Loan;

use Tests\TestCase;

class LoanOriginationTest extends TestCase
{
    /**
     * @test
     */
    public function complete_loan_origination_workflow(): void
    {
        // Create borrower
        $response = $this->postJson('/api/v1/borrowers', [
            'name' => 'John Doe',
            'ssn' => '123-45-6789',
            'email' => 'john@example.com'
        ]);
        
        $borrower = $response->json('data');
        
        // Create loan application
        $response = $this->postJson('/api/v1/loans', [
            'borrower_id' => $borrower['id'],
            'amount' => 50000,
            'term_months' => 36
        ]);
        
        $this->assertStatus(201);
        $loan = $response->json('data');
        
        // Verify loan created
        $this->assertDatabaseHas('0_ksf_loans', [
            'loan_id' => $loan['id'],
            'stage' => 'ORIGINATION'
        ]);
    }
}
```

### 3. Calculation Accuracy Tests

```php
// tests/Feature/Loan/AmortizationAccuracyTest.php
namespace Tests\Feature\Loan;

use App\Domain\Loan\Service\AmortizationCalculator;
use Tests\TestCase;

class AmortizationAccuracyTest extends TestCase
{
    /**
     * Validate against known examples
     */
    public function test_calculation_against_external_source(): void
    {
        $calculator = new AmortizationCalculator();
        
        // Test case from external amortization calculator
        $schedule = $calculator->generateSchedule(
            50000,   // Principal
            7.75,    // Rate
            36,      // Months
            new \DateTime('2026-04-15')
        );
        
        // Known monthly payment from external calc
        $this->assertEquals(510.00, $schedule[0]['payment']);
        
        // Total interest should be ~$8,360
        $totalInterest = array_sum(array_column($schedule, 'interest'));
        $this->assertGreaterThan(8350);
        $this->assertLessThan(8370);
        
        // Final balance should be ~$0
        $finalBalance = end($schedule)['balance'];
        $this->assertLessThan(0.01);
    }
}
```

---

## DEPLOYMENT PROCEDURES {#deployment-procedures}

### 1. Pre-Deployment Checklist

```markdown
# Pre-Deployment Validation

## Code Quality
- [ ] All tests passing (80%+ coverage)
- [ ] Code review approved
- [ ] No security vulnerabilities (Psalm/PHPStan)
- [ ] No hardcoded credentials or secrets

## Documentation
- [ ] Deployment guide created
- [ ] Environment variables documented
- [ ] Database migrations documented
- [ ] API changes documented

## Data
- [ ] Database migrations tested on staging
- [ ] Backup strategy defined
- [ ] Rollback plan documented
- [ ] Data validation checks in place

## Infrastructure
- [ ] Load balancers configured
- [ ] Database replicas verified
- [ ] CDN cache invalidation planned
- [ ] Monitoring alerts configured

## Security
- [ ] SSL certificates valid
- [ ] Secrets Manager updated
- [ ] Access logs configured
- [ ] Firewall rules updated
```

### 2. Production Deployment Runbook

```bash
#!/bin/bash
# scripts/deploy-production.sh

set -e  # Exit on error

echo "Starting production deployment..."

# 1. Pull latest code
git pull origin release/v1.0

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Clear old cache
php artisan cache:clear

# 6. Warmup caches
php artisan db:seed --class=CacheSeeder

# 7. Restart queue workers
supervisorctl restart laravel-worker

# 8. Health check
curl "https://api.example.com/health" -f

echo "Deployment complete!"
```

---

## SUMMARY

This guide provides practical implementation strategies across all specifications. Key points:

1. **Layered Architecture**: Domain → Application → Infrastructure
2. **Test-Driven**: Unit (80%) → Integration (10%) → E2E (10%)
3. **Calculation Accuracy**: Validate against external sources
4. **Compliance First**: FDCPA, ECOA, TCPA built-in
5. **Performance**: Caching, indexing, query optimization
6. **Monitoring**: Audit trails, error tracking, performance metrics

**Next**: Proceed to Phase 1 implementation (Foundation Infrastructure)

