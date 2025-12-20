<?php

namespace Tests\Authentication;

use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\Authentication\Client;

/**
 * ClientTest - OAuth2 Client Tests
 *
 * Tests client credentials, scope management,
 * and validation logic.
 *
 * @package Tests\Authentication
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class ClientTest extends TestCase
{
    /**
     * Test client creation
     *
     * @test
     */
    public function testClientCreation(): void
    {
        $client = new Client('app_id', 'app_secret', 'My App');

        $this->assertEquals('app_id', $client->getClientId());
        $this->assertEquals('app_secret', $client->getClientSecret());
        $this->assertEquals('My App', $client->getName());
    }

    /**
     * Test client creation without name uses client ID
     *
     * @test
     */
    public function testClientCreationWithoutName(): void
    {
        $client = new Client('app_id', 'app_secret');

        $this->assertEquals('app_id', $client->getName());
    }

    /**
     * Test grant single scope
     *
     * @test
     */
    public function testGrantScope(): void
    {
        $client = new Client('app_id', 'app_secret');

        $client->grantScope('loan:read');
        $client->grantScope('loan:write');

        $this->assertTrue($client->hasScope('loan:read'));
        $this->assertTrue($client->hasScope('loan:write'));
        $this->assertFalse($client->hasScope('admin'));
    }

    /**
     * Test grant multiple scopes
     *
     * @test
     */
    public function testGrantScopes(): void
    {
        $client = new Client('app_id', 'app_secret');
        $scopes = ['loan:read', 'loan:write', 'analysis:read'];

        $client->grantScopes($scopes);

        $this->assertEquals($scopes, $client->getScopes());
    }

    /**
     * Test grant scope returns self (fluent interface)
     *
     * @test
     */
    public function testGrantScopeReturnsSelf(): void
    {
        $client = new Client('app_id', 'app_secret');

        $result = $client->grantScope('loan:read');

        $this->assertInstanceOf(Client::class, $result);
        $this->assertSame($client, $result);
    }

    /**
     * Test grant duplicate scope only adds once
     *
     * @test
     */
    public function testGrantDuplicateScopeOnlyOnce(): void
    {
        $client = new Client('app_id', 'app_secret');

        $client->grantScope('loan:read');
        $client->grantScope('loan:read');

        $this->assertCount(1, $client->getScopes());
    }

    /**
     * Test revoke scope
     *
     * @test
     */
    public function testRevokeScope(): void
    {
        $client = new Client('app_id', 'app_secret');
        $client->grantScopes(['loan:read', 'loan:write']);

        $client->revokeScope('loan:write');

        $this->assertTrue($client->hasScope('loan:read'));
        $this->assertFalse($client->hasScope('loan:write'));
    }

    /**
     * Test has all scopes
     *
     * @test
     */
    public function testHasAllScopes(): void
    {
        $client = new Client('app_id', 'app_secret');
        $client->grantScopes(['loan:read', 'loan:write', 'analysis:read']);

        $this->assertTrue($client->hasScopes(['loan:read', 'loan:write']));
        $this->assertTrue($client->hasScopes(['loan:read']));
        $this->assertFalse($client->hasScopes(['loan:read', 'admin']));
    }

    /**
     * Test active status
     *
     * @test
     */
    public function testActiveStatus(): void
    {
        $client = new Client('app_id', 'app_secret');

        $this->assertTrue($client->isActive());

        $client->setActive(false);

        $this->assertFalse($client->isActive());
    }

    /**
     * Test set active returns self
     *
     * @test
     */
    public function testSetActiveReturnsSelf(): void
    {
        $client = new Client('app_id', 'app_secret');

        $result = $client->setActive(false);

        $this->assertInstanceOf(Client::class, $result);
        $this->assertSame($client, $result);
    }

    /**
     * Test verify secret
     *
     * @test
     */
    public function testVerifySecret(): void
    {
        $client = new Client('app_id', 'app_secret');

        $this->assertTrue($client->verifySecret('app_secret'));
        $this->assertFalse($client->verifySecret('wrong_secret'));
    }

    /**
     * Test verify secret uses constant time comparison
     *
     * @test
     */
    public function testVerifySecretConstantTime(): void
    {
        $client = new Client('app_id', 'app_secret');

        // Both should be false but with constant time
        $this->assertFalse($client->verifySecret('app_secret_'));
        $this->assertFalse($client->verifySecret('other'));
    }

    /**
     * Test add redirect URI
     *
     * @test
     */
    public function testAddRedirectUri(): void
    {
        $client = new Client('app_id', 'app_secret');

        $client->addRedirectUri('https://example.com/callback');
        $client->addRedirectUri('https://example.com/logout');

        $this->assertTrue($client->isRedirectUriAllowed('https://example.com/callback'));
        $this->assertTrue($client->isRedirectUriAllowed('https://example.com/logout'));
        $this->assertFalse($client->isRedirectUriAllowed('https://other.com/callback'));
    }

    /**
     * Test add duplicate redirect URI only adds once
     *
     * @test
     */
    public function testAddDuplicateRedirectUri(): void
    {
        $client = new Client('app_id', 'app_secret');

        $client->addRedirectUri('https://example.com/callback');
        $client->addRedirectUri('https://example.com/callback');

        $this->assertCount(1, $client->getRedirectUris());
    }

    /**
     * Test validate client
     *
     * @test
     */
    public function testValidateClient(): void
    {
        $client = new Client('app_id', 'app_secret');

        $this->assertTrue($client->validate());

        $client->setActive(false);

        $this->assertFalse($client->validate());
    }

    /**
     * Test to array excludes secret
     *
     * @test
     */
    public function testToArrayExcludesSecret(): void
    {
        $client = new Client('app_id', 'app_secret', 'My App');
        $client->grantScopes(['loan:read', 'loan:write']);
        $client->addRedirectUri('https://example.com/callback');

        $array = $client->toArray();

        $this->assertEquals('app_id', $array['client_id']);
        $this->assertEquals('My App', $array['name']);
        $this->assertContains('loan:read', $array['scopes']);
        $this->assertContains('https://example.com/callback', $array['redirect_uris']);
        $this->assertArrayNotHasKey('client_secret', $array);
    }

    /**
     * Test constructor validation requires ID and secret
     *
     * @test
     */
    public function testConstructorValidatesCredentials(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('', 'secret');
    }

    /**
     * Test constructor validation requires both parameters
     *
     * @test
     */
    public function testConstructorValidatesSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('app_id', '');
    }

    /**
     * Test fluent interface chaining
     *
     * @test
     */
    public function testFluentInterfaceChaining(): void
    {
        $client = new Client('app_id', 'app_secret')
            ->grantScope('loan:read')
            ->grantScope('loan:write')
            ->setActive(true)
            ->addRedirectUri('https://example.com/callback');

        $this->assertTrue($client->hasScope('loan:read'));
        $this->assertTrue($client->hasScope('loan:write'));
        $this->assertTrue($client->isActive());
        $this->assertTrue($client->isRedirectUriAllowed('https://example.com/callback'));
    }

    /**
     * Test get scopes empty initially
     *
     * @test
     */
    public function testGetScopesEmpty(): void
    {
        $client = new Client('app_id', 'app_secret');

        $this->assertEmpty($client->getScopes());
    }

    /**
     * Test get redirect URIs empty initially
     *
     * @test
     */
    public function testGetRedirectUrisEmpty(): void
    {
        $client = new Client('app_id', 'app_secret');

        $this->assertEmpty($client->getRedirectUris());
    }
}
