<?php
/**
 * API Bootstrap
 * 
 * Initialize and start the API application.
 * Loads routes and processes incoming requests.
 * 
 * @package Ksfraser\Api
 * @author KSF Development Team
 * @version 1.0.0
 */

namespace Ksfraser\Api;

use Exception;

/**
 * Application Bootstrap
 * 
 * Initializes the API application and routes requests
 */
class Application
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var array Application configuration
     */
    protected $config = [];

    /**
     * Constructor
     * 
     * @param array $config Application configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'debug' => false,
            'base_path' => '/api',
            'timezone' => 'UTC',
        ], $config);

        $this->setupEnvironment();
    }

    /**
     * Setup application environment
     * 
     * @return void
     */
    protected function setupEnvironment(): void
    {
        // Set timezone
        date_default_timezone_set($this->config['timezone']);

        // Start session if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set error handling
        if ($this->config['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', 0);
        }

        // Set JSON response headers
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');

        // Allow CORS if configured
        $this->setupCors();
    }

    /**
     * Setup CORS headers
     * 
     * @return void
     */
    protected function setupCors(): void
    {
        // TODO: Configure allowed origins
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');

        // Handle preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Initialize application
     * 
     * Loads routes and creates router
     * 
     * @return void
     */
    public function initialize(): void
    {
        // Load routes
        $this->loadRoutes();
    }

    /**
     * Load routes from routes.php
     * 
     * @return void
     */
    protected function loadRoutes(): void
    {
        $routesFile = __DIR__ . '/routes.php';
        
        if (!file_exists($routesFile)) {
            throw new Exception('Routes file not found: ' . $routesFile);
        }

        $routes = include $routesFile;
        
        if (!is_array($routes)) {
            throw new Exception('Routes file must return an array');
        }

        // Create router with routes
        $this->router = new Router($routes);
    }

    /**
     * Handle request
     * 
     * Routes the incoming request to appropriate handler
     * 
     * @return void
     */
    public function run(): void
    {
        try {
            // Initialize if not already done
            if ($this->router === null) {
                $this->initialize();
            }

            // Route the request
            $this->router->route();
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle uncaught exceptions
     * 
     * @param Exception $e Exception that was thrown
     * 
     * @return void
     */
    protected function handleException(Exception $e): void
    {
        $statusCode = $e->getCode() ?: 500;
        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        $response = [
            'error' => true,
            'message' => $this->config['debug'] ? $e->getMessage() : 'Internal server error',
            'status' => $statusCode,
        ];

        if ($this->config['debug']) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = $e->getTraceAsString();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Get router instance
     * 
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get configuration
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}

/**
 * API Entry Point Bootstrap
 * 
 * This file should be loaded first by any entry point (like index.php in FrontAccounting module)
 * 
 * Usage:
 * 
 * ```php
 * <?php
 * 
 * // Bootstrap the API application
 * require_once __DIR__ . '/src/Ksfraser/Api/Bootstrap.php';
 * 
 * use Ksfraser\Api\Application;
 * 
 * $app = new Application([
 *     'debug' => false,
 *     'base_path' => '/modules/amortization/api',
 * ]);
 * 
 * $app->run();
 * ?>
 * ```
 * 
 * @package Ksfraser\Api
 * @author KSF Development Team
 * @version 1.0.0
 */
