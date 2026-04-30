<?php

namespace App\Infrastructure\Logging;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Phase 1: Structured Logging Service
 * Provides consistent, comprehensive logging across the application
 * Implements ELK stack integration (Elasticsearch, Logstash, Kibana)
 */
class StructuredLogger
{
    private LoggerInterface $logger;
    private array $context = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log API request
     */
    public function logApiRequest(
        string $method,
        string $path,
        array $parameters = [],
        ?string $userId = null
    ): void {
        $this->logger->info('API Request', [
            'event_type' => 'api_request',
            'method' => $method,
            'path' => $path,
            'user_id' => $userId,
            'parameters_count' => count($parameters),
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }

    /**
     * Log API response
     */
    public function logApiResponse(
        string $method,
        string $path,
        int $statusCode,
        float $duration,
        ?string $userId = null
    ): void {
        $this->logger->info('API Response', [
            'event_type' => 'api_response',
            'method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'user_id' => $userId,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log business event (e.g., loan originated, payment received)
     */
    public function logBusinessEvent(
        string $eventType,
        string $entityType,
        int $entityId,
        array $details = []
    ): void {
        $this->logger->info("Business Event: {$eventType}", [
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log data modification (audit trail)
     */
    public function logDataModification(
        string $entityType,
        int $entityId,
        string $action,
        array $oldValues,
        array $newValues,
        ?string $userId = null
    ): void {
        $this->logger->info('Data Modification', [
            'event_type' => 'data_modification',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'user_id' => $userId,
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Log compliance check
     */
    public function logComplianceCheck(
        string $checkType,
        string $entityType,
        int $entityId,
        bool $passed,
        array $details = []
    ): void {
        $this->logger->info("Compliance Check: {$checkType}", [
            'event_type' => 'compliance_check',
            'check_type' => $checkType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'passed' => $passed,
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log authentication event
     */
    public function logAuthenticationEvent(
        string $eventType,
        string $email,
        bool $successful,
        ?array $details = null
    ): void {
        $this->logger->info("Authentication Event: {$eventType}", [
            'event_type' => 'authentication',
            'auth_event_type' => $eventType,
            'email' => $email,
            'successful' => $successful,
            'details' => $details ?? [],
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Log calculation event (for traceability of amortization, interest)
     */
    public function logCalculation(
        string $calculationType,
        int $loanId,
        array $inputs,
        array $outputs
    ): void {
        $this->logger->info("Calculation: {$calculationType}", [
            'event_type' => 'calculation',
            'calculation_type' => $calculationType,
            'loan_id' => $loanId,
            'inputs' => $inputs,
            'outputs' => $outputs,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log performance metrics
     */
    public function logPerformanceMetric(
        string $metricName,
        float $value,
        string $unit = 'ms',
        array $tags = []
    ): void {
        $this->logger->info("Performance Metric: {$metricName}", [
            'event_type' => 'performance',
            'metric_name' => $metricName,
            'value' => $value,
            'unit' => $unit,
            'tags' => $tags,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log error with full context
     */
    public function logError(
        \Throwable $exception,
        string $context = 'unknown',
        array $details = []
    ): void {
        $this->logger->error("Application Error: {$context}", [
            'event_type' => 'error',
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-ID'),
        ]);
    }

    /**
     * Sanitize sensitive data before logging
     */
    private function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'ssn', 'card_number', 'routing_number', 'account_number'];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '***REDACTED***';
            }
        });

        return $data;
    }
}
