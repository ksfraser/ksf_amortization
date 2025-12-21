<?php

declare(strict_types=1);

namespace Ksfraser\Api;

/**
 * Represents an HTTP API request
 *
 * Encapsulates all request data including method, URI, headers, parameters,
 * and body. Provides methods for common request operations.
 */
class ApiRequest
{
    /**
     * @var string The HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)
     */
    private string $method;

    /**
     * @var string The request URI/endpoint
     */
    private string $uri;

    /**
     * @var array<string, mixed> Request headers
     */
    private array $headers = [];

    /**
     * @var array<string, mixed> Query string parameters
     */
    private array $queryParams = [];

    /**
     * @var array<string, mixed> Request body parameters (for POST/PUT/PATCH)
     */
    private array $bodyParams = [];

    /**
     * @var mixed Raw request body (if not using bodyParams)
     */
    private mixed $rawBody = null;

    /**
     * @var array<string, mixed> Route parameters extracted from URI pattern
     */
    private array $routeParams = [];

    /**
     * @var string|null Authentication token if present
     */
    private ?string $authToken = null;

    /**
     * @var string The request timestamp
     */
    private string $timestamp;

    public function __construct(string $method, string $uri)
    {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->timestamp = date('Y-m-d H:i:s');
    }

    /**
     * Get the HTTP method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the request URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set a request header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get a request header
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set query parameter
     */
    public function setQueryParam(string $name, mixed $value): self
    {
        $this->queryParams[$name] = $value;
        return $this;
    }

    /**
     * Get query parameter
     */
    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->queryParams[$name] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Set body parameter
     */
    public function setBodyParam(string $name, mixed $value): self
    {
        $this->bodyParams[$name] = $value;
        return $this;
    }

    /**
     * Get body parameter
     */
    public function getBodyParam(string $name, mixed $default = null): mixed
    {
        return $this->bodyParams[$name] ?? $default;
    }

    /**
     * Get all body parameters
     */
    public function getBodyParams(): array
    {
        return $this->bodyParams;
    }

    /**
     * Set raw body content
     */
    public function setRawBody(mixed $body): self
    {
        $this->rawBody = $body;
        return $this;
    }

    /**
     * Get raw body
     */
    public function getRawBody(): mixed
    {
        return $this->rawBody;
    }

    /**
     * Set route parameter
     */
    public function setRouteParam(string $name, mixed $value): self
    {
        $this->routeParams[$name] = $value;
        return $this;
    }

    /**
     * Get route parameter
     */
    public function getRouteParam(string $name, mixed $default = null): mixed
    {
        return $this->routeParams[$name] ?? $default;
    }

    /**
     * Get all route parameters
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Set authentication token
     */
    public function setAuthToken(string $token): self
    {
        $this->authToken = $token;
        return $this;
    }

    /**
     * Get authentication token
     */
    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }

    /**
     * Check if authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->authToken !== null;
    }

    /**
     * Get request timestamp
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * Get all parameters (merged query + body + route)
     */
    public function getAllParams(): array
    {
        return array_merge($this->queryParams, $this->bodyParams, $this->routeParams);
    }

    /**
     * Check if parameter exists in any collection
     */
    public function hasParam(string $name): bool
    {
        return isset($this->queryParams[$name]) || 
               isset($this->bodyParams[$name]) || 
               isset($this->routeParams[$name]);
    }

    /**
     * Get parameter from any collection
     */
    public function getParam(string $name, mixed $default = null): mixed
    {
        return $this->queryParams[$name] ?? 
               $this->bodyParams[$name] ?? 
               $this->routeParams[$name] ?? 
               $default;
    }
}
