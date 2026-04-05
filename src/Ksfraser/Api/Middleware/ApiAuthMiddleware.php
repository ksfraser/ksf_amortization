<?php
namespace Ksfraser\Api\Middleware;

use Ksfraser\Security\OAuth2\OAuth2Service;
use Ksfraser\Security\OAuth2\ScopeManager;
use Ksfraser\Security\Exceptions\TokenException;
use Ksfraser\Security\Exceptions\AuthenticationException;
use Ksfraser\Security\Exceptions\AuthorizationException;

/**
 * API Authentication Middleware
 * 
 * Validates OAuth2 Bearer tokens on API requests.
 * Checks token validity, expiration, and required scopes.
 * Logs all authentication attempts for audit trail.
 * 
 * Usage:
 * ```php
 * $middleware = new ApiAuthMiddleware($oauth2Service, $scopeManager);
 * $authContext = $middleware->authenticate($request);
 * 
 * // For endpoint-specific scope checking:
 * $middleware->requireScope($request, 'write');
 * ```
 * 
 * @package   Ksfraser\Api\Middleware
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-03
 */
class ApiAuthMiddleware
{
    /**
     * @var OAuth2Service
     */
    private $oauth2Service;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var \PDO Database for audit logging
     */
    private $db;

    /**
     * @var array Current request context
     */
    private $context = [];

    /**
     * ApiAuthMiddleware constructor.
     *
     * @param OAuth2Service $oauth2Service
     * @param ScopeManager $scopeManager
     * @param \PDO|null $db Database connection for logging
     */
    public function __construct(
        OAuth2Service $oauth2Service,
        ScopeManager $scopeManager,
        \PDO $db = null
    ) {
        $this->oauth2Service = $oauth2Service;
        $this->scopeManager = $scopeManager;
        $this->db = $db;
    }

    /**
     * Authenticate API request
     * 
     * Extracts and validates Bearer token from Authorization header.
     * Validates token signature, expiration, and claims.
     * Logs authentication attempt.
     *
     * @param array $headers Request headers
     * @param string $endpoint API endpoint (e.g., "GET /api/loans")
     * @param string $clientIp Client IP address
     *
     * @return array Authentication context with client_id, scopes, etc.
     *
     * @throws AuthenticationException If token invalid/missing
     */
    public function authenticate(array $headers, string $endpoint = '', string $clientIp = ''): array
    {
        try {
            // Extract Bearer token
            $token = $this->extractBearerToken($headers);
            if (!$token) {
                throw new AuthenticationException("Missing or invalid Authorization header");
            }

            // Validate token
            $claims = $this->oauth2Service->validateToken($token);

            // Build context
            $this->context = [
                'authenticated' => true,
                'client_id' => $claims['client_id'] ?? null,
                'scopes' => $claims['scopes'] ?? [],
                'type' => $claims['type'] ?? 'access',
                'iss' => $claims['iss'] ?? null,
                'aud' => $claims['aud'] ?? null,
                'iat' => $claims['iat'] ?? null,
                'exp' => $claims['exp'] ?? null,
                'endpoint' => $endpoint,
                'ip' => $clientIp,
            ];

            // Log successful authentication
            $this->logAuthAttempt(true, $endpoint, $clientIp, $claims['client_id'] ?? 'unknown');

            return $this->context;
        } catch (\Exception $e) {
            // Log failed authentication
            $this->logAuthAttempt(false, $endpoint, $clientIp, 'unknown', $e->getMessage());

            if ($e instanceof AuthenticationException) {
                throw $e;
            }

            throw new AuthenticationException("Authentication failed: " . $e->getMessage());
        }
    }

    /**
     * Require a specific scope
     * 
     * Checks if authenticated request has required scope.
     * Throws AuthorizationException if scope missing.
     *
     * @param string $requiredScope
     *
     * @return void
     *
     * @throws AuthorizationException If scope not granted
     */
    public function requireScope(string $requiredScope): void
    {
        if (!$this->context || !isset($this->context['authenticated'])) {
            throw new AuthorizationException("Request not authenticated");
        }

        try {
            $this->scopeManager->requireScope(
                $this->context['scopes'] ?? [],
                $requiredScope
            );
        } catch (AuthorizationException $e) {
            $this->logAuthAttempt(
                false,
                $this->context['endpoint'] ?? '',
                $this->context['ip'] ?? '',
                $this->context['client_id'] ?? 'unknown',
                $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * Require multiple scopes
     *
     * @param array $requiredScopes
     *
     * @return void
     *
     * @throws AuthorizationException If scopes not granted
     */
    public function requireScopes(array $requiredScopes): void
    {
        if (!$this->context || !isset($this->context['authenticated'])) {
            throw new AuthorizationException("Request not authenticated");
        }

        try {
            $this->scopeManager->requireScopes(
                $this->context['scopes'] ?? [],
                $requiredScopes
            );
        } catch (AuthorizationException $e) {
            $this->logAuthAttempt(
                false,
                $this->context['endpoint'] ?? '',
                $this->context['ip'] ?? '',
                $this->context['client_id'] ?? 'unknown',
                $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * Get current authentication context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Check if request is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->context['authenticated'] ?? false;
    }

    /**
     * Get client ID from current context
     *
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->context['client_id'] ?? null;
    }

    /**
     * Get scopes from current context
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->context['scopes'] ?? [];
    }

    /**
     * Extract Bearer token from Authorization header
     *
     * @param array $headers Request headers
     *
     * @return string|null Token or null if not found
     */
    private function extractBearerToken(array $headers): ?string
    {
        $authHeader = null;

        // Try different header name variations
        foreach (['Authorization', 'authorization', 'HTTP_AUTHORIZATION'] as $headerName) {
            if (isset($headers[$headerName])) {
                $authHeader = $headers[$headerName];
                break;
            }
        }

        if (!$authHeader) {
            return null;
        }

        // Parse Bearer token
        if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Log authentication attempt
     *
     * @param bool $success
     * @param string $endpoint
     * @param string $clientIp
     * @param string $clientId
     * @param string $reason Reason for failure (if applicable)
     *
     * @return void
     */
    private function logAuthAttempt(
        bool $success,
        string $endpoint,
        string $clientIp,
        string $clientId,
        string $reason = ''
    ): void {
        if (!$this->db) {
            return; // No database for logging
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO auth_logs (
                    client_id, endpoint, ip_address, success, reason, attempted_at
                ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $clientId,
                $endpoint,
                $clientIp,
                $success ? 1 : 0,
                $reason,
            ]);
        } catch (\PDOException $e) {
            // Log database errors but don't fail authentication
            error_log("Auth logging failed: " . $e->getMessage());
        }
    }
}
