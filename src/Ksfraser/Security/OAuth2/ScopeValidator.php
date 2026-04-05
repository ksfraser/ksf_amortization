<?php
namespace Ksfraser\Security\OAuth2;

use Ksfraser\Security\Exceptions\AuthorizationException;

/**
 * Scope Validator
 * 
 * Validates and matches OAuth2 scopes with support for hierarchical scope patterns.
 * Supports general scopes (read/write/delete) and resource-specific scopes.
 * 
 * Scope Hierarchy Examples:
 * - amortization:read - read access to all amortization resources
 * - amortization:portfolio:read - read access to amortization portfolio
 * - amortization:analysis:write - write access to analysis
 * - portfolio:delete - delete portfolio operations
 * - admin:* - admin access to all resources
 * 
 * @package   Ksfraser\Security\OAuth2
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class ScopeValidator
{
    /**
     * Verify if granted scopes include required scope
     * 
     * Supports wildcard matching and hierarchical scopes:
     * - Exact match: grantedScopes contains requiredScope
     * - Wildcard: admin:* matches admin:read, admin:write, etc.
     * - Hierarchical: amortization:* matches amortization:read, amortization:portfolio:read
     *
     * @param array $grantedScopes Scopes granted to client
     * @param string $requiredScope Scope required for operation
     *
     * @return bool True if scope granted, false otherwise
     */
    public function hasScope(array $grantedScopes, string $requiredScope): bool
    {
        if (empty($grantedScopes) || empty($requiredScope)) {
            return false;
        }

        foreach ($grantedScopes as $grantedScope) {
            if ($this->scopeMatches($grantedScope, $requiredScope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Require scope - throw exception if not granted
     *
     * @param array $grantedScopes Scopes granted to client
     * @param string $requiredScope Scope required for operation
     *
     * @return void
     *
     * @throws AuthorizationException If scope not granted
     */
    public function requireScope(array $grantedScopes, string $requiredScope): void
    {
        if (!$this->hasScope($grantedScopes, $requiredScope)) {
            throw new AuthorizationException(
                "Insufficient scope: '{$requiredScope}' required"
            );
        }
    }

    /**
     * Check if any of multiple required scopes are granted
     * 
     * Useful for endpoints that accept multiple alternative scopes.
     * Example: endpoint allows either read access OR admin access
     *
     * @param array $grantedScopes Scopes granted to client
     * @param array $requiredScopes Any one of these scopes required
     *
     * @return bool True if any required scope is granted
     */
    public function hasScopeAny(array $grantedScopes, array $requiredScopes): bool
    {
        foreach ($requiredScopes as $scope) {
            if ($this->hasScope($grantedScopes, $scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Require any of multiple scopes - throw exception if none granted
     *
     * @param array $grantedScopes Scopes granted to client
     * @param array $requiredScopes Any one of these scopes required
     *
     * @return void
     *
     * @throws AuthorizationException If none of required scopes granted
     */
    public function requireScopeAny(array $grantedScopes, array $requiredScopes): void
    {
        if (!$this->hasScopeAny($grantedScopes, $requiredScopes)) {
            $scopeList = implode(', ', $requiredScopes);
            throw new AuthorizationException(
                "Insufficient scope: one of [{$scopeList}] required"
            );
        }
    }

    /**
     * Check if all of multiple required scopes are granted
     * 
     * Useful for endpoints that require multiple permissions.
     * Example: bulk operations requiring both read and write
     *
     * @param array $grantedScopes Scopes granted to client
     * @param array $requiredScopes All of these scopes required
     *
     * @return bool True if all required scopes granted
     */
    public function hasScopeAll(array $grantedScopes, array $requiredScopes): bool
    {
        foreach ($requiredScopes as $scope) {
            if (!$this->hasScope($grantedScopes, $scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Require all of multiple scopes - throw exception if any missing
     *
     * @param array $grantedScopes Scopes granted to client
     * @param array $requiredScopes All of these scopes required
     *
     * @return void
     *
     * @throws AuthorizationException If any required scope is missing
     */
    public function requireScopeAll(array $grantedScopes, array $requiredScopes): void
    {
        if (!$this->hasScopeAll($grantedScopes, $requiredScopes)) {
            $scopeList = implode(', ', $requiredScopes);
            throw new AuthorizationException(
                "Insufficient scope: all of [{$scopeList}] required"
            );
        }
    }

    /**
     * Get scopes matching a permission level
     * 
     * Returns all granted scopes that match a permission (read/write/delete)
     * Examples:
     * - Getting all :read scopes: amortization:read, portfolio:read
     * - Getting all :write scopes for amortization: amortization:write, amortization:portfolio:write
     *
     * @param array $grantedScopes Scopes granted to client
     * @param string $permission Permission suffix: read, write, delete
     * @param string $prefix Optional prefix filter: amortization, portfolio
     *
     * @return array Matching scopes
     */
    public function getScopesForPermission(
        array $grantedScopes,
        string $permission = 'read',
        string $prefix = ''
    ): array {
        $matching = [];

        foreach ($grantedScopes as $scope) {
            // Check if scope ends with :permission
            if (substr($scope, -strlen(":{$permission}")) === ":{$permission}") {
                // If prefix specified, check if scope starts with prefix
                if (empty($prefix) || strpos($scope, "{$prefix}:") === 0) {
                    $matching[] = $scope;
                }
            }
            // Check for wildcard scopes
            elseif (substr($scope, -2) === ':*') {
                $wildcard = substr($scope, 0, -2);
                if (empty($prefix) || strpos("{$prefix}:", $wildcard . ':') === 0) {
                    $matching[] = $scope;
                }
            }
        }

        return $matching;
    }

    /**
     * Filter scopes for a specific resource
     * 
     * Returns scopes granted for a specific resource.
     * Example: getting all scopes for "amortization:portfolio"
     *
     * @param array $grantedScopes All granted scopes
     * @param string $resource Resource identifier
     *
     * @return array Scopes for this resource
     */
    public function getScopesForResource(array $grantedScopes, string $resource): array
    {
        $matching = [];

        foreach ($grantedScopes as $scope) {
            if ($this->scopeAppliesToResource($scope, $resource)) {
                $matching[] = $scope;
            }
        }

        return $matching;
    }

    /**
     * Check if a scope matches a resource
     * 
     * Examples:
     * - amortization:* matches amortization (all amortization resources)
     * - amortization:portfolio:read matches amortization:portfolio resource
     * - admin:* matches any resource
     *
     * @param string $scope Granted scope
     * @param string $resource Resource identifier
     *
     * @return bool True if scope applies to resource
     */
    private function scopeAppliesToResource(string $scope, string $resource): bool
    {
        // Exact match on resource prefix
        if (strpos($scope, $resource . ':') === 0) {
            return true;
        }

        // Check wildcard matches
        $parts = explode(':', $scope);
        $resourceParts = explode(':', $resource);

        // admin:* matches anything
        if ($parts[0] === 'admin' && end($parts) === '*') {
            return true;
        }

        // Check hierarchical wildcard: amortization:* matches amortization:*
        if (end($parts) === '*') {
            $wildcardPrefix = implode(':', array_slice($parts, 0, -1));
            $resourcePrefix = implode(':', array_slice($resourceParts, 0, count($parts) - 1));
            if ($wildcardPrefix === $resourcePrefix) {
                return true;
            }
        }

        return false;
    }

    /**
     * Internal: Check if one scope matches another
     * 
     * Handles exact matches and wildcards:
     * - admin:* matches admin:read, admin:write
     * - amortization:* matches amortization:read, amortization:portfolio:read
     * - amortization:read exactly matches amortization:read
     *
     * @param string $grantedScope  Granted scope
     * @param string $requiredScope Required scope
     *
     * @return bool True if granted scope matches required
     */
    private function scopeMatches(string $grantedScope, string $requiredScope): bool
    {
        // Exact match
        if ($grantedScope === $requiredScope) {
            return true;
        }

        // Granted scope is a wildcard
        if (substr($grantedScope, -2) === ':*') {
            $prefix = substr($grantedScope, 0, -2);
            // Check if required scope starts with wildcard prefix
            if (strpos($requiredScope, $prefix . ':') === 0) {
                return true;
            }
        }

        // Special case: admin:* grants all scopes
        if ($grantedScope === 'admin:*') {
            return true;
        }

        return false;
    }

    /**
     * Validate scope format
     * 
     * Scope format: [resource]:[permission] or [resource]:[subresource]:[permission]
     * Examples: read, write, delete, amortization:read, portfolio:write, admin:*
     *
     * @param string $scope Scope to validate
     *
     * @return bool True if scope format is valid
     */
    public function isValidScopeFormat(string $scope): bool
    {
        if (empty($scope)) {
            return false;
        }

        // Must contain at least one colon for resource:permission format
        // OR be a simple permission: read, write, delete
        $parts = explode(':', $scope);

        if (count($parts) < 2 && !in_array($scope, ['read', 'write', 'delete'])) {
            return false;
        }

        // Each part must be alphanumeric, underscore, or asterisk
        foreach ($parts as $part) {
            if (!preg_match('/^[a-zA-Z0-9_*]+$/', $part)) {
                return false;
            }
        }

        // If last part is *, only allow for wildcard (admin:*, resource:*)
        if (end($parts) === '*' && count($parts) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Normalize scope list (remove duplicates, sort)
     *
     * @param array $scopes Scope list
     *
     * @return array Normalized list
     */
    public function normalizeScopeList(array $scopes): array
    {
        $normalized = array_unique(array_filter($scopes));
        sort($normalized);
        return array_values($normalized);
    }

    /**
     * Parse scope string to array
     * 
     * Handles both space-separated and comma-separated scope formats
     *
     * @param string $scopeString Scope string "read write delete" or "read,write,delete"
     *
     * @return array Parsed scopes
     */
    public function parseScopeString(string $scopeString): array
    {
        if (empty($scopeString)) {
            return [];
        }

        // Try both space and comma separators
        if (strpos($scopeString, ',') !== false) {
            $scopes = explode(',', $scopeString);
        } else {
            $scopes = explode(' ', $scopeString);
        }

        // Trim and filter
        $scopes = array_map('trim', $scopes);
        $scopes = array_filter($scopes);

        return array_values($scopes);
    }
}
