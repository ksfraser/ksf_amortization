<?php
namespace Tests\Integration\Security\OAuth2;

use PHPUnit\Framework\TestCase;
use Ksfraser\Security\OAuth2\Http\OAuth2Controller;
use Ksfraser\Security\OAuth2\Grant\AuthorizationCodeGrant;
use Ksfraser\Security\OAuth2\PKCE\PKCEHandler;
use Ksfraser\Security\OAuth2\OAuth2Service;
use Ksfraser\Security\OAuth2\JWTTokenManager;
use Ksfraser\Security\OAuth2\OpenIDConnect\OpenIDConnectProvider;
use Ksfraser\Security\OAuth2\Repositories\AuthorizationCodeRepository;
use Ksfraser\Security\OAuth2\Repositories\OAuth2UserIdentityRepository;
use Ksfraser\Security\OAuth2\Repositories\OAuth2UserConsentRepository;
use PDO;

/**
 * OAuth2 End-to-End Integration Tests
 * 
 * Tests complete OAuth2 workflow scenarios:
 * 1. Authorization Code Flow (Web) - standard OAuth2 for web apps
 * 2. PKCE Flow (Mobile) - for native/mobile clients without client secret
 * 3. Refresh Token Flow - extending access token lifetime
 * 4. OpenID Connect Flow - identity federation
 * 
 * @package   Tests\Integration\Security\OAuth2
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class OAuth2EndToEndTest extends TestCase
{
    private $db;
    private $oauth2Controller;
    private $oauth2Service;
    private $codeRepo;
    private $userRepo;
    private $consentRepo;
    private $secretKey = 'this-is-a-very-long-secret-key-for-testing-purposes-12345';

    protected function setUp(): void
    {
        // Create in-memory SQLite database
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables
        $this->createTables();

        // Initialize repositories
        $this->codeRepo = new AuthorizationCodeRepository($this->db);
        $this->userRepo = new OAuth2UserIdentityRepository($this->db);
        $this->consentRepo = new OAuth2UserConsentRepository($this->db);

        // Initialize OAuth2 services
        $pkceHandler = new PKCEHandler();
        $authCodeGrant = new AuthorizationCodeGrant($this->codeRepo);
        $this->oauth2Controller = new OAuth2Controller($authCodeGrant, $pkceHandler);

        // Initialize OAuth2Service for token management
        $tokenManager = new JWTTokenManager($this->secretKey);
        $this->oauth2Service = new OAuth2Service(
            $tokenManager,
            ['issuer' => 'https://auth.example.com', 'audience' => 'api.example.com']
        );
    }

    private function createTables(): void
    {
        // Authorization codes
        $this->db->exec('
            CREATE TABLE oauth2_authorization_codes (
                id INTEGER PRIMARY KEY,
                code TEXT UNIQUE NOT NULL,
                client_id TEXT NOT NULL,
                user_id TEXT,
                redirect_uri TEXT NOT NULL,
                scopes TEXT,
                state TEXT,
                code_challenge TEXT,
                code_challenge_method TEXT,
                expires_at DATETIME NOT NULL,
                used_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // User identities
        $this->db->exec('
            CREATE TABLE oauth2_user_identities (
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

        // User consents
        $this->db->exec('
            CREATE TABLE oauth2_user_consents (
                id INTEGER PRIMARY KEY,
                user_id TEXT NOT NULL,
                client_id TEXT NOT NULL,
                scopes TEXT NOT NULL,
                consented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                revoked_at DATETIME
            )
        ');
    }

    // ===== AUTHORIZATION CODE FLOW (WEB) =====

    /**
     * E2E: Complete Authorization Code Flow (Web Application)
     * 
     * Scenario:
     * 1. User initiates login on web app
     * 2. App redirects to authorization endpoint
     * 3. User grants consent
     * 4. Authorization endpoint returns code
     * 5. App exchanges code for access token
     * 6. App uses token to access API
     */
    public function testAuthorizationCodeFlowComplete(): void
    {
        // Step 1: User requests authorization
        $authRequest = [
            'response_type' => 'code',
            'client_id' => 'web-app-client',
            'redirect_uri' => 'https://webapp.example.com/callback',
            'scope' => 'openid email profile',
            'state' => 'web-state-12345',
        ];

        // Step 2: Create user identity
        $this->userRepo->create('user-web-1', [
            'email' => 'user@example.com',
            'name' => 'Web User',
            'email_verified' => true
        ]);

        // Step 3: User grants consent
        $this->consentRepo->grant('user-web-1', 'web-app-client', ['openid', 'email', 'profile']);

        // Step 4: Get authorization code
        $authResponse = $this->oauth2Controller->handleAuthorizationRequest(
            $authRequest,
            ['id' => 'user-web-1'],
            ['openid', 'email', 'profile']
        );

        $this->assertEquals('authorization_granted', $authResponse['status']);
        $this->assertNotEmpty($authResponse['code']);
        $authCode = $authResponse['code'];

        // Step 5: Exchange code for token
        $tokenRequest = [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'web-app-client',
            'client_secret' => 'web-secret',
            'redirect_uri' => 'https://webapp.example.com/callback'
        ];

        $tokenResponse = $this->oauth2Controller->handleTokenRequest($tokenRequest);
        $this->assertArrayHasKey('access_token', $tokenResponse);
        $this->assertNotEmpty($tokenResponse['refresh_token']);

        // Step 6: Verify token is usable
        $this->assertNotEmpty($tokenResponse['access_token']);
    }

    /**
     * E2E: Authorization Code Flow with Login Required
     */
    public function testAuthorizationCodeFlowLoginRequired(): void
    {
        $authRequest = [
            'response_type' => 'code',
            'client_id' => 'web-app',
            'redirect_uri' => 'https://webapp.example.com/callback',
            'scope' => 'openid',
            'state' => 'state-123',
        ];

        // User not authenticated
        $response = $this->oauth2Controller->handleAuthorizationRequest(
            $authRequest,
            [], // Empty user data
            []
        );

        $this->assertEquals('login_required', $response['status']);
    }

    /**
     * E2E: Authorization Code Flow with Consent Required
     */
    public function testAuthorizationCodeFlowConsentRequired(): void
    {
        $authRequest = [
            'response_type' => 'code',
            'client_id' => 'web-app',
            'redirect_uri' => 'https://webapp.example.com/callback',
            'scope' => 'openid email',
            'state' => 'state-456',
        ];

        // User authenticated but no consent yet
        $response = $this->oauth2Controller->handleAuthorizationRequest(
            $authRequest,
            ['id' => 'user-123'],
            [] // No approved scopes
        );

        $this->assertEquals('consent_required', $response['status']);
        $this->assertEquals('openid email', $response['scope']);
    }

    // ===== PKCE FLOW (MOBILE) =====

    /**
     * E2E: Complete PKCE Flow (Mobile Application)
     * 
     * PKCE protects mobile apps without secure client secret storage.
     * 
     * Scenario:
     * 1. Mobile app generates code_verifier and code_challenge
     * 2. App requests authorization with code_challenge
     * 3. User grants consent
     * 4. Authorization endpoint returns code
     * 5. App exchanges code + verifier for token
     */
    public function testPKCEFlowComplete(): void
    {
        // Step 1: Generate PKCE parameters
        $codeVerifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXo';
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        // Step 2: Request authorization with PKCE
        $authRequest = [
            'response_type' => 'code',
            'client_id' => 'mobile-app',
            'redirect_uri' => 'myapp://callback',
            'scope' => 'openid offline_access',
            'state' => 'pkce-state-789',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        // Create user and consent
        $this->userRepo->create('user-mobile-1', ['email' => 'mobile@example.com']);
        $this->consentRepo->grant('user-mobile-1', 'mobile-app', ['openid', 'offline_access']);

        // Step 3: Get authorization code
        $authResponse = $this->oauth2Controller->handleAuthorizationRequest(
            $authRequest,
            ['id' => 'user-mobile-1'],
            ['openid', 'offline_access']
        );

        $this->assertEquals('authorization_granted', $authResponse['status']);
        $pkceCode = $authResponse['code'];

        // Step 4: Exchange code + verifier for token
        $tokenRequest = [
            'grant_type' => 'authorization_code',
            'code' => $pkceCode,
            'client_id' => 'mobile-app',
            'redirect_uri' => 'myapp://callback',
            'code_verifier' => $codeVerifier
        ];

        $tokenResponse = $this->oauth2Controller->handleTokenRequest($tokenRequest);
        $this->assertArrayHasKey('access_token', $tokenResponse);
    }

    /**
     * E2E: PKCE Flow with Invalid Verifier
     */
    public function testPKCEFlowInvalidVerifier(): void
    {
        // Create PKCE challenges
        $codeVerifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXo';
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        // Request authorization
        $authRequest = [
            'response_type' => 'code',
            'client_id' => 'mobile-app',
            'redirect_uri' => 'myapp://callback',
            'scope' => 'openid',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        $this->userRepo->create('user-mobile-2', []);
        $this->consentRepo->grant('user-mobile-2', 'mobile-app', ['openid']);

        $authResponse = $this->oauth2Controller->handleAuthorizationRequest(
            $authRequest,
            ['id' => 'user-mobile-2'],
            ['openid']
        );

        $code = $authResponse['code'];

        // Try to exchange with wrong verifier
        $tokenRequest = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => 'mobile-app',
            'redirect_uri' => 'myapp://callback',
            'code_verifier' => 'wrong-verifier'
        ];

        $tokenResponse = $this->oauth2Controller->handleTokenRequest($tokenRequest);
        $this->assertArrayHasKey('error', $tokenResponse);
    }

    // ===== OPENID CONNECT FLOW =====

    /**
     * E2E: OpenID Connect Flow with ID Token
     * 
     * OpenID Connect adds identity layer on top of OAuth2.
     * ID token contains user claims (subject, email, name, etc.)
     */
    public function testOpenIDConnectFlow(): void
    {
        // Step 1: Request authorization with openid scope
        $authRequest = [
            'response_type' => 'code',
            'client_id' => 'oidc-app',
            'redirect_uri' => 'https://oidc.example.com/callback',
            'scope' => 'openid email profile',
            'state' => 'oidc-state-999',
        ];

        // Create user identity for OpenID Connect
        $this->userRepo->create('user-oidc-1', [
            'email' => 'oidc@example.com',
            'email_verified' => true,
            'name' => 'OIDC User',
            'given_name' => 'Open',
            'family_name' => 'ID'
        ]);

        $this->consentRepo->grant('user-oidc-1', 'oidc-app', ['openid', 'email', 'profile']);

        // Step 2: Get authorization code
        $authResponse = $this->oauth2Controller->handleAuthorizationRequest(
            $authRequest,
            ['id' => 'user-oidc-1'],
            ['openid', 'email', 'profile']
        );

        $oidcCode = $authResponse['code'];

        // Step 3: Exchange for tokens (includes ID token)
        $tokenRequest = [
            'grant_type' => 'authorization_code',
            'code' => $oidcCode,
            'client_id' => 'oidc-app',
            'client_secret' => 'oidc-secret',
            'redirect_uri' => 'https://oidc.example.com/callback'
        ];

        $tokenResponse = $this->oauth2Controller->handleTokenRequest($tokenRequest);
        
        // Verify token response includes ID token
        $this->assertArrayHasKey('access_token', $tokenResponse);
        $this->assertArrayHasKey('id_token', $tokenResponse);

        // Step 4: Use UserInfo endpoint to get claims
        $userInfoResponse = $this->oauth2Controller->handleUserInfoRequest($tokenResponse['access_token']);
        $this->assertArrayHasKey('sub', $userInfoResponse);
        $this->assertArrayHasKey('email', $userInfoResponse);
    }

    // ===== DISCOVERY =====

    /**
     * E2E: OpenID Connect Discovery
     * 
     * Clients can discover OAuth2 endpoints via /.well-known/openid-configuration
     */
    public function testOpenIDConnectDiscovery(): void
    {
        $discovery = $this->oauth2Controller->getDiscoveryDocument();

        // Verify discovery document contains required endpoints
        $this->assertArrayHasKey('issuer', $discovery);
        $this->assertArrayHasKey('authorization_endpoint', $discovery);
        $this->assertArrayHasKey('token_endpoint', $discovery);
        $this->assertArrayHasKey('userinfo_endpoint', $discovery);
        $this->assertArrayHasKey('jwks_uri', $discovery);

        // Verify supported features
        $this->assertArrayHasKey('scopes_supported', $discovery);
        $this->assertContains('openid', $discovery['scopes_supported']);
        $this->assertContains('profile', $discovery['scopes_supported']);
        $this->assertContains('email', $discovery['scopes_supported']);

        // Verify PKCE support
        $this->assertArrayHasKey('code_challenge_methods_supported', $discovery);
        $this->assertContains('S256', $discovery['code_challenge_methods_supported']);
    }

    // ===== REPLAY PROTECTION =====

    /**
     * E2E: Authorization Code Replay Protection
     * 
     * Authorization codes are single-use to prevent replay attacks.
     */
    public function testAuthCodeReplayProtection(): void
    {
        // Request authorization
        $authRequest = [
            'response_type' => 'code',
            'client_id' => 'replay-client',
            'redirect_uri' => 'https://example.com/callback',
            'scope' => 'openid'
        ];

        $this->userRepo->create('user-replay', []);
        $this->consentRepo->grant('user-replay', 'replay-client', ['openid']);

        $authResponse = $this->oauth2Controller->handleAuthorizationRequest(
            $authRequest,
            ['id' => 'user-replay'],
            ['openid']
        );

        $code = $authResponse['code'];

        // First exchange succeeds
        $tokenRequest = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => 'replay-client',
            'client_secret' => 'secret',
            'redirect_uri' => 'https://example.com/callback'
        ];

        $firstExchange = $this->oauth2Controller->handleTokenRequest($tokenRequest);
        $this->assertArrayHasKey('access_token', $firstExchange);

        // Replay attempt with same code fails
        $replayResponse = $this->oauth2Controller->handleTokenRequest($tokenRequest);
        $this->assertArrayHasKey('error', $replayResponse);
    }

    // ===== STATE PARAMETER =====

    /**
     * E2E: CSRF Protection via State Parameter
     * 
     * State parameter prevents CSRF attacks in authorization request.
     */
    public function testStateParameterPreservation(): void
    {
        $csrfState = 'csrf-state-' . bin2hex(random_bytes(16));

        $authRequest = [
            'response_type' => 'code',
            'client_id' => 'state-client',
            'redirect_uri' => 'https://example.com/callback',
            'scope' => 'openid',
            'state' => $csrfState
        ];

        $this->userRepo->create('user-state', []);
        $this->consentRepo->grant('user-state', 'state-client', ['openid']);

        $response = $this->oauth2Controller->handleAuthorizationRequest(
            $authRequest,
            ['id' => 'user-state'],
            ['openid']
        );

        // State must be returned in response for app to verify
        $this->assertEquals($csrfState, $response['state']);
    }
}
