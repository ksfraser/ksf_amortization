<?php

namespace Ksfraser\Amortizations\Authentication;

use InvalidArgumentException;

/**
 * ScopeManager - OAuth2 Scope Management
 *
 * Manages API scopes and permissions for OAuth2 authentication.
 * Defines available scopes, validates scope requests, and handles
 * scope hierarchy and dependencies.
 *
 * ### Built-in Scopes
 *
 * **Loan Management:**
 * - `loan:read` - Read loan information
 * - `loan:write` - Create/update loans
 * - `loan:delete` - Delete loans
 *
 * **Schedule Management:**
 * - `schedule:read` - Read amortization schedules
 * - `schedule:export` - Export schedule data
 *
 * **Events:**
 * - `event:read` - Read loan events
 * - `event:write` - Record loan events (extra payments, skip payments, etc)
 *
 * **Analysis:**
 * - `analysis:read` - Access analytics and reports
 * - `analysis:export` - Export analysis data
 *
 * **Portfolio:**
 * - `portfolio:read` - Read portfolio overview
 * - `portfolio:write` - Manage portfolio
 *
 * **Administration:**
 * - `client:read` - Read other clients
 * - `client:write` - Create/manage clients
 * - `scope:read` - Read scope definitions
 * - `admin` - Full administrative access
 *
 * ### Usage
 * ```php
 * $scopeMgr = new ScopeManager();
 *
 * // Validate requested scopes
 * $valid = $scopeMgr->validateScopes(['loan:read', 'schedule:read']);
 *
 * // Check if scope hierarchy satisfied
 * $scopeMgr->validateScopeHierarchy(['loan:write']); // OK if plan allows
 *
 * // Get scope metadata
 * $meta = $scopeMgr->getScopeMetadata('loan:read');
 * echo $meta['description']; // "Read loan information"
 * ```
 *
 * @package Ksfraser\Amortizations\Authentication
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class ScopeManager
{
    /**
     * Registered scopes with metadata
     *
     * @var array
     */
    private $scopes = [];

    /**
     * Scope hierarchy (write implies read)
     * Format: ['write' => ['read']]
     *
     * @var array
     */
    private $hierarchy = [];

    /**
     * Default scopes for new clients
     *
     * @var array
     */
    private $defaultScopes = [];

    /**
     * Constructor - Initialize with built-in scopes
     */
    public function __construct()
    {
        $this->registerBuiltInScopes();
    }

    /**
     * Register built-in API scopes
     *
     * @return void
     */
    private function registerBuiltInScopes(): void
    {
        // Loan Management Scopes
        $this->registerScope('loan:read', [
            'category' => 'loan',
            'description' => 'Read loan information',
            'tier' => 'basic',
            'human_readable' => 'View Loans',
        ]);

        $this->registerScope('loan:write', [
            'category' => 'loan',
            'description' => 'Create and update loan records',
            'tier' => 'advanced',
            'human_readable' => 'Create/Edit Loans',
            'implies' => ['loan:read'],
        ]);

        $this->registerScope('loan:delete', [
            'category' => 'loan',
            'description' => 'Delete loan records',
            'tier' => 'admin',
            'human_readable' => 'Delete Loans',
            'implies' => ['loan:read', 'loan:write'],
        ]);

        // Schedule Management Scopes
        $this->registerScope('schedule:read', [
            'category' => 'schedule',
            'description' => 'Read amortization schedules',
            'tier' => 'basic',
            'human_readable' => 'View Schedules',
        ]);

        $this->registerScope('schedule:export', [
            'category' => 'schedule',
            'description' => 'Export amortization schedule data (CSV, PDF)',
            'tier' => 'advanced',
            'human_readable' => 'Export Schedules',
            'implies' => ['schedule:read'],
        ]);

        // Event Management Scopes
        $this->registerScope('event:read', [
            'category' => 'event',
            'description' => 'Read loan events (payments, adjustments, etc)',
            'tier' => 'basic',
            'human_readable' => 'View Events',
        ]);

        $this->registerScope('event:write', [
            'category' => 'event',
            'description' => 'Record loan events (extra payments, skip payments)',
            'tier' => 'advanced',
            'human_readable' => 'Record Events',
            'implies' => ['event:read'],
        ]);

        // Analysis Scopes
        $this->registerScope('analysis:read', [
            'category' => 'analysis',
            'description' => 'Access analytics and reports',
            'tier' => 'basic',
            'human_readable' => 'View Analytics',
        ]);

        $this->registerScope('analysis:export', [
            'category' => 'analysis',
            'description' => 'Export analysis data',
            'tier' => 'advanced',
            'human_readable' => 'Export Analytics',
            'implies' => ['analysis:read'],
        ]);

        // Portfolio Scopes
        $this->registerScope('portfolio:read', [
            'category' => 'portfolio',
            'description' => 'Read portfolio overview and summaries',
            'tier' => 'basic',
            'human_readable' => 'View Portfolio',
        ]);

        $this->registerScope('portfolio:write', [
            'category' => 'portfolio',
            'description' => 'Manage portfolio settings',
            'tier' => 'admin',
            'human_readable' => 'Manage Portfolio',
            'implies' => ['portfolio:read'],
        ]);

        // Client Management Scopes (Admin only)
        $this->registerScope('client:read', [
            'category' => 'admin',
            'description' => 'Read other clients and their settings',
            'tier' => 'admin',
            'human_readable' => 'View Clients',
        ]);

        $this->registerScope('client:write', [
            'category' => 'admin',
            'description' => 'Create and manage API clients',
            'tier' => 'admin',
            'human_readable' => 'Manage Clients',
            'implies' => ['client:read'],
        ]);

        // Scope Management Scopes (Super admin only)
        $this->registerScope('scope:read', [
            'category' => 'admin',
            'description' => 'Read available scopes and their definitions',
            'tier' => 'superadmin',
            'human_readable' => 'View Scopes',
        ]);

        // Admin Superscope
        $this->registerScope('admin', [
            'category' => 'admin',
            'description' => 'Full administrative access to all API operations',
            'tier' => 'superadmin',
            'human_readable' => 'Administrator',
            'implies' => [
                'loan:read', 'loan:write', 'loan:delete',
                'schedule:read', 'schedule:export',
                'event:read', 'event:write',
                'analysis:read', 'analysis:export',
                'portfolio:read', 'portfolio:write',
                'client:read', 'client:write',
                'scope:read',
            ],
        ]);

        // Set default scopes for new clients (read-only)
        $this->defaultScopes = ['loan:read', 'schedule:read', 'analysis:read'];
    }

    /**
     * Register custom scope (extensibility)
     *
     * @param string $scope    Scope identifier (e.g., 'loan:read')
     * @param array  $metadata Scope metadata
     *
     * @return self Fluent interface
     *
     * @throws InvalidArgumentException If scope invalid format
     */
    public function registerScope(string $scope, array $metadata = []): self
    {
        if (!$this->isValidScopeFormat($scope)) {
            throw new InvalidArgumentException("Invalid scope format: {$scope}");
        }

        $this->scopes[$scope] = array_merge([
            'category' => '',
            'description' => '',
            'tier' => 'basic',
            'human_readable' => ucfirst($scope),
            'implies' => [],
        ], $metadata);

        return $this;
    }

    /**
     * Validate scope format (category:action)
     *
     * @param string $scope Scope string
     *
     * @return bool
     */
    private function isValidScopeFormat(string $scope): bool
    {
        // Allow single word scopes like 'admin'
        if (preg_match('/^[a-z0-9_]+(\:[a-z0-9_]+)*$/', $scope)) {
            return true;
        }

        return false;
    }

    /**
     * Validate that all scopes are registered
     *
     * @param array $scopeList List of scope strings
     *
     * @return bool
     *
     * @throws InvalidArgumentException If scopes contain unregistered scopes
     */
    public function validateScopes(array $scopeList): bool
    {
        $unregistered = [];

        foreach ($scopeList as $scope) {
            if (!isset($this->scopes[$scope])) {
                $unregistered[] = $scope;
            }
        }

        if (!empty($unregistered)) {
            throw new InvalidArgumentException(
                'Unregistered scopes: ' . implode(', ', $unregistered)
            );
        }

        return true;
    }

    /**
     * Get scope metadata
     *
     * @param string $scope Scope identifier
     *
     * @return array Scope metadata
     *
     * @throws InvalidArgumentException If scope not registered
     */
    public function getScopeMetadata(string $scope): array
    {
        if (!isset($this->scopes[$scope])) {
            throw new InvalidArgumentException("Scope not registered: {$scope}");
        }

        return $this->scopes[$scope];
    }

    /**
     * Get all scopes that a given scope implies (includes itself)
     *
     * Implements scope hierarchy where 'loan:write' implies 'loan:read'
     *
     * @param array $scopeList List of granted scopes
     *
     * @return array Full list of implied scopes (deduped)
     */
    public function expandScopes(array $scopeList): array
    {
        $expanded = [];
        $toProcess = $scopeList;

        while (!empty($toProcess)) {
            $scope = array_shift($toProcess);

            if (in_array($scope, $expanded)) {
                continue; // Already processed
            }

            $expanded[] = $scope;

            // Add implied scopes to queue
            if (isset($this->scopes[$scope]['implies'])) {
                foreach ($this->scopes[$scope]['implies'] as $implied) {
                    if (!in_array($implied, $expanded) && !in_array($implied, $toProcess)) {
                        $toProcess[] = $implied;
                    }
                }
            }
        }

        return $expanded;
    }

    /**
     * Check if required scope is granted (considering hierarchy)
     *
     * @param array  $grantedScopes   List of granted scopes
     * @param string $requiredScope   Required scope
     *
     * @return bool
     */
    public function hasRequiredScope(array $grantedScopes, string $requiredScope): bool
    {
        $expanded = $this->expandScopes($grantedScopes);
        return in_array($requiredScope, $expanded);
    }

    /**
     * Get all registered scopes
     *
     * @return array All scopes keyed by scope identifier
     */
    public function getAllScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Get scopes by category
     *
     * @param string $category Category name (e.g., 'loan', 'admin')
     *
     * @return array Scopes in category
     */
    public function getScopesByCategory(string $category): array
    {
        return array_filter($this->scopes, function ($meta) use ($category) {
            return $meta['category'] === $category;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get default scopes for new clients
     *
     * @return array Default scopes
     */
    public function getDefaultScopes(): array
    {
        return $this->defaultScopes;
    }

    /**
     * Set default scopes for new clients
     *
     * @param array $scopes Default scopes
     *
     * @return self Fluent interface
     *
     * @throws InvalidArgumentException If scopes invalid
     */
    public function setDefaultScopes(array $scopes): self
    {
        $this->validateScopes($scopes);
        $this->defaultScopes = $scopes;
        return $this;
    }
}
