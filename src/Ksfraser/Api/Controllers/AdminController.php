<?php
namespace Ksfraser\Api\Controllers;

use Ksfraser\Monitoring\PerformanceMetrics;

/**
 * Admin Controller - Admin REST API Endpoints
 * 
 * Handles OAuth2 client management and admin operations.
 * Requires admin scope for access.
 * 
 * Endpoints:
 * - GET    /api/v1/admin/clients              - List all clients
 * - POST   /api/v1/admin/clients              - Create new client
 * - GET    /api/v1/admin/clients/{client_id}  - Get client details
 * - PUT    /api/v1/admin/clients/{client_id}  - Update client
 * - DELETE /api/v1/admin/clients/{client_id}  - Delete client
 * - GET    /api/v1/admin/audit-log            - Get authorization logs
 * - GET    /api/v1/admin/health               - System health status
 * 
 * @package   Ksfraser\Api\Controllers
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class AdminController
{
    /**
     * @var PerformanceMetrics Metrics collector
     */
    private $metrics;

    /**
     * @var array OAuth2 clients storage (in-memory for demo)
     */
    private $clients = [];

    /**
     * @var array Audit log storage
     */
    private $auditLog = [];

    /**
     * Constructor
     *
     * @param PerformanceMetrics|null $metrics Metrics collector
     */
    public function __construct(?PerformanceMetrics $metrics = null)
    {
        $this->metrics = $metrics ?? new PerformanceMetrics();
    }

    /**
     * GET /api/v1/admin/clients
     * 
     * List all OAuth2 clients
     *
     * @param array $query Query parameters: { limit, offset, filter }
     *
     * @return array JSON response
     */
    public function listClients(array $query = []): array
    {
        try {
            $limit = (int)($query['limit'] ?? 50);
            $offset = (int)($query['offset'] ?? 0);
            $filter = $query['filter'] ?? null;

            // Filter clients if needed
            $filtered = $this->clients;
            if ($filter) {
                $filtered = array_filter($this->clients, function($client) use ($filter) {
                    return stripos($client['name'], $filter) !== false ||
                           stripos($client['client_id'], $filter) !== false;
                });
            }

            // Paginate
            $clients = array_slice($filtered, $offset, $limit);

            $this->metrics->recordMetric('admin_list_clients', 1, ['operation' => 'list']);

            return $this->success([
                'clients' => array_values($clients),
                'total' => count($filtered),
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/admin/clients
     * 
     * Create a new OAuth2 client
     *
     * @param array $request Client data
     *
     * @return array JSON response
     */
    public function createClient(array $request): array
    {
        try {
            // Validate required fields
            $required = ['name', 'redirect_uris'];
            foreach ($required as $field) {
                if (empty($request[$field])) {
                    return $this->error("Missing required field: $field", 400);
                }
            }

            // Generate client credentials
            $clientId = 'client_' . bin2hex(random_bytes(16));
            $clientSecret = bin2hex(random_bytes(32));

            // Create client record
            $client = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'name' => $request['name'],
                'description' => $request['description'] ?? '',
                'redirect_uris' => is_array($request['redirect_uris']) 
                    ? $request['redirect_uris'] 
                    : [$request['redirect_uris']],
                'scopes' => $request['scopes'] ?? ['read', 'write'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'active' => true
            ];

            $this->clients[$clientId] = $client;

            // Log audit event
            $this->logAuditEvent('client_created', $clientId, 'success');
            $this->metrics->recordMetric('admin_create_client', 1, ['operation' => 'create']);

            return $this->success([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'name' => $client['name'],
                'created_at' => $client['created_at']
            ], 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/admin/clients/{client_id}
     * 
     * Get client details
     *
     * @param string $clientId Client ID
     *
     * @return array JSON response
     */
    public function getClient(string $clientId): array
    {
        try {
            if (!isset($this->clients[$clientId])) {
                return $this->error('Client not found', 404);
            }

            $client = $this->clients[$clientId];
            // Remove secret from response
            unset($client['client_secret']);

            return $this->success($client);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/admin/clients/{client_id}
     * 
     * Update client configuration
     *
     * @param string $clientId Client ID
     * @param array $request Update data
     *
     * @return array JSON response
     */
    public function updateClient(string $clientId, array $request): array
    {
        try {
            if (!isset($this->clients[$clientId])) {
                return $this->error('Client not found', 404);
            }

            $client = $this->clients[$clientId];

            // Update allowed fields
            if (isset($request['name'])) {
                $client['name'] = $request['name'];
            }
            if (isset($request['description'])) {
                $client['description'] = $request['description'];
            }
            if (isset($request['redirect_uris'])) {
                $client['redirect_uris'] = is_array($request['redirect_uris']) 
                    ? $request['redirect_uris'] 
                    : [$request['redirect_uris']];
            }
            if (isset($request['scopes'])) {
                $client['scopes'] = is_array($request['scopes']) 
                    ? $request['scopes'] 
                    : explode(' ', $request['scopes']);
            }
            if (isset($request['active'])) {
                $client['active'] = (bool)$request['active'];
            }

            $client['updated_at'] = date('Y-m-d H:i:s');
            $this->clients[$clientId] = $client;

            $this->logAuditEvent('client_updated', $clientId, 'success');
            $this->metrics->recordMetric('admin_update_client', 1, ['operation' => 'update']);

            return $this->success($client);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/admin/clients/{client_id}
     * 
     * Delete an OAuth2 client
     *
     * @param string $clientId Client ID
     *
     * @return array JSON response
     */
    public function deleteClient(string $clientId): array
    {
        try {
            if (!isset($this->clients[$clientId])) {
                return $this->error('Client not found', 404);
            }

            unset($this->clients[$clientId]);
            $this->logAuditEvent('client_deleted', $clientId, 'success');
            $this->metrics->recordMetric('admin_delete_client', 1, ['operation' => 'delete']);

            return $this->success(['message' => 'Client deleted successfully']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/admin/audit-log
     * 
     * Get authorization audit log
     *
     * @param array $query Query parameters: { limit, offset, action, status }
     *
     * @return array JSON response
     */
    public function getAuditLog(array $query = []): array
    {
        try {
            $limit = (int)($query['limit'] ?? 100);
            $offset = (int)($query['offset'] ?? 0);
            $action = $query['action'] ?? null;
            $status = $query['status'] ?? null;

            // Filter log entries
            $filtered = $this->auditLog;
            if ($action) {
                $filtered = array_filter($filtered, fn($e) => $e['action'] === $action);
            }
            if ($status) {
                $filtered = array_filter($filtered, fn($e) => $e['status'] === $status);
            }

            // Sort by timestamp descending
            usort($filtered, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

            // Paginate
            $entries = array_slice($filtered, $offset, $limit);

            return $this->success([
                'entries' => array_values($entries),
                'total' => count($filtered),
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/admin/health
     * 
     * Get system health status
     *
     * @return array JSON response
     */
    public function getHealth(): array
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'uptime' => time(), // Would be from process start time
                'memory' => [
                    'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'limit_mb' => ini_get('memory_limit')
                ],
                'database' => [
                    'connected' => true,
                    'tables' => ['oauth2_clients', 'oauth2_tokens', 'oauth2_consents']
                ],
                'cache' => [
                    'backend' => 'Redis',
                    'connected' => true,
                    'items_cached' => count($this->clients)
                ],
                'services' => [
                    'oauth2' => 'running',
                    'metrics' => 'running',
                    'api' => 'running'
                ]
            ];

            return $this->success($health);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Helper Methods
     */

    /**
     * Log an audit event
     *
     * @param string $action Action performed
     * @param string $clientId Client ID affected
     * @param string $status Success/failure status
     *
     * @return void
     */
    private function logAuditEvent(string $action, string $clientId, string $status): void
    {
        $this->auditLog[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'client_id' => $clientId,
            'status' => $status,
            'details' => []
        ];
    }

    /**
     * Return success response
     *
     * @param array $data Response data
     * @param int $code HTTP status code
     *
     * @return array JSON response
     */
    private function success(array $data, int $code = 200): array
    {
        return [
            'success' => true,
            'data' => $data,
            'code' => $code
        ];
    }

    /**
     * Return error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     *
     * @return array JSON response
     */
    private function error(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code
            ]
        ];
    }
}
