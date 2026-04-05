<?php

namespace Ksfraser\Amortizations\Tests\Authentication;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Authentication\ScopeManager;
use InvalidArgumentException;

/**
 * ScopeManagerTest - Comprehensive scope management tests
 *
 * Tests scope registration, validation, hierarchy, and expansion.
 */
class ScopeManagerTest extends TestCase
{
    protected $scopeManager;

    protected function setUp(): void
    {
        $this->scopeManager = new ScopeManager();
    }

    // ===== Scope Registration Tests =====

    public function testDefaultScopesAreRegistered(): void
    {
        $scopes = $this->scopeManager->getAllScopes();
        $this->assertNotEmpty($scopes);
        $this->assertArrayHasKey('loan:read', $scopes);
        $this->assertArrayHasKey('loan:write', $scopes);
        $this->assertArrayHasKey('admin', $scopes);
    }

    public function testValidScopeFormat(): void
    {
        $this->scopeManager->registerScope('custom:test');
        $scopes = $this->scopeManager->getAllScopes();
        $this->assertArrayHasKey('custom:test', $scopes);
    }

    public function testInvalidScopeFormatThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->scopeManager->registerScope('invalid@scope');
    }

    public function testRegisterCustomScope(): void
    {
        $this->scopeManager->registerScope('report:generate', [
            'category' => 'reports',
            'description' => 'Generate reports',
            'tier' => 'advanced',
        ]);

        $meta = $this->scopeManager->getScopeMetadata('report:generate');
        $this->assertEquals('reports', $meta['category']);
        $this->assertEquals('Generate reports', $meta['description']);
    }

    // ===== Scope Validation Tests =====

    public function testValidateScopesSuccess(): void
    {
        $this->assertTrue(
            $this->scopeManager->validateScopes(['loan:read', 'schedule:read'])
        );
    }

    public function testValidateScopesWithUnregisteredScope(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unregistered scopes');
        $this->scopeManager->validateScopes(['loan:read', 'nonexistent:scope']);
    }

    public function testValidateScopesEmpty(): void
    {
        $this->assertTrue($this->scopeManager->validateScopes([]));
    }

    // ===== Scope Hierarchy Tests =====

    public function testWriteScopeImpliesReadScope(): void
    {
        $expanded = $this->scopeManager->expandScopes(['loan:write']);
        $this->assertContains('loan:write', $expanded);
        $this->assertContains('loan:read', $expanded);
    }

    public function testDeleteScopeImpliesWriteAndRead(): void
    {
        $expanded = $this->scopeManager->expandScopes(['loan:delete']);
        $this->assertContains('loan:delete', $expanded);
        $this->assertContains('loan:write', $expanded);
        $this->assertContains('loan:read', $expanded);
    }

    public function testAdminScopeImpliesAllScopes(): void
    {
        $expanded = $this->scopeManager->expandScopes(['admin']);
        $this->assertContains('loan:read', $expanded);
        $this->assertContains('loan:write', $expanded);
        $this->assertContains('schedule:read', $expanded);
        $this->assertContains('event:write', $expanded);
        $this->assertGreaterThan(10, count($expanded));
    }

    public function testExpandScopesDeduplication(): void
    {
        $expanded = $this->scopeManager->expandScopes(['loan:write', 'loan:read']);
        $counts = array_count_values($expanded);
        $this->assertEquals(1, $counts['loan:read']);
        $this->assertEquals(1, $counts['loan:write']);
    }

    // ===== Required Scope Tests =====

    public function testHasRequiredScopeWithExactMatch(): void
    {
        $this->assertTrue(
            $this->scopeManager->hasRequiredScope(['loan:read'], 'loan:read')
        );
    }

    public function testHasRequiredScopeWithHierarchy(): void
    {
        // loan:write implies loan:read
        $this->assertTrue(
            $this->scopeManager->hasRequiredScope(['loan:write'], 'loan:read')
        );
    }

    public function testHasRequiredScopeFails(): void
    {
        $this->assertFalse(
            $this->scopeManager->hasRequiredScope(['schedule:read'], 'loan:read')
        );
    }

    // ===== Category Tests =====

    public function testGetScopesByCategory(): void
    {
        $loanScopes = $this->scopeManager->getScopesByCategory('loan');
        $this->assertNotEmpty($loanScopes);
        $this->assertArrayHasKey('loan:read', $loanScopes);
        $this->assertArrayHasKey('loan:write', $loanScopes);
        $this->assertArrayHasKey('loan:delete', $loanScopes);
    }

    public function testGetScopesByCategoryEmptyCategory(): void
    {
        $scopes = $this->scopeManager->getScopesByCategory('nonexistent');
        $this->assertEmpty($scopes);
    }

    // ===== Default Scopes Tests =====

    public function testGetDefaultScopes(): void
    {
        $defaults = $this->scopeManager->getDefaultScopes();
        $this->assertNotEmpty($defaults);
        $this->assertContains('loan:read', $defaults);
        $this->assertContains('schedule:read', $defaults);
    }

    public function testSetDefaultScopes(): void
    {
        $newDefaults = ['loan:read', 'event:read'];
        $this->scopeManager->setDefaultScopes($newDefaults);
        $defaults = $this->scopeManager->getDefaultScopes();
        $this->assertEquals($newDefaults, $defaults);
    }

    public function testSetDefaultScopesWithInvalidScopes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->scopeManager->setDefaultScopes(['invalid:scope']);
    }

    // ===== Metadata Tests =====

    public function testGetScopeMetadata(): void
    {
        $meta = $this->scopeManager->getScopeMetadata('loan:read');
        $this->assertEquals('loan', $meta['category']);
        $this->assertEquals('basic', $meta['tier']);
        $this->assertNotEmpty($meta['description']);
        $this->assertNotEmpty($meta['human_readable']);
    }

    public function testGetScopeMetadataForUnknownScope(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->scopeManager->getScopeMetadata('unknown:scope');
    }

    // ===== Multiple Scope Expansion Tests =====

    public function testExpandMultipleScopes(): void
    {
        $scopes = ['loan:write', 'schedule:export', 'event:write'];
        $expanded = $this->scopeManager->expandScopes($scopes);

        // Should contain all implied scopes
        $this->assertContains('loan:write', $expanded);
        $this->assertContains('loan:read', $expanded);
        $this->assertContains('schedule:export', $expanded);
        $this->assertContains('schedule:read', $expanded);
        $this->assertContains('event:write', $expanded);
        $this->assertContains('event:read', $expanded);
    }

    // ===== Fluent Interface Tests =====

    public function testRegisterScopeFluentInterface(): void
    {
        $result = $this->scopeManager->registerScope('test:scope');
        $this->assertSame($this->scopeManager, $result);
    }

    public function testSetDefaultScopesFluentInterface(): void
    {
        $result = $this->scopeManager->setDefaultScopes(['loan:read']);
        $this->assertSame($this->scopeManager, $result);
    }
}
