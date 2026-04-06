<?php
/**
 * Base Controller Class
 * 
 * Provides common functionality for all API controllers.
 * Handles authentication, authorization, response formatting, etc.
 * 
 * @package Ksfraser\Api\Controllers
 * @author KSF Development Team
 * @version 1.0.0
 */

namespace Ksfraser\Api\Controllers;

use Exception;

/**
 * BaseController - Abstract base for all API controllers
 * 
 * Provides common functionality for request handling,
 * authentication, validation, and response formatting.
 */
abstract class BaseController
{
    /**
     * @var array Current request data
     */
    protected $request = [];

    /**
     * @var array Current user
     */
    protected $user = [];

    /**
     * @var bool Whether request is authenticated
     */
    protected $authenticated = false;

    /**
     * Constructor
     * 
     * Initializes controller with request data
     */
    public function __construct()
    {
        $this->initializeRequest();
    }

    /**
     * Initialize request data
     * 
     * @return void
     */
    protected function initializeRequest(): void
    {
        // Parse authentication
        $this->authenticated = $this->checkAuthentication();
        if ($this->authenticated) {
            $this->user = $this->getCurrentUser();
        }
    }

    /**
     * Check if request is authenticated
     * 
     * @return bool
     */
    protected function checkAuthentication(): bool
    {
        // Check session
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id'] ?? null)) {
            return true;
        }

        // Check bearer token
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/', $auth, $matches)) {
            return $this->validateToken($matches[1]);
        }

        return false;
    }

    /**
     * Validate authentication token
     * 
     * @param string $token Token to validate
     * 
     * @return bool
     */
    protected function validateToken(string $token): bool
    {
        // TODO: Implement token validation
        // This should check token expiry, signature, etc.
        return !empty($token);
    }

    /**
     * Get current authenticated user
     * 
     * @return array
     */
    protected function getCurrentUser(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id'] ?? null)) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'user',
            ];
        }

        // TODO: Extract user from token

        return [];
    }

    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    protected function isAdmin(): bool
    {
        return $this->user['role'] ?? '' === 'admin';
    }

    /**
     * Require authentication
     * 
     * @return void
     * @throws Exception
     */
    protected function requireAuth(): void
    {
        if (!$this->authenticated) {
            throw new Exception('Unauthorized', 401);
        }
    }

    /**
     * Require admin role
     * 
     * @return void
     * @throws Exception
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            throw new Exception('Admin access required', 403);
        }
    }

    /**
     * Validate required fields
     * 
     * @param array $data Data to validate
     * @param array $required Required field names
     * 
     * @return void
     * @throws Exception
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new Exception(
                'Missing required fields: ' . implode(', ', $missing),
                400
            );
        }
    }

    /**
     * Validate field types
     * 
     * @param array $data Data to validate
     * @param array $schema Type schema [field => type]
     * 
     * @return void
     * @throws Exception
     */
    protected function validateTypes(array $data, array $schema): void
    {
        $errors = [];

        foreach ($schema as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            if ($type === 'int' && !is_int($value)) {
                $errors[] = "{$field} must be an integer";
            } elseif ($type === 'string' && !is_string($value)) {
                $errors[] = "{$field} must be a string";
            } elseif ($type === 'bool' && !is_bool($value)) {
                $errors[] = "{$field} must be a boolean";
            } elseif ($type === 'array' && !is_array($value)) {
                $errors[] = "{$field} must be an array";
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode('; ', $errors), 400);
        }
    }

    /**
     * Validate email format
     * 
     * @param string $email Email to validate
     * 
     * @return bool
     */
    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL format
     * 
     * @param string $url URL to validate
     * 
     * @return bool
     */
    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Success response
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @param array $meta Additional metadata
     * 
     * @return array
     */
    protected function success($data = null, int $statusCode = 200, array $meta = []): array
    {
        return array_merge([
            'success' => true,
            'data' => $data,
            'status' => $statusCode,
        ], $meta);
    }

    /**
     * Error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $details Additional error details
     * 
     * @return array
     */
    protected function error(string $message, int $statusCode = 400, array $details = []): array
    {
        return array_merge([
            'error' => true,
            'message' => $message,
            'status' => $statusCode,
        ], $details);
    }

    /**
     * Paginated response
     * 
     * @param array $items List of items
     * @param int $page Current page
     * @param int $pageSize Items per page
     * @param int $total Total item count
     * 
     * @return array
     */
    protected function paginated(array $items, int $page = 1, int $pageSize = 20, int $total = 0): array
    {
        return [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'pageCount' => ceil($total / $pageSize),
            ],
        ];
    }

    /**
     * Get query parameter with default
     * 
     * @param array $query Query parameters
     * @param string $key Parameter name
     * @param mixed $default Default value
     * 
     * @return mixed
     */
    protected function getQuery(array $query, string $key, $default = null)
    {
        return $query[$key] ?? $default;
    }

    /**
     * Log action
     * 
     * @param string $action Action name
     * @param array $context Context data
     * 
     * @return void
     */
    protected function log(string $action, array $context = []): void
    {
        // TODO: Implement logging
        error_log(json_encode([
            'timestamp' => date('c'),
            'user_id' => $this->user['id'] ?? null,
            'action' => $action,
            'context' => $context,
        ]));
    }
}
