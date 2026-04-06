<?php
/**
 * BaseController Unit Tests
 * 
 * @package Ksfraser\Api\Tests
 * @author KSF Development Team
 * @license MIT
 */

namespace Ksfraser\Api\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Api\Controllers\BaseController;

/**
 * Test concrete implementation of BaseController
 */
class TestController extends BaseController
{
    public function testMethod()
    {
        return 'test';
    }
}

/**
 * BaseControllerTest - Unit tests for BaseController class
 */
class BaseControllerTest extends TestCase
{
    /**
     * @var TestController
     */
    protected $controller;

    /**
     * Setup test fixtures
     */
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $this->controller = new TestController();
    }

    /**
     * Teardown
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    /**
     * Test controller can be instantiated
     */
    public function testControllerInstantiation(): void
    {
        $this->assertInstanceOf(BaseController::class, $this->controller);
    }

    /**
     * Test authentication check - not authenticated
     */
    public function testAuthenticationCheckNotAuthenticated(): void
    {
        $_SESSION = [];
        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('authenticated');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($controller));
    }

    /**
     * Test authentication check - authenticated via session
     */
    public function testAuthenticationCheckViaSession(): void
    {
        $_SESSION['user_id'] = 1;
        session_status() === PHP_SESSION_NONE && session_start();

        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('authenticated');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($controller));
    }

    /**
     * Test admin role check - not admin
     */
    public function testAdminRoleCheckNotAdmin(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'user';
        session_status() === PHP_SESSION_NONE && session_start();

        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isAdmin');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller));
    }

    /**
     * Test admin role check - is admin
     */
    public function testAdminRoleCheckIsAdmin(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        session_status() === PHP_SESSION_NONE && session_start();

        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isAdmin');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller));
    }

    /**
     * Test require auth throws exception when not authenticated
     */
    public function testRequireAuthThrowsException(): void
    {
        $_SESSION = [];
        $controller = new TestController();

        $this->expectException(\Exception::class);
        
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($controller);
    }

    /**
     * Test require auth succeeds when authenticated
     */
    public function testRequireAuthSucceeds(): void
    {
        $_SESSION['user_id'] = 1;
        session_status() === PHP_SESSION_NONE && session_start();
        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($controller);
        $this->assertTrue(true);
    }

    /**
     * Test require admin throws exception when not admin
     */
    public function testRequireAdminThrowsException(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'user';
        session_status() === PHP_SESSION_NONE && session_start();

        $controller = new TestController();

        $this->expectException(\Exception::class);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('requireAdmin');
        $method->setAccessible(true);
        $method->invoke($controller);
    }

    /**
     * Test required field validation
     */
    public function testRequiredFieldValidation(): void
    {
        $controller = new TestController();
        $data = ['email' => 'test@example.com', 'password' => 'secret'];

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateRequired');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($controller, $data, ['email', 'password']);
        $this->assertTrue(true);
    }

    /**
     * Test required field validation missing field
     */
    public function testRequiredFieldValidationMissingField(): void
    {
        $controller = new TestController();
        $data = ['email' => 'test@example.com'];

        $this->expectException(\Exception::class);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateRequired');
        $method->setAccessible(true);
        $method->invoke($controller, $data, ['email', 'password']);
    }

    /**
     * Test type validation
     */
    public function testTypeValidation(): void
    {
        $controller = new TestController();
        $data = ['count' => 5, 'name' => 'Test', 'active' => true];

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateTypes');
        $method->setAccessible(true);

        $schema = ['count' => 'int', 'name' => 'string', 'active' => 'bool'];
        
        // Should not throw
        $method->invoke($controller, $data, $schema);
        $this->assertTrue(true);
    }

    /**
     * Test type validation failure
     */
    public function testTypeValidationFailure(): void
    {
        $controller = new TestController();
        $data = ['count' => 'invalid'];

        $this->expectException(\Exception::class);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateTypes');
        $method->setAccessible(true);
        $method->invoke($controller, $data, ['count' => 'int']);
    }

    /**
     * Test email validation
     */
    public function testEmailValidation(): void
    {
        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isValidEmail');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, 'test@example.com'));
        $this->assertFalse($method->invoke($controller, 'invalid-email'));
    }

    /**
     * Test URL validation
     */
    public function testUrlValidation(): void
    {
        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isValidUrl');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, 'https://example.com'));
        $this->assertFalse($method->invoke($controller, 'invalid-url'));
    }

    /**
     * Test success response format
     */
    public function testSuccessResponseFormat(): void
    {
        $controller = new TestController();
        $data = ['id' => 1, 'name' => 'Test'];

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('success');
        $method->setAccessible(true);

        $response = $method->invoke($controller, $data, 200);

        $this->assertTrue($response['success']);
        $this->assertEquals($data, $response['data']);
        $this->assertEquals(200, $response['status']);
    }

    /**
     * Test error response format
     */
    public function testErrorResponseFormat(): void
    {
        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('error');
        $method->setAccessible(true);

        $response = $method->invoke($controller, 'Something went wrong', 400);

        $this->assertTrue($response['error']);
        $this->assertEquals('Something went wrong', $response['message']);
        $this->assertEquals(400, $response['status']);
    }

    /**
     * Test paginated response format
     */
    public function testPaginatedResponseFormat(): void
    {
        $controller = new TestController();
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('paginated');
        $method->setAccessible(true);

        $response = $method->invoke($controller, $items, 1, 20, 100);

        $this->assertTrue($response['success']);
        $this->assertEqual($items, $response['data']);
        $this->assertArrayHasKey('pagination', $response);
        $this->assertEquals(1, $response['pagination']['page']);
        $this->assertEquals(20, $response['pagination']['pageSize']);
        $this->assertEquals(100, $response['pagination']['total']);
    }

    /**
     * Test get query parameter with default
     */
    public function testGetQueryParameterWithDefault(): void
    {
        $controller = new TestController();
        $query = ['page' => '1'];

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getQuery');
        $method->setAccessible(true);

        $value = $method->invoke($controller, $query, 'page', 1);
        $this->assertEquals('1', $value);

        $value = $method->invoke($controller, $query, 'missing', 'default');
        $this->assertEquals('default', $value);
    }

    /**
     * Test logging
     */
    public function testLogging(): void
    {
        $controller = new TestController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('log');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($controller, 'test_action', ['key' => 'value']);
        $this->assertTrue(true);
    }
}
