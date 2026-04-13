<?php
namespace Ksfraser\Security\OAuth2\Http;

use Ksfraser\Security\OAuth2\Grant\AuthorizationCodeGrant;
use Ksfraser\Security\OAuth2\PKCE\PKCEHandler;
use Ksfraser\Security\Exceptions\AuthenticationException;
use Ksfraser\Security\Exceptions\TokenException;

/**
 * OAuth2 Authorization Controller
 * 
 * Handles OAuth2 authorization endpoint (/oauth2/authorize) for RFC 6749 compliant flows.
 * 
 * Endpoints:
 * - GET/POST /oauth2/authorize - Authorization code request
 * - POST /oauth2/token - Token exchange
 * - GET /oauth2/userinfo - User information
 * - GET /.well-known/openid-configuration - Discovery document
 * 
 * @package   Ksfraser\Security\OAuth2\Http
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-03
 */
class OAuth2Controller
{
    /**
     * @var AuthorizationCodeGrant
     */
    private $authorizationCodeGrant;

    /**
     * @var PKCEHandler
     */
    private $pkceHandler;

    /**
     * @var array Configuration
     */
    private $config;

    /**
     * Constructor
     *
     * @param AuthorizationCodeGrant $authorizationCodeGrant Grant handler
     * @param PKCEHandler $pkceHandler PKCE handler
     * @param array $config Configuration options
     */
    public function __construct(
        AuthorizationCodeGrant $authorizationCodeGrant,
        PKCEHandler $pkceHandler,
        array $config = []
    ) {
        $this->authorizationCodeGrant = $authorizationCodeGrant;
        $this->pkceHandler = $pkceHandler;
        $this->config = array_merge([
            'base_url' => 'https://localhost',
            'authorize_path' => '/oauth2/authorize',
            'token_path' => '/oauth2/token',
            'userinfo_path' => '/oauth2/userinfo',
            'discovery_path' => '/.well-known/openid-configuration',
        ], $config);
    }

    /**
     * Handle authorization request
     * 
     * RFC 6749 §4.1.1 Authorization Request
     * 
     * Query Parameters:
     * - response_type: "code" (REQUIRED)
     * - client_id: OAuth2 client identifier (REQUIRED)
     * - redirect_uri: Callback URL (REQUIRED)
     * - scope: Space-separated scopes (OPTIONAL)
     * - state: CSRF protection token (RECOMMENDED)
     * - code_challenge: PKCE code challenge (OPTIONAL)
     * - code_challenge_method: "S256" or "plain" (OPTIONAL, default "plain")
     * 
     * Response:
     * - On success: Redirect to redirect_uri with ?code=...&state=...
     * - On error: Redirect to redirect_uri with ?error=...&error_description=...
     *
     * @param array $queryParams Request query parameters
     * @param array $authenticatedUser User identity from session (['id' => 'user-123'])
     * @param array $approvedScopes Pre-approved scopes (empty = show consent)
     *
     * @return array Response data with redirect_uri, code, state, error
     *
     * @throws AuthenticationException If client_id or redirect_uri invalid
     */
    public function handleAuthorizationRequest(
        array $queryParams,
        array $authenticatedUser = [],
        array $approvedScopes = []
    ): array
    {
        // Validate required parameters
        $clientId = $queryParams['client_id'] ?? null;
        $redirectUri = $queryParams['redirect_uri'] ?? null;
        $responseType = $queryParams['response_type'] ?? null;
        $scope = $queryParams['scope'] ?? '';
        $state = $queryParams['state'] ?? '';
        $codeChallenge = $queryParams['code_challenge'] ?? '';
        $codeChallengeMethod = $queryParams['code_challenge_method'] ?? 'plain';

        // Validate response_type
        if ($responseType !== 'code') {
            return $this->buildErrorResponse(
                $redirectUri,
                'unsupported_response_type',
                'Only response_type=code is supported',
                $state
            );
        }

        // Validate client_id
        if (empty($clientId)) {
            return $this->buildErrorResponse(
                null,
                'invalid_request',
                'client_id parameter is required'
            );
        }

        // Validate redirect_uri
        if (empty($redirectUri)) {
            return $this->buildErrorResponse(
                null,
                'invalid_request',
                'redirect_uri parameter is required'
            );
        }

        // TODO: In production, validate client_id and redirect_uri against registered clients
        // This is a simplified implementation

        // Check if user is authenticated
        if (empty($authenticatedUser) || empty($authenticatedUser['id'])) {
            // User not authenticated - return login redirect
            return [
                'status' => 'login_required',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => $scope,
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => $codeChallengeMethod,
            ];
        }

        // Check if user consent is required
        if (empty($approvedScopes)) {
            // Show consent screen
            return [
                'status' => 'consent_required',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => $scope,
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => $codeChallengeMethod,
                'user_id' => $authenticatedUser['id'],
            ];
        }

        // User authenticated and consent granted - generate authorization code
        try {
            $requestedScopes = !empty($scope) ? explode(' ', $scope) : [];

            $authorizationCode = $this->authorizationCodeGrant->generateAuthorizationCode(
                $clientId,
                $authenticatedUser['id'],
                $requestedScopes,
                $redirectUri,
                $state,
                $codeChallenge,
                $codeChallengeMethod
            );

            // Return redirect response
            return [
                'status' => 'authorization_granted',
                'redirect_uri' => $redirectUri,
                'code' => $authorizationCode,
                'state' => $state,
            ];
        } catch (\Exception $e) {
            return $this->buildErrorResponse(
                $redirectUri,
                'server_error',
                'Failed to generate authorization code: ' . $e->getMessage(),
                $state
            );
        }
    }

    /**
     * Handle token exchange request
     * 
     * RFC 6749 §4.1.3 Token Request
     * 
     * Form Parameters:
     * - grant_type: "authorization_code" (REQUIRED)
     * - code: Authorization code from authorize endpoint (REQUIRED)
     * - client_id: OAuth2 client identifier (REQUIRED)
     * - client_secret: Client secret (REQUIRED)
     * - redirect_uri: Must match authorization request (REQUIRED)
     * - code_verifier: PKCE code verifier (REQUIRED if PKCE used)
     *
     * Response:
     * - access_token: Bearer token for API requests
     * - token_type: "Bearer"
     * - expires_in: Token expiration in seconds
     * - refresh_token: Token for refreshing access_token
     * - id_token: JWT with user identity (OpenID Connect)
     *
     * @param array $formParams Request form parameters
     *
     * @return array Token response or error
     *
     * @throws AuthenticationException If credentials invalid
     * @throws TokenException If token generation fails
     */
    public function handleTokenRequest(array $formParams): array
    {
        $grantType = $formParams['grant_type'] ?? null;
        $code = $formParams['code'] ?? null;
        $clientId = $formParams['client_id'] ?? null;
        $clientSecret = $formParams['client_secret'] ?? null;
        $redirectUri = $formParams['redirect_uri'] ?? null;
        $codeVerifier = $formParams['code_verifier'] ?? null;

        // Validate grant_type
        if ($grantType !== 'authorization_code') {
            return $this->buildTokenErrorResponse(
                'unsupported_grant_type',
                'Only grant_type=authorization_code is supported'
            );
        }

        // Validate required parameters
        if (empty($code) || empty($clientId) || empty($clientSecret) || empty($redirectUri)) {
            return $this->buildTokenErrorResponse(
                'invalid_request',
                'Missing required parameters: code, client_id, client_secret, redirect_uri'
            );
        }

        try {
            // Validate authorization code
            $codeData = $this->authorizationCodeGrant->validateAuthorizationCode(
                $code,
                $clientId,
                $redirectUri
            );

            // Validate PKCE if code_challenge was used
            if (!empty($codeData['code_challenge'])) {
                if (empty($codeVerifier)) {
                    return $this->buildTokenErrorResponse(
                        'invalid_request',
                        'code_verifier is required when PKCE was used'
                    );
                }

                // Validate code_verifier matches code_challenge
                $this->pkceHandler->validateCodeChallenge(
                    $codeVerifier,
                    $codeData['code_challenge'],
                    $codeData['code_challenge_method'] ?? 'plain'
                );
            }

            // Exchange code for token
            $tokenResponse = $this->authorizationCodeGrant->exchangeCodeForToken(
                $code,
                $clientId,
                $clientSecret,
                $redirectUri,
                $codeVerifier ?? ''
            );

            return [
                'access_token' => $tokenResponse['access_token'],
                'token_type' => $tokenResponse['token_type'],
                'expires_in' => $tokenResponse['expires_in'],
                'refresh_token' => $tokenResponse['refresh_token'],
                'id_token' => $tokenResponse['id_token'] ?? null,
            ];
        } catch (AuthenticationException $e) {
            return $this->buildTokenErrorResponse(
                'invalid_grant',
                $e->getMessage()
            );
        } catch (TokenException $e) {
            return $this->buildTokenErrorResponse(
                'invalid_request',
                $e->getMessage()
            );
        }
    }

    /**
     * Handle userinfo request
     * 
     * OpenID Connect UserInfo Endpoint
     * 
     * Headers:
     * - Authorization: "Bearer {access_token}" (REQUIRED)
     *
     * Response:
     * - sub: Subject (user ID)
     * - email: User email (if email scope approved)
     * - name: User name (if profile scope approved)
     * - picture: Profile picture URL
     * - ... other claims based on approved scopes
     *
     * @param string $accessToken Bearer token from Authorization header
     *
     * @return array User information or error
     *
     * @throws TokenException If token invalid
     */
    public function handleUserInfoRequest(string $accessToken): array
    {
        if (empty($accessToken)) {
            return $this->buildUserInfoErrorResponse(
                'invalid_request',
                'access_token is required'
            );
        }

        try {
            // TODO: Validate access_token and extract user_id
            // This is a placeholder - implement token validation
            
            return [
                'sub' => 'user-123',
                'email' => 'user@example.com',
                'name' => 'John Doe',
                'email_verified' => true,
            ];
        } catch (\Exception $e) {
            return $this->buildUserInfoErrorResponse(
                'invalid_token',
                $e->getMessage()
            );
        }
    }

    /**
     * Get OpenID Connect discovery document
     * 
     * Returns /.well-known/openid-configuration with server metadata
     *
     * @return array Discovery document
     */
    public function getDiscoveryDocument(): array
    {
        return [
            'issuer' => $this->config['base_url'],
            'authorization_endpoint' => $this->config['base_url'] . $this->config['authorize_path'],
            'token_endpoint' => $this->config['base_url'] . $this->config['token_path'],
            'userinfo_endpoint' => $this->config['base_url'] . $this->config['userinfo_path'],
            'jwks_uri' => $this->config['base_url'] . '/oauth2/jwks',
            'scopes_supported' => [
                'openid',
                'profile',
                'email',
                'address',
                'phone',
                'amortization:read',
                'amortization:write',
                'portfolio:read',
                'portfolio:write',
            ],
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'code_challenge_methods_supported' => ['S256', 'plain'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['HS256', 'RS256'],
            'claims_supported' => [
                'sub',
                'iss',
                'aud',
                'iat',
                'exp',
                'auth_time',
                'email',
                'email_verified',
                'name',
                'given_name',
                'family_name',
                'picture',
                'phone_number',
                'phone_number_verified',
                'address',
            ],
        ];
    }

    /**
     * Build error response for authorization endpoint
     *
     * @param string|null $redirectUri Redirect URI (null for invalid_request)
     * @param string $error Error code
     * @param string $description Error description
     * @param string $state State parameter from request
     *
     * @return array Error response
     */
    private function buildErrorResponse(
        ?string $redirectUri,
        string $error,
        string $description,
        string $state = ''
    ): array
    {
        if ($redirectUri === null) {
            // Cannot redirect - return error directly
            return [
                'status' => 'error',
                'error' => $error,
                'error_description' => $description,
            ];
        }

        // Build redirect with error parameters
        $response = [
            'status' => 'authorization_denied',
            'redirect_uri' => $redirectUri,
            'error' => $error,
            'error_description' => $description,
        ];

        if (!empty($state)) {
            $response['state'] = $state;
        }

        return $response;
    }

    /**
     * Build error response for token endpoint
     *
     * @param string $error Error code
     * @param string $description Error description
     *
     * @return array Error response
     */
    private function buildTokenErrorResponse(string $error, string $description): array
    {
        return [
            'error' => $error,
            'error_description' => $description,
        ];
    }

    /**
     * Build error response for userinfo endpoint
     *
     * @param string $error Error code
     * @param string $description Error description
     *
     * @return array Error response
     */
    private function buildUserInfoErrorResponse(string $error, string $description): array
    {
        return [
            'error' => $error,
            'error_description' => $description,
        ];
    }
}
