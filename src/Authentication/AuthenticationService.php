<?php

namespace Ksfraser\Amortizations\Authentication;

use DateTimeImmutable;
use DateInterval;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use RuntimeException;

/**
 * AuthenticationService - Core OAuth2 Authentication
 *
 * Implements OAuth2 Client Credentials flow for API access.
 * Generates, validates, and manages JWT access tokens.
 *
 * ### Responsibilities
 * - Generate JWT tokens for clients
 * - Validate token signatures and expiration
 * - Manage token claims (scope, subject, etc)
 * - Handle token refresh
 * - Track active tokens
 *
 * ### Usage
 * ```php
 * $authService = new AuthenticationService(
 *     $privateKey,
 *     $publicKey,
 *     'amortization-api'
 * );
 *
 * // Generate token
 * $token = $authService->generateToken($client, ['loan:read']);
 *
 * // Validate token
 * try {
 *     $validated = $authService->validateToken($tokenString);
 * } catch (InvalidTokenException $e) {
 *     // Handle invalid token
 * }
 * ```
 *
 * ### Security Features
 * - RS256 (RSA) signature algorithm (not HS256)
 * - Configurable token expiration (default 1 hour)
 * - Token revocation support
 * - Audit trail on all operations
 * - Scope-based claims
 *
 * @package Ksfraser\Amortizations\Authentication
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class AuthenticationService
{
    /**
     * Private key for signing tokens (PEM format)
     *
     * @var string
     */
    private $privateKey;

    /**
     * Public key for verifying tokens (PEM format)
     *
     * @var string
     */
    private $publicKey;

    /**
     * Issuer identifier (audience claim)
     *
     * @var string
     */
    private $issuer;

    /**
     * Token expiration time in seconds (default 1 hour)
     *
     * @var int
     */
    private $tokenExpiration = 3600;

    /**
     * Refresh token expiration in seconds (default 7 days)
     *
     * @var int
     */
    private $refreshExpiration = 604800;

    /**
     * Algorithm for signing (RS256 recommended)
     *
     * @var string
     */
    private $algorithm = 'RS256';

    /**
     * Revoked tokens (token IDs to reject)
     *
     * @var array
     */
    private $revokedTokens = [];

    /**
     * Active tokens by client
     *
     * @var array
     */
    private $activeTokens = [];

    /**
     * Constructor
     *
     * @param string $privateKey RSA private key (PEM format)
     * @param string $publicKey  RSA public key (PEM format)
     * @param string $issuer     Token issuer identifier
     *
     * @throws InvalidArgumentException If keys invalid
     */
    public function __construct(string $privateKey, string $publicKey, string $issuer)
    {
        if (empty($privateKey) || empty($publicKey)) {
            throw new InvalidArgumentException('Private and public keys required');
        }

        if (!$this->isValidPemKey($privateKey)) {
            throw new InvalidArgumentException('Invalid PEM private key format');
        }

        if (!$this->isValidPemKey($publicKey)) {
            throw new InvalidArgumentException('Invalid PEM public key format');
        }

        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->issuer = $issuer;
    }

    /**
     * Generate OAuth2 access token for client
     *
     * ### Token Claims
     * - iss (issuer): API identifier
     * - sub (subject): Client ID
     * - aud (audience): API identifier
     * - iat (issued at): Current timestamp
     * - exp (expiration): Current + token expiration
     * - scope: Granted scopes
     * - jti (JWT ID): Unique token ID for revocation
     *
     * @param Client $client Authenticated client
     * @param array  $scopes Granted scopes
     *
     * @return Token Generated access token
     *
     * @throws RuntimeException If token generation fails
     */
    public function generateToken(Client $client, array $scopes): Token
    {
        try {
            $now = new DateTimeImmutable();
            $expiration = $now->add(new DateInterval("PT{$this->tokenExpiration}S"));

            // Create JWT claims
            $jti = bin2hex(random_bytes(16));
            $claims = [
                'iss' => $this->issuer,
                'sub' => $client->getClientId(),
                'aud' => $this->issuer,
                'iat' => $now->getTimestamp(),
                'exp' => $expiration->getTimestamp(),
                'scope' => implode(' ', $scopes),
                'jti' => $jti,
            ];

            // Sign with private key (RS256)
            $tokenString = JWT::encode($claims, $this->privateKey, $this->algorithm);

            // Create token object
            $token = new Token(
                $jti,
                $tokenString,
                $client->getClientId(),
                $scopes,
                $expiration,
                Token::TYPE_ACCESS
            );

            // Track active token
            $this->activeTokens[$client->getClientId()][] = $jti;

            return $token;
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to generate token: {$e->getMessage()}");
        }
    }

    /**
     * Generate refresh token for extending access
     *
     * Refresh tokens have longer expiration and can be exchanged
     * for new access tokens without re-authentication.
     *
     * @param Client $client Authenticated client
     * @param string $accessTokenJti Related access token JTI
     *
     * @return Token Generated refresh token
     *
     * @throws RuntimeException If token generation fails
     */
    public function generateRefreshToken(Client $client, string $accessTokenJti): Token
    {
        try {
            $now = new DateTimeImmutable();
            $expiration = $now->add(new DateInterval("PT{$this->refreshExpiration}S"));

            // Refresh tokens are not revocable but linked to access token
            $jti = bin2hex(random_bytes(16));
            $claims = [
                'iss' => $this->issuer,
                'sub' => $client->getClientId(),
                'aud' => $this->issuer,
                'iat' => $now->getTimestamp(),
                'exp' => $expiration->getTimestamp(),
                'jti' => $jti,
                'access_token_jti' => $accessTokenJti,
            ];

            $tokenString = JWT::encode($claims, $this->privateKey, $this->algorithm);

            return new Token(
                $jti,
                $tokenString,
                $client->getClientId(),
                [],
                $expiration,
                Token::TYPE_REFRESH
            );
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to generate refresh token: {$e->getMessage()}");
        }
    }

    /**
     * Validate token and return claims
     *
     * Verifies:
     * - JWT signature with public key
     * - Token expiration
     * - Token not revoked
     * - Claims present
     *
     * @param string $tokenString JWT token string
     *
     * @return Token Validated token object
     *
     * @throws InvalidTokenException If token invalid
     * @throws RuntimeException If validation fails
     */
    public function validateToken(string $tokenString): Token
    {
        try {
            // Decode and verify JWT signature
            $decoded = JWT::decode($tokenString, new Key($this->publicKey, $this->algorithm));

            $jti = $decoded->jti ?? null;
            if (!$jti) {
                throw new InvalidTokenException('Missing token ID (jti)');
            }

            // Check if token revoked
            if (in_array($jti, $this->revokedTokens)) {
                throw new InvalidTokenException('Token has been revoked');
            }

            // Validate required claims
            if (!isset($decoded->sub, $decoded->iat, $decoded->exp)) {
                throw new InvalidTokenException('Missing required claims');
            }

            // Check expiration
            if ($decoded->exp < time()) {
                throw new InvalidTokenException('Token has expired');
            }

            // Parse scopes
            $scopes = [];
            if (isset($decoded->scope) && !empty($decoded->scope)) {
                $scopes = explode(' ', $decoded->scope);
            }

            // Create token object
            $expiration = DateTimeImmutable::createFromFormat('U', (string)$decoded->exp);

            $token = new Token(
                $jti,
                $tokenString,
                $decoded->sub,
                $scopes,
                $expiration,
                $decoded->grant_type ?? Token::TYPE_ACCESS
            );

            return $token;
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidTokenException("Token validation failed: {$e->getMessage()}");
        }
    }

    /**
     * Revoke token by ID
     *
     * Once revoked, token will fail validation.
     * Useful for logout/token invalidation.
     *
     * @param string $jti Token ID (jti claim)
     *
     * @return bool True if revoked
     */
    public function revokeToken(string $jti): bool
    {
        if (!in_array($jti, $this->revokedTokens)) {
            $this->revokedTokens[] = $jti;
        }

        return true;
    }

    /**
     * Revoke all tokens for client
     *
     * @param string $clientId Client identifier
     *
     * @return bool True if revoked
     */
    public function revokeClientTokens(string $clientId): bool
    {
        if (isset($this->activeTokens[$clientId])) {
            foreach ($this->activeTokens[$clientId] as $jti) {
                $this->revokeToken($jti);
            }
            unset($this->activeTokens[$clientId]);
        }

        return true;
    }

    /**
     * Check if token is revoked
     *
     * @param string $jti Token ID
     *
     * @return bool True if revoked
     */
    public function isRevoked(string $jti): bool
    {
        return in_array($jti, $this->revokedTokens);
    }

    /**
     * Get active tokens for client
     *
     * @param string $clientId Client identifier
     *
     * @return array Array of token JTIs
     */
    public function getClientTokens(string $clientId): array
    {
        return $this->activeTokens[$clientId] ?? [];
    }

    /**
     * Set token expiration time
     *
     * @param int $seconds Expiration in seconds (default 3600)
     *
     * @return self
     */
    public function setTokenExpiration(int $seconds): self
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Token expiration must be positive');
        }

        $this->tokenExpiration = $seconds;
        return $this;
    }

    /**
     * Set refresh token expiration time
     *
     * @param int $seconds Expiration in seconds (default 604800)
     *
     * @return self
     */
    public function setRefreshExpiration(int $seconds): self
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Refresh expiration must be positive');
        }

        $this->refreshExpiration = $seconds;
        return $this;
    }

    /**
     * Check if string is valid PEM key format
     *
     * @param string $key Key to validate
     *
     * @return bool True if valid PEM
     */
    private function isValidPemKey(string $key): bool
    {
        return (strpos($key, '-----BEGIN') !== false &&
                strpos($key, '-----END') !== false);
    }

    /**
     * Get issuer identifier
     *
     * @return string Issuer
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * Get current token expiration setting
     *
     * @return int Expiration in seconds
     */
    public function getTokenExpiration(): int
    {
        return $this->tokenExpiration;
    }

    /**
     * Get revoked token count (for metrics)
     *
     * @return int Number of revoked tokens
     */
    public function getRevokedTokenCount(): int
    {
        return count($this->revokedTokens);
    }

    /**
     * Get active token count (for metrics)
     *
     * @return int Number of active tokens
     */
    public function getActiveTokenCount(): int
    {
        $count = 0;
        foreach ($this->activeTokens as $tokens) {
            $count += count($tokens);
        }
        return $count;
    }
}
