<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Phase 1: Request ID Middleware
 * Generates unique request IDs for tracing across all systems
 */
class RequestIdMiddleware
{
    /**
     * Generate and attach unique request ID
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if request ID already exists (from load balancer or client)
        $requestId = $request->header('X-Request-ID');

        if (!$requestId) {
            $requestId = 'REQ-' . now()->format('YmdHis') . '-' . uniqid();
        }

        // Store in request context
        $request->merge(['request_id' => $requestId]);

        // Process request
        $response = $next($request);

        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
