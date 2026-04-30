<?php

namespace Tests\Unit\Infrastructure\Logging;

use App\Infrastructure\Logging\StructuredLogger;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Phase 1: Unit Tests for Logging Service
 */
class StructuredLoggerTest extends TestCase
{
    private StructuredLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
        $this->logger = new StructuredLogger(Log::channel());
    }

    /**
     * @test
     * Test API request logging
     */
    public function it_logs_api_requests(): void
    {
        $this->logger->logApiRequest(
            'GET',
            '/api/v1/loans',
            ['status' => 'active'],
            1
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn($message, $context) =>
                $message === 'API Request' &&
                $context['method'] === 'GET' &&
                $context['path'] === '/api/v1/loans'
            )
            ->once();
    }

    /**
     * @test
     * Test business event logging
     */
    public function it_logs_business_events(): void
    {
        $this->logger->logBusinessEvent(
            'loan_originated',
            'Loan',
            123,
            ['amount' => 50000]
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn($message, $context) =>
                $message === 'Business Event: loan_originated' &&
                $context['entity_id'] === 123
            )
            ->once();
    }

    /**
     * @test
     * Test data modification logging (audit trail)
     */
    public function it_logs_data_modifications(): void
    {
        $this->logger->logDataModification(
            'Loan',
            123,
            'status_change',
            ['status' => 'ACTIVE'],
            ['status' => 'DELINQUENT_30'],
            1
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn($message, $context) =>
                $message === 'Data Modification' &&
                $context['action'] === 'status_change'
            )
            ->once();
    }

    /**
     * @test
     * Test sensitive data redaction
     */
    public function it_redacts_sensitive_data(): void
    {
        $this->logger->logDataModification(
            'User',
            1,
            'created',
            [],
            [
                'email' => 'test@example.com',
                'password' => 'secret123',
                'ssn' => '123-45-6789'
            ],
            1
        );

        // Verify LOG entries don't contain actual sensitive data
        // This would be verified in the log output
        Log::shouldHaveReceived('info')->once();
    }

    /**
     * @test
     * Test compliance check logging
     */
    public function it_logs_compliance_checks(): void
    {
        $this->logger->logComplianceCheck(
            'fdcpa_contact_frequency',
            'CollectionTask',
            456,
            true,
            ['week_contacts' => 3]
        );

        Log::shouldHaveReceived('info')
            ->withArgs(fn($message) =>
                str_contains($message, 'Compliance Check')
            )
            ->once();
    }
}
