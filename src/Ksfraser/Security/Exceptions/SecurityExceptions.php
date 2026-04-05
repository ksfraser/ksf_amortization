<?php
namespace Ksfraser\Security\Exceptions;

/**
 * TokenException - Thrown when token operations fail
 */
class TokenException extends \RuntimeException
{
}

/**
 * AuthenticationException - Thrown when authentication fails
 */
class AuthenticationException extends \RuntimeException
{
}

/**
 * AuthorizationException - Thrown when authorization check fails
 */
class AuthorizationException extends \RuntimeException
{
}
