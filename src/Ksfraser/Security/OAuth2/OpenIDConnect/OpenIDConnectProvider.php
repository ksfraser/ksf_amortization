<?php
namespace Ksfraser\Security\OAuth2\OpenIDConnect;

use Ksfraser\Security\Exceptions\TokenException;
use Ksfraser\Security\OAuth2\JWTTokenManager;

/**
 * OpenID Connect Provider
 * 
 * Implements basic OpenID Connect features built on top of OAuth2.
 * Extends OAuth2 with user identity information.
 * 
 * OpenID Connect adds:
 * - ID Token: JWT containing user identity claims
 * - UserInfo Endpoint: Returns authenticated user information
 * - Discovery: /.well-known/openid-configuration
 * 
 * Standard Claims (returned by UserInfo):
 * - sub: Subject (unique user identifier)
 * - aud: Audience (intended app)
 * - iss: Issuer (identity provider)
 * - iat: Issued at (timestamp)
 * - exp: Expiration (timestamp)
 * - email: Email address
 * - email_verified: Whether email is verified
 * - name: Full name
 * - given_name: First name
 * - family_name: Last name
 * - picture: Profile picture URL
 * 
 * Scopes:
 * - profile: Basic profile info (name, picture, etc.)
 * - email: Email and email_verified
 * - address: Full address
 * - phone: Phone number
 * 
 * @package   Ksfraser\Security\OAuth2\OpenIDConnect
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-03
 */
class OpenIDConnectProvider
{
    /**
     * @var JWTTokenManager JWT manager for token generation
     */
    private $jwtManager;

    /**
     * @var array Configuration
     */
    private $config;

    /**
     * Constructor
     *
     * @param JWTTokenManager $jwtManager JWT manager instance
     * @param array $config Configuration options
     */
    public function __construct(JWTTokenManager $jwtManager, array $config = [])
    {
        $this->jwtManager = $jwtManager;
        $this->config = array_merge([
            'issuer' => 'https://auth.example.com',
            'base_url' => 'https://auth.example.com',
            'supported_scopes' => ['openid', 'email', 'profile', 'address', 'phone'],
            'id_token_expiry' => 3600,
        ], $config);
    }

    /**
     * Generate ID Token
     * 
     * Creates a JWT containing user identity information.
     * Should be returned with access token in authorization code flow.
     *
     * @param string $userId User identifier (subject)
     * @param array $userClaims User data (email, name, picture, etc.)
     * @param array $requestedScopes Scopes requested by client
     * @param string $clientId OAuth2 client ID (audience)
     * @param string $nonce Nonce parameter from auth request (for security)
     *
     * @return string JWT ID token
     *
     * @throws TokenException If token generation fails
     */
    public function generateIDToken(
        string $userId,
        array $userClaims,
        array $requestedScopes,
        string $clientId,
        string $nonce = ''
    ): string
    {
        try {
            $now = time();

            // Build ID token claims
            $claims = [
                'sub' => $userId, // Subject (must be unique and stable)
                'aud' => $clientId, // Audience (the client requesting the token)
                'iss' => $this->config['issuer'], // Issuer
                'iat' => $now, // Issued at
                'exp' => $now + $this->config['id_token_expiry'], // Expiration
                'auth_time' => $now, // Time user authenticated
            ];

            // Add nonce if provided (security: links ID token to auth request)
            if (!empty($nonce)) {
                $claims['nonce'] = $nonce;
            }

            // Add claims based on scopes
            $claims = $this->addScopeClaims($claims, $userClaims, $requestedScopes);

            // Generate JWT using JWTTokenManager
            return $this->jwtManager->generate($claims, $this->config['issuer'], $clientId);
        } catch (\Exception $e) {
            throw new TokenException("Failed to generate ID token: " . $e->getMessage());
        }
    }

    /**
     * Get UserInfo
     * 
     * Returns user information for the authenticated access token.
     * Should be protected by the access token validation.
     *
     * @param string $userId User identifier
     * @param array $userClaims User data from storage
     * @param array $requestedScopes Scopes approved by user
     *
     * @return array User information claims
     *
     * @throws TokenException If user not found
     */
    public function getUserInfo(
        string $userId,
        array $userClaims = [],
        array $requestedScopes = ['openid']
    ): array
    {
        try {
            // Always include subject (required)
            $info = [
                'sub' => $userId, // Subject identifier
            ];

            // Add claims based on requested scopes
            $info = $this->addScopeClaims($info, $userClaims, $requestedScopes);

            return $info;
        } catch (\Exception $e) {
            throw new TokenException("Failed to retrieve user info: " . $e->getMessage());
        }
    }

    /**
     * Get OpenID Connect Discovery Document
     * 
     * Returns /.well-known/openid-configuration
     * Lists endpoints and capabilities of the authorization server.
     *
     * @return array Discovery document
     */
    public function getDiscoveryDocument(): array
    {
        return [
            'issuer' => $this->config['issuer'],
            'authorization_endpoint' => $this->config['base_url'] . '/oauth/authorize',
            'token_endpoint' => $this->config['base_url'] . '/oauth/token',
            'userinfo_endpoint' => $this->config['base_url'] . '/oauth/userinfo',
            'end_session_endpoint' => $this->config['base_url'] . '/oauth/logout',
            'jwks_uri' => $this->config['base_url'] . '/.well-known/jwks.json',
            'registration_endpoint' => $this->config['base_url'] . '/oauth/register',
            
            // Capabilities
            'scopes_supported' => $this->config['supported_scopes'],
            'response_types_supported' => ['code', 'token', 'id_token'],
            'grant_types_supported' => ['authorization_code', 'refresh_token', 'implicit'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['HS256', 'RS256'],
            'id_token_encryption_alg_values_supported' => [],
            'id_token_encryption_enc_values_supported' => [],
            'userinfo_signing_alg_values_supported' => ['HS256', 'RS256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'token_endpoint_auth_signing_alg_values_supported' => ['HS256'],
            'code_challenge_methods_supported' => ['S256', 'plain'],
            'claims_supported' => [
                'sub', 'aud', 'iss', 'iat', 'exp', 'auth_time', 'nonce',
                'email', 'email_verified', 'name', 'given_name', 'family_name',
                'picture', 'profile', 'website', 'locale', 'zoneinfo',
                'address', 'phone_number', 'phone_number_verified',
            ],
            'claim_types_supported' => ['normal', 'aggregated', 'distributed'],
            'claims_parameter_supported' => true,
            'request_parameter_supported' => false,
            'request_uri_parameter_supported' => false,
            
            // Service policies
            'service_documentation' => $this->config['base_url'] . '/docs/oidc',
            'ui_locales_supported' => ['en-US', 'en', 'es'],
            'require_request_uri_registration' => false,
        ];
    }

    /**
     * Validate ID token signature
     * 
     * Verifies an ID token was issued by this server and hasn't been modified.
     *
     * @param string $idToken ID token to validate
     * @param string $clientId Expected client ID (audience)
     * @param string $nonce Expected nonce value
     *
     * @return array Decoded token claims
     *
     * @throws TokenException If validation fails
     */
    public function validateIDToken(
        string $idToken,
        string $clientId,
        string $nonce = ''
    ): array
    {
        try {
            // Validate using JWT manager
            $claims = $this->jwtManager->validate(
                $idToken,
                $this->config['issuer'],
                $clientId
            );

            // Verify nonce if provided
            if (!empty($nonce)) {
                if (($claims['nonce'] ?? '') !== $nonce) {
                    throw new TokenException("Nonce mismatch");
                }
            }

            return $claims;
        } catch (\Exception $e) {
            throw new TokenException("ID token validation failed: " . $e->getMessage());
        }
    }

    /**
     * Add claims to token based on scopes
     * 
     * Maps requested scopes to user claim data.
     * Only returns claims for scopes the user approved.
     *
     * @param array $claims Current claims
     * @param array $userClaims Available user data
     * @param array $scopes Approved scopes
     *
     * @return array Enhanced claims
     */
    private function addScopeClaims(array $claims, array $userClaims, array $scopes): array
    {
        // OpenID scope (always required, just adds sub)
        if (in_array('openid', $scopes)) {
            // Already have 'sub', nothing extra needed
        }

        // Profile scope: name, given_name, family_name, picture, etc.
        if (in_array('profile', $scopes)) {
            $profileClaims = [
                'name' => $userClaims['name'] ?? null,
                'given_name' => $userClaims['given_name'] ?? null,
                'family_name' => $userClaims['family_name'] ?? null,
                'middle_name' => $userClaims['middle_name'] ?? null,
                'nickname' => $userClaims['nickname'] ?? null,
                'preferred_username' => $userClaims['preferred_username'] ?? null,
                'profile' => $userClaims['profile_url'] ?? null,
                'picture' => $userClaims['picture_url'] ?? null,
                'website' => $userClaims['website_url'] ?? null,
                'gender' => $userClaims['gender'] ?? null,
                'birthdate' => $userClaims['birthdate'] ?? null,
                'zoneinfo' => $userClaims['zoneinfo'] ?? null,
                'locale' => $userClaims['locale'] ?? null,
                'updated_at' => $userClaims['updated_at'] ?? null,
            ];
            // Only add claims that have values
            foreach ($profileClaims as $key => $value) {
                if ($value !== null) {
                    $claims[$key] = $value;
                }
            }
        }

        // Email scope: email, email_verified
        if (in_array('email', $scopes)) {
            if (!empty($userClaims['email'])) {
                $claims['email'] = $userClaims['email'];
                $claims['email_verified'] = $userClaims['email_verified'] ?? false;
            }
        }

        // Address scope: address (JSON object)
        if (in_array('address', $scopes)) {
            if (!empty($userClaims['address'])) {
                $claims['address'] = $userClaims['address'];
            }
        }

        // Phone scope: phone_number, phone_number_verified
        if (in_array('phone', $scopes)) {
            if (!empty($userClaims['phone_number'])) {
                $claims['phone_number'] = $userClaims['phone_number'];
                $claims['phone_number_verified'] = $userClaims['phone_number_verified'] ?? false;
            }
        }

        return $claims;
    }

    /**
     * Get supported scopes
     *
     * @return array List of supported scope names
     */
    public function getSupportedScopes(): array
    {
        return $this->config['supported_scopes'];
    }

    /**
     * Get scope descriptions
     *
     * @return array Scope names and descriptions
     */
    public function getScopeDescriptions(): array
    {
        return [
            'openid' => 'Access ID token with basic identity',
            'profile' => 'Access profile information (name, picture, etc.)',
            'email' => 'Access email address and verification status',
            'address' => 'Access postal address',
            'phone' => 'Access phone number',
        ];
    }
}
