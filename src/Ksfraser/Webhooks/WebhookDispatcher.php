<?php

declare(strict_types=1);

namespace Ksfraser\Webhooks;

/**
 * Webhook event object
 *
 * Represents an event that can be dispatched to registered webhooks.
 */
class WebhookEvent
{
    private string $eventType;
    private mixed $data;
    private string $timestamp;
    private string $id;

    public function __construct(string $eventType, mixed $data = null)
    {
        $this->eventType = $eventType;
        $this->data = $data;
        $this->timestamp = date('Y-m-d H:i:s');
        $this->id = uniqid('evt_', true);
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->eventType,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
        ];
    }
}

/**
 * Webhook registry and dispatcher
 *
 * Manages webhook registration and event dispatching with retry logic
 * and payload signing for security.
 */
class WebhookDispatcher
{
    /**
     * @var array Registered webhooks
     */
    private array $webhooks = [];

    /**
     * @var array Event history
     */
    private array $eventHistory = [];

    /**
     * @var int Max retry attempts
     */
    private int $maxRetries = 3;

    /**
     * @var string Secret key for signing payloads
     */
    private string $secretKey = '';

    /**
     * @var array Request logs
     */
    private array $requestLogs = [];

    public function __construct(string $secretKey = '')
    {
        $this->secretKey = $secretKey ?: hash('sha256', uniqid());
    }

    /**
     * Register a webhook
     */
    public function register(string $eventType, string $url, array $headers = []): void
    {
        if (!isset($this->webhooks[$eventType])) {
            $this->webhooks[$eventType] = [];
        }

        $this->webhooks[$eventType][] = [
            'url' => $url,
            'headers' => $headers,
            'created_at' => date('Y-m-d H:i:s'),
            'active' => true,
        ];
    }

    /**
     * Unregister a webhook
     */
    public function unregister(string $eventType, string $url): bool
    {
        if (!isset($this->webhooks[$eventType])) {
            return false;
        }

        $this->webhooks[$eventType] = array_filter(
            $this->webhooks[$eventType],
            fn($webhook) => $webhook['url'] !== $url
        );

        return true;
    }

    /**
     * Dispatch an event to registered webhooks
     */
    public function dispatch(WebhookEvent $event): array
    {
        $eventType = $event->getEventType();
        $results = [];

        if (!isset($this->webhooks[$eventType])) {
            return $results;
        }

        $payload = $event->toArray();
        $signature = $this->generateSignature($payload);

        foreach ($this->webhooks[$eventType] as $webhook) {
            if (!$webhook['active']) {
                continue;
            }

            $result = $this->sendWebhook($webhook, $payload, $signature);
            $results[] = $result;

            // Log request
            $this->requestLogs[] = [
                'event_id' => $event->getId(),
                'event_type' => $eventType,
                'url' => $webhook['url'],
                'status' => $result['status'],
                'attempts' => $result['attempts'],
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }

        // Store event in history
        $this->eventHistory[] = [
            'event' => $event->toArray(),
            'dispatched_to' => count($results),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return $results;
    }

    /**
     * Send webhook to URL with retry logic
     */
    private function sendWebhook(array $webhook, array $payload, string $signature): array
    {
        $attempts = 0;
        $lastError = '';

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            $attempts++;

            try {
                // In a real implementation, this would use curl or http client
                // For testing purposes, we'll simulate the request
                $result = $this->simulateHttpRequest($webhook['url'], $payload, $signature, $webhook['headers']);

                if ($result['success']) {
                    return [
                        'url' => $webhook['url'],
                        'status' => 'success',
                        'attempts' => $attempts,
                        'response' => $result['response'],
                    ];
                }

                $lastError = $result['error'];
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }

            // Wait before retry (exponential backoff)
            if ($attempt < $this->maxRetries) {
                usleep(1000 * pow(2, $attempt)); // 2ms, 4ms, 8ms, etc.
            }
        }

        return [
            'url' => $webhook['url'],
            'status' => 'failed',
            'attempts' => $attempts,
            'error' => $lastError,
        ];
    }

    /**
     * Simulate HTTP request
     */
    private function simulateHttpRequest(string $url, array $payload, string $signature, array $headers): array
    {
        // In a real implementation, this would make an actual HTTP request
        // For testing, we'll just validate the signature
        $expectedSignature = $this->generateSignature($payload);

        if ($signature !== $expectedSignature) {
            return [
                'success' => false,
                'error' => 'Invalid signature',
                'response' => null,
            ];
        }

        return [
            'success' => true,
            'response' => ['status' => 'ok'],
        ];
    }

    /**
     * Generate HMAC signature for payload
     */
    public function generateSignature(array $payload): string
    {
        $json = json_encode($payload, JSON_SORT_KEYS);
        return 'sha256=' . hash_hmac('sha256', $json, $this->secretKey);
    }

    /**
     * Verify payload signature
     */
    public function verifySignature(array $payload, string $signature): bool
    {
        return hash_equals($this->generateSignature($payload), $signature);
    }

    /**
     * Get all webhooks
     */
    public function getWebhooks(): array
    {
        return $this->webhooks;
    }

    /**
     * Get webhooks for event type
     */
    public function getWebhooksForEvent(string $eventType): array
    {
        return $this->webhooks[$eventType] ?? [];
    }

    /**
     * Get event history
     */
    public function getEventHistory(int $limit = 100): array
    {
        return array_slice($this->eventHistory, -$limit);
    }

    /**
     * Get request logs
     */
    public function getRequestLogs(int $limit = 100): array
    {
        return array_slice($this->requestLogs, -$limit);
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $totalEvents = count($this->eventHistory);
        $totalWebhooks = array_sum(array_map('count', $this->webhooks));
        $totalRequests = count($this->requestLogs);

        $successfulRequests = count(array_filter($this->requestLogs, fn($log) => $log['status'] === 'success'));
        $failedRequests = count(array_filter($this->requestLogs, fn($log) => $log['status'] === 'failed'));

        return [
            'total_events' => $totalEvents,
            'total_webhooks' => $totalWebhooks,
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $failedRequests,
            'success_rate' => $totalRequests > 0 ? round($successfulRequests / $totalRequests * 100, 2) : 0,
        ];
    }

    /**
     * Set max retry attempts
     */
    public function setMaxRetries(int $maxRetries): void
    {
        $this->maxRetries = $maxRetries;
    }

    /**
     * Disable webhook temporarily
     */
    public function disableWebhook(string $eventType, string $url): bool
    {
        if (!isset($this->webhooks[$eventType])) {
            return false;
        }

        foreach ($this->webhooks[$eventType] as &$webhook) {
            if ($webhook['url'] === $url) {
                $webhook['active'] = false;
                return true;
            }
        }

        return false;
    }

    /**
     * Enable webhook
     */
    public function enableWebhook(string $eventType, string $url): bool
    {
        if (!isset($this->webhooks[$eventType])) {
            return false;
        }

        foreach ($this->webhooks[$eventType] as &$webhook) {
            if ($webhook['url'] === $url) {
                $webhook['active'] = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Clear all history
     */
    public function clearHistory(): void
    {
        $this->eventHistory = [];
        $this->requestLogs = [];
    }
}
