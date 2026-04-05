<?php

namespace Ksfraser\Amortizations\Authentication\Middleware;

use Ksfraser\Amortizations\Authentication\AuthenticationService;
use Ksfraser\Amortizations\Authentication\InvalidTokenException;
use Ksfraser\Amortizations\Authentication\ScopeManager;
use Ksfraser\Amortizations\Authentication\TokenManager;

/**
 * AuthenticationMiddleware - OAuth2 Token Validation Middleware
 *
 * Intercepts incoming API requests and validates OAuth2 Bearer tokens.
 * Enforces authentication and scope-based access control.
 *
 * ### Request Flow
 *
 * ```
 * Client Request (with Bearer token)
 *   ↓
 * AuthenticationMiddleware.handle()
 *   ├─ Extract Authorization header
 *   ├─ Parse Bearer token
 *   ├─ Validate token (signature, expiration, not revoked)
 *   ├─ Validate required scopes
 *   ├─ Check rate limiting
 *   └─ Return 401/403 if authentication/authorization fails
 *   ↓
 * API Request Handler (token attached to request)
 *   ↓
 * Response to Client
 * ```
 *
 * ### Usage
 *
 * ```php
 * $middleware = new AuthenticationMiddleware(
 *     $authService,
 *     $tokenManager,
 *     $scopeManager
 * );
 *
 * // Require specific scopes for endpoint
 * if (!$middleware->validateScope($request, 'loan:read')) {
 *     return ApiResponse::forbidden('Insufficient permissions');
 * }
 *
 * // Get authenticated client info
 * $token = $middleware->getToken($request);
 * $clientId = $token->getClientId();
 * ```
 *
 * ### HTTP Status Codes
 * - 200 OK: Token valid, request processed
 * - 400 Bad Request: Missing or malformed Authorization header
 * - 401 Unauthorized: Invalid token, expired, or revoked
 * - 403 Forbidden: Token valid but insufficient scope
 * - 429 Too Many Requests: Rate limit exceeded
 *
 * ### Security Considerations
 * - Always use HTTPS to prevent token interception
 * - Never log full tokens (log only first 8 chars)
 * - Always validate scope claims (do not trust client)
 * - Implement rate limiting to prevent abuse
 * - Track failed authentication attempts for audit
 *
 * @package Ksfraser\Amortizations\Authentication\Middleware
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class AuthenticationMiddleware
{
    /**
     * Authentication service for token validation
     *
     * @var AuthenticationService
     */
    private $authService;

    /**
     * Token manager for revocation checks
     *
     * @var TokenManager|null
     */
    private $tokenManager;

    /**
     * Scope manager for scope validation
     *
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * Authenticated token (cached after validation)
     *
     * @var \Ksfraser\Amortizations\Authentication\Token|null
     */
    private $currentToken;

    /**
     * Request context data
     *
     * @var array
     */
    private $requestContext = [];

    /**
     * Rate limit tracking (token JTI => request count)
     *
     * @var array
     */
    private $rateLimitTracker = [];

    /**
     * Constructor
     *
     * @param AuthenticationService $authService   Authentication service
     * @param ScopeManager          $scopeManager  Scope manager
     * @param TokenManager|null     $tokenManager  Token manager (optional, for revocation)
     */
    public function __construct(
        AuthenticationService $authService,
        ScopeManager $scopeManager,
        TokenManager $tokenManager = null
    ) {
        $this->authService = $authService;
        $this->scopeManager = $scopeManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Authenticate incoming request
     *
     * Extracts and validates Bearer token from Authorization header.
     * Returns true if token valid and authenticated, throws exception otherwise.
     *
     * @param array $headers HTTP headers (e.g., $_SERVER or request headers)
     *
     * @return bool True if authenticated
     *
     * @throws InvalidTokenException If token missing, invalid, or expired
     */
    public function authenticate(array $headers): bool
    {
        try {
            // Extract Authorization header
            $authHeader = $this->getAuthorizationHeader($headers);

            if (!$authHeader) {
                throw new InvalidTokenException('Missing Authorization header');
            }

            // Parse Bearer token
            $token = $this->parseBearerToken($authHeader);

            if (!$token) {
                throw new InvalidTokenException('Invalid or missing Bearer token');
            }

            // Validate token
            $this->currentToken = $this->authService->validateToken($token);

            // Check revocation if TokenManager available
            if ($this->tokenManager) {
                if ($this->tokenManager->isTokenRevoked($this->currentToken->getJti())) {
                    throw new InvalidTokenException('Token has been revoked');
                }
            }

            // Set request context
            $this->requestContext = [
                'authenticated' => true,
                'client_id' => $this->currentToken->getClientId(),
                'scopes' => $this->currentToken->getScopes(),
                'token_jti' => $this->currentToken->getJti(),
            ];

            return true;
        } catch (InvalidTokenException $e) {
            $this->requestContext = ['authenticated' => false, 'error' => $e->getMessage()];
            throw $e;
        }
    }

    /**
     * Validate that token has required scope
     *
     * Uses scope hierarchy (e.g., 'loan:write' implies 'loan:read').
     *
     * @param string $requiredScope Required scope identifier
     *
     * @return bool True if scope granted
     *
     * @throws \RuntimeException If token not authenticated
     */
    public function validateScope(string $requiredScope): bool
    {
        if (!$this->currentToken) {
            throw new \RuntimeException('Token not authenticated. Call authenticate() first.');
        }

        return $this->scopeManager->hasRequiredScope(
            $this->currentToken->getScopes(),
            $requiredScope
        );
    }

    /**
     * Validate multiple scopes (any matching)
     *
     * @param array $scopes List of acceptable scopes
     *
     * @return bool True if token has any of the scopes
     *
     * @throws \RuntimeException If token not authenticated
     */
    public function validateScopeAny(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($this->validateScope($scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate all required scopes
     *
     * @param array $scopes List of required scopes
     *
     * @return bool True if token has all scopes
     *
     * @throws \RuntimeException If token not authenticated
     */
    public function validateScopeAll(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (!$this->validateScope($scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get authenticated token
     *
     * @return \Ksfraser\Amortizations\Authentication\Token
     *
     * @throws \RuntimeException If not authenticated
     */
    public function getToken()
    {
        if (!$this->currentToken) {
            throw new \RuntimeException('Not authenticated');
        }

        return $this->currentToken;
    }

    /**
     * Get authenticated client ID
     *
     * @return string Client ID
     *
     * @throws \RuntimeException If not authenticated
     */
    public function getClientId(): string
    {
        if (!$this->currentToken) {
            throw new \RuntimeException('Not authenticated');
        }

        return $this->currentToken->getClientId();
    }

    /**
     * Get authenticated client scopes
     *
     * @return array Scopes
     *
     * @throws \RuntimeException If not authenticated
     */
    public function getScopes(): array
    {
        if (!$this->currentToken) {
            throw new \RuntimeException('Not authenticated');
        }

        return $this->currentToken->getScopes();
    }

    /**
     * Get request context
     *
     * @return array Context data including client_id, scopes, etc
     */
    public function getContext(): array
    {
        return $this->requestContext;
    }

    /**
     * Check rate limit for token
     *
     * Simple in-memory rate limiting. For production, use Redis or database.
     *
     * @param int $maxRequests   Max requests per window
     * @param int $windowSeconds Time window in seconds
     *
     * @return bool True if within limit
     *
     * @throws \RuntimeException If not authenticated
     */
    public function checkRateLimit(int $maxRequests = 100, int $windowSeconds = 60): bool
    {
        if (!$this->currentToken) {
            throw new \RuntimeException('Not authenticated');
        }

        $jti = $this->currentToken->getJti();
        $now = time();

        if (!isset($this->rateLimitTracker[$jti])) {
            $this->rateLimitTracker[$jti] = ['requests' => 0, 'window_start' => $now];
        }

        $tracker = &$this->rateLimitTracker[$jti];

        // Reset window if expired
        if (($now - $tracker['window_start']) > $windowSeconds) {
            $tracker = ['requests' => 0, 'window_start' => $now];
        }

        // Check limit
        if ($tracker['requests'] >= $maxRequests) {
            return false;
        }

        // Increment counter
        $tracker['requests']++;

        return true;
    }

    /**
     * Extract Authorization header from headers array
     *
     * @param array $headers HTTP headers
     *
     * @return string|null Authorization header value or null
     */
    private function getAuthorizationHeader(array $headers): ?string
    {
        // Check various header formats
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }

        if (isset($headers['authorization'])) {
            return $headers['authorization'];
        }

        if (isset($headers['HTTP_AUTHORIZATION'])) {
            return $headers['HTTP_AUTHORIZATION'];
        }

        return null;
    }

    /**
     * Parse Bearer token from Authorization header
     *
     * Expected format: "Bearer eyJ..."
     *
     * @param string $authHeader Authorization header value
     *
     * @return string|null Token string or null
     */
    private function parseBearerToken(string $authHeader): ?string
    {
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }
}
