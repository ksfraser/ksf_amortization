<?php
namespace Tests\Unit\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\ScopeManager;
use Ksfraser\Security\Exceptions\AuthorizationException;

class ScopeManagerTest extends TestCase
{
    /**
     * @var ScopeManager
     */
    private $scopeManager;

    protected function setUp(): void
    {
        $this->scopeManager = new ScopeManager();
    }

    /**
     * Test hasScope returns true for directly granted scope
     */
    public function testHasScopeWithDirectScope(): void
    {
        $grantedScopes = ['read', 'write'];
        $this->assertTrue($this->scopeManager->hasScope($grantedScopes, 'read'));
        $this->assertTrue($this->scopeManager->hasScope($grantedScopes, 'write'));
    }

    /**
     * Test hasScope returns false for missing scope
     */
    public function testHasScopeWithMissingScope(): void
    {
        $grantedScopes = ['read'];
        $this->assertFalse($this->scopeManager->hasScope($grantedScopes, 'write'));
        $this->assertFalse($this->scopeManager->hasScope($grantedScopes, 'admin'));
    }

    /**
     * Test hasScope with scope hierarchy (admin implies other scopes)
     */
    public function testHasScopeWithHierarchy(): void
    {
        $grantedScopes = ['admin'];
        
        // Admin scope should imply other scopes
        $this->assertTrue($this->scopeManager->hasScope($grantedScopes, 'admin'));
        $this->assertTrue($this->scopeManager->hasScope($grantedScopes, 'read'));
        $this->assertTrue($this->scopeManager->hasScope($grantedScopes, 'write'));
        $this->assertTrue($this->scopeManager->hasScope($grantedScopes, 'delete'));
        $this->assertTrue($this->scopeManager->hasScope($grantedScopes, 'analytics'));
    }

    /**
     * Test hasScopes with requireAll=true
     */
    public function testHasMultipleScopesRequireAll(): void
    {
        $grantedScopes = ['read', 'write', 'analytics'];

        // All required scopes present
        $this->assertTrue($this->scopeManager->hasScopes(
            $grantedScopes,
            ['read', 'write'],
            true
        ));

        // Missing one scope
        $this->assertFalse($this->scopeManager->hasScopes(
            $grantedScopes,
            ['read', 'write', 'delete'],
            true
        ));
    }

    /**
     * Test hasScopes with requireAll=false
     */
    public function testHasMultipleScopesRequireAny(): void
    {
        $grantedScopes = ['read'];

        // At least one scope present
        $this->assertTrue($this->scopeManager->hasScopes(
            $grantedScopes,
            ['read', 'write'],
            false
        ));

        // No matching scopes
        $this->assertFalse($this->scopeManager->hasScopes(
            $grantedScopes,
            ['write', 'delete'],
            false
        ));
    }

    /**
     * Test requireScope throws for missing scope
     */
    public function testRequireScopeThrowsForMissingScope(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Insufficient permissions');

        $grantedScopes = ['read'];
        $this->scopeManager->requireScope($grantedScopes, 'write');
    }

    /**
     * Test requireScope succeeds for granted scope
     */
    public function testRequireScopeSucceedsForGrantedScope(): void
    {
        $grantedScopes = ['read', 'write'];
        
        // Should not throw
        $this->scopeManager->requireScope($grantedScopes, 'read');
        $this->scopeManager->requireScope($grantedScopes, 'write');
        
        $this->assertTrue(true); // Placeholder assertion
    }

    /**
     * Test requireScopes throws for missing scopes
     */
    public function testRequireMultipleScopesThrowsForMissing(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Missing required scopes');

        $grantedScopes = ['read'];
        $this->scopeManager->requireScopes($grantedScopes, ['read', 'write', 'delete']);
    }

    /**
     * Test getAllScopes returns defined scopes
     */
    public function testGetAllScopes(): void
    {
        $allScopes = $this->scopeManager->getAllScopes();

        $this->assertArrayHasKey('read', $allScopes);
        $this->assertArrayHasKey('write', $allScopes);
        $this->assertArrayHasKey('admin', $allScopes);
        $this->assertArrayHasKey('analytics', $allScopes);
    }

    /**
     * Test getScopeDescription
     */
    public function testGetScopeDescription(): void
    {
        $description = $this->scopeManager->getScopeDescription('read');
        $this->assertNotNull($description);
        $this->assertStringContainsString('read', strtolower($description));

        // Non-existent scope
        $this->assertNull($this->scopeManager->getScopeDescription('nonexistent'));
    }

    /**
     * Test validateScopes method
     */
    public function testValidateScopes(): void
    {
        $invalid = $this->scopeManager->validateScopes(['read', 'write', 'nonexistent']);
        $this->assertCount(1, $invalid);
        $this->assertContains('nonexistent', $invalid);

        // All valid scopes
        $invalid = $this->scopeManager->validateScopes(['read', 'write']);
        $this->assertCount(0, $invalid);
    }

    /**
     * Test getEndpointScopes
     */
    public function testGetEndpointScopes(): void
    {
        $scopes = $this->scopeManager->getEndpointScopes('GET /api/loans');
        $this->assertContains('read', $scopes);

        $scopes = $this->scopeManager->getEndpointScopes('POST /api/loans');
        $this->assertContains('write', $scopes);

        $scopes = $this->scopeManager->getEndpointScopes('DELETE /api/loans/{id}');
        $this->assertContains('delete', $scopes);
    }

    /**
     * Test registerEndpoint
     */
    public function testRegisterEndpoint(): void
    {
        $this->scopeManager->registerEndpoint('PATCH /api/custom', ['custom']);
        $scopes = $this->scopeManager->getEndpointScopes('PATCH /api/custom');
        $this->assertContains('custom', $scopes);
    }

    /**
     * Test custom scopes in constructor
     */
    public function testCustomScopesInConstructor(): void
    {
        $customScopes = ['custom1' => 'Custom scope 1', 'custom2' => 'Custom scope 2'];
        $scopeManager = new ScopeManager($customScopes);

        $allScopes = $scopeManager->getAllScopes();
        $this->assertArrayHasKey('custom1', $allScopes);
        $this->assertArrayHasKey('custom2', $allScopes);

        // Built-in scopes should still exist
        $this->assertArrayHasKey('read', $allScopes);
    }

    /**
     * Test empty scopes array with hasScopes requireAll=false
     */
    public function testEmptyRequiredScopesReturnsTrue(): void
    {
        $grantedScopes = ['read'];
        $this->assertTrue($this->scopeManager->hasScopes($grantedScopes, [], false));
    }
}
