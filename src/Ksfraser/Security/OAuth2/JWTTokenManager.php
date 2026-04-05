<?php
namespace Ksfraser\Security\OAuth2;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\DomainException;
use Ksfraser\Security\Exceptions\TokenException;

/**
 * JWT Token Manager - JWT token lifecycle management
 * 
 * Wraps firebase/php-jwt for production-grade JWT handling.
 * 
 * This implementation uses the battle-tested firebase/php-jwt library which:
 * - Handles all JWT algorithms (HS256, RS256, ES256, EdDSA, etc.)
 * - Properly validates signatures and claims
 * - Implements security best practices
 * - Is maintained by the PHP community
 * - Supports 20M+ monthly downloads
 * 
 * @package   Ksfraser\Security\OAuth2
 * @author    KSF Development Team
 * @version   2.0.0 (Now using firebase/php-jwt)
 * @since     2026-04-03
 */
class JWTTokenManager
{
    /**
     * @var string Secret key for signing tokens (HMAC) or path to private key (RSA)
     */
    private $secretKey;

    /**
     * @var string Algorithm used for signing (default: HS256)
     */
    private $algorithm = 'HS256';

    /**
     * JWTTokenManager constructor.
     *
     * @param string $secretKey Secret key for signing tokens
     * @param string $algorithm Signing algorithm (default: HS256)
     *
     * @throws TokenException If secret key is invalid
     */
    public function __construct(string $secretKey)
    {
        if (strlen($secretKey) < 32) {
            throw new TokenException("Secret key must be at least 32 characters long");
        }
        
        $this->secretKey = $secretKey;
    }

    /**
     * Generate a JWT token
     *
     * @param array $claims Token claims/payload
     * @param string $issuer Token issuer (iss claim)
     * @param string $audience Token audience (aud claim)
     *
     * @return string Encoded JWT token
     *
     * @throws TokenException If token generation fails
     */
    public function generate(array $claims, string $issuer = '', string $audience = ''): string
    {
        try {
            // Add issuer and audience if provided
            if (!empty($issuer)) {
                $claims['iss'] = $issuer;
            }
            if (!empty($audience)) {
                $claims['aud'] = $audience;
            }

            // Set issued-at time if not present
            if (!isset($claims['iat'])) {
                $claims['iat'] = time();
            }

            // Ensure exp is set if not explicitly provided
            if (!isset($claims['exp'])) {
                $claims['exp'] = time() + 3600; // Default 1 hour
            }

            $key = new Key($this->secretKey, $this->algorithm);
            return JWT::encode($claims, $this->secretKey, $this->algorithm);
        } catch (\Exception $e) {
            throw new TokenException("Failed to generate token: " . $e->getMessage());
        }
    }

    /**
     * Validate and decode a JWT token
     *
     * @param string $token JWT token
     * @param string $issuer Expected issuer (iss claim)
     * @param string $audience Expected audience (aud claim)
     *
     * @return array Decoded claims
     *
     * @throws TokenException If token invalid, expired, or signature mismatches
     */
    public function validate(string $token, string $issuer = '', string $audience = ''): array
    {
        try {
            // Decode and validate token
            $key = new Key($this->secretKey, $this->algorithm);
            $decoded = JWT::decode($token, $key);

            // Convert stdClass to array
            $claims = json_decode(json_encode($decoded), true);

            // Verify issuer if specified
            if (!empty($issuer) && ($claims['iss'] ?? '') !== $issuer) {
                throw new TokenException("Invalid token issuer");
            }

            // Verify audience if specified
            if (!empty($audience) && ($claims['aud'] ?? '') !== $audience) {
                throw new TokenException("Invalid audience");
            }

            return $claims;
        } catch (ExpiredException $e) {
            throw new TokenException("Token has expired");
        } catch (SignatureInvalidException $e) {
            throw new TokenException("Invalid token signature");
        } catch (BeforeValidException $e) {
            throw new TokenException("Token not yet valid");
        } catch (DomainException $e) {
            throw new TokenException("Invalid token domain: " . $e->getMessage());
        } catch (TokenException $e) {
            throw $e; // Re-throw our custom exceptions
        } catch (\Exception $e) {
            throw new TokenException("Token validation failed: " . $e->getMessage());
        }
    }

    /**
     * Check if a token is expired
     *
     * @param string $token JWT token
     *
     * @return bool True if expired, false otherwise
     */
    public function isExpired(string $token): bool
    {
        try {
            $key = new Key($this->secretKey, $this->algorithm);
            JWT::decode($token, $key);
            return false;
        } catch (ExpiredException $e) {
            return true;
        } catch (\Exception $e) {
            return true; // Consider any error as expired for safety
        }
    }

    /**
     * Decode token without verification (for debugging only)
     * 
     * WARNING: This bypasses security checks. Only use for debugging/inspection.
     *
     * @param string $token JWT token
     *
     * @return array Decoded claims
     *
     * @throws TokenException If token format invalid
     */
    public function decode(string $token): array
    {
        try {
            // Safely decode without verification
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new TokenException("Invalid token format");
            }

            // Decode payload manually (no verification)
            $payload = base64_decode(strtr($parts[1], '-_', '+/'));
            $claims = json_decode($payload, true);
            
            if (!$claims) {
                throw new TokenException("Failed to decode token payload");
            }

            return $claims;
        } catch (TokenException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TokenException("Failed to decode token: " . $e->getMessage());
        }
    }

    /**
     * Set the algorithm for token signing
     * 
     * Common algorithms:
     * - HS256: HMAC with SHA-256 (default, requires shared secret)
     * - RS256: RSA with SHA-256 (requires private/public key pair)
     * - ES256: ECDSA with SHA-256 (requires private/public key pair)
     * - EdDSA: Edwards-curve DSA (requires private/public key pair)
     *
     * @param string $algorithm
     *
     * @return void
     */
    public function setAlgorithm(string $algorithm): void
    {
        $this->algorithm = $algorithm;
    }

    /**
     * Get the current algorithm
     *
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
}
