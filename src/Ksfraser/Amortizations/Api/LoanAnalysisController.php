<?php
namespace Ksfraser\Amortizations\Api;

use Ksfraser\Amortizations\Authentication\Middleware\AuthenticationMiddleware;

/**
 * LoanAnalysisController: API endpoints for loan analysis
 * 
 * PROTECTED ENDPOINTS (require OAuth2 token):
 * POST /api/v1/loans/analyze        - Analyze loan terms (scope: loan:read)
 * GET /api/v1/loans/rates           - Get interest rates (scope: loan:read)
 * POST /api/v1/loans/compare        - Compare loans (scope: loan:read)
 */
class LoanAnalysisController extends BaseApiController
{
    public function __construct(AuthenticationMiddleware $authMiddleware = null)
    {
        // Configure OAuth2 protection for this controller
        if ($authMiddleware) {
            $this->setAuthMiddleware($authMiddleware);
        }
        
        // All endpoints require loan scopes
        $this->requiresAuthentication = true;
    }

    /**
     * POST /api/v1/loans/analyze
     * Analyze loan parameters
     * 
     * OAuth2 Scope Required: loan:read
     * 
     * Request: { "principal": 100000, "rate": 0.05, "term": 360 }
     */
    public function analyze($request = [], $bearerToken = '')
    {
        try {
            // Verify OAuth2 token for loan:read access
            if ($errorResponse = $this->verifyRequest($bearerToken)) {
                return $errorResponse;
            }

            // Log access for audit
            $this->logAccess('loans.analyze', (array)$request);

            if (isset($request['principal']) && $request['principal'] > 0) {
                return (object)[
                    'success' => true,
                    'statusCode' => 200,
                    'data' => ['analysis' => 'result']
                ];
            } else {
                return (object)[
                    'success' => false,
                    'statusCode' => 422,
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            return (object)[
                'success' => false,
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * GET /api/v1/loans/rates
     * Get current market interest rates
     * 
     * OAuth2 Scope Required: loan:read
     */
    public function getRates($bearerToken = '')
    {
        try {
            // Verify OAuth2 token for loan:read access
            if ($errorResponse = $this->verifyRequest($bearerToken)) {
                return $errorResponse;
            }

            // Log access for audit
            $this->logAccess('loans.getRates', []);

            return (object)[
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    'prime_rate' => 0.05,
                    'average_mortgage_30' => 0.067
                ]
            ];
        } catch (\Exception $e) {
            return (object)[
                'success' => false,
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * POST /api/v1/loans/compare
     * Compare multiple loans
     * 
     * OAuth2 Scope Required: loan:read
     * 
     * Request: { "loans": [...] }
     */
    public function compare($request = [], $bearerToken = '')
    {
        try {
            // Verify OAuth2 token for loan:read access
            if ($errorResponse = $this->verifyRequest($bearerToken)) {
                return $errorResponse;
            }

            // Log access for audit
            $this->logAccess('loans.compare', (array)$request);

            return (object)[
                'success' => true,
                'statusCode' => 200,
                'data' => [
                    'comparisons' => []
                ]
            ];
        } catch (\Exception $e) {
            return (object)[
                'success' => false,
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }
}
