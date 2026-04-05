<?php
namespace Tests\Unit\Security\OAuth2\Attributes;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\Attributes\OAuth2Protected;
use ReflectionClass;
use ReflectionMethod;

class OAuth2ProtectedTest extends TestCase
{
    /**
     * Test OAuth2Protected single scope
     */
    public function testOAuth2ProtectedSingleScope(): void
    {
        $attr = new OAuth2Protected(scope: 'amortization:read');
        $this->assertEquals('amortization:read', $attr->scope);
        $this->assertTrue($attr->requiresAuthentication());
        $this->assertFalse($attr->allowPublic);
    }

    /**
     * Test OAuth2Protected multiple scopes
     */
    public function testOAuth2ProtectedMultipleScopes(): void
    {
        $attr = new OAuth2Protected(scopes: ['read', 'write']);
        $this->assertEquals(['read', 'write'], $attr->scopes);
        $this->assertTrue($attr->requiresAuthentication());
    }

    /**
     * Test OAuth2Protected alternative scopes
     */
    public function testOAuth2ProtectedAlternativeScopes(): void
    {
        $attr = new OAuth2Protected(scopesAny: ['admin:*', 'superuser:*']);
        $this->assertEquals(['admin:*', 'superuser:*'], $attr->scopesAny);
    }

    /**
     * Test OAuth2Protected allows public access
     */
    public function testOAuth2ProtectedAllowPublic(): void
    {
        $attr = new OAuth2Protected(scope: 'read', allowPublic: true);
        $this->assertTrue($attr->allowPublic);
        $this->assertTrue($attr->isPublic());
    }

    /**
     * Test OAuth2Protected rate limiting
     */
    public function testOAuth2ProtectedRateLimit(): void
    {
        $attr = new OAuth2Protected(scope: 'read', rateLimit: 1000);
        $this->assertEquals(1000, $attr->getRateLimit());
    }

    /**
     * Test OAuth2Protected custom error message
     */
    public function testOAuth2ProtectedCustomErrorMessage(): void
    {
        $message = 'Custom error message';
        $attr = new OAuth2Protected(scope: 'read', errorMessage: $message);
        $this->assertEquals($message, $attr->getErrorMessage());
    }

    /**
     * Test getRequiredScopes combines single and multiple
     */
    public function testGetRequiredScopesCombined(): void
    {
        $attr = new OAuth2Protected(
            scope: 'admin',
            scopes: ['read', 'write']
        );
        $required = $attr->getRequiredScopes();
        $this->assertContains('admin', $required);
        $this->assertContains('read', $required);
        $this->assertContains('write', $required);
    }

    /**
     * Test getAlternativeScopes
     */
    public function testGetAlternativeScopes(): void
    {
        $attr = new OAuth2Protected(scopesAny: ['admin:*', 'user:admin']);
        $alternatives = $attr->getAlternativeScopes();
        $this->assertContains('admin:*', $alternatives);
        $this->assertContains('user:admin', $alternatives);
    }

    /**
     * Test OAuth2Protected with no scopes doesn't require auth
     */
    public function testOAuth2ProtectedNoScopesNoAuth(): void
    {
        $attr = new OAuth2Protected();
        $this->assertFalse($attr->requiresAuthentication());
    }

    /**
     * Test attribute can be used on methods
     */
    public function testAttributeOnMethod(): void
    {
        // Create a test class with the attribute
        $class = new class {
            #[OAuth2Protected(scope: 'read')]
            public function protectedMethod()
            {
            }

            public function publicMethod()
            {
            }
        };

        $reflectionClass = new ReflectionClass($class);
        $protected = $reflectionClass->getMethod('protectedMethod');
        $public = $reflectionClass->getMethod('publicMethod');

        $protectedAttrs = $protected->getAttributes(OAuth2Protected::class);
        $publicAttrs = $public->getAttributes(OAuth2Protected::class);

        $this->assertCount(1, $protectedAttrs);
        $this->assertCount(0, $publicAttrs);

        $attr = $protectedAttrs[0]->newInstance();
        $this->assertEquals('read', $attr->scope);
    }

    /**
     * Test attribute on class level
     */
    public function testAttributeOnClass(): void
    {
        #[OAuth2Protected(scope: 'amortization:*')]
        $class = new class {
            public function method1()
            {
            }

            public function method2()
            {
            }
        };

        $reflectionClass = new ReflectionClass($class);
        $attrs = $reflectionClass->getAttributes(OAuth2Protected::class);

        $this->assertCount(1, $attrs);
        $attr = $attrs[0]->newInstance();
        $this->assertEquals('amortization:*', $attr->scope);
    }

    /**
     * Test OAuth2Protected with all parameters
     */
    public function testOAuth2ProtectedAllParameters(): void
    {
        $attr = new OAuth2Protected(
            scope: 'primary:read',
            scopes: ['secondary:read', 'secondary:write'],
            scopesAny: ['admin:*', 'system:*'],
            allowPublic: false,
            rateLimit: 5000,
            errorMessage: 'Access denied'
        );

        $this->assertEquals('primary:read', $attr->scope);
        $this->assertEquals(['secondary:read', 'secondary:write'], $attr->scopes);
        $this->assertEquals(['admin:*', 'system:*'], $attr->scopesAny);
        $this->assertFalse($attr->allowPublic);
        $this->assertEquals(5000, $attr->getRateLimit());
        $this->assertEquals('Access denied', $attr->getErrorMessage());
        $this->assertTrue($attr->requiresAuthentication());
    }

    /**
     * Test OAuth2Protected defaults
     */
    public function testOAuth2ProtectedDefaults(): void
    {
        $attr = new OAuth2Protected();
        $this->assertNull($attr->scope);
        $this->assertEquals([], $attr->scopes);
        $this->assertEquals([], $attr->scopesAny);
        $this->assertFalse($attr->allowPublic);
        $this->assertNull($attr->rateLimit);
        $this->assertNull($attr->errorMessage);
    }
}
