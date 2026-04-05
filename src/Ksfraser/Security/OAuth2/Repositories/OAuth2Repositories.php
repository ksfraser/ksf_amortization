<?php
namespace Ksfraser\Security\OAuth2\Repositories;

use PDO;
use Ksfraser\Security\Exceptions\TokenException;

/**
 * OAuth2 User Identity Repository
 * 
 * Manages persistence of user identity information for OpenID Connect.
 * Stores user claims (email, name, phone, etc.) returned by UserInfo endpoint.
 * 
 * @package   Ksfraser\Security\OAuth2\Repositories
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class OAuth2UserIdentityRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create user identity
     */
    public function create(string $userId, array $claims): void
    {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO oauth2_user_identities 
                (user_id, email, email_verified, name, given_name, family_name, phone_number, phone_number_verified, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ');

            $stmt->execute([
                $userId,
                $claims['email'] ?? null,
                $claims['email_verified'] ?? false,
                $claims['name'] ?? null,
                $claims['given_name'] ?? null,
                $claims['family_name'] ?? null,
                $claims['phone_number'] ?? null,
                $claims['phone_number_verified'] ?? false
            ]);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to create user identity: " . $e->getMessage());
        }
    }

    /**
     * Get user identity by ID
     */
    public function getIdentity(string $userId): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM oauth2_user_identities WHERE user_id = ?');
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            throw new TokenException("Failed to get user identity: " . $e->getMessage());
        }
    }

    /**
     * Update user identity
     */
    public function update(string $userId, array $claims): void
    {
        try {
            $updates = [];
            $params = [];

            foreach ($claims as $key => $value) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }

            $params[] = $userId;
            $stmt = $this->db->prepare('UPDATE oauth2_user_identities SET ' . implode(', ', $updates) . ' WHERE user_id = ?');
            $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to update user identity: " . $e->getMessage());
        }
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM oauth2_user_identities WHERE email = ?');
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            throw new TokenException("Failed to find user by email: " . $e->getMessage());
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail(string $userId): void
    {
        try {
            $stmt = $this->db->prepare('UPDATE oauth2_user_identities SET email_verified = 1 WHERE user_id = ?');
            $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to verify email: " . $e->getMessage());
        }
    }

    /**
     * Verify phone number
     */
    public function verifyPhoneNumber(string $userId): void
    {
        try {
            $stmt = $this->db->prepare('UPDATE oauth2_user_identities SET phone_number_verified = 1 WHERE user_id = ?');
            $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to verify phone number: " . $e->getMessage());
        }
    }
}

/**
 * OAuth2 Token Repository
 * 
 * Tracks issued tokens for revocation and audit trail.
 * 
 * @package   Ksfraser\Security\OAuth2\Repositories
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class OAuth2TokenRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Store token
     */
    public function create(string $tokenHash, string $clientId, string $type, int $expirationSeconds): void
    {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $expirationSeconds);
            $stmt = $this->db->prepare('
                INSERT INTO oauth2_tokens (token_hash, client_id, token_type, expires_at, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ');
            $stmt->execute([$tokenHash, $clientId, $type, $expiresAt]);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to create token record: " . $e->getMessage());
        }
    }

    /**
     * Check if token exists
     */
    public function exists(string $tokenHash): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT 1 FROM oauth2_tokens WHERE token_hash = ?');
            $stmt->execute([$tokenHash]);
            return $stmt->fetch() !== false;
        } catch (\PDOException $e) {
            throw new TokenException("Failed to check token: " . $e->getMessage());
        }
    }

    /**
     * Check if token is revoked
     */
    public function isRevoked(string $tokenHash): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT revoked FROM oauth2_tokens WHERE token_hash = ?');
            $stmt->execute([$tokenHash]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (bool)$result['revoked'] : false;
        } catch (\PDOException $e) {
            throw new TokenException("Failed to get revocation status: " . $e->getMessage());
        }
    }

    /**
     * Revoke token
     */
    public function revoke(string $tokenHash): void
    {
        try {
            $stmt = $this->db->prepare('UPDATE oauth2_tokens SET revoked = 1 WHERE token_hash = ?');
            $stmt->execute([$tokenHash]);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to revoke token: " . $e->getMessage());
        }
    }

    /**
     * Delete expired tokens
     */
    public function deleteExpired(): int
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM oauth2_tokens WHERE expires_at < NOW()');
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new TokenException("Failed to delete expired tokens: " . $e->getMessage());
        }
    }
}

/**
 * OAuth2 User Consent Repository
 * 
 * Tracks user consent to share specific scopes with applications.
 * Prevents repeated consent screens for same app + scope combination.
 * 
 * @package   Ksfraser\Security\OAuth2\Repositories
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class OAuth2UserConsentRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Grant consent for scopes
     */
    public function grant(string $userId, string $clientId, array $scopes): void
    {
        try {
            // First revoke any existing consent
            $this->revoke($userId, $clientId);

            // Grant new consent
            $scopesJson = json_encode($scopes);
            $stmt = $this->db->prepare('
                INSERT INTO oauth2_user_consents (user_id, client_id, scopes, consented_at)
                VALUES (?, ?, ?, NOW())
            ');
            $stmt->execute([$userId, $clientId, $scopesJson]);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to grant consent: " . $e->getMessage());
        }
    }

    /**
     * Check if user has consented to required scopes
     */
    public function hasConsent(string $userId, string $clientId, array $requiredScopes): bool
    {
        try {
            $stmt = $this->db->prepare('
                SELECT scopes FROM oauth2_user_consents 
                WHERE user_id = ? AND client_id = ? AND revoked_at IS NULL
                ORDER BY consented_at DESC
                LIMIT 1
            ');
            $stmt->execute([$userId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return false;
            }

            $grantedScopes = json_decode($result['scopes'], true);
            foreach ($requiredScopes as $scope) {
                if (!in_array($scope, $grantedScopes)) {
                    return false;
                }
            }

            return true;
        } catch (\PDOException $e) {
            throw new TokenException("Failed to check consent: " . $e->getMessage());
        }
    }

    /**
     * Revoke consent
     */
    public function revoke(string $userId, string $clientId): void
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE oauth2_user_consents 
                SET revoked_at = NOW()
                WHERE user_id = ? AND client_id = ? AND revoked_at IS NULL
            ');
            $stmt->execute([$userId, $clientId]);
        } catch (\PDOException $e) {
            throw new TokenException("Failed to revoke consent: " . $e->getMessage());
        }
    }
}
