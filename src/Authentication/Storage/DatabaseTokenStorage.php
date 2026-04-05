<?php

namespace Ksfraser\Amortizations\Authentication\Storage;

use Ksfraser\Amortizations\Authentication\TokenStorageInterface;
use Ksfraser\Amortizations\Authentication\Token;
use PDO;
use PDOException;
use RuntimeException;
use DateTimeImmutable;

/**
 * DatabaseTokenStorage - Database-backed token storage implementation
 *
 * Provides persistent token storage using PDO with support for:
 * - MySQL/MariaDB
 * - PostgreSQL
 * - SQLite
 *
 * Suitable for production multi-process deployments.
 *
 * ### Schema Required
 *
 * ```sql
 * CREATE TABLE oauth_tokens (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     jti VARCHAR(64) UNIQUE NOT NULL,
 *     client_id VARCHAR(255) NOT NULL,
 *     token_type ENUM('access', 'refresh') DEFAULT 'access',
 *     scopes JSON,
 *     expires_at BIGINT NOT NULL,
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     revoked_at TIMESTAMP NULL,
 *     revocation_reason VARCHAR(255),
 *     revoked_by VARCHAR(255),
 *     INDEX idx_client_id (client_id),
 *     INDEX idx_expires_at (expires_at),
 *     INDEX idx_revoked_at (revoked_at)
 * );
 *
 * CREATE TABLE token_revocations (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     jti VARCHAR(64) UNIQUE NOT NULL,
 *     client_id VARCHAR(255) NOT NULL,
 *     reason VARCHAR(255),
 *     revoked_by VARCHAR(255),
 *     revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     INDEX idx_client_id (client_id),
 *     INDEX idx_jti (jti)
 * );
 * ```
 *
 * @package Ksfraser\Amortizations\Authentication\Storage
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class DatabaseTokenStorage implements TokenStorageInterface
{
    /**
     * PDO database connection
     *
     * @var PDO
     */
    private $pdo;

    /**
     * Database table for tokens
     *
     * @var string
     */
    private $tokenTable = 'oauth_tokens';

    /**
     * Database table for revocations
     *
     * @var string
     */
    private $revocationTable = 'token_revocations';

    /**
     * Constructor
     *
     * @param PDO    $pdo        PDO database connection
     * @param string $tokenTable Token table name (optional)
     * @param string $revocationTable Revocation audit table (optional)
     *
     * @throws RuntimeException If connection test fails
     */
    public function __construct(
        PDO $pdo,
        string $tokenTable = 'oauth_tokens',
        string $revocationTable = 'token_revocations'
    ) {
        $this->pdo = $pdo;
        $this->tokenTable = $tokenTable;
        $this->revocationTable = $revocationTable;

        // Verify connection
        try {
            $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: {$e->getMessage()}");
        }
    }

    /**
     * Save token to database
     *
     * @param Token $token Token to save
     *
     * @return void
     *
     * @throws RuntimeException If save fails
     */
    public function saveToken(Token $token): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tokenTable} 
                (jti, client_id, token_type, scopes, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at)
            ");

            $stmt->execute([
                $token->getJti(),
                $token->getClientId(),
                $token->getType(),
                json_encode($token->getScopes()),
                $token->getExpiresAt()->getTimestamp(),
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to save token: {$e->getMessage()}");
        }
    }

    /**
     * Check if token is revoked
     *
     * @param string $jti JWT ID
     *
     * @return bool True if revoked
     *
     * @throws RuntimeException If query fails
     */
    public function isTokenRevoked(string $jti): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM {$this->revocationTable}
                WHERE jti = ?
            ");

            $stmt->execute([$jti]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)$result['count'] > 0;
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to check token revocation: {$e->getMessage()}");
        }
    }

    /**
     * Revoke token by JTI
     *
     * @param string $jti                JWT ID
     * @param string $reason             Reason for revocation
     * @param string $revokedByClientId  Client that revoked it
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
            // Get token info
            $stmt = $this->pdo->prepare("
                SELECT client_id FROM {$this->tokenTable} WHERE jti = ?
            ");
            $stmt->execute([$jti]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);

            $clientId = $token['client_id'] ?? '';

            // Record in revocation table
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->revocationTable}
                (jti, client_id, reason, revoked_by, revoked_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE revoked_at = NOW()
            ");

            $stmt->execute([$jti, $clientId, $reason, $revokedByClientId]);

            // Mark token as revoked
            $stmt = $this->pdo->prepare("
                UPDATE {$this->tokenTable}
                SET revoked_at = NOW(), 
                    revocation_reason = ?,
                    revoked_by = ?
                WHERE jti = ?
            ");

            $stmt->execute([$reason, $revokedByClientId, $jti]);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to revoke token: {$e->getMessage()}");
        }
    }

    /**
     * Revoke all tokens for a client
     *
     * @param string $clientId Client ID
     * @param string $reason   Reason for revocation
     *
     * @return int Number of tokens revoked
     *
     * @throws RuntimeException If revocation fails
     */
    public function revokeClientTokens(string $clientId, string $reason = ''): int
    {
        try {
            // Get all active tokens for client
            $stmt = $this->pdo->prepare("
                SELECT jti FROM {$this->tokenTable}
                WHERE client_id = ? AND revoked_at IS NULL
            ");
            $stmt->execute([$clientId]);
            $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $count = 0;

            // Revoke each token
            foreach ($tokens as $token) {
                $this->revokeToken($token['jti'], $reason, 'system');
                $count++;
            }

            return $count;
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to revoke client tokens: {$e->getMessage()}");
        }
    }

    /**
     * Get token statistics for a client
     *
     * @param string $clientId Client ID
     *
     * @return array Statistics
     *
     * @throws RuntimeException If query fails
     */
    public function getClientTokenStats(string $clientId): array
    {
        try {
            $now = time();

            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN revoked_at IS NULL AND expires_at > ? THEN 1 END) as active,
                    COUNT(CASE WHEN revoked_at IS NULL AND expires_at <= ? THEN 1 END) as expired,
                    COUNT(CASE WHEN revoked_at IS NOT NULL THEN 1 END) as revoked,
                    COUNT(*) as total
                FROM {$this->tokenTable}
                WHERE client_id = ?
            ");

            $stmt->execute([$now, $now, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'active_tokens' => (int)$result['active'],
                'expired_tokens' => (int)$result['expired'],
                'revoked_tokens' => (int)$result['revoked'],
                'total_tokens' => (int)$result['total'],
            ];
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to get token statistics: {$e->getMessage()}");
        }
    }

    /**
     * Delete expired tokens from storage
     *
     * Cleanup job to reduce database size. Run periodically (e.g., daily).
     *
     * @return int Number of tokens deleted
     *
     * @throws RuntimeException If cleanup fails
     */
    public function deleteExpiredTokens(): int
    {
        try {
            $now = time();

            // Delete from main table
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->tokenTable}
                WHERE expires_at < ? AND revoked_at IS NOT NULL
            ");

            $stmt->execute([$now]);
            $count = $stmt->rowCount();

            // Cleanup revocation audit log (keep for 90 days)
            $ninetyDaysAgo = $now - (90 * 24 * 60 * 60);

            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->revocationTable}
                WHERE revoked_at < FROM_UNIXTIME(?)
            ");

            $stmt->execute([$ninetyDaysAgo]);

            return $count;
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to cleanup expired tokens: {$e->getMessage()}");
        }
    }

    /**
     * Get revocation audit log for a client
     *
     * @param string $clientId Client ID
     * @param int    $limit    Maximum records to return
     *
     * @return array Revocation history
     *
     * @throws RuntimeException If query fails
     */
    public function getClientRevocationLog(string $clientId, int $limit = 100): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT jti, reason, revoked_by, revoked_at
                FROM {$this->revocationTable}
                WHERE client_id = ?
                ORDER BY revoked_at DESC
                LIMIT ?
            ");

            $stmt->bindValue(1, $clientId, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to get revocation log: {$e->getMessage()}");
        }
    }

    /**
     * Create required database tables
     *
     * Call this during application initialization.
     *
     * @return void
     *
     * @throws RuntimeException If table creation fails
     */
    public function createTables(): void
    {
        try {
            // Determine database type
            $dbType = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($dbType === 'mysql') {
                $this->createMysqlTables();
            } elseif ($dbType === 'pgsql') {
                $this->createPostgresTables();
            } elseif ($dbType === 'sqlite') {
                $this->createSqliteTables();
            } else {
                throw new RuntimeException("Unsupported database type: {$dbType}");
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to create tables: {$e->getMessage()}");
        }
    }

    /**
     * Create MySQL tables
     *
     * @return void
     */
    private function createMysqlTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->tokenTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                jti VARCHAR(64) UNIQUE NOT NULL,
                client_id VARCHAR(255) NOT NULL,
                token_type ENUM('access', 'refresh') DEFAULT 'access',
                scopes JSON,
                expires_at BIGINT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                revoked_at TIMESTAMP NULL,
                revocation_reason VARCHAR(255),
                revoked_by VARCHAR(255),
                INDEX idx_client_id (client_id),
                INDEX idx_expires_at (expires_at),
                INDEX idx_revoked_at (revoked_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->revocationTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                jti VARCHAR(64) UNIQUE NOT NULL,
                client_id VARCHAR(255) NOT NULL,
                reason VARCHAR(255),
                revoked_by VARCHAR(255),
                revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_client_id (client_id),
                INDEX idx_jti (jti)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Create PostgreSQL tables
     *
     * @return void
     */
    private function createPostgresTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->tokenTable} (
                id SERIAL PRIMARY KEY,
                jti VARCHAR(64) UNIQUE NOT NULL,
                client_id VARCHAR(255) NOT NULL,
                token_type TEXT DEFAULT 'access' CHECK (token_type IN ('access', 'refresh')),
                scopes JSONB,
                expires_at BIGINT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                revoked_at TIMESTAMP NULL,
                revocation_reason VARCHAR(255),
                revoked_by VARCHAR(255)
            )
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_pg_client_id ON {$this->tokenTable}(client_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_pg_expires_at ON {$this->tokenTable}(expires_at)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_pg_revoked_at ON {$this->tokenTable}(revoked_at)");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->revocationTable} (
                id SERIAL PRIMARY KEY,
                jti VARCHAR(64) UNIQUE NOT NULL,
                client_id VARCHAR(255) NOT NULL,
                reason VARCHAR(255),
                revoked_by VARCHAR(255),
                revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_pg_rev_client_id ON {$this->revocationTable}(client_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_pg_rev_jti ON {$this->revocationTable}(jti)");
    }

    /**
     * Create SQLite tables
     *
     * @return void
     */
    private function createSqliteTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->tokenTable} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jti TEXT UNIQUE NOT NULL,
                client_id TEXT NOT NULL,
                token_type TEXT DEFAULT 'access',
                scopes TEXT,
                expires_at INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                revoked_at TIMESTAMP NULL,
                revocation_reason TEXT,
                revoked_by TEXT
            )
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_client_id ON {$this->tokenTable}(client_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_expires_at ON {$this->tokenTable}(expires_at)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_revoked_at ON {$this->tokenTable}(revoked_at)");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->revocationTable} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jti TEXT UNIQUE NOT NULL,
                client_id TEXT NOT NULL,
                reason TEXT,
                revoked_by TEXT,
                revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_rev_client_id ON {$this->revocationTable}(client_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_rev_jti ON {$this->revocationTable}(jti)");
    }
}
