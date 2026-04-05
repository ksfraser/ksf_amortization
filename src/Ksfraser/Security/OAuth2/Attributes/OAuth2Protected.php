<?php
namespace Ksfraser\Security\OAuth2\Attributes;

/**
 * OAuth2Protected Attribute
 * 
 * PHP 8+ attribute for marking routes/methods that require OAuth2 authentication.
 * Supports granular scope requirements through inheritance and overrides.
 * 
 * Usage Examples:
 * ```php
 * // Require any amortization scope
 * #[OAuth2Protected(scope: 'amortization:read')]
 * public function getLoans() { }
 * 
 * // Require specific resource scope
 * #[OAuth2Protected(scope: 'amortization:portfolio:read')]
 * public function getPortfolio() { }
 * 
 * // Require multiple scopes (all required)
 * #[OAuth2Protected(scopes: ['amortization:read', 'portfolio:read'])]
 * public function compareLoans() { }
 * 
 * // Allow any of multiple scopes
 * #[OAuth2Protected(scopesAny: ['amortization:admin', 'portfolio:admin'])]
 * public function adminOperation() { }
 * 
 * // Optional: allow public access
 * #[OAuth2Protected(scope: 'amortization:read', allowPublic: true)]
 * public function getPublicRates() { }
 * ```
 * 
 * @Attribute
 * @Target({Target::METHOD, Target::CLASS, Target::FUNCTION})
 * 
 * @package   Ksfraser\Security\OAuth2\Attributes
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class OAuth2Protected
{
    /**
     * Single scope required for this endpoint
     *
     * @var string|null
     */
    public ?string $scope = null;

    /**
     * Multiple scopes - all required for this endpoint
     *
     * @var array
     */
    public array $scopes = [];

    /**
     * Multiple scopes - any one required for this endpoint
     *
     * @var array
     */
    public array $scopesAny = [];

    /**
     * Allow public access (no OAuth2 token required)
     * Useful for public endpoints that also support OAuth2
     *
     * @var bool
     */
    public bool $allowPublic = false;

    /**
     * Rate limit per scope (requests per minute)
     * null means unlimited
     *
     * @var int|null
     */
    public ?int $rateLimit = null;

    /**
     * Custom error message for scope violations
     *
     * @var string|null
     */
    public ?string $errorMessage = null;

    /**
     * OAuth2Protected constructor
     *
     * @param string|null $scope Single required scope
     * @param array $scopes Multiple scopes (all required)
     * @param array $scopesAny Multiple scopes (any required)
     * @param bool $allowPublic Allow public access
     * @param int|null $rateLimit Rate limit (requests/minute)
     * @param string|null $errorMessage Custom error message
     */
    public function __construct(
        ?string $scope = null,
        array $scopes = [],
        array $scopesAny = [],
        bool $allowPublic = false,
        ?int $rateLimit = null,
        ?string $errorMessage = null
    ) {
        $this->scope = $scope;
        $this->scopes = $scopes;
        $this->scopesAny = $scopesAny;
        $this->allowPublic = $allowPublic;
        $this->rateLimit = $rateLimit;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the required scopes as a list
     * 
     * Handles conversion of single scope to array format.
     *
     * @return array All scope requirements
     */
    public function getRequiredScopes(): array
    {
        $required = [];

        if ($this->scope) {
            $required[] = $this->scope;
        }

        if (!empty($this->scopes)) {
            array_push($required, ...$this->scopes);
        }

        return array_unique($required);
    }

    /**
     * Get the alternative scopes (any one required)
     *
     * @return array Alternative scopes
     */
    public function getAlternativeScopes(): array
    {
        return array_unique($this->scopesAny);
    }

    /**
     * Check if endpoint allows public access
     *
     * @return bool True if public access allowed
     */
    public function isPublic(): bool
    {
        return $this->allowPublic;
    }

    /**
     * Check if endpoint requires authentication
     *
     * @return bool True if OAuth2 token required
     */
    public function requiresAuthentication(): bool
    {
        return !$this->allowPublic && (
            !empty($this->scope) || 
            !empty($this->scopes) || 
            !empty($this->scopesAny)
        );
    }

    /**
     * Get custom error message if defined
     *
     * @return string|null Custom error message
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Get rate limit setting
     *
     * @return int|null Rate limit in requests per minute
     */
    public function getRateLimit(): ?int
    {
        return $this->rateLimit;
    }
}
