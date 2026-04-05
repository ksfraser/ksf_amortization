<?php
namespace Ksfraser\Security\OAuth2\Repositories;

use PDO;
use Ksfraser\Security\Exceptions\TokenException;

/**
 * Authorization Code Repository
 * 
 * Manages persistence of authorization codes for OAuth2 Authorization Code Flow.
 * Handles code generation, validation, and expiration management.
 * 
 * Per RFC 6749 §4.1.2:
 * - Authorization codes MUST expire shortly after issuance (typically 10 minutes)
 * - Codes are single-use only
 * - Codes are bound to client and redirect_uri for security
 * 
 * Per RFC 7636 (PKCE):
 * - Stores code_challenge and code_challenge_method
 * - Validates code_verifier on token exchange
 * 
 * @package   Ksfraser\Security\OAuth2\Repositories
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class AuthorizationCodeRepository
{
    /**
     * @var PDO Database connection
     */
    private $db;

    /**
     * @var int Authorization code expiration time in seconds (default: 10 minutes)
     */
    private $expirationTime = 600;

    /**
     * AuthorizationCodeRepository constructor
     *
     * @param PDO $db Database connection
     * @param int $expirationTime Code expiration time in seconds
     */
    public function __construct(PDO $db, int $expirationTime = 600)
    {
        $this->db = $db;
        $this->expirationTime = $expirationTime;
    }

    /**
     * Store authorization code
     * 
     * Generates and stores a new authorization code with optional PKCE parameters.
     * Code is valid for $expirationTime seconds.
     *
     * @param string $clientId OAuth2 client ID
     * @param string $redirectUri Exact redirect URI from authorization request
     * @param array $scopes Requested scopes
     * @param string $state State parameter for CSRF protection
     * @param string|null $userId Resource owner user ID
     * @param string|null $codeChallenge PKCE code challenge (for PKCE flow)
     * @param string|null $codeChallengeMethod PKCE method: S256 or plain
     *
     * @return string Generated authorization code
     *
     * @throws TokenException On database error
     */
    public function create(
        string $clientId,
        string $redirectUri,
        array $scopes,
        string $state,
        ?string $userId = null,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = 'S256'
    ): string {
        try {
            // Generate random authorization code (43 characters, URL-safe)
            $code = $this->generateCode();

            $expiresAt = date('Y-m-d H:i:s', time() + $this->expirationTime);
            $scopesJson = json_encode($scopes);

            $stmt = $this->db->prepare('
                INSERT INTO oauth2_authorization_codes 
                (code, client_id, user_id, redirect_uri, scopes, state, 
                 code_challenge, code_challenge_method, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                $code,
                $clientId,
                $userId,
                $redirectUri,
                $scopesJson,
                $state,
                $codeChallenge,
                $codeChallengeMethod,
                $expiresAt
            ]);

            return $code;
        } catch (\PDOException $e) {
            throw new TokenException("Failed to store authorization code: " . $e->getMessage());
        }
    }

    /**
     * Get authorization code details
     * 
     * Retrieves code details if valid (not expired, not revoked).
     * Returns null if code doesn't exist, expired, or already used.
     *
     * @param string $code Authorization code
     *
     * @return array|null Code details with client_id, user_id, scopes, etc. or null
     *
     * @throws TokenException On database error
     */
    public function getCode(string $code): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM oauth2_authorization_codes 
                WHERE code = ?
                LIMIT 1
            ');

            $stmt->execute([$code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return null;
            }

            // Check expiration
            if (strtotime($result['expires_at']) < time()) {
                return null;
            }

            // Check if already used (code is single-use)
            if ($result['used_at'] !== null) {
                return null;
            }

            // Parse scopes JSON
            $result['scopes'] = is_string($result['scopes']) 
                ? json_decode($result['scopes'], true) 
                : $result['scopes'];

            return $result;
        } catch (\PDOException $e) {
            throw new TokenException("Failed to retrieve authorization code: " . $e->getMessage());
        }
    }

    /**
     * Validate and consume authorization code
     * 
     * Validates that code:
     * - Exists and hasn't expired
     * - Matches client_id and redirect_uri (RFC 6749 requirement)
     * - Is not already used
     * - Has matching PKCE verifier (if needed)
     * 
     * Marks code as used after validation.
     *
     * @param string $code Authorization code
     * @param string $clientId Expected client ID
     * @param string $redirectUri Expected redirect URI
     * @param string|null $codeVerifier PKCE code verifier for validation
     *
     * @return array Code details (scopes, user_id, state, etc.)
     *
     * @throws TokenException If code is invalid, expired, or compromised
     */
    public function validate(
        string $code,
        string $clientId,
        string $redirectUri,
        ?string $codeVerifier = null
    ): array {
        try {
            $codeData = $this->getCode($code);

            if (!$codeData) {
                throw new TokenException("Invalid or expired authorization code");
            }

            // Validate client_id matches (RFC 6749 §4.1.3)
            if ($codeData['client_id'] !== $clientId) {
                throw new TokenException("Authorization code client_id mismatch");
            }

            // Validate redirect_uri matches exactly (RFC 6749 §3.1.2.1)
            if ($codeData['redirect_uri'] !== $redirectUri) {
                throw new TokenException("Authorization code redirect_uri mismatch");
            }

            // Validate PKCE code_verifier if code_challenge was set
            if ($codeData['code_challenge']) {
                if (!$codeVerifier) {
                    throw new TokenException("PKCE code_verifier required but not provided");
                }

                $this->validateCodeVerifier(
                    $codeVerifier,
                    $codeData['code_challenge'],
                    $codeData['code_challenge_method'] ?? 'S256'
                );
            }

            // Mark code as used (single-use)
            $this->markAsUsed($code);

            return $codeData;
        } catch (TokenException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TokenException("Authorization code validation failed: " . $e->getMessage());
        }
    }

    /**
     * Check if code is valid and not expired
     *
     * @param string $code Authorization code
     *
     * @return bool True if code exists and is not expired
     */
    public function isValid(string $code): bool
    {
        return $this->getCode($code) !== null;
    }

    /**
     * Mark code as used
     * 
     * Sets used_at timestamp to prevent code reuse.
     * (Single-use per RFC 6749)
     *
     * @param string $code Authorization code
     *
     * @return void
     *
     * @throws TokenException On database error
     */
    private function markAsUsed(string $code): void
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE oauth2_authorization_codes 
                SET used_at = NOW()
                WHERE code = ?
            ');

            $stmt->execute([$code]);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to mark authorization code as used: " . $e->getMessage());
        }
    }

    /**
     * Revoke authorization code
     * 
     * Explicitly marks code as used to prevent future use.
     *
     * @param string $code Authorization code
     *
     * @return void
     *
     * @throws TokenException On database error
     */
    public function revoke(string $code): void
    {
        $this->markAsUsed($code);
    }

    /**
     * Delete expired codes
     * 
     * Purges authorization codes that have expired.
     * Should be run periodically for cleanup.
     *
     * @return int Number of codes deleted
     *
     * @throws TokenException On database error
     */
    public function deleteExpired(): int
    {
        try {
            $stmt = $this->db->prepare('
                DELETE FROM oauth2_authorization_codes 
                WHERE expires_at < NOW()
            ');

            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new TokenException("Failed to delete expired codes: " . $e->getMessage());
        }
    }

    /**
     * Revoke all codes for a client
     * 
     * Used when client credentials are compromised.
     *
     * @param string $clientId Client ID
     *
     * @return int Number of codes revoked
     *
     * @throws TokenException On database error
     */
    public function revokeAllForClient(string $clientId): int
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE oauth2_authorization_codes 
                SET used_at = NOW()
                WHERE client_id = ? AND used_at IS NULL
            ');

            $stmt->execute([$clientId]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new TokenException("Failed to revoke codes for client: " . $e->getMessage());
        }
    }

    /**
     * Validate PKCE code verifier
     * 
     * Validates code_verifier against code_challenge using specified method.
     * 
     * - S256: SHA256(code_verifier) = code_challenge
     * - plain: code_verifier = code_challenge
     *
     * @param string $codeVerifier PKCE code verifier from client
     * @param string $codeChallenge PKCE code challenge from authorization request
     * @param string $method Challenge method: S256 or plain
     *
     * @return void
     *
     * @throws TokenException If verifier doesn't match challenge
     */
    private function validateCodeVerifier(
        string $codeVerifier,
        string $codeChallenge,
        string $method = 'S256'
    ): void {
        $expectedChallenge = null;

        if ($method === 'S256') {
            // SHA256 method: code_challenge = BASE64URL(SHA256(code_verifier))
            $expectedChallenge = rtrim(
                strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
                '='
            );
        } elseif ($method === 'plain') {
            // Plain method: code_challenge = code_verifier
            $expectedChallenge = $codeVerifier;
        } else {
            throw new TokenException("Unsupported PKCE challenge method: {$method}");
        }

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals($expectedChallenge, $codeChallenge)) {
            throw new TokenException("PKCE code verifier validation failed");
        }
    }

    /**
     * Generate random authorization code
     * 
     * Generates a cryptographically secure random code (43 characters, URL-safe base64).
     * Matches typical OAuth2 authorization code format.
     *
     * @return string Generated authorization code
     */
    private function generateCode(): string
    {
        return rtrim(
            strtr(base64_encode(random_bytes(32)), '+/', '-_'),
            '='
        );
    }

    /**
     * Set authorization code expiration time
     *
     * @param int $seconds Expiration time in seconds
     *
     * @return void
     */
    public function setExpirationTime(int $seconds): void
    {
        $this->expirationTime = $seconds;
    }

    /**
     * Get authorization code expiration time
     *
     * @return int Expiration time in seconds
     */
    public function getExpirationTime(): int
    {
        return $this->expirationTime;
    }
}
