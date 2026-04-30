<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Phase 1: Base Test Case
 * Provides common test setup and utility methods
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Disable external API calls during testing
        $this->disableExternalApiCalls();

        // Seed test data
        $this->seedTestData();
    }

    /**
     * Disable external API calls
     */
    private function disableExternalApiCalls(): void
    {
        \Http::preventStrayRequests();
    }

    /**
     * Seed basic test data
     */
    private function seedTestData(): void
    {
        // Create test users
        $this->createTestUser('admin@test.com', 'admin');
        $this->createTestUser('officer@test.com', 'loan_officer');
        $this->createTestUser('collector@test.com', 'collector');
        $this->createTestUser('borrower@test.com', 'borrower');
    }

    /**
     * Create test user
     */
    protected function createTestUser(string $email, string $role): mixed
    {
        return \App\Models\User::factory()
            ->create([
                'email' => $email,
                'role' => $role
            ]);
    }

    /**
     * Create test loan
     */
    protected function createTestLoan(array $attributes = []): mixed
    {
        return \App\Models\Loan::factory()
            ->create($attributes);
    }

    /**
     * Create test borrower
     */
    protected function createTestBorrower(array $attributes = []): mixed
    {
        return \App\Models\Borrower::factory()
            ->create($attributes);
    }

    /**
     * Authenticate as user
     */
    protected function authenticateAs($user): self
    {
        $this->actingAs($user);
        return $this;
    }

    /**
     * Assert API success response
     */
    protected function assertApiSuccess($response, ?array $expectedData = null): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'data',
            'meta'
        ]);
        $response->assertJsonPath('status', 'success');

        if ($expectedData) {
            $response->assertJsonPath('data', $expectedData);
        }
    }

    /**
     * Assert API error response
     */
    protected function assertApiError($response, int $statusCode = 400): void
    {
        $response->assertStatus($statusCode);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'errors'
        ]);
        $response->assertJsonPath('status', 'error');
    }

    /**
     * Assert validation error response
     */
    protected function assertValidationError($response, array $expectedFields): void
    {
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'errors'
        ]);
        $response->assertJsonPath('status', 'validation_error');

        foreach ($expectedFields as $field) {
            $response->assertJsonPath("errors.{$field}", true);
        }
    }

    /**
     * Get API token for testing
     */
    protected function getApiToken($user = null): string
    {
        $user = $user ?? $this->createTestUser('test@test.com', 'borrower');
        return $user->createToken('test')->plainTextToken;
    }
}
