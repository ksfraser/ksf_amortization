<?php

namespace Ksfraser\Security;

/**
 * AuthorizationManager - RBAC (Role-Based Access Control) system
 * 
 * Features:
 * - Role and permission management
 * - User role assignment
 * - Permission checking
 * - Access logging
 * 
 * @package    Ksfraser\Security
 * @since      20251221
 */
class AuthorizationManager
{
    /**
     * @var array Defined roles
     */
    private array $roles = [];

    /**
     * @var array User to role mapping
     */
    private array $userRoles = [];

    /**
     * @var array Access log
     */
    private array $accessLog = [];

    /**
     * Define a role
     * 
     * @param Role $role Role definition
     * @return self
     */
    public function defineRole(Role $role): self
    {
        $this->roles[$role->getId()] = $role;
        return $this;
    }

    /**
     * Get a defined role
     * 
     * @param string $roleId Role ID
     * @return Role|null
     */
    public function getRole(string $roleId): ?Role
    {
        return $this->roles[$roleId] ?? null;
    }

    /**
     * Assign role to user
     * 
     * @param string $userId User ID
     * @param string $roleId Role ID
     * @return self
     */
    public function assignRoleToUser(string $userId, string $roleId): self
    {
        if (!$this->getRole($roleId)) {
            throw new \InvalidArgumentException("Role '{$roleId}' is not defined");
        }

        if (!isset($this->userRoles[$userId])) {
            $this->userRoles[$userId] = [];
        }

        if (!in_array($roleId, $this->userRoles[$userId])) {
            $this->userRoles[$userId][] = $roleId;
        }

        return $this;
    }

    /**
     * Remove role from user
     * 
     * @param string $userId User ID
     * @param string $roleId Role ID
     * @return self
     */
    public function removeRoleFromUser(string $userId, string $roleId): self
    {
        if (isset($this->userRoles[$userId])) {
            $this->userRoles[$userId] = array_filter(
                $this->userRoles[$userId],
                fn($r) => $r !== $roleId
            );
        }

        return $this;
    }

    /**
     * Get user roles
     * 
     * @param string $userId User ID
     * @return array Role IDs
     */
    public function getUserRoles(string $userId): array
    {
        return $this->userRoles[$userId] ?? [];
    }

    /**
     * Check if user has permission
     * 
     * @param string $userId User ID
     * @param string $permission Permission name
     * @return bool
     */
    public function hasPermission(string $userId, string $permission): bool
    {
        $roles = $this->getUserRoles($userId);

        foreach ($roles as $roleId) {
            $role = $this->getRole($roleId);
            if ($role && $role->hasPermission($permission)) {
                $this->logAccess($userId, $permission, true);
                return true;
            }
        }

        $this->logAccess($userId, $permission, false);
        return false;
    }

    /**
     * Log access attempt
     * 
     * @param string $userId User ID
     * @param string $permission Permission
     * @param bool $granted Was access granted
     * @return void
     */
    private function logAccess(string $userId, string $permission, bool $granted): void
    {
        $this->accessLog[] = [
            'user_id' => $userId,
            'permission' => $permission,
            'granted' => $granted,
            'timestamp' => time()
        ];
    }

    /**
     * Get access log
     * 
     * @param int $limit Number of recent entries
     * @return array
     */
    public function getAccessLog(int $limit = 100): array
    {
        return array_slice($this->accessLog, -$limit);
    }

    /**
     * Clear access log
     * 
     * @return void
     */
    public function clearAccessLog(): void
    {
        $this->accessLog = [];
    }

    /**
     * Get all defined roles
     * 
     * @return array
     */
    public function getAllRoles(): array
    {
        return $this->roles;
    }

    /**
     * Get role statistics
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $roleCount = count($this->roles);
        $userCount = count($this->userRoles);
        $deniedCount = count(array_filter($this->accessLog, fn($log) => !$log['granted']));
        $grantedCount = count($this->accessLog) - $deniedCount;

        return [
            'total_roles' => $roleCount,
            'total_users' => $userCount,
            'total_access_checks' => count($this->accessLog),
            'access_granted' => $grantedCount,
            'access_denied' => $deniedCount,
            'grant_rate' => count($this->accessLog) > 0
                ? round(($grantedCount / count($this->accessLog)) * 100, 2)
                : 0
        ];
    }
}
