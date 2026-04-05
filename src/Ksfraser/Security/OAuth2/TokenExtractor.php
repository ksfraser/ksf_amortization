<?php
namespace Ksfraser\Security\OAuth2;

/**
 * Token Extractor
 * 
 * Extracts OAuth2 Bearer tokens from HTTP requests.
 * Supports multiple token sources with priority:
 * 1. Authorization header (Bearer token)
 * 2. Query parameter (for non-browser clients)
 * 3. POST body parameter
 * 4. Cookie (for same-origin requests)
 * 
 * Per RFC 6750: The preferred method is Authorization header.
 * 
 * @package   Ksfraser\Security\OAuth2
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class TokenExtractor
{
    /**
     * Extract Bearer token from Authorization header
     * 
     * Format: Authorization: Bearer <token>
     *
     * @param array $headers Request headers
     *
     * @return string|null Token if found, null otherwise
     */
    public static function fromAuthorizationHeader(array $headers): ?string
    {
        $authHeader = null;

        // Check for Authorization header (case-insensitive)
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }

        if (!$authHeader) {
            return null;
        }

        // Parse "Bearer <token>" format
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract Bearer token from query parameter
     * 
     * RFC 6750 Section 2.3 (discouraged but allowed)
     * Query param: ?access_token=<token>
     *
     * @param array $query Query parameters
     * @param string $paramName Query parameter name (default: access_token)
     *
     * @return string|null Token if found, null otherwise
     */
    public static function fromQueryParameter(
        array $query,
        string $paramName = 'access_token'
    ): ?string {
        return $query[$paramName] ?? null;
    }

    /**
     * Extract Bearer token from POST body
     * 
     * POST param: access_token=<token>
     * Used for token endpoint and refresh token flows
     *
     * @param array $body POST body parameters
     * @param string $paramName Parameter name (default: access_token)
     *
     * @return string|null Token if found, null otherwise
     */
    public static function fromPostBody(
        array $body,
        string $paramName = 'access_token'
    ): ?string {
        return $body[$paramName] ?? null;
    }

    /**
     * Extract Bearer token from Cookie
     * 
     * Cookie name: access_token=<token>
     * Useful for SPAs and browser-based clients
     *
     * @param array $cookies Cookie values
     * @param string $cookieName Cookie name (default: access_token)
     *
     * @return string|null Token if found, null otherwise
     */
    public static function fromCookie(
        array $cookies,
        string $cookieName = 'access_token'
    ): ?string {
        return $cookies[$cookieName] ?? null;
    }

    /**
     * Extract token from request with priority order
     * 
     * Priority (RFC 6750 compliant):
     * 1. Authorization header (Bearer token)
     * 2. Query parameter
     * 3. POST body parameter
     * 4. Cookie (SPA/browser clients)
     *
     * @param array $headers Request headers
     * @param array $query Query parameters (optional)
     * @param array $body POST body (optional)
     * @param array $cookies Cookies (optional)
     *
     * @return string|null Token if found, null otherwise
     */
    public static function extract(
        array $headers,
        array $query = [],
        array $body = [],
        array $cookies = []
    ): ?string {
        // Priority 1: Authorization header (most secure)
        $token = self::fromAuthorizationHeader($headers);
        if ($token) {
            return $token;
        }

        // Priority 2: Query parameter
        $token = self::fromQueryParameter($query);
        if ($token) {
            return $token;
        }

        // Priority 3: POST body parameter
        $token = self::fromPostBody($body);
        if ($token) {
            return $token;
        }

        // Priority 4: Cookie
        $token = self::fromCookie($cookies);
        if ($token) {
            return $token;
        }

        return null;
    }

    /**
     * Validate token format (basic syntax check)
     * 
     * Tokens should be non-empty alphanumeric strings with dots (for JWTs)
     * JWT format: header.payload.signature (three parts separated by dots)
     * Opaque format: alphanumeric string
     *
     * @param string $token Token to validate
     *
     * @return bool True if token format looks valid
     */
    public static function isValidFormat(string $token): bool
    {
        if (empty($token) || strlen($token) > 10000) {
            return false;
        }

        // Allow JWT format (contains dots) and opaque tokens (alphanumeric with hyphens)
        return preg_match('/^[a-zA-Z0-9._-]+$/', $token) === 1;
    }

    /**
     * Check if token is JWT format
     * 
     * JWT format: three dot-separated parts
     *
     * @param string $token Token to check
     *
     * @return bool True if token appears to be JWT
     */
    public static function isJwtFormat(string $token): bool
    {
        $parts = explode('.', $token);
        return count($parts) === 3;
    }

    /**
     * Sanitize token string
     * 
     * Removes whitespace and invalid characters
     *
     * @param string $token Token to sanitize
     *
     * @return string Sanitized token
     */
    public static function sanitize(string $token): string
    {
        $token = trim($token);
        // Remove any control characters
        $token = preg_replace('/[\x00-\x1f\x7f]/', '', $token);
        return $token;
    }

    /**
     * Extract token type from Authorization header
     * 
     * Returns the scheme (Bearer, Basic, etc.)
     *
     * @param array $headers Request headers
     *
     * @return string|null Token type (Bearer, Basic, etc.) or null
     */
    public static function getTokenType(array $headers): ?string
    {
        $authHeader = null;

        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/^(\w+)\s+/', $authHeader, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }

    /**
     * Check if Authorization header contains Bearer token
     *
     * @param array $headers Request headers
     *
     * @return bool True if Bearer token present
     */
    public static function hasBearerToken(array $headers): bool
    {
        return self::getTokenType($headers) === 'bearer';
    }
}
