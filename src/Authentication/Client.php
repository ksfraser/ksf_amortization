<?php

namespace Ksfraser\Amortizations\Authentication;

/**
 * Client - OAuth2 Client Credential
 *
 * Represents an OAuth2 client (application) authorized to use the API.
 * Each client has credentials and granted scopes.
 *
 * ### Credentials Grant
 * - Client ID: Public identifier
 * - Client Secret: Private credential (never transmitted)
 * - Scopes: Permissions granted to this client
 *
 * ### Usage
 * ```php
 * $client = new Client('app_id', 'app_secret');
 * $client->grantScope('loan:read');
 * $client->grantScope('loan:write');
 *
 * if ($client->hasScope('loan:read')) {
 *     // Allow read access
 * }
 * ```
 *
 * @package Ksfraser\Amortizations\Authentication
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class Client
{
    /**
     * Client identifier (public)
     *
     * @var string
     */
    private $clientId;

    /**
     * Client secret (private)
     *
     * @var string
     */
    private $clientSecret;

    /**
     * Client display name
     *
     * @var string
     */
    private $name;

    /**
     * Granted scopes
     *
     * @var array
     */
    private $scopes = [];

    /**
     * Client active status
     *
     * @var bool
     */
    private $active = true;

    /**
     * Allowed redirect URIs (for future OAuth2 flow)
     *
     * @var array
     */
    private $redirectUris = [];

    /**
     * Constructor
     *
     * @param string $clientId     Client identifier
     * @param string $clientSecret Client secret
     * @param string $name         Client display name
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        string $name = ''
    ) {
        if (empty($clientId) || empty($clientSecret)) {
            throw new \InvalidArgumentException('Client ID and secret required');
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->name = $name ?: $clientId;
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
     * Get client secret
     *
     * @return string Client secret
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * Get client name
     *
     * @return string Client name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Grant scope to client
     *
     * @param string $scope Scope name
     *
     * @return self
     */
    public function grantScope(string $scope): self
    {
        if (!in_array($scope, $this->scopes)) {
            $this->scopes[] = $scope;
        }

        return $this;
    }

    /**
     * Grant multiple scopes
     *
     * @param array $scopes Scope names
     *
     * @return self
     */
    public function grantScopes(array $scopes): self
    {
        foreach ($scopes as $scope) {
            $this->grantScope($scope);
        }

        return $this;
    }

    /**
     * Revoke scope from client
     *
     * @param string $scope Scope name
     *
     * @return self
     */
    public function revokeScope(string $scope): self
    {
        $this->scopes = array_diff($this->scopes, [$scope]);
        return $this;
    }

    /**
     * Check if client has scope
     *
     * @param string $scope Scope to check
     *
     * @return bool True if granted
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes);
    }

    /**
     * Check if client has all scopes
     *
     * @param array $scopes Scopes to check
     *
     * @return bool True if all granted
     */
    public function hasScopes(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (!$this->hasScope($scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all granted scopes
     *
     * @return array Scopes
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Set client active status
     *
     * @param bool $active Active status
     *
     * @return self
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Check if client active
     *
     * @return bool True if active
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Add redirect URI
     *
     * @param string $uri Redirect URI
     *
     * @return self
     */
    public function addRedirectUri(string $uri): self
    {
        if (!in_array($uri, $this->redirectUris)) {
            $this->redirectUris[] = $uri;
        }

        return $this;
    }

    /**
     * Check if URI allowed
     *
     * @param string $uri URI to check
     *
     * @return bool True if allowed
     */
    public function isRedirectUriAllowed(string $uri): bool
    {
        return in_array($uri, $this->redirectUris);
    }

    /**
     * Get all redirect URIs
     *
     * @return array URIs
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    /**
     * Verify client secret
     *
     * @param string $secret Secret to verify
     *
     * @return bool True if matches
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->clientSecret, $secret);
    }

    /**
     * Validate credentials
     *
     * Checks if client is active and credentials present.
     *
     * @return bool True if valid
     */
    public function validate(): bool
    {
        return $this->active &&
               !empty($this->clientId) &&
               !empty($this->clientSecret);
    }

    /**
     * Convert to array (for logging - excludes secret)
     *
     * @return array Client data (secret excluded)
     */
    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'name' => $this->name,
            'scopes' => $this->scopes,
            'active' => $this->active,
            'redirect_uris' => $this->redirectUris,
        ];
    }
}
