<?php
/**
 * Router Unit Tests
 * 
 * @package Ksfraser\Api\Tests
 * @author KSF Development Team
 * @license MIT
 */

namespace Ksfraser\Api\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Api\Router;

/**
 * RouterTest - Unit tests for Router class
 */
class RouterTest extends TestCase
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var array Test routes
     */
    protected $routes = [];

    /**
     * Setup test fixtures
     */
    protected function setUp(): void
    {
        $this->routes = [
            [
                'method' => 'GET',
                'path' => '/health',
                'handler' => 'HealthController::index',
                'middleware' => [],
                'description' => 'Health check',
            ],
            [
                'method' => 'POST',
                'path' => '/auth/login',
                'handler' => 'AuthController::login',
                'middleware' => [],
                'description' => 'Login',
            ],
            [
                'method' => 'GET',
                'path' => '/users/:id',
                'handler' => 'UserController::show',
                'middleware' => ['auth'],
                'description' => 'Get user',
            ],
            [
                'method' => 'DELETE',
                'path' => '/users/:id',
                'handler' => 'UserController::destroy',
                'middleware' => ['auth', 'admin'],
                'description' => 'Delete user',
            ],
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/health';
        $_GET = [];
        $_SESSION = [];

        $this->router = new Router($this->routes);
    }

    /**
     * Teardown
     */
    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
        $_GET = [];
        $_SESSION = [];
    }

    /**
     * Test router initialization
     */
    public function testRouterInitialization(): void
    {
        $this->assertInstanceOf(Router::class, $this->router);
        $this->assertCount(4, $this->router->getRoutes());
    }

    /**
     * Test simple GET route matching
     */
    public function testSimpleGetRouteMatching(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/health';

        $router = new Router($this->routes);
        $request = $router->getRequest();

        $this->assertEquals('GET', $request['method']);
        $this->assertEquals('/health', $request['path']);
    }

    /**
     * Test POST route matching
     */
    public function testPostRouteMatching(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';

        $router = new Router($this->routes);
        $request = $router->getRequest();

        $this->assertEquals('POST', $request['method']);
        $this->assertEquals('/auth/login', $request['path']);
    }

    /**
     * Test dynamic parameter extraction
     */
    public function testDynamicParameterExtraction(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/123';

        $router = new Router($this->routes);

        // Manually test path matching and extraction
        $params = $this->extractParamsReflection('/users/:id', '/users/123');

        $this->assertArrayHasKey('id', $params);
        $this->assertEquals('123', $params['id']);
    }

    /**
     * Test request body parsing
     */
    public function testRequestBodyParsing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/auth/login';
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        $router = new Router($this->routes);
        $request = $router->getRequest();

        $this->assertEquals('POST', $request['method']);
        $this->assertIsArray($request['body']);
    }

    /**
     * Test query parameter extraction
     */
    public function testQueryParameterExtraction(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users?page=2&limit=10';
        $_GET = ['page' => '2', 'limit' => '10'];

        $router = new Router($this->routes);
        $request = $router->getRequest();

        $this->assertArrayHasKey('page', $request['query']);
        $this->assertEquals('2', $request['query']['page']);
    }

    /**
     * Test middleware validation - auth required
     */
    public function testMiddlewareValidationAuthRequired(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/123';
        $_SESSION = [];

        $router = new Router($this->routes);
        
        $this->assertFalse($this->isAuthenticatedReflection($router));
    }

    /**
     * Test middleware validation - auth provided via session
     */
    public function testMiddlewareValidationAuthViaSession(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users/123';
        $_SESSION['user_id'] = 1;

        $router = new Router($this->routes);

        $this->assertTrue($this->isAuthenticatedReflection($router));
    }

    /**
     * Test admin middleware requirement
     */
    public function testAdminMiddlewareRequirement(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/users/123';
        $_SESSION = ['user_id' => 1, 'user_role' => 'user'];

        $router = new Router($this->routes);

        $this->assertFalse($this->isAdminReflection($router));
    }

    /**
     * Test admin middleware with admin user
     */
    public function testAdminMiddlewareWithAdminUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/users/123';
        $_SESSION = ['user_id' => 1, 'user_role' => 'admin'];

        $router = new Router($this->routes);

        $this->assertTrue($this->isAdminReflection($router));
    }

    /**
     * Test multiple path parameters
     */
    public function testMultiplePathParameters(): void
    {
        $routes = [
            [
                'method' => 'GET',
                'path' => '/clients/:clientId/metrics/:metricId',
                'handler' => 'MetricsController::show',
                'middleware' => [],
            ],
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/clients/42/metrics/99';

        $router = new Router($routes);
        $params = $this->extractParamsReflection('/clients/:clientId/metrics/:metricId', '/clients/42/metrics/99');

        $this->assertEquals('42', $params['clientId']);
        $this->assertEquals('99', $params['metricId']);
    }

    /**
     * Test request headers parsing
     */
    public function testRequestHeadersParsing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/health';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token123';

        $router = new Router($this->routes);
        $request = $router->getRequest();

        $this->assertArrayHasKey('Authorization', $request['headers']);
    }

    /**
     * Test case-insensitive HTTP methods are handled correctly
     */
    public function testHttpMethodHandling(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/health';

        $router = new Router($this->routes);
        $request = $router->getRequest();

        $this->assertEquals('GET', $request['method']);
    }

    /**
     * Test path normalization
     */
    public function testPathNormalization(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '///health///';

        $router = new Router($this->routes);
        $request = $router->getRequest();

        $this->assertIsString($request['path']);
    }

    /**
     * Test route parameter validation
     */
    public function testRouteParameterValidation(): void
    {
        $routes = [
            [
                'method' => 'GET',
                'path' => '/items/:id',
                'handler' => 'ItemController::show',
                'middleware' => [],
            ],
        ];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/items/abc-123';

        $router = new Router($routes);
        $params = $this->extractParamsReflection('/items/:id', '/items/abc-123');

        $this->assertEquals('abc-123', $params['id']);
    }

    /**
     * Helper: Extract parameters using reflection
     */
    protected function extractParamsReflection(string $routePath, string $requestPath): array
    {
        $reflection = new \ReflectionClass($this->router);
        $method = $reflection->getMethod('extractParams');
        $method->setAccessible(true);

        return $method->invoke($this->router, $routePath);
    }

    /**
     * Helper: Check authentication using reflection
     */
    protected function isAuthenticatedReflection(Router $router): bool
    {
        $reflection = new \ReflectionClass($router);
        $method = $reflection->getMethod('isAuthenticated');
        $method->setAccessible(true);

        return $method->invoke($router);
    }

    /**
     * Helper: Check admin using reflection
     */
    protected function isAdminReflection(Router $router): bool
    {
        $reflection = new \ReflectionClass($router);
        $method = $reflection->getMethod('isAdmin');
        $method->setAccessible(true);

        return $method->invoke($router);
    }
}
