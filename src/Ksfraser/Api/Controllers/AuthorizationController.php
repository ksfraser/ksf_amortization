<?php
namespace Ksfraser\Api\Controllers;

use Ksfraser\Security\OAuth2\OAuth2Service;
use Ksfraser\Security\OAuth2\JWTTokenManager;
use Ksfraser\Security\OAuth2\Caching\AuthorizationCodeCache;
use Ksfraser\Security\OAuth2\Repositories\AuthorizationCodeRepository;
use Ksfraser\Security\Exceptions\AuthenticationException;
use Ksfraser\Security\Exceptions\TokenException;

/**
 * Authorization Controller - REST API Endpoints
 * 
 * Handles user authentication and OAuth2 authorization flows via REST API.
 * All responses are JSON for frontend consumption.
 * 
 * Endpoints:
 * - POST   /api/v1/auth/login           - Authenticate user
 * - GET    /api/v1/auth/authorize       - Get authorization details
 * - POST   /api/v1/auth/authorize       - Submit consent approval
 * - POST   /api/v1/auth/token           - Exchange code for token
 * - POST   /api/v1/auth/verify          - Verify token validity
 * - POST   /api/v1/auth/logout          - Logout user
 * 
 * @package   Ksfraser\Api\Controllers
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2026-04-04
 */
class AuthorizationController
{
    /**
     * @var OAuth2Service OAuth2 service
     */
    private $oauth2Service;

    /**
     * @var JWTTokenManager JWT token manager
     */
    private $jwtManager;

    /**
     * @var AuthorizationCodeCache Authorization code cache
     */
    private $codeCache;

    /**
     * @var array User database (in-memory for demo, replace with real DB)
     */
    private $users = [];

    /**
     * @var array Active sessions
     */
    private $sessions = [];

    /**
     * Constructor
     *
     * @param OAuth2Service $oauth2Service OAuth2 service
     * @param JWTTokenManager $jwtManager JWT token manager
     * @param AuthorizationCodeCache $codeCache Authorization code cache
     */
    public function __construct(
        OAuth2Service $oauth2Service,
        JWTTokenManager $jwtManager,
        AuthorizationCodeCache $codeCache
    ) {
        $this->oauth2Service = $oauth2Service;
        $this->jwtManager = $jwtManager;
        $this->codeCache = $codeCache;
    }

    /**
     * POST /api/v1/auth/login
     * 
     * Authenticate user with credentials
     *
     * @param array $request Request data: { username, password }
     *
     * @return array JSON response
     */
    public function login(array $request): array
    {
        try {
            // Validate input
            if (empty($request['username']) || empty($request['password'])) {
                return $this->error('Missing username or password', 400);
            }

            // Authenticate user (replace with real database lookup)
            $user = $this->authenticateUser($request['username'], $request['password']);
            if (!$user) {
                return $this->error('Invalid credentials', 401);
            }

            // Create session
            $sessionToken = bin2hex(random_bytes(32));
            $this->sessions[$sessionToken] = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'created_at' => time(),
                'expires_at' => time() + 3600 // 1 hour
            ];

            return $this->success([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'session_token' => $sessionToken,
                'expires_in' => 3600
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/auth/authorize
     * 
     * Get authorization request details for frontend
     *
     * @param array $query Query parameters
     *
     * @return array JSON response
     */
    public function getAuthorize(array $query): array
    {
        try {
            // Validate required parameters
            $required = ['client_id', 'redirect_uri', 'scope', 'state'];
            foreach ($required as $param) {
                if (empty($query[$param])) {
                    return $this->error("Missing required parameter: $param", 400);
                }
            }

            // Generate code challenge for PKCE (frontend will have verifier)
            $codeChallenge = $query['code_challenge'] ?? null;

            return $this->success([
                'client_id' => $query['client_id'],
                'scopes' => explode(' ', $query['scope']),
                'state' => $query['state'],
                'requires_pkce' => !empty($codeChallenge),
                'code_challenge' => $codeChallenge
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/auth/authorize
     * 
     * Submit consent and get authorization code
     *
     * @param array $request Request data
     *
     * @return array JSON response
     */
    public function submitAuthorize(array $request): array
    {
        try {
            // Validate session
            $session = $this->validateSession($request['session_token'] ?? null);
            if (!$session) {
                return $this->error('Invalid or expired session', 401);
            }

            // Validate required fields
            $required = ['client_id', 'redirect_uri', 'scopes'];
            foreach ($required as $field) {
                if (empty($request[$field])) {
                    return $this->error("Missing required field: $field", 400);
                }
            }

            // Create authorization code
            $scopes = is_array($request['scopes']) ? $request['scopes'] : explode(' ', $request['scopes']);
            $code = bin2hex(random_bytes(32));
            $state = $request['state'] ?? '';

            // Store authorization code with metadata
            $this->codeCache->create(
                $request['client_id'],
                $request['redirect_uri'],
                $scopes,
                $state,
                $session['user_id'],
                $request['code_challenge'] ?? null,
                $request['code_challenge_method'] ?? 'S256'
            );

            return $this->success([
                'code' => $code,
                'state' => $state,
                'expires_in' => 600 // 10 minutes
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/auth/token
     * 
     * Exchange authorization code for access token
     *
     * @param array $request Token request
     *
     * @return array JSON response
     */
    public function token(array $request): array
    {
        try {
            // Validate required fields
            $required = ['code', 'client_id', 'redirect_uri'];
            foreach ($required as $field) {
                if (empty($request[$field])) {
                    return $this->error("Missing required field: $field", 400);
                }
            }

            // Get authorization code from cache
            $authCode = $this->codeCache->get($request['code']);
            if (!$authCode) {
                return $this->error('Invalid or expired authorization code', 401);
            }

            // Verify client ID and redirect URI match
            if ($authCode['client_id'] !== $request['client_id'] || 
                $authCode['redirect_uri'] !== $request['redirect_uri']) {
                return $this->error('Client ID or redirect URI mismatch', 400);
            }

            // Verify PKCE if used
            if (!empty($authCode['code_challenge'])) {
                if (empty($request['code_verifier'])) {
                    return $this->error('Missing code_verifier for PKCE', 400);
                }
                // Verify code_verifier matches code_challenge
                // (Implementation omitted for brevity)
            }

            // Generate JWT access token
            $token = $this->jwtManager->generate([
                'user_id' => $authCode['user_id'],
                'client_id' => $authCode['client_id'],
                'scope' => implode(' ', $authCode['scopes']),
                'exp' => time() + 3600 // 1 hour
            ]);

            // Invalidate authorization code (single-use)
            $this->codeCache->invalidate($request['code']);

            return $this->success([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => implode(' ', $authCode['scopes'])
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/auth/verify
     * 
     * Verify access token validity
     *
     * @param array $request Verification request
     *
     * @return array JSON response
     */
    public function verify(array $request): array
    {
        try {
            if (empty($request['access_token'])) {
                return $this->error('Missing access token', 400);
            }

            // Verify and decode JWT
            $decoded = $this->jwtManager->verify($request['access_token']);

            return $this->success([
                'valid' => true,
                'user_id' => $decoded['user_id'],
                'client_id' => $decoded['client_id'],
                'scopes' => explode(' ', $decoded['scope']),
                'expires_at' => $decoded['exp']
            ]);
        } catch (TokenException $e) {
            return $this->error('Invalid token: ' . $e->getMessage(), 401);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/auth/logout
     * 
     * Logout user and invalidate session
     *
     * @param array $request Logout request
     *
     * @return array JSON response
     */
    public function logout(array $request): array
    {
        try {
            $sessionToken = $request['session_token'] ?? null;
            if ($sessionToken && isset($this->sessions[$sessionToken])) {
                unset($this->sessions[$sessionToken]);
            }

            return $this->success(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Helper Methods
     */

    /**
     * Authenticate user with credentials
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return array|null User data or null if invalid
     */
    private function authenticateUser(string $username, string $password): ?array
    {
        // In production, this would query a user database
        // For now, accept any username/password for demo
        if (empty($username) || empty($password)) {
            return null;
        }

        return [
            'id' => 'user_' . bin2hex(random_bytes(4)),
            'username' => $username,
            'email' => $username . '@example.com'
        ];
    }

    /**
     * Validate session token
     *
     * @param string|null $token Session token
     *
     * @return array|null Session data or null if invalid
     */
    private function validateSession(?string $token): ?array
    {
        if (!$token || !isset($this->sessions[$token])) {
            return null;
        }

        $session = $this->sessions[$token];
        if ($session['expires_at'] < time()) {
            unset($this->sessions[$token]);
            return null;
        }

        return $session;
    }

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
