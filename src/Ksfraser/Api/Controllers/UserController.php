<?php
namespace Ksfraser\Api\Controllers;

/**
 * User Controller - User Profile REST API
 * 
 * Provides user-specific endpoints for managing profile, consents, and tokens.
 * Requires valid access token.
 * 
 * Endpoints:
 * - GET    /api/v1/user/me           - Get current user info
 * - GET    /api/v1/user/consents     - Get user consents
 * - POST   /api/v1/user/consents/revoke - Revoke consent
 * - GET    /api/v1/user/tokens       - Get user tokens
 * 
 * @package   Ksfraser\Api\Controllers
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class UserController
{
    /**
     * @var array User data storage (in-memory for demo)
     */
    private $users = [];

    /**
     * @var array User consents storage
     */
    private $consents = [];

    /**
     * Require authentication middleware
     * Headers: { Authorization: Bearer <token> }
     */

    /**
     * GET /api/v1/user/me
     * 
     * Get current user profile information
     *
     * @param array $claims JWT claims from token
     *
     * @return array JSON response
     */
    public function getMe(array $claims): array
    {
        try {
            $userId = $claims['user_id'] ?? null;
            if (!$userId) {
                return $this->error('Unauthorized: No user in token', 401);
            }

            // In production, fetch from database
            $user = [
                'user_id' => $userId,
                'email' => $userId . '@example.com',
                'name' => 'User ' . substr($userId, 0, 8),
                'picture' => 'https://api.example.com/avatars/' . $userId . '.jpg',
                'email_verified' => false,
                'created_at' => date('Y-m-d H:i:s', time() - 86400),
                'updated_at' => date('Y-m-d H:i:s'),
                'scopes' => explode(' ', $claims['scope'] ?? 'read')
            ];

            return $this->success($user);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/user/consents
     * 
     * Get all consents granted by user
     *
     * @param array $claims JWT claims from token
     * @param array $query Query parameters
     *
     * @return array JSON response
     */
    public function getConsents(array $claims, array $query = []): array
    {
        try {
            $userId = $claims['user_id'] ?? null;
            if (!$userId) {
                return $this->error('Unauthorized: No user in token', 401);
            }

            $limit = (int)($query['limit'] ?? 50);
            $offset = (int)($query['offset'] ?? 0);

            // Get user's consents
            $userConsents = array_filter(
                $this->consents,
                fn($c) => $c['user_id'] === $userId
            );

            // Sort by granted date descending
            usort($userConsents, fn($a, $b) => strtotime($b['granted_at']) - strtotime($a['granted_at']));

            // Paginate
            $consents = array_slice($userConsents, $offset, $limit);

            return $this->success([
                'consents' => array_values($consents),
                'total' => count($userConsents),
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/user/consents/{client_id}/revoke
     * 
     * Revoke consent for a specific client
     *
     * @param string $clientId Client ID
     * @param array $claims JWT claims from token
     *
     * @return array JSON response
     */
    public function revokeConsent(string $clientId, array $claims): array
    {
        try {
            $userId = $claims['user_id'] ?? null;
            if (!$userId) {
                return $this->error('Unauthorized: No user in token', 401);
            }

            // Find and revoke consent
            $found = false;
            foreach ($this->consents as $key => $consent) {
                if ($consent['user_id'] === $userId && $consent['client_id'] === $clientId) {
                    $this->consents[$key]['revoked_at'] = date('Y-m-d H:i:s');
                    $this->consents[$key]['revoked'] = true;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return $this->error('Consent not found', 404);
            }

            return $this->success([
                'message' => 'Consent revoked successfully',
                'revoked_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/user/tokens
     * 
     * Get user's issued tokens (metadata only, not actual tokens)
     *
     * @param array $claims JWT claims from token
     * @param array $query Query parameters
     *
     * @return array JSON response
     */
    public function getTokens(array $claims, array $query = []): array
    {
        try {
            $userId = $claims['user_id'] ?? null;
            if (!$userId) {
                return $this->error('Unauthorized: No user in token', 401);
            }

            $limit = (int)($query['limit'] ?? 20);
            $offset = (int)($query['offset'] ?? 0);

            // In production, would fetch from token storage
            // For now, return mock tokens
            $tokens = [
                [
                    'token_id' => 'token_' . bin2hex(random_bytes(8)),
                    'client_id' => 'client_123',
                    'scopes' => ['read', 'write'],
                    'created_at' => date('Y-m-d H:i:s', time() - 3600),
                    'last_used' => date('Y-m-d H:i:s', time() - 600),
                    'expires_at' => date('Y-m-d H:i:s', time() + 7200),
                    'revoked' => false
                ],
                [
                    'token_id' => 'token_' . bin2hex(random_bytes(8)),
                    'client_id' => 'client_456',
                    'scopes' => ['read'],
                    'created_at' => date('Y-m-d H:i:s', time() - 86400),
                    'last_used' => date('Y-m-d H:i:s', time() - 7200),
                    'expires_at' => date('Y-m-d H:i:s', time() + 86400),
                    'revoked' => false
                ]
            ];

            // Paginate
            $paginated = array_slice($tokens, $offset, $limit);

            return $this->success([
                'tokens' => array_values($paginated),
                'total' => count($tokens),
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/user/tokens/{token_id}/revoke
     * 
     * Revoke a specific token
     *
     * @param string $tokenId Token ID
     * @param array $claims JWT claims from token
     *
     * @return array JSON response
     */
    public function revokeToken(string $tokenId, array $claims): array
    {
        try {
            $userId = $claims['user_id'] ?? null;
            if (!$userId) {
                return $this->error('Unauthorized: No user in token', 401);
            }

            // In production, would mark token as revoked in storage
            return $this->success([
                'message' => 'Token revoked successfully',
                'revoked_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Helper Methods
     */

    /**
     * Return success response
     *
     * @param array $data Response data
     *
     * @return array JSON response
     */
    private function success(array $data): array
    {
        return [
            'success' => true,
            'data' => $data
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
