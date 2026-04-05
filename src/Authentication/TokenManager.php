<?php

namespace Ksfraser\Amortizations\Authentication;

use DateTimeImmutable;
use RuntimeException;
use InvalidArgumentException;

/**
 * TokenManager - Token Lifecycle Management
 *
 * Manages token persistence, lifecycle, and operations including:
 * - Token storage and retrieval
 * - Token refresh workflow
 * - Token revocation
 * - Token statistics and auditing
 * - Cleanup of expired tokens
 *
 * ### Token Lifecycle
 *
 * ```
 * 1. Generate access token + refresh token
 *    ├─ Access token: 1 hour expiration, used for API requests
 *    └─ Refresh token: 7 days expiration, used to get new access tokens
 *
 * 2. Client uses access token for API requests
 *    ├─ If valid: API request succeeds
 *    └─ If expired: Client uses refresh token to get new access token
 *
 * 3. Exchange refresh token for new access token
 *    ├─ Verify refresh token signature and expiration
 *    ├─ Check refresh token not revoked
 *    └─ Generate new access token (link to old refresh token chain)
 *
 * 4. Token revocation (manual or on logout)
 *    └─ Add token JTI to revocation list (persisted)
 *
 * 5. Token cleanup (periodic)
 *    └─ Remove expired tokens from storage
 * ```
 *
 * ### Usage
 * ```php
 * $tokenMgr = new TokenManager($authService, $storage);
 *
 * // Generate tokens on login
 * $result = $tokenMgr->generateTokenPair($client, ['loan:read', 'schedule:read']);
 * echo $result['access_token'];  // JWT access token
 * echo $result['refresh_token']; // JWT refresh token
 * echo $result['expires_in'];    // 3600 (seconds)
 *
 * // Refresh access token
 * $newAccess = $tokenMgr->refreshAccessToken($client, $refreshTokenString);
 *
 * // Revoke token
 * $tokenMgr->revokeToken($tokenJti);
 *
 * // Get token stats
 * $stats = $tokenMgr->getClientTokenStats($clientId);
 * echo $stats['active_tokens']; // 5
 * ```
 *
 * @package Ksfraser\Amortizations\Authentication
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class TokenManager
{
    /**
     * Authentication service for token generation/validation
     *
     * @var AuthenticationService
     */
    private $authService;

    /**
     * Storage backend for persistent token data
     *
     * @var TokenStorageInterface
     */
    private $storage;

    /**
     * In-memory token cache
     *
     * @var array
     */
    private $cache = [];

    /**
     * Revoked token tracking (for current request)
     *
     * @var array
     */
    private $revokedTokens = [];

    /**
     * Constructor
     *
     * @param AuthenticationService $authService Authentication service
     * @param TokenStorageInterface $storage     Token storage backend
     *
     * @throws InvalidArgumentException If dependencies invalid
     */
    public function __construct(
        AuthenticationService $authService,
        TokenStorageInterface $storage
    ) {
        if (!$authService) {
            throw new InvalidArgumentException('AuthenticationService required');
        }

        if (!$storage) {
            throw new InvalidArgumentException('TokenStorage required');
        }

        $this->authService = $authService;
        $this->storage = $storage;
    }

    /**
     * Generate access and refresh token pair
     *
     * Typical response for OAuth2 token endpoint:
     *
     * ```json
     * {
     *     "access_token": "eyJ...",
     *     "refresh_token": "eyJ...",
     *     "token_type": "Bearer",
     *     "expires_in": 3600,
     *     "scope": "loan:read schedule:read"
     * }
     * ```
     *
     * @param Client $client  Authenticated client
     * @param array  $scopes  Granted scopes
     *
     * @return array Token response data
     *
     * @throws RuntimeException If token generation fails
     */
    public function generateTokenPair(Client $client, array $scopes): array
    {
        try {
            // Generate access token
            $accessToken = $this->authService->generateToken($client, $scopes);

            // Generate refresh token
            $refreshToken = $this->authService->generateRefreshToken(
                $client,
                $accessToken->getJti()
            );

            // Persist tokens
            $this->storage->saveToken($accessToken);
            $this->storage->saveToken($refreshToken);

            // Cache tokens
            $this->cache[$accessToken->getJti()] = $accessToken;
            $this->cache[$refreshToken->getJti()] = $refreshToken;

            // Return OAuth2 response format
            return [
                'access_token' => $accessToken->getTokenString(),
                'refresh_token' => $refreshToken->getTokenString(),
                'token_type' => 'Bearer',
                'expires_in' => $this->getExpiresIn($accessToken),
                'scope' => implode(' ', $scopes),
            ];
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to generate token pair: {$e->getMessage()}");
        }
    }

    /**
     * Exchange refresh token for new access token
     *
     * Used when access token expires. Validates refresh token and
     * generates new access token with same scopes.
     *
     * @param Client $client              Authenticated client
     * @param string $refreshTokenString  Refresh token JWT
     *
     * @return array New token response
     *
     * @throws InvalidTokenException If refresh token invalid/expired
     * @throws RuntimeException If token generation fails
     */
    public function refreshAccessToken(
        Client $client,
        string $refreshTokenString
    ): array {
        try {
            // Validate refresh token
            $refreshToken = $this->authService->validateToken($refreshTokenString);

            // Verify it's a refresh token
            if ($refreshToken->getType() !== Token::TYPE_REFRESH) {
                throw new InvalidTokenException('Token is not a refresh token');
            }

            // Verify client matches
            if ($refreshToken->getClientId() !== $client->getClientId()) {
                throw new InvalidTokenException('Token does not belong to client');
            }

            // Generate new access token with same scopes
            $newAccessToken = $this->authService->generateToken(
                $client,
                $refreshToken->getScopes()
            );

            // Persist new access token
            $this->storage->saveToken($newAccessToken);
            $this->cache[$newAccessToken->getJti()] = $newAccessToken;

            // Return OAuth2 response format
            return [
                'access_token' => $newAccessToken->getTokenString(),
                'token_type' => 'Bearer',
                'expires_in' => $this->getExpiresIn($newAccessToken),
            ];
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to refresh token: {$e->getMessage()}");
        }
    }

    /**
     * Revoke token by JTI
     *
     * Prevents token from being used in future requests.
     * Persists revocation to storage.
     *
     * @param string $jti          JWT ID of token to revoke
     * @param string $reason       Reason for revocation
     * @param string $revokedByClientId Client that revoked it (for audit)
     *
     * @return void
     *
     * @throws RuntimeException If revocation fails
     */
    public function revokeToken(
        string $jti,
        string $reason = '',
        string $revokedByClientId = ''
    ): void {
        try {
            $this->storage->revokeToken($jti, $reason, $revokedByClientId);
            $this->revokedTokens[$jti] = true;

            // Remove from cache
            unset($this->cache[$jti]);
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to revoke token: {$e->getMessage()}");
        }
    }

    /**
     * Check if token is revoked
     *
     * Checks both in-memory revocation list and persistent storage.
     *
     * @param string $jti JWT ID
     *
     * @return bool
     */
    public function isTokenRevoked(string $jti): bool
    {
        if (isset($this->revokedTokens[$jti])) {
            return true;
        }

        return $this->storage->isTokenRevoked($jti);
    }

    /**
     * Revoke all tokens for a client
     *
     * Used for logout, client deactivation, etc.
     *
     * @param string $clientId     Client identifier
     * @param string $reason       Reason for revocation
     *
     * @return int Number of tokens revoked
     *
     * @throws RuntimeException If revocation fails
     */
    public function revokeClientTokens(string $clientId, string $reason = ''): int
    {
        try {
            $count = $this->storage->revokeClientTokens($clientId, $reason);

            // Clear cache for client
            foreach ($this->cache as $jti => $token) {
                if ($token->getClientId() === $clientId) {
                    unset($this->cache[$jti]);
                }
            }

            return $count;
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to revoke client tokens: {$e->getMessage()}");
        }
    }

    /**
     * Get token statistics for a client
     *
     * Returns counts of active, expired, and revoked tokens.
     *
     * @param string $clientId Client identifier
     *
     * @return array Statistics
     */
    public function getClientTokenStats(string $clientId): array
    {
        return $this->storage->getClientTokenStats($clientId);
    }

    /**
     * Cleanup expired tokens
     *
     * Should be run periodically (e.g., daily) to remove expired tokens
     * from storage and reduce database size.
     *
     * @return int Number of tokens removed
     *
     * @throws RuntimeException If cleanup fails
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            return $this->storage->deleteExpiredTokens();
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to cleanup expired tokens: {$e->getMessage()}");
        }
    }

    /**
     * Get expires_in value for token response (in seconds)
     *
     * @param Token $token Token object
     *
     * @return int Seconds until expiration
     */
    private function getExpiresIn(Token $token): int
    {
        $now = new DateTimeImmutable();
        $diff = $token->getExpiresAt()->getTimestamp() - $now->getTimestamp();
        return max(0, $diff);
    }
}

/**
 * TokenStorageInterface - Contract for token persistence
 *
 * Implementations can use database, Redis, or other storage backends.
 *
 * @package Ksfraser\Amortizations\Authentication
 */
interface TokenStorageInterface
{
    /**
     * Save token to storage
     *
     * @param Token $token Token to save
     *
     * @return void
     */
    public function saveToken(Token $token): void;

    /**
     * Check if token is revoked
     *
     * @param string $jti JWT ID
     *
     * @return bool
     */
    public function isTokenRevoked(string $jti): bool;

    /**
     * Revoke token by JTI
     *
     * @param string $jti                JWT ID
     * @param string $reason             Reason for revocation
     * @param string $revokedByClientId  Client that revoked it
     *
     * @return void
     */
    public function revokeToken(
        string $jti,
        string $reason = '',
        string $revokedByClientId = ''
    ): void;

    /**
     * Revoke all tokens for a client
     *
     * @param string $clientId Client ID
     * @param string $reason   Reason for revocation
     *
     * @return int Number of tokens revoked
     */
    public function revokeClientTokens(string $clientId, string $reason = ''): int;

    /**
     * Get token statistics for a client
     *
     * @param string $clientId Client ID
     *
     * @return array Statistics
     */
    public function getClientTokenStats(string $clientId): array;

    /**
     * Delete expired tokens from storage
     *
     * @return int Number of tokens deleted
     */
    public function deleteExpiredTokens(): int;
}
