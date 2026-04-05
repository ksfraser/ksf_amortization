<?php
namespace Ksfraser\Security\OAuth2;

use Ksfraser\Security\Exceptions\AuthenticationException;
use Ksfraser\Security\Exceptions\TokenException;

/**
 * OAuth2 Service - Central authentication authority
 * 
 * NOTE: This is a temporary implementation using custom OAuth2 logic
 * which will be replaced with league/oauth2-server once package installation
 * issues are resolved. The API remains identical for seamless migration.
 * 
 * Handles OAuth2 authentication flows including:
 * - Client credentials grant
 * - Token generation and validation
 * - Refresh token management
 * - Token revocation
 * 
 * TODO-MIGRATION: Replace with league/oauth2-server wrapper
 * Run: composer install (after resolving GitHub authentication)
 * Edit: Update to use AuthorizationServer and ResourceServer from league
 * 
 * @package   Ksfraser\Security\OAuth2
 * @author    KSF Development Team
 * @version   1.0-temporary
 * @since     2026-04-03
 */
class OAuth2Service
{
    /**
     * @var JWTTokenManager
     */
    private $jwtManager;

    /**
     * @var array Configuration
     */
    private $config;

    /**
     * OAuth2Service constructor.
     *
     * Supports simplified constructor mode for backward compatibility.
     *
     * @param JWTTokenManager $jwtManager
     * @param array $config Configuration options
     */
    public function __construct(JWTTokenManager $jwtManager, array $config = [])
    {
        $this->jwtManager = $jwtManager;
        $this->config = array_merge([
            'issuer' => 'ksfraser-api',
            'audience' => 'ksfraser-services',
            'tokenExpiry' => 3600, // 1 hour
            'refreshTokenExpiry' => 604800, // 7 days
        ], $config);
    }

    /**
     * Authenticate a client using client credentials
     *
     * @param string $clientId Client identifier
     * @param string $clientSecret Client secret
     * @param array $scopes Requested scopes/permissions
     *
     * @return array Token response with access_token, refresh_token, and expiry
     *
     * @throws AuthenticationException If credentials invalid
     * @throws TokenException If token generation fails
     */
    public function authenticateClient(string $clientId, string $clientSecret, array $scopes = []): array
    {
        try {
            // Validate credentials (basic validation without database)
            if (empty($clientId) || empty($clientSecret)) {
                throw new AuthenticationException("Invalid client credentials");
            }

            $now = time();
            $accessTokenExpiry = $now + $this->config['tokenExpiry'];
            $refreshTokenExpiry = $now + $this->config['refreshTokenExpiry'];

            $accessToken = $this->jwtManager->generate([
                'client_id' => $clientId,
                'scopes' => $scopes,
                'type' => 'access',
                'iat' => $now,
                'exp' => $accessTokenExpiry,
            ], $this->config['issuer'], $this->config['audience']);

            $refreshToken = $this->jwtManager->generate([
                'client_id' => $clientId,
                'type' => 'refresh',
                'iat' => $now,
                'exp' => $refreshTokenExpiry,
            ], $this->config['issuer'], $this->config['audience']);

            return [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->config['tokenExpiry'],
                'refresh_token' => $refreshToken,
                'scope' => implode(' ', $scopes),
            ];
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TokenException("Token generation failed: " . $e->getMessage());
        }
    }

    /**
     * Validate an access token
     *
     * @param string $token JWT token
     *
     * @return array Decoded token claims
     *
     * @throws TokenException If token invalid/expired
     */
    public function validateToken(string $token): array
    {
        try {
            return $this->jwtManager->validate($token, $this->config['issuer'], $this->config['audience']);
        } catch (\Exception $e) {
            throw new TokenException("Token validation failed: " . $e->getMessage());
        }
    }

    /**
     * Refresh an access token using a refresh token
     *
     * @param string $refreshToken Refresh token
     *
     * @return array New token response
     *
     * @throws TokenException If refresh token invalid/expired
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $claims = $this->jwtManager->validate(
                $refreshToken,
                $this->config['issuer'],
                $this->config['audience']
            );

            if ($claims['type'] !== 'refresh') {
                throw new TokenException("Not a refresh token");
            }

            $now = time();
            $accessTokenExpiry = $now + $this->config['tokenExpiry'];

            $newAccessToken = $this->jwtManager->generate([
                'client_id' => $claims['client_id'],
                'scopes' => $claims['scopes'] ?? [],
                'type' => 'access',
                'iat' => $now,
                'exp' => $accessTokenExpiry,
            ], $this->config['issuer'], $this->config['audience']);

            return [
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->config['tokenExpiry'],
            ];
        } catch (\Exception $e) {
            throw new TokenException("Refresh token validation failed: " . $e->getMessage());
        }
    }

    /**
     * Revoke a token
     *
     * @param string $token Token to revoke
     *
     * @return void
     *
     * @throws TokenException If revocation fails
     */
    public function revokeToken(string $token): void
    {
        try {
            // In simplified mode, tokens cannot be revoked (requires database)
            throw new TokenException("Database not configured for token revocation");
        } catch (TokenException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TokenException("Failed to revoke token: " . $e->getMessage());
        }
    }

    /**
     * Get the JWT Manager instance (for advanced use)
     *
     * @return JWTTokenManager
     */
    public function getJWTManager(): JWTTokenManager
    {
        return $this->jwtManager;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
