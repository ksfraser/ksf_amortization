<?php

namespace Ksfraser\Amortizations\Authentication\Storage;

use Ksfraser\Amortizations\Authentication\TokenStorageInterface;
use Ksfraser\Amortizations\Authentication\Token;
use DateTimeImmutable;

/**
 * InMemoryTokenStorage - In-memory token storage implementation
 *
 * Provides fast token storage for testing and single-process scenarios.
 * NOT suitable for multi-process/cluster deployments.
 *
 * Use DatabaseTokenStorage for production environments.
 *
 * @package Ksfraser\Amortizations\Authentication\Storage
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class InMemoryTokenStorage implements TokenStorageInterface
{
    /**
     * Stored tokens by JTI
     *
     * @var array
     */
    private $tokens = [];

    /**
     * Revoked token JTIs
     *
     * @var array
     */
    private $revokedTokens = [];

    /**
     * Token revocation audit trail
     *
     * @var array
     */
    private $revocationLog = [];

    /**
     * Save token to storage
     *
     * @param Token $token Token to save
     *
     * @return void
     */
    public function saveToken(Token $token): void
    {
        $this->tokens[$token->getJti()] = [
            'jti' => $token->getJti(),
            'client_id' => $token->getClientId(),
            'token_type' => $token->getType(),
            'scopes' => $token->getScopes(),
            'expires_at' => $token->getExpiresAt()->getTimestamp(),
            'created_at' => time(),
            'token_string' => $token->getTokenString(),
        ];
    }

    /**
     * Check if token is revoked
     *
     * @param string $jti JWT ID
     *
     * @return bool
     */
    public function isTokenRevoked(string $jti): bool
    {
        return isset($this->revokedTokens[$jti]);
    }

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
    ): void {
        $this->revokedTokens[$jti] = true;

        $this->revocationLog[] = [
            'jti' => $jti,
            'reason' => $reason,
            'revoked_by' => $revokedByClientId,
            'revoked_at' => time(),
        ];
    }

    /**
     * Revoke all tokens for a client
     *
     * @param string $clientId Client ID
     * @param string $reason   Reason for revocation
     *
     * @return int Number of tokens revoked
     */
    public function revokeClientTokens(string $clientId, string $reason = ''): int
    {
        $count = 0;

        foreach ($this->tokens as $jti => $data) {
            if ($data['client_id'] === $clientId && !isset($this->revokedTokens[$jti])) {
                $this->revokeToken($jti, $reason);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get token statistics for a client
     *
     * @param string $clientId Client ID
     *
     * @return array Statistics
     */
    public function getClientTokenStats(string $clientId): array
    {
        $stats = [
            'active_tokens' => 0,
            'expired_tokens' => 0,
            'revoked_tokens' => 0,
            'total_tokens' => 0,
        ];

        $now = time();

        foreach ($this->tokens as $jti => $data) {
            if ($data['client_id'] === $clientId) {
                $stats['total_tokens']++;

                if (isset($this->revokedTokens[$jti])) {
                    $stats['revoked_tokens']++;
                } elseif ($data['expires_at'] < $now) {
                    $stats['expired_tokens']++;
                } else {
                    $stats['active_tokens']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Delete expired tokens from storage
     *
     * @return int Number of tokens deleted
     */
    public function deleteExpiredTokens(): int
    {
        $count = 0;
        $now = time();

        foreach ($this->tokens as $jti => $data) {
            if ($data['expires_at'] < $now) {
                unset($this->tokens[$jti]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get all tokens (for testing/debugging)
     *
     * @return array All stored tokens
     */
    public function getAllTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Get revocation log (for auditing)
     *
     * @return array Revocation history
     */
    public function getRevocationLog(): array
    {
        return $this->revocationLog;
    }

    /**
     * Clear all data (for testing)
     *
     * @return void
     */
    public function clear(): void
    {
        $this->tokens = [];
        $this->revokedTokens = [];
        $this->revocationLog = [];
    }
}
