<?php
namespace Tests\Unit\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\ScopeValidator;
use Ksfraser\Security\Exceptions\AuthorizationException;

class ScopeValidatorTest extends TestCase
{
    /**
     * @var ScopeValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new ScopeValidator();
    }

    // ========== hasScope Tests ==========

    /**
     * Test exact scope match
     */
    public function testHasScopeExactMatch(): void
    {
        $granted = ['read', 'write', 'delete'];
        $this->assertTrue($this->validator->hasScope($granted, 'read'));
        $this->assertTrue($this->validator->hasScope($granted, 'write'));
    }

    /**
     * Test wildcard scope matching
     */
    public function testHasScopeWildcard(): void
    {
        $granted = ['amortization:*', 'portfolio:read'];
        $this->assertTrue($this->validator->hasScope($granted, 'amortization:read'));
        $this->assertTrue($this->validator->hasScope($granted, 'amortization:portfolio:write'));
        $this->assertFalse($this->validator->hasScope($granted, 'market:read'));
    }

    /**
     * Test admin wildcard
     */
    public function testHasScopeAdminWildcard(): void
    {
        $granted = ['admin:*'];
        $this->assertTrue($this->validator->hasScope($granted, 'amortization:read'));
        $this->assertTrue($this->validator->hasScope($granted, 'portfolio:write'));
        $this->assertTrue($this->validator->hasScope($granted, 'market:delete'));
    }

    /**
     * Test scope not found
     */
    public function testHasScopeNotFound(): void
    {
        $granted = ['read:data'];
        $this->assertFalse($this->validator->hasScope($granted, 'write'));
    }

    /**
     * Test empty scopes
     */
    public function testHasScopeEmpty(): void
    {
        $this->assertFalse($this->validator->hasScope([], 'read'));
        $this->assertFalse($this->validator->hasScope(['read'], ''));
    }

    // ========== requireScope Tests ==========

    /**
     * Test requireScope throws on missing
     */
    public function testRequireScopeThrowsOnMissing(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->validator->requireScope(['read'], 'write');
    }

    /**
     * Test requireScope passes on match
     */
    public function testRequireScopePassesOnMatch(): void
    {
        $this->validator->requireScope(['read', 'write'], 'write');
        $this->assertTrue(true); // No exception thrown
    }

    // ========== hasScopeAny Tests ==========

    /**
     * Test hasScopeAny with matching first option
     */
    public function testHasScopeAnyFirstOption(): void
    {
        $granted = ['amortization:read'];
        $alternatives = ['amortization:read', 'amortization:admin'];
        $this->assertTrue($this->validator->hasScopeAny($granted, $alternatives));
    }

    /**
     * Test hasScopeAny with matching second option
     */
    public function testHasScopeAnySecondOption(): void
    {
        $granted = ['admin:*'];
        $alternatives = ['portfolio:write', 'admin:delete'];
        $this->assertTrue($this->validator->hasScopeAny($granted, $alternatives));
    }

    /**
     * Test hasScopeAny with no match
     */
    public function testHasScopeAnyNoMatch(): void
    {
        $granted = ['read:data'];
        $alternatives = ['write', 'delete'];
        $this->assertFalse($this->validator->hasScopeAny($granted, $alternatives));
    }

    // ========== hasScopeAll Tests ==========

    /**
     * Test hasScopeAll with all scopes granted
     */
    public function testHasScopeAllGranted(): void
    {
        $granted = ['amortization:read', 'portfolio:write', 'market:delete'];
        $required = ['amortization:read', 'portfolio:write'];
        $this->assertTrue($this->validator->hasScopeAll($granted, $required));
    }

    /**
     * Test hasScopeAll with missing scope
     */
    public function testHasScopeAllMissing(): void
    {
        $granted = ['amortization:read'];
        $required = ['amortization:read', 'portfolio:write'];
        $this->assertFalse($this->validator->hasScopeAll($granted, $required));
    }

    // ========== getScopesForPermission Tests ==========

    /**
     * Test getting read scopes
     */
    public function testGetScopesForPermissionRead(): void
    {
        $granted = ['amortization:read', 'portfolio:read', 'portfolio:write', 'market:delete'];
        $scopes = $this->validator->getScopesForPermission($granted, 'read');
        $this->assertContains('amortization:read', $scopes);
        $this->assertContains('portfolio:read', $scopes);
        $this->assertNotContains('portfolio:write', $scopes);
    }

    /**
     * Test getting write scopes with prefix
     */
    public function testGetScopesForPermissionWriteWithPrefix(): void
    {
        $granted = ['amortization:read', 'amortization:portfolio:write', 'portfolio:write', 'admin:*'];
        $scopes = $this->validator->getScopesForPermission($granted, 'write', 'amortization');
        $this->assertContains('amortization:portfolio:write', $scopes);
        $this->assertNotContains('portfolio:write', $scopes);
    }

    // ========== getScopesForResource Tests ==========

    /**
     * Test getting scopes for resource
     */
    public function testGetScopesForResource(): void
    {
        $granted = ['amortization:read', 'amortization:portfolio:write', 'portfolio:read'];
        $scopes = $this->validator->getScopesForResource($granted, 'amortization:portfolio');
        $this->assertContains('amortization:portfolio:write', $scopes);
    }

    // ========== isValidScopeFormat Tests ==========

    /**
     * Test valid scope formats
     */
    public function testIsValidScopeFormatValid(): void
    {
        $this->assertTrue($this->validator->isValidScopeFormat('read'));
        $this->assertTrue($this->validator->isValidScopeFormat('amortization:read'));
        $this->assertTrue($this->validator->isValidScopeFormat('amortization:portfolio:write'));
        $this->assertTrue($this->validator->isValidScopeFormat('admin:*'));
        $this->assertTrue($this->validator->isValidScopeFormat('amortization:*'));
    }

    /**
     * Test invalid scope formats
     */
    public function testIsValidScopeFormatInvalid(): void
    {
        $this->assertFalse($this->validator->isValidScopeFormat(''));
        $this->assertFalse($this->validator->isValidScopeFormat('_invalid-scope!'));
        $this->assertFalse($this->validator->isValidScopeFormat('*')); // Wildcard alone
        $this->assertFalse($this->validator->isValidScopeFormat('scope with spaces'));
    }

    // ========== normalizeScopeList Tests ==========

    /**
     * Test normalize scope list
     */
    public function testNormalizeScopeList(): void
    {
        $scopes = ['read', 'write', 'read', 'delete', 'write'];
        $normalized = $this->validator->normalizeScopeList($scopes);
        $this->assertEquals(['delete', 'read', 'write'], $normalized);
    }

    // ========== parseScopeString Tests ==========

    /**
     * Test parse space-separated scope string
     */
    public function testParseScopeStringSpaceSeparated(): void
    {
        $result = $this->validator->parseScopeString('read write delete');
        $this->assertEquals(['read', 'write', 'delete'], $result);
    }

    /**
     * Test parse comma-separated scope string
     */
    public function testParseScopeStringCommaSeparated(): void
    {
        $result = $this->validator->parseScopeString('read,write,delete');
        $this->assertEquals(['read', 'write', 'delete'], $result);
    }

    /**
     * Test parse empty scope string
     */
    public function testParseScopeStringEmpty(): void
    {
        $result = $this->validator->parseScopeString('');
        $this->assertEquals([], $result);
    }
}
