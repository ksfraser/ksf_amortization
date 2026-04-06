<?php
/**
 * API Router Class
 * 
 * Handles HTTP request routing to controller methods.
 * Matches requests to registered routes and executes handlers.
 * 
 * @package Ksfraser\Api
 * @author KSF Development Team
 * @version 1.0.0
 */

namespace Ksfraser\Api;

use Exception;
use RuntimeException;

/**
 * Router - HTTP Request Router
 * 
 * Matches incoming HTTP requests to defined routes and
 * dispatches to the appropriate controller method.
 */
class Router
{
    /**
     * @var array Registered routes
     */
    protected $routes = [];

    /**
     * @var array Request context
     */
    protected $request = [];

    /**
     * @var array Route parameters
     */
    protected $params = [];

    /**
     * @var string Current method
     */
    protected $method = '';

    /**
     * @var string Current path
     */
    protected $path = '';

    /**
     * Constructor
     * 
     * @param array $routes Route definitions (typically from routes.php)
     */
    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
        $this->parseRequest();
    }

    /**
     * Parse incoming HTTP request
     * 
     * Extracts method, path, query params, and body
     * 
     * @return void
     */
    protected function parseRequest(): void
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Remove base path if needed
        if (defined('API_BASE_PATH')) {
            $this->path = preg_replace('|^' . preg_quote(API_BASE_PATH) . '|', '', $this->path);
        }
        
        $this->request = [
            'method' => $this->method,
            'path' => $this->path,
            'query' => $_GET ?? [],
            'body' => $this->getRequestBody(),
            'headers' => getallheaders() ?? [],
        ];
    }

    /**
     * Get request body as array
     * 
     * @return array
     */
    protected function getRequestBody(): array
    {
        $input = file_get_contents('php://input');
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? 'application/json';
        
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($input, true);
            return is_array($data) ? $data : [];
        }
        
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($input, $data);
            return $data ?? [];
        }
        
        return [];
    }

    /**
     * Route the current request
     * 
     * Matches request to route and executes handler
     * 
     * @return void
     * @throws RuntimeException
     */
    public function route(): void
    {
        $route = $this->findRoute();
        
        if (!$route) {
            $this->notFound();
            return;
        }

        // Validate middleware
        $middleware = $route['middleware'] ?? [];
        $validation = $this->validateMiddleware($middleware);
        if ($validation !== true) {
            $this->error($validation, 403);
            return;
        }

        // Extract path parameters
        $this->params = $this->extractParams($route['path']);

        // Execute handler
        try {
            $this->execute($route);
        } catch (Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Find matching route for current request
     * 
     * @return array|null Matched route or null
     */
    protected function findRoute(): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->method) {
                continue;
            }

            if ($this->pathMatches($route['path'], $this->path)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Check if route path matches request path
     * 
     * @param string $routePath Route path pattern
     * @param string $requestPath Actual request path
     * 
     * @return bool
     */
    protected function pathMatches(string $routePath, string $requestPath): bool
    {
        $pattern = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '([^/]+)', $routePath);
        $pattern = '#^' . preg_quote($pattern, '#') . '$#';
        $pattern = preg_replace('#\\\\\(\\^/\+\\\\\)#', '([^/]+)', $pattern);
        
        return (bool) preg_match($pattern, $requestPath);
    }

    /**
     * Extract path parameters from request
     * 
     * @param string $routePath Route path pattern
     * 
     * @return array Path parameters
     */
    protected function extractParams(string $routePath): array
    {
        $params = [];
        
        // Extract parameter names from route
        if (preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $routePath, $names)) {
            $paramNames = $names[1];
            
            // Build regex to extract values
            $pattern = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '([^/]+)', $routePath);
            $pattern = '#^' . preg_quote($pattern, '#') . '$#';
            $pattern = preg_replace('#\\\\\(\\^/\+\\\\\)#', '([^/]+)', $pattern);
            
            if (preg_match($pattern, $this->path, $values)) {
                array_shift($values); // Remove full match
                
                foreach ($paramNames as $i => $name) {
                    $params[$name] = $values[$i] ?? null;
                }
            }
        }
        
        return $params;
    }

    /**
     * Validate middleware requirements
     * 
     * @param array $middleware Middleware requirements
     * 
     * @return bool|string True if valid, error message if invalid
     */
    protected function validateMiddleware(array $middleware): bool|string
    {
        foreach ($middleware as $requirement) {
            switch ($requirement) {
                case 'auth':
                    if (!$this->isAuthenticated()) {
                        return 'Unauthorized';
                    }
                    break;

                case 'admin':
                    if (!$this->isAdmin()) {
                        return 'Admin access required';
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Check if request is authenticated
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        // Check session
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id'] ?? null)) {
            return true;
        }

        // Check Authorization header
        $auth = $this->request['headers']['Authorization'] ?? '';
        return !empty($auth);
    }

    /**
     * Check if user is admin
     * 
     * @return bool
     */
    protected function isAdmin(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        return ($_SESSION['user_role'] ?? null) === 'admin';
    }

    /**
     * Execute route handler
     * 
     * @param array $route Route definition
     * 
     * @return void
     * @throws RuntimeException
     */
    protected function execute(array $route): void
    {
        [$class, $method] = explode('::', $route['handler']);
        
        // Build full class name
        $fullyQualified = 'Ksfraser\\Api\\Controllers\\' . $class;
        
        if (!class_exists($fullyQualified)) {
            throw new RuntimeException("Controller not found: {$fullyQualified}");
        }

        // Instantiate controller
        $controller = new $fullyQualified();
        
        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Method not found: {$fullyQualified}::{$method}");
        }

        // Prepare request data
        $requestData = array_merge(
            $this->request['body'],
            $this->params,
            $this->request['query']
        );

        // Call handler and send response
        $response = $controller->$method($requestData, $this->request);
        
        $this->sendResponse($response);
    }

    /**
     * Send response
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @param string $contentType Content type
     * 
     * @return void
     */
    public function sendResponse($data = null, int $statusCode = 200, string $contentType = 'application/json'): void
    {
        http_response_code($statusCode);
        header('Content-Type: ' . $contentType);
        
        if ($data !== null) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        
        exit;
    }

    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * 
     * @return void
     */
    public function error(string $message, int $statusCode = 400): void
    {
        $response = [
            'error' => true,
            'message' => $message,
            'status' => $statusCode,
            'timestamp' => gmdate('c'),
        ];

        $this->sendResponse($response, $statusCode);
    }

    /**
     * Send 404 Not Found response
     * 
     * @return void
     */
    public function notFound(): void
    {
        $response = [
            'error' => true,
            'message' => 'Route not found',
            'path' => $this->path,
            'method' => $this->method,
        ];

        $this->sendResponse($response, 404);
    }

    /**
     * Get registered routes
     * 
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get current request
     * 
     * @return array
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * Get path parameters
     * 
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
