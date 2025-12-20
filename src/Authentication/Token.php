<?php

namespace Ksfraser\Amortizations\Authentication;

use DateTimeImmutable;

/**
 * Token - OAuth2 Token Model
 *
 * Represents an OAuth2 token (access or refresh).
 * Encapsulates token data and provides access to claims.
 *
 * ### Token Types
 * - ACCESS: Short-lived token for API requests
 * - REFRESH: Long-lived token for obtaining new access tokens
 *
 * ### Usage
 * ```php
 * $token = new Token(
 *     'jti123',
 *     'eyJ...',
 *     'client_id',
 *     ['loan:read', 'loan:write'],
 *     new DateTimeImmutable('+1 hour'),
 *     Token::TYPE_ACCESS
 * );
 *
 * echo $token->getClientId();     // 'client_id'
 * echo $token->getTokenString();  // 'eyJ...'
 * echo $token->hasScope('loan:read'); // true
 * ```
 *
 * @package Ksfraser\Amortizations\Authentication
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class Token
{
    /**
     * Access token type (short-lived)
     */
    public const TYPE_ACCESS = 'access';

    /**
     * Refresh token type (long-lived)
     */
    public const TYPE_REFRESH = 'refresh';

    /**
     * Unique token identifier (jti claim)
     *
     * @var string
     */
    private $jti;

    /**
     * Token string (JWT format)
     *
     * @var string
     */
    private $tokenString;

    /**
     * Client ID (subject)
     *
     * @var string
     */
    private $clientId;

    /**
     * Granted scopes
     *
     * @var array
     */
    private $scopes;

    /**
     * Token expiration time
     *
     * @var DateTimeImmutable
     */
    private $expiresAt;

    /**
     * Token type (access or refresh)
     *
     * @var string
     */
    private $type;

    /**
     * Creation time
     *
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * Constructor
     *
     * @param string                 $jti         Token ID
     * @param string                 $tokenString JWT string
     * @param string                 $clientId    Client identifier
     * @param array                  $scopes      Granted scopes
     * @param DateTimeImmutable      $expiresAt   Expiration time
     * @param string                 $type        Token type
     */
    public function __construct(
        string $jti,
        string $tokenString,
        string $clientId,
        array $scopes,
        DateTimeImmutable $expiresAt,
        string $type = self::TYPE_ACCESS
    ) {
        $this->jti = $jti;
        $this->tokenString = $tokenString;
        $this->clientId = $clientId;
        $this->scopes = $scopes;
        $this->expiresAt = $expiresAt;
        $this->type = $type;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Get token identifier
     *
     * @return string Token JTI
     */
    public function getJti(): string
    {
        return $this->jti;
    }

    /**
     * Get token string (JWT)
     *
     * @return string JWT string
     */
    public function getTokenString(): string
    {
        return $this->tokenString;
    }

    /**
     * Get client identifier
     *
     * @return string Client ID
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Get all granted scopes
     *
     * @return array Scope array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Check if token has scope
     *
     * @param string $scope Scope to check
     *
     * @return bool True if scope granted
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes);
    }

    /**
     * Get expiration time
     *
     * @return DateTimeImmutable Expiration datetime
     */
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Check if token expired
     *
     * @return bool True if expired
     */
    public function isExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < time();
    }

    /**
     * Get seconds until expiration
     *
     * @return int Seconds remaining (negative if expired)
     */
    public function getSecondsUntilExpiration(): int
    {
        return $this->expiresAt->getTimestamp() - time();
    }

    /**
     * Get token type
     *
     * @return string Token type (access or refresh)
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Check if access token
     *
     * @return bool True if access token
     */
    public function isAccessToken(): bool
    {
        return $this->type === self::TYPE_ACCESS;
    }

    /**
     * Check if refresh token
     *
     * @return bool True if refresh token
     */
    public function isRefreshToken(): bool
    {
        return $this->type === self::TYPE_REFRESH;
    }

    /**
     * Get creation time
     *
     * @return DateTimeImmutable Creation datetime
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get age in seconds
     *
     * @return int Age in seconds
     */
    public function getAge(): int
    {
        return time() - $this->createdAt->getTimestamp();
    }

    /**
     * Convert to array (for responses)
     *
     * @return array Token data
     */
    public function toArray(): array
    {
        return [
            'jti' => $this->jti,
            'access_token' => $this->tokenString,
            'token_type' => 'Bearer',
            'expires_in' => $this->getSecondsUntilExpiration(),
            'scope' => implode(' ', $this->scopes),
            'type' => $this->type,
        ];
    }
}
