<?php

namespace Ksfraser\Amortizations\Authentication;

use Exception;

/**
 * InvalidTokenException - Token Validation Error
 *
 * Thrown when token validation fails for any reason:
 * - Invalid signature
 * - Expired token
 * - Revoked token
 * - Missing claims
 * - Wrong algorithm
 *
 * @package Ksfraser\Amortizations\Authentication
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class InvalidTokenException extends Exception
{
    /**
     * Error code for invalid signature
     */
    public const CODE_INVALID_SIGNATURE = 1001;

    /**
     * Error code for expired token
     */
    public const CODE_EXPIRED = 1002;

    /**
     * Error code for revoked token
     */
    public const CODE_REVOKED = 1003;

    /**
     * Error code for missing claims
     */
    public const CODE_MISSING_CLAIMS = 1004;

    /**
     * Error code for malformed token
     */
    public const CODE_MALFORMED = 1005;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int    $code    Error code
     */
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct("Invalid token: {$message}", $code);
    }
}
