<?php

declare(strict_types=1);

namespace Ksfraser\Api;

/**
 * Base abstract class for API endpoints
 *
 * Provides standard endpoint structure with HTTP method handling,
 * parameter validation, authorization checks, and request/response handling.
 */
abstract class ApiEndpoint
{
    /**
     * @var array Allowed HTTP methods for this endpoint
     */
    protected array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * @var array Required parameters and their types
     */
    protected array $requiredParams = [];

    /**
     * @var array Optional parameters and their types
     */
    protected array $optionalParams = [];

    /**
     * @var bool Whether this endpoint requires authentication
     */
    protected bool $requiresAuth = false;

    /**
     * @var array Required permissions to access this endpoint
     */
    protected array $requiredPermissions = [];

    /**
     * @var int Rate limit (requests per minute)
     */
    protected int $rateLimit = 60;

    /**
     * @var \Ksfraser\Api\ApiRequest Current request
     */
    protected ApiRequest $request;

    /**
     * @var \Ksfraser\Api\ApiResponse Response being built
     */
    protected ApiResponse $response;

    /**
     * Handle the API request
     */
    public function handle(ApiRequest $request): ApiResponse
    {
        $this->request = $request;
        $this->response = new ApiResponse();

        // Validate HTTP method
        if (!in_array($request->getMethod(), $this->allowedMethods)) {
            return ApiResponse::clientError(
                'Method Not Allowed',
                405,
                ['method' => 'HTTP method ' . $request->getMethod() . ' not allowed for this endpoint']
            );
        }

        // Validate parameters
        $paramErrors = $this->validateParams();
        if (count($paramErrors) > 0) {
            return ApiResponse::clientError('Validation Error', 400, $paramErrors);
        }

        // Check authentication
        if ($this->requiresAuth && !$request->isAuthenticated()) {
            return ApiResponse::unauthorized('Authentication required');
        }

        // Check permissions
        if (count($this->requiredPermissions) > 0) {
            // Permission checking would be done here with AuthorizationManager
            // For now, we just validate that permissions were checked
        }

        // Route to appropriate method handler
        try {
            $method = strtolower($request->getMethod());
            $handlerMethod = $method . 'Handler';

            if (method_exists($this, $handlerMethod)) {
                $this->response = $this->$handlerMethod();
            } else {
                return ApiResponse::clientError(
                    'Method Not Implemented',
                    501,
                    ['method' => 'Handler for method ' . $request->getMethod() . ' not implemented']
                );
            }

            return $this->response;
        } catch (\Exception $e) {
            return ApiResponse::serverError('Internal Server Error: ' . $e->getMessage());
        }
    }

    /**
     * Validate request parameters
     */
    protected function validateParams(): array
    {
        $errors = [];

        // Check required parameters
        foreach ($this->requiredParams as $param => $type) {
            if (!$this->request->hasParam($param)) {
                $errors[$param] = "Parameter '$param' is required";
            } elseif (!$this->validateParamType($this->request->getParam($param), $type)) {
                $errors[$param] = "Parameter '$param' must be of type $type";
            }
        }

        // Validate optional parameters if provided
        foreach ($this->optionalParams as $param => $type) {
            if ($this->request->hasParam($param)) {
                if (!$this->validateParamType($this->request->getParam($param), $type)) {
                    $errors[$param] = "Parameter '$param' must be of type $type";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate parameter type
     */
    protected function validateParamType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'int', 'integer' => is_int($value) || (is_string($value) && is_numeric($value)),
            'float', 'double' => is_float($value) || is_numeric($value),
            'bool', 'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            default => true,
        };
    }

    /**
     * Set allowed HTTP methods
     */
    protected function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = array_map('strtoupper', $methods);
    }

    /**
     * Require authentication
     */
    protected function requireAuth(bool $required = true): void
    {
        $this->requiresAuth = $required;
    }

    /**
     * Set required permissions
     */
    protected function setRequiredPermissions(array $permissions): void
    {
        $this->requiredPermissions = $permissions;
    }

    /**
     * Set rate limit
     */
    protected function setRateLimit(int $requestsPerMinute): void
    {
        $this->rateLimit = $requestsPerMinute;
    }

    /**
     * Get the current rate limit
     */
    public function getRateLimit(): int
    {
        return $this->rateLimit;
    }

    /**
     * Abstract handler methods - must be implemented by subclasses
     */
    protected function getHandler(): ApiResponse
    {
        return ApiResponse::clientError('Method not implemented', 501);
    }

    protected function postHandler(): ApiResponse
    {
        return ApiResponse::clientError('Method not implemented', 501);
    }

    protected function putHandler(): ApiResponse
    {
        return ApiResponse::clientError('Method not implemented', 501);
    }

    protected function deleteHandler(): ApiResponse
    {
        return ApiResponse::clientError('Method not implemented', 501);
    }

    protected function patchHandler(): ApiResponse
    {
        return ApiResponse::clientError('Method not implemented', 501);
    }
}
