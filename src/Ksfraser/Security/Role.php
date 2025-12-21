<?php

namespace Ksfraser\Security;

/**
 * Role - Permission role definition
 * 
 * @package    Ksfraser\Security
 * @since      20251221
 */
class Role
{
    /**
     * @var string Role ID
     */
    private string $id;

    /**
     * @var string Role name
     */
    private string $name;

    /**
     * @var array Permissions
     */
    private array $permissions = [];

    /**
     * Constructor
     * 
     * @param string $id Role ID
     * @param string $name Display name
     */
    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Get role ID
     * 
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get role name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add permission
     * 
     * @param string $permission Permission name
     * @return self
     */
    public function addPermission(string $permission): self
    {
        if (!in_array($permission, $this->permissions)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    /**
     * Remove permission
     * 
     * @param string $permission Permission name
     * @return self
     */
    public function removePermission(string $permission): self
    {
        $this->permissions = array_filter($this->permissions, fn($p) => $p !== $permission);
        return $this;
    }

    /**
     * Check if role has permission
     * 
     * @param string $permission Permission name
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    /**
     * Get all permissions
     * 
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
