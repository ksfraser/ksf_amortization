<?php

namespace Tests\Unit\Services;

use Ksfraser\Amortizations\Models\Loan;
use Ksfraser\Amortizations\Services\DocumentGenerationService;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class DocumentGenerationServiceTest extends TestCase
{
    private DocumentGenerationService $service;
    private Loan $loan;
    private array $schedule;

    protected function setUp(): void
    {
        $this->service = new DocumentGenerationService();
        $this->loan = $this->createTestLoan();
        $this->schedule = $this->createTestSchedule();
    }

    private function createTestLoan(): Loan
    {
        $loan = new Loan();
        $loan->setId(1);
        $loan->setPrincipal(100000.00);
        $loan->setAnnualRate(0.05);
        $loan->setMonths(120);
        $loan->setStartDate(new DateTimeImmutable('2024-01-01'));
        $loan->setCurrentBalance(100000.00);
        return $loan;
    }

    private function createTestSchedule(): array
    {
        $schedule = [];
        $balance = 100000.00;
        $rate = 0.05 / 12;
        $payment = 943.56;

        for ($month = 1; $month <= 120; $month++) {
            $interest = round($balance * $rate, 2);
            $principal = round($payment - $interest, 2);
            $balance = round($balance - $principal, 2);

            $schedule[] = [
                'month' => $month,
                'payment' => $payment,
                'principal' => $principal,
                'interest' => $interest,
                'balance' => max(0, $balance),
                'date' => (new DateTimeImmutable('2024-01-01'))->modify("+{$month} month")->format('Y-m-d'),
            ];
        }

        return $schedule;
    }

    /**
     * Test 1: Generate amortization schedule as CSV
     */
    public function testGenerateAmortizationScheduleAsCSV()
    {
        $csv = $this->service->generateAmortizationScheduleAsCSV($this->loan, $this->schedule);

        $this->assertIsString($csv);
        $this->assertStringContainsString('Month', $csv);
        $this->assertStringContainsString('Payment', $csv);
        $this->assertStringContainsString('Principal', $csv);
        $this->assertStringContainsString('Interest', $csv);
        $this->assertStringContainsString('Balance', $csv);
    }

    /**
     * Test 2: Generate amortization schedule as Excel (binary format)
     */
    public function testGenerateAmortizationScheduleAsExcel()
    {
        $excel = $this->service->generateAmortizationScheduleAsExcel($this->loan, $this->schedule);

        $this->assertIsString($excel);
        $this->assertNotEmpty($excel);
        // Excel files start with specific binary signature or are serialized objects
    }

    /**
     * Test 3: Generate loan summary document
     */
    public function testGenerateLoanSummaryDocument()
    {
        $summary = $this->service->generateLoanSummaryDocument($this->loan, $this->schedule);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('loan_id', $summary);
        $this->assertArrayHasKey('principal', $summary);
        $this->assertArrayHasKey('rate', $summary);
        $this->assertArrayHasKey('total_interest', $summary);
        $this->assertArrayHasKey('generated_date', $summary);
    }

    /**
     * Test 4: Generate payment schedule with custom headers
     */
    public function testGeneratePaymentScheduleWithCustomHeaders()
    {
        $customHeaders = [
            'Period' => 'month',
            'Monthly Payment' => 'payment',
            'Principal Amount' => 'principal',
            'Interest Charge' => 'interest',
            'Remaining Balance' => 'balance',
        ];

        $document = $this->service->generatePaymentScheduleWithCustomHeaders($this->loan, $this->schedule, $customHeaders);

        $this->assertIsArray($document);
        $this->assertArrayHasKey('headers', $document);
        $this->assertArrayHasKey('rows', $document);
        $this->assertCount(5, $document['headers']);
    }

    /**
     * Test 5: Export to HTML format
     */
    public function testExportToHTML()
    {
        $html = $this->service->exportToHTML($this->loan, $this->schedule);

        $this->assertIsString($html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
        $this->assertStringContainsString('<thead', $html);
        $this->assertStringContainsString('<tbody', $html);
    }

    /**
     * Test 6: Export to JSON
     */
    public function testExportToJSON()
    {
        $json = $this->service->exportToJSON($this->loan, $this->schedule);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('loan_info', $decoded);
        $this->assertArrayHasKey('schedule', $decoded);
    }

    /**
     * Test 7: Generate comparison schedule (current vs accelerated)
     */
    public function testGenerateComparisonSchedule()
    {
        $acceleratedSchedule = array_slice($this->schedule, 0, 60);  // Half the payments

        $comparison = $this->service->generateComparisonSchedule(
            $this->loan,
            $this->schedule,
            $acceleratedSchedule,
            'Standard vs Accelerated'
        );

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('loan_id', $comparison);
        $this->assertArrayHasKey('comparison_type', $comparison);
        $this->assertArrayHasKey('schedules', $comparison);
    }

    /**
     * Test 8: Add formatting to document
     */
    public function testAddFormattingToDocument()
    {
        $unformatted = $this->service->generateLoanSummaryDocument($this->loan, $this->schedule);

        $formatted = $this->service->addFormattingToDocument($unformatted, [
            'currency_format' => 'USD',
            'thousand_separator' => ',',
            'decimal_places' => 2,
        ]);

        $this->assertIsArray($formatted);
        // Check that formatting was applied
        $this->assertArrayHasKey('formatting', $formatted);
    }

    /**
     * Test 9: Generate payment coupon for specific month
     */
    public function testGeneratePaymentCoupon()
    {
        $coupon = $this->service->generatePaymentCoupon($this->loan, $this->schedule[0]);

        $this->assertIsArray($coupon);
        $this->assertArrayHasKey('month', $coupon);
        $this->assertArrayHasKey('payment_amount', $coupon);
        $this->assertArrayHasKey('due_date', $coupon);
        $this->assertArrayHasKey('breakdown', $coupon);
    }

    /**
     * Test 10: Generate year-end statement
     */
    public function testGenerateYearEndStatement()
    {
        $statement = $this->service->generateYearEndStatement($this->loan, $this->schedule, 2024);

        $this->assertIsArray($statement);
        $this->assertArrayHasKey('year', $statement);
        $this->assertArrayHasKey('total_paid', $statement);
        $this->assertArrayHasKey('total_interest', $statement);
        $this->assertArrayHasKey('tax_summary', $statement);
    }

    /**
     * Test 11: Generate multiple documents (bundle)
     */
    public function testGenerateDocumentBundle()
    {
        $bundle = $this->service->generateDocumentBundle($this->loan, $this->schedule, ['csv', 'json', 'html']);

        $this->assertIsArray($bundle);
        $this->assertArrayHasKey('csv', $bundle);
        $this->assertArrayHasKey('json', $bundle);
        $this->assertArrayHasKey('html', $bundle);
        $this->assertNotEmpty($bundle['csv']);
    }

    /**
     * Test 12: Validate document for compliance
     */
    public function testValidateDocumentForCompliance()
    {
        $document = $this->service->generateLoanSummaryDocument($this->loan, $this->schedule);

        $validation = $this->service->validateDocumentForCompliance($document);

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('compliant', $validation);
        $this->assertArrayHasKey('issues', $validation);
        $this->assertTrue($validation['compliant']);
    }

    /**
     * Test 13: Add watermark/header/footer to document
     */
    public function testAddDocumentDecorations()
    {
        $document = $this->service->generateLoanSummaryDocument($this->loan, $this->schedule);

        $decorated = $this->service->addDocumentDecorations($document, [
            'header' => 'Loan Amortization Schedule',
            'footer' => 'Confidential - Page [PAGE]',
            'watermark' => 'DRAFT',
        ]);

        $this->assertIsArray($decorated);
        $this->assertArrayHasKey('decorations', $decorated);
    }

    /**
     * Test 14: Export to PDF (structural validation)
     */
    public function testExportToPDF()
    {
        $pdf = $this->service->exportToPDF($this->loan, $this->schedule);

        $this->assertIsString($pdf);
        // PDF files start with %PDF signature
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
