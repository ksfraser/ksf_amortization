<?php

declare(strict_types=1);

namespace Ksfraser\Api;

use Ksfraser\Performance\QueryOptimizer;
use Ksfraser\Caching\CacheManager;

/**
 * API Router - manages route registration and request dispatching
 *
 * Routes incoming requests to appropriate endpoints based on URI patterns,
 * implements rate limiting, caching, and performance monitoring.
 */
class ApiRouter
{
    /**
     * @var array<string, array<string, ApiEndpoint>> Routes mapping [method][pattern] => endpoint
     */
    private array $routes = [];

    /**
     * @var array<string, int> Request counts for rate limiting
     */
    private array $requestCounts = [];

    /**
     * @var QueryOptimizer|null Optional query optimizer
     */
    private ?QueryOptimizer $queryOptimizer = null;

    /**
     * @var CacheManager|null Optional cache manager
     */
    private ?CacheManager $cacheManager = null;

    /**
     * @var array<string, string> Middleware handlers
     */
    private array $middlewares = [];

    /**
     * @var int Request counter for metrics
     */
    private int $requestCount = 0;

    /**
     * @var float Total request processing time
     */
    private float $totalProcessingTime = 0.0;

    public function __construct()
    {
        // Initialize request counts for rate limiting (cleared every minute)
        $this->cleanupOldRequestCounts();
    }

    /**
     * Register a route
     */
    public function register(string $method, string $pattern, ApiEndpoint $endpoint): void
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }
        $this->routes[$method][$pattern] = $endpoint;
    }

    /**
     * Register multiple routes at once
     */
    public function registerRoutes(array $routes): void
    {
        foreach ($routes as ['method' => $method, 'pattern' => $pattern, 'endpoint' => $endpoint]) {
            $this->register($method, $pattern, $endpoint);
        }
    }

    /**
     * Set query optimizer for performance monitoring
     */
    public function setQueryOptimizer(QueryOptimizer $optimizer): self
    {
        $this->queryOptimizer = $optimizer;
        return $this;
    }

    /**
     * Set cache manager for response caching
     */
    public function setCacheManager(CacheManager $cacheManager): self
    {
        $this->cacheManager = $cacheManager;
        return $this;
    }

    /**
     * Add middleware
     */
    public function addMiddleware(string $name, string $handler): self
    {
        $this->middlewares[$name] = $handler;
        return $this;
    }

    /**
     * Route and handle a request
     */
    public function route(ApiRequest $request): ApiResponse
    {
        $startTime = microtime(true);
        $this->requestCount++;

        try {
            // Check rate limiting
            $clientId = $this->getClientIdentifier($request);
            if (!$this->checkRateLimit($clientId)) {
                return ApiResponse::clientError('Rate limit exceeded', 429);
            }

            // Try to get cached response for GET requests
            if ($request->getMethod() === 'GET' && $this->cacheManager !== null) {
                $cacheKey = $this->generateCacheKey($request);
                $cachedResponse = $this->cacheManager->get($cacheKey);
                if ($cachedResponse !== null) {
                    $cachedResponse->addMetadata('cached', true);
                    return $cachedResponse;
                }
            }

            // Find matching route
            $endpoint = $this->findMatchingEndpoint($request);
            if ($endpoint === null) {
                return ApiResponse::notFound('Route not found');
            }

            // Handle request through endpoint
            $response = $endpoint->handle($request);

            // Cache successful GET responses
            if ($request->getMethod() === 'GET' && $response->isSuccess() && $this->cacheManager !== null) {
                $cacheKey = $this->generateCacheKey($request);
                $this->cacheManager->set($cacheKey, $response, 300); // Cache for 5 minutes
            }

            // Add timing metadata
            $processingTime = microtime(true) - $startTime;
            $this->totalProcessingTime += $processingTime;
            $response->addMetadata('processing_time_ms', round($processingTime * 1000, 2));
            $response->addMetadata('request_id', $this->generateRequestId());

            return $response;
        } catch (\Exception $e) {
            return ApiResponse::serverError('Internal Server Error: ' . $e->getMessage());
        }
    }

    /**
     * Find matching endpoint for request
     */
    private function findMatchingEndpoint(ApiRequest $request): ?ApiEndpoint
    {
        $method = $request->getMethod();
        if (!isset($this->routes[$method])) {
            return null;
        }

        $uri = $request->getUri();
        foreach ($this->routes[$method] as $pattern => $endpoint) {
            $params = $this->matchUriPattern($pattern, $uri);
            if ($params !== null) {
                // Set extracted route parameters
                foreach ($params as $name => $value) {
                    $request->setRouteParam($name, $value);
                }
                return $endpoint;
            }
        }

        return null;
    }

    /**
     * Match URI against pattern
     *
     * Supports patterns like:
     * - /loans/{id}
     * - /users/{userId}/loans/{loanId}
     */
    private function matchUriPattern(string $pattern, string $uri): ?array
    {
        // Convert pattern to regex
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Filter out numeric keys (full match and numbered groups)
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return null;
    }

    /**
     * Check rate limit for client
     */
    private function checkRateLimit(string $clientId): bool
    {
        $key = 'rate_limit_' . $clientId . '_' . date('Y-m-d H:i');
        
        if (!isset($this->requestCounts[$key])) {
            $this->requestCounts[$key] = 0;
        }

        $this->requestCounts[$key]++;

        // Get default rate limit (60 requests per minute)
        $limit = 60;

        return $this->requestCounts[$key] <= $limit;
    }

    /**
     * Get client identifier (IP address or user ID)
     */
    private function getClientIdentifier(ApiRequest $request): string
    {
        // Check for authenticated user
        if ($request->isAuthenticated()) {
            return 'user_' . $request->getAuthToken();
        }

        // Fall back to IP address (in real scenario, get from $_SERVER)
        return 'ip_' . md5('127.0.0.1');
    }

    /**
     * Generate cache key for request
     */
    private function generateCacheKey(ApiRequest $request): string
    {
        $key = $request->getMethod() . ':' . $request->getUri();
        
        if (count($request->getQueryParams()) > 0) {
            ksort($request->getQueryParams());
            $key .= '?' . http_build_query($request->getQueryParams());
        }

        return 'api_response:' . md5($key);
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Clean up old request counts (older than 2 minutes)
     */
    private function cleanupOldRequestCounts(): void
    {
        $now = date('Y-m-d H:i');
        foreach ($this->requestCounts as $key => $count) {
            // Simple cleanup - in production, use timestamps
            if (!str_contains($key, $now)) {
                unset($this->requestCounts[$key]);
            }
        }
    }

    /**
     * Get router metrics
     */
    public function getMetrics(): array
    {
        return [
            'total_requests' => $this->requestCount,
            'total_processing_time_ms' => round($this->totalProcessingTime * 1000, 2),
            'avg_processing_time_ms' => $this->requestCount > 0 
                ? round(($this->totalProcessingTime / $this->requestCount) * 1000, 2) 
                : 0,
            'registered_routes' => count($this->routes),
        ];
    }

    /**
     * Get registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Clear all routes
     */
    public function clearRoutes(): void
    {
        $this->routes = [];
    }
}
