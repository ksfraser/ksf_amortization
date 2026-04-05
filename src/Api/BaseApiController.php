<?php

namespace Ksfraser\Amortizations\Api;

use Ksfraser\Amortizations\Authentication\Middleware\AuthenticationMiddleware;

/**
 * BaseApiController - Base class for all API controllers
 *
 * Provides:
 * - Middleware support for authentication
 * - Scope-based access control
 * - Common error handling patterns
 * - Fluent configuration
 */
abstract class BaseApiController
{
    protected ?AuthenticationMiddleware $authMiddleware = null;
    protected array $requiredScopes = [];
    protected bool $requiresAuthentication = true;

    /**
     * Set authentication middleware
     *
     * @param AuthenticationMiddleware $middleware
     * @return $this
     */
    public function setAuthMiddleware(AuthenticationMiddleware $middleware): self
    {
        $this->authMiddleware = $middleware;
        return $this;
    }

    /**
     * Set required scopes for this controller
     *
     * @param string|array $scopes
     * @return $this
     */
    public function requireScopes($scopes): self
    {
        $this->requiredScopes = is_array($scopes) ? $scopes : [$scopes];
        return $this;
    }

    /**
     * Allow public access (no authentication required)
     *
     * @return $this
     */
    public function allowPublic(): self
    {
        $this->requiresAuthentication = false;
        return $this;
    }

    /**
     * Verify request has required authentication and scopes
     *
     * @param string $bearerToken The Bearer token from Authorization header
     * @return ApiResponse|null Null if verified, error response otherwise
     */
    protected function verifyRequest(string $bearerToken): ?ApiResponse
    {
        // Public endpoints don't require authentication
        if (!$this->requiresAuthentication) {
            return null;
        }

        // Middleware not configured - should not happen in production
        if (!$this->authMiddleware) {
            return ApiResponse::unauthorized('Authentication not configured');
        }

        // Verify token and extract claims
        try {
            // Remove "Bearer " prefix if present
            $token = preg_replace('/^Bearer\s+/', '', $bearerToken);

            // Call middleware to validate
            $verified = $this->authMiddleware->verify($token, $this->requiredScopes);

            if (!$verified) {
                return ApiResponse::forbidden('Insufficient permissions');
            }

            return null; // Token is valid
        } catch (\Exception $e) {
            return ApiResponse::unauthorized('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Get current request context (client ID, scopes, etc.)
     *
     * Should be implemented by controllers that need context info
     *
     * @return array
     */
    protected function getRequestContext(): array
    {
        return [
            'client_id' => null,
            'scopes' => [],
            'issued_at' => null,
            'expires_at' => null,
        ];
    }

    /**
     * Log API access for audit trail
     *
     * @param string $action The action performed
     * @param array $context Additional context
     * @return void
     */
    protected function logAccess(string $action, array $context = []): void
    {
        // TODO: Implement audit logging
        // This will be expanded in a future phase
    }
}
