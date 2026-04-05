<?php

namespace Ksfraser\Amortizations\Api;

/**
 * API Routing Configuration
 * 
 * Defines all protected API endpoints, their HTTP methods, and required OAuth2 scopes.
 * 
 * Format:
 * 'METHOD /path' => [
 *     'controller' => ControllerClass::class,
 *     'method' => 'methodName',
 *     'scopes' => ['required', 'scopes'],
 *     'description' => 'What this endpoint does'
 * ]
 */
class Routing
{
    /**
     * Map of all protected API endpoints with their scope requirements
     */
    public static array $protectedRoutes = [
        // Analysis Endpoints
        'GET /api/v1/analysis/compare' => [
            'controller' => AnalysisController::class,
            'method' => 'compare',
            'scopes' => ['analysis:read'],
            'description' => 'Compare multiple loans',
            'requiresAuth' => true,
        ],
        'POST /api/v1/analysis/forecast' => [
            'controller' => AnalysisController::class,
            'method' => 'forecast',
            'scopes' => ['analysis:advanced'],
            'description' => 'Forecast early payoff with extra payments',
            'requiresAuth' => true,
        ],
        'GET /api/v1/analysis/recommendations' => [
            'controller' => AnalysisController::class,
            'method' => 'recommendations',
            'scopes' => ['analysis:advanced'],
            'description' => 'Get recommendations based on loan analysis',
            'requiresAuth' => true,
        ],
        'GET /api/v1/analysis/timeline' => [
            'controller' => AnalysisController::class,
            'method' => 'timeline',
            'scopes' => ['analysis:advanced'],
            'description' => 'Get debt payoff timeline',
            'requiresAuth' => true,
        ],

        // Loan Analysis Endpoints
        'POST /api/v1/loans/analyze' => [
            'controller' => LoanAnalysisController::class,
            'method' => 'analyze',
            'scopes' => ['loan:read'],
            'description' => 'Analyze loan parameters',
            'requiresAuth' => true,
        ],
        'GET /api/v1/loans/rates' => [
            'controller' => LoanAnalysisController::class,
            'method' => 'getRates',
            'scopes' => ['loan:read'],
            'description' => 'Get current market interest rates',
            'requiresAuth' => true,
        ],
        'POST /api/v1/loans/compare' => [
            'controller' => LoanAnalysisController::class,
            'method' => 'compare',
            'scopes' => ['loan:read'],
            'description' => 'Compare multiple loans',
            'requiresAuth' => true,
        ],

        // Portfolio Endpoints
        'POST /api/v1/portfolio/analyze' => [
            'controller' => PortfolioController::class,
            'method' => 'analyze',
            'scopes' => ['portfolio:read'],
            'description' => 'Analyze entire portfolio',
            'requiresAuth' => true,
        ],
        'GET /api/v1/portfolio/{id}' => [
            'controller' => PortfolioController::class,
            'method' => 'retrieve',
            'scopes' => ['portfolio:read'],
            'description' => 'Retrieve specific portfolio',
            'requiresAuth' => true,
        ],
        'GET /api/v1/portfolio/{id}/yield' => [
            'controller' => PortfolioController::class,
            'method' => 'getYield',
            'scopes' => ['portfolio:read'],
            'description' => 'Calculate portfolio yield',
            'requiresAuth' => true,
        ],

        // Reporting Endpoints
        'POST /api/v1/reports/generate' => [
            'controller' => ReportingController::class,
            'method' => 'generate',
            'scopes' => ['report:read'],
            'description' => 'Generate report in specified format',
            'requiresAuth' => true,
        ],
        'POST /api/v1/reports/export' => [
            'controller' => ReportingController::class,
            'method' => 'export',
            'scopes' => ['report:write'],
            'description' => 'Export report data to external system',
            'requiresAuth' => true,
        ],
    ];

    /**
     * Public endpoints that don't require authentication
     */
    public static array $publicRoutes = [
        'POST /api/v1/auth/token' => [
            'controller' => AuthController::class,
            'method' => 'token',
            'description' => 'Generate OAuth2 access token',
            'requiresAuth' => false,
        ],
        'POST /api/v1/auth/refresh' => [
            'controller' => AuthController::class,
            'method' => 'refresh',
            'description' => 'Refresh OAuth2 access token',
            'requiresAuth' => false,
        ],
        'GET /api/v1/auth/scopes' => [
            'controller' => AuthController::class,
            'method' => 'listScopes',
            'description' => 'List available OAuth2 scopes',
            'requiresAuth' => false,
        ],
        'GET /api/v1/health' => [
            'description' => 'Health check endpoint',
            'requiresAuth' => false,
        ],
    ];

    /**
     * Protected endpoints requiring token revocation
     */
    public static array $revokeEndpoints = [
        'POST /api/v1/auth/revoke' => [
            'controller' => AuthController::class,
            'method' => 'revoke',
            'description' => 'Revoke an OAuth2 token',
            'requiresAuth' => false, // Can be called with expired token
        ],
        'POST /api/v1/auth/logout' => [
            'controller' => AuthController::class,
            'method' => 'logout',
            'description' => 'Logout client and revoke all tokens',
            'requiresAuth' => true,
        ],
    ];

    /**
     * Get route definition by path and method
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path URL path
     * @return array|null Route definition or null if not found
     */
    public static function getRoute(string $method, string $path): ?array
    {
        $routeKey = "$method $path";
        
        // Check protected routes first
        if (isset(self::$protectedRoutes[$routeKey])) {
            return self::$protectedRoutes[$routeKey];
        }
        
        // Check public routes
        if (isset(self::$publicRoutes[$routeKey])) {
            return self::$publicRoutes[$routeKey];
        }
        
        // Check revoke endpoints
        if (isset(self::$revokeEndpoints[$routeKey])) {
            return self::$revokeEndpoints[$routeKey];
        }
        
        return null;
    }

    /**
     * Check if a route requires authentication
     * 
     * @param string $method HTTP method
     * @param string $path URL path
     * @return bool True if authentication is required
     */
    public static function requiresAuthentication(string $method, string $path): bool
    {
        $route = self::getRoute($method, $path);
        return $route ? ($route['requiresAuth'] ?? false) : false;
    }

    /**
     * Get required scopes for a route
     * 
     * @param string $method HTTP method
     * @param string $path URL path
     * @return array List of required scopes
     */
    public static function getRequiredScopes(string $method, string $path): array
    {
        $route = self::getRoute($method, $path);
        return $route ? ($route['scopes'] ?? []) : [];
    }

    /**
     * Get all protected endpoints grouped by controller
     * 
     * @return array Routes grouped by controller class
     */
    public static function getProtectedByController(): array
    {
        $grouped = [];
        
        foreach (self::$protectedRoutes as $route => $config) {
            $controller = $config['controller'] ?? 'Unknown';
            if (!isset($grouped[$controller])) {
                $grouped[$controller] = [];
            }
            $grouped[$controller][$route] = $config;
        }
        
        return $grouped;
    }

    /**
     * Get all protected endpoints grouped by scope
     * 
     * @return array Routes grouped by required scope
     */
    public static function getProtectedByScope(): array
    {
        $grouped = [];
        
        foreach (self::$protectedRoutes as $route => $config) {
            foreach ($config['scopes'] ?? [] as $scope) {
                if (!isset($grouped[$scope])) {
                    $grouped[$scope] = [];
                }
                $grouped[$scope][$route] = $config;
            }
        }
        
        return $grouped;
    }

    /**
     * Generate API documentation
     * 
     * @return string Markdown-formatted API documentation
     */
    public static function generateDocumentation(): string
    {
        $doc = "# API Routing Documentation\n\n";
        $doc .= "## Protected Endpoints (Require OAuth2 Token)\n\n";
        
        foreach (self::$protectedRoutes as $route => $config) {
            $scopes = implode(', ', $config['scopes'] ?? []);
            $doc .= "### $route\n";
            $doc .= "- **Description:** " . ($config['description'] ?? 'N/A') . "\n";
            $doc .= "- **Required Scopes:** $scopes\n";
            $doc .= "- **Controller:** " . ($config['controller'] ?? 'N/A') . "\n";
            $doc .= "- **Method:** " . ($config['method'] ?? 'N/A') . "\n\n";
        }
        
        $doc .= "## Public Endpoints (No Authentication Required)\n\n";
        
        foreach (self::$publicRoutes as $route => $config) {
            $doc .= "### $route\n";
            $doc .= "- **Description:** " . ($config['description'] ?? 'N/A') . "\n\n";
        }
        
        return $doc;
    }
}
