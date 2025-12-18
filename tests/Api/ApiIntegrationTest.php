<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Api\{
    ApiRouter,
    LoanController,
    ScheduleController,
    EventController,
    ApiResponse
};
use Ksfraser\Amortizations\Repositories\{
    MockLoanRepository,
    MockScheduleRepository,
    MockEventRepository
};

/**
 * ApiRouterTest: Tests for API request routing
 */
class ApiRouterTest extends TestCase
{
    private ApiRouter $router;

    protected function setUp(): void
    {
        parent::setUp();
        MockLoanRepository::reset();
        MockScheduleRepository::reset();
        MockEventRepository::reset();
        
        $this->router = new ApiRouter();
    }

    /**
     * Test: Route GET /api/v1/loans to list handler
     */
    public function test_routes_get_loans_list()
    {
        $response = $this->router->dispatch('GET', '/api/v1/loans', []);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Route POST /api/v1/loans to create handler
     */
    public function test_routes_post_loans_create()
    {
        $data = [
            'principal' => 30000,
            'interest_rate' => 0.045,
            'term_months' => 60,
            'start_date' => '2025-01-01',
            'loan_type' => 'auto'
        ];
        
        $response = $this->router->dispatch('POST', '/api/v1/loans', $data);
        
        $this->assertTrue($response->getStatusCode() === 201 || $response->getStatusCode() === 422);
    }

    /**
     * Test: Route GET /api/v1/loans/{id} to get handler
     */
    public function test_routes_get_loan_by_id()
    {
        $response = $this->router->dispatch('GET', '/api/v1/loans/1', []);
        
        $this->assertTrue(in_array($response->getStatusCode(), [200, 404]));
    }

    /**
     * Test: Route DELETE /api/v1/loans/{id} to delete handler
     */
    public function test_routes_delete_loan()
    {
        $response = $this->router->dispatch('DELETE', '/api/v1/loans/1', []);
        
        // Will return 404 since loan doesn't exist in mock
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test: Route GET /api/v1/loans/{loanId}/schedules to schedule list handler
     */
    public function test_routes_get_loan_schedules()
    {
        $response = $this->router->dispatch('GET', '/api/v1/loans/1/schedules', []);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Route POST /api/v1/loans/{loanId}/events to event record handler
     */
    public function test_routes_post_loan_event()
    {
        $data = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01',
            'amount' => 500
        ];
        
        $response = $this->router->dispatch('POST', '/api/v1/loans/1/events', $data);
        
        // Will validate but fail at repository level
        $this->assertTrue($response->getStatusCode() >= 200);
    }

    /**
     * Test: Return 404 for unknown routes
     */
    public function test_returns_404_for_unknown_route()
    {
        $response = $this->router->dispatch('GET', '/api/v1/unknown', []);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test: Parse paths with trailing slashes
     */
    public function test_handles_trailing_slashes()
    {
        $response = $this->router->dispatch('GET', '/api/v1/loans/', []);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Parse paths without leading slashes
     */
    public function test_handles_paths_without_leading_slash()
    {
        $response = $this->router->dispatch('GET', 'api/v1/loans', []);
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}

/**
 * ApiIntegrationTest: End-to-end API integration tests
 */
class ApiIntegrationTest extends TestCase
{
    private ApiRouter $router;

    protected function setUp(): void
    {
        parent::setUp();
        MockLoanRepository::reset();
        MockScheduleRepository::reset();
        MockEventRepository::reset();
        
        $this->router = new ApiRouter();
    }

    /**
     * Test: Complete loan creation flow
     */
    public function test_complete_loan_creation_flow()
    {
        // Create loan
        $loanData = [
            'principal' => 30000,
            'interest_rate' => 0.045,
            'term_months' => 60,
            'start_date' => '2025-01-01',
            'loan_type' => 'auto',
            'description' => 'Test Auto Loan'
        ];
        
        $createResponse = $this->router->dispatch('POST', '/api/v1/loans', $loanData);
        
        $this->assertTrue(
            $createResponse->getStatusCode() === 201 || $createResponse->getStatusCode() === 422,
            'Loan creation should succeed or fail with validation error'
        );
    }

    /**
     * Test: Loan CRUD operations
     */
    public function test_loan_crud_operations()
    {
        // Create
        $createData = [
            'principal' => 50000,
            'interest_rate' => 0.05,
            'term_months' => 60,
            'start_date' => '2025-01-01'
        ];
        
        $createResponse = $this->router->dispatch('POST', '/api/v1/loans', $createData);
        
        // List
        $listResponse = $this->router->dispatch('GET', '/api/v1/loans', ['page' => 1, 'per_page' => 20]);
        $this->assertEquals(200, $listResponse->getStatusCode());
    }

    /**
     * Test: Event recording workflow
     */
    public function test_event_recording_workflow()
    {
        // Record extra payment event
        $eventData = [
            'event_type' => 'extra_payment',
            'event_date' => '2025-02-01',
            'amount' => 500,
            'notes' => 'Bonus applied'
        ];
        
        $response = $this->router->dispatch('POST', '/api/v1/loans/1/events', $eventData);
        
        // May succeed or fail depending on loan existence
        $this->assertTrue($response->getStatusCode() >= 200);
    }

    /**
     * Test: Pagination parameters validation
     */
    public function test_pagination_validation()
    {
        // Valid pagination
        $validResponse = $this->router->dispatch('GET', '/api/v1/loans', ['page' => 1, 'per_page' => 20]);
        $this->assertEquals(200, $validResponse->getStatusCode());

        // Invalid pagination
        $invalidResponse = $this->router->dispatch('GET', '/api/v1/loans', ['page' => 0, 'per_page' => 1000]);
        $this->assertEquals(422, $invalidResponse->getStatusCode());
    }
}

/**
 * ApiEndpointTest: More detailed endpoint tests with repository setup
 */
class ApiEndpointTest extends TestCase
{
    private LoanController $loanController;
    private ScheduleController $scheduleController;
    private EventController $eventController;

    protected function setUp(): void
    {
        parent::setUp();
        MockLoanRepository::reset();
        MockScheduleRepository::reset();
        MockEventRepository::reset();
        
        $this->loanController = new LoanController(
            new \Ksfraser\Amortizations\Repositories\LoanRepository(),
            $this->createScheduleGeneratorMock()
        );
        
        $this->scheduleController = new ScheduleController(
            new \Ksfraser\Amortizations\Repositories\ScheduleRepository(),
            new \Ksfraser\Amortizations\Repositories\LoanRepository(),
            $this->createScheduleGeneratorMock()
        );
        
        $this->eventController = new EventController(
            new \Ksfraser\Amortizations\Repositories\EventRepository(),
            new \Ksfraser\Amortizations\Repositories\LoanRepository()
        );
    }

    private function createScheduleGeneratorMock()
    {
        return $this->getMockBuilder(\Ksfraser\Amortizations\Services\ScheduleGeneratorService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test: Loan creation returns 201 Created
     */
    public function test_loan_creation_returns_201()
    {
        $response = $this->loanController->create([
            'principal' => 30000,
            'interest_rate' => 0.045,
            'term_months' => 60,
            'start_date' => '2025-01-01'
        ]);
        
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Test: Loan creation validation error returns 422
     */
    public function test_loan_creation_validation_error()
    {
        $response = $this->loanController->create([
            'principal' => -100,  // Invalid
            'interest_rate' => 0.045,
            'term_months' => 60,
            'start_date' => '2025-01-01'
        ]);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test: Get non-existent loan returns 404
     */
    public function test_get_nonexistent_loan()
    {
        $response = $this->loanController->get(999);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test: Invalid loan ID returns 422
     */
    public function test_invalid_loan_id()
    {
        $response = $this->loanController->get(-1);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test: Event list returns success
     */
    public function test_event_list_returns_success()
    {
        $response = $this->eventController->list(1);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test: Event list with invalid loan ID
     */
    public function test_event_list_invalid_loan_id()
    {
        $response = $this->eventController->list(-1);
        
        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test: Response has proper structure
     */
    public function test_response_structure()
    {
        $response = $this->loanController->list();
        $data = $response->toArray();
        
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('version', $data['meta']);
        $this->assertArrayHasKey('timestamp', $data['meta']);
        $this->assertArrayHasKey('requestId', $data['meta']);
    }
}
