<?php
/**
 * API Route Registration
 * 
 * Registers all REST API endpoints with their methods and handlers.
 * This file maps HTTP requests to appropriate controller methods.
 * 
 * Route structure: METHOD /path => ControllerClass::method
 * 
 * @package Ksfraser\Api
 * @author KSF Development Team
 * @version 1.0.0
 */

namespace Ksfraser\Api;

/**
 * Define all API routes
 * 
 * Structure: 
 * [
 *   'method' => 'GET|POST|PUT|DELETE|PATCH',
 *   'path' => '/api/v1/...',
 *   'handler' => 'ControllerNamespace::methodName',
 *   'middleware' => ['auth', 'admin'], // Optional
 *   'description' => 'Human-readable description',
 * ]
 */
return [
    // ========================================
    // Authentication & Authorization Routes
    // ========================================
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/login',
        'handler' => 'AuthorizationController::login',
        'description' => 'Authenticate user with credentials',
        'examples' => [
            'request' => '{"email": "user@example.com", "password": "secret"}',
            'response' => '{"success": true, "user": {...}, "token": "..."}',
        ],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/auth/authorize',
        'handler' => 'AuthorizationController::getAuthorization',
        'middleware' => ['auth'],
        'description' => 'Get authorization details for client and scopes',
        'query' => ['client_id', 'redirect_uri', 'scope', 'state'],
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/authorize',
        'handler' => 'AuthorizationController::submitConsent',
        'middleware' => ['auth'],
        'description' => 'Submit consent/approval for scopes',
        'examples' => [
            'request' => '{"client_id": "...", "scopes": ["profile", "email"], "approved": true}',
        ],
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/token',
        'handler' => 'AuthorizationController::exchangeToken',
        'description' => 'Exchange authorization code for access token',
        'examples' => [
            'request' => '{"code": "...", "client_id": "...", "client_secret": "..."}',
            'response' => '{"access_token": "...", "expires_in": 3600}',
        ],
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/verify',
        'handler' => 'AuthorizationController::verifyToken',
        'middleware' => ['auth'],
        'description' => 'Verify token validity and get user info',
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/refresh',
        'handler' => 'AuthorizationController::refreshToken',
        'description' => 'Refresh access token using refresh token',
        'examples' => [
            'request' => '{"refresh_token": "..."}',
            'response' => '{"access_token": "...", "expires_in": 3600}',
        ],
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/auth/logout',
        'handler' => 'AuthorizationController::logout',
        'middleware' => ['auth'],
        'description' => 'Logout user and invalidate token',
    ],
    
    // ========================================
    // User Routes
    // ========================================
    [
        'method' => 'GET',
        'path' => '/api/v1/user/profile',
        'handler' => 'UserController::getProfile',
        'middleware' => ['auth'],
        'description' => 'Get authenticated user profile',
    ],
    
    [
        'method' => 'PUT',
        'path' => '/api/v1/user/profile',
        'handler' => 'UserController::updateProfile',
        'middleware' => ['auth'],
        'description' => 'Update user profile',
        'examples' => [
            'request' => '{"name": "John Doe", "email": "john@example.com"}',
        ],
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/user/change-password',
        'handler' => 'UserController::changePassword',
        'middleware' => ['auth'],
        'description' => 'Change user password',
        'examples' => [
            'request' => '{"current_password": "...", "new_password": "..."}',
        ],
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/user/2fa/setup',
        'handler' => 'UserController::setup2FA',
        'middleware' => ['auth'],
        'description' => 'Setup two-factor authentication',
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/user/2fa/verify',
        'handler' => 'UserController::verify2FA',
        'middleware' => ['auth'],
        'description' => 'Verify 2FA code',
        'examples' => [
            'request' => '{"code": "123456"}',
        ],
    ],
    
    [[],
        'method' => 'GET',
        'path' => '/api/v1/user/tokens',
        'handler' => 'UserController::getTokens',
        'middleware' => ['auth'],
        'description' => 'Get API tokens/keys',
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/user/tokens',
        'handler' => 'UserController::createToken',
        'middleware' => ['auth'],
        'description' => 'Create new API token',
        'examples' => [
            'request' => '{"name": "Mobile App", "scopes": ["api.read"]}',
        ],
    ],
    
    [
        'method' => 'DELETE',
        'path' => '/api/v1/user/tokens/:id',
        'handler' => 'UserController::deleteToken',
        'middleware' => ['auth'],
        'description' => 'Revoke API token',
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/user/consents',
        'handler' => 'UserController::getConsents',
        'middleware' => ['auth'],
        'description' => 'Get granted consents',
    ],
    
    [
        'method' => 'DELETE',
        'path' => '/api/v1/user/consents/:id',
        'handler' => 'UserController::revokeConsent',
        'middleware' => ['auth'],
        'description' => 'Revoke granted consent',
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/user/sessions',
        'handler' => 'UserController::getSessions',
        'middleware' => ['auth'],
        'description' => 'Get active sessions',
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/user/sessions/:id/logout',
        'handler' => 'UserController::logoutSession',
        'middleware' => ['auth'],
        'description' => 'Logout from specific device/session',
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/user/audit-log',
        'handler' => 'UserController::getAuditLog',
        'middleware' => ['auth'],
        'description' => 'Get account audit log',
    ],
    
    // ========================================
    // Client Management Routes (Admin Only)
    // ========================================
    [
        'method' => 'GET',
        'path' => '/api/v1/clients',
        'handler' => 'AdminController::listClients',
        'middleware' => ['auth', 'admin'],
        'description' => 'List all OAuth2 clients',
        'query' => ['page', 'limit', 'filter', 'sort'],
        'examples' => [
            'response' => '[{"id": 1, "name": "...", "client_id": "...", "status": "active"}]',
        ],
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/clients',
        'handler' => 'AdminController::createClient',
        'middleware' => ['auth', 'admin'],
        'description' => 'Create new OAuth2 client',
        'examples' => [
            'request' => '{"name": "Mobile App", "redirect_uris": [...], "scopes": [...]}',
            'response' => '{"id": 1, "name": "...", "client_id": "...", "client_secret": "..."}',
        ],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/clients/:id',
        'handler' => 'AdminController::getClient',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get client details',
    ],
    
    [
        'method' => 'PUT',
        'path' => '/api/v1/clients/:id',
        'handler' => 'AdminController::updateClient',
        'middleware' => ['auth', 'admin'],
        'description' => 'Update client configuration',
    ],
    
    [
        'method' => 'DELETE',
        'path' => '/api/v1/clients/:id',
        'handler' => 'AdminController::deleteClient',
        'middleware' => ['auth', 'admin'],
        'description' => 'Delete client',
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/clients/:id/regenerate-secret',
        'handler' => 'AdminController::regenerateSecret',
        'middleware' => ['auth', 'admin'],
        'description' => 'Regenerate client secret',
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/clients/:id/tokens',
        'handler' => 'AdminController::getClientTokens',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get tokens issued to client',
    ],
    
    [
        'method' => 'DELETE',
        'path' => '/api/v1/clients/:id/tokens/:token_id',
        'handler' => 'AdminController::revokeClientToken',
        'middleware' => ['auth', 'admin'],
        'description' => 'Revoke specific token issued to client',
    ],
    
    // ========================================
    // Metrics & Analytics Routes (Admin Only)
    // ========================================
    [
        'method' => 'GET',
        'path' => '/api/v1/metrics/overview',
        'handler' => 'MetricsController::getOverview',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get metrics overview (requests, errors, response times)',
        'query' => ['period' => '24h|7d|30d', 'start_date', 'end_date'],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/metrics/requests',
        'handler' => 'MetricsController::getRequests',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get request metrics breakdown',
        'query' => ['period', 'group_by' => 'endpoint|status|hour|day'],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/metrics/errors',
        'handler' => 'MetricsController::getErrors',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get error metrics and breakdown',
        'query' => ['period', 'status_code'],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/metrics/response-time',
        'handler' => 'MetricsController::getResponseTime',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get response time metrics',
        'query' => ['period', 'endpoint'],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/metrics/endpoints',
        'handler' => 'MetricsController::getEndpointStats',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get statistics by endpoint',
        'query' => ['period', 'sort' => 'requests|errors|response_time'],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/metrics/users',
        'handler' => 'MetricsController::getUserStats',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get statistics by user/client',
        'query' => ['period'],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/metrics/export',
        'handler' => 'MetricsController::exportMetrics',
        'middleware' => ['auth', 'admin'],
        'description' => 'Export metrics data',
        'query' => ['period', 'format' => 'csv|json|xlsx'],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/metrics/audit',
        'handler' => 'AdminController::getAuditLog',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get system audit log',
        'query' => ['action', 'user_id', 'limit', 'offset'],
    ],
    
    // ========================================
    // Health & Status Routes
    // ========================================
    [
        'method' => 'GET',
        'path' => '/api/v1/health',
        'handler' => 'AdminController::getHealth',
        'description' => 'Health check endpoint (no auth required)',
        'examples' => [
            'response' => '{"status": "healthy", "timestamp": "2026-04-05T10:00:00Z"}',
        ],
    ],
    
    [
        'method' => 'GET',
        'path' => '/api/v1/status',
        'handler' => 'AdminController::getStatus',
        'description' => 'Get system status',
    ],
    
    // ========================================
    // Configuration & Settings (Admin Only)
    // ========================================
    [
        'method' => 'GET',
        'path' => '/api/v1/settings',
        'handler' => 'AdminController::getSettings',
        'middleware' => ['auth', 'admin'],
        'description' => 'Get system settings',
    ],
    
    [
        'method' => 'PUT',
        'path' => '/api/v1/settings',
        'handler' => 'AdminController::updateSettings',
        'middleware' => ['auth', 'admin'],
        'description' => 'Update system settings',
    ],
    
    // ========================================
    // Scope Management (Admin Only)
    // ========================================
    [
        'method' => 'GET',
        'path' => '/api/v1/scopes',
        'handler' => 'AdminController::listScopes',
        'middleware' => ['auth', 'admin'],
        'description' => 'List available scopes',
    ],
    
    [
        'method' => 'POST',
        'path' => '/api/v1/scopes',
        'handler' => 'AdminController::createScope',
        'middleware' => ['auth', 'admin'],
        'description' => 'Create new scope',
    ],
    
    [
        'method' => 'PUT',
        'path' => '/api/v1/scopes/:id',
        'handler' => 'AdminController::updateScope',
        'middleware' => ['auth', 'admin'],
        'description' => 'Update scope',
    ],
    
    [
        'method' => 'DELETE',
        'path' => '/api/v1/scopes/:id',
        'handler' => 'AdminController::deleteScope',
        'middleware' => ['auth', 'admin'],
        'description' => 'Delete scope',
    ],
];


/**
 * Route Registration Helper Functions
 * 
 * These functions assist in matching requests to route handlers
 */

/**
 * Match incoming request to a route
 * 
 * @param string $method HTTP method (GET, POST, PUT, DELETE)
 * @param string $path Request path
 * @param array $routes Route definitions
 * 
 * @return array|null Matched route config or null
 */
function matchRoute(string $method, string $path, array $routes): ?array
{
    foreach ($routes as $route) {
        if ($route['method'] !== $method) {
            continue;
        }
        
        // Convert path pattern to regex
        $pattern = preg_replace('/:[^\/]+/', '[^/]+', $route['path']);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $path, $matches)) {
            return $route;
        }
    }
    
    return null;
}

/**
 * Extract path parameters from request path
 * 
 * @param string $pattern Route pattern
 * @param string $path Actual request path
 * 
 * @return array Parameters
 */
function extractPathParams(string $pattern, string $path): array
{
    $paramNames = [];
    preg_match_all('/:([^\/]+)/', $pattern, $matches);
    $paramNames = $matches[1];
    
    $regex = preg_replace('/:([^\/]+)/', '([^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';
    
    if (preg_match($regex, $path, $matches)) {
        array_shift($matches); // Remove full match
        return array_combine($paramNames, $matches);
    }
    
    return [];
}

/**
 * Check if user is authenticated
 * 
 * @return bool
 */
function isAuthenticated(): bool
{
    return !empty($_SESSION['user_id'] ?? null) || !empty($_SERVER['HTTP_AUTHORIZATION'] ?? null);
}

/**
 * Check if user is admin
 * 
 * @return bool
 */
function isAdmin(): bool
{
    return isAuthenticated() && ($_SESSION['user_role'] ?? null) === 'admin';
}

/**
 * Validate middleware requirements
 * 
 * @param array $middleware Required middleware
 * 
 * @return bool|string True if valid, error message string if invalid
 */
function validateMiddleware(array $middleware): bool|string
{
    foreach ($middleware as $requirement) {
        switch ($requirement) {
            case 'auth':
                if (!isAuthenticated()) {
                    return 'Unauthorized: Authentication required';
                }
                break;
            case 'admin':
                if (!isAdmin()) {
                    return 'Forbidden: Admin privileges required';
                }
                break;
        }
    }
    
    return true;
}
