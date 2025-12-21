<?php

declare(strict_types=1);

namespace Ksfraser\Api;

/**
 * Represents an HTTP API response
 *
 * Standardizes response format with status codes, messages, data payload,
 * and metadata. Supports multiple content types and serialization formats.
 */
class ApiResponse
{
    /**
     * @var int HTTP status code
     */
    private int $statusCode = 200;

    /**
     * @var string Response message
     */
    private string $message = 'OK';

    /**
     * @var mixed Response data payload
     */
    private mixed $data = null;

    /**
     * @var array<string, mixed> Response metadata (timing, pagination, etc.)
     */
    private array $metadata = [];

    /**
     * @var array<string, mixed> Response headers
     */
    private array $headers = [];

    /**
     * @var array<string, string> Error details if response is an error
     */
    private array $errors = [];

    /**
     * @var string Response timestamp
     */
    private string $timestamp;

    public function __construct(int $statusCode = 200, string $message = 'OK', mixed $data = null)
    {
        $this->statusCode = $statusCode;
        $this->message = $message;
        $this->data = $data;
        $this->timestamp = date('Y-m-d H:i:s');
        $this->setHeader('Content-Type', 'application/json');
        $this->setHeader('X-API-Version', '1.0');
    }

    /**
     * Set the HTTP status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set the response message
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get the response message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the response data
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the response data
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Add metadata
     */
    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get specific metadata value
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set response header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get response header
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all response headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Add an error
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        if ($this->statusCode === 200) {
            $this->statusCode = 400;
        }
        return $this;
    }

    /**
     * Get all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if response has errors
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Check if response is successful (2xx status code)
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is a client error (4xx status code)
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is a server error (5xx status code)
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Get response timestamp
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * Convert response to JSON array
     */
    public function toArray(): array
    {
        $response = [
            'status' => $this->statusCode,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if (count($this->metadata) > 0) {
            $response['metadata'] = $this->metadata;
        }

        if (count($this->errors) > 0) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }

    /**
     * Convert response to JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Create a success response
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): self
    {
        return new self($statusCode, $message, $data);
    }

    /**
     * Create a client error response
     */
    public static function clientError(string $message = 'Bad Request', int $statusCode = 400, array $errors = []): self
    {
        $response = new self($statusCode, $message);
        foreach ($errors as $field => $error) {
            $response->addError($field, $error);
        }
        return $response;
    }

    /**
     * Create a server error response
     */
    public static function serverError(string $message = 'Internal Server Error', int $statusCode = 500): self
    {
        return new self($statusCode, $message);
    }

    /**
     * Create an unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(401, $message);
    }

    /**
     * Create a forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(403, $message);
    }

    /**
     * Create a not found response
     */
    public static function notFound(string $message = 'Not Found'): self
    {
        return new self(404, $message);
    }
}
