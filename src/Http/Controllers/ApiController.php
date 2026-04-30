<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Infrastructure\Logging\StructuredLogger;

/**
 * Phase 1: Base API Controller
 * Provides common functionality for all API endpoints
 */
abstract class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected StructuredLogger $logger;

    public function __construct(StructuredLogger $logger)
    {
        $this->logger = $logger;
        $this->middleware('auth:sanctum')->except(['store', 'index']);
    }

    /**
     * Get authenticated user
     */
    protected function getUser()
    {
        return auth()->user();
    }

    /**
     * Check user authorization
     */
    protected function authorize(string $action): bool
    {
        if (!$this->getUser()->canPerformAction($action)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'You are not authorized to perform this action'
            );
        }
        return true;
    }

    /**
     * Log API activity
     */
    protected function logActivity(string $eventType, array $details = []): void
    {
        $this->logger->logBusinessEvent(
            $eventType,
            class_basename(static::class),
            $this->getUser()->id ?? 0,
            $details
        );
    }
}
