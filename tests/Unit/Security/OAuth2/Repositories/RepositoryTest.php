<?php
namespace Tests\Unit\Security\OAuth2\Repositories;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\Repositories\AuthorizationCodeRepository;
use Ksfraser\Security\OAuth2\Repositories\OAuth2UserIdentityRepository;
use Ksfraser\Security\OAuth2\Repositories\OAuth2TokenRepository;
use Ksfraser\Security\OAuth2\Repositories\OAuth2UserConsentRepository;
use Ksfraser\Security\Exceptions\TokenException;
use PDO;

class AuthorizationCodeRepositoryTest extends TestCase
{
    private $db;
    private $repo;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables
        $this->createTables();
        
        $this->repo = new AuthorizationCodeRepository($this->db, 600);
    }

    private function createTables(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../../../../../migrations/migration_20260403_001_authorization_code_flow.sql');
        // Replace IF NOT EXISTS for SQLite compatibility
        $statements = array_filter(explode(';', $sql));
        foreach ($statements as $stmt) {
            try {
                $this->db->exec($stmt);
            } catch (\Exception $e) {
                // Skip comments and already created tables
            }
        }
    }

    /**
     * Test create and retrieve authorization code
     */
    public function testCreateAndRetrieveCode(): void
    {
        $code = $this->repo->create(
            'test-client',
            'https://app.example.com/callback',
            ['read', 'write'],
            'state123',
            'user-456'
        );

        $this->assertNotEmpty($code);
        $this->assertTrue($this->repo->isValid($code));

        $codeData = $this->repo->getCode($code);
        $this->assertNotNull($codeData);
        $this->assertEquals('test-client', $codeData['client_id']);
        $this->assertEquals('user-456', $codeData['user_id']);
        $this->assertEquals('https://app.example.com/callback', $codeData['redirect_uri']);
    }

    /**
     * Test code expiration
     */
    public function testCodeExpiration(): void
    {
        $this->repo->setExpirationTime(1); // 1 second
        $code = $this->repo->create(
            'test-client',
            'https://app.example.com/callback',
            ['read'],
            'state123'
        );

        sleep(2);

        $this->assertNull($this->repo->getCode($code));
        $this->assertFalse($this->repo->isValid($code));
    }

    /**
     * Test single-use authorization code
     */
    public function testSingleUseCode(): void
    {
        $code = $this->repo->create(
            'test-client',
            'https://app.example.com/callback',
            ['read'],
            'state123'
        );

        // First use succeeds
        $validated = $this->repo->validate($code, 'test-client', 'https://app.example.com/callback');
        $this->assertNotNull($validated);

        // Second use fails
        $this->expectException(TokenException::class);
        $this->repo->validate($code, 'test-client', 'https://app.example.com/callback');
    }

    /**
     * Test client_id mismatch
     */
    public function testClientIdMismatch(): void
    {
        $code = $this->repo->create(
            'test-client',
            'https://app.example.com/callback',
            ['read'],
            'state123'
        );

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('mismatch');
        $this->repo->validate($code, 'wrong-client', 'https://app.example.com/callback');
    }

    /**
     * Test redirect_uri mismatch
     */
    public function testRedirectUriMismatch(): void
    {
        $code = $this->repo->create(
            'test-client',
            'https://app.example.com/callback',
            ['read'],
            'state123'
        );

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('mismatch');
        $this->repo->validate($code, 'test-client', 'https://wrong.example.com/callback');
    }

    /**
     * Test PKCE with S256 method
     */
    public function testPKCES256(): void
    {
        $codeVerifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXo';
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $code = $this->repo->create(
            'mobile-client',
            'myapp://callback',
            ['read'],
            'state123',
            'user-789',
            $codeChallenge,
            'S256'
        );

        // Valid verification
        $validated = $this->repo->validate(
            $code,
            'mobile-client',
            'myapp://callback',
            $codeVerifier
        );
        $this->assertNotNull($validated);
    }

    /**
     * Test PKCE with plain method
     */
    public function testPKCEPlain(): void
    {
        $codeVerifier = 'plain-verifier-123';
        $code = $this->repo->create(
            'client',
            'https://example.com/callback',
            ['read'],
            'state',
            null,
            $codeVerifier,
            'plain'
        );

        $validated = $this->repo->validate($code, 'client', 'https://example.com/callback', $codeVerifier);
        $this->assertNotNull($validated);
    }

    /**
     * Test PKCE missing verifier
     */
    public function testPKCEMissingVerifier(): void
    {
        $codeChallenge = 'some_challenge';
        $code = $this->repo->create(
            'client',
            'https://example.com/callback',
            ['read'],
            'state',
            null,
            $codeChallenge,
            'S256'
        );

        $this->expectException(TokenException::class);
        $this->repo->validate($code, 'client', 'https://example.com/callback');
    }

    /**
     * Test scopes preservation
     */
    public function testScopesPreservation(): void
    {
        $scopes = ['amortization:read', 'portfolio:write', 'admin:*'];
        $code = $this->repo->create(
            'client',
            'https://example.com/callback',
            $scopes,
            'state'
        );

        $codeData = $this->repo->getCode($code);
        $this->assertEquals($scopes, $codeData['scopes']);
    }

    /**
     * Test revoke code
     */
    public function testRevokeCode(): void
    {
        $code = $this->repo->create('client', 'https://example.com/callback', ['read'], 'state');
        
        $this->repo->revoke($code);
        
        $this->expectException(TokenException::class);
        $this->repo->validate($code, 'client', 'https://example.com/callback');
    }

    /**
     * Test state parameter
     */
    public function testStateParameter(): void
    {
        $state = 'unique-state-' . random_bytes(16);
        $code = $this->repo->create(
            'client',
            'https://example.com/callback',
            ['read'],
            $state
        );

        $codeData = $this->repo->getCode($code);
        $this->assertEquals($state, $codeData['state']);
    }
}

class OAuth2UserIdentityRepositoryTest extends TestCase
{
    private $db;
    private $repo;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->createTables();
        $this->repo = new OAuth2UserIdentityRepository($this->db);
    }

    private function createTables(): void
    {
        // Create user identities table
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS oauth2_user_identities (
                id INTEGER PRIMARY KEY,
                user_id TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE,
                email_verified BOOLEAN DEFAULT 0,
                name TEXT,
                given_name TEXT,
                family_name TEXT,
                phone_number TEXT,
                phone_number_verified BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    /**
     * Test create and retrieve user identity
     */
    public function testCreateAndRetrieveIdentity(): void
    {
        $this->repo->create('user-123', [
            'email' => 'user@example.com',
            'name' => 'John Doe'
        ]);

        $identity = $this->repo->getIdentity('user-123');
        $this->assertNotNull($identity);
        $this->assertEquals('user@example.com', $identity['email']);
        $this->assertEquals('John Doe', $identity['name']);
    }

    /**
     * Test update user identity
     */
    public function testUpdateIdentity(): void
    {
        $this->repo->create('user-123', ['email' => 'old@example.com']);
        $this->repo->update('user-123', ['email' => 'new@example.com']);

        $identity = $this->repo->getIdentity('user-123');
        $this->assertEquals('new@example.com', $identity['email']);
    }

    /**
     * Test find by email
     */
    public function testFindByEmail(): void
    {
        $this->repo->create('user-123', ['email' => 'test@example.com']);
        
        $identity = $this->repo->findByEmail('test@example.com');
        $this->assertNotNull($identity);
        $this->assertEquals('user-123', $identity['user_id']);
    }

    /**
     * Test email verification
     */
    public function testEmailVerification(): void
    {
        $this->repo->create('user-123', ['email' => 'user@example.com']);
        $this->repo->verifyEmail('user-123');

        $identity = $this->repo->getIdentity('user-123');
        $this->assertTrue((bool)$identity['email_verified']);
    }

    /**
     * Test phone verification
     */
    public function testPhoneVerification(): void
    {
        $this->repo->create('user-123', ['phone_number' => '+1234567890']);
        $this->repo->verifyPhoneNumber('user-123');

        $identity = $this->repo->getIdentity('user-123');
        $this->assertTrue((bool)$identity['phone_number_verified']);
    }
}

class OAuth2TokenRepositoryTest extends TestCase
{
    private $db;
    private $repo;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->createTables();
        $this->repo = new OAuth2TokenRepository($this->db);
    }

    private function createTables(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS oauth2_tokens (
                id INTEGER PRIMARY KEY,
                token_hash TEXT UNIQUE NOT NULL,
                client_id TEXT NOT NULL,
                token_type TEXT NOT NULL,
                revoked BOOLEAN DEFAULT 0,
                expires_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    /**
     * Test store and verify token
     */
    public function testStoreAndVerifyToken(): void
    {
        $token = 'test-token-123';
        $hash = hash('sha256', $token);

        $this->repo->create($hash, 'client-123', 'access', 3600);

        $this->assertTrue($this->repo->exists($hash));
        $this->assertFalse($this->repo->isRevoked($hash));
    }

    /**
     * Test revoke token
     */
    public function testRevokeToken(): void
    {
        $hash = hash('sha256', 'token-123');
        $this->repo->create($hash, 'client-123', 'access', 3600);

        $this->repo->revoke($hash);
        $this->assertTrue($this->repo->isRevoked($hash));
    }

    /**
     * Test delete expired tokens
     */
    public function testDeleteExpiredTokens(): void
    {
        $hash = hash('sha256', 'expired-token');
        $this->repo->create($hash, 'client-123', 'access', -1); // Already expired

        $deleted = $this->repo->deleteExpired();
        $this->assertGreater($deleted, 0);
    }
}

class OAuth2UserConsentRepositoryTest extends TestCase
{
    private $db;
    private $repo;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->createTables();
        $this->repo = new OAuth2UserConsentRepository($this->db);
    }

    private function createTables(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS oauth2_user_consents (
                id INTEGER PRIMARY KEY,
                user_id TEXT NOT NULL,
                client_id TEXT NOT NULL,
                scopes TEXT NOT NULL,
                consented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                revoked_at DATETIME
            )
        ');
    }

    /**
     * Test grant consent
     */
    public function testGrantConsent(): void
    {
        $this->repo->grant('user-123', 'client-456', ['read', 'write']);

        $this->assertTrue($this->repo->hasConsent('user-123', 'client-456', ['read']));
    }

    /**
     * Test revoke consent
     */
    public function testRevokeConsent(): void
    {
        $this->repo->grant('user-123', 'client-456', ['read']);
        $this->repo->revoke('user-123', 'client-456');

        $this->assertFalse($this->repo->hasConsent('user-123', 'client-456', ['read']));
    }

    /**
     * Test check required scopes
     */
    public function testCheckRequiredScopes(): void
    {
        $this->repo->grant('user-123', 'client-456', ['read', 'write', 'delete']);

        $this->assertTrue($this->repo->hasConsent('user-123', 'client-456', ['read', 'write']));
        $this->assertFalse($this->repo->hasConsent('user-123', 'client-456', ['admin']));
    }
}
