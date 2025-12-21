<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use Ksfraser\Api\ApiRequest;
use Ksfraser\Api\ApiResponse;
use Ksfraser\Api\ApiEndpoint;
use Ksfraser\Api\ApiRouter;

class ApiRequestPhase19Test extends TestCase
{
    private ApiRequest $request;

    protected function setUp(): void
    {
        $this->request = new ApiRequest('GET', '/api/loans/123');
    }

    public function testRequestCreation(): void
    {
        $this->assertEquals('GET', $this->request->getMethod());
        $this->assertEquals('/api/loans/123', $this->request->getUri());
    }

    public function testSetAndGetHeaders(): void
    {
        $this->request->setHeader('Authorization', 'Bearer token123');
        $this->request->setHeader('Content-Type', 'application/json');

        $this->assertEquals('Bearer token123', $this->request->getHeader('Authorization'));
        $this->assertEquals('application/json', $this->request->getHeader('Content-Type'));
        $this->assertNull($this->request->getHeader('Non-Existent'));
    }

    public function testSetAndGetQueryParams(): void
    {
        $this->request->setQueryParam('page', 1);
        $this->request->setQueryParam('limit', 10);
        $this->request->setQueryParam('sort', 'name');

        $this->assertEquals(1, $this->request->getQueryParam('page'));
        $this->assertEquals(10, $this->request->getQueryParam('limit'));
        $this->assertEquals('name', $this->request->getQueryParam('sort'));
        $this->assertNull($this->request->getQueryParam('non_existent'));
    }

    public function testSetAndGetBodyParams(): void
    {
        $this->request->setBodyParam('name', 'John Doe');
        $this->request->setBodyParam('email', 'john@example.com');
        $this->request->setBodyParam('amount', 5000.50);

        $this->assertEquals('John Doe', $this->request->getBodyParam('name'));
        $this->assertEquals('john@example.com', $this->request->getBodyParam('email'));
        $this->assertEquals(5000.50, $this->request->getBodyParam('amount'));
    }

    public function testSetAndGetRouteParams(): void
    {
        $this->request->setRouteParam('id', 123);
        $this->request->setRouteParam('userId', 456);

        $this->assertEquals(123, $this->request->getRouteParam('id'));
        $this->assertEquals(456, $this->request->getRouteParam('userId'));
    }

    public function testAuthentication(): void
    {
        $this->assertFalse($this->request->isAuthenticated());
        $this->assertNull($this->request->getAuthToken());

        $this->request->setAuthToken('token123');
        $this->assertTrue($this->request->isAuthenticated());
        $this->assertEquals('token123', $this->request->getAuthToken());
    }

    public function testFluentInterface(): void
    {
        $result = $this->request
            ->setHeader('X-Custom', 'header')
            ->setQueryParam('page', 1)
            ->setBodyParam('name', 'Test')
            ->setAuthToken('token');

        $this->assertInstanceOf(ApiRequest::class, $result);
    }

    public function testGetAllParams(): void
    {
        $this->request->setQueryParam('query', 'test');
        $this->request->setBodyParam('body', 'data');
        $this->request->setRouteParam('id', 123);

        $allParams = $this->request->getAllParams();
        $this->assertEquals('test', $allParams['query']);
        $this->assertEquals('data', $allParams['body']);
        $this->assertEquals(123, $allParams['id']);
    }

    public function testHasParam(): void
    {
        $this->request->setQueryParam('test', 'value');
        
        $this->assertTrue($this->request->hasParam('test'));
        $this->assertFalse($this->request->hasParam('nonexistent'));
    }

    public function testGetParam(): void
    {
        $this->request->setQueryParam('q1', 'value1');
        $this->request->setBodyParam('b1', 'value2');

        $this->assertEquals('value1', $this->request->getParam('q1'));
        $this->assertEquals('value2', $this->request->getParam('b1'));
        $this->assertEquals('default', $this->request->getParam('nonexistent', 'default'));
    }

    public function testRequestTimestamp(): void
    {
        $timestamp = $this->request->getTimestamp();
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $timestamp);
    }
}

class ApiResponsePhase19Test extends TestCase
{
    public function testSuccessResponse(): void
    {
        $response = ApiResponse::success(['id' => 1, 'name' => 'Test'], 'Resource created', 201);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Resource created', $response->getMessage());
        $this->assertIsArray($response->getData());
        $this->assertTrue($response->isSuccess());
    }

    public function testClientErrorResponse(): void
    {
        $errors = ['email' => 'Invalid email', 'name' => 'Name required'];
        $response = ApiResponse::clientError('Validation failed', 400, $errors);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertTrue($response->isClientError());
        $this->assertTrue($response->hasErrors());
        $this->assertEquals($errors, $response->getErrors());
    }

    public function testServerErrorResponse(): void
    {
        $response = ApiResponse::serverError('Database connection failed', 500);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue($response->isServerError());
    }

    public function testUnauthorizedResponse(): void
    {
        $response = ApiResponse::unauthorized('Missing authorization token');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue($response->isClientError());
    }

    public function testForbiddenResponse(): void
    {
        $response = ApiResponse::forbidden('Insufficient permissions');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertTrue($response->isClientError());
    }

    public function testNotFoundResponse(): void
    {
        $response = ApiResponse::notFound('Loan not found');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($response->isClientError());
    }

    public function testAddMetadata(): void
    {
        $response = new ApiResponse(200, 'Success', ['data' => 'test']);
        $response->addMetadata('total', 100);
        $response->addMetadata('page', 1);

        $this->assertEquals(100, $response->getMetadataValue('total'));
        $this->assertEquals(1, $response->getMetadataValue('page'));
        $this->assertEquals('default', $response->getMetadataValue('nonexistent', 'default'));
    }

    public function testAddError(): void
    {
        $response = new ApiResponse();
        $response->addError('email', 'Invalid email format');
        $response->addError('phone', 'Phone number is required');

        $this->assertTrue($response->hasErrors());
        $this->assertCount(2, $response->getErrors());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testSetHeader(): void
    {
        $response = new ApiResponse();
        $response->setHeader('X-Custom', 'value');

        $this->assertEquals('value', $response->getHeader('X-Custom'));
    }

    public function testToArray(): void
    {
        $response = new ApiResponse(201, 'Created', ['id' => 1]);
        $response->addMetadata('resource_type', 'loan');
        
        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(201, $array['status']);
        $this->assertEquals('Created', $array['message']);
    }

    public function testToJson(): void
    {
        $response = new ApiResponse(200, 'Success', ['id' => 1]);
        $json = $response->toJson();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals(200, $decoded['status']);
    }

    public function testFluentInterface(): void
    {
        $response = (new ApiResponse())
            ->setStatusCode(201)
            ->setMessage('Created')
            ->setData(['id' => 1])
            ->addMetadata('type', 'loan')
            ->setHeader('X-Custom', 'header');

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Created', $response->getMessage());
    }
}

class SimpleTestEndpoint extends ApiEndpoint
{
    protected function getHandler(): ApiResponse
    {
        return new ApiResponse(200, 'Success', ['message' => 'GET successful']);
    }

    protected function postHandler(): ApiResponse
    {
        $data = $this->request->getBodyParams();
        return new ApiResponse(201, 'Created', $data);
    }

    protected function putHandler(): ApiResponse
    {
        $id = $this->request->getRouteParam('id');
        return new ApiResponse(200, 'Updated', ['id' => $id, 'updated' => true]);
    }

    protected function deleteHandler(): ApiResponse
    {
        $id = $this->request->getRouteParam('id');
        return new ApiResponse(204, 'Deleted', ['id' => $id]);
    }
}

class ApiRouterPhase19Test extends TestCase
{
    private ApiRouter $router;
    private SimpleTestEndpoint $endpoint;

    protected function setUp(): void
    {
        $this->router = new ApiRouter();
        $this->endpoint = new SimpleTestEndpoint();
    }

    public function testRouteRegistration(): void
    {
        $this->router->register('GET', '/loans', $this->endpoint);
        $this->router->register('POST', '/loans', $this->endpoint);

        $routes = $this->router->getRoutes();
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
    }

    public function testGetRequest(): void
    {
        $this->router->register('GET', '/loans', $this->endpoint);
        $request = new ApiRequest('GET', '/loans');

        $response = $this->router->route($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->isSuccess());
    }

    public function testPostRequest(): void
    {
        $this->router->register('POST', '/loans', $this->endpoint);
        $request = new ApiRequest('POST', '/loans');
        $request->setBodyParam('amount', 10000);
        $request->setBodyParam('term', 60);

        $response = $this->router->route($request);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testRouteParameterExtraction(): void
    {
        $this->router->register('PUT', '/loans/{id}', $this->endpoint);
        $request = new ApiRequest('PUT', '/loans/123');

        $response = $this->router->route($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNotFoundRoute(): void
    {
        $request = new ApiRequest('GET', '/nonexistent');

        $response = $this->router->route($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($response->isClientError());
    }

    public function testResponseMetadata(): void
    {
        $this->router->register('GET', '/loans', $this->endpoint);
        $request = new ApiRequest('GET', '/loans');

        $response = $this->router->route($request);

        $metadata = $response->getMetadata();
        $this->assertArrayHasKey('processing_time_ms', $metadata);
        $this->assertArrayHasKey('request_id', $metadata);
    }

    public function testGetMetrics(): void
    {
        $this->router->register('GET', '/loans', $this->endpoint);
        
        $request1 = new ApiRequest('GET', '/loans');
        $request2 = new ApiRequest('GET', '/loans');
        
        $this->router->route($request1);
        $this->router->route($request2);

        $metrics = $this->router->getMetrics();

        $this->assertArrayHasKey('total_requests', $metrics);
        $this->assertArrayHasKey('total_processing_time_ms', $metrics);
        $this->assertGreaterThanOrEqual(2, $metrics['total_requests']);
    }

    public function testMultipleParameterExtraction(): void
    {
        $this->router->register('GET', '/users/{userId}/loans/{loanId}', $this->endpoint);
        $request = new ApiRequest('GET', '/users/456/loans/789');

        $response = $this->router->route($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testClearRoutes(): void
    {
        $this->router->register('GET', '/loans', $this->endpoint);
        $this->assertNotEmpty($this->router->getRoutes());

        $this->router->clearRoutes();
        $this->assertEmpty($this->router->getRoutes());
    }
}
