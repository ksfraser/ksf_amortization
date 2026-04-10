<?php

namespace Ksfraser\Amortizations\Api;

use Ksfraser\Amortizations\Authentication\AuthenticationService;
use Ksfraser\Amortizations\Authentication\TokenManager;
use Ksfraser\Amortizations\Authentication\Client;
use Ksfraser\Amortizations\Authentication\InvalidTokenException;
use Ksfraser\Amortizations\Repositories\ClientRepository;

/**
 * AuthController - OAuth2 Authentication Endpoints
 *
 * Implements OAuth2 token endpoint and related authentication operations.
 *
 * ### Endpoints
 *
 * **POST /api/v1/auth/token** - OAuth2 Token Endpoint (Client Credentials)
 * - Request: { client_id, client_secret, scope, grant_type }
 * - Response: { access_token, refresh_token, token_type, expires_in, scope }
 * - Status: 200 OK, 400 Bad Request, 401 Unauthorized
 *
 * **POST /api/v1/auth/refresh** - Refresh Token Endpoint
 * - Request: { refresh_token }
 * - Response: { access_token, token_type, expires_in }
 * - Status: 200 OK, 401 Unauthorized
 *
 * **POST /api/v1/auth/revoke** - Token Revocation Endpoint
 * - Request: { token, client_id (optional) }
 * - Response: { message }
 * - Status: 200 OK
 *
 * **GET /api/v1/auth/scopes** - List Available Scopes
 * - Response: { scopes: [...] }
 * - Status: 200 OK
 *
 * @package Ksfraser\Amortizations\Api
 * @author  KSF Development Team
 * @version 1.0.0
 * @since   18.0.0
 */
class AuthController extends BaseApiController
{
    /**
     * Authentication service
     *
     * @var AuthenticationService
     */
    private $authService;

    /**
     * Token manager
     *
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * Client repository
     *
     * @var ClientRepository
     */
    private $clientRepo;

    /**
     * Constructor
     *
     * @param AuthenticationService $authService Authentication service
     * @param TokenManager          $tokenManager Token manager
     * @param ClientRepository|null $clientRepo   Client repository
     */
    public function __construct(
        AuthenticationService $authService,
        TokenManager $tokenManager,
        ClientRepository $clientRepo = null
    ) {
        $this->authService = $authService;
        $this->tokenManager = $tokenManager;
        $this->clientRepo = $clientRepo ?? new ClientRepository();
    }

    /**
     * POST /api/v1/auth/token
     *
     * OAuth2 Token Endpoint - Client Credentials Grant
     *
     * ### Request Format
     * ```json
     * {
     *     "client_id": "app_id",
     *     "client_secret": "app_secret",
     *     "scope": "loan:read schedule:read",
     *     "grant_type": "client_credentials"
     * }
     * ```
     *
     * ### Response Format (Success: 200)
     * ```json
     * {
     *     "access_token": "eyJ...",
     *     "refresh_token": "eyJ...",
     *     "token_type": "Bearer",
     *     "expires_in": 3600,
     *     "scope": "loan:read schedule:read"
     * }
     * ```
     *
     * ### Error Responses
     * - 400 Bad Request: Missing/invalid parameters
     * - 401 Unauthorized: Invalid credentials
     * - 403 Forbidden: Insufficient permissions
     *
     * @param array $requestData Request body
     *
     * @return ApiResponse OAuth2 token response
     */
    public function token(array $requestData = []): ApiResponse
    {
        try {
            // Validate required fields
            $clientId = $requestData['client_id'] ?? null;
            $clientSecret = $requestData['client_secret'] ?? null;
            $requestedScopes = $requestData['scope'] ?? '';
            $grantType = $requestData['grant_type'] ?? 'client_credentials';

            if (!$clientId || !$clientSecret) {
                return ApiResponse::badRequest('Missing client credentials', [
                    'required' => ['client_id', 'client_secret'],
                ]);
            }

            if ($grantType !== 'client_credentials') {
                return ApiResponse::badRequest('Grant type not supported', [
                    'supported' => ['client_credentials'],
                ]);
            }

            // Parse scopes
            $scopes = array_filter(explode(' ', $requestedScopes));

            if (empty($scopes)) {
                return ApiResponse::badRequest('At least one scope required', [
                    'example' => 'loan:read schedule:read',
                ]);
            }

            // Authenticate client
            $client = $this->authenticateClient($clientId, $clientSecret);

            if (!$client) {
                // Log failed attempt for audit/security
                return ApiResponse::unauthorized('Invalid client credentials');
            }

            // Generate token pair
            $tokenResponse = $this->tokenManager->generateTokenPair($client, $scopes);

            return ApiResponse::success($tokenResponse, 'Tokens generated successfully', 200);
        } catch (InvalidTokenException $e) {
            return ApiResponse::badRequest($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::serverError('Token generation failed: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/v1/auth/refresh
     *
     * Refresh Token Endpoint
     *
     * Exchange expired access token using refresh token.
     *
     * ### Request Format
     * ```json
     * {
     *     "refresh_token": "eyJ..."
     * }
     * ```
     *
     * ### Response Format (Success: 200)
     * ```json
     * {
     *     "access_token": "eyJ...",
     *     "token_type": "Bearer",
     *     "expires_in": 3600
     * }
     * ```
     *
     * @param array $requestData Request body
     *
     * @return ApiResponse New access token
     */
    public function refresh(array $requestData = []): ApiResponse
    {
        try {
            $refreshToken = $requestData['refresh_token'] ?? null;

            if (!$refreshToken) {
                return ApiResponse::badRequest('refresh_token required');
            }

            // Validate refresh token
            $token = $this->authService->validateToken($refreshToken);

            if ($token->getType() !== \Ksfraser\Amortizations\Authentication\Token::TYPE_REFRESH) {
                return ApiResponse::badRequest('Invalid token type');
            }

            // Get client
            $client = new Client(
                $token->getClientId(),
                '' // Secret not needed for refresh
            );

            // Generate new access token
            $result = $this->tokenManager->refreshAccessToken($client, $refreshToken);

            return ApiResponse::success($result, 'Token refreshed successfully', 200);
        } catch (InvalidTokenException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::serverError('Token refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/v1/auth/revoke
     *
     * Token Revocation Endpoint
     *
     * Revoke access or refresh token. After revocation, token cannot be used.
     *
     * ### Request Format
     * ```json
     * {
     *     "token": "eyJ...",
     *     "client_id": "app_id"
     * }
     * ```
     *
     * ### Response Format (Success: 200)
     * ```json
     * {
     *     "message": "Token revoked successfully"
     * }
     * ```
     *
     * @param array $requestData Request body
     *
     * @return ApiResponse Revocation response
     */
    public function revoke(array $requestData = []): ApiResponse
    {
        try {
            $tokenString = $requestData['token'] ?? null;
            $clientId = $requestData['client_id'] ?? null;

            if (!$tokenString) {
                return ApiResponse::badRequest('token required');
            }

            // Validate and get token info
            $token = $this->authService->validateToken($tokenString);

            // Revoke token
            $this->tokenManager->revokeToken(
                $token->getJti(),
                'User initiated revocation',
                $clientId ?? $token->getClientId()
            );

            return ApiResponse::success(
                ['message' => 'Token revoked successfully'],
                'Token revoked',
                200
            );
        } catch (InvalidTokenException $e) {
            // Per OAuth2 spec, revoke should succeed even for invalid tokens
            return ApiResponse::success(
                ['message' => 'Token revoked'],
                'Token revoked',
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::serverError('Token revocation failed: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Logout Endpoint - Revoke All Client Tokens
     *
     * Called after authentication middleware validates token.
     *
     * ### Request Format
     * Authorization: Bearer <access_token>
     *
     * ### Response Format (Success: 200)
     * ```json
     * {
     *     "message": "Logged out successfully",
     *     "tokens_revoked": 5
     * }
     * ```
     *
     * @param string $clientId Client ID (from authenticated context)
     *
     * @return ApiResponse Logout response
     */
    public function logout(string $clientId): ApiResponse
    {
        try {
            $count = $this->tokenManager->revokeClientTokens($clientId, 'User logout');

            return ApiResponse::success(
                [
                    'message' => 'Logged out successfully',
                    'tokens_revoked' => $count,
                ],
                'Logout successful',
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::serverError('Logout failed: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/auth/scopes
     *
     * List Available Scopes
     *
     * Returns all available scopes for API access.
     *
     * ### Response Format
     * ```json
     * {
     *     "scopes": [
     *         {
     *             "name": "loan:read",
     *             "category": "loan",
     *             "description": "Read loan information",
     *             "tier": "basic"
     *         },
     *         ...
     *     ]
     * }
     * ```
     *
     * @return ApiResponse List of scopes
     */
    public function listScopes(): ApiResponse
    {
        try {
            // Get all scopes from ScopeManager
            $scopeManager = new \Ksfraser\Amortizations\Authentication\ScopeManager();
            $allScopes = $scopeManager->getAllScopes();

            $scopeList = [];
            foreach ($allScopes as $name => $metadata) {
                $scopeList[] = array_merge(['name' => $name], $metadata);
            }

            return ApiResponse::success(
                ['scopes' => $scopeList],
                'Scopes retrieved successfully',
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve scopes: ' . $e->getMessage());
        }
    }

    /**
     * Authenticate client using credentials
     *
     * @param string $clientId     Client ID
     * @param string $clientSecret Client secret
     *
     * @return Client|null Authenticated client or null
     */
    private function authenticateClient(string $clientId, string $clientSecret): ?Client
    {
        try {
            // Lookup client in repository
            $clientData = $this->clientRepo->findById($clientId);

            if (!$clientData) {
                return null;
            }

            // Create client object
            $client = new Client(
                $clientData['id'],
                $clientData['secret'],
                $clientData['name'] ?? $clientId
            );

            // Verify secret
            if (!hash_equals($clientData['secret'], $clientSecret)) {
                return null;
            }

            // Check if client is active
            if (!$client->isActive()) {
                return null;
            }

            return $client;
        } catch (\Exception $e) {
            return null; // Fail closed on errors
        }
    }
}
