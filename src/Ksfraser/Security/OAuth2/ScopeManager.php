<?php
namespace Ksfraser\Security\OAuth2;

use Ksfraser\Security\Exceptions\AuthorizationException;

/**
 * Scope Manager - API permission management
 * 
 * Manages scopes and permissions for OAuth2 authentication.
 * Scopes define what a client can do with the API.
 * 
 * Built-in scopes:
 * - read: Access read-only endpoints
 * - write: Access write/modify endpoints
 * - admin: Administrative access
 * - analytics: Access to analytics data
 * - reporting: Access to reporting features
 * 
 * @package   Ksfraser\Security\OAuth2
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-03
 */
class ScopeManager
{
    /**
     * @var array Defined scopes and their descriptions
     */
    private $scopes = [
        'read' => 'Read-only access to loans and schedules',
        'write' => 'Create and modify loans and schedules',
        'delete' => 'Delete loans and related data',
        'admin' => 'Administrative access to all API operations',
        'analytics' => 'Access to portfolio analytics and reports',
        'reporting' => 'Generate and export reports',
        'webhooks' => 'Manage webhook subscriptions',
        'audit' => 'Access audit logs',
    ];

    /**
     * @var array Scope hierarchy (what implies what)
     */
    private $scopeHierarchy = [
        'admin' => ['read', 'write', 'delete', 'analytics', 'reporting', 'webhooks', 'audit'],
    ];

    /**
     * @var array Endpoint scope requirements
     */
    private $endpointScopes = [];

    /**
     * ScopeManager constructor.
     *
     * @param array $customScopes Additional custom scopes
     */
    public function __construct(array $customScopes = [])
    {
        $this->scopes = array_merge($this->scopes, $customScopes);
        $this->initializeEndpointScopes();
    }

    /**
     * Check if a set of scopes includes a required scope
     *
     * @param array $grantedScopes Scopes granted to client
     * @param string $requiredScope Required scope to check
     *
     * @return bool
     */
    public function hasScope(array $grantedScopes, string $requiredScope): bool
    {
        // Check direct scope match
        if (in_array($requiredScope, $grantedScopes, true)) {
            return true;
        }

        // Check if any granted scope implies the required scope
        foreach ($grantedScopes as $grantedScope) {
            if ($this->scopeImplies($grantedScope, $requiredScope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if multiple required scopes are present
     *
     * @param array $grantedScopes Scopes granted to client
     * @param array $requiredScopes Scopes to check
     * @param bool $requireAll If true, require all scopes; if false, require any
     *
     * @return bool
     */
    public function hasScopes(array $grantedScopes, array $requiredScopes, bool $requireAll = false): bool
    {
        if (empty($requiredScopes)) {
            return true;
        }

        foreach ($requiredScopes as $scope) {
            $hasScope = $this->hasScope($grantedScopes, $scope);
            if ($requireAll && !$hasScope) {
                return false;
            }
            if (!$requireAll && $hasScope) {
                return true;
            }
        }

        return $requireAll;
    }

    /**
     * Require a scope, throw if not present
     *
     * @param array $grantedScopes Scopes granted to client
     * @param string $requiredScope Required scope
     *
     * @return void
     *
     * @throws AuthorizationException If scope not granted
     */
    public function requireScope(array $grantedScopes, string $requiredScope): void
    {
        if (!$this->hasScope($grantedScopes, $requiredScope)) {
            throw new AuthorizationException("Insufficient permissions. Required scope: {$requiredScope}");
        }
    }

    /**
     * Require multiple scopes, throw if not all present
     *
     * @param array $grantedScopes Scopes granted to client
     * @param array $requiredScopes Required scopes
     *
     * @return void
     *
     * @throws AuthorizationException If scopes not granted
     */
    public function requireScopes(array $grantedScopes, array $requiredScopes): void
    {
        $missing = [];
        foreach ($requiredScopes as $scope) {
            if (!$this->hasScope($grantedScopes, $scope)) {
                $missing[] = $scope;
            }
        }

        if (!empty($missing)) {
            throw new AuthorizationException(
                "Missing required scopes: " . implode(', ', $missing)
            );
        }
    }

    /**
     * Get all defined scopes
     *
     * @return array [scope => description, ...]
     */
    public function getAllScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Get scope description
     *
     * @param string $scope
     *
     * @return string|null
     */
    public function getScopeDescription(string $scope): ?string
    {
        return $this->scopes[$scope] ?? null;
    }

    /**
     * Validate that requested scopes exist
     *
     * @param array $requestedScopes
     *
     * @return array Invalid scopes
     */
    public function validateScopes(array $requestedScopes): array
    {
        $invalid = [];
        foreach ($requestedScopes as $scope) {
            if (!isset($this->scopes[$scope])) {
                $invalid[] = $scope;
            }
        }
        return $invalid;
    }

    /**
     * Get required scopes for an endpoint
     *
     * @param string $endpoint Endpoint path (e.g., "POST /api/loans")
     *
     * @return array Required scopes
     */
    public function getEndpointScopes(string $endpoint): array
    {
        return $this->endpointScopes[$endpoint] ?? ['read'];
    }

    /**
     * Register required scopes for an endpoint
     *
     * @param string $endpoint
     * @param array $requiredScopes
     *
     * @return void
     */
    public function registerEndpoint(string $endpoint, array $requiredScopes): void
    {
        $this->endpointScopes[$endpoint] = $requiredScopes;
    }

    /**
     * Check if one scope implies another
     *
     * @param string $scopeA
     * @param string $scopeB
     *
     * @return bool True if scopeA implies scopeB
     */
    private function scopeImplies(string $scopeA, string $scopeB): bool
    {
        if (!isset($this->scopeHierarchy[$scopeA])) {
            return false;
        }

        return in_array($scopeB, $this->scopeHierarchy[$scopeA], true);
    }

    /**
     * Initialize default endpoint scope requirements
     *
     * @return void
     */
    private function initializeEndpointScopes(): void
    {
        $this->endpointScopes = [
            // Loans
            'GET /api/loans' => ['read'],
            'GET /api/loans/{id}' => ['read'],
            'POST /api/loans' => ['write'],
            'PUT /api/loans/{id}' => ['write'],
            'DELETE /api/loans/{id}' => ['delete'],

            // Schedules
            'GET /api/loans/{id}/schedule' => ['read'],
            'POST /api/loans/{id}/schedule/generate' => ['write'],
            'POST /api/loans/{id}/schedule/post' => ['write'],

            // Analytics
            'GET /api/analytics/portfolio' => ['analytics'],
            'GET /api/analytics/performance' => ['analytics'],
            'GET /api/analytics/yields' => ['analytics'],

            // Reports
            'GET /api/reports' => ['reporting'],
            'POST /api/reports/generate' => ['reporting'],
            'GET /api/reports/{id}/export' => ['reporting'],

            // Webhooks
            'GET /api/webhooks' => ['webhooks'],
            'POST /api/webhooks' => ['webhooks'],
            'DELETE /api/webhooks/{id}' => ['webhooks'],

            // Audit
            'GET /api/audit/logs' => ['audit'],
            'GET /api/audit/events' => ['audit'],
        ];
    }
}
